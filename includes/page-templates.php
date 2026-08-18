<?php
/**
 * Headless page templates — registered from the plugin (no theme file copies).
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

const FWP_HEADLESS_APP_PAGE_TEMPLATES_OPTION   = '4wp_headless_app_page_templates_migrated_v3';
const FWP_HEADLESS_APP_PAGE_TEMPLATES_V4       = '4wp_headless_app_page_templates_migrated_v4';
const FWP_HEADLESS_APP_PAGE_TEMPLATES_V5       = '4wp_headless_app_page_templates_migrated_v5';
const FWP_HEADLESS_APP_THEME_TEMPLATES_VERSION = '4wp_theme_templates_v4';
const FWP_HEADLESS_APP_TEMPLATE_PLUGIN_SLUG    = '4wp-headless-app';

/**
 * Default headless page template (App Page / custom GRV blocks).
 *
 * @return string API slug.
 */
function fwp_headless_app_get_default_page_template() {
	return 'blocks';
}

/**
 * @return string WP block template slug for the default template.
 */
function fwp_headless_app_default_page_template_wp_slug() {
	return fwp_headless_app_api_template_to_wp_slug( fwp_headless_app_get_default_page_template() );
}

/**
 * Legacy API slugs from v1–v3 (all map to blocks layout in React).
 *
 * @return string[]
 */
function fwp_headless_app_legacy_block_template_slugs() {
	return array(
		'home',
		'service_v1',
		'service_v2',
		'service_v3',
		'about',
		'portfolio',
		'contact',
		'region',
		'service',
	);
}

/**
 * API template slug → React layout metadata.
 *
 * @return array<string, array{label: string, description: string}>
 */
function fwp_headless_app_get_page_templates() {
	return array(
		'blocks' => array(
			'label'       => 'App Page',
			'description' => __( 'За замовчуванням — custom GRV блоки', '4wp-headless-app' ),
		),
		'simple' => array(
			'label'       => 'Simple Page',
			'description' => __( 'Текст з редактора (Terms, Policy)', '4wp-headless-app' ),
		),
	);
}

/**
 * Map legacy API slug to current export slug.
 *
 * @param string $api_slug Raw slug from DB or seed.
 * @return string Current slug (`blocks`, `simple`) or empty.
 */
function fwp_headless_app_legacy_api_template_to_current( $api_slug ) {
	if ( ! is_string( $api_slug ) || $api_slug === '' ) {
		return '';
	}

	if ( fwp_headless_app_is_valid_page_template( $api_slug ) ) {
		return $api_slug;
	}

	if ( in_array( $api_slug, fwp_headless_app_legacy_block_template_slugs(), true ) ) {
		return 'blocks';
	}

	return '';
}

/**
 * @param string $api_slug API template slug (blocks, simple, …).
 * @return string WP block template slug (4wp-blocks, 4wp-simple, …).
 */
function fwp_headless_app_api_template_to_wp_slug( $api_slug ) {
	$current = fwp_headless_app_legacy_api_template_to_current( $api_slug );
	if ( $current === '' ) {
		return '';
	}

	return '4wp-' . str_replace( '_', '-', $current );
}

/**
 * @param string $wp_slug WP block template slug.
 * @return string API template slug or empty.
 */
function fwp_headless_app_wp_slug_to_api_template( $wp_slug ) {
	if ( ! is_string( $wp_slug ) || ! str_starts_with( $wp_slug, '4wp-' ) ) {
		return '';
	}

	$api_slug = str_replace( '-', '_', substr( $wp_slug, 4 ) );

	return fwp_headless_app_legacy_api_template_to_current( $api_slug );
}

/**
 * @param string $template Template slug.
 * @return bool
 */
function fwp_headless_app_is_valid_page_template( $template ) {
	return is_string( $template ) && $template !== '' && isset( fwp_headless_app_get_page_templates()[ $template ] );
}

/**
 * @param string       $template Raw template value.
 * @param WP_Post|null $post     Page post (unused; kept for callers).
 * @return string API template slug or empty.
 */
function fwp_headless_app_normalize_page_template( $template, $post = null ) {
	unset( $post );

	if ( fwp_headless_app_is_valid_page_template( $template ) ) {
		return $template;
	}

	$mapped = fwp_headless_app_legacy_api_template_to_current( $template );
	if ( $mapped !== '' ) {
		return $mapped;
	}

	$wp_api = fwp_headless_app_wp_slug_to_api_template( $template );
	if ( $wp_api !== '' ) {
		return $wp_api;
	}

	return '';
}

/**
 * Human-readable title for a headless page template.
 *
 * @param string $api_slug API template slug.
 * @return string
 */
function fwp_headless_app_get_page_template_title( $api_slug ) {
	$meta = fwp_headless_app_get_page_templates()[ $api_slug ] ?? null;
	if ( ! $meta ) {
		return '';
	}

	return $meta['label'] . ' — ' . $meta['description'];
}

/**
 * Register custom page templates in theme.json (native «Шаблон» picker metadata).
 *
 * @param WP_Theme_JSON_Data $theme_json Theme JSON data.
 * @return WP_Theme_JSON_Data
 */
function fwp_headless_app_extend_theme_json_templates( $theme_json ) {
	$data             = $theme_json->get_data();
	$custom_templates = $data['customTemplates'] ?? array();
	$existing_names   = wp_list_pluck( $custom_templates, 'name' );

	foreach ( fwp_headless_app_get_page_templates() as $api_slug => $meta ) {
		$name = fwp_headless_app_api_template_to_wp_slug( $api_slug );
		if ( in_array( $name, $existing_names, true ) ) {
			continue;
		}
		$custom_templates[] = array(
			'name'      => $name,
			'postTypes' => array( 'page' ),
			'title'     => fwp_headless_app_get_page_template_title( $api_slug ),
		);
	}

	return $theme_json->update_with(
		array(
			'version'         => 3,
			'customTemplates' => $custom_templates,
		)
	);
}
add_filter( 'wp_theme_json_data_theme', 'fwp_headless_app_extend_theme_json_templates' );

/**
 * Register block templates from the plugin directory (WP 6.7+).
 */
function fwp_headless_app_register_plugin_block_templates() {
	if ( ! function_exists( 'register_block_template' ) ) {
		return;
	}

	$templates_dir = dirname( __DIR__ ) . '/templates';

	foreach ( fwp_headless_app_get_page_templates() as $api_slug => $meta ) {
		$slug      = fwp_headless_app_api_template_to_wp_slug( $api_slug );
		$file_path = $templates_dir . '/' . $slug . '.html';
		$content   = is_readable( $file_path ) ? file_get_contents( $file_path ) : "<!-- wp:post-content /-->\n";

		register_block_template(
			FWP_HEADLESS_APP_TEMPLATE_PLUGIN_SLUG . '//' . $slug,
			array(
				'title'       => fwp_headless_app_get_page_template_title( $api_slug ),
				'description' => $meta['description'],
				'content'     => $content,
				'post_types'  => array( 'page' ),
			)
		);
	}
}
add_action( 'init', 'fwp_headless_app_register_plugin_block_templates' );

/**
 * Ensure all headless templates appear in the page editor picker.
 *
 * @param string[]     $post_templates Template slug => title.
 * @param WP_Theme     $theme          Active theme.
 * @param WP_Post|null $post           Page being edited.
 * @param string       $post_type      Post type.
 * @return string[]
 */
function fwp_headless_app_filter_theme_page_templates( $post_templates, $theme, $post, $post_type ) {
	unset( $theme, $post );

	if ( 'page' !== $post_type ) {
		return $post_templates;
	}

	foreach ( fwp_headless_app_get_page_templates() as $api_slug => $meta ) {
		$slug                    = fwp_headless_app_api_template_to_wp_slug( $api_slug );
		$post_templates[ $slug ] = fwp_headless_app_get_page_template_title( $api_slug );
	}

	return $post_templates;
}
add_filter( 'theme_page_templates', 'fwp_headless_app_filter_theme_page_templates', 10, 4 );

/**
 * Classic editor + REST: new pages start with App Page template.
 *
 * @param string $template Current default template slug.
 * @return string
 */
function fwp_headless_app_filter_default_page_template( $template ) {
	return fwp_headless_app_default_page_template_wp_slug();
}
add_filter( 'default_page_template', 'fwp_headless_app_filter_default_page_template' );

/**
 * Persist default template when a page is saved without an explicit template.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an existing post.
 */
function fwp_headless_app_assign_default_page_template_on_save( $post_id, $post, $update ) {
	unset( $update );

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! $post instanceof WP_Post || $post->post_type !== 'page' ) {
		return;
	}

	if ( $post->post_status === 'auto-draft' ) {
		return;
	}

	$wp_slug = get_page_template_slug( $post );
	if ( $wp_slug !== '' && fwp_headless_app_wp_slug_to_api_template( $wp_slug ) !== '' ) {
		return;
	}

	update_post_meta( $post_id, '_wp_page_template', fwp_headless_app_default_page_template_wp_slug() );
}
add_action( 'save_post_page', 'fwp_headless_app_assign_default_page_template_on_save', 10, 3 );

/**
 * One-time cleanup: remove legacy theme copies and DB duplicates from v1/v2 approach.
 */
function fwp_headless_app_migrate_plugin_templates() {
	if ( get_option( FWP_HEADLESS_APP_THEME_TEMPLATES_VERSION ) === '4' ) {
		return;
	}

	$theme = get_stylesheet();
	$slugs = array_merge(
		array_map( 'fwp_headless_app_api_template_to_wp_slug', array_keys( fwp_headless_app_get_page_templates() ) ),
		array(
			'4wp-home',
			'4wp-service-v1',
			'4wp-service-v2',
			'4wp-service-v3',
			'4wp-about',
			'4wp-portfolio',
			'4wp-contact',
			'4wp-region',
		)
	);

	foreach ( array_unique( $slugs ) as $slug ) {
		$theme_file = get_stylesheet_directory() . '/templates/' . $slug . '.html';
		if ( is_file( $theme_file ) ) {
			wp_delete_file( $theme_file );
		}

		$db_templates = get_posts(
			array(
				'post_type'              => 'wp_template',
				'name'                   => $slug,
				'posts_per_page'         => -1,
				'post_status'            => 'any',
				'update_post_term_cache' => false,
				'fields'                 => 'ids',
				'tax_query'              => array(
					array(
						'taxonomy' => 'wp_theme',
						'field'    => 'name',
						'terms'    => $theme,
					),
				),
			)
		);

		foreach ( $db_templates as $post_id ) {
			wp_delete_post( (int) $post_id, true );
		}
	}

	update_option( FWP_HEADLESS_APP_THEME_TEMPLATES_VERSION, '4', false );
	wp_clean_themes_cache( false );

	if ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
		WP_Theme_JSON_Resolver::clean_cached_data();
	}
}
add_action( 'admin_init', 'fwp_headless_app_migrate_plugin_templates', 5 );

add_action( 'admin_init', 'fwp_headless_app_migrate_to_native_page_templates' );

/**
 * Migrate custom meta + legacy service → native _wp_page_template.
 */
function fwp_headless_app_migrate_to_native_page_templates() {
	if ( get_option( FWP_HEADLESS_APP_PAGE_TEMPLATES_OPTION ) ) {
		return;
	}

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		)
	);

	foreach ( $pages as $post ) {
		$wp_slug = get_page_template_slug( $post );
		if ( $wp_slug && fwp_headless_app_wp_slug_to_api_template( $wp_slug ) ) {
			delete_post_meta( $post->ID, 'page_template_slug' );
			continue;
		}

		$legacy = get_post_meta( $post->ID, 'page_template_slug', true );
		$api    = fwp_headless_app_normalize_page_template( is_string( $legacy ) ? $legacy : '', $post );

		if ( $api === '' ) {
			$front_id = (int) get_option( 'page_on_front' );
			if ( $front_id && (int) $post->ID === $front_id ) {
				$api = 'blocks';
			}
		}

		if ( $api !== '' ) {
			update_post_meta( $post->ID, '_wp_page_template', fwp_headless_app_api_template_to_wp_slug( $api ) );
		}

		delete_post_meta( $post->ID, 'page_template_slug' );
	}

	update_option( FWP_HEADLESS_APP_PAGE_TEMPLATES_OPTION, 1, false );
}

/**
 * v4: map legacy WP template slugs (4wp-home, …) → 4wp-blocks.
 */
function fwp_headless_app_migrate_page_templates_v4() {
	if ( get_option( FWP_HEADLESS_APP_PAGE_TEMPLATES_V4 ) ) {
		return;
	}

	$legacy_wp_slugs = array(
		'4wp-home',
		'4wp-service-v1',
		'4wp-service-v2',
		'4wp-service-v3',
		'4wp-about',
		'4wp-portfolio',
		'4wp-contact',
		'4wp-region',
	);

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		)
	);

	$blocks_wp = fwp_headless_app_api_template_to_wp_slug( 'blocks' );

	foreach ( $pages as $post ) {
		$wp_slug = get_page_template_slug( $post );
		if ( $wp_slug && in_array( $wp_slug, $legacy_wp_slugs, true ) ) {
			update_post_meta( $post->ID, '_wp_page_template', $blocks_wp );
		}
	}

	update_option( FWP_HEADLESS_APP_PAGE_TEMPLATES_V4, 1, false );
}
add_action( 'admin_init', 'fwp_headless_app_migrate_page_templates_v4', 6 );

/**
 * v5: pages without _wp_page_template → App Page (blocks).
 */
function fwp_headless_app_migrate_page_templates_v5() {
	if ( get_option( FWP_HEADLESS_APP_PAGE_TEMPLATES_V5 ) ) {
		return;
	}

	$default_wp = fwp_headless_app_default_page_template_wp_slug();

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		)
	);

	foreach ( $pages as $post ) {
		$wp_slug = get_page_template_slug( $post );
		if ( $wp_slug === '' || fwp_headless_app_wp_slug_to_api_template( $wp_slug ) === '' ) {
			update_post_meta( $post->ID, '_wp_page_template', $default_wp );
		}
	}

	update_option( FWP_HEADLESS_APP_PAGE_TEMPLATES_V5, 1, false );
}
add_action( 'admin_init', 'fwp_headless_app_migrate_page_templates_v5', 7 );

/**
 * Assign native blocks template when a page is set as front page.
 *
 * @param mixed $old_value Previous front page ID.
 * @param mixed $value     New front page ID.
 */
function fwp_headless_app_sync_front_page_template( $old_value, $value ) {
	unset( $old_value );

	$page_id = (int) $value;
	if ( ! $page_id ) {
		return;
	}

	$current = get_page_template_slug( $page_id );
	if ( $current && fwp_headless_app_wp_slug_to_api_template( $current ) ) {
		return;
	}

	update_post_meta( $page_id, '_wp_page_template', fwp_headless_app_default_page_template_wp_slug() );
}
add_action( 'update_option_page_on_front', 'fwp_headless_app_sync_front_page_template', 10, 2 );
