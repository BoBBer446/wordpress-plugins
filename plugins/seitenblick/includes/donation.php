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

function sbk_donation_defaults(): array {
	return array(
		'enabled'     => true,
		'title'       => __( 'Seitenblick unterstützen', 'seitenblick' ),
		'description' => __( 'Wenn dir Seitenblick hilft, kannst du die Weiterentwicklung mit einer freiwilligen Spende unterstützen. Alle Funktionen bleiben unabhängig von einer Spende verfügbar.', 'seitenblick' ),
		'button'      => __( 'Über PayPal spenden', 'seitenblick' ),
	);
}
function sbk_donation(): array {
	$raw      = sbk_settings()['donation'] ?? array();
	$defaults = sbk_donation_defaults();
	if ( ! is_array( $raw ) ) {
		return $defaults;
	}
	foreach ( array( 'title', 'description', 'button' ) as $key ) {
		if ( is_string( $raw[ $key ] ?? null ) && trim( $raw[ $key ] ) !== '' ) {
			$defaults[ $key ] = sanitize_textarea_field( $raw[ $key ] );
		}
	}
	if ( is_bool( $raw['enabled'] ?? null ) ) {
		$defaults['enabled'] = $raw['enabled'];
	}
	return $defaults;
}
function sbk_donation_url(): string {
	// Author-provided recipient; fixed German locale, no tracking or visitor parameters.
	return 'https://www.paypal.com/donate/?hosted_button_id=G72QV8CQAL6DY&locale.x=de_DE';
}
function sbk_parse_donation( $raw ) {
	if ( ! is_array( $raw ) || array_diff( array_keys( $raw ), array( 'enabled', 'title', 'description', 'button' ) ) ) {
		return new WP_Error( 'sbk_settings', __( 'Ungültige Spendeneinstellungen.', 'seitenblick' ) );
	}
	$result = sbk_donation_defaults();
	if ( isset( $raw['enabled'] ) && $raw['enabled'] !== '1' ) {
		return new WP_Error( 'sbk_settings', __( 'Ungültige Spendenfreigabe.', 'seitenblick' ) );
	}
	$result['enabled'] = isset( $raw['enabled'] );
	foreach ( array(
		'title'       => 160,
		'description' => 1200,
		'button'      => 100,
	) as $key => $limit ) {
		$value = $raw[ $key ] ?? '';
		if ( ! is_string( $value ) || strlen( $value ) > $limit * 4 || mb_strlen( $value ) > $limit ) {
			return new WP_Error( 'sbk_settings', __( 'Ein Spendentext ist zu lang oder ungültig.', 'seitenblick' ) );
		}
		$clean = sanitize_textarea_field( $value );
		if ( trim( $clean ) !== '' ) {
			$result[ $key ] = $clean;
		}
	}
	return $result;
}
function sbk_donation_html(): string {
	$donation = sbk_donation();
	if ( ! $donation['enabled'] ) {
		return '';
	}
	$class       = __( 'sbk-panel sbk-donate', 'seitenblick' );
	$buttonClass = __( 'button sbk-donate-button', 'seitenblick' );
	return '<section class="' . esc_attr( $class ) . '"><div><h2>' . esc_html( $donation['title'] ) . '</h2><p>' . esc_html( $donation['description'] ) . '</p>'
		. '<p class="sbk-muted">Freiwillig. PayPal öffnet sich erst beim Anklicken in einem neuen Tab. Seitenblick erfasst keine Zahlungen oder Spenderdaten.</p></div>'
		. '<a class="' . esc_attr( $buttonClass ) . '" href="' . esc_url( sbk_donation_url() ) . '" target="_blank" rel="noopener noreferrer external" referrerpolicy="no-referrer">'
		. esc_html( $donation['button'] ) . '</a></section>';
}
function sbk_donation_fields(): void {
	$donation = sbk_donation();
	?>
	<fieldset><legend><?php esc_html_e( 'Freiwillige Spenden', 'seitenblick' ); ?></legend>
	<label><input type="checkbox" name="donation[enabled]" value="1" <?php checked( $donation['enabled'] ); ?>> <?php esc_html_e( 'Spendenhinweis im Plugin-Backend anzeigen', 'seitenblick' ); ?></label>
	<p><?php esc_html_e( 'Freiwillige Unterstützung der Plugin-Weiterentwicklung. Alle Funktionen bleiben ohne Spende nutzbar. Kein PayPal-Skript, keine externen Bilder, kein Zahlungsformular und keine Verbindung zu PayPal vor dem Klick.', 'seitenblick' ); ?></p>
	<label><?php esc_html_e( 'Überschrift', 'seitenblick' ); ?> <input type="text" name="donation[title]" maxlength="160" value="<?php echo esc_attr( $donation['title'] ); ?>"></label>
	<label><?php esc_html_e( 'Beschreibung', 'seitenblick' ); ?> <textarea name="donation[description]" rows="3" maxlength="1200"><?php echo esc_textarea( $donation['description'] ); ?></textarea></label>
	<label><?php esc_html_e( 'Knopftext', 'seitenblick' ); ?> <input type="text" name="donation[button]" maxlength="100" value="<?php echo esc_attr( $donation['button'] ); ?>"></label>
	<p><?php esc_html_e( 'Spendenziel:', 'seitenblick' ); ?> <a href="<?php echo esc_url( sbk_donation_url() ); ?>" target="_blank" rel="noopener noreferrer external" referrerpolicy="no-referrer"><?php esc_html_e( 'PayPal-Spendenlink öffnen', 'seitenblick' ); ?></a><?php esc_html_e( '. Empfänger-Link ist im Plugin fest hinterlegt; keine Besucherparameter werden angehängt. Zahlung und Zahlungsstatus verwaltet PayPal.', 'seitenblick' ); ?></p>
	<p><?php esc_html_e( 'Der Hinweis erscheint ausschließlich im Plugin-Backend. Er wird nicht in die öffentliche Website oder den Einwilligungsdialog eingefügt.', 'seitenblick' ); ?></p>
	</fieldset>
	<?php
}
