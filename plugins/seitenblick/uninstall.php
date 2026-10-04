<?php
/**
 * Seitenblick Statistik.
 *
 * @package Seitenblick
 * @license GPL-2.0-or-later
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
// Explicit opt-in. Deactivation and ordinary uninstall retain aggregate history.
$settings = get_option( 'sbk_settings', array() );
if ( ! empty( $settings['delete_on_uninstall'] ) && ! is_multisite() ) {
	global $wpdb;
	foreach ( array( 'sbk_totals', 'sbk_guard' ) as $suffix ) {
		$table = $wpdb->prefix . $suffix;
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );
	}
	foreach ( array( 'sbk_settings', 'sbk_schema', 'sbk_started' ) as $key ) {
		delete_option( $key );
	}
}
wp_clear_scheduled_hook( 'sbk_cleanup' );
