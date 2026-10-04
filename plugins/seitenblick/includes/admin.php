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
function sbk_admin_permission( WP_REST_Request $request ) {
	return current_user_can( 'manage_options' ) && wp_verify_nonce( $request->get_header( 'x-wp-nonce' ), 'wp_rest' )
		? true : new WP_Error( 'sbk_forbidden', __( 'Diese Statistik ist nur für Administratoren verfügbar.', 'seitenblick' ), array( 'status' => 403 ) );
}
function sbk_days( $value ): ?int {
	return is_scalar( $value ) && preg_match( '/^(7|30|90|365)$/D', (string) $value ) ? (int) $value : null;
}
function sbk_report( int $days ): array {
	global $wpdb;
	[$table]       = sbk_tables();
	$today         = new DateTimeImmutable( 'today', wp_timezone() );
	$end           = $today->format( 'Y-m-d' );
	$start         = $today->modify( '-' . ( $days - 1 ) . ' days' )->format( 'Y-m-d' );
	$previousStart = $today->modify( '-' . ( 2 * $days - 1 ) . ' days' )->format( 'Y-m-d' );
	$previousEnd   = $today->modify( '-' . $days . ' days' )->format( 'Y-m-d' );
	$columns       = array( 'server_views', 'browser_views', 'timed_views', 'active_seconds', 'engaged_views', 'mobile', 'desktop', 'other_device', 'direct', 'search_source', 'social', 'internal_source', 'other_source' );
	$current       = $wpdb->get_row( $wpdb->prepare( 'SELECT COALESCE(SUM(server_views),0) AS server_views,COALESCE(SUM(browser_views),0) AS browser_views,COALESCE(SUM(timed_views),0) AS timed_views,COALESCE(SUM(active_seconds),0) AS active_seconds,COALESCE(SUM(engaged_views),0) AS engaged_views,COALESCE(SUM(mobile),0) AS mobile,COALESCE(SUM(desktop),0) AS desktop,COALESCE(SUM(other_device),0) AS other_device,COALESCE(SUM(direct),0) AS direct,COALESCE(SUM(search_source),0) AS search_source,COALESCE(SUM(social),0) AS social,COALESCE(SUM(internal_source),0) AS internal_source,COALESCE(SUM(other_source),0) AS other_source FROM %i WHERE day BETWEEN %s AND %s', $table, $start, $end ), ARRAY_A ) ?: array();
	$previous      = $wpdb->get_row( $wpdb->prepare( 'SELECT COALESCE(SUM(server_views),0) AS server_views,COALESCE(SUM(browser_views),0) AS browser_views,COALESCE(SUM(timed_views),0) AS timed_views,COALESCE(SUM(active_seconds),0) AS active_seconds,COALESCE(SUM(engaged_views),0) AS engaged_views,COALESCE(SUM(mobile),0) AS mobile,COALESCE(SUM(desktop),0) AS desktop,COALESCE(SUM(other_device),0) AS other_device,COALESCE(SUM(direct),0) AS direct,COALESCE(SUM(search_source),0) AS search_source,COALESCE(SUM(social),0) AS social,COALESCE(SUM(internal_source),0) AS internal_source,COALESCE(SUM(other_source),0) AS other_source FROM %i WHERE day BETWEEN %s AND %s', $table, $previousStart, $previousEnd ), ARRAY_A ) ?: array();
	$daily         = $wpdb->get_results( $wpdb->prepare( 'SELECT day, COALESCE(SUM(server_views),0) AS server_views,COALESCE(SUM(browser_views),0) AS browser_views,COALESCE(SUM(timed_views),0) AS timed_views,COALESCE(SUM(active_seconds),0) AS active_seconds,COALESCE(SUM(engaged_views),0) AS engaged_views,COALESCE(SUM(mobile),0) AS mobile,COALESCE(SUM(desktop),0) AS desktop,COALESCE(SUM(other_device),0) AS other_device,COALESCE(SUM(direct),0) AS direct,COALESCE(SUM(search_source),0) AS search_source,COALESCE(SUM(social),0) AS social,COALESCE(SUM(internal_source),0) AS internal_source,COALESCE(SUM(other_source),0) AS other_source FROM %i WHERE day BETWEEN %s AND %s GROUP BY day ORDER BY day', $table, $start, $end ), ARRAY_A ) ?: array();
	$rows          = $wpdb->get_results( $wpdb->prepare( 'SELECT path, COALESCE(SUM(server_views),0) AS server_views,COALESCE(SUM(browser_views),0) AS browser_views,COALESCE(SUM(timed_views),0) AS timed_views,COALESCE(SUM(active_seconds),0) AS active_seconds,COALESCE(SUM(engaged_views),0) AS engaged_views,COALESCE(SUM(mobile),0) AS mobile,COALESCE(SUM(desktop),0) AS desktop,COALESCE(SUM(other_device),0) AS other_device,COALESCE(SUM(direct),0) AS direct,COALESCE(SUM(search_source),0) AS search_source,COALESCE(SUM(social),0) AS social,COALESCE(SUM(internal_source),0) AS internal_source,COALESCE(SUM(other_source),0) AS other_source FROM %i WHERE day BETWEEN %s AND %s GROUP BY path ORDER BY SUM(server_views)+SUM(browser_views) DESC,path ASC LIMIT 1000', $table, $start, $end ), ARRAY_A ) ?: array();
	$hours         = $wpdb->get_results( $wpdb->prepare( 'SELECT day,hour,SUM(server_views) AS views FROM %i WHERE day BETWEEN %s AND %s GROUP BY day,hour ORDER BY day,hour', $table, $start, $end ), ARRAY_A ) ?: array();
	$routes        = sbk_routes();
	foreach ( $rows as &$row ) {
		$row['title'] = $routes[ $row['path'] ]['title'] ?? __( 'Ehemalige öffentliche Seite', 'seitenblick' );
		$row['group'] = $routes[ $row['path'] ]['group'] ?? __( 'Website', 'seitenblick' );
		foreach ( $columns as $col ) {
			$row[ $col ] = (int) $row[ $col ];
		}
		$row['average'] = $row['timed_views'] ? round( $row['active_seconds'] / $row['timed_views'], 1 ) : null;
	}
	unset( $row );
	foreach ( $columns as $col ) {
		$current[ $col ]  = (int) ( $current[ $col ] ?? 0 );
		$previous[ $col ] = (int) ( $previous[ $col ] ?? 0 );
	}
	$oldest = $wpdb->get_var( $wpdb->prepare( 'SELECT MIN(day) FROM %i', $table ) );
	return array(
		'days'          => $days,
		'start'         => $start,
		'end'           => $end,
		'previousStart' => $previousStart,
		'previousEnd'   => $previousEnd,
		'current'       => $current,
		'previous'      => $previous,
		'daily'         => $daily,
		'pages'         => $rows,
		'hours'         => $hours,
		'started'       => get_option( 'sbk_started' ),
		'oldest'        => $oldest,
		'settings'      => sbk_settings(),
		'timezone'      => wp_timezone_string(),
		'limits'        => array(
			'maxPages'    => 1000,
			'viewSeconds' => 1800,
		),
		'generated'     => gmdate( 'c' ),
	);
}
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'seitenblick/v1',
			'/stats',
			array(
				'methods'             => 'GET',
				'permission_callback' => 'sbk_admin_permission',
				'callback'            => function ( WP_REST_Request $request ) {
					$days = sbk_days( $request->get_param( 'days' ) ?? 7 );
					if ( $days === null ) {
						return new WP_Error( 'sbk_days', __( 'Ungültiger Zeitraum.', 'seitenblick' ), array( 'status' => 400 ) );
					}
					return sbk_response( sbk_report( $days ) );
				},
			)
		);
	}
);
add_action(
	'admin_menu',
	function () {
		add_menu_page( __( 'Seitenblick Statistik', 'seitenblick' ), __( 'Seitenblick', 'seitenblick' ), 'manage_options', 'seitenblick', 'sbk_admin_page', 'dashicons-chart-area', 58 );
	}
);
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( $hook !== 'toplevel_page_seitenblick' ) {
			return;
		}
		wp_enqueue_style( 'seitenblick-admin', plugins_url( 'assets/admin.css', SBK_FILE ), array(), SBK_VERSION );
		wp_enqueue_script( 'seitenblick-admin', plugins_url( 'assets/admin.js', SBK_FILE ), array(), SBK_VERSION, true );
		wp_localize_script(
			'seitenblick-admin',
			'seitenblickAdmin',
			array(
				'strings'   => sbk_admin_strings(),
			'url'       => add_query_arg( 'rest_route', '/seitenblick/v1/stats', home_url( '/' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'exportUrl' => wp_nonce_url( admin_url( 'admin-post.php?action=sbk_export' ), 'sbk_export' ),
			)
		);
	}
);
function sbk_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Zugriff nicht erlaubt.', 'seitenblick' ) );
	}
	$settings = sbk_settings();
	?>
	<div class="wrap" id="sbk-app">
		<div class="sbk-topline"><span class="sbk-brand"><span aria-hidden="true">◒</span> <?php esc_html_e( 'Seitenblick', 'seitenblick' ); ?></span><span class="sbk-local">● <?php esc_html_e( 'Lokal auf deinem Server', 'seitenblick' ); ?></span></div>
		<header class="sbk-hero">
			<div><p class="sbk-eyebrow"><?php esc_html_e( 'DEINE WEBSITE IN ZAHLEN', 'seitenblick' ); ?></p><h1><?php echo esc_html( sbk_text( 'dashboard_title' ) ); ?></h1><p><?php echo esc_html( sbk_text( 'dashboard_intro' ) ); ?></p></div>
			<div class="sbk-hero-art" aria-hidden="true"><span>▥</span><i></i><b>↗</b></div>
		</header>
		<div class="sbk-toolbar">
			<div class="sbk-periods" role="group" aria-label="<?php esc_attr_e( 'Zeitraum', 'seitenblick' ); ?>">
				<?php
				foreach ( array( 7, 30, 90, 365 ) as $days ) {
					?>
					<button type="button" data-sbk-days="<?php echo (int) $days; ?>" aria-pressed="<?php echo $days === 7 ? 'true' : 'false'; ?>">
					<?php
					/* translators: %d: Number of days in the selected reporting period. */
					echo esc_html( sprintf( __( '%d Tage', 'seitenblick' ), $days ) );
					?>
					</button><?php } ?>
			</div>
			<div class="sbk-actions"><button type="button" class="button" id="sbk-demo"><?php esc_html_e( 'Beispieldaten ansehen', 'seitenblick' ); ?></button><a class="button" id="sbk-export" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sbk_export&days=7' ), 'sbk_export' ) ); ?>"><?php esc_html_e( 'CSV exportieren', 'seitenblick' ); ?></a></div>
		</div>
		<p id="sbk-status" role="status" aria-live="polite"><?php esc_html_e( 'Statistik wird geladen …', 'seitenblick' ); ?></p>
		<div id="sbk-demo-note" class="sbk-notice" hidden><?php esc_html_e( 'Vorschau: Diese Zahlen sind erfundene Beispieldaten. Sie werden nicht gespeichert.', 'seitenblick' ); ?></div>
		<div class="sbk-kpis">
			<article class="sbk-kpi sbk-violet"><span class="sbk-kpi-icon" aria-hidden="true">↗</span><h2><?php esc_html_e( 'Seitenaufrufe', 'seitenblick' ); ?></h2><strong id="sbk-views">—</strong><p id="sbk-change">—</p><small><?php esc_html_e( 'Serverzählung · bekannte Bots gefiltert', 'seitenblick' ); ?></small></article>
			<article class="sbk-kpi sbk-blue"><span class="sbk-kpi-icon" aria-hidden="true">▤</span><h2><?php esc_html_e( 'Gemessene Ansichten', 'seitenblick' ); ?></h2><strong id="sbk-browser">—</strong><p><?php esc_html_e( 'Seiten & App-Navigation', 'seitenblick' ); ?></p><small><?php esc_html_e( 'Nur mit freiwilliger Einwilligung', 'seitenblick' ); ?></small></article>
			<article class="sbk-kpi sbk-peach"><span class="sbk-kpi-icon" aria-hidden="true">◷</span><h2><?php esc_html_e( 'Ø aktive Zeit', 'seitenblick' ); ?></h2><strong id="sbk-average">—</strong><p id="sbk-timed">—</p><small><?php esc_html_e( 'Sichtbar, fokussiert & kürzlich aktiv', 'seitenblick' ); ?></small></article>
			<article class="sbk-kpi sbk-mint"><span class="sbk-kpi-icon" aria-hidden="true">✦</span><h2><?php esc_html_e( 'Länger verweilt', 'seitenblick' ); ?></h2><strong id="sbk-engaged">—</strong><p><?php esc_html_e( 'Mindestens 30 aktive Sekunden', 'seitenblick' ); ?></p><small><?php esc_html_e( 'Anteil an den gemessenen Ansichten', 'seitenblick' ); ?></small></article>
		</div>
		<div class="sbk-main-grid">
			<section class="sbk-panel sbk-trend"><div class="sbk-panel-heading"><div><p class="sbk-eyebrow"><?php esc_html_e( 'ENTWICKLUNG', 'seitenblick' ); ?></p><h2><?php esc_html_e( 'Aufrufe im Verlauf', 'seitenblick' ); ?></h2></div><span class="sbk-key"><i></i><?php esc_html_e( 'Seitenaufrufe', 'seitenblick' ); ?></span></div><div id="sbk-chart" role="img" aria-label="<?php esc_attr_e( 'Tagesverlauf der Seitenaufrufe', 'seitenblick' ); ?>"></div><p id="sbk-range" class="sbk-muted"></p><details><summary><?php esc_html_e( 'Tageswerte als Tabelle', 'seitenblick' ); ?></summary><table class="sbk-table"><thead><tr><th><?php esc_html_e( 'Datum', 'seitenblick' ); ?></th><th><?php esc_html_e( 'Aufrufe', 'seitenblick' ); ?></th><th><?php esc_html_e( 'Gemessene Ansichten', 'seitenblick' ); ?></th></tr></thead><tbody id="sbk-daily"></tbody></table></details></section>
			<section class="sbk-panel"><p class="sbk-eyebrow"><?php esc_html_e( 'GERÄTE', 'seitenblick' ); ?></p><h2><?php esc_html_e( 'Wie wird geöffnet?', 'seitenblick' ); ?></h2><div id="sbk-donut" aria-hidden="true"></div><div id="sbk-devices"></div><p class="sbk-muted"><?php esc_html_e( 'Grobe Klassen der Serveraufrufe. Keine Gerätekennung.', 'seitenblick' ); ?></p></section>
		</div>
		<section class="sbk-panel sbk-pages"><div class="sbk-panel-heading"><div><p class="sbk-eyebrow"><?php esc_html_e( 'INHALTE', 'seitenblick' ); ?></p><h2><?php esc_html_e( 'Welche Seiten interessieren?', 'seitenblick' ); ?></h2></div><label class="sbk-search"><?php esc_html_e( 'Seite suchen', 'seitenblick' ); ?><input type="search" id="sbk-search" placeholder="<?php esc_attr_e( 'Titel oder Seite …', 'seitenblick' ); ?>"></label></div><p class="sbk-muted"><?php esc_html_e( 'Serveraufrufe und freiwillige App-Ansichten sind getrennte Messgrößen und werden nicht addiert. Die Zeit gehört nur zur Einwilligungsstichprobe.', 'seitenblick' ); ?></p><div class="sbk-table-scroll"><table class="sbk-table"><thead><tr><th scope="col"><?php esc_html_e( 'Seite', 'seitenblick' ); ?></th><th scope="col"><?php esc_html_e( 'Aufrufe', 'seitenblick' ); ?></th><th scope="col"><?php esc_html_e( 'Gemessen', 'seitenblick' ); ?></th><th scope="col"><?php esc_html_e( 'Ø aktive Zeit', 'seitenblick' ); ?></th><th scope="col"><?php esc_html_e( 'Aktive Zeit gesamt', 'seitenblick' ); ?></th></tr></thead><tbody id="sbk-pages"></tbody></table></div><p id="sbk-page-count" class="sbk-muted"></p></section>
		<div class="sbk-bottom-grid">
			<section class="sbk-panel"><p class="sbk-eyebrow"><?php esc_html_e( 'TAGESZEITEN', 'seitenblick' ); ?></p><h2><?php esc_html_e( 'Wann wird gelesen?', 'seitenblick' ); ?></h2><div id="sbk-hours" role="img" aria-label="<?php esc_attr_e( 'Aufrufe nach Tageszeit', 'seitenblick' ); ?>"></div><p class="sbk-muted" id="sbk-timezone"></p></section>
			<section class="sbk-panel"><p class="sbk-eyebrow"><?php esc_html_e( 'HERKUNFT', 'seitenblick' ); ?></p><h2><?php esc_html_e( 'Wie kommen Aufrufe an?', 'seitenblick' ); ?></h2><div id="sbk-sources"></div><p class="sbk-muted"><?php esc_html_e( 'Nur Kategorien, keine fremden URLs oder Suchbegriffe. „Direkt / unbekannt“ umfasst auch unterdrückte Herkunftsangaben.', 'seitenblick' ); ?></p></section>
		</div>
		<section class="sbk-panel sbk-privacy"><div><p class="sbk-eyebrow"><?php esc_html_e( 'DATENSPARSAM VOREINGESTELLT', 'seitenblick' ); ?></p><h2><?php esc_html_e( 'Du behältst die Kontrolle.', 'seitenblick' ); ?></h2><p><?php esc_html_e( 'Keine Statistik-Cookies, keine Profile, keine gespeicherten IP-Adressen. Die Auswahl wird bis zu 90 Tage in einem signierten Einstellungs-Cookie gemerkt. Alters- und Einwilligungsprüfung bleiben Betreiberaufgaben.', 'seitenblick' ); ?></p><p id="sbk-retention"></p></div><ul><li><?php esc_html_e( 'Nur Administratoren sehen die Statistik.', 'seitenblick' ); ?></li><li><?php esc_html_e( 'Do Not Track & Global Privacy Control werden respektiert.', 'seitenblick' ); ?></li><li><?php esc_html_e( 'Keine externen Analyse-, Schrift- oder Diagrammdienste.', 'seitenblick' ); ?></li></ul></section>
		<section class="sbk-panel sbk-consent-info" style="margin-bottom:22px">
			<p class="sbk-eyebrow"><?php esc_html_e( 'EINWILLIGUNG & FREMDDIENSTE', 'seitenblick' ); ?></p><h2><?php esc_html_e( 'Eine klare Datenschutz-Auswahl.', 'seitenblick' ); ?></h2>
			<p><?php esc_html_e( 'Notwendige Funktionen, Statistik, externe Medien und sonstige Fremddienste sind getrennt. Optionale Kategorien sind bei der ersten Auswahl ausgeschaltet. Erlauben und Ablehnen sind gleichwertig erreichbar; Widerruf ist jederzeit möglich.', 'seitenblick' ); ?></p>
			<p class="sbk-muted"><?php esc_html_e( 'Einstellungs-Cookie:', 'seitenblick' ); ?> <?php echo esc_html( sbk_cookie_name() ); ?> <?php esc_html_e( '· maximal 90 Tage · HttpOnly · SameSite=Lax · Secure bei HTTPS · ohne Besucherkennung. Es wird erst nach einer ausdrücklichen Auswahl gesetzt. Änderungen an den registrierten Diensten oder der Messrichtlinie verlangen eine neue Auswahl.', 'seitenblick' ); ?></p>
			<details><summary><?php esc_html_e( 'Registrierte Dienste und Grenzen', 'seitenblick' ); ?></summary><ul>
			<?php
			foreach ( sbk_services() as $service ) {
				?>
				<li><strong><?php echo esc_html( $service['label'] ); ?></strong> — <?php echo esc_html( $service['description'] ); ?></li><?php } ?></ul>
			<p class="sbk-muted"><?php esc_html_e( 'WordPress-Skripte und Stylesheets von externen Adressen sowie die Video-Shortcodes sind vor Zustimmung gesperrt. Hartkodierte Tags, Inline-Tracking, eingebettete Bilder, serverseitige Übertragungen und andere Plugins müssen vor Verwendung separat geprüft werden. Der Einstellungs-Cookie dokumentiert die aktuelle Auswahl im Browser; Seitenblick speichert kein personenbezogenes Einwilligungsarchiv. Rechtsgrundlagen und Nachweispflichten sind vom Betreiber zu prüfen.', 'seitenblick' ); ?></p></details>
		</section>
		<?php
		echo wp_kses_post( sbk_donation_html() );
		sbk_audit_panel();
		sbk_settings_form( $settings );
		?>
		<footer class="sbk-footer"><?php esc_html_e( 'Seitenblick', 'seitenblick' ); ?> <?php echo esc_html( SBK_VERSION ); ?> · <?php esc_html_e( 'Aufrufe sind keine Besucherzahlen. Aktive Zeit ist eine Näherung.', 'seitenblick' ); ?></footer>
	</div>
	<?php
}
function sbk_admin_post_guard( string $nonce ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Zugriff nicht erlaubt.', 'seitenblick' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( $nonce );
}
add_action(
	'admin_post_sbk_settings',
	function () {
		sbk_admin_post_guard( 'sbk_settings' );
		if ( ( sbk_server_value( 'REQUEST_METHOD' ) ) !== 'POST' ) {
			wp_die( '', '', array( 'response' => 405 ) );
		}
		if ( (int) ( sbk_server_value( 'CONTENT_LENGTH' ) ) > 65536 ) {
			wp_die( esc_html__( 'Einstellungen sind zu umfangreich.', 'seitenblick' ), '', array( 'response' => 413 ) );
		}
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- sbk_admin_post_guard verified this action nonce and administrator capability.
		$next = sbk_parse_settings( wp_unslash( $_POST ) );
		if ( is_wp_error( $next ) ) {
			wp_die( esc_html( $next->get_error_message() ), '', array( 'response' => 400 ) );
		}
		update_option( 'sbk_settings', $next, false );
		wp_safe_redirect( admin_url( 'admin.php?page=seitenblick&saved=1' ) );
		exit;
	}
);
function sbk_csv_cell( string $value ): string {
	return preg_match( '/^[\s]*[=+@-]/u', $value ) ? "'" . $value : $value;
}
add_action(
	'admin_post_sbk_export',
	function () {
		sbk_admin_post_guard( 'sbk_export' );
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sbk_admin_post_guard verified the export action nonce.
		$days = sbk_days( isset( $_GET['days'] ) && is_scalar( $_GET['days'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['days'] ) ) : 7 );
		if ( $days === null ) {
			wp_die( esc_html__( 'Ungültiger Zeitraum.', 'seitenblick' ), '', array( 'response' => 400 ) );
		}
		$report = sbk_report( $days );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Disposition: attachment; filename="seitenblick-' . $report['end'] . '.csv"' );
		$out = fopen( 'php://output', 'w' );
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Writes a UTF-8 BOM to the HTTP response stream, not a file.
		fwrite( $out, "\xEF\xBB\xBF" );
		fputcsv( $out, array( 'Von', 'Bis', 'Seite', 'Pfad', 'Serveraufrufe', __( 'Gemessene Ansichten', 'seitenblick' ), __( 'Ansichten mit Zeit', 'seitenblick' ), __( 'Aktive Sekunden', 'seitenblick' ), __( 'Durchschnitt aktive Sekunden', 'seitenblick' ) ), ';' );
		foreach ( $report['pages'] as $row ) {
			fputcsv( $out, array_map( 'sbk_csv_cell', array_map( 'strval', array( $report['start'], $report['end'], $row['title'], $row['path'], $row['server_views'], $row['browser_views'], $row['timed_views'], $row['active_seconds'], $row['average'] ?? '' ) ) ), ';' );
		}
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes php://output after the CSV download.
		fclose( $out );
		exit;
	}
);
