<?php
/**
 * CTA Related (next-step teaser) Gutenberg block.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_cta_related_from_attrs( $attrs ) {
	$attrs = is_array( $attrs ) ? $attrs : array();

	return array(
		'type'            => 'cta_related',
		'intro'           => ! empty( $attrs['intro'] )
			? sanitize_textarea_field( $attrs['intro'] )
			: 'Фасад готовий — беремось за внутрішнє. Ремонт і реконструкція будь-якої складності під ключ.',
		'eyebrow'         => ! empty( $attrs['eyebrow'] )
			? sanitize_text_field( $attrs['eyebrow'] )
			: 'Наступний крок',
		'title_prefix'    => ! empty( $attrs['titlePrefix'] )
			? sanitize_text_field( $attrs['titlePrefix'] )
			: 'А далі',
		'title_highlight' => ! empty( $attrs['titleHighlight'] )
			? sanitize_text_field( $attrs['titleHighlight'] )
			: 'РЕМОНТНІ РОБОТИ',
		'href'            => ! empty( $attrs['href'] )
			? sanitize_text_field( $attrs['href'] )
			: '/remont',
	);
}

/**
 * @param array<string, mixed> $section Seed section.
 * @return string
 */
function fwp_headless_app_cta_related_block_markup( $section = array() ) {
	$defaults = fwp_headless_app_cta_related_from_attrs( array() );
	$attrs    = array(
		'intro'          => $section['intro'] ?? $defaults['intro'],
		'eyebrow'        => $section['eyebrow'] ?? $defaults['eyebrow'],
		'titlePrefix'    => $section['title_prefix'] ?? $defaults['title_prefix'],
		'titleHighlight' => $section['title_highlight'] ?? $defaults['title_highlight'],
		'href'           => $section['href'] ?? $defaults['href'],
	);

	return '<!-- wp:grv/cta-related ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Register block type.
 */
function fwp_headless_app_register_cta_related_block() {
	if ( ! function_exists( 'register_block_type' ) || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_block_type(
		'grv/cta-related',
		array(
			'api_version' => 3,
			'attributes'  => array(
				'intro'          => array(
					'type'    => 'string',
					'default' => 'Фасад готовий — беремось за внутрішнє. Ремонт і реконструкція будь-якої складності під ключ.',
				),
				'eyebrow'        => array(
					'type'    => 'string',
					'default' => 'Наступний крок',
				),
				'titlePrefix'    => array(
					'type'    => 'string',
					'default' => 'А далі',
				),
				'titleHighlight' => array(
					'type'    => 'string',
					'default' => 'РЕМОНТНІ РОБОТИ',
				),
				'href'           => array(
					'type'    => 'string',
					'default' => '/remont',
				),
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_cta_related_block' );

/**
 * Enqueue editor script.
 */
function fwp_headless_app_enqueue_cta_related_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/cta-related/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'grv-cta-related-editor',
		plugins_url( 'blocks/cta-related/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		(string) filemtime( $script_path ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_cta_related_block_editor_assets' );
