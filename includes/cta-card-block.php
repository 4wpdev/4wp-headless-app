<?php
/**
 * CTA Card Gutenberg block (centered card — region / portfolio).
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string, mixed>
 */
function fwp_headless_app_cta_card_default_attrs() {
	return array(
		'eyebrow'              => 'Готові розпочати?',
		'title'                => 'Безкоштовна консультація',
		'text'                 => "Виїзд на об'єкт — безкоштовно. Фіксований кошторис без прихованих доплат. Ми підготуємо точний розрахунок вашого проєкту протягом 24 годин.",
		'primaryButtonLabel'   => 'Зателефонувати',
		'primaryButtonHref'    => '',
		'secondaryButtonLabel' => 'Дивитись роботи',
		'secondaryButtonHref'  => '/our-works',
	);
}

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_cta_card_from_attrs( $attrs ) {
	$attrs    = is_array( $attrs ) ? $attrs : array();
	$defaults = fwp_headless_app_cta_card_default_attrs();

	$get = static function ( $key ) use ( $attrs, $defaults ) {
		if ( array_key_exists( $key, $attrs ) ) {
			return sanitize_text_field( (string) $attrs[ $key ] );
		}
		return (string) ( $defaults[ $key ] ?? '' );
	};

	return array(
		'type'                   => 'cta_card',
		'eyebrow'                => $get( 'eyebrow' ),
		'title'                  => $get( 'title' ),
		'text'                   => array_key_exists( 'text', $attrs )
			? sanitize_textarea_field( (string) $attrs['text'] )
			: (string) ( $defaults['text'] ?? '' ),
		'primary_button_label'   => $get( 'primaryButtonLabel' ),
		'primary_button_href'    => $get( 'primaryButtonHref' ),
		'secondary_button_label' => $get( 'secondaryButtonLabel' ),
		'secondary_button_href'  => $get( 'secondaryButtonHref' ),
	);
}

/**
 * @param array<string, mixed> $section Seed section.
 * @return string
 */
function fwp_headless_app_cta_card_block_markup( $section = array() ) {
	$defaults = fwp_headless_app_cta_card_default_attrs();
	$attrs    = array(
		'eyebrow'              => $section['eyebrow'] ?? $defaults['eyebrow'],
		'title'                => $section['title'] ?? $defaults['title'],
		'text'                 => $section['text'] ?? $defaults['text'],
		'primaryButtonLabel'   => $section['primary_button_label'] ?? $defaults['primaryButtonLabel'],
		'primaryButtonHref'    => $section['primary_button_href'] ?? $defaults['primaryButtonHref'],
		'secondaryButtonLabel' => $section['secondary_button_label'] ?? $defaults['secondaryButtonLabel'],
		'secondaryButtonHref'  => $section['secondary_button_href'] ?? $defaults['secondaryButtonHref'],
	);

	return '<!-- wp:grv/cta-card ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Register block.
 */
function fwp_headless_app_register_cta_card_block() {
	if ( ! function_exists( 'register_block_type' ) || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$defaults = fwp_headless_app_cta_card_default_attrs();

	register_block_type(
		'grv/cta-card',
		array(
			'api_version' => 3,
			'attributes'  => array(
				'eyebrow'              => array( 'type' => 'string', 'default' => $defaults['eyebrow'] ),
				'title'                => array( 'type' => 'string', 'default' => $defaults['title'] ),
				'text'                 => array( 'type' => 'string', 'default' => $defaults['text'] ),
				'primaryButtonLabel'   => array( 'type' => 'string', 'default' => $defaults['primaryButtonLabel'] ),
				'primaryButtonHref'    => array( 'type' => 'string', 'default' => $defaults['primaryButtonHref'] ),
				'secondaryButtonLabel' => array( 'type' => 'string', 'default' => $defaults['secondaryButtonLabel'] ),
				'secondaryButtonHref'  => array( 'type' => 'string', 'default' => $defaults['secondaryButtonHref'] ),
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_cta_card_block' );

/**
 * Editor assets.
 */
function fwp_headless_app_enqueue_cta_card_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/cta-card/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'grv-cta-card-editor',
		plugins_url( 'blocks/cta-card/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		(string) filemtime( $script_path ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_cta_card_block_editor_assets' );
