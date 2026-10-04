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
function sbk_service_catalog(): array {
	$services = array(
		'unclassified' => array(
			'label'       => __( 'Sonstige Fremdskripte und Stylesheets', 'seitenblick' ),
			'category'    => 'blocked',
			'description' => __( 'Nicht eingeordnete externe WordPress-Skripte und Stylesheets bleiben dauerhaft gesperrt. Vor einer Freigabe muss der Betreiber sie mit Dienstname, Zweck und Datenschutzhinweisen registrieren.', 'seitenblick' ),
			'privacy'     => '',
			'handles'     => array(),
		),
		'youtube'      => array(
			'label'       => 'YouTube-Videos',
			'category'    => 'external',
			'description' => __( 'Videos werden erst nach Freigabe von youtube-nocookie.com geladen. Der Anbieter erhält dabei technisch unter anderem die IP-Adresse.', 'seitenblick' ),
			'privacy'     => 'https://policies.google.com/privacy',
			'handles'     => array(),
		),
		'vimeo'        => array(
			'label'       => 'Vimeo-Videos',
			'category'    => 'external',
			'description' => __( 'Videos werden erst nach Freigabe von player.vimeo.com geladen. Der Anbieter erhält dabei technisch unter anderem die IP-Adresse.', 'seitenblick' ),
			'privacy'     => 'https://vimeo.com/privacy',
			'handles'     => array(),
		),
	);
	// Trusted integration code may register services and associate WordPress handles.
	$services = apply_filters( 'seitenblick_services', $services );
	$result   = array();
	foreach ( $services as $id => $service ) {
		if ( ! is_string( $id ) || ! preg_match( '/^[a-z][a-z0-9-]{0,47}$/D', $id ) || ! is_array( $service )
			|| ! in_array( $service['category'] ?? '', array( 'external', 'marketing', 'blocked' ), true ) ) {
			continue;
		}
		$result[ $id ] = array(
			'label'       => sanitize_text_field( $service['label'] ?? $id ),
			'category'    => $service['category'],
			'description' => sanitize_text_field( $service['description'] ?? '' ),
			'privacy'     => esc_url_raw( $service['privacy'] ?? '', array( 'https' ) ),
			'handles'     => array_values( array_filter( is_array( $service['handles'] ?? null ) ? $service['handles'] : array(), 'is_string' ) ),
		);
	}
	return $result;
}
function sbk_controlled_services(): array {
	$settings = sbk_settings();
	$result   = array();
	foreach ( sbk_service_catalog() as $id => $service ) {
		$service = array_merge( $service, $settings['service_overrides'][ $id ] ?? array() );
		if ( $service['category'] !== 'blocked' && ! in_array( $id, $settings['enabled_services'], true ) ) {
			$service['category'] = 'blocked';
		}
		$result[ $id ] = $service;
	}
	return $result;
}
function sbk_services(): array {
	return array_filter( sbk_controlled_services(), fn( $s )=>$s['category'] !== 'blocked' );
}
function sbk_public_services(): array {
	$services = sbk_services();
	foreach ( $services as &$service ) {
		unset( $service['handles'] );
	}
	return $services;
}
function sbk_consent_config(): array {
	return array(
		'policy'              => sbk_policy(),
		'services'            => sbk_public_services(),
		'texts'               => sbk_texts(),
		'statisticsAvailable' => (bool) ( sbk_settings()['enabled'] && sbk_settings()['browser'] && ! sbk_excluded() ),
		'routes'              => array_keys( sbk_routes() ),
	);
}
function sbk_policy(): string {
	$services = sbk_services();
	ksort( $services );
	return substr(
		hash(
			'sha256',
			wp_json_encode(
				array(
					'v'        => 'consent-v2',
					'enabled'  => (bool) sbk_settings()['enabled'],
					'browser'  => (bool) sbk_settings()['browser'],
					'services' => $services,
					'texts'    => array_diff_key( sbk_texts(), array_flip( array( 'dashboard_title', 'dashboard_intro' ) ) ),
				)
			)
		),
		0,
		20
	);
}
function sbk_cookie_name(): string {
	return ( is_ssl() ? '__Host-' : '' ) . 'sbk_consent_' . substr( hash( 'sha256', home_url( '/' ) ), 0, 8 );
}
function sbk_encode_consent( array $flags, bool $adult, ?int $now = null ): string {
	$day     = (int) floor( ( $now ?? time() ) / DAY_IN_SECONDS );
	$payload = array(
		'v'       => sbk_policy(),
		'day'     => $day,
		'until'   => $day + 90,
		'choices' => $flags,
		'adult'   => $adult,
	);
	$json    = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES );
	$value   = rtrim( strtr( base64_encode( $json ), '+/', '-_' ), '=' );
	return $value . '.' . hash_hmac( 'sha256', $value, wp_salt( 'auth' ) );
}
function sbk_decode_consent( string $value, ?int $now = null, bool $requirePolicy = true ): ?array {
	if ( strlen( $value ) > 2048 || ! preg_match( '/^([A-Za-z0-9_-]+)\.([a-f0-9]{64})$/D', $value, $m )
		|| ! hash_equals( hash_hmac( 'sha256', $m[1], wp_salt( 'auth' ) ), $m[2] ) ) {
		return null;
	}
	$json = base64_decode( strtr( $m[1], '-_', '+/' ), true );
	if ( $json === false ) {
		return null;
	}
	$payload = json_decode( $json, true, 8 );
	$day     = (int) floor( ( $now ?? time() ) / DAY_IN_SECONDS );
	if ( ! is_array( $payload ) || ( $requirePolicy && ( $payload['v'] ?? null ) !== sbk_policy() ) || ! is_int( $payload['day'] ?? null ) || ! is_int( $payload['until'] ?? null )
		|| $payload['day'] > $day || $payload['until'] <= $day || $payload['until'] - $payload['day'] !== 90
		|| ! is_array( $payload['choices'] ?? null ) || array_keys( $payload['choices'] ) !== array( 'statistics', 'external', 'marketing' )
		|| ! is_bool( $payload['adult'] ?? null ) ) {
		return null;
	}
	foreach ( $payload['choices'] as $choice ) {
		if ( ! is_bool( $choice ) ) {
			return null;
		}
	}
	if ( in_array( true, $payload['choices'], true ) && ! $payload['adult'] ) {
		return null;
	}
	return $payload;
}
function sbk_read_consent(): ?array {
	return sbk_decode_consent( sbk_preference_cookie() );
}
function sbk_consent_permission( WP_REST_Request $request ) {
	$origin = $request->get_header( 'origin' );
	if ( ! $origin || sbk_origin( $origin ) !== sbk_origin( home_url( '/' ) ) || ! in_array( (string) $request->get_header( 'sec-fetch-site' ), array( '', 'same-origin' ), true ) ) {
		return new WP_Error( 'sbk_origin', __( 'Anfrage nicht erlaubt.', 'seitenblick' ), array( 'status' => 403 ) );
	}
	if ( strlen( $request->get_body() ) > 1024 || strtolower( trim( explode( ';', $request->get_header( 'content-type' ) )[0] ) ) !== 'application/json' ) {
		return new WP_Error( 'sbk_body', __( 'Ungültige Anfrage.', 'seitenblick' ), array( 'status' => 400 ) );
	}
	if ( ! sbk_limit( 'consent', 2400 ) ) {
		return new WP_Error( 'sbk_limit', __( 'Zu viele Anfragen.', 'seitenblick' ), array( 'status' => 429 ) );
	}
	return true;
}
function sbk_consent( WP_REST_Request $request ) {
	$data = $request->get_json_params();
	if ( ! is_array( $data ) || array_is_list( $data ) ) {
		return new WP_Error( 'sbk_input', __( 'Ungültige Auswahl.', 'seitenblick' ), array( 'status' => 400 ) );
	}
	if ( ( $data['action'] ?? null ) === 'read' && array_keys( $data ) === array( 'action' ) ) {
		$saved = sbk_read_consent();
		return sbk_response(
			array(
				'saved'   => (bool) $saved,
				'choices' => $saved['choices'] ?? array(
					'statistics' => false,
					'external'   => false,
					'marketing'  => false,
				),
				'adult'   => $saved['adult'] ?? false,
				'policy'  => sbk_policy(),
				'config'  => sbk_consent_config(),
			)
		);
	}
	if ( ( $data['action'] ?? null ) !== 'save' || array_diff( array_keys( $data ), array( 'action', 'choices', 'adult', 'policy' ) )
		|| ( $data['policy'] ?? null ) !== sbk_policy() || ! is_bool( $data['adult'] ?? null ) || ! is_array( $data['choices'] ?? null )
		|| array_keys( $data['choices'] ) !== array( 'statistics', 'external', 'marketing' ) ) {
		return new WP_Error( 'sbk_input', __( 'Ungültige Auswahl.', 'seitenblick' ), array( 'status' => 400 ) );
	}
	foreach ( $data['choices'] as $choice ) {
		if ( ! is_bool( $choice ) ) {
			return new WP_Error( 'sbk_input', __( 'Ungültige Auswahl.', 'seitenblick' ), array( 'status' => 400 ) );
		}
	}
	if ( in_array( true, $data['choices'], true ) && ! $data['adult'] ) {
		return new WP_Error( 'sbk_adult', __( 'Freigabe ab 16 Jahren oder durch Sorgeberechtigte erforderlich.', 'seitenblick' ), array( 'status' => 400 ) );
	}
	$available = array(
		'statistics' => (bool) ( sbk_settings()['enabled'] && sbk_settings()['browser'] && ! sbk_excluded() ),
		'external'   => false,
		'marketing'  => false,
	);
	foreach ( sbk_services() as $service ) {
		$available[ $service['category'] ] = true;
	}
	foreach ( $data['choices'] as $key => $allowed ) {
		if ( $allowed && ! $available[ $key ] ) {
			return new WP_Error( 'sbk_disabled', __( 'Diese Kategorie ist nicht verfügbar.', 'seitenblick' ), array( 'status' => 400 ) );
		}
	}
	$cookie = sbk_encode_consent( $data['choices'], $data['adult'] );
	// Host-only cookie. It records choices, never a random visitor identifier.
	$ok = setcookie(
		sbk_cookie_name(),
		$cookie,
		array(
			'expires'  => ( (int) floor( time() / DAY_IN_SECONDS ) + 90 ) * DAY_IN_SECONDS,
			'path'     => '/',

			'secure'   => is_ssl(),

			'httponly' => true,

			'samesite' => 'Lax',
		)
	);
	if ( ! $ok ) {
		return new WP_Error( 'sbk_cookie', __( 'Auswahl konnte nicht gespeichert werden.', 'seitenblick' ), array( 'status' => 503 ) );
	}
	return sbk_response(
		array(
			'saved'   => true,
			'choices' => $data['choices'],
			'adult'   => $data['adult'],
			'policy'  => sbk_policy(),
		)
	);
}
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'seitenblick/v1',
			'/consent',
			array(
				'methods'             => 'POST',
				'callback'            => 'sbk_consent',
				'permission_callback' => 'sbk_consent_permission',
			)
		);
	}
);
add_shortcode(
	'seitenblick_video',
	function ( $attrs ) {
		$attrs    = shortcode_atts(
			array(
				'anbieter' => 'youtube',
				'id'       => '',
				'titel'    => 'Video',
			),
			$attrs,
			'seitenblick_video'
		);
		$provider = $attrs['anbieter'];
		if ( ! isset( sbk_services()[ $provider ] ) ) {
			return '';
		}
		if ( $provider === 'youtube' && preg_match( '/^[A-Za-z0-9_-]{11}$/D', $attrs['id'] ) ) {
			$src = 'https://www.youtube-nocookie.com/embed/' . $attrs['id'];
		} elseif ( $provider === 'vimeo' && preg_match( '/^[0-9]{1,12}$/D', $attrs['id'] ) ) {
				$src = 'https://player.vimeo.com/video/' . $attrs['id'];
		} else {
				return '';
		}
				return '<div class="sbk-embed" data-sbk-service="' . esc_attr( $provider ) . '" data-sbk-src="' . esc_url( $src ) . '" data-sbk-title="' . esc_attr( sanitize_text_field( $attrs['titel'] ) ) . '"><p>' . esc_html( sbk_text( 'video_wait' ) ) . '</p><button type="button" data-sbk-open-consent>' . esc_html( sbk_text( 'video_open' ) ) . '</button></div>';
	}
);
add_filter(
	'script_loader_tag',
	function ( $tag, $handle, $src ) {
		if ( is_admin() ) {
			return $tag;
		}
		foreach ( sbk_controlled_services() as $id => $service ) {
			if ( ! in_array( $handle, $service['handles'], true ) ) {
				continue;
			}
			// Inert tag has no src: no network request and no execution before consent.
			$processor = new WP_HTML_Tag_Processor( $tag );
			$attrs     = '';
			if ( $processor->next_tag( 'script' ) ) {
				foreach ( array( 'id', 'type', 'nonce', 'integrity', 'crossorigin', 'referrerpolicy' ) as $key ) {
					$value = $processor->get_attribute( $key );
					if ( is_string( $value ) ) {
						$attrs .= ' data-sbk-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
					}
				}
			}
			return '<script type="application/x-seitenblick" data-sbk-service="' . esc_attr( $id ) . '" data-sbk-src="' . esc_url( $src ) . '"' . $attrs . '></script>';
		}
		$absolute = str_starts_with( $src, '//' ) ? wp_parse_url( home_url( '/' ), PHP_URL_SCHEME ) . ':' . $src : $src;
		if ( sbk_origin( $absolute ) && sbk_origin( $absolute ) !== sbk_origin( home_url( '/' ) ) ) {
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- This filter replaces an already enqueued tag with inert metadata; it does not load another script.
			return '<script type="application/x-seitenblick" data-sbk-service="unclassified" data-sbk-src="' . esc_url( $absolute ) . '"></script>';
		}
		return $tag;
	},
	10,
	3
);
add_filter(
	'style_loader_tag',
	function ( $tag, $handle, $href ) {
		if ( is_admin() ) {
			return $tag;
		}
		$absolute  = str_starts_with( $href, '//' ) ? wp_parse_url( home_url( '/' ), PHP_URL_SCHEME ) . ':' . $href : $href;
		$serviceId = 'unclassified';
		foreach ( sbk_controlled_services() as $id => $service ) {
			if ( in_array( $handle, $service['handles'], true ) ) {
				$serviceId = $id;
				break;
			}
		}
		if ( $serviceId !== 'unclassified' || ( sbk_origin( $absolute ) && sbk_origin( $absolute ) !== sbk_origin( home_url( '/' ) ) ) ) {
			$processor = new WP_HTML_Tag_Processor( $tag );
			$attrs     = '';
			if ( $processor->next_tag( 'link' ) ) {
				foreach ( array( 'media', 'nonce', 'integrity', 'crossorigin', 'referrerpolicy' ) as $key ) {
					$value = $processor->get_attribute( $key );
					if ( is_string( $value ) ) {
						$attrs .= ' data-sbk-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
					}
				}
			}
			return '<template data-sbk-style data-sbk-service="' . esc_attr( $serviceId ) . '" data-sbk-src="' . esc_url( $absolute ) . '"' . $attrs . '></template>';
		}
		return $tag;
	},
	10,
	3
);
