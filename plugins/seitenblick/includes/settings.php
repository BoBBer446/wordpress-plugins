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

function sbk_text_fields(): array {
	return array(
		'dashboard_title'        => array( __( 'Dashboard: Überschrift', 'seitenblick' ), __( 'Was wird entdeckt?', 'seitenblick' ), 160 ),
		'dashboard_intro'        => array( 'Dashboard: Einleitung', __( 'Seitenaufrufe und freiwillig gemessene aktive Zeit. Übersichtlich, ohne Besucherprofile.', 'seitenblick' ), 600 ),
		'title'                  => array( __( 'Auswahl: Überschrift', 'seitenblick' ), __( 'Deine Datenschutz-Auswahl', 'seitenblick' ), 160 ),
		'intro'                  => array( 'Auswahl: Einleitung', __( 'Notwendige Funktionen halten die Website nutzbar. Zusätzliche Statistik und externe Medien sind freiwillig. Du kannst auch mit „Nur notwendige“ uneingeschränkt weitermachen.', 'seitenblick' ), 1200 ),
		'necessary_label'        => array( __( 'Notwendige Funktionen: Titel', 'seitenblick' ), __( 'Notwendige Funktionen', 'seitenblick' ), 120 ),
		'necessary_description'  => array( __( 'Notwendige Funktionen: Beschreibung', 'seitenblick' ), __( 'Anmeldung, von dir gewünschte Funktionen und das Merken deiner Datenschutz-Auswahl. Das Einstellungs-Cookie gilt bis zu 90 Tage und enthält keine Besucherkennung.', 'seitenblick' ), 1200 ),
		'statistics_label'       => array( 'Statistik: Titel', 'Statistik', 120 ),
		'statistics_description' => array( 'Statistik: Beschreibung', __( 'Seiten und aktive Zeit werden zusammengefasst. Keine Statistik-Cookies, keine gespeicherten IP-Adressen, keine Besucherprofile. Messbelege verfallen nach 30 Minuten.', 'seitenblick' ), 1200 ),
		'external_label'         => array( __( 'Externe Medien: Titel', 'seitenblick' ), esc_html__( 'Externe Medien', 'seitenblick' ), 120 ),
		'external_description'   => array( __( 'Externe Medien: Beschreibung', 'seitenblick' ), __( 'Eingebundene Videos und registrierte Fremdinhalte laden erst nach deiner Freigabe. Dabei können Anbieter Daten erhalten und eigene Cookies setzen.', 'seitenblick' ), 1200 ),
		'marketing_label'        => array( __( 'Weitere Fremddienste: Titel', 'seitenblick' ), __( 'Weitere registrierte Fremddienste', 'seitenblick' ), 120 ),
		'marketing_description'  => array( __( 'Weitere Fremddienste: Beschreibung', 'seitenblick' ), __( 'Diese Dienste können eigene Kennungen und Cookies verwenden. Einzelheiten findest du in der Diensteliste und in den Datenschutzhinweisen.', 'seitenblick' ), 1200 ),
		'services_label'         => array( 'Diensteliste: Titel', __( 'Angebundene Dienste ansehen', 'seitenblick' ), 160 ),
		'provider_privacy'       => array( __( 'Link zur Anbieter-Datenschutzerklärung', 'seitenblick' ), __( 'Datenschutz beim Anbieter', 'seitenblick' ), 120 ),
		'adult'                  => array( __( 'Alters-/Sorgeberechtigung: Bestätigung', 'seitenblick' ), __( 'Ich bin mindestens 16 Jahre alt oder sorgeberechtigt. Für freiwillige Freigaben erforderlich.', 'seitenblick' ), 400 ),
		'basic'                  => array( __( 'Hinweis zur Serverzählung', 'seitenblick' ), __( 'Allgemeine Serveraufrufe werden ohne Besucherprofil als Summen gezählt. IP-Adressen werden zur technischen Übertragung verarbeitet; die Statistik speichert sie nicht.', 'seitenblick' ), 1200 ),
		'reject'                 => array( 'Ablehnen-Knopf', __( 'Nur notwendige', 'seitenblick' ), 100 ),
		'save'                   => array( 'Speichern-Knopf', __( 'Auswahl speichern', 'seitenblick' ), 100 ),
		'privacy'                => array( __( 'Link zu den eigenen Datenschutzhinweisen', 'seitenblick' ), 'Datenschutzhinweise', 120 ),
		'reopen'                 => array( __( 'Dauerhafter Auswahl-Knopf', 'seitenblick' ), 'Datenschutz-Auswahl', 120 ),
		'adult_error'            => array( __( 'Fehlende Bestätigung: Hinweis', 'seitenblick' ), __( 'Bitte bestätige die Alters- oder Sorgeberechtigung für freiwillige Freigaben.', 'seitenblick' ), 600 ),
		'saving'                 => array( 'Speichern: Wartehinweis', __( 'Auswahl wird gespeichert …', 'seitenblick' ), 160 ),
		'save_error'             => array( __( 'Speichern fehlgeschlagen: Hinweis', 'seitenblick' ), __( 'Die Auswahl konnte nicht gespeichert werden. Ohne bestätigte Freigabe bleiben neue optionale Dienste gesperrt. Bitte versuche es mit einer Internetverbindung erneut.', 'seitenblick' ), 1200 ),
		'video_wait'             => array( __( 'Gesperrtes Video: Hinweis', 'seitenblick' ), __( 'Dieses Video wird erst nach deiner Freigabe externer Medien geladen.', 'seitenblick' ), 600 ),
		'video_open'             => array( __( 'Gesperrtes Video: Knopf', 'seitenblick' ), __( 'Datenschutz-Auswahl öffnen', 'seitenblick' ), 120 ),
	);
}
function sbk_texts(): array {
	$settings = sbk_settings();
	$texts    = array();
	foreach ( sbk_text_fields() as $key => $field ) {
		$value         = $settings['texts'][ $key ] ?? $field[1];
		$texts[ $key ] = is_string( $value ) && trim( $value ) !== '' ? sanitize_textarea_field( $value ) : $field[1];
	}
	return $texts;
}
function sbk_text( string $key ): string {
	return sbk_texts()[ $key ] ?? '';
}

/** Strict numeric addresses only; normalize mapped IPv4, support CIDR networks. */
function sbk_ip_network( $value ): ?array {
	if ( ! is_string( $value ) || strlen( $value ) > 80 ) {
		return null;
	}
	$parts = explode( '/', trim( $value ) );
	if ( count( $parts ) > 2 || ! filter_var( $parts[0], FILTER_VALIDATE_IP ) ) {
		return null;
	}
	$packed = inet_pton( $parts[0] );
	if ( $packed === false ) {
		return null;
	}
	if ( strlen( $packed ) === 16 && substr( $packed, 0, 12 ) === str_repeat( "\0", 10 ) . "\xff\xff" ) {
		if ( isset( $parts[1] ) ) {
			return null;
		}
		$packed = substr( $packed, 12 );
	}
	$max = strlen( $packed ) * 8;
	if ( isset( $parts[1] ) && ( ! preg_match( '/^(0|[1-9][0-9]{0,2})$/D', $parts[1] ) || (int) $parts[1] > $max ) ) {
		return null;
	}
	$bits    = isset( $parts[1] ) ? (int) $parts[1] : $max;
	$network = $packed;
	for ( $i = 0;$i < strlen( $packed );$i++ ) {
		$mask          = max( 0, min( 8, $bits - $i * 8 ) );
		$network[ $i ] = chr( ord( $packed[ $i ] ) & ( $mask === 0 ? 0 : ( 255 << ( 8 - $mask ) ) ) );
	}
	return array(
		'packed' => $network,
		'bits'   => $bits,
		'value'  => inet_ntop( $network ) . ( $bits === $max ? '' : '/' . $bits ),
	);
}
function sbk_ip_matches( string $ip, array $rules ): bool {
	$address = sbk_ip_network( $ip );
	if ( ! $address ) {
		return false;
	}
	foreach ( $rules as $rule ) {
		$network = sbk_ip_network( $rule );
		if ( ! $network || strlen( $network['packed'] ) !== strlen( $address['packed'] ) ) {
			continue;
		}
		$candidate = $address['packed'];
		for ( $i = 0;$i < strlen( $candidate );$i++ ) {
			$mask            = max( 0, min( 8, $network['bits'] - $i * 8 ) );
			$candidate[ $i ] = chr( ord( $candidate[ $i ] ) & ( $mask === 0 ? 0 : ( 255 << ( 8 - $mask ) ) ) );
		}
		if ( hash_equals( $network['packed'], $candidate ) ) {
			return true;
		}
	}
	return false;
}
function sbk_parse_ip_list( $raw ) {
	if ( ! is_string( $raw ) || strlen( $raw ) > 12000 || preg_match( '/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $raw ) ) {
		return new WP_Error( 'sbk_settings', __( 'Ungültige IP-Liste.', 'seitenblick' ) );
	}
	$lines = preg_split( '/\R/u', $raw );
	if ( $lines !== false ) {
		$lines = array_values( array_filter( $lines, fn( $line )=>trim( $line ) !== '' ) );
	}
	if ( $lines === false || count( $lines ) > 128 ) {
		return new WP_Error( 'sbk_settings', __( 'Höchstens 128 IP-Einträge je Liste.', 'seitenblick' ) );
	}
	$result = array();
	foreach ( $lines as $line ) {
		if ( trim( $line ) === '' ) {
			continue;
		}
		$entry = sbk_ip_network( $line );
		if ( ! $entry ) {
			return new WP_Error( 'sbk_settings', __( 'Ungültige IP-Adresse oder Netzmaske. Bitte jeden Eintrag prüfen.', 'seitenblick' ) );
		}
		$result[] = $entry['value'];
	}
	return array_values( array_unique( $result ) );
}
function sbk_client_ip(): ?string {
	$rawPeer = sbk_server_value( 'REMOTE_ADDR' );
	if ( ! is_string( $rawPeer ) || ! filter_var( $rawPeer, FILTER_VALIDATE_IP ) ) {
		return null;
	}
	$peer = sbk_ip_network( $rawPeer );
	if ( ! $peer || str_contains( $peer['value'], '/' ) ) {
		return null;
	}
	$current = $peer['value'];
	$proxies = sbk_settings()['trusted_proxies'];
	// Never trust visitor-supplied forwarding headers without an explicitly trusted peer.
	if ( ! sbk_ip_matches( $current, $proxies ) ) {
		return $current;
	}
	$raw = sbk_server_value( 'HTTP_X_FORWARDED_FOR' );
	if ( ! is_string( $raw ) || strlen( $raw ) > 1024 || $raw === '' ) {
		return $current;
	}
	$chain = explode( ',', $raw );
	if ( count( $chain ) > 16 ) {
		return $current;
	}
	$ips = array();
	foreach ( $chain as $part ) {
		if ( ! filter_var( trim( $part ), FILTER_VALIDATE_IP ) ) {
			return $current;
		}
		$entry = sbk_ip_network( trim( $part ) );
		if ( ! $entry || str_contains( $entry['value'], '/' ) ) {
			return $current;
		}
		$ips[] = $entry['value'];
	}
	foreach ( array_reverse( $ips ) as $next ) {
		if ( ! sbk_ip_matches( $current, $proxies ) ) {
			break;
		}
		$current = $next;
	}
	return $current;
}
function sbk_ip_excluded(): bool {
	$rules = sbk_settings()['ip_blacklist'];
	if ( ! $rules ) {
		return false;
	}
	$ip = sbk_client_ip();
	return $ip !== null && sbk_ip_matches( $ip, $rules );
}

function sbk_parse_settings( array $post ) {
	$retention = isset( $post['retention'] ) && is_scalar( $post['retention'] ) && in_array( (string) $post['retention'], array( '7', '30', '90', '180', '365' ), true ) ? (int) $post['retention'] : 0;
	if ( ! in_array( $retention, array( 7, 30, 90, 180, 365 ), true ) ) {
		return new WP_Error( 'sbk_settings', __( 'Ungültige Aufbewahrung.', 'seitenblick' ) );
	}
	$result = sbk_settings();
	foreach ( array( 'enabled', 'browser', 'delete_on_uninstall' ) as $key ) {
		$result[ $key ] = isset( $post[ $key ] ) && $post[ $key ] === '1';
	}
	$result['retention'] = $retention;
	foreach ( array( 'ip_blacklist', 'trusted_proxies' ) as $key ) {
		$list = sbk_parse_ip_list( $post[ $key ] ?? '' );
		if ( is_wp_error( $list ) ) {
			return $list;
		}
		if ( $key === 'trusted_proxies' ) {
			foreach ( $list as $entry ) {
				if ( sbk_ip_network( $entry )['bits'] === 0 ) {
					return new WP_Error( 'sbk_settings', __( 'Alle Absender als Proxy zu vertrauen ist nicht erlaubt.', 'seitenblick' ) );
				}
			}
		}
		$result[ $key ] = $list;
	}
	$texts = $post['texts'] ?? array();
	if ( ! is_array( $texts ) || array_diff( array_keys( $texts ), array_keys( sbk_text_fields() ) ) ) {
		return new WP_Error( 'sbk_settings', __( 'Ungültige Textfelder.', 'seitenblick' ) );
	}
	$result['texts'] = array();
	foreach ( sbk_text_fields() as $key => $field ) {
		if ( ! array_key_exists( $key, $texts ) ) {
			continue;
		}
		if ( ! is_string( $texts[ $key ] ) || strlen( $texts[ $key ] ) > $field[2] * 4 ) {
			return new WP_Error( 'sbk_settings', __( 'Ein Textfeld ist zu lang oder ungültig.', 'seitenblick' ) );
		}
		$text = sanitize_textarea_field( $texts[ $key ] );
		if ( mb_strlen( $text ) > $field[2] ) {
			return new WP_Error( 'sbk_settings', __( 'Ein Textfeld ist zu lang.', 'seitenblick' ) );
		}
		if ( trim( $text ) !== '' && $text !== $field[1] ) {
			$result['texts'][ $key ] = $text;
		}
	}
	$catalog   = sbk_service_catalog();
	$overrides = $post['services'] ?? array();
	if ( ! is_array( $overrides ) || array_diff( array_keys( $overrides ), array_keys( $catalog ) ) ) {
		return new WP_Error( 'sbk_settings', __( 'Unbekannter Dienst.', 'seitenblick' ) );
	}
	$result['service_overrides'] = array();
	$result['enabled_services']  = array();
	foreach ( $catalog as $id => $service ) {
		if ( $service['category'] === 'blocked' ) {
			continue;
		}
		$row = $overrides[ $id ] ?? array();
		if ( ! is_array( $row ) || array_diff( array_keys( $row ), array( 'enabled', 'label', 'description', 'privacy' ) ) ) {
			return new WP_Error( 'sbk_settings', __( 'Ungültige Dienstefelder.', 'seitenblick' ) );
		}
		if ( isset( $row['enabled'] ) && $row['enabled'] !== '1' ) {
			return new WP_Error( 'sbk_settings', __( 'Ungültige Dienstefreigabe.', 'seitenblick' ) );
		}
		if ( isset( $row['enabled'] ) ) {
			$result['enabled_services'][] = $id;
		}
		$item = array();
		foreach ( array(
			'label'       => 120,
			'description' => 1200,
			'privacy'     => 1000,
		) as $key => $limit ) {
			$value = $row[ $key ] ?? $service[ $key ];
			if ( ! is_string( $value ) || mb_strlen( $value ) > $limit ) {
				return new WP_Error( 'sbk_settings', __( 'Ein Dienstefeld ist zu lang oder ungültig.', 'seitenblick' ) );
			}
			if ( $key === 'privacy' ) {
				$item[ $key ] = trim( $value ) === '' ? '' : esc_url_raw( $value, array( 'https' ) );
				if ( trim( $value ) !== '' && ( ! $item[ $key ] || ! sbk_origin( $item[ $key ] ) || strtolower( (string) wp_parse_url( $item[ $key ], PHP_URL_SCHEME ) ) !== 'https' ) ) {
					return new WP_Error( 'sbk_settings', __( 'Datenschutzhinweise müssen eine gültige HTTPS-Adresse sein.', 'seitenblick' ) );
				}
			} else {
				$item[ $key ] = sanitize_textarea_field( $value );
			}
		}
		if ( trim( $item['label'] ) === '' || trim( $item['description'] ) === '' || ( isset( $row['enabled'] ) && $item['privacy'] === '' ) ) {
			return new WP_Error( 'sbk_settings', __( 'Aktive Dienste benötigen Name, Zweck und HTTPS-Datenschutzhinweis.', 'seitenblick' ) );
		}
		$result['service_overrides'][ $id ] = $item;
	}
	if ( array_key_exists( 'donation', $post ) ) {
		$donation = sbk_parse_donation( $post['donation'] );
		if ( is_wp_error( $donation ) ) {
			return $donation;
		}
		$result['donation'] = $donation;
	}
	return $result;
}
function sbk_settings_form( array $settings ): void {
	?>
	<details class="sbk-panel sbk-settings" id="sbk-settings"><summary><?php esc_html_e( 'Texte, Dienste, Ausschlüsse & Messung einstellen', 'seitenblick' ); ?></summary>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="sbk_settings"><?php wp_nonce_field( 'sbk_settings' ); ?>
	<fieldset><legend><?php esc_html_e( 'Messung & Aufbewahrung', 'seitenblick' ); ?></legend>
	<label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> <?php esc_html_e( 'Allgemeine Serverzählung aktiv', 'seitenblick' ); ?></label>
	<label><input type="checkbox" name="browser" value="1" <?php checked( ! empty( $settings['browser'] ) ); ?>> <?php esc_html_e( 'Freiwillige Browsermessung mit Einwilligung anbieten', 'seitenblick' ); ?></label>
	<label><?php esc_html_e( 'Summen aufbewahren', 'seitenblick' ); ?> <select name="retention">
	<?php
	foreach ( array( 7, 30, 90, 180, 365 ) as $d ) {
		?>
		<option value="<?php echo (int) $d; ?>" <?php selected( (int) $settings['retention'], $d ); ?>><?php echo (int) $d; ?> <?php esc_html_e( 'Tage', 'seitenblick' ); ?></option><?php } ?></select></label>
	<label><input type="checkbox" name="delete_on_uninstall" value="1" <?php checked( ! empty( $settings['delete_on_uninstall'] ) ); ?>> <?php esc_html_e( 'Plugin-Daten bei späterem Löschen des Plugins endgültig entfernen', 'seitenblick' ); ?></label>
	<p><?php esc_html_e( 'Verkürzte Aufbewahrung wird bei der nächsten stündlichen Bereinigung wirksam. Aufrufe sind keine einzelnen Besucher. Bei Kindern bleiben wirksame Einwilligung und Nachweise Betreiberaufgaben.', 'seitenblick' ); ?></p>
	</fieldset>
	<fieldset><legend><?php esc_html_e( 'Dienste in der Einwilligung', 'seitenblick' ); ?></legend>
	<p><?php esc_html_e( 'Aktiviere nur tatsächlich eingebundene Dienste. Ohne aktive Dienste der Kategorie „Externe Medien“ erscheint diese Kategorie nicht. Ein ausgeschalteter registrierter Dienst bleibt gesperrt; seine Abschaltung erteilt keine Freigabe.', 'seitenblick' ); ?></p>
	<?php
	foreach ( sbk_service_catalog() as $id => $service ) {
		if ( $service['category'] === 'blocked' ) {
			continue;
		}
		$item = array_merge( $service, $settings['service_overrides'][ $id ] ?? array() );
		?>
		<div class="sbk-service-card">
		<label><input type="checkbox" name="services[<?php echo esc_attr( $id ); ?>][enabled]" value="1" <?php checked( in_array( $id, $settings['enabled_services'], true ) ); ?>> <strong><?php echo esc_html( $service['label'] ); ?></strong> <?php esc_html_e( 'anbieten', 'seitenblick' ); ?></label>
		<label><?php esc_html_e( 'Anzeigename', 'seitenblick' ); ?> <input type="text" name="services[<?php echo esc_attr( $id ); ?>][label]" maxlength="120" value="<?php echo esc_attr( $item['label'] ); ?>"></label>
		<label><?php esc_html_e( 'Zweck / Datenverarbeitung', 'seitenblick' ); ?> <textarea name="services[<?php echo esc_attr( $id ); ?>][description]" maxlength="1200" rows="3"><?php echo esc_textarea( $item['description'] ); ?></textarea></label>
		<label><?php esc_html_e( 'Datenschutzhinweise beim Anbieter', 'seitenblick' ); ?> <input type="url" name="services[<?php echo esc_attr( $id ); ?>][privacy]" maxlength="1000" value="<?php echo esc_attr( $item['privacy'] ); ?>"></label>
		<p><?php esc_html_e( 'Kategorie:', 'seitenblick' ); ?> <?php echo $service['category'] === 'external' ? esc_html__( 'Externe Medien', 'seitenblick' ) : esc_html__( 'Weitere Fremddienste', 'seitenblick' ); ?>. <?php echo $service['handles'] ? esc_html__( 'Registrierte WordPress-Kennungen: ', 'seitenblick' ) . esc_html( implode( ', ', $service['handles'] ) ) : esc_html__( 'Video-Shortcode; keine automatische Freigabe beliebiger Einbettungen.', 'seitenblick' ); ?></p>
		</div>
	<?php } ?>
	<p><?php esc_html_e( 'Weitere Dienste lassen sich durch eine geprüfte WordPress-Integration registrieren. Unbekannte Fremdskripte bleiben gesperrt. Hartkodierte Einbettungen und andere Plugins müssen gesondert geprüft werden.', 'seitenblick' ); ?></p>
	</fieldset>
	<fieldset><legend><?php esc_html_e( 'IP-Ausschlussliste', 'seitenblick' ); ?></legend>
	<label><?php esc_html_e( 'Diese IPs / Netze nicht zählen', 'seitenblick' ); ?> <textarea name="ip_blacklist" rows="5" spellcheck="false" placeholder="192.0.2.10&#10;2001:db8::/32"><?php echo esc_textarea( implode( "\n", $settings['ip_blacklist'] ) ); ?></textarea></label>
	<p><?php esc_html_e( 'Eine IPv4-/IPv6-Adresse oder ein CIDR-Netz je Zeile, höchstens 128 Einträge. Gilt für Serveraufrufe und Browsermessung. Die Adresse eines Besuchers wird nur zur Prüfung im Arbeitsspeicher verarbeitet, ohne Speicherung in Statistik oder Prüfprotokoll. Nur die von dir eingetragenen Ausschlüsse werden als geschützte Einstellung gespeichert. Wirkt ab dem Speichern; frühere Summen können mangels IP-Zuordnung nicht nachträglich um diese Adressen bereinigt werden.', 'seitenblick' ); ?></p>
	<details><summary><?php esc_html_e( 'Reverse Proxy: vertrauenswürdige Absender', 'seitenblick' ); ?></summary>
	<label><?php esc_html_e( 'Vertrauenswürdige Proxy-IP / Netze', 'seitenblick' ); ?> <textarea name="trusted_proxies" rows="4" spellcheck="false"><?php echo esc_textarea( implode( "\n", $settings['trusted_proxies'] ) ); ?></textarea></label>
	<p><?php esc_html_e( 'Normalerweise leer lassen. Hinter einem Reverse Proxy nur dessen unmittelbare, kontrollierte Absenderadressen eintragen. X-Forwarded-For wird nur von diesen Absendern gelesen und von rechts nach links geprüft. Niemals sämtliche Besucher oder pauschal 0.0.0.0/0 beziehungsweise ::/0 vertrauen.', 'seitenblick' ); ?></p>
	</details>
	</fieldset>
	<?php sbk_donation_fields(); ?>
	<fieldset><legend><?php esc_html_e( 'Texte bearbeiten', 'seitenblick' ); ?></legend>
	<p><?php esc_html_e( 'Reiner Text, kein HTML oder JavaScript. Leeres Feld stellt den Standardtext wieder her. Änderungen am Einwilligungsdialog oder an Diensten verlangen eine neue Auswahl. Eine bisherige Ablehnung bleibt bis zum Ablauf berücksichtigt. Formulierungen müssen die tatsächliche Verarbeitung verständlich und vollständig beschreiben.', 'seitenblick' ); ?></p>
	<div class="sbk-text-grid">
	<?php foreach ( sbk_text_fields() as $key => $field ) { ?>
		<label><?php echo esc_html( $field[0] ); ?>
		<textarea name="texts[<?php echo esc_attr( $key ); ?>]" maxlength="<?php echo (int) $field[2]; ?>" rows="<?php echo $field[2] > 400 ? '3' : '2'; ?>" placeholder="<?php echo esc_attr( $field[1] ); ?>"><?php echo esc_textarea( $settings['texts'][ $key ] ?? '' ); ?></textarea></label>
	<?php } ?>
	</div>
	</fieldset>
	<button class="button button-primary" type="submit"><?php esc_html_e( 'Einstellungen speichern', 'seitenblick' ); ?></button>
	</form></details>
	<?php
}
