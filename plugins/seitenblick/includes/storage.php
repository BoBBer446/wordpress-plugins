<?php
/**
 * Seitenblick Statistik.
 *
 * @package Seitenblick
 * @license GPL-2.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
function sbk_settings(): array {
	$raw = get_option( 'sbk_settings', array() );
	return array_merge(
		array(
			'enabled'             => true,
			'browser'             => false,
			'retention'           => 90,
			'delete_on_uninstall' => false,
			'texts'               => array(),
			'enabled_services'    => array(),
			'service_overrides'   => array(),
			'ip_blacklist'        => array(),
			'trusted_proxies'     => array(),
		),
		is_array( $raw ) ? $raw : array()
	);
}
function sbk_tables(): array {
	global $wpdb;
	return array( $wpdb->prefix . 'sbk_totals', $wpdb->prefix . 'sbk_guard' );
}
function sbk_schema_sql(): array {
	global $wpdb;
	[$totals, $guard] = sbk_tables();
	$collation        = $wpdb->get_charset_collate();
	return array(
		"CREATE TABLE $totals (
            day date NOT NULL,
            hour tinyint unsigned NOT NULL,
            page_hash char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            path varchar(384) NOT NULL,
            server_views bigint unsigned NOT NULL DEFAULT 0,
            browser_views bigint unsigned NOT NULL DEFAULT 0,
            timed_views bigint unsigned NOT NULL DEFAULT 0,
            active_seconds bigint unsigned NOT NULL DEFAULT 0,
            engaged_views bigint unsigned NOT NULL DEFAULT 0,
            mobile bigint unsigned NOT NULL DEFAULT 0,
            desktop bigint unsigned NOT NULL DEFAULT 0,
            other_device bigint unsigned NOT NULL DEFAULT 0,
            direct bigint unsigned NOT NULL DEFAULT 0,
            search_source bigint unsigned NOT NULL DEFAULT 0,
            social bigint unsigned NOT NULL DEFAULT 0,
            internal_source bigint unsigned NOT NULL DEFAULT 0,
            other_source bigint unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (day,hour,page_hash),
            KEY page_hash (page_hash)
        ) ENGINE=InnoDB $collation;",
		"CREATE TABLE $guard (
            token_hash char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            kind varchar(12) NOT NULL,
            path varchar(384) NOT NULL DEFAULT '',
            day date NOT NULL,
            hour tinyint unsigned NOT NULL DEFAULT 0,
            started bigint unsigned NOT NULL,
            expires bigint unsigned NOT NULL,
            seconds int unsigned NOT NULL DEFAULT 0,
            sequence int unsigned NOT NULL DEFAULT 0,
            hits int unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (token_hash),
            KEY expires (expires)
        ) ENGINE=InnoDB $collation;",
	);
}
function sbk_activate(): void {
	if ( is_multisite() ) {
		wp_die( esc_html__( 'Diese Version unterstützt einzelne WordPress-Websites. Eine Netzwerkaktivierung ist nicht vorgesehen.', 'seitenblick' ) );
	}
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	foreach ( sbk_schema_sql() as $sql ) {
		dbDelta( $sql );
	}
	update_option( 'sbk_schema', SBK_VERSION, false );
	if ( ! get_option( 'sbk_started' ) ) {
		update_option( 'sbk_started', gmdate( 'c' ), false );
	}
	if ( ! wp_next_scheduled( 'sbk_cleanup' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'sbk_cleanup' );
	}
}
function sbk_bucket( ?int $now = null ): array {
	$stamp = new DateTimeImmutable( '@' . ( $now ?? time() ) );
	$stamp = $stamp->setTimezone( wp_timezone() );
	return array( $stamp->format( 'Y-m-d' ), (int) $stamp->format( 'G' ) );
}
function sbk_add( string $path, array $values, ?array $bucket = null ): bool {
	global $wpdb;
	[$table] = sbk_tables();
	$allowed = array( 'server_views', 'browser_views', 'timed_views', 'active_seconds', 'engaged_views', 'mobile', 'desktop', 'other_device', 'direct', 'search_source', 'social', 'internal_source', 'other_source' );
	if ( ! $values || strlen( $path ) > 384 || array_diff( array_keys( $values ), $allowed ) ) {
		return false;
	}
	foreach ( $values as $v ) {
		if ( ! is_int( $v ) || $v < 0 || $v > 10000 ) {
			return false;
		}
	}
	[$day, $hour] = $bucket ?? sbk_bucket();
	$columns      = implode( ',', array_fill( 0, count( $values ), '%i' ) );
	$formats      = implode( ',', array_fill( 0, count( $values ), '%d' ) );
	$updates      = implode( ',', array_fill( 0, count( $values ), '%i=%i+VALUES(%i)' ) );
	$arguments    = array_merge( array( $table ), array_keys( $values ), array( $day, $hour, hash( 'sha256', $path ), $path ), array_values( $values ) );
	foreach ( array_keys( $values ) as $column ) {
		array_push( $arguments, $column, $column, $column );
	}
    // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Variable fragments contain only fixed placeholder tokens; every identifier and value is supplied separately.
	return $wpdb->query( $wpdb->prepare( "INSERT INTO %i (day,hour,page_hash,path,$columns) VALUES (%s,%d,%s,%s,$formats) ON DUPLICATE KEY UPDATE $updates", $arguments ) ) !== false;
}
/** Global volume limits. No IP, IP hash, browser fingerprint or visitor key. */
function sbk_limit( string $scope, int $limit, int $window = 60, ?int $now = null ): bool {
	global $wpdb;
	[, $table]    = sbk_tables();
	$now          = $now ?? time();
	$key          = hash( 'sha256', $scope . ':' . (int) floor( $now / $window ) );
	[$day, $hour] = sbk_bucket( $now );
	if ( $wpdb->query( $wpdb->prepare( "INSERT INTO %i (token_hash,kind,day,hour,started,expires,hits) VALUES (%s,'limit',%s,%d,%d,%d,1) ON DUPLICATE KEY UPDATE hits=IF(hits<%d,hits+1,hits)", $table, $key, $day, $hour, $now, $now + 2 * $window, $limit + 1 ) ) === false ) {
		return false;
	}
	$hits = $wpdb->get_var( $wpdb->prepare( 'SELECT hits FROM %i WHERE token_hash=%s', $table, $key ) );
	return $hits !== null && (int) $hits <= $limit;
}
function sbk_cleanup(): void {
	global $wpdb;
	[$totals, $guard] = sbk_tables();
	$retention        = max( 7, min( 365, (int) sbk_settings()['retention'] ) );
	// Hourly cron is also invoked by the site's independent maintenance timer.
	$cutoff = ( new DateTimeImmutable( 'now', wp_timezone() ) )->modify( '-' . ( $retention - 1 ) . ' days' )->format( 'Y-m-d' );
	$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE expires < %d', $guard, time() ) );
	$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE day < %s', $totals, $cutoff ) );
}
function sbk_privacy_text(): string {
	return __(
		'Wir nutzen Seitenblick für eine lokale Statistik. Allgemeine Aufrufe öffentlich verfügbarer Seiten werden auf unserem Server als Summen je Seite, Tag und Stunde gezählt. Bekannte Bots, Betriebsprüfungen, angemeldete Benutzer und Anfragen mit Do Not Track oder Global Privacy Control werden ausgeschlossen. Grobe Geräteklassen und Herkunftskategorien werden nur als Summen gespeichert. Eine gespeicherte Ablehnung der Statistik wird bei weiteren Seitenabrufen respektiert. Es gibt keine Besucherprofile und keine Zählung eindeutig identifizierter Personen. URL-Parameter, Suchtexte, Namen, IP-Adressen und vollständige Browserkennungen werden in der Statistik nicht gespeichert. Der Webserver verarbeitet IP-Adressen technisch zur Übertragung; seine Protokolle und Backups sind gesondert zu berücksichtigen.

Nur nach freiwilliger Zustimmung messen wir zusätzlich Ansichten und aktive Zeit im Browser. Die Auswahl wird auf ausdrücklichen Wunsch in einem signierten, gegen JavaScript-Zugriff geschützten Einstellungs-Cookie bis zu 90 Tage gespeichert. Es enthält nur Kategorien, Alters-/Sorgeberechtigungsbestätigung, Richtlinienversion und taggenaue Gültigkeit, keine zufällige Besucherkennung. Es werden keine Statistik-Cookies und keine Einträge in localStorage oder sessionStorage angelegt. Über „Datenschutz-Auswahl“ kann die Freigabe jederzeit zurückgenommen werden. Ein zufälliger Beleg gehört nur zu einer einzelnen Seitenansicht, verknüpft keine Seiten miteinander und verfällt nach 30 Minuten. Er wird beim Widerruf gelöscht; abgelaufene Belege werden durch die stündliche Bereinigung entfernt. Ohne funktionierende regelmäßige Wartung können abgelaufene Daten länger gespeichert bleiben. Erfasst wird sichtbare, fokussierte Zeit bis 60 Sekunden nach der letzten Interaktion; Zeitmessung ist eine Näherung und auf 30 Minuten je Ansicht begrenzt. Einwilligung ist nur für Personen ab 16 Jahren oder durch Sorgeberechtigte vorgesehen. Es erfolgt keine technische Altersprüfung.

Die Summen werden standardmäßig 90 Tage aufbewahrt; die tatsächliche Einstellung und Betreiberinformationen stehen in unseren Datenschutzhinweisen. Alte Sicherungen können Daten länger enthalten. Seitenblick sendet seine Statistik nicht an externe Dienste. Freigegebene Fremdinhalte und Fremdskripte können Daten an deren Anbieter übertragen; Dienste und Zweck sind gesondert anzugeben. Von WordPress eingereihte externe Skripte und Stylesheets werden vor Freigabe blockiert; hartkodierte Skripte, Inline-Code, Bilder und fremder Plugin-Code benötigen eine zusätzliche Prüfung und gezielte Integration. Bereits zusammengefasste Werte lassen sich keiner einzelnen Person mehr zuordnen. Rechtsgrundlage, Interessenabwägung der allgemeinen Serverzählung, Einwilligungsvorgang und Betreiberpflichten sind vom Betreiber anhand des konkreten Einsatzes zu prüfen.',
		'seitenblick'
	);
}
