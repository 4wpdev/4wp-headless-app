<?php
/**
 * Shared breadcrumb block attributes (Hero / Team / CTA Advanced).
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gutenberg attribute definitions for breadcrumb toggle + optional override items.
 *
 * @return array<string, array<string, mixed>>
 */
function fwp_headless_app_breadcrumb_block_attributes() {
	return array(
		'showBreadcrumb'    => array(
			'type'    => 'boolean',
			'default' => false,
		),
		'breadcrumbItems'   => array(
			'type'    => 'array',
			'default' => array(),
			'items'   => array(
				'type' => 'object',
			),
		),
	);
}

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @return array{show_breadcrumb: bool, breadcrumb_items: array<int, array{label: string, href: string}>}
 */
function fwp_headless_app_breadcrumb_from_attrs( $attrs ) {
	$attrs = is_array( $attrs ) ? $attrs : array();
	$items = array();

	if ( ! empty( $attrs['breadcrumbItems'] ) && is_array( $attrs['breadcrumbItems'] ) ) {
		foreach ( $attrs['breadcrumbItems'] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = sanitize_text_field( $row['label'] ?? '' );
			$href  = sanitize_text_field( $row['href'] ?? '' );
			if ( $label === '' ) {
				continue;
			}
			if ( $href !== '' && $href !== '/' && $href[0] !== '/' ) {
				$href = '/' . ltrim( $href, '/' );
			}
			$items[] = array(
				'label' => $label,
				'href'  => $href,
			);
		}
	}

	return array(
		'show_breadcrumb'   => ! empty( $attrs['showBreadcrumb'] ),
		'breadcrumb_items'  => $items,
	);
}

/**
 * Merge breadcrumb fields into a section export row.
 *
 * @param array<string, mixed> $section Section row.
 * @param array<string, mixed> $attrs   Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_section_with_breadcrumb( array $section, $attrs ) {
	$crumb = fwp_headless_app_breadcrumb_from_attrs( $attrs );
	if ( $crumb['show_breadcrumb'] ) {
		$section['show_breadcrumb'] = true;
	}
	if ( ! empty( $crumb['breadcrumb_items'] ) ) {
		$section['breadcrumb_items'] = $crumb['breadcrumb_items'];
	}
	return $section;
}

/**
 * Enqueue shared breadcrumb inspector script (once per request).
 */
function fwp_headless_app_enqueue_breadcrumb_block_editor() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/shared/breadcrumb-inspector.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'grv-breadcrumb-inspector',
		plugins_url( 'blocks/shared/breadcrumb-inspector.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-i18n' ),
		FWP_HEADLESS_APP_VERSION,
		true
	);
}

/**
 * Video attachment meta for Schema.org VideoObject export.
 *
 * @param int $attachment_id Attachment ID.
 * @return array{thumbnail_url: string, upload_date: string}
 */
function fwp_headless_app_video_attachment_meta( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	$out           = array(
		'thumbnail_url' => '',
		'upload_date'   => '',
	);

	if ( $attachment_id <= 0 ) {
		return $out;
	}

	$attachment = get_post( $attachment_id );
	if ( $attachment instanceof WP_Post ) {
		$out['upload_date'] = get_post_time( 'c', true, $attachment );
	}

	$thumb_id = (int) get_post_thumbnail_id( $attachment_id );
	if ( $thumb_id > 0 ) {
		$url = wp_get_attachment_image_url( $thumb_id, 'large' );
		if ( $url ) {
			$out['thumbnail_url'] = $url;
		}
	}

	if ( $out['thumbnail_url'] === '' ) {
		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( is_array( $meta ) && ! empty( $meta['image']['file'] ) ) {
			$upload_dir = wp_get_upload_dir();
			$base       = trailingslashit( $upload_dir['baseurl'] );
			$dir        = ! empty( $meta['file'] ) ? dirname( $meta['file'] ) : '';
			$out['thumbnail_url'] = $base . ( $dir ? trailingslashit( $dir ) : '' ) . $meta['image']['file'];
		}
	}

	return $out;
}
