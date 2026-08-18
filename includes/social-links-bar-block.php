<?php
/**
 * Social Links Bar Gutenberg block.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_social_links_bar_from_attrs( $attrs ) {
	$attrs = is_array( $attrs ) ? $attrs : array();

	return array(
		'type'            => 'social_links_bar',
		'eyebrow'         => ! empty( $attrs['eyebrow'] )
			? sanitize_text_field( $attrs['eyebrow'] )
			: 'Слідкуйте за нашими роботами',
		'title'           => ! empty( $attrs['title'] )
			? sanitize_text_field( $attrs['title'] )
			: 'Ми є скрізь',
		'title_highlight' => ! empty( $attrs['titleHighlight'] )
			? sanitize_text_field( $attrs['titleHighlight'] )
			: 'де потрібно',
	);
}

/**
 * @param array<string, mixed> $section Seed section.
 * @return string
 */
function fwp_headless_app_social_links_bar_block_markup( $section = array() ) {
	$defaults = fwp_headless_app_social_links_bar_from_attrs( array() );
	$attrs    = array(
		'eyebrow'         => $section['eyebrow'] ?? $defaults['eyebrow'],
		'title'          => $section['title'] ?? $defaults['title'],
		'titleHighlight' => $section['title_highlight'] ?? $defaults['title_highlight'],
	);

	return '<!-- wp:grv/social-links-bar ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Register block type.
 */
function fwp_headless_app_register_social_links_bar_block() {
	if ( ! function_exists( 'register_block_type' ) || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_block_type(
		'grv/social-links-bar',
		array(
			'api_version' => 3,
			'attributes'  => array(
				'eyebrow'         => array(
					'type'    => 'string',
					'default' => 'Слідкуйте за нашими роботами',
				),
				'title'          => array(
					'type'    => 'string',
					'default' => 'Ми є скрізь',
				),
				'titleHighlight' => array(
					'type'    => 'string',
					'default' => 'де потрібно',
				),
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_social_links_bar_block' );

/**
 * Enqueue editor script.
 */
function fwp_headless_app_enqueue_social_links_bar_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/social-links-bar/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'grv-social-links-bar-editor',
		plugins_url( 'blocks/social-links-bar/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		(string) filemtime( $script_path ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_social_links_bar_block_editor_assets' );
