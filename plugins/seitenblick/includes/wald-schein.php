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
/** Optional adapter. The plugin remains usable without Wald-Schein. */
add_filter(
	'seitenblick_routes',
	function ( array $routes ): array {
		if ( ! function_exists( 'wald_app_page' ) ) {
			return $routes;
		}
		$base = array(
			'lexikon'      => 'Lernbuch',
			'fortschritt'  => 'Lernfortschritt',
			'quellen'      => 'Quellen',
			'installieren' => __( 'App installieren', 'seitenblick' ),
			'feedback'     => 'Hinweise',
			'blog'         => 'Waldgeschichten',
			'waldschein'   => __( 'Wald-Schein Prüfung', 'seitenblick' ),
			'urkunde'      => 'Lernurkunde',
			'runde'        => __( 'Übungsrunde', 'seitenblick' ),
		);
		foreach ( $base as $path => $label ) {
			$routes[ '/#/' . $path ] = array(
				'title' => $label,
				'group' => 'App',
			);
		}
		foreach ( get_option( 'wald_app_pages', array() ) as $route => $id ) {
			$page = wald_app_page( $route );
			if ( $page ) {
				$routes[ $route === 'home' ? '/' : '/#/' . $route ] = array(
					'title' => wp_strip_all_tags( $page->post_title ),
					'group' => 'Infoseite',
				);
			}
		}
		foreach ( get_posts(
			array(
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'numberposts'            => 5000,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		) as $post ) {
			if ( $post->post_password === '' && ! wald_app_page_route( $post->ID ) ) {
				$routes[ '/#/seite/' . $post->ID ] = array(
					'title' => wp_strip_all_tags( $post->post_title ),
					'group' => 'Infoseite',
				);
			}
		}
		foreach ( get_terms(
			array(
				'taxonomy'   => 'wald_topic',
				'hide_empty' => true,
			)
		) as $term ) {
			if ( ! is_object( $term ) || empty( $term->slug ) ) {
				continue;
			}
			$routes[ '/#/thema/' . $term->slug ]   = array(
				'title' => wp_strip_all_tags( $term->name ),
				'group' => 'Lernthema',
			);
			$routes[ '/#/lexikon/' . $term->slug ] = array(
				'title' => 'Lernbuch · ' . wp_strip_all_tags( $term->name ),
				'group' => 'Lernbuch',
			);
		}
		foreach ( get_posts(
			array(
				'post_type'              => 'wald_entry',
				'post_status'            => 'publish',
				'numberposts'            => 5000,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		) as $post ) {
			if ( $post->post_password === '' ) {
				$routes[ '/#/art/' . $post->post_name ] = array(
					'title' => wp_strip_all_tags( $post->post_title ),
					'group' => 'Lernkarte',
				);
			}
		}
		return $routes;
	}
);
add_filter(
	'wald_public_html',
	function ( string $html ): string {
		if ( str_contains( $html, 'id="sbk-config"' ) ) {
			return $html;
		}
		return str_replace( '</body>', sbk_assets_html() . '</body>', $html );
	}
);

add_filter(
	'seitenblick_audit_sources',
	function ( array $sources ): array {
		if ( ! function_exists( 'wald_app_page' ) ) {
			return $sources;
		}
		$file = get_stylesheet_directory() . '/app/index.html';
		if ( is_readable( $file ) ) {
			$sources[] = array(
				'label'   => __( 'Wald-Schein App-Grundgerüst', 'seitenblick' ),
				'content' => (string) file_get_contents( $file ),
			);
		}
		return $sources;
	}
);

add_filter(
	'seitenblick_audit_post_types',
	function ( array $types ): array {
		if ( function_exists( 'wald_media' ) ) {
			$types[] = 'wald_entry';
		}
		return array_values( array_unique( $types ) );
	}
);
add_filter(
	'seitenblick_audit_content',
	function ( string $content, WP_Post $post ): string {
		if ( $post->post_type !== 'wald_entry' || ! function_exists( 'wald_media' ) ) {
			return $content;
		}
		foreach ( wald_images( $post->ID ) as $media ) {
			$content .= '<img src="' . esc_attr( $media['path'] ) . '">';
		}
		$audio = wald_media( $post->ID, 'audio' );
		if ( $audio ) {
			$content .= '<audio src="' . esc_attr( $audio['path'] ) . '"></audio>';
		}
		return $content;
	},
	10,
	2
);
