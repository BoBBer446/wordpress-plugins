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
/** Inspection only. Never fetch remote resources, execute shortcodes or enable services. */
function sbk_audit_provider( string $url ): ?array {
	if ( str_starts_with( $url, '//' ) ) {
		$url = 'https:' . $url;
	}
	$parts = wp_parse_url( html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	if ( ! is_array( $parts ) || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true ) || empty( $parts['host'] ) ) {
		return null;
	}
	$host = strtolower( $parts['host'] );
	if ( $host === strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) ) {
		return null;
	}
	$id = 'other';
	if ( preg_match( '/(^|\.)(youtube\.com|youtube-nocookie\.com|youtu\.be)$/D', $host ) ) {
		$id = 'youtube';
	} elseif ( preg_match( '/(^|\.)vimeo\.com$/D', $host ) ) {
		$id = 'vimeo';
	}
		return array(
			'id'   => $id,
			'host' => $host,
		);
}
function sbk_audit_markup( string $content ): array {
	$found     = array();
	$add       = function ( $url, $kind ) use ( &$found ) {
		if ( ! is_string( $url ) || strlen( $url ) > 4000 ) {
			return;
		}
		$provider = sbk_audit_provider( trim( $url ) );
		if ( $provider ) {
			$found[] = $provider + array( 'kind' => $kind );
		}
	};
	$processor = new WP_HTML_Tag_Processor( $content );
	$tags      = 0;
	while ( $processor->next_tag() && ++$tags <= 3000 ) {
		$tag = $processor->get_tag();
		if ( in_array( $tag, array( 'IFRAME', 'SCRIPT', 'IMG', 'VIDEO', 'AUDIO', 'SOURCE', 'EMBED', 'OBJECT' ), true ) ) {
			$add( $processor->get_attribute( $tag === 'OBJECT' ? 'data' : 'src' ), strtolower( $tag ) );
			if ( $tag === 'VIDEO' ) {
				$add( $processor->get_attribute( 'poster' ), 'poster' );
			}
			if ( in_array( $tag, array( 'IMG', 'SOURCE' ), true ) ) {
				$set = $processor->get_attribute( 'srcset' );
				if ( is_string( $set ) ) {
					foreach ( array_slice( explode( ',', $set ), 0, 30 ) as $item ) {
						$add( preg_split( '/\s+/', trim( $item ) )[0] ?? '', 'srcset' );
					}
				}
			}
		} elseif ( $tag === 'LINK' && strtolower( (string) $processor->get_attribute( 'rel' ) ) === 'stylesheet' ) {
			$add( $processor->get_attribute( 'href' ), 'stylesheet' );
		}
	}
	if ( preg_match_all( '/\[seitenblick_video\b([^\]]{0,1000})\]/i', $content, $shortcodes ) ) {
		foreach ( array_slice( $shortcodes[1], 0, 100 ) as $raw ) {
			$attrs    = shortcode_parse_atts( $raw );
			$provider = is_array( $attrs ) ? ( $attrs['anbieter'] ?? 'youtube' ) : 'youtube';
			if ( in_array( $provider, array( 'youtube', 'vimeo' ), true ) ) {
				$found[] = array(
					'id'   => $provider,
					'host' => $provider === 'youtube' ? 'www.youtube-nocookie.com' : 'player.vimeo.com',
					'kind' => 'seitenblick_video',
				);
			}
		}
	}
	// WordPress core/embed and standalone provider URLs can create automatic embeds.
	if ( preg_match_all( '/<!--\s*wp:(?:core\/)?embed\s+(\{[^\r\n]{0,8000}\})\s*(?:\/)?-->/', $content, $blocks ) ) {
		foreach ( array_slice( $blocks[1], 0, 100 ) as $json ) {
			$attrs = json_decode( $json, true, 8 );
			if ( is_array( $attrs ) ) {
				$add( $attrs['url'] ?? null, 'wordpress-embed' );
			}
		}
	}
	if ( preg_match_all( '~^\s*(https?://(?:[a-z0-9-]+\.)?(?:youtube\.com|youtube-nocookie\.com|youtu\.be|vimeo\.com)/[^\s<>]{1,2000})\s*$~im', $content, $urls ) ) {
		foreach ( array_slice( $urls[1], 0, 100 ) as $url ) {
			$add( $url, 'auto-embed' );
		}
	}
	return $found;
}
function sbk_audit(): array {
	$types   = array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );
	$types   = array_values( array_filter( apply_filters( 'seitenblick_audit_post_types', $types ), fn( $type )=>is_string( $type ) && post_type_exists( $type ) && ! in_array( $type, array( 'attachment', 'revision' ), true ) ) );
	$posts   = $types ? get_posts(
		array(
			'post_type'              => $types,
			'post_status'            => 'publish',
			'numberposts'            => 2001,
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'suppress_filters'       => false,

			'update_post_meta_cache' => false,

			'update_post_term_cache' => false,
		)
	) : array();
	$limited = count( $posts ) > 2000;
	$items   = array();
	$scanned = 0;
	foreach ( array_slice( $posts, 0, 2000 ) as $post ) {
		if ( $post->post_password !== '' ) {
			continue;
		}
		$content = apply_filters( 'seitenblick_audit_content', $post->post_content, $post );
		if ( ! is_string( $content ) ) {
			$content = '';
			$limited = true;
		}
		if ( strlen( $content ) > 200000 ) {
			$limited = true;
			$content = substr( $content, 0, 200000 );
		}
		if ( substr_count( $content, '<' ) > 3000 ) {
			$limited = true;
		}
		$items[] = array(
			'label'   => wp_strip_all_tags( $post->post_title ),
			'edit'    => get_edit_post_link( $post->ID, 'raw' ),
			'content' => $content,
		);
		++$scanned;
	}
	// A trusted adapter can inspect the local app shell; no external request.
	foreach ( array_slice( apply_filters( 'seitenblick_audit_sources', array() ), 0, 10 ) as $source ) {
		if ( ! is_array( $source ) || ! is_string( $source['content'] ?? null ) ) {
			continue;
		}
		$content = $source['content'];
		if ( strlen( $content ) > 200000 ) {
			$limited = true;
			$content = substr( $content, 0, 200000 );
		}
		if ( substr_count( $content, '<' ) > 3000 ) {
			$limited = true;
		}
		$items[] = array(
			'label'   => sanitize_text_field( $source['label'] ?? __( 'Lokale Oberfläche', 'seitenblick' ) ),
			'edit'    => '',
			'content' => $content,
		);
	}
	$groups = array();
	$names  = array(
		'youtube' => 'YouTube',
		'vimeo'   => 'Vimeo',
		'other'   => __( 'Weitere externe Ressourcen', 'seitenblick' ),
	);
	foreach ( $items as $item ) {
		$seen = array();
		foreach ( sbk_audit_markup( $item['content'] ) as $finding ) {
			$id  = $finding['id'];
			$key = $id . '|' . $finding['host'];
			if ( isset( $seen[ $key ] ) ) {
				continue;
			} // Count affected documents, not duplicate markup representations.
			$seen[ $key ] = true;
			if ( ! isset( $groups[ $id ] ) ) {
				$groups[ $id ] = array(
					'label'   => $names[ $id ],
					'count'   => 0,
					'hosts'   => array(),
					'samples' => array(),
				);
			}
			++$groups[ $id ]['count'];
			if ( count( $groups[ $id ]['hosts'] ) < 20 ) {
				$groups[ $id ]['hosts'][ $finding['host'] ] = true;
			}
			if ( count( $groups[ $id ]['samples'] ) < 5 ) {
				$groups[ $id ]['samples'][] = array(
					'label' => $item['label'],
					'edit'  => $item['edit'],
					'kind'  => $finding['kind'],
				);
			}
		}
	}
	return array(
		'groups'  => $groups,
		'scanned' => $scanned,
		'limited' => $limited,
	);
}
function sbk_audit_panel(): void {
	$scan = isset( $_GET['sbk_scan'] ) && is_scalar( $_GET['sbk_scan'] ) && $_GET['sbk_scan'] === '1';
	if ( $scan && ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['_wpnonce'] ) || ! is_string( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sbk_scan' ) ) ) {
		$scan = false;
	}
	$report = $scan ? sbk_audit() : null;
	?>
	<section class="sbk-panel sbk-audit">
	<p class="sbk-eyebrow"><?php esc_html_e( 'DIENSTPRÜFUNG', 'seitenblick' ); ?></p><h2><?php esc_html_e( 'Welche Fremdinhalte sind eingebunden?', 'seitenblick' ); ?></h2>
	<p><?php esc_html_e( 'Die Prüfung liest veröffentlichte, nicht passwortgeschützte Inhalte und das lokale App-Grundgerüst. Sie erkennt unter anderem Video-Shortcodes, WordPress-Einbettungen und externe Medien-, Skript- und Stylesheet-Adressen. Quellen- und andere normale Links zählen nicht als Einbettung. Es werden keine Anbieter kontaktiert und keine Dienste automatisch freigegeben.', 'seitenblick' ); ?></p>
	<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=seitenblick&sbk_scan=1' ), 'sbk_scan' ) ); ?>#sbk-audit-result"><?php esc_html_e( 'Inhalte prüfen & Empfehlungen anzeigen', 'seitenblick' ); ?></a>
	<?php if ( $report ) { ?>
	<div id="sbk-audit-result" role="status">
	<p><?php echo (int) $report['scanned']; ?> <?php esc_html_e( 'veröffentlichte Inhalte geprüft.', 'seitenblick' ); ?>
		<?php
		if ( $report['limited'] ) {
			?>
			<?php esc_html_e( 'Die Größen-/Mengengrenze wurde erreicht; die Prüfung ist unvollständig.', 'seitenblick' ); ?><?php } ?></p>
		<?php
		if ( ! $report['groups'] ) {
			?>
			<p><strong><?php esc_html_e( 'In den geprüften Inhalten wurden keine externen Einbettungen gefunden.', 'seitenblick' ); ?></strong> <?php esc_html_e( 'YouTube und Vimeo können ausgeschaltet bleiben, solange du sie nicht einbindest.', 'seitenblick' ); ?></p><?php } else { ?>
	<ul><?php foreach ( $report['groups'] as $id => $group ) { ?>
	<li><strong><?php echo esc_html( $group['label'] ); ?></strong> — <?php echo (int) $group['count']; ?> <?php esc_html_e( 'Fundstellen in Inhalten/Grundgerüst. Anbieter-Adressen:', 'seitenblick' ); ?> <?php echo esc_html( implode( ', ', array_keys( $group['hosts'] ) ) ); ?>.
		<?php
		if ( $id !== 'other' ) {
			$active = isset( sbk_services()[ $id ] );
			echo $active ? esc_html__( 'Dienst wird bereits zur Auswahl angeboten.', 'seitenblick' ) : esc_html__( 'Empfehlung: Einbettung prüfen und diesen Dienst nur bei tatsächlicher Verwendung in den Einstellungen aktivieren.', 'seitenblick' );
			?>
			<?php esc_html_e( 'Sichere Video-Einbindung über den Seitenblick-Video-Shortcode prüfen. Hartkodierte iframes und fremde WordPress-Embeds werden durch die Diensteauswahl nicht automatisch gesperrt.', 'seitenblick' ); ?>
			<?php
		} else {
			?>
			<?php esc_html_e( 'Empfehlung: Zweck und Anbieter prüfen; gegebenenfalls Einbindung entfernen, lokal bereitstellen oder eine geprüfte Consent-Integration registrieren. Eine Kategorienfreigabe allein blockiert oder kontrolliert diese Fundstelle nicht automatisch.', 'seitenblick' ); ?><?php } ?>
	<ul>
		<?php
		foreach ( $group['samples'] as $sample ) {
			?>
		<li>
			<?php
			if ( $sample['edit'] ) {
				?>
		<a href="<?php echo esc_url( $sample['edit'] ); ?>"><?php echo esc_html( $sample['label'] ); ?></a>
				<?php
			} else {
				echo esc_html( $sample['label'] );
			}
			?>
			(<?php echo esc_html( $sample['kind'] ); ?>)</li><?php } ?></ul>
	</li><?php } ?></ul><?php } ?>
	</div><?php } ?>
	<p class="sbk-muted"><?php esc_html_e( 'Eine begrenzte Inhaltsprüfung ist kein vollständiger Cookie- oder Sicherheitsaudit. Dynamischer Fremdcode, Theme-/Plugin-PHP, JavaScript-Nachladen, CSS-Ressourcen, serverseitige Übertragungen und andere Einbindungen können zusätzliche Dienste verwenden. Ein Ergebnis „nichts gefunden“ bestätigt keine vollständige Freiheit von Fremddiensten.', 'seitenblick' ); ?></p>
	</section>
	<?php
}
