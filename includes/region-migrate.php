<?php
/**
 * Region pages — hierarchy migration, geo↔page sync, nav href updates.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

const FWP_HEADLESS_APP_REGION_MIGRATE_OPTION = '4wp_headless_app_region_migrate_v1';

add_action( 'admin_init', 'fwp_headless_app_maybe_migrate_region_hierarchy', 8 );

/**
 * One-time migration: parent hub /region/, link geo terms, refresh nav hrefs.
 */
function fwp_headless_app_maybe_migrate_region_hierarchy() {
	if ( ! is_admin() || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}
	if ( get_option( FWP_HEADLESS_APP_REGION_MIGRATE_OPTION ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	fwp_headless_app_migrate_region_hierarchy();
	update_option( FWP_HEADLESS_APP_REGION_MIGRATE_OPTION, '1' );
}

/**
 * Run region hierarchy + geo link + nav sync (callable from admin or CLI).
 *
 * @return array{hub_id: int, reparented: int, linked: int, nav_updated: int}
 */
function fwp_headless_app_migrate_region_hierarchy() {
	$stats = array(
		'hub_id'     => 0,
		'reparented' => 0,
		'linked'     => 0,
		'nav_updated' => 0,
	);

	$hub = get_page_by_path( 'region' );
	if ( ! $hub ) {
		$hub_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_title'   => 'Регіони будівництва та ремонту',
				'post_name'    => 'region',
				'post_status'  => 'publish',
				'post_content' => fwp_headless_app_regions_hub_default_content(),
			)
		);
		if ( is_wp_error( $hub_id ) || ! $hub_id ) {
			return $stats;
		}
		update_post_meta( $hub_id, 'h1', 'Регіони будівництва та ремонту' );
		update_post_meta(
			$hub_id,
			'_wp_page_template',
			fwp_headless_app_api_template_to_wp_slug( 'blocks' )
		);
		$stats['hub_id'] = (int) $hub_id;
	} else {
		$stats['hub_id'] = (int) $hub->ID;
	}

	$hub_id = $stats['hub_id'];
	$pages  = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);

	foreach ( $pages as $page ) {
		if ( (int) $page->ID === $hub_id ) {
			continue;
		}
		if ( ! preg_match( '/^region-/i', (string) $page->post_name ) ) {
			continue;
		}
		if ( (int) $page->post_parent === $hub_id ) {
			continue;
		}
		wp_update_post(
			array(
				'ID'          => (int) $page->ID,
				'post_parent' => $hub_id,
			)
		);
		++$stats['reparented'];
	}

	$slug_map = fwp_headless_app_get_published_page_slug_map();
	$cities   = get_terms(
		array(
			'taxonomy'   => 'geo_area',
			'hide_empty' => false,
			'parent'     => 0,
		)
	);

	if ( ! is_wp_error( $cities ) ) {
		foreach ( $cities as $oblast ) {
			$children = get_terms(
				array(
					'taxonomy'   => 'geo_area',
					'hide_empty' => false,
					'parent'     => (int) $oblast->term_id,
				)
			);
			if ( is_wp_error( $children ) ) {
				continue;
			}
			foreach ( $children as $city ) {
				if ( ! ( $city instanceof WP_Term ) ) {
					continue;
				}
				$page_id = fwp_headless_app_find_region_page_id_for_geo( $city->slug, $hub_id );
				if ( $page_id > 0 ) {
					update_term_meta( $city->term_id, FWP_HEADLESS_APP_GEO_PAGE_META, $page_id );
					++$stats['linked'];
				}
			}
		}
	}

	$stats['nav_updated'] = fwp_headless_app_sync_regions_nav_from_geo( $slug_map );

	return $stats;
}

/**
 * Default Gutenberg content for /region/ hub if created by migration.
 *
 * @return string
 */
function fwp_headless_app_regions_hub_default_content() {
	if ( function_exists( 'fwp_headless_app_regions_section_block_markup' ) ) {
		return fwp_headless_app_regions_section_block_markup( array() );
	}

	return '<!-- wp:grv/regions-section /-->';
}

/**
 * Find WP page ID for a geo city slug.
 *
 * @param string $city_slug City term slug.
 * @param int    $hub_id    Region hub page ID.
 * @return int
 */
function fwp_headless_app_find_region_page_id_for_geo( $city_slug, $hub_id = 0 ) {
	$city_slug = sanitize_title( (string) $city_slug );
	if ( '' === $city_slug ) {
		return 0;
	}

	$names = array(
		'region-' . $city_slug,
		$city_slug,
	);

	foreach ( $names as $name ) {
		if ( $hub_id > 0 ) {
			$child = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'name'           => $name,
					'post_parent'    => $hub_id,
				)
			);
			if ( ! empty( $child[0] ) ) {
				return (int) $child[0]->ID;
			}
		}

		$page = get_page_by_path( 'region/' . $name );
		if ( $page ) {
			return (int) $page->ID;
		}

		$page = get_page_by_path( $name );
		if ( $page ) {
			return (int) $page->ID;
		}
	}

	return 0;
}

/**
 * Rebuild nav_menus.regions hrefs from geo_area + linked pages.
 *
 * @param array|null $slug_map Optional slug map.
 * @return int Number of items updated.
 */
function fwp_headless_app_sync_regions_nav_from_geo( $slug_map = null ) {
	if ( null === $slug_map ) {
		$slug_map = fwp_headless_app_get_published_page_slug_map();
	}

	$menus = fwp_headless_app_get_site_nav_menus();
	$items = array();

	$parents = function_exists( 'fwp_headless_app_get_geo_area_terms_ordered' )
		? fwp_headless_app_get_geo_area_terms_ordered( array( 'parent' => 0 ) )
		: get_terms( array( 'taxonomy' => 'geo_area', 'hide_empty' => false, 'parent' => 0 ) );

	if ( is_wp_error( $parents ) ) {
		return 0;
	}

	foreach ( $parents as $oblast ) {
		$children = function_exists( 'fwp_headless_app_get_geo_area_terms_ordered' )
			? fwp_headless_app_get_geo_area_terms_ordered( array( 'parent' => $oblast->term_id ) )
			: get_terms( array( 'taxonomy' => 'geo_area', 'hide_empty' => false, 'parent' => $oblast->term_id ) );
		if ( is_wp_error( $children ) ) {
			continue;
		}
		foreach ( $children as $city ) {
			$label = (string) get_term_meta( $city->term_id, 'display_name', true );
			if ( '' === $label ) {
				$label = $city->name;
			}
			$href = fwp_headless_app_geo_city_href( $city->term_id, $city->slug, $slug_map );
			$items[] = array(
				'label' => $label,
				'href'  => $href,
			);
		}
	}

	if ( empty( $items ) ) {
		return 0;
	}

	// Preserve custom labels when href matches an existing item (by city order).
	if ( ! empty( $menus['regions'] ) && is_array( $menus['regions'] ) ) {
		foreach ( $items as $i => $item ) {
			if ( isset( $menus['regions'][ $i ]['label'] ) && '' !== trim( (string) $menus['regions'][ $i ]['label'] ) ) {
				$items[ $i ]['label'] = $menus['regions'][ $i ]['label'];
			}
		}
	}

	$menus['regions'] = $items;
	update_option( FWP_HEADLESS_APP_SITE_OPTION_NAV, $menus );

	return count( $items );
}

/**
 * Build regions nav items from geo (for API export fallback).
 *
 * @return array<int, array{label: string, href: string}>
 */
function fwp_headless_app_build_regions_nav_items() {
	$slug_map = fwp_headless_app_get_published_page_slug_map();
	$items    = array();

	$parents = function_exists( 'fwp_headless_app_get_geo_area_terms_ordered' )
		? fwp_headless_app_get_geo_area_terms_ordered( array( 'parent' => 0 ) )
		: get_terms( array( 'taxonomy' => 'geo_area', 'hide_empty' => false, 'parent' => 0 ) );

	if ( is_wp_error( $parents ) ) {
		return $items;
	}

	foreach ( $parents as $oblast ) {
		$children = function_exists( 'fwp_headless_app_get_geo_area_terms_ordered' )
			? fwp_headless_app_get_geo_area_terms_ordered( array( 'parent' => $oblast->term_id ) )
			: get_terms( array( 'taxonomy' => 'geo_area', 'hide_empty' => false, 'parent' => $oblast->term_id ) );
		if ( is_wp_error( $children ) ) {
			continue;
		}
		foreach ( $children as $city ) {
			$label = (string) get_term_meta( $city->term_id, 'display_name', true );
			if ( '' === $label ) {
				$label = $city->name;
			}
			$items[] = array(
				'label' => $label,
				'href'  => fwp_headless_app_geo_city_href( $city->term_id, $city->slug, $slug_map ),
			);
		}
	}

	return $items;
}
