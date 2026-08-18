<?php
/**
 * GRV CTA strip Gutenberg block.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * Server-side block registration.
 */
function fwp_headless_app_register_cta_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type(
		'grv/cta-strip',
		array(
			'api_version' => 3,
			'attributes'  => array(
				'title'       => array(
					'type'    => 'string',
					'default' => 'Готові розпочати ваш проект?',
				),
				'buttonLabel' => array(
					'type'    => 'string',
					'default' => "Зв'язатись з нами",
				),
				'buttonHref'  => array(
					'type'    => 'string',
					'default' => '/contacts',
				),
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_cta_block' );

/**
 * Enqueue CTA block editor script.
 */
function fwp_headless_app_enqueue_cta_block_editor_assets() {
	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/cta-strip/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'grv-cta-strip-editor',
		plugins_url( 'blocks/cta-strip/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		(string) filemtime( $script_path ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_cta_block_editor_assets' );
