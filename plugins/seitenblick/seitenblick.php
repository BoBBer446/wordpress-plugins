<?php
/**
 * Seitenblick Statistik.
 *
 * @package Seitenblick
 * @license GPL-2.0-or-later
 */
/**
 * Plugin Name: Seitenblick Statistik
 * Description: Lokale Seitenstatistik mit farbigem Dashboard, sparsamen Zählern und freiwilliger aktiver Zeitmessung.
 * Version: 0.3.1
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Plugin URI: https://github.com/BoBBer446/wordpress-plugins/tree/main/plugins/seitenblick
 * Author: René Wurch
 * Author URI: https://wald-schein.de/
 * License: GPL-2.0-or-later
 * Text Domain: seitenblick
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'SBK_VERSION', '0.3.1' );
define( 'SBK_FILE', __FILE__ );
define( 'SBK_DIR', __DIR__ );
require_once SBK_DIR . '/includes/storage.php';
require_once SBK_DIR . '/includes/settings.php';
require_once SBK_DIR . '/includes/i18n.php';
require_once SBK_DIR . '/includes/donation.php';
require_once SBK_DIR . '/includes/collection.php';
require_once SBK_DIR . '/includes/consent.php';
require_once SBK_DIR . '/includes/audit.php';
require_once SBK_DIR . '/includes/admin.php';
require_once SBK_DIR . '/includes/wald-schein.php';
register_activation_hook( __FILE__, 'sbk_activate' );
register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'sbk_cleanup' );
	}
);
add_action( 'sbk_cleanup', 'sbk_cleanup' );
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'sbk_cleanup' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'sbk_cleanup' );
		}
	}
);
add_action(
	'admin_init',
	function () {
		if ( get_option( 'sbk_schema' ) !== SBK_VERSION && current_user_can( 'manage_options' ) ) {
			sbk_activate();
		}
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content( __( 'Seitenblick Statistik', 'seitenblick' ), wpautop( sbk_privacy_text() ) );
		}
	}
);
