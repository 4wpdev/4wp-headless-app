<?php
/**
 * Headless site profile — CPT, taxonomies, seed import, REST export.
 *
 * Reusable for any app profile whose content model includes site_settings / nav_menus.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

const FWP_HEADLESS_APP_SITE_OPTION_SETTINGS = '4wp_headless_app_site_settings';
const FWP_HEADLESS_APP_SITE_OPTION_BRAND    = '4wp_headless_app_site_brand';
const FWP_HEADLESS_APP_SITE_OPTION_NAV      = '4wp_headless_app_site_nav_menus';

/** @deprecated 0.1.4 */
const FWP_HEADLESS_APP_GRV_OPTION_SETTINGS = FWP_HEADLESS_APP_SITE_OPTION_SETTINGS;
/** @deprecated 0.1.4 */
const FWP_HEADLESS_APP_GRV_OPTION_BRAND    = FWP_HEADLESS_APP_SITE_OPTION_BRAND;
/** @deprecated 0.1.4 */
const FWP_HEADLESS_APP_GRV_OPTION_NAV      = FWP_HEADLESS_APP_SITE_OPTION_NAV;

/**
 * Read site option, migrating legacy grv_* keys once.
 *
 * @param string $option_key Option constant.
 * @param string $legacy_key Legacy option name.
 * @param mixed  $default    Default value.
 * @return mixed
 */
function fwp_headless_app_get_site_option_value( $option_key, $legacy_key, $default = array() ) {
	$value = get_option( $option_key, null );
	if ( null !== $value ) {
		return $value;
	}

	$legacy = get_option( $legacy_key, null );
	if ( null !== $legacy ) {
		update_option( $option_key, $legacy );
		return $legacy;
	}

	return $default;
}

/**
 * REST path for the site export payload (relative to 4wp/v1).
 */
function fwp_headless_app_get_site_export_rest_path() {
	return '/export';
}

/**
 * Register site-profile CPTs and taxonomies (only those declared in content model).
 *
 * @param array<string, mixed>|null $model Active content model.
 */
function fwp_headless_app_register_site_cpts( $model = null ) {
	if ( null === $model ) {
		$model = fwp_headless_app_get_content_model();
	}

	$uses_site_types = array_intersect(
		array( 'work_item', 'team_member', 'faq_item' ),
		$model['post_types'] ?? array()
	);
	if ( empty( $uses_site_types ) && empty( array_intersect( array( 'catalog_line', 'geo_area' ), $model['taxonomies'] ?? array() ) ) ) {
		return;
	}

	$cpt_args = array(
		'public'       => true,
		'show_in_rest' => true,
		'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		'has_archive'  => false,
	);

	if ( fwp_headless_app_model_uses_post_type( 'work_item', $model ) ) {
	register_post_type(
		'work_item',
		array_merge(
			$cpt_args,
			array(
				'label'              => 'Work Items',
				'menu_icon'          => 'dashicons-portfolio',
				'publicly_queryable' => false,
				'rewrite'            => false,
				'exclude_from_search'=> true,
			)
		)
	);
	}

	if ( fwp_headless_app_model_uses_post_type( 'team_member', $model ) ) {
	register_post_type(
		'team_member',
		array_merge(
			$cpt_args,
			array(
				'label'              => 'Team',
				'menu_icon'          => 'dashicons-groups',
				'publicly_queryable' => false,
				'rewrite'            => false,
				'exclude_from_search'=> true,
				'supports'           => array( 'title', 'thumbnail', 'page-attributes' ),
			)
		)
	);
	}

	if ( fwp_headless_app_model_uses_post_type( 'faq_item', $model ) ) {
	register_post_type(
		'faq_item',
		array_merge(
			$cpt_args,
			array(
				'label'              => 'FAQ',
				'menu_icon'          => 'dashicons-editor-help',
				'publicly_queryable' => false,
				'rewrite'            => false,
				'exclude_from_search'=> true,
				'supports'           => array( 'title', 'editor', 'page-attributes' ),
			)
		)
	);
	}

	$catalog_on = fwp_headless_app_model_uses_taxonomy( 'catalog_line', $model ) ? array( 'work_item' ) : array();
	$geo_on     = fwp_headless_app_model_uses_taxonomy( 'geo_area', $model )
		? array_values( array_filter( array(
			fwp_headless_app_model_uses_post_type( 'work_item', $model ) ? 'work_item' : null,
			fwp_headless_app_model_uses_post_type( 'faq_item', $model ) ? 'faq_item' : null,
		) ) )
		: array();

	if ( ! empty( $catalog_on ) ) {
	register_taxonomy(
		'catalog_line',
		$catalog_on,
		array(
			'label'             => 'Catalog Line',
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'rewrite'           => array( 'slug' => 'catalog-line' ),
		)
	);
	}

	if ( ! empty( $geo_on ) ) {
	register_taxonomy(
		'geo_area',
		$geo_on,
		array(
			'label'             => 'Geo Area',
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'rewrite'           => array( 'slug' => 'geo-area' ),
		)
	);
	}

	$meta_args = array(
		'single'       => true,
		'show_in_rest' => true,
		'type'         => 'string',
	);

	if ( fwp_headless_app_model_uses_post_type( 'work_item', $model ) ) {
	register_post_meta( 'work_item', 'location_label', $meta_args );
	register_post_meta( 'work_item', 'cover_id', array_merge( $meta_args, array( 'type' => 'integer' ) ) );
	register_post_meta( 'work_item', 'gallery_ids', array(
		'single'       => true,
		'show_in_rest' => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'integer' ),
			),
		),
		'type'         => 'array',
	) );
	register_post_meta( 'work_item', 'photo_labels', array(
		'single'       => true,
		'show_in_rest' => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'string' ),
			),
		),
		'type'         => 'array',
	) );
	register_post_meta( 'work_item', 'card_icon', $meta_args );
	}

	if ( fwp_headless_app_model_uses_post_type( 'team_member', $model ) ) {
	register_post_meta( 'team_member', 'nick', $meta_args );
	register_post_meta( 'team_member', 'subtitle', $meta_args );
	register_post_meta( 'team_member', 'role_label', $meta_args );
	register_post_meta( 'team_member', 'media_type', $meta_args );
	register_post_meta( 'team_member', 'media_url', array_merge( $meta_args, array( 'type' => 'string' ) ) );
	register_post_meta( 'team_member', 'media_id', array_merge( $meta_args, array( 'type' => 'integer' ) ) );
	}

	if ( fwp_headless_app_model_uses_post_type( 'faq_item', $model ) ) {
	register_post_meta( 'faq_item', 'faq_scope', $meta_args );
	register_post_meta(
		'faq_item',
		'faq_pages',
		array(
			'single'       => true,
			'show_in_rest' => true,
			'type'         => 'string',
		)
	);
	}

	if ( fwp_headless_app_model_uses_post_type( 'page', $model ) ) {
	register_post_meta( 'page', 'h1', $meta_args );
	register_post_meta( 'page', 'meta_description', $meta_args );
	register_post_meta( 'page', 'service_groups', array(
		'single'       => true,
		'show_in_rest' => true,
		'type'         => 'string',
	) );
	}
}

/**
 * Sideload remote image/video URL into media library.
 *
 * @param string $url Remote URL.
 * @return int Attachment ID or 0.
 */
function fwp_headless_app_sideload_media( $url ) {
	if ( empty( $url ) ) {
		return 0;
	}

	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'posts_per_page' => 1,
			'post_status'    => 'inherit',
			'meta_key'       => 'fwp_source_url',
			'meta_value'     => $url,
			'fields'         => 'ids',
		)
	);

	if ( ! empty( $existing ) ) {
		return (int) $existing[0];
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_sideload_image( $url, 0, null, 'id' );
	if ( is_wp_error( $attachment_id ) ) {
		// Try generic sideload for videos.
		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			return 0;
		}
		$file_array = array(
			'name'     => basename( wp_parse_url( $url, PHP_URL_PATH ) ),
			'tmp_name' => $tmp,
		);
		$attachment_id = media_handle_sideload( $file_array, 0 );
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp );
			return 0;
		}
	}

	update_post_meta( (int) $attachment_id, 'fwp_source_url', $url );
	return (int) $attachment_id;
}

/**
 * Ensure taxonomy term exists; return term_id.
 *
 * @param string $taxonomy Taxonomy slug.
 * @param string $slug     Term slug.
 * @param string $name     Term name.
 * @param int    $parent   Parent term ID.
 * @param array  $meta     Term meta.
 * @return int
 */
function fwp_headless_app_ensure_term( $taxonomy, $slug, $name, $parent = 0, $meta = array() ) {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term && ! is_wp_error( $term ) ) {
		$term_id = (int) $term->term_id;
	} else {
		$result = wp_insert_term(
			$name,
			$taxonomy,
			array(
				'slug'   => $slug,
				'parent' => $parent,
			)
		);
		if ( is_wp_error( $result ) ) {
			return 0;
		}
		$term_id = (int) $result['term_id'];
	}

	foreach ( $meta as $key => $value ) {
		update_term_meta( $term_id, $key, $value );
	}

	return $term_id;
}

/**
 * Import full site profile from seed array.
 *
 * @param array<string, mixed> $data Seed data.
 * @return array<string, int> Import counts.
 */
function fwp_headless_app_apply_site_profile( $data ) {
	$stats = array(
		'work_item'   => 0,
		'team_member' => 0,
		'faq_item'    => 0,
		'pages'       => 0,
		'geo_area'    => 0,
	);

	if ( empty( $data ) ) {
		return $stats;
	}

	$app_key = get_option( FWP_HEADLESS_APP_OPTION_APP_KEY, '' );
	$apps    = fwp_headless_app_get_apps();
	$default_title = ( '' !== $app_key && ! empty( $apps[ $app_key ]['name'] ) )
		? $apps[ $app_key ]['name']
		: 'Site';

	if ( ! empty( $data['content_model'] ) ) {
		fwp_headless_app_save_content_model( $data['content_model'] );
	} elseif ( '' !== $app_key ) {
		fwp_headless_app_save_content_model( fwp_headless_app_get_content_model( $app_key ) );
	}

	if ( ! empty( $data['site_settings'] ) ) {
		update_option( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS, $data['site_settings'] );
		update_option( 'blogname', $data['site_settings']['logo'] ?? $default_title );
		update_option( 'blogdescription', $data['site_settings']['slogan'] ?? '' );

		$social_links = fwp_headless_app_get_site_social_links( $data['site_settings'] );
		if ( ! empty( $social_links ) ) {
			update_option( FWP_HEADLESS_APP_SITE_OPTION_SOCIAL_LINKS, $social_links );
		}
	}

	if ( ! empty( $data['site_brand'] ) ) {
		update_option( FWP_HEADLESS_APP_SITE_OPTION_BRAND, $data['site_brand'] );
	}

	if ( ! empty( $data['nav_menus'] ) ) {
		update_option( FWP_HEADLESS_APP_SITE_OPTION_NAV, $data['nav_menus'] );
	}

	// Catalog line terms.
	if ( ! empty( $data['catalog_line'] ) ) {
		foreach ( $data['catalog_line'] as $line ) {
			if ( empty( $line['slug'] ) ) {
				continue;
			}
			fwp_headless_app_ensure_term(
				'catalog_line',
				$line['slug'],
				$line['label'] ?? $line['slug'],
				0,
				array(
					'href'        => $line['href'] ?? '',
					'description' => $line['description'] ?? '',
					'items'       => $line['items'] ?? array(),
				)
			);
		}
	}

	// Geo area: oblast → cities.
	if ( ! empty( $data['geo_area'] ) ) {
		foreach ( $data['geo_area'] as $oblast_index => $oblast ) {
			$parent_slug = sanitize_title( $oblast['short'] ?? $oblast['oblast'] ?? 'region' );
			$parent_id   = fwp_headless_app_ensure_term(
				'geo_area',
				$parent_slug,
				$oblast['oblast'] ?? $parent_slug,
				0,
				array(
					'color'       => $oblast['color'] ?? '',
					'description' => $oblast['description'] ?? '',
				)
			);
			if ( $parent_id && function_exists( 'fwp_headless_app_set_geo_area_term_order' ) ) {
				fwp_headless_app_set_geo_area_term_order( $parent_id, (int) $oblast_index * 10 );
			}

			if ( empty( $oblast['cities'] ) ) {
				continue;
			}

			foreach ( $oblast['cities'] as $city_index => $city ) {
				if ( empty( $city['slug'] ) ) {
					continue;
				}
				$city_id = fwp_headless_app_ensure_term(
					'geo_area',
					$city['slug'],
					$city['name'] ?? $city['slug'],
					$parent_id,
					array(
						'name_locative' => $city['name_locative'] ?? '',
						'display_name'  => $city['display_name'] ?? '',
						'services'        => $city['services'] ?? array(),
						'description'     => $city['description'] ?? '',
						'hero_image'      => $city['hero_image'] ?? '',
					)
				);
				if ( $city_id && function_exists( 'fwp_headless_app_set_geo_area_term_order' ) ) {
					fwp_headless_app_set_geo_area_term_order( $city_id, (int) $city_index * 10 );
				}
				if ( $city_id && defined( 'FWP_HEADLESS_APP_GEO_LAT_META' ) && ! empty( $city['latitude'] ) ) {
					update_term_meta( $city_id, FWP_HEADLESS_APP_GEO_LAT_META, sanitize_text_field( (string) $city['latitude'] ) );
				}
				if ( $city_id && defined( 'FWP_HEADLESS_APP_GEO_LNG_META' ) && ! empty( $city['longitude'] ) ) {
					update_term_meta( $city_id, FWP_HEADLESS_APP_GEO_LNG_META, sanitize_text_field( (string) $city['longitude'] ) );
				}
			}
		}
	}

	// Work items.
	if ( ! empty( $data['work_item'] ) ) {
		foreach ( $data['work_item'] as $item ) {
			if ( empty( $item['id'] ) ) {
				continue;
			}

			$existing = fwp_headless_app_find_post_by_seed_id( 'work_item', $item['id'] );
			if ( $existing ) {
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'   => 'work_item',
					'post_title'  => $item['title'] ?? $item['id'],
					'post_status' => 'publish',
					'post_name'   => sanitize_title( $item['id'] ),
				)
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			++$stats['work_item'];
			update_post_meta( $post_id, 'fwp_seed_id', $item['id'] );
			update_post_meta( $post_id, 'location_label', $item['location_label'] ?? '' );

			$gallery_ids = array();
			if ( ! empty( $item['gallery'] ) ) {
				foreach ( $item['gallery'] as $url ) {
					$aid = fwp_headless_app_sideload_media( $url );
					if ( $aid ) {
						$gallery_ids[] = $aid;
					}
				}
			}

			$cover_id = 0;
			if ( ! empty( $item['cover'] ) ) {
				$cover_id = fwp_headless_app_sideload_media( $item['cover'] );
			}
			if ( ! $cover_id && ! empty( $gallery_ids ) ) {
				$cover_id = $gallery_ids[0];
			}

			update_post_meta( $post_id, 'cover_id', $cover_id );
			update_post_meta( $post_id, 'gallery_ids', $gallery_ids );
			update_post_meta( $post_id, 'photo_labels', $item['photo_labels'] ?? array() );
			update_post_meta( $post_id, 'card_icon', sanitize_key( $item['card_icon'] ?? 'moon' ) );

			if ( $cover_id ) {
				set_post_thumbnail( $post_id, $cover_id );
			}

			if ( ! empty( $item['catalog_line'] ) ) {
				wp_set_object_terms( $post_id, $item['catalog_line'], 'catalog_line', false );
			}
			if ( ! empty( $item['geo_area'] ) ) {
				wp_set_object_terms( $post_id, $item['geo_area'], 'geo_area', false );
			}
		}
	}

	// Team members.
	if ( ! empty( $data['team_member'] ) ) {
		foreach ( $data['team_member'] as $member ) {
			$seed_id = 'team-' . ( $member['sort_order'] ?? $member['name'] ?? wp_generate_password( 4, false ) );
			$existing = fwp_headless_app_find_post_by_seed_id( 'team_member', $seed_id );
			if ( $existing ) {
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'   => 'team_member',
					'post_title'  => $member['name'] ?? 'Team member',
					'post_status' => 'publish',
					'menu_order'  => (int) ( $member['sort_order'] ?? 0 ),
				)
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			++$stats['team_member'];
			update_post_meta( $post_id, 'fwp_seed_id', $seed_id );
			update_post_meta( $post_id, 'nick', $member['nick'] ?? '' );
			update_post_meta( $post_id, 'subtitle', $member['subtitle'] ?? '' );
			update_post_meta( $post_id, 'role_label', $member['role'] ?? '' );
			update_post_meta( $post_id, 'media_type', $member['media_type'] ?? 'photo' );
			update_post_meta( $post_id, 'media_url', $member['media_url'] ?? '' );

			if ( ! empty( $member['media_url'] ) ) {
				$media_id = fwp_headless_app_sideload_media( $member['media_url'] );
				update_post_meta( $post_id, 'media_id', $media_id );
				if ( $media_id && ( $member['media_type'] ?? '' ) === 'photo' ) {
					set_post_thumbnail( $post_id, $media_id );
				}
			}
		}
	}

	// FAQ items.
	if ( ! empty( $data['faq_item'] ) ) {
		foreach ( $data['faq_item'] as $faq ) {
			$seed_id = 'faq-' . ( $faq['sort_order'] ?? 0 );
			$existing = fwp_headless_app_find_post_by_seed_id( 'faq_item', $seed_id );
			if ( $existing ) {
				continue;
			}

			$scope = empty( $faq['scope'] ) ? 'global' : 'region';

			$post_id = wp_insert_post(
				array(
					'post_type'    => 'faq_item',
					'post_title'   => $faq['question'] ?? '',
					'post_content' => $faq['answer'] ?? '',
					'post_status'  => 'publish',
					'menu_order'   => (int) ( $faq['sort_order'] ?? 0 ),
				)
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			++$stats['faq_item'];
			update_post_meta( $post_id, 'fwp_seed_id', $seed_id );
			update_post_meta( $post_id, 'faq_scope', $scope );
			if ( ! empty( $faq['pages'] ) && is_array( $faq['pages'] ) ) {
				update_post_meta( $post_id, 'faq_pages', wp_json_encode( $faq['pages'] ) );
			} elseif ( empty( $faq['scope'] ) ) {
				update_post_meta( $post_id, 'faq_pages', wp_json_encode( array( '/' ) ) );
			}
		}
	}

	// Pages (shallow paths first so parent pages exist before children).
	if ( ! empty( $data['pages'] ) ) {
		$page_rows = $data['pages'];
		usort(
			$page_rows,
			static function ( $a, $b ) {
				$depth_a = substr_count( trim( (string) ( $a['slug'] ?? '' ), '/' ), '/' );
				$depth_b = substr_count( trim( (string) ( $b['slug'] ?? '' ), '/' ), '/' );
				return $depth_a <=> $depth_b;
			}
		);

		foreach ( $page_rows as $page ) {
			if ( empty( $page['slug'] ) && ! array_key_exists( 'slug', $page ) ) {
				continue;
			}

			$path_slug = trim( (string) $page['slug'], '/' );
			$post_name = ( '' === $path_slug ) ? 'home' : basename( $path_slug );
			$parent_id = 0;

			if ( '' !== $path_slug && str_contains( $path_slug, '/' ) ) {
				$parent_path = dirname( $path_slug );
				if ( '.' !== $parent_path && '' !== $parent_path ) {
					$parent_page = get_page_by_path( $parent_path );
					if ( $parent_page instanceof WP_Post ) {
						$parent_id = (int) $parent_page->ID;
					}
				}
			}

			$existing_page = null;
			if ( '' === $path_slug ) {
				$home_pages = get_posts(
					array(
						'post_type'      => 'page',
						'posts_per_page' => 1,
						'post_status'    => 'any',
						'meta_key'       => '_wp_page_template',
						'meta_value'     => fwp_headless_app_default_page_template_wp_slug(),
					)
				);
				$existing_page = $home_pages[0] ?? null;
			} else {
				$existing_page = get_page_by_path( $path_slug );
			}

			if ( $existing_page ) {
				if ( $parent_id > 0 && (int) $existing_page->post_parent !== $parent_id ) {
					wp_update_post(
						array(
							'ID'          => (int) $existing_page->ID,
							'post_parent' => $parent_id,
						)
					);
				}
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_title'   => $page['h1'] ?? $post_name,
					'post_status'  => 'publish',
					'post_name'    => $post_name,
					'post_parent'  => $parent_id,
					'post_content' => fwp_headless_app_default_page_content( $page ),
				)
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			++$stats['pages'];
			$template = fwp_headless_app_normalize_page_template(
				$page['template'] ?? fwp_headless_app_get_default_page_template(),
				null
			);
			update_post_meta(
				$post_id,
				'_wp_page_template',
				fwp_headless_app_api_template_to_wp_slug( $template ?: fwp_headless_app_get_default_page_template() )
			);
			update_post_meta( $post_id, 'h1', $page['h1'] ?? '' );
			update_post_meta( $post_id, 'meta_description', $page['meta_description'] ?? '' );
			if ( ! empty( $page['service_groups'] ) ) {
				update_post_meta( $post_id, 'service_groups', wp_json_encode( $page['service_groups'] ) );
			}
		}

		if ( function_exists( 'fwp_headless_app_migrate_region_hierarchy' ) ) {
			fwp_headless_app_migrate_region_hierarchy();
		}
	}

	$geo_terms = get_terms(
		array(
			'taxonomy'   => 'geo_area',
			'hide_empty' => false,
		)
	);
	$stats['geo_area'] = is_wp_error( $geo_terms ) ? 0 : count( $geo_terms );

	return $stats;
}

/**
 * Ensure option payloads export as JSON objects {}, not empty arrays [].
 *
 * @param mixed $value Option value.
 * @return array<string, mixed>
 */
function fwp_headless_app_site_option_object( $value ) {
	if ( empty( $value ) || ( is_array( $value ) && array_is_list( $value ) ) ) {
		return new \stdClass();
	}
	return (array) $value;
}

/**
 * Build site export payload from database (mirrors seed JSON shape).
 *
 * @return array<string, mixed>
 */
function fwp_headless_app_build_site_export() {
	$site_settings = fwp_headless_app_get_site_option_value(
		FWP_HEADLESS_APP_SITE_OPTION_SETTINGS,
		'4wp_headless_app_grv_site_settings',
		array()
	);
	if ( ! is_array( $site_settings ) ) {
		$site_settings = array();
	}
	$site_settings = fwp_headless_app_site_settings_with_logo_url( $site_settings );
	$site_settings = fwp_headless_app_site_settings_with_social_links( $site_settings );
	if ( function_exists( 'fwp_headless_app_site_settings_with_seo_urls' ) ) {
		$site_settings = fwp_headless_app_site_settings_with_seo_urls( $site_settings );
	}

	$nav_menus = fwp_headless_app_get_site_nav_menus();
	if ( function_exists( 'fwp_headless_app_build_regions_nav_items' ) ) {
		$from_geo = fwp_headless_app_build_regions_nav_items();
		if ( ! empty( $from_geo ) ) {
			if ( empty( $nav_menus['regions'] ) || ! is_array( $nav_menus['regions'] ) ) {
				$nav_menus['regions'] = $from_geo;
			} else {
				foreach ( $from_geo as $i => $geo_item ) {
					if ( isset( $nav_menus['regions'][ $i ] ) && is_array( $nav_menus['regions'][ $i ] ) ) {
						$nav_menus['regions'][ $i ]['href'] = $geo_item['href'];
						if ( empty( $nav_menus['regions'][ $i ]['label'] ) ) {
							$nav_menus['regions'][ $i ]['label'] = $geo_item['label'];
						}
					} else {
						$nav_menus['regions'][] = $geo_item;
					}
				}
			}
		}
	}

	$export = array(
		'content_model' => fwp_headless_app_get_content_model(),
		'site_settings' => fwp_headless_app_site_option_object( $site_settings ),
		'site_brand'    => fwp_headless_app_site_option_object(
			fwp_headless_app_get_site_option_value(
				FWP_HEADLESS_APP_SITE_OPTION_BRAND,
				'4wp_headless_app_grv_site_brand',
				array()
			)
		),
		'nav_menus'     => fwp_headless_app_site_option_object( $nav_menus ),
		'catalog_line'  => array(),
		'geo_area'      => array(),
		'work_item'     => array(),
		'team_member'   => array(),
		'faq_item'      => array(),
		'pages'         => array(),
	);

	$catalog_terms = get_terms( array( 'taxonomy' => 'catalog_line', 'hide_empty' => false, 'parent' => 0 ) );
	if ( ! is_wp_error( $catalog_terms ) ) {
		foreach ( $catalog_terms as $term ) {
			$export['catalog_line'][] = array(
				'slug'        => $term->slug,
				'label'       => $term->name,
				'href'        => get_term_meta( $term->term_id, 'href', true ),
				'description' => get_term_meta( $term->term_id, 'description', true ) ?: $term->description,
				'items'       => get_term_meta( $term->term_id, 'items', true ) ?: array(),
			);
		}
	}

	$geo_slug_map = function_exists( 'fwp_headless_app_get_published_page_slug_map' )
		? fwp_headless_app_get_published_page_slug_map()
		: array( 'by_id' => array(), 'by_slug' => array() );

	$geo_parents = function_exists( 'fwp_headless_app_get_geo_area_terms_ordered' )
		? fwp_headless_app_get_geo_area_terms_ordered( array( 'parent' => 0 ) )
		: get_terms( array( 'taxonomy' => 'geo_area', 'hide_empty' => false, 'parent' => 0 ) );
	if ( ! is_wp_error( $geo_parents ) ) {
		foreach ( $geo_parents as $parent ) {
			$cities = array();
			$children = function_exists( 'fwp_headless_app_get_geo_area_terms_ordered' )
				? fwp_headless_app_get_geo_area_terms_ordered( array( 'parent' => $parent->term_id ) )
				: get_terms( array( 'taxonomy' => 'geo_area', 'hide_empty' => false, 'parent' => $parent->term_id ) );
			if ( ! is_wp_error( $children ) ) {
				foreach ( $children as $child ) {
					$href = function_exists( 'fwp_headless_app_geo_city_href' )
						? fwp_headless_app_geo_city_href( $child->term_id, $child->slug, $geo_slug_map )
						: '/region/region-' . $child->slug;

					$cities[] = array(
						'slug'           => $child->slug,
						'name'           => $child->name,
						'name_locative'  => get_term_meta( $child->term_id, 'name_locative', true ),
						'display_name'   => get_term_meta( $child->term_id, 'display_name', true ),
						'services'       => get_term_meta( $child->term_id, 'services', true ) ?: array(),
						'description'    => get_term_meta( $child->term_id, 'description', true ) ?: $child->description,
						'hero_image'     => get_term_meta( $child->term_id, 'hero_image', true ),
						'href'           => $href,
						'page_id'        => (int) get_term_meta( $child->term_id, 'page_id', true ),
						'latitude'       => (string) get_term_meta( $child->term_id, FWP_HEADLESS_APP_GEO_LAT_META, true ),
						'longitude'      => (string) get_term_meta( $child->term_id, FWP_HEADLESS_APP_GEO_LNG_META, true ),
					);
				}
			}
			$export['geo_area'][] = array(
				'oblast'      => $parent->name,
				'short'       => $parent->slug,
				'color'       => get_term_meta( $parent->term_id, 'color', true ),
				'description' => get_term_meta( $parent->term_id, 'description', true ) ?: $parent->description,
				'cities'      => $cities,
			);
		}
	}

	$works = get_posts( array( 'post_type' => 'work_item', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
	foreach ( $works as $post ) {
		$gallery_ids = get_post_meta( $post->ID, 'gallery_ids', true ) ?: array();
		$gallery     = array();
		$gallery_meta = array();
		foreach ( $gallery_ids as $aid ) {
			$aid = (int) $aid;
			$url = wp_get_attachment_url( $aid );
			if ( ! $url ) {
				continue;
			}
			$gallery[] = $url;
			$meta_item = array( 'url' => $url );
			$mime      = get_post_mime_type( $aid );
			if ( $mime && str_starts_with( $mime, 'video/' ) && function_exists( 'fwp_headless_app_video_attachment_meta' ) ) {
				$video_meta = fwp_headless_app_video_attachment_meta( $aid );
				if ( ! empty( $video_meta['thumbnail_url'] ) ) {
					$meta_item['thumbnail_url'] = $video_meta['thumbnail_url'];
				}
			}
			$gallery_meta[] = $meta_item;
		}
		$cover_id = (int) get_post_meta( $post->ID, 'cover_id', true );
		if ( ! $cover_id ) {
			$cover_id = (int) get_post_thumbnail_id( $post->ID );
		}
		$card_icon = sanitize_key( (string) get_post_meta( $post->ID, 'card_icon', true ) );
		if ( ! $card_icon ) {
			$card_icon = 'moon';
		}
		$export['work_item'][] = array(
			'id'             => get_post_meta( $post->ID, 'fwp_seed_id', true ) ?: (string) $post->ID,
			'wp_id'          => (int) $post->ID,
			'title'          => $post->post_title,
			'location_label' => get_post_meta( $post->ID, 'location_label', true ),
			'catalog_line'   => wp_list_pluck( wp_get_post_terms( $post->ID, 'catalog_line' ), 'slug' ),
			'geo_area'       => wp_list_pluck( wp_get_post_terms( $post->ID, 'geo_area' ), 'slug' ),
			'cover'          => $cover_id ? wp_get_attachment_url( $cover_id ) : '',
			'gallery'        => $gallery,
			'gallery_meta'   => $gallery_meta,
			'photo_labels'   => get_post_meta( $post->ID, 'photo_labels', true ) ?: array(),
			'card_icon'      => $card_icon,
			'created_at'     => get_post_time( 'c', true, $post ),
		);
	}

	$team = get_posts( array( 'post_type' => 'team_member', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'menu_order' ) );
	foreach ( $team as $post ) {
		$media_id  = (int) get_post_meta( $post->ID, 'media_id', true );
		$media_url = (string) get_post_meta( $post->ID, 'media_url', true );
		if ( $media_id > 0 ) {
			$attachment_url = wp_get_attachment_url( $media_id );
			if ( $attachment_url ) {
				$media_url = $attachment_url;
			}
		}
		$media_type = get_post_meta( $post->ID, 'media_type', true ) ?: 'photo';
		$video_meta = ( 'video' === $media_type && $media_id > 0 && function_exists( 'fwp_headless_app_video_attachment_meta' ) )
			? fwp_headless_app_video_attachment_meta( $media_id )
			: array( 'thumbnail_url' => '', 'upload_date' => '' );

		$export['team_member'][] = array(
			'wp_id'               => (int) $post->ID,
			'sort_order'          => (int) $post->menu_order,
			'name'                => $post->post_title,
			'nick'                => get_post_meta( $post->ID, 'nick', true ),
			'role'                => get_post_meta( $post->ID, 'role_label', true ),
			'subtitle'            => get_post_meta( $post->ID, 'subtitle', true ),
			'media_type'          => $media_type,
			'media_url'           => $media_url,
			'media_id'            => $media_id,
			'media_thumbnail_url' => $video_meta['thumbnail_url'] ?? '',
			'media_upload_date'   => $video_meta['upload_date'] ?? '',
		);
	}

	$faqs = get_posts( array( 'post_type' => 'faq_item', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'menu_order' ) );
	foreach ( $faqs as $post ) {
		$scope     = get_post_meta( $post->ID, 'faq_scope', true );
		$faq_pages = fwp_headless_app_decode_faq_pages( get_post_meta( $post->ID, 'faq_pages', true ) );
		$export['faq_item'][] = array(
			'id'         => (int) $post->ID,
			'sort_order' => (int) $post->menu_order,
			'question'   => $post->post_title,
			'answer'     => apply_filters( 'the_content', $post->post_content ),
			'scope'      => ( $scope === 'region' ) ? array( 'geo_area' ) : array(),
			'pages'      => $faq_pages,
		);
	}

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);
	foreach ( $pages as $post ) {
		$template = fwp_headless_app_resolve_page_template( $post );

		$sg = get_post_meta( $post->ID, 'service_groups', true );
		$parent_slug = '';
		$parent_h1   = '';
		if ( $post->post_parent ) {
			$parent_post = get_post( (int) $post->post_parent );
			if ( $parent_post instanceof WP_Post ) {
				$parent_slug = fwp_headless_app_page_export_slug( $parent_post );
				$parent_h1   = get_post_meta( $parent_post->ID, 'h1', true ) ?: $parent_post->post_title;
			}
		}

		$entry = array(
			'slug'             => fwp_headless_app_page_export_slug( $post ),
			'template'         => $template,
			'h1'               => get_post_meta( $post->ID, 'h1', true ) ?: $post->post_title,
			'meta_description' => get_post_meta( $post->ID, 'meta_description', true ),
			'seo'              => fwp_headless_app_get_post_seo( $post->ID ),
			'parent_slug'      => $parent_slug,
			'parent_h1'        => $parent_h1,
		);

		if ( function_exists( 'fwp_headless_app_page_geo_export_meta' ) ) {
			$geo_meta = fwp_headless_app_page_geo_export_meta( $post->ID );
			if ( ! empty( $geo_meta ) ) {
				$entry = array_merge( $entry, $geo_meta );
			}
		}
		if ( $sg ) {
			$entry['service_groups'] = json_decode( $sg, true );
		}
		if ( $template === 'simple' ) {
			$entry['content_html'] = apply_filters( 'the_content', $post->post_content );
		} else {
			$sections = fwp_headless_app_parse_page_sections( $post->ID );
			if ( ! empty( $sections ) ) {
				$entry['sections'] = $sections;
			}
		}
		$export['pages'][] = $entry;
	}

	return $export;
}

/**
 * Register site export REST routes.
 */
function fwp_headless_app_register_site_export_rest_routes() {
	$callback = function () {
		return rest_ensure_response( fwp_headless_app_build_site_export() );
	};

	$route_args = array(
		'methods'             => 'GET',
		'callback'            => $callback,
		'permission_callback' => 'fwp_headless_app_rest_cms_api_permission_callback',
	);

	register_rest_route( FWP_HEADLESS_APP_REST_NAMESPACE, fwp_headless_app_get_site_export_rest_path(), $route_args );

	// Backward compatibility for existing frontends.
	register_rest_route( FWP_HEADLESS_APP_REST_NAMESPACE, '/grv', $route_args );
}

const FWP_HEADLESS_APP_CPT_INTERNAL_FLUSH_OPTION = '4wp_headless_app_cpt_internal_urls_v1';

add_action( 'admin_init', 'fwp_headless_app_maybe_flush_internal_cpt_rewrites', 9 );

/**
 * One-time flush after disabling public URLs for internal CPTs (faq, team, work).
 */
function fwp_headless_app_maybe_flush_internal_cpt_rewrites() {
	if ( ! is_admin() || get_option( FWP_HEADLESS_APP_CPT_INTERNAL_FLUSH_OPTION ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( FWP_HEADLESS_APP_CPT_INTERNAL_FLUSH_OPTION, '1' );
}
