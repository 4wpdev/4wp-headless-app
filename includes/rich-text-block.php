<?php
/**
 * Rich text (SEO body) Gutenberg block — classic formatting via core InnerBlocks.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render nested blocks to HTML for the headless API.
 *
 * @param array<string, mixed> $block Parsed block.
 * @return string
 */
function fwp_headless_app_rich_text_block_to_html( $block ) {
	$html = '';

	if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
		foreach ( $block['innerBlocks'] as $inner ) {
			if ( ! is_array( $inner ) ) {
				continue;
			}
			$html .= render_block( $inner );
		}
	} elseif ( ! empty( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ) {
		$html = $block['innerHTML'];
	}

	$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
	if ( $html === '' && ! empty( $attrs['content'] ) && is_string( $attrs['content'] ) ) {
		$html = $attrs['content'];
	}

	$html = trim( $html );
	if ( $html === '' ) {
		return '';
	}

	return wp_kses_post( $html );
}

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @param array<string, mixed> $block Full parsed block (for innerBlocks).
 * @return array<string, mixed>
 */
function fwp_headless_app_rich_text_from_block( $attrs, $block = array() ) {
	$attrs = is_array( $attrs ) ? $attrs : array();
	$html  = fwp_headless_app_rich_text_block_to_html( $block );

	$section = array(
		'type' => 'rich_text',
		'html' => $html,
	);

	if ( ! empty( $attrs['title'] ) ) {
		$section['title'] = sanitize_text_field( $attrs['title'] );
	}

	return $section;
}

/**
 * @param array<string, mixed> $section Seed section.
 * @return string
 */
function fwp_headless_app_rich_text_block_markup( $section = array() ) {
	$attrs = array();
	if ( ! empty( $section['title'] ) ) {
		$attrs['title'] = (string) $section['title'];
	}

	$html = ! empty( $section['html'] ) ? (string) $section['html'] : '';
	$attr_json = $attrs
		? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE )
		: '';

	if ( $html === '' ) {
		return '<!-- wp:grv/rich-text' . $attr_json . ' -->'
			. "\n<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->\n"
			. '<!-- /wp:grv/rich-text -->';
	}

	// Prefer freeform so seed HTML keeps classic formatting.
	$escaped = str_replace( '-->', '--&gt;', $html );

	return '<!-- wp:grv/rich-text' . $attr_json . ' -->'
		. "\n<!-- wp:freeform -->\n"
		. $escaped
		. "\n<!-- /wp:freeform -->\n"
		. '<!-- /wp:grv/rich-text -->';
}

/**
 * Register block type.
 */
function fwp_headless_app_register_rich_text_block() {
	if ( ! function_exists( 'register_block_type' ) || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_block_type(
		'grv/rich-text',
		array(
			'api_version' => 3,
			'attributes'  => array(
				'title'   => array(
					'type'    => 'string',
					'default' => '',
				),
				'content' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_rich_text_block' );

/**
 * Enqueue editor script.
 */
function fwp_headless_app_enqueue_rich_text_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/rich-text/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'grv-rich-text-editor',
		plugins_url( 'blocks/rich-text/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		(string) filemtime( $script_path ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_rich_text_block_editor_assets' );
