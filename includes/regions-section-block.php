<?php
/**
 * Regions section Gutenberg block.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<int, array<string, string>>
 */
function fwp_headless_app_regions_section_default_stats() {
	return array(
		array( 'value' => '3', 'label' => 'Області' ),
		array( 'value' => '15+', 'label' => 'Районів' ),
		array( 'value' => '100+', 'label' => 'Населених пунктів' ),
		array( 'value' => '0 грн', 'label' => 'Виїзд на оцінку' ),
	);
}

/**
 * @param array<int, mixed>|null $stats Raw stats.
 * @return array<int, array<string, string>>
 */
function fwp_headless_app_sanitize_regions_section_stats( $stats ) {
	$output = array();
	if ( ! is_array( $stats ) ) {
		return $output;
	}
	foreach ( $stats as $stat ) {
		if ( ! is_array( $stat ) ) {
			continue;
		}
		$value = sanitize_text_field( $stat['value'] ?? '' );
		$label = sanitize_text_field( $stat['label'] ?? '' );
		if ( '' === $value || '' === $label ) {
			continue;
		}
		$output[] = array(
			'value' => $value,
			'label' => $label,
		);
	}
	return $output;
}

/**
 * @param array<int, mixed>|null $services Raw service slugs or labels.
 * @return array<int, string>
 */
function fwp_headless_app_sanitize_regions_section_services( $services ) {
	$allowed = array( 'construction', 'facade', 'repair', 'будівництво', 'фасад', 'ремонт' );
	$output  = array();
	if ( ! is_array( $services ) ) {
		return $output;
	}
	foreach ( $services as $service ) {
		$service = sanitize_text_field( (string) $service );
		if ( in_array( $service, $allowed, true ) && ! in_array( $service, $output, true ) ) {
			$output[] = $service;
		}
	}
	return $output;
}

/**
 * @param array<int, mixed>|null $districts Raw districts.
 * @return array<int, array<string, mixed>>
 */
function fwp_headless_app_sanitize_regions_section_districts( $districts ) {
	$output = array();
	if ( ! is_array( $districts ) ) {
		return $output;
	}
	foreach ( $districts as $district ) {
		if ( ! is_array( $district ) ) {
			continue;
		}
		$name = sanitize_text_field( $district['name'] ?? '' );
		$slug = sanitize_title( $district['slug'] ?? '' );
		if ( '' === $name || '' === $slug ) {
			continue;
		}
		$output[] = array(
			'name'     => $name,
			'slug'     => $slug,
			'desc'     => sanitize_textarea_field( $district['desc'] ?? ( $district['description'] ?? '' ) ),
			'services' => fwp_headless_app_sanitize_regions_section_services( $district['services'] ?? array() ),
		);
	}
	return $output;
}

/**
 * @param array<int, mixed>|null $regions Raw regions.
 * @return array<int, array<string, mixed>>
 */
function fwp_headless_app_sanitize_regions_section_regions( $regions ) {
	$output = array();
	if ( ! is_array( $regions ) ) {
		return $output;
	}
	foreach ( $regions as $region ) {
		if ( ! is_array( $region ) ) {
			continue;
		}
		$name = sanitize_text_field( $region['name'] ?? '' );
		if ( '' === $name ) {
			continue;
		}
		$color = sanitize_hex_color( $region['color'] ?? '' );
		if ( ! $color ) {
			$color = '#c9a227';
		}
		$districts = fwp_headless_app_sanitize_regions_section_districts( $region['districts'] ?? array() );
		$output[] = array(
			'name'      => $name,
			'short'     => sanitize_text_field( $region['short'] ?? '' ),
			'color'     => $color,
			'desc'      => sanitize_textarea_field( $region['desc'] ?? ( $region['description'] ?? '' ) ),
			'districts' => $districts,
		);
	}
	return $output;
}

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_regions_section_from_attrs( $attrs ) {
	$defaults = array(
		'sub_title'         => 'Географія',
		'title'             => 'Регіони будівництва та ремонту',
		'intro'             => 'Будівництво, фасадні роботи, ремонт та реконструкція у Волинській і Рівненській областях та суміжних районах Львівської області. Працюємо в Луцьку, Ковелі, Рівному та околицях. Безкоштовний виїзд на оцінку обсягу робіт.',
		'use_geo_catalog'   => true,
		'show_stats'        => true,
		'stats'             => fwp_headless_app_regions_section_default_stats(),
		'regions'           => array(),
		'cta_title'         => 'Не знайшли свій населений пункт?',
		'cta_text'          => "Зателефонуйте нам — обговоримо умови виїзду індивідуально. Ми часто беремо об'єкти за межами основного регіону.",
		'cta_button_label'  => "Зв'язатися",
		'cta_button_href'   => '/contacts',
	);

	$attrs = is_array( $attrs ) ? $attrs : array();
	$stats = fwp_headless_app_sanitize_regions_section_stats( $attrs['stats'] ?? array() );
	if ( empty( $stats ) ) {
		$stats = fwp_headless_app_regions_section_default_stats();
	}

	$use_geo = ! isset( $attrs['useGeoCatalog'] ) || (bool) $attrs['useGeoCatalog'];
	$regions = fwp_headless_app_sanitize_regions_section_regions( $attrs['regions'] ?? array() );

	return array(
		'type'              => 'regions_section',
		'sub_title'         => ! empty( $attrs['subTitle'] ) ? sanitize_text_field( $attrs['subTitle'] ) : $defaults['sub_title'],
		'title'             => ! empty( $attrs['title'] ) ? sanitize_text_field( $attrs['title'] ) : $defaults['title'],
		'intro'             => ! empty( $attrs['intro'] ) ? sanitize_textarea_field( $attrs['intro'] ) : $defaults['intro'],
		'use_geo_catalog'   => $use_geo,
		'show_stats'        => isset( $attrs['showStats'] ) ? (bool) $attrs['showStats'] : $defaults['show_stats'],
		'stats'             => $stats,
		'regions'           => $regions,
		'cta_title'         => ! empty( $attrs['ctaTitle'] ) ? sanitize_text_field( $attrs['ctaTitle'] ) : $defaults['cta_title'],
		'cta_text'          => ! empty( $attrs['ctaText'] ) ? sanitize_textarea_field( $attrs['ctaText'] ) : $defaults['cta_text'],
		'cta_button_label'  => ! empty( $attrs['ctaButtonLabel'] ) ? sanitize_text_field( $attrs['ctaButtonLabel'] ) : $defaults['cta_button_label'],
		'cta_button_href'   => ! empty( $attrs['ctaButtonHref'] ) ? sanitize_text_field( $attrs['ctaButtonHref'] ) : $defaults['cta_button_href'],
	);
}

/**
 * @param array<string, mixed> $section Seed section.
 * @return string
 */
function fwp_headless_app_regions_section_block_markup( $section = array() ) {
	$mapped = fwp_headless_app_regions_section_from_attrs(
		array(
			'subTitle'        => $section['sub_title'] ?? null,
			'title'           => $section['title'] ?? null,
			'intro'           => $section['intro'] ?? null,
			'useGeoCatalog'   => $section['use_geo_catalog'] ?? true,
			'showStats'       => $section['show_stats'] ?? true,
			'stats'           => $section['stats'] ?? array(),
			'regions'         => $section['regions'] ?? array(),
			'ctaTitle'        => $section['cta_title'] ?? null,
			'ctaText'         => $section['cta_text'] ?? null,
			'ctaButtonLabel'  => $section['cta_button_label'] ?? null,
			'ctaButtonHref'   => $section['cta_button_href'] ?? null,
		)
	);

	$attrs = array(
		'subTitle'       => $mapped['sub_title'],
		'title'          => $mapped['title'],
		'intro'          => $mapped['intro'],
		'useGeoCatalog'  => $mapped['use_geo_catalog'],
		'showStats'      => $mapped['show_stats'],
		'stats'          => $mapped['stats'],
		'regions'        => $mapped['regions'],
		'ctaTitle'       => $mapped['cta_title'],
		'ctaText'        => $mapped['cta_text'],
		'ctaButtonLabel' => $mapped['cta_button_label'],
		'ctaButtonHref'  => $mapped['cta_button_href'],
	);

	return '<!-- wp:grv/regions-section ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Register block type.
 */
function fwp_headless_app_register_regions_section_block() {
	if ( ! function_exists( 'register_block_type' ) || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_block_type(
		'grv/regions-section',
		array(
			'api_version' => 3,
			'attributes'  => array(
				'subTitle'       => array( 'type' => 'string', 'default' => 'Географія' ),
				'title'          => array( 'type' => 'string', 'default' => 'Регіони будівництва та ремонту' ),
				'intro'          => array(
					'type'    => 'string',
					'default' => 'Будівництво, фасадні роботи, ремонт та реконструкція у Волинській і Рівненській областях та суміжних районах Львівської області. Працюємо в Луцьку, Ковелі, Рівному та околицях. Безкоштовний виїзд на оцінку обсягу робіт.',
				),
				'useGeoCatalog'  => array( 'type' => 'boolean', 'default' => true ),
				'showStats'      => array( 'type' => 'boolean', 'default' => true ),
				'stats'          => array( 'type' => 'array', 'default' => array() ),
				'regions'        => array( 'type' => 'array', 'default' => array() ),
				'ctaTitle'       => array( 'type' => 'string', 'default' => '' ),
				'ctaText'        => array( 'type' => 'string', 'default' => '' ),
				'ctaButtonLabel' => array( 'type' => 'string', 'default' => "Зв'язатися" ),
				'ctaButtonHref'  => array( 'type' => 'string', 'default' => '/contacts' ),
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_regions_section_block' );

/**
 * Enqueue editor script.
 */
function fwp_headless_app_enqueue_regions_section_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/regions-section/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'grv-regions-section-editor',
		plugins_url( 'blocks/regions-section/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		(string) filemtime( $script_path ),
		true
	);

	wp_localize_script(
		'grv-regions-section-editor',
		'grvRegionsSectionData',
		array(
			'defaults' => array(
				'subTitle'        => 'Географія',
				'title'           => 'Регіони будівництва та ремонту',
				'intro'           => 'Будівництво, фасадні роботи, ремонт та реконструкція у Волинській і Рівненській областях та суміжних районах Львівської області. Працюємо в Луцьку, Ковелі, Рівному та околицях. Безкоштовний виїзд на оцінку обсягу робіт.',
				'useGeoCatalog'   => true,
				'showStats'       => true,
				'stats'           => fwp_headless_app_regions_section_default_stats(),
				'ctaTitle'        => 'Не знайшли свій населений пункт?',
				'ctaText'         => "Зателефонуйте нам — обговоримо умови виїзду індивідуально. Ми часто беремо об'єкти за межами основного регіону.",
				'ctaButtonLabel'  => "Зв'язатися",
				'ctaButtonHref'   => '/contacts',
			),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_regions_section_block_editor_assets' );
