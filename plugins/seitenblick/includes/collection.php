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
function sbk_server_value( string $name ): string {
	if ( ! isset( $_SERVER[ $name ] ) || ! is_string( $_SERVER[ $name ] ) ) {
		return '';
	}
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Transport values are checked for control characters below and validated by each caller; text sanitization would corrupt percent-encoded paths.
	$value = wp_unslash( $_SERVER[ $name ] );
	return preg_match( '/[\x00-\x1f\x7f]/', $value ) ? '' : $value;
}
function sbk_preference_cookie(): string {
	$name = sbk_cookie_name();
	if ( ! isset( $_COOKIE[ $name ] ) || ! is_string( $_COOKIE[ $name ] ) ) {
		return '';
	}
	return sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) );
}
function sbk_origin( string $url ): string {
	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
		return '';
	}
	$scheme = strtolower( $parts['scheme'] );
	if ( ! in_array( $scheme, array( 'https', 'http' ), true ) ) {
		return '';
	}
	$port = (int) ( $parts['port'] ?? ( $scheme === 'https' ? 443 : 80 ) );
	return $scheme . '://' . strtolower( $parts['host'] ) . ':' . $port;
}
function sbk_excluded(): bool {
	if ( sbk_ip_excluded() ) {
		return true;
	}
	if ( is_user_logged_in() || ! empty( $_COOKIE[ defined( 'LOGGED_IN_COOKIE' ) ? LOGGED_IN_COOKIE : 'wordpress_logged_in' ] ) ) {
		return true;
	}
	foreach ( array_keys( $_COOKIE ) as $name ) {
		if ( str_starts_with( (string) $name, 'wordpress_logged_in_' ) ) {
			return true;
		}
	}
	if ( ( sbk_server_value( 'HTTP_DNT' ) ) === '1' || ( sbk_server_value( 'HTTP_SEC_GPC' ) ) === '1' ) {
		return true;
	}
	$agent = (string) ( sbk_server_value( 'HTTP_USER_AGENT' ) );
	return $agent === '' || strlen( $agent ) > 512 || (bool) preg_match( '/bot|crawl|spider|slurp|blackbox|prometheus|uptime|monitor|curl|wget|python|headless|lighthouse|pingdom|facebookexternalhit|preview|scrapy|httpclient|go-http-client/i', $agent );
}
function sbk_valid_path( $path ): bool {
	return is_string( $path ) && strlen( $path ) <= 384 && str_starts_with( $path, '/' ) && ! str_starts_with( $path, '//' )
		&& ! preg_match( '/[\x00-\x20\x7f?\\\\]/', $path ) && ! str_contains( $path, '..' );
}
function sbk_routes(): array {
	static $routes = null;
	if ( $routes !== null ) {
		return $routes;
	}
	$cached = wp_cache_get( 'routes', 'seitenblick' );
	if ( is_array( $cached ) ) {
		return $routes = $cached;
	}
	$homePath = wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/';
	$routes   = array(
		$homePath => array(
			'title' => __( 'Startseite', 'seitenblick' ),
			'group' => __( 'Website', 'seitenblick' ),
		),
	);
	$types    = array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );
	if ( $types ) {
		foreach ( get_posts(
			array(
				'post_type'              => $types,
				'post_status'            => 'publish',
				'numberposts'            => 5000,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'suppress_filters'       => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		) as $post ) {
			if ( $post->post_password !== '' ) {
				continue;
			}
			$url = get_permalink( $post );
			if ( sbk_origin( $url ) !== sbk_origin( home_url( '/' ) ) ) {
				continue;
			}
			$p = wp_parse_url( $url );
			// Fragments and private/query-based routes are supplied only by trusted adapters.
			if ( ! empty( $p['query'] ) || ! empty( $p['fragment'] ) ) {
				continue;
			}
			$path = $p['path'] ?? '/';
			if ( sbk_valid_path( $path ) ) {
				$routes[ $path ] = array(
					'title' => wp_strip_all_tags( $post->post_title ),
					'group' => __( 'Website', 'seitenblick' ),
				);
			}
		}
	}
	$routes = apply_filters( 'seitenblick_routes', $routes );
	$routes = array_filter( $routes, fn( $value, $key )=>sbk_valid_path( $key ) && is_array( $value ) && isset( $value['title'], $value['group'] ), ARRAY_FILTER_USE_BOTH );
	wp_cache_set( 'routes', $routes, 'seitenblick', 60 );
	return $routes;
}
add_action(
	'clean_post_cache',
	function () {
		wp_cache_delete( 'routes', 'seitenblick' );
	}
);
add_action(
	'edited_term',
	function () {
		wp_cache_delete( 'routes', 'seitenblick' );
	}
);
function sbk_current_path(): ?string {
	if ( is_admin() || is_404() || is_search() || is_preview() || is_feed() || is_trackback()
		|| wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
		return null;
	}
	if ( is_singular() && post_password_required() ) {
		return null;
	}
	$uri = (string) ( sbk_server_value( 'REQUEST_URI' ) );
	if ( str_contains( $uri, '?' ) ) {
		return null;
	} // No query URLs, even when canonical.
	$path = wp_parse_url( $uri, PHP_URL_PATH );
	return is_string( $path ) && isset( sbk_routes()[ $path ] ) ? $path : null;
}
function sbk_source(): string {
	$host = strtolower( (string) wp_parse_url( (string) ( sbk_server_value( 'HTTP_REFERER' ) ), PHP_URL_HOST ) );
	if ( ! $host ) {
		return 'direct';
	}
	if ( $host === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) ) {
		return 'internal_source';
	}
	if ( preg_match( '/(^|\.)(google\.[a-z.]+|bing\.com|duckduckgo\.com|ecosia\.org|yahoo\.[a-z.]+|startpage\.com)$/', $host ) ) {
		return 'search_source';
	}
	if ( preg_match( '/(^|\.)(facebook\.com|instagram\.com|t\.co|x\.com|reddit\.com|pinterest\.[a-z.]+|linkedin\.com|bsky\.app)$/', $host ) ) {
		return 'social';
	}
	return 'other_source';
}
function sbk_device(): string {
	$ua = (string) ( sbk_server_value( 'HTTP_USER_AGENT' ) );
	if ( preg_match( '/mobile|android|iphone|ipad|tablet/i', $ua ) ) {
		return 'mobile';
	}
	return preg_match( '/windows|macintosh|x11|linux/i', $ua ) ? 'desktop' : 'other_device';
}
add_action(
	'template_redirect',
	function () {
		if ( ! sbk_settings()['enabled'] || ( sbk_server_value( 'REQUEST_METHOD' ) ) !== 'GET' || sbk_excluded() ) {
			return;
		}
		$preference = sbk_decode_consent( sbk_preference_cookie(), null, false );
		if ( $preference && ! $preference['choices']['statistics'] ) {
			return;
		}
		$path = sbk_current_path();
		if ( $path === null || ! sbk_limit( 'server', 6000 ) ) {
			return;
		}
		sbk_add(
			$path,
			array(
				'server_views' => 1,
				sbk_device()   => 1,
				sbk_source()   => 1,
			)
		);
	},
	20
);
function sbk_permission( WP_REST_Request $request ) {
	if ( ! sbk_settings()['enabled'] || ! sbk_settings()['browser'] || sbk_excluded() ) {
		return new WP_Error( 'sbk_disabled', __( 'Messung nicht verfügbar.', 'seitenblick' ), array( 'status' => 403 ) );
	}
	$origin = $request->get_header( 'origin' );
	if ( ! $origin || sbk_origin( $origin ) !== sbk_origin( home_url( '/' ) )
		|| ! in_array( (string) $request->get_header( 'sec-fetch-site' ), array( '', 'same-origin' ), true ) ) {
		return new WP_Error( 'sbk_origin', __( 'Anfrage nicht erlaubt.', 'seitenblick' ), array( 'status' => 403 ) );
	}
	if ( strlen( $request->get_body() ) > 1024 || strtolower( trim( explode( ';', $request->get_header( 'content-type' ) )[0] ) ) !== 'application/json' ) {
		return new WP_Error( 'sbk_body', __( 'Ungültige Anfrage.', 'seitenblick' ), array( 'status' => 400 ) );
	}
	$payload = $request->get_json_params();
	if ( ! is_array( $payload ) || array_is_list( $payload ) ) {
		return new WP_Error( 'sbk_body', __( 'Ungültige Anfrage.', 'seitenblick' ), array( 'status' => 400 ) );
	}
	if ( ! sbk_limit( 'api', 2400 ) ) {
		return new WP_Error( 'sbk_limit', __( 'Zu viele Anfragen.', 'seitenblick' ), array( 'status' => 429 ) );
	}
	$choice = sbk_read_consent();
	if ( ! $choice || ! $choice['choices']['statistics'] || ! $choice['adult'] ) {
		return new WP_Error( 'sbk_consent', __( 'Keine gültige Einwilligung.', 'seitenblick' ), array( 'status' => 403 ) );
	}
	return true;
}
function sbk_response( array $data, int $status = 200 ): WP_REST_Response {
	$response = new WP_REST_Response( $data, $status );
	$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
	$response->header( 'X-Content-Type-Options', 'nosniff' );
	return $response;
}
function sbk_open( WP_REST_Request $request ) {
	global $wpdb;
	$data = $request->get_json_params();
	if ( array_diff( array_keys( $data ), array( 'path', 'consent', 'adult' ) ) || ( $data['consent'] ?? null ) !== 'v1' || ( $data['adult'] ?? null ) !== true
		|| ! sbk_valid_path( $data['path'] ?? null ) || ! isset( sbk_routes()[ $data['path'] ] ) ) {
		return new WP_Error( 'sbk_input', __( 'Ungültige Seitenansicht oder Einwilligung.', 'seitenblick' ), array( 'status' => 400 ) );
	}
	if ( ! sbk_limit( 'open', 600 ) ) {
		return new WP_Error( 'sbk_limit', __( 'Zu viele Seitenansichten.', 'seitenblick' ), array( 'status' => 429 ) );
	}
	[, $table]    = sbk_tables();
	$token        = bin2hex( random_bytes( 32 ) );
	$now          = time();
	[$day, $hour] = sbk_bucket( $now );
	$wpdb->query( 'START TRANSACTION' );
	$ok = $wpdb->insert(
		$table,
		array(
			'token_hash' => hash( 'sha256', $token ),
			'kind'       => 'view',
			'path'       => $data['path'],
			'day'        => $day,
			'hour'       => $hour,
			'started'    => $now,
			'expires'    => $now + 1800,
		),
		array( '%s', '%s', '%s', '%s', '%d', '%d', '%d' )
	);
	if ( ! $ok || ! sbk_add( $data['path'], array( 'browser_views' => 1 ), array( $day, $hour ) ) ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'sbk_store', __( 'Messung gerade nicht verfügbar.', 'seitenblick' ), array( 'status' => 503 ) );
	}
	$wpdb->query( 'COMMIT' );
	return sbk_response(
		array(
			'token'     => $token,
			'expiresIn' => 1800,
		)
	);
}
function sbk_token_data( WP_REST_Request $request, bool $heartbeat ): ?array {
	$data = $request->get_json_params();
	if ( array_diff( array_keys( $data ), $heartbeat ? array( 'token', 'seconds', 'sequence' ) : array( 'token' ) )
		|| ! is_string( $data['token'] ?? null ) || ! preg_match( '/^[a-f0-9]{64}$/D', $data['token'] ) ) {
		return null;
	}
	if ( $heartbeat && ( ! is_int( $data['seconds'] ?? null ) || $data['seconds'] < 0 || $data['seconds'] > 1800
		|| ! is_int( $data['sequence'] ?? null ) || $data['sequence'] < 1 || $data['sequence'] > 512 ) ) {
		return null;
	}
	return $data;
}
function sbk_heartbeat( WP_REST_Request $request ) {
	global $wpdb;
	$data = sbk_token_data( $request, true );
	if ( ! $data ) {
		return new WP_Error( 'sbk_input', __( 'Ungültige Zeitmessung.', 'seitenblick' ), array( 'status' => 400 ) );
	}
	[, $table] = sbk_tables();
	$key       = hash( 'sha256', $data['token'] );
	$wpdb->query( 'START TRANSACTION' );
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE token_hash=%s AND kind='view' FOR UPDATE", $table, $key ), ARRAY_A );
	$now = time();
	if ( ! $row || (int) $row['expires'] <= $now || $data['sequence'] <= (int) $row['sequence']
		|| $data['seconds'] < (int) $row['seconds'] || $data['seconds'] > $now - (int) $row['started'] + 1
		|| $data['seconds'] - (int) $row['seconds'] > 60 ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'sbk_replay', __( 'Veraltete oder unplausible Zeitmessung.', 'seitenblick' ), array( 'status' => 409 ) );
	}
	$delta  = $data['seconds'] - (int) $row['seconds'];
	$values = array( 'active_seconds' => $delta );
	if ( ! (int) $row['hits'] && $data['seconds'] > 0 ) {
		$values['timed_views'] = 1;
	}
	if ( (int) $row['seconds'] < 30 && $data['seconds'] >= 30 ) {
		$values['engaged_views'] = 1;
	}
	$changed = $wpdb->update(
		$table,
		array(
			'seconds'  => $data['seconds'],
			'sequence' => $data['sequence'],
			'hits'     => max( (int) $row['hits'], (int) ( $data['seconds'] > 0 ) ),
		),
		array( 'token_hash' => $key ),
		array( '%d', '%d', '%d' ),
		array( '%s' )
	);
	if ( $changed === false || ! sbk_add( $row['path'], $values, array( $row['day'], (int) $row['hour'] ) ) ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'sbk_store', __( 'Messung gerade nicht verfügbar.', 'seitenblick' ), array( 'status' => 503 ) );
	}
	$wpdb->query( 'COMMIT' );
	return sbk_response( array( 'ok' => true ) );
}
function sbk_revoke( WP_REST_Request $request ) {
	global $wpdb;
	$data = sbk_token_data( $request, false );
	if ( ! $data ) {
		return new WP_Error( 'sbk_input', __( 'Ungültiger Beleg.', 'seitenblick' ), array( 'status' => 400 ) );
	}
	[, $table] = sbk_tables();
	$wpdb->delete(
		$table,
		array(
			'token_hash' => hash( 'sha256', $data['token'] ),
			'kind'       => 'view',
		),
		array( '%s', '%s' )
	);
	return sbk_response( array( 'ok' => true ) );
}
add_action(
	'rest_api_init',
	function () {
		foreach ( array(
			'open'      => 'sbk_open',
			'heartbeat' => 'sbk_heartbeat',
			'revoke'    => 'sbk_revoke',
		) as $route => $callback ) {
			register_rest_route(
				'seitenblick/v1',
				'/' . $route,
				array(
					'methods'             => 'POST',
					'callback'            => $callback,
					'permission_callback' => $route === 'revoke' ? 'sbk_consent_permission' : 'sbk_permission',
				)
			);
		}
	}
);
function sbk_frontend_config(): array {

	// Includes cacheable offline shell; all actual recording endpoints remain no-store.
	return array(
		'endpoint'            => add_query_arg( 'rest_route', '/seitenblick/v1/', home_url( '/' ) ),
		'routes'              => array_keys( sbk_routes() ),
		'privacy'             => get_privacy_policy_url() ?: home_url( function_exists( 'wald_app_page' ) ? '/#/datenschutz' : '/' ),
		'version'             => 'v1',
		'policy'              => sbk_policy(),
		'services'            => sbk_public_services(),
		'texts'               => sbk_texts(),
		'statisticsAvailable' => (bool) ( sbk_settings()['enabled'] && sbk_settings()['browser'] && ! sbk_excluded() ),
	);
}
function sbk_enqueue_frontend(): void {
	if ( sbk_current_path() === null ) {
		return;
	}
	wp_enqueue_style( 'seitenblick-consent', plugins_url( 'assets/consent.css', SBK_FILE ), array(), SBK_VERSION );
	wp_enqueue_script( 'seitenblick-consent', plugins_url( 'assets/consent.js', SBK_FILE ), array(), SBK_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'seitenblick-consent', 'window.SeitenblickConfig = ' . wp_json_encode( sbk_frontend_config(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
	wp_enqueue_script( 'seitenblick-tracker', plugins_url( 'assets/tracker.js', SBK_FILE ), array( 'seitenblick-consent' ), SBK_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}
add_action( 'wp_enqueue_scripts', 'sbk_enqueue_frontend' );

/** Asset markup for integrations that render their own HTML shell without wp_head/wp_footer. */
function sbk_assets_html(): string {
	if ( preg_match( '/bot|crawl|spider|blackbox|monitor|headless|python|curl|wget/i', sbk_server_value( 'HTTP_USER_AGENT' ) ) ) {
		return '';
	}
	$config = sbk_frontend_config();
    // phpcs:disable WordPress.WP.EnqueuedResources -- Raw shell adapters cannot use WordPress enqueue output; standard themes use sbk_enqueue_frontend instead.
	return '<link rel="stylesheet" href="' . esc_url( plugins_url( 'assets/consent.css', SBK_FILE ) . '?ver=' . SBK_VERSION ) . '">'
		. '<script type="application/json" id="sbk-config">' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>'
		. '<script defer src="' . esc_url( plugins_url( 'assets/consent.js', SBK_FILE ) . '?ver=' . SBK_VERSION ) . '"></script>'
		. '<script defer src="' . esc_url( plugins_url( 'assets/tracker.js', SBK_FILE ) . '?ver=' . SBK_VERSION ) . '"></script>';
    // phpcs:enable WordPress.WP.EnqueuedResources
}
