<?php
/**
 * Works Gallery Gutenberg block.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_works_gallery_from_attrs( $attrs ) {
	$attrs = is_array( $attrs ) ? $attrs : array();

	$work_ids = array();
	if ( ! empty( $attrs['workIds'] ) && is_array( $attrs['workIds'] ) ) {
		$work_ids = array_values( array_filter( array_map( 'intval', $attrs['workIds'] ) ) );
	}

	$catalog = array_key_exists( 'catalogLine', $attrs )
		? sanitize_key( (string) $attrs['catalogLine'] )
		: 'facade';
	$allowed = array( 'construction', 'facade', 'repair', '' );
	if ( ! in_array( $catalog, $allowed, true ) ) {
		$catalog = 'facade';
	}

	$layout = sanitize_key( $attrs['layout'] ?? 'carousel' );
	if ( ! in_array( $layout, array( 'carousel', 'masonry' ), true ) ) {
		$layout = 'carousel';
	}

	$geo_area = sanitize_title( (string) ( $attrs['geoArea'] ?? '' ) );

	$section = array(
		'type'                => 'works_gallery',
		'layout'              => $layout,
		'eyebrow'             => ! empty( $attrs['eyebrow'] )
			? sanitize_text_field( $attrs['eyebrow'] )
			: 'Фасади · Портфоліо',
		'title'               => ! empty( $attrs['title'] )
			? sanitize_text_field( $attrs['title'] )
			: 'Наші фасадні роботи',
		'cta_label'           => array_key_exists( 'ctaLabel', $attrs )
			? sanitize_text_field( (string) $attrs['ctaLabel'] )
			: 'Всі роботи',
		'cta_href'            => array_key_exists( 'ctaHref', $attrs )
			? sanitize_text_field( (string) $attrs['ctaHref'] )
			: '/our-works',
		'show_type_filter'    => ! empty( $attrs['showTypeFilter'] ),
		'show_location_filter'=> ! empty( $attrs['showRegionFilter'] ) || ! empty( $attrs['showLocationFilter'] ),
		// BC alias used by older frontends.
		'show_region_filter'  => ! empty( $attrs['showRegionFilter'] ) || ! empty( $attrs['showLocationFilter'] ),
	);

	if ( $geo_area !== '' ) {
		$section['geo_area'] = $geo_area;
	}
	if ( $catalog !== '' ) {
		$section['catalog_line'] = $catalog;
	}
	if ( ! empty( $work_ids ) ) {
		$section['work_ids'] = $work_ids;
	}

	return $section;
}

/**
 * @param array<string, mixed> $section Seed section.
 * @return string
 */
function fwp_headless_app_works_gallery_block_markup( $section = array() ) {
	$defaults = fwp_headless_app_works_gallery_from_attrs( array() );
	$attrs    = array(
		'layout'             => $section['layout'] ?? ( $defaults['layout'] ?? 'carousel' ),
		'eyebrow'            => $section['eyebrow'] ?? $defaults['eyebrow'],
		'title'              => $section['title'] ?? $defaults['title'],
		'ctaLabel'           => $section['cta_label'] ?? $defaults['cta_label'],
		'ctaHref'            => $section['cta_href'] ?? $defaults['cta_href'],
		'catalogLine'        => $section['catalog_line'] ?? '',
		'geoArea'            => $section['geo_area'] ?? '',
		'showTypeFilter'     => ! empty( $section['show_type_filter'] ),
		'showLocationFilter' => ! empty( $section['show_location_filter'] ) || ! empty( $section['show_region_filter'] ),
		'showRegionFilter'   => ! empty( $section['show_location_filter'] ) || ! empty( $section['show_region_filter'] ),
	);
	if ( ! empty( $section['work_ids'] ) && is_array( $section['work_ids'] ) ) {
		$attrs['workIds'] = array_values( array_map( 'intval', $section['work_ids'] ) );
	}

	return '<!-- wp:grv/works-gallery ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Register block type.
 */
function fwp_headless_app_register_works_gallery_block() {
	if ( ! function_exists( 'register_block_type' ) || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_block_type(
		'grv/works-gallery',
		array(
			'api_version' => 3,
			'attributes'  => array(
				'layout'             => array(
					'type'    => 'string',
					'default' => 'carousel',
				),
				'eyebrow'            => array(
					'type'    => 'string',
					'default' => 'Фасади · Портфоліо',
				),
				'title'              => array(
					'type'    => 'string',
					'default' => 'Наші фасадні роботи',
				),
				'ctaLabel'           => array(
					'type'    => 'string',
					'default' => 'Всі роботи',
				),
				'ctaHref'            => array(
					'type'    => 'string',
					'default' => '/our-works',
				),
				'catalogLine'        => array(
					'type'    => 'string',
					'default' => 'facade',
				),
				'geoArea'            => array(
					'type'    => 'string',
					'default' => '',
				),
				'workIds'            => array(
					'type'    => 'array',
					'default' => array(),
				),
				'showTypeFilter'     => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'showLocationFilter' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'showRegionFilter'   => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_works_gallery_block' );

/**
 * Work items for the block editor picker.
 *
 * @return array<int, array{id: int, title: string, catalog_line: string}>
 */
function fwp_headless_app_get_work_editor_choices() {
	$choices = array();
	$posts   = get_posts(
		array(
			'post_type'      => 'work_item',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	foreach ( $posts as $post ) {
		$terms = wp_get_post_terms( $post->ID, 'catalog_line', array( 'fields' => 'slugs' ) );
		$choices[] = array(
			'id'           => (int) $post->ID,
			'title'        => $post->post_title,
			'catalog_line' => is_array( $terms ) ? implode( ', ', $terms ) : '',
		);
	}

	return $choices;
}

/**
 * Geo Area cities for Works Gallery region selector.
 *
 * @return array<int, array{slug: string, label: string}>
 */
function fwp_headless_app_get_geo_area_editor_choices() {
	$choices = array();
	$parents = function_exists( 'fwp_headless_app_get_geo_area_terms_ordered' )
		? fwp_headless_app_get_geo_area_terms_ordered( array( 'parent' => 0 ) )
		: get_terms(
			array(
				'taxonomy'   => 'geo_area',
				'hide_empty' => false,
				'parent'     => 0,
			)
		);

	if ( is_wp_error( $parents ) || empty( $parents ) ) {
		return $choices;
	}

	foreach ( $parents as $parent ) {
		$children = function_exists( 'fwp_headless_app_get_geo_area_terms_ordered' )
			? fwp_headless_app_get_geo_area_terms_ordered( array( 'parent' => (int) $parent->term_id ) )
			: get_terms(
				array(
					'taxonomy'   => 'geo_area',
					'hide_empty' => false,
					'parent'     => (int) $parent->term_id,
				)
			);
		if ( is_wp_error( $children ) || empty( $children ) ) {
			continue;
		}
		foreach ( $children as $city ) {
			$choices[] = array(
				'slug'  => (string) $city->slug,
				'label' => $parent->name . ' / ' . $city->name,
			);
		}
	}

	return $choices;
}

/**
 * Enqueue editor script.
 */
function fwp_headless_app_enqueue_works_gallery_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/works-gallery/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'grv-works-gallery-editor',
		plugins_url( 'blocks/works-gallery/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		(string) filemtime( $script_path ),
		true
	);

	wp_localize_script(
		'grv-works-gallery-editor',
		'grvWorksGalleryData',
		array(
			'works'    => fwp_headless_app_get_work_editor_choices(),
			'geoAreas' => fwp_headless_app_get_geo_area_editor_choices(),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_works_gallery_block_editor_assets' );
