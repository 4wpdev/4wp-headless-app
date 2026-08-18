<?php
/**
 * CTA Advanced Gutenberg block (video + quote + social + buttons).
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default block attributes.
 *
 * @return array<string, mixed>
 */
function fwp_headless_app_cta_advanced_default_attrs() {
	return array(
		'eyebrow'              => 'Готові зробити',
		'headingLine1'         => 'Будуємо так, як самі б хотіли',
		'headingLine2'         => 'для себе',
		'introText'            => 'Ми робимо свою роботу по справжньому і якісно, так як має бути.',
		'introHighlight'       => 'по справжньому і якісно',
		'bodyText'             => 'З адекватною оцінкою, любовю, жартами та за прийнятну ціну. Без прикрас, без халтури, без обіцянок, яких не виконаємо.',
		'quote'                => 'Справжність — це не маркетинг. Це спосіб роботи.',
		'quoteAuthor'          => 'Віталій Грушовець',
		'socialLabel'          => 'Написати напряму',
		'showSocialLinks'      => true,
		'videoUrl'             => 'https://media.base44.com/videos/public/6a1311bd1062b12420e3c449/15cfe171c_welcome.mp4',
		'videoId'              => 0,
		'primaryButtonLabel'   => "Зв'язатися",
		'primaryButtonHref'    => '/contacts',
		'secondaryButtonLabel' => 'Переглянути роботи',
		'secondaryButtonHref'  => '/our-works',
	);
}

/**
 * @param array<string, mixed> $attrs        Block attributes.
 * @param string               $key          Attribute key (camelCase).
 * @param bool                 $use_default  When key is omitted from saved block JSON, use plugin default.
 */
function fwp_headless_app_cta_advanced_attr_string( $attrs, $key, $use_default = false ) {
	if ( array_key_exists( $key, $attrs ) ) {
		return (string) $attrs[ $key ];
	}

	if ( ! $use_default ) {
		return '';
	}

	$defaults = fwp_headless_app_cta_advanced_default_attrs();

	return (string) ( $defaults[ $key ] ?? '' );
}

/**
 * Map block attrs → REST section row (no default fallbacks — empty CMS field stays empty).
 *
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_cta_advanced_section_from_attrs( $attrs ) {
	$attrs = is_array( $attrs ) ? $attrs : array();

	$video_id  = isset( $attrs['videoId'] ) ? (int) $attrs['videoId'] : 0;
	$video_url = fwp_headless_app_cta_advanced_attr_string( $attrs, 'videoUrl' );
	if ( $video_id > 0 ) {
		$attachment_url = wp_get_attachment_url( $video_id );
		if ( $attachment_url ) {
			$video_url = $attachment_url;
		}
	}

	$video_meta = $video_id > 0 ? fwp_headless_app_video_attachment_meta( $video_id ) : array(
		'thumbnail_url' => '',
		'upload_date'   => '',
	);

	return fwp_headless_app_section_with_breadcrumb(
		array(
			'type'                   => 'cta_advanced',
			'eyebrow'                => fwp_headless_app_cta_advanced_attr_string( $attrs, 'eyebrow' ),
			'heading_line1'          => fwp_headless_app_cta_advanced_attr_string( $attrs, 'headingLine1' ),
			'heading_line2'          => fwp_headless_app_cta_advanced_attr_string( $attrs, 'headingLine2' ),
			'intro_text'             => fwp_headless_app_cta_advanced_attr_string( $attrs, 'introText' ),
			'intro_highlight'        => fwp_headless_app_cta_advanced_attr_string( $attrs, 'introHighlight' ),
			'body_text'              => fwp_headless_app_cta_advanced_attr_string( $attrs, 'bodyText' ),
			'quote'                  => fwp_headless_app_cta_advanced_attr_string( $attrs, 'quote' ),
			'quote_author'           => fwp_headless_app_cta_advanced_attr_string( $attrs, 'quoteAuthor' ),
			'social_label'           => fwp_headless_app_cta_advanced_attr_string( $attrs, 'socialLabel', true ),
			'show_social_links'      => array_key_exists( 'showSocialLinks', $attrs ) ? (bool) $attrs['showSocialLinks'] : true,
			'video_url'              => $video_url,
			'video_id'               => $video_id,
			'video_thumbnail_url'    => $video_meta['thumbnail_url'] ?? '',
			'video_upload_date'      => $video_meta['upload_date'] ?? '',
			'primary_button_label'   => fwp_headless_app_cta_advanced_attr_string( $attrs, 'primaryButtonLabel', true ),
			'primary_button_href'    => fwp_headless_app_cta_advanced_attr_string( $attrs, 'primaryButtonHref', true ),
			'secondary_button_label' => fwp_headless_app_cta_advanced_attr_string( $attrs, 'secondaryButtonLabel', true ),
			'secondary_button_href'  => fwp_headless_app_cta_advanced_attr_string( $attrs, 'secondaryButtonHref', true ),
		),
		$attrs
	);
}

/**
 * Build Gutenberg block comment for seed / defaults.
 *
 * @param array<string, mixed> $section Seed section row.
 * @return string
 */
function fwp_headless_app_cta_advanced_block_markup( $section = array() ) {
	$defaults = fwp_headless_app_cta_advanced_default_attrs();
	$map      = array(
		'eyebrow'              => 'eyebrow',
		'heading_line1'        => 'headingLine1',
		'heading_line2'        => 'headingLine2',
		'intro_text'           => 'introText',
		'intro_highlight'      => 'introHighlight',
		'body_text'            => 'bodyText',
		'quote'                => 'quote',
		'quote_author'         => 'quoteAuthor',
		'social_label'         => 'socialLabel',
		'show_social_links'    => 'showSocialLinks',
		'video_url'            => 'videoUrl',
		'video_id'             => 'videoId',
		'primary_button_label' => 'primaryButtonLabel',
		'primary_button_href'  => 'primaryButtonHref',
		'secondary_button_label' => 'secondaryButtonLabel',
		'secondary_button_href'  => 'secondaryButtonHref',
	);

	$attrs = $defaults;
	foreach ( $map as $snake => $camel ) {
		if ( array_key_exists( $snake, $section ) ) {
			$attrs[ $camel ] = $section[ $snake ];
		}
	}

	return '<!-- wp:grv/cta-advanced ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Register server-side block type.
 */
function fwp_headless_app_register_cta_advanced_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$defaults = fwp_headless_app_cta_advanced_default_attrs();
	$attrs    = array();

	foreach ( $defaults as $key => $value ) {
		$type = is_bool( $value ) ? 'boolean' : ( is_int( $value ) ? 'number' : 'string' );
		$attrs[ $key ] = array(
			'type'    => $type,
			'default' => $value,
		);
	}

	$attrs = array_merge( $attrs, fwp_headless_app_breadcrumb_block_attributes() );

	register_block_type(
		'grv/cta-advanced',
		array(
			'api_version' => 3,
			'attributes'  => $attrs,
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_cta_advanced_block' );

/**
 * Enqueue block editor script.
 */
function fwp_headless_app_enqueue_cta_advanced_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/cta-advanced/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_media();
	fwp_headless_app_enqueue_breadcrumb_block_editor();

	wp_enqueue_script(
		'grv-cta-advanced-editor',
		plugins_url( 'blocks/cta-advanced/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'grv-breadcrumb-inspector' ),
		(string) filemtime( $script_path ),
		true
	);

	wp_localize_script(
		'grv-cta-advanced-editor',
		'grvCtaAdvancedDefaults',
		fwp_headless_app_cta_advanced_default_attrs()
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_cta_advanced_block_editor_assets' );
