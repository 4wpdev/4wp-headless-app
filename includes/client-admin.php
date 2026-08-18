<?php
/**
 * Client-facing admin — Editor role: edit any page, clean dashboard.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

const FWP_HEADLESS_APP_CLIENT_ROLE = 'editor';

/**
 * Headless site profiles only.
 */
function fwp_headless_app_client_admin_enabled() {
	return function_exists( 'fwp_headless_app_should_show_site_panel' )
		&& fwp_headless_app_should_show_site_panel();
}

/**
 * Current user is the simplified client role (Editor).
 */
function fwp_headless_app_is_client_user() {
	if ( ! fwp_headless_app_client_admin_enabled() ) {
		return false;
	}

	$user = wp_get_current_user();
	if ( ! $user->exists() ) {
		return false;
	}

	return in_array( FWP_HEADLESS_APP_CLIENT_ROLE, (array) $user->roles, true );
}

/**
 * Ensure Editor can manage all pages (any author, any status).
 */
function fwp_headless_app_setup_client_role_caps() {
	if ( ! fwp_headless_app_client_admin_enabled() ) {
		return;
	}

	$role = get_role( FWP_HEADLESS_APP_CLIENT_ROLE );
	if ( ! $role ) {
		return;
	}

	$page_caps = array(
		'edit_pages',
		'edit_others_pages',
		'edit_published_pages',
		'edit_private_pages',
		'publish_pages',
		'delete_pages',
		'delete_others_pages',
		'delete_published_pages',
		'delete_private_pages',
		'read_private_pages',
	);

	foreach ( $page_caps as $cap ) {
		$role->add_cap( $cap );
	}

	foreach ( array( 'work_item', 'team_member', 'faq_item' ) as $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			continue;
		}

		$object = get_post_type_object( $post_type );
		if ( ! $object || empty( $object->cap ) ) {
			continue;
		}

		foreach ( array( 'edit_posts', 'edit_others_posts', 'edit_published_posts', 'edit_private_posts', 'publish_posts', 'delete_posts', 'delete_others_posts', 'delete_published_posts', 'delete_private_posts', 'read_private_posts' ) as $cap_key ) {
			if ( ! empty( $object->cap->$cap_key ) ) {
				$role->add_cap( $object->cap->$cap_key );
			}
		}
	}
}

/**
 * Allow Editors to edit any page regardless of author.
 *
 * @param string[] $caps    Required caps.
 * @param string   $cap     Requested cap.
 * @param int      $user_id User ID.
 * @param array    $args    Extra args (post ID).
 * @return string[]
 */
function fwp_headless_app_client_map_page_caps( $caps, $cap, $user_id, $args ) {
	if ( ! fwp_headless_app_client_admin_enabled() || 'edit_page' !== $cap || empty( $args[0] ) ) {
		return $caps;
	}

	$user = get_userdata( $user_id );
	if ( ! $user || ! in_array( FWP_HEADLESS_APP_CLIENT_ROLE, (array) $user->roles, true ) ) {
		return $caps;
	}

	$post = get_post( (int) $args[0] );
	if ( ! $post || 'page' !== $post->post_type ) {
		return $caps;
	}

	return array( 'edit_pages' );
}

/**
 * Skip dashboard — go straight to Pages (mobile-friendly).
 */
function fwp_headless_app_client_redirect_dashboard() {
	if ( ! fwp_headless_app_is_client_user() ) {
		return;
	}

	wp_safe_redirect( admin_url( 'edit.php?post_type=page' ) );
	exit;
}

/**
 * Remove default dashboard widgets if the screen is opened.
 */
function fwp_headless_app_clear_client_dashboard() {
	if ( ! fwp_headless_app_is_client_user() ) {
		return;
	}

	remove_action( 'welcome_panel', 'wp_welcome_panel' );

	global $wp_meta_boxes;
	unset( $wp_meta_boxes['dashboard'] );
}

/**
 * Remove Comments and Tools from admin menu (Editor only).
 */
function fwp_headless_app_trim_client_admin_menu() {
	if ( ! fwp_headless_app_is_client_user() ) {
		return;
	}

	remove_menu_page( 'edit-comments.php' );
	remove_menu_page( 'tools.php' );
}

/**
 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
 */
function fwp_headless_app_trim_client_admin_bar( $wp_admin_bar ) {
	if ( ! fwp_headless_app_is_client_user() ) {
		return;
	}

	$wp_admin_bar->remove_node( 'comments' );
}

/**
 * GRV custom blocks allowed for client Editor inserter.
 *
 * @return string[]
 */
function fwp_headless_app_get_grv_block_names() {
	return array(
		'grv/cta-advanced',
		'grv/services-section',
		'grv/how-it-works',
		'grv/linked-section',
		'grv/regions-section',
		'grv/hero-slider',
		'grv/faq-section',
		'grv/cta-strip',
		'grv/cta-card',
		'grv/social-links-bar',
		'grv/works-gallery',
		'grv/team-section',
		'grv/cta-related',
		'grv/steps-section',
		'grv/contacts-section',
		'grv/rich-text',
	);
}

/**
 * Core blocks needed inside «Розширений текст» (client allowlist).
 *
 * @return string[]
 */
function fwp_headless_app_get_rich_text_inner_block_names() {
	return array(
		'core/paragraph',
		'core/heading',
		'core/list',
		'core/list-item',
		'core/quote',
		'core/image',
		'core/separator',
		'core/table',
		'core/html',
		'core/freeform',
		'core/embed',
		'core/buttons',
		'core/button',
		'core/spacer',
	);
}

/**
 * Editor inserter — GRV blocks only.
 *
 * @param bool|string[]            $allowed_block_types Allowed blocks.
 * @param WP_Block_Editor_Context  $context             Editor context.
 * @return bool|string[]
 */
function fwp_headless_app_client_allowed_blocks( $allowed_block_types, $context ) {
	if ( ! fwp_headless_app_is_client_user() ) {
		return $allowed_block_types;
	}

	return array_values(
		array_unique(
			array_merge(
				fwp_headless_app_get_grv_block_names(),
				fwp_headless_app_get_rich_text_inner_block_names()
			)
		)
	);
}

/**
 * Block inserter categories — GRV BUILD only.
 *
 * @param array<int, array<string, mixed>> $categories Block categories.
 * @param WP_Block_Editor_Context          $context    Editor context.
 * @return array<int, array<string, mixed>>
 */
function fwp_headless_app_client_block_categories( $categories, $context ) {
	if ( ! fwp_headless_app_is_client_user() ) {
		return $categories;
	}

	foreach ( $categories as $category ) {
		if ( ( $category['slug'] ?? '' ) === 'grv' ) {
			return array( $category );
		}
	}

	return array(
		array(
			'slug'  => 'grv',
			'title' => __( 'GRV BUILD', '4wp-headless-app' ),
			'icon'  => null,
		),
	);
}

/**
 * Disable dashboard welcome panel and onboarding for Editor.
 */
function fwp_headless_app_disable_client_welcome() {
	if ( ! fwp_headless_app_is_client_user() ) {
		return;
	}

	update_user_meta( get_current_user_id(), 'show_welcome_panel', 0 );
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
}

/**
 * @param array<string, mixed> $editor_settings Block editor settings.
 * @param WP_Block_Editor_Context $context       Editor context.
 * @return array<string, mixed>
 */
function fwp_headless_app_block_editor_settings( $editor_settings, $context ) {
	if ( ! fwp_headless_app_is_client_user() ) {
		return $editor_settings;
	}

	if ( isset( $editor_settings['enableWelcomeGuide'] ) ) {
		$editor_settings['enableWelcomeGuide'] = false;
	}

	return $editor_settings;
}

/**
 * Turn off block editor welcome guide (Gutenberg onboarding).
 */
function fwp_headless_app_disable_client_welcome_guide_script() {
	if ( ! fwp_headless_app_is_client_user() ) {
		return;
	}

	$script = <<<'JS'
wp.domReady(function () {
	try {
		var dispatch = wp.data.dispatch('core/preferences');
		if (dispatch) {
			dispatch.set('core', 'welcomeGuide', false);
		}
	} catch (e) {}
});
JS;

	wp_add_inline_script( 'wp-edit-post', $script, 'after' );
	wp_add_inline_script( 'wp-edit-site', $script, 'after' );
}

/**
 * @param array<int, string> $pointers Admin pointer IDs.
 * @return array<int, string>
 */
function fwp_headless_app_disable_client_pointers( $pointers ) {
	if ( ! fwp_headless_app_is_client_user() ) {
		return $pointers;
	}

	return array();
}

/**
 * Disable dashboard welcome + block editor intro for client users.
 */
function fwp_headless_app_disable_client_onboarding() {
	if ( ! fwp_headless_app_is_client_user() ) {
		return;
	}

	update_user_meta( get_current_user_id(), 'show_welcome_panel', 0 );

	$prefs = get_user_meta( get_current_user_id(), 'wp_persisted_preferences', true );
	if ( ! is_array( $prefs ) ) {
		$prefs = array();
	}
	if ( ! isset( $prefs['core'] ) || ! is_array( $prefs['core'] ) ) {
		$prefs['core'] = array();
	}
	$prefs['core']['welcomeGuide'] = false;
	update_user_meta( get_current_user_id(), 'wp_persisted_preferences', $prefs );
}

/**
 * Block editor — no welcome guide, tighter mobile layout.
 */
function fwp_headless_app_client_block_editor_assets() {
	if ( ! fwp_headless_app_is_client_user() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );

	wp_enqueue_style(
		'grv-client-editor',
		plugins_url( 'assets/admin/client-editor.css', $plugin_root . '/4wp-headless-app.php' ),
		array(),
		FWP_HEADLESS_APP_VERSION
	);

	if ( wp_script_is( 'wp-edit-post', 'registered' ) ) {
		wp_add_inline_script(
			'wp-edit-post',
			"(function(){try{wp.data.dispatch('core/preferences').set('core','welcomeGuide',false);}catch(e){}})();",
			'after'
		);
	}
}

/**
 * Profile page — hide admin color scheme and keyboard shortcuts for Editor.
 */
function fwp_headless_app_trim_client_profile() {
	if ( ! fwp_headless_app_is_client_user() ) {
		return;
	}

	remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );
}

/**
 * Hide leftover dashboard / dev notices for client users.
 */
function fwp_headless_app_client_admin_styles() {
	if ( ! fwp_headless_app_is_client_user() ) {
		return;
	}

	$css = '#welcome-panel,
		.edit-post-welcome-guide,
		.edit-site-welcome-guide,
		#dashboard-widgets-wrap,
		#dashboard-widgets,
		#screen-meta-links { display: none !important; }';

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && in_array( $screen->id, array( 'profile', 'user-edit' ), true ) ) {
		$css .= '
		.user-comment-shortcuts-wrap { display: none !important; }';
	}

	wp_add_inline_style( 'common', $css );
}

add_filter( 'allowed_block_types_all', 'fwp_headless_app_client_allowed_blocks', 10, 2 );
add_filter( 'block_categories_all', 'fwp_headless_app_client_block_categories', 999, 2 );
add_action( 'admin_init', 'fwp_headless_app_disable_client_welcome', 0 );
add_filter( 'block_editor_settings_all', 'fwp_headless_app_block_editor_settings', 10, 2 );
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_disable_client_welcome_guide_script', 100 );
add_filter( 'wp_pointers', 'fwp_headless_app_disable_client_pointers' );
add_action( 'admin_menu', 'fwp_headless_app_trim_client_admin_menu', 999 );
add_action( 'admin_bar_menu', 'fwp_headless_app_trim_client_admin_bar', 999 );
add_action( 'admin_init', 'fwp_headless_app_disable_client_onboarding' );
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_client_block_editor_assets' );
add_action( 'init', 'fwp_headless_app_setup_client_role_caps', 20 );
add_filter( 'map_meta_cap', 'fwp_headless_app_client_map_page_caps', 10, 4 );
add_action( 'load-index.php', 'fwp_headless_app_client_redirect_dashboard' );
add_action( 'wp_dashboard_setup', 'fwp_headless_app_clear_client_dashboard', 999 );
add_action( 'admin_init', 'fwp_headless_app_trim_client_profile' );
add_action( 'admin_enqueue_scripts', 'fwp_headless_app_client_admin_styles' );
