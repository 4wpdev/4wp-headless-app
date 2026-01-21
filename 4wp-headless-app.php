<?php
/**
 * Plugin Name: 4WP Headless App
 * Plugin URI: https://anatoliy.local/
 * Description: Headless API layer for the personal site app.
 * Version: 0.1.0
 * Author: Anatoliy Dovgun
 * Author URI: https://anatoliy.local/
 * License: GPL-2.0-or-later
 * Text Domain: 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

const FWP_HEADLESS_APP_VERSION = '0.1.0';
const FWP_HEADLESS_APP_OPTION_GROUP = '4wp_headless_app_settings';
const FWP_HEADLESS_APP_OPTION_APP_KEY = '4wp_headless_app_key';
const FWP_HEADLESS_APP_OPTION_SOURCE_TYPE = '4wp_headless_app_source_type';
const FWP_HEADLESS_APP_OPTION_SOURCE_URL = '4wp_headless_app_source_url';
const FWP_HEADLESS_APP_OPTION_SETTINGS_DATA = '4wp_headless_app_settings_data';
const FWP_HEADLESS_APP_OPTION_THEME_JSON = '4wp_headless_app_theme_json';
const FWP_HEADLESS_APP_ACTIVATE_NOTICE = '4wp_headless_app_activate_notice';
const FWP_HEADLESS_APP_APPLY_NOTICE = '4wp_headless_app_apply_notice';
const FWP_HEADLESS_APP_REST_NAMESPACE = '4wp/v1';

require_once __DIR__ . '/includes/app-registry.php';

register_activation_hook( __FILE__, 'fwp_headless_app_on_activate' );
add_action( 'admin_notices', 'fwp_headless_app_admin_notices' );
add_action( 'admin_menu', 'fwp_headless_app_register_menu' );
add_action( 'admin_init', 'fwp_headless_app_register_settings' );
add_action( 'init', 'fwp_headless_app_register_cpts' );
add_action( 'rest_api_init', 'fwp_headless_app_register_rest_routes' );
add_action( 'admin_post_4wp_headless_app_apply_profile', 'fwp_headless_app_apply_profile' );
add_action( 'add_meta_boxes', 'fwp_headless_app_register_meta_boxes' );
add_action( 'save_post_fwp_project', 'fwp_headless_app_save_project_meta' );
add_action( 'save_post_fwp_skill', 'fwp_headless_app_save_skill_meta' );
add_action( 'save_post_fwp_service', 'fwp_headless_app_save_service_meta' );
add_action( 'save_post_fwp_experience', 'fwp_headless_app_save_experience_meta' );
add_action( 'rest_api_init', 'fwp_headless_app_register_contact_routes' );
add_action( 'fwp_project_category_add_form_fields', 'fwp_headless_app_render_project_category_add_fields' );
add_action( 'fwp_project_category_edit_form_fields', 'fwp_headless_app_render_project_category_edit_fields' );
add_action( 'created_fwp_project_category', 'fwp_headless_app_save_project_category_fields' );
add_action( 'edited_fwp_project_category', 'fwp_headless_app_save_project_category_fields' );

function fwp_headless_app_on_activate() {
	set_transient( FWP_HEADLESS_APP_ACTIVATE_NOTICE, '1', 60 );
}

function fwp_headless_app_admin_notices() {
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	$needs_notice = false;
	if ( get_transient( FWP_HEADLESS_APP_ACTIVATE_NOTICE ) ) {
		delete_transient( FWP_HEADLESS_APP_ACTIVATE_NOTICE );
		$needs_notice = true;
	}

	$app_key = get_option( FWP_HEADLESS_APP_OPTION_APP_KEY, '' );
	$settings_url = admin_url( 'admin.php?page=4wp-headless-app' );

	if ( empty( $app_key ) ) {
		?>
		<div class="notice notice-warning">
			<p>
				<?php echo esc_html__( '4WP Headless App needs an application selected to configure CPTs and default data.', '4wp-headless-app' ); ?>
				<a href="<?php echo esc_url( $settings_url ); ?>">
					<?php echo esc_html__( 'Select application', '4wp-headless-app' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	if ( get_transient( FWP_HEADLESS_APP_APPLY_NOTICE ) ) {
		delete_transient( FWP_HEADLESS_APP_APPLY_NOTICE );
		?>
		<div class="notice notice-success">
			<p><?php echo esc_html__( 'App profile applied successfully.', '4wp-headless-app' ); ?></p>
		</div>
		<?php
	}

	if ( fwp_headless_app_is_create_block_theme_active() ) {
		return;
	}

	$needs_notice = true;
	if ( ! $needs_notice ) {
		return;
	}

	$install_url = wp_nonce_url(
		self_admin_url( 'update.php?action=install-plugin&plugin=create-block-theme' ),
		'install-plugin_create-block-theme'
	);

	$details_url = 'https://wordpress.org/plugins/create-block-theme/';
	?>
	<div class="notice notice-warning">
		<p>
			<?php echo esc_html__( '4WP Headless App recommends installing the Create Block Theme plugin to manage theme.json easily.', '4wp-headless-app' ); ?>
			<a href="<?php echo esc_url( $install_url ); ?>">
				<?php echo esc_html__( 'Install Create Block Theme', '4wp-headless-app' ); ?>
			</a>
			|
			<a href="<?php echo esc_url( $details_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html__( 'View plugin', '4wp-headless-app' ); ?>
			</a>
		</p>
	</div>
	<?php
}

function fwp_headless_app_is_create_block_theme_active() {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	return is_plugin_active( 'create-block-theme/create-block-theme.php' );
}

function fwp_headless_app_register_menu() {
	$general_title = fwp_headless_app_get_profile_menu_title();
	$general_page_title = $general_title . ' Settings';

	add_menu_page(
		'4WP Headless App',
		'4WP Headless App',
		'manage_options',
		'4wp-headless-app',
		'fwp_headless_app_render_settings_page',
		'dashicons-rest-api'
	);

	add_submenu_page(
		'4wp-headless-app',
		'Settings',
		'Settings',
		'manage_options',
		'4wp-headless-app',
		'fwp_headless_app_render_settings_page'
	);

	add_submenu_page(
		'4wp-headless-app',
		$general_page_title,
		$general_title,
		'manage_options',
		'4wp-headless-app-general',
		'fwp_headless_app_render_general_settings_page'
	);
}

function fwp_headless_app_register_settings() {
	register_setting(
		FWP_HEADLESS_APP_OPTION_GROUP,
		FWP_HEADLESS_APP_OPTION_APP_KEY,
		array(
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default' => '',
		)
	);

	register_setting(
		FWP_HEADLESS_APP_OPTION_GROUP,
		FWP_HEADLESS_APP_OPTION_SOURCE_TYPE,
		array(
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default' => 'url',
		)
	);

	register_setting(
		FWP_HEADLESS_APP_OPTION_GROUP,
		FWP_HEADLESS_APP_OPTION_SOURCE_URL,
		array(
			'type' => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default' => '',
		)
	);

	add_settings_section(
		'fwp_headless_app_main',
		'App Source',
		'__return_false',
		'4wp-headless-app'
	);

	add_settings_field(
		FWP_HEADLESS_APP_OPTION_APP_KEY,
		'Application',
		'fwp_headless_app_render_app_field',
		'4wp-headless-app',
		'fwp_headless_app_main'
	);

	add_settings_field(
		FWP_HEADLESS_APP_OPTION_SOURCE_TYPE,
		'Source type',
		'fwp_headless_app_render_source_type_field',
		'4wp-headless-app',
		'fwp_headless_app_main'
	);

	add_settings_field(
		FWP_HEADLESS_APP_OPTION_SOURCE_URL,
		'Source URL',
		'fwp_headless_app_render_source_url_field',
		'4wp-headless-app',
		'fwp_headless_app_main'
	);

	register_setting(
		'4wp_headless_app_general_settings',
		FWP_HEADLESS_APP_OPTION_SETTINGS_DATA,
		array(
			'type' => 'array',
			'sanitize_callback' => 'fwp_headless_app_sanitize_general_settings',
			'default' => array(),
		)
	);
}

function fwp_headless_app_render_app_field() {
	$value = get_option( FWP_HEADLESS_APP_OPTION_APP_KEY, '' );
	$apps = fwp_headless_app_get_apps();
	$selected_app = isset( $apps[ $value ] ) ? $apps[ $value ] : null;
	?>
	<select name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_APP_KEY ); ?>">
		<option value="">
			<?php echo esc_html__( 'Select an app', '4wp-headless-app' ); ?>
		</option>
		<?php foreach ( $apps as $app_key => $app ) : ?>
			<option value="<?php echo esc_attr( $app_key ); ?>" <?php selected( $value, $app_key ); ?>>
				<?php echo esc_html( $app['name'] ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php if ( ! empty( $apps ) ) : ?>
		<p class="description">
			<?php echo esc_html( $selected_app ? $selected_app['description'] : $apps[ array_key_first( $apps ) ]['description'] ); ?>
		</p>
	<?php endif; ?>
	<?php
}

function fwp_headless_app_render_source_type_field() {
	$value = get_option( FWP_HEADLESS_APP_OPTION_SOURCE_TYPE, 'url' );
	?>
	<select name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SOURCE_TYPE ); ?>">
		<option value="url" <?php selected( $value, 'url' ); ?>>
			<?php echo esc_html__( 'Remote URL', '4wp-headless-app' ); ?>
		</option>
		<option value="local" <?php selected( $value, 'local' ); ?>>
			<?php echo esc_html__( 'Local build folder', '4wp-headless-app' ); ?>
		</option>
	</select>
	<p class="description">
		<?php echo esc_html__( 'Choose where the React application is hosted.', '4wp-headless-app' ); ?>
	</p>
	<?php
}

function fwp_headless_app_render_source_url_field() {
	$value = get_option( FWP_HEADLESS_APP_OPTION_SOURCE_URL, '' );
	?>
	<input
		type="url"
		class="regular-text"
		name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SOURCE_URL ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		placeholder="https://app.example.com"
	/>
	<p class="description">
		<?php echo esc_html__( 'Base URL for the React app (used for API mappings and syncing).', '4wp-headless-app' ); ?>
	</p>
	<?php
}

function fwp_headless_app_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( '4WP Headless App Settings', '4wp-headless-app' ); ?></h1>
		<p class="description">
			<?php echo esc_html__( 'Detected API base:', '4wp-headless-app' ); ?>
			<code><?php echo esc_url( rest_url( FWP_HEADLESS_APP_REST_NAMESPACE . '/' ) ); ?></code>
		</p>
		<h2><?php echo esc_html__( 'Notes (MVP)', '4wp-headless-app' ); ?></h2>
		<ul class="ul-disc">
			<li><?php echo esc_html__( 'Global goal: install WordPress, activate the plugin, select an app and source, then get full control over theme.json styles and the app-specific CPT content via the API.', '4wp-headless-app' ); ?></li>
			<li><?php echo esc_html__( 'Current MVP uses plugin seed data as the initial source of truth. Later, WordPress will fully own and persist the content.', '4wp-headless-app' ); ?></li>
			<li><?php echo esc_html__( 'App profiles define which CPTs and settings are required, plus default data to import.', '4wp-headless-app' ); ?></li>
		</ul>
		<p class="description">
			<?php echo esc_html__( 'Data is currently served from the plugin seed for the selected app.', '4wp-headless-app' ); ?>
		</p>
		<p class="description">
			<?php echo esc_html__( 'Theme source: app-specific theme.json (if available).', '4wp-headless-app' ); ?>
		</p>
		<form method="post" action="options.php">
			<?php
			settings_fields( FWP_HEADLESS_APP_OPTION_GROUP );
			do_settings_sections( '4wp-headless-app' );
			submit_button();
			?>
		</form>
		<hr />
		<h2><?php echo esc_html__( 'Apply App Profile', '4wp-headless-app' ); ?></h2>
		<p class="description">
			<?php echo esc_html__( 'Creates CPTs and imports default data for the selected app.', '4wp-headless-app' ); ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="4wp_headless_app_apply_profile" />
			<?php wp_nonce_field( '4wp_headless_app_apply_profile', '4wp_headless_app_apply_nonce' ); ?>
			<?php submit_button( __( 'Apply Profile', '4wp-headless-app' ), 'secondary' ); ?>
		</form>
	</div>
	<?php
}

function fwp_headless_app_render_general_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = get_option( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA, array() );
	$site = $settings['site'] ?? array();
	$contact = $settings['contact'] ?? array();
	$social = $settings['social'] ?? array();
	$stats = $settings['stats'] ?? array();
	$theme = $settings['theme'] ?? array();
	$footer_links = $settings['footer_links'] ?? array();
	$follow_links = $settings['follow_links'] ?? array();
	$hero = $settings['hero'] ?? array();
	$skills_section = $settings['skills_section'] ?? array();
	$ai = $settings['ai'] ?? array();

	?>
	<div class="wrap">
		<h1><?php echo esc_html( fwp_headless_app_get_profile_menu_title() ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( '4wp_headless_app_general_settings' ); ?>
			<h2><?php echo esc_html__( 'Site', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="fwp_site_title"><?php echo esc_html__( 'Site title', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_site_title" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[site][title]" value="<?php echo esc_attr( $site['title'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_site_logo_text"><?php echo esc_html__( 'Logo text', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_site_logo_text" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[site][logo_text]" value="<?php echo esc_attr( $site['logo_text'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_site_tagline"><?php echo esc_html__( 'Tagline', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_site_tagline" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[site][tagline]" value="<?php echo esc_attr( $site['tagline'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_site_description"><?php echo esc_html__( 'Description', '4wp-headless-app' ); ?></label></th>
					<td><textarea id="fwp_site_description" class="large-text" rows="3" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[site][description]"><?php echo esc_textarea( $site['description'] ?? '' ); ?></textarea></td>
				</tr>
			</table>

			<h2><?php echo esc_html__( 'Hero', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="fwp_hero_first_name"><?php echo esc_html__( 'First name', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_hero_first_name" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[hero][first_name]" value="<?php echo esc_attr( $hero['first_name'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_hero_last_name"><?php echo esc_html__( 'Last name', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_hero_last_name" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[hero][last_name]" value="<?php echo esc_attr( $hero['last_name'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_hero_role"><?php echo esc_html__( 'Role / Title', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_hero_role" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[hero][role]" value="<?php echo esc_attr( $hero['role'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_hero_description"><?php echo esc_html__( 'Description', '4wp-headless-app' ); ?></label></th>
					<td><textarea id="fwp_hero_description" class="large-text" rows="3" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[hero][description]"><?php echo esc_textarea( $hero['description'] ?? '' ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_hero_available"><?php echo esc_html__( 'Availability text', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_hero_available" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[hero][availability_text]" value="<?php echo esc_attr( $hero['availability_text'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_hero_avatar"><?php echo esc_html__( 'Avatar URL', '4wp-headless-app' ); ?></label></th>
					<td><input type="url" id="fwp_hero_avatar" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[hero][avatar_url]" value="<?php echo esc_attr( $hero['avatar_url'] ?? '' ); ?>" /></td>
				</tr>
			</table>

			<h2><?php echo esc_html__( 'Skills Section', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="fwp_skills_label"><?php echo esc_html__( 'Label', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_skills_label" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[skills_section][label]" value="<?php echo esc_attr( $skills_section['label'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_skills_title"><?php echo esc_html__( 'Title', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_skills_title" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[skills_section][title]" value="<?php echo esc_attr( $skills_section['title'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_skills_subtitle"><?php echo esc_html__( 'Subtitle', '4wp-headless-app' ); ?></label></th>
					<td><textarea id="fwp_skills_subtitle" class="large-text" rows="2" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[skills_section][subtitle]"><?php echo esc_textarea( $skills_section['subtitle'] ?? '' ); ?></textarea></td>
				</tr>
			</table>

			<h2><?php echo esc_html__( 'AI Assistant', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="fwp_ai_enabled"><?php echo esc_html__( 'Enable AI assistant', '4wp-headless-app' ); ?></label></th>
					<td>
						<label>
							<input type="checkbox" id="fwp_ai_enabled" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[ai][enabled]" value="1" <?php checked( ! empty( $ai['enabled'] ) ); ?> />
							<?php echo esc_html__( 'Show AI widget', '4wp-headless-app' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<h2><?php echo esc_html__( 'Contact', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="fwp_contact_email"><?php echo esc_html__( 'Email', '4wp-headless-app' ); ?></label></th>
					<td><input type="email" id="fwp_contact_email" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[contact][email]" value="<?php echo esc_attr( $contact['email'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_contact_phone"><?php echo esc_html__( 'Phone', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_contact_phone" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[contact][phone]" value="<?php echo esc_attr( $contact['phone'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_contact_location"><?php echo esc_html__( 'Location', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_contact_location" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[contact][location]" value="<?php echo esc_attr( $contact['location'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_contact_available"><?php echo esc_html__( 'Available', '4wp-headless-app' ); ?></label></th>
					<td><label><input type="checkbox" id="fwp_contact_available" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[contact][available]" value="1" <?php checked( ! empty( $contact['available'] ) ); ?> /> <?php echo esc_html__( 'Open for new projects', '4wp-headless-app' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_contact_availability"><?php echo esc_html__( 'Availability text', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_contact_availability" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[contact][availability_text]" value="<?php echo esc_attr( $contact['availability_text'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_contact_short_text"><?php echo esc_html__( 'Contact short text', '4wp-headless-app' ); ?></label></th>
					<td><textarea id="fwp_contact_short_text" class="large-text" rows="2" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[contact][short_text]"><?php echo esc_textarea( $contact['short_text'] ?? '' ); ?></textarea></td>
				</tr>
			</table>

			<h2><?php echo esc_html__( 'Social', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="fwp_social_github"><?php echo esc_html__( 'GitHub', '4wp-headless-app' ); ?></label></th>
					<td><input type="url" id="fwp_social_github" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[social][github]" value="<?php echo esc_attr( $social['github'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_social_linkedin"><?php echo esc_html__( 'LinkedIn', '4wp-headless-app' ); ?></label></th>
					<td><input type="url" id="fwp_social_linkedin" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[social][linkedin]" value="<?php echo esc_attr( $social['linkedin'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_social_facebook"><?php echo esc_html__( 'Facebook', '4wp-headless-app' ); ?></label></th>
					<td><input type="url" id="fwp_social_facebook" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[social][facebook]" value="<?php echo esc_attr( $social['facebook'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_social_wordpress"><?php echo esc_html__( 'WordPress.org', '4wp-headless-app' ); ?></label></th>
					<td><input type="url" id="fwp_social_wordpress" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[social][wordpress]" value="<?php echo esc_attr( $social['wordpress'] ?? '' ); ?>" /></td>
				</tr>
			</table>

			<h2><?php echo esc_html__( 'Footer Links', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php for ( $i = 0; $i < 4; $i++ ) : ?>
					<?php
					$link = $footer_links[ $i ] ?? array();
					?>
					<tr>
						<th scope="row">
							<?php echo esc_html__( 'Link', '4wp-headless-app' ); ?>
							<?php echo ' ' . ( $i + 1 ); ?>
						</th>
						<td>
							<p>
								<label for="fwp_footer_label_<?php echo esc_attr( $i ); ?>">
									<?php echo esc_html__( 'Label', '4wp-headless-app' ); ?>
								</label>
								<input type="text" id="fwp_footer_label_<?php echo esc_attr( $i ); ?>" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[footer_links][<?php echo esc_attr( $i ); ?>][label]" value="<?php echo esc_attr( $link['label'] ?? '' ); ?>" />
							</p>
							<p>
								<label for="fwp_footer_url_<?php echo esc_attr( $i ); ?>">
									<?php echo esc_html__( 'URL', '4wp-headless-app' ); ?>
								</label>
								<input type="url" id="fwp_footer_url_<?php echo esc_attr( $i ); ?>" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[footer_links][<?php echo esc_attr( $i ); ?>][url]" value="<?php echo esc_attr( $link['url'] ?? '' ); ?>" />
							</p>
							<p>
								<label for="fwp_footer_icon_<?php echo esc_attr( $i ); ?>">
									<?php echo esc_html__( 'Icon (Lucide name)', '4wp-headless-app' ); ?>
								</label>
								<input type="text" id="fwp_footer_icon_<?php echo esc_attr( $i ); ?>" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[footer_links][<?php echo esc_attr( $i ); ?>][icon]" value="<?php echo esc_attr( $link['icon'] ?? '' ); ?>" placeholder="Github" />
							</p>
						</td>
					</tr>
				<?php endfor; ?>
			</table>

			<h2><?php echo esc_html__( 'Follow Links', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php for ( $i = 0; $i < 4; $i++ ) : ?>
					<?php
					$link = $follow_links[ $i ] ?? array();
					?>
					<tr>
						<th scope="row">
							<?php echo esc_html__( 'Link', '4wp-headless-app' ); ?>
							<?php echo ' ' . ( $i + 1 ); ?>
						</th>
						<td>
							<p>
								<label for="fwp_follow_label_<?php echo esc_attr( $i ); ?>">
									<?php echo esc_html__( 'Label', '4wp-headless-app' ); ?>
								</label>
								<input type="text" id="fwp_follow_label_<?php echo esc_attr( $i ); ?>" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[follow_links][<?php echo esc_attr( $i ); ?>][label]" value="<?php echo esc_attr( $link['label'] ?? '' ); ?>" />
							</p>
							<p>
								<label for="fwp_follow_url_<?php echo esc_attr( $i ); ?>">
									<?php echo esc_html__( 'URL', '4wp-headless-app' ); ?>
								</label>
								<input type="url" id="fwp_follow_url_<?php echo esc_attr( $i ); ?>" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[follow_links][<?php echo esc_attr( $i ); ?>][url]" value="<?php echo esc_attr( $link['url'] ?? '' ); ?>" />
							</p>
							<p>
								<label for="fwp_follow_icon_<?php echo esc_attr( $i ); ?>">
									<?php echo esc_html__( 'Icon (Lucide name)', '4wp-headless-app' ); ?>
								</label>
								<input type="text" id="fwp_follow_icon_<?php echo esc_attr( $i ); ?>" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[follow_links][<?php echo esc_attr( $i ); ?>][icon]" value="<?php echo esc_attr( $link['icon'] ?? '' ); ?>" placeholder="Github" />
							</p>
						</td>
					</tr>
				<?php endfor; ?>
			</table>

			<h2><?php echo esc_html__( 'Stats', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="fwp_stats_years"><?php echo esc_html__( 'Years experience', '4wp-headless-app' ); ?></label></th>
					<td><input type="number" id="fwp_stats_years" class="small-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[stats][years_experience]" value="<?php echo esc_attr( $stats['years_experience'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_stats_projects"><?php echo esc_html__( 'Projects completed', '4wp-headless-app' ); ?></label></th>
					<td><input type="number" id="fwp_stats_projects" class="small-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[stats][projects_completed]" value="<?php echo esc_attr( $stats['projects_completed'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_stats_clients"><?php echo esc_html__( 'Happy clients', '4wp-headless-app' ); ?></label></th>
					<td><input type="number" id="fwp_stats_clients" class="small-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[stats][happy_clients]" value="<?php echo esc_attr( $stats['happy_clients'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_stats_satisfaction"><?php echo esc_html__( 'Client satisfaction (%)', '4wp-headless-app' ); ?></label></th>
					<td><input type="number" id="fwp_stats_satisfaction" class="small-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[stats][client_satisfaction]" value="<?php echo esc_attr( $stats['client_satisfaction'] ?? '' ); ?>" min="0" max="100" /></td>
				</tr>
			</table>

			<h2><?php echo esc_html__( 'Theme', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="fwp_theme_primary"><?php echo esc_html__( 'Primary color', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_theme_primary" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[theme][primary_color]" value="<?php echo esc_attr( $theme['primary_color'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_theme_secondary"><?php echo esc_html__( 'Secondary color', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_theme_secondary" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[theme][secondary_color]" value="<?php echo esc_attr( $theme['secondary_color'] ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_theme_accent"><?php echo esc_html__( 'Accent color', '4wp-headless-app' ); ?></label></th>
					<td><input type="text" id="fwp_theme_accent" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA ); ?>[theme][accent_color]" value="<?php echo esc_attr( $theme['accent_color'] ?? '' ); ?>" /></td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

function fwp_headless_app_sanitize_general_settings( $input ) {
	$output = array();

	$output['site'] = array(
		'title' => sanitize_text_field( $input['site']['title'] ?? '' ),
		'tagline' => sanitize_text_field( $input['site']['tagline'] ?? '' ),
		'description' => sanitize_textarea_field( $input['site']['description'] ?? '' ),
		'logo_text' => sanitize_text_field( $input['site']['logo_text'] ?? '' ),
	);

	$output['hero'] = array(
		'first_name' => sanitize_text_field( $input['hero']['first_name'] ?? '' ),
		'last_name' => sanitize_text_field( $input['hero']['last_name'] ?? '' ),
		'role' => sanitize_text_field( $input['hero']['role'] ?? '' ),
		'description' => sanitize_textarea_field( $input['hero']['description'] ?? '' ),
		'availability_text' => sanitize_text_field( $input['hero']['availability_text'] ?? '' ),
		'avatar_url' => esc_url_raw( $input['hero']['avatar_url'] ?? '' ),
	);

	$output['skills_section'] = array(
		'label' => sanitize_text_field( $input['skills_section']['label'] ?? '' ),
		'title' => sanitize_text_field( $input['skills_section']['title'] ?? '' ),
		'subtitle' => sanitize_textarea_field( $input['skills_section']['subtitle'] ?? '' ),
	);

	$output['ai'] = array(
		'enabled' => ! empty( $input['ai']['enabled'] ),
	);

	$output['contact'] = array(
		'email' => sanitize_email( $input['contact']['email'] ?? '' ),
		'phone' => sanitize_text_field( $input['contact']['phone'] ?? '' ),
		'location' => sanitize_text_field( $input['contact']['location'] ?? '' ),
		'available' => ! empty( $input['contact']['available'] ),
		'availability_text' => sanitize_text_field( $input['contact']['availability_text'] ?? '' ),
		'short_text' => sanitize_textarea_field( $input['contact']['short_text'] ?? '' ),
	);

	$output['social'] = array(
		'github' => esc_url_raw( $input['social']['github'] ?? '' ),
		'linkedin' => esc_url_raw( $input['social']['linkedin'] ?? '' ),
		'facebook' => esc_url_raw( $input['social']['facebook'] ?? '' ),
		'wordpress' => esc_url_raw( $input['social']['wordpress'] ?? '' ),
	);

	$output['stats'] = array(
		'years_experience' => (int) ( $input['stats']['years_experience'] ?? 0 ),
		'projects_completed' => (int) ( $input['stats']['projects_completed'] ?? 0 ),
		'happy_clients' => (int) ( $input['stats']['happy_clients'] ?? 0 ),
		'client_satisfaction' => (int) ( $input['stats']['client_satisfaction'] ?? 0 ),
	);

	$output['theme'] = array(
		'primary_color' => sanitize_text_field( $input['theme']['primary_color'] ?? '' ),
		'secondary_color' => sanitize_text_field( $input['theme']['secondary_color'] ?? '' ),
		'accent_color' => sanitize_text_field( $input['theme']['accent_color'] ?? '' ),
	);

	$output['footer_links'] = array();
	if ( ! empty( $input['footer_links'] ) && is_array( $input['footer_links'] ) ) {
		foreach ( $input['footer_links'] as $link ) {
			$label = sanitize_text_field( $link['label'] ?? '' );
			$url = esc_url_raw( $link['url'] ?? '' );
			$icon = sanitize_text_field( $link['icon'] ?? '' );
			if ( $label || $url || $icon ) {
				$output['footer_links'][] = array(
					'label' => $label,
					'url' => $url,
					'icon' => $icon,
				);
			}
		}
	}

	$output['follow_links'] = array();
	if ( ! empty( $input['follow_links'] ) && is_array( $input['follow_links'] ) ) {
		foreach ( $input['follow_links'] as $link ) {
			$label = sanitize_text_field( $link['label'] ?? '' );
			$url = esc_url_raw( $link['url'] ?? '' );
			$icon = sanitize_text_field( $link['icon'] ?? '' );
			if ( $label || $url || $icon ) {
				$output['follow_links'][] = array(
					'label' => $label,
					'url' => $url,
					'icon' => $icon,
				);
			}
		}
	}

	if ( ! empty( $output['site']['title'] ) ) {
		update_option( 'blogname', $output['site']['title'] );
	}

	return $output;
}

function fwp_headless_app_get_profile_menu_title() {
	$app_key = get_option( FWP_HEADLESS_APP_OPTION_APP_KEY, '' );
	$apps = fwp_headless_app_get_apps();
	if ( empty( $app_key ) || empty( $apps[ $app_key ]['name'] ) ) {
		return '4WP Profile';
	}

	$name = $apps[ $app_key ]['name'];
	$name = str_replace( 'Portfolio:', '', $name );
	$name = str_replace( 'Portfolio', '', $name );
	$name = trim( $name );

	return '4WP ' . ( $name ? $name : 'Profile' );
}

function fwp_headless_app_register_cpts() {
	$cpt_args = array(
		'public' => true,
		'show_in_rest' => true,
		'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
		'has_archive' => false,
	);

	register_post_type(
		'fwp_skill',
		array_merge(
			$cpt_args,
			array(
				'label' => 'Skills',
				'menu_icon' => 'dashicons-chart-bar',
			)
		)
	);

	register_post_type(
		'fwp_experience',
		array_merge(
			$cpt_args,
			array(
				'label' => 'Experience',
				'menu_icon' => 'dashicons-businessperson',
			)
		)
	);

	register_post_type(
		'fwp_project',
		array_merge(
			$cpt_args,
			array(
				'label' => 'Projects',
				'menu_icon' => 'dashicons-portfolio',
			)
		)
	);

	register_post_type(
		'fwp_service',
		array_merge(
			$cpt_args,
			array(
				'label' => 'Services',
				'menu_icon' => 'dashicons-admin-tools',
			)
		)
	);

	register_taxonomy(
		'fwp_project_category',
		'fwp_project',
		array(
			'label' => 'Project Categories',
			'public' => true,
			'show_in_rest' => true,
			'hierarchical' => false,
		)
	);

	register_taxonomy_for_object_type( 'post_tag', 'fwp_project' );
}

function fwp_headless_app_register_meta_boxes() {
	add_meta_box(
		'fwp_project_links',
		__( 'Project Links', '4wp-headless-app' ),
		'fwp_headless_app_render_project_links_meta_box',
		'fwp_project',
		'normal',
		'default'
	);

	add_meta_box(
		'fwp_skill_fields',
		__( 'Skill Fields', '4wp-headless-app' ),
		'fwp_headless_app_render_skill_fields_meta_box',
		'fwp_skill',
		'normal',
		'default'
	);

	add_meta_box(
		'fwp_service_fields',
		__( 'Service Fields', '4wp-headless-app' ),
		'fwp_headless_app_render_service_fields_meta_box',
		'fwp_service',
		'normal',
		'default'
	);

	add_meta_box(
		'fwp_experience_fields',
		__( 'Experience Fields', '4wp-headless-app' ),
		'fwp_headless_app_render_experience_fields_meta_box',
		'fwp_experience',
		'normal',
		'default'
	);
}

function fwp_headless_app_render_project_links_meta_box( $post ) {
	wp_nonce_field( 'fwp_headless_app_project_links', 'fwp_headless_app_project_links_nonce' );
	$link = get_post_meta( $post->ID, 'link', true );
	$repo = get_post_meta( $post->ID, 'repo', true );
	?>
	<p>
		<label for="fwp_project_link">
			<?php echo esc_html__( 'Project link', '4wp-headless-app' ); ?>
		</label>
		<input
			type="url"
			class="widefat"
			id="fwp_project_link"
			name="fwp_project_link"
			value="<?php echo esc_attr( $link ); ?>"
			placeholder="https://example.com"
		/>
	</p>
	<p>
		<label for="fwp_project_repo">
			<?php echo esc_html__( 'Repository link', '4wp-headless-app' ); ?>
		</label>
		<input
			type="url"
			class="widefat"
			id="fwp_project_repo"
			name="fwp_project_repo"
			value="<?php echo esc_attr( $repo ); ?>"
			placeholder="https://github.com/username/project"
		/>
	</p>
	<?php
}

function fwp_headless_app_render_skill_fields_meta_box( $post ) {
	wp_nonce_field( 'fwp_headless_app_skill_fields', 'fwp_headless_app_skill_fields_nonce' );
	$icon = get_post_meta( $post->ID, 'icon', true );
	$color = get_post_meta( $post->ID, 'color', true );
	$level = get_post_meta( $post->ID, 'level', true );
	?>
	<p>
		<label for="fwp_skill_icon">
			<?php echo esc_html__( 'Icon (Lucide name)', '4wp-headless-app' ); ?>
		</label>
		<input
			type="text"
			class="widefat"
			id="fwp_skill_icon"
			name="fwp_skill_icon"
			value="<?php echo esc_attr( $icon ); ?>"
			placeholder="Code2"
		/>
	</p>
	<p>
		<label for="fwp_skill_color">
			<?php echo esc_html__( 'Color (Tailwind gradient classes)', '4wp-headless-app' ); ?>
		</label>
		<input
			type="text"
			class="widefat"
			id="fwp_skill_color"
			name="fwp_skill_color"
			value="<?php echo esc_attr( $color ); ?>"
			placeholder="from-blue-500 to-blue-600"
		/>
	</p>
	<p>
		<label for="fwp_skill_level">
			<?php echo esc_html__( 'Level (%)', '4wp-headless-app' ); ?>
		</label>
		<input
			type="number"
			class="small-text"
			id="fwp_skill_level"
			name="fwp_skill_level"
			value="<?php echo esc_attr( $level ); ?>"
			min="0"
			max="100"
		/>
	</p>
	<?php
}

function fwp_headless_app_save_project_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['fwp_headless_app_project_links_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['fwp_headless_app_project_links_nonce'], 'fwp_headless_app_project_links' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['fwp_project_link'] ) ) {
		update_post_meta( $post_id, 'link', esc_url_raw( wp_unslash( $_POST['fwp_project_link'] ) ) );
	}
	if ( isset( $_POST['fwp_project_repo'] ) ) {
		update_post_meta( $post_id, 'repo', esc_url_raw( wp_unslash( $_POST['fwp_project_repo'] ) ) );
	}
}

function fwp_headless_app_save_skill_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['fwp_headless_app_skill_fields_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['fwp_headless_app_skill_fields_nonce'], 'fwp_headless_app_skill_fields' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['fwp_skill_icon'] ) ) {
		update_post_meta( $post_id, 'icon', sanitize_text_field( wp_unslash( $_POST['fwp_skill_icon'] ) ) );
	}
	if ( isset( $_POST['fwp_skill_color'] ) ) {
		update_post_meta( $post_id, 'color', sanitize_text_field( wp_unslash( $_POST['fwp_skill_color'] ) ) );
	}
	if ( isset( $_POST['fwp_skill_level'] ) ) {
		update_post_meta( $post_id, 'level', (int) $_POST['fwp_skill_level'] );
	}
}

function fwp_headless_app_render_service_fields_meta_box( $post ) {
	wp_nonce_field( 'fwp_headless_app_service_fields', 'fwp_headless_app_service_fields_nonce' );
	$icon = get_post_meta( $post->ID, 'icon', true );
	$color = get_post_meta( $post->ID, 'color', true );
	$features = get_post_meta( $post->ID, 'features', true );
	$reserved = fwp_headless_app_get_reserved_gradients();

	if ( empty( $icon ) ) {
		$icon = 'Code2';
	}
	if ( empty( $color ) && ! empty( $reserved ) ) {
		$color = $reserved[0]['value'];
	}
	if ( empty( $features ) || ! is_array( $features ) ) {
		$features = array();
	}
	?>
	<p>
		<label for="fwp_service_icon">
			<?php echo esc_html__( 'Icon (Lucide name)', '4wp-headless-app' ); ?>
		</label>
		<input
			type="text"
			class="widefat"
			id="fwp_service_icon"
			name="fwp_service_icon"
			value="<?php echo esc_attr( $icon ); ?>"
			placeholder="Code2"
		/>
	</p>
	<p>
		<label for="fwp_service_color">
			<?php echo esc_html__( 'Gradient (reserved)', '4wp-headless-app' ); ?>
		</label>
		<select id="fwp_service_color" name="fwp_service_color" class="widefat">
			<?php foreach ( $reserved as $gradient ) : ?>
				<option value="<?php echo esc_attr( $gradient['value'] ); ?>" <?php selected( $color, $gradient['value'] ); ?>>
					<?php echo esc_html( $gradient['label'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="fwp_service_features">
			<?php echo esc_html__( 'Service features (one per line)', '4wp-headless-app' ); ?>
		</label>
		<textarea
			id="fwp_service_features"
			name="fwp_service_features"
			class="widefat"
			rows="5"
			placeholder="Custom theme development"
		><?php echo esc_textarea( implode( "\n", $features ) ); ?></textarea>
	</p>
	<?php
}

function fwp_headless_app_save_service_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['fwp_headless_app_service_fields_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['fwp_headless_app_service_fields_nonce'], 'fwp_headless_app_service_fields' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$icon = isset( $_POST['fwp_service_icon'] ) ? sanitize_text_field( wp_unslash( $_POST['fwp_service_icon'] ) ) : '';
	$color = isset( $_POST['fwp_service_color'] ) ? sanitize_text_field( wp_unslash( $_POST['fwp_service_color'] ) ) : '';
	$features_raw = isset( $_POST['fwp_service_features'] ) ? wp_unslash( $_POST['fwp_service_features'] ) : '';
	$reserved = fwp_headless_app_get_reserved_gradients();

	if ( empty( $icon ) ) {
		$icon = 'Code2';
	}
	if ( empty( $color ) && ! empty( $reserved ) ) {
		$color = $reserved[0]['value'];
	}

	update_post_meta( $post_id, 'icon', $icon );
	update_post_meta( $post_id, 'color', $color );

	if ( is_string( $features_raw ) ) {
		$lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $features_raw ) ) );
		$features = array_map( 'sanitize_text_field', $lines );
		update_post_meta( $post_id, 'features', $features );
	}
}

function fwp_headless_app_render_experience_fields_meta_box( $post ) {
	wp_nonce_field( 'fwp_headless_app_experience_fields', 'fwp_headless_app_experience_fields_nonce' );
	$company = get_post_meta( $post->ID, 'company', true );
	$location = get_post_meta( $post->ID, 'location', true );
	$period = get_post_meta( $post->ID, 'period', true );
	$current = get_post_meta( $post->ID, 'current', true );
	$technologies = get_post_meta( $post->ID, 'technologies', true );

	if ( empty( $technologies ) || ! is_array( $technologies ) ) {
		$technologies = array();
	}
	?>
	<p>
		<label for="fwp_experience_company">
			<?php echo esc_html__( 'Company', '4wp-headless-app' ); ?>
		</label>
		<input
			type="text"
			class="widefat"
			id="fwp_experience_company"
			name="fwp_experience_company"
			value="<?php echo esc_attr( $company ); ?>"
		/>
	</p>
	<p>
		<label for="fwp_experience_location">
			<?php echo esc_html__( 'Location', '4wp-headless-app' ); ?>
		</label>
		<input
			type="text"
			class="widefat"
			id="fwp_experience_location"
			name="fwp_experience_location"
			value="<?php echo esc_attr( $location ); ?>"
		/>
	</p>
	<p>
		<label for="fwp_experience_period">
			<?php echo esc_html__( 'Period', '4wp-headless-app' ); ?>
		</label>
		<input
			type="text"
			class="widefat"
			id="fwp_experience_period"
			name="fwp_experience_period"
			value="<?php echo esc_attr( $period ); ?>"
			placeholder="2021 - Present"
		/>
	</p>
	<p>
		<label for="fwp_experience_current">
			<?php echo esc_html__( 'Current role', '4wp-headless-app' ); ?>
		</label>
		<label>
			<input
				type="checkbox"
				id="fwp_experience_current"
				name="fwp_experience_current"
				value="1"
				<?php checked( ! empty( $current ) ); ?>
			/>
			<?php echo esc_html__( 'Mark as current', '4wp-headless-app' ); ?>
		</label>
	</p>
	<p>
		<label for="fwp_experience_technologies">
			<?php echo esc_html__( 'Technologies (one per line)', '4wp-headless-app' ); ?>
		</label>
		<textarea
			id="fwp_experience_technologies"
			name="fwp_experience_technologies"
			class="widefat"
			rows="4"
		><?php echo esc_textarea( implode( "\n", $technologies ) ); ?></textarea>
	</p>
	<?php
}

function fwp_headless_app_save_experience_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['fwp_headless_app_experience_fields_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['fwp_headless_app_experience_fields_nonce'], 'fwp_headless_app_experience_fields' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, 'company', sanitize_text_field( wp_unslash( $_POST['fwp_experience_company'] ?? '' ) ) );
	update_post_meta( $post_id, 'location', sanitize_text_field( wp_unslash( $_POST['fwp_experience_location'] ?? '' ) ) );
	update_post_meta( $post_id, 'period', sanitize_text_field( wp_unslash( $_POST['fwp_experience_period'] ?? '' ) ) );
	update_post_meta( $post_id, 'current', ! empty( $_POST['fwp_experience_current'] ) );

	$tech_raw = isset( $_POST['fwp_experience_technologies'] ) ? wp_unslash( $_POST['fwp_experience_technologies'] ) : '';
	if ( is_string( $tech_raw ) ) {
		$lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $tech_raw ) ) );
		$tech = array_map( 'sanitize_text_field', $lines );
		update_post_meta( $post_id, 'technologies', $tech );
	}
}

function fwp_headless_app_get_reserved_gradients() {
	$theme = get_option( FWP_HEADLESS_APP_OPTION_THEME_JSON, array() );
	$gradients = $theme['settings']['color']['gradients'] ?? array();

	if ( empty( $gradients ) ) {
		$app_key = get_option( FWP_HEADLESS_APP_OPTION_APP_KEY, '' );
		$app_theme = fwp_headless_app_get_app_theme_json( $app_key );
		$gradients = $app_theme['settings']['color']['gradients'] ?? array();
	}

	$output = array();
	foreach ( $gradients as $gradient ) {
		if ( empty( $gradient['gradient'] ) ) {
			continue;
		}
		$output[] = array(
			'label' => $gradient['name'] ?? $gradient['slug'] ?? 'Gradient',
			'value' => $gradient['gradient'],
		);
	}

	if ( empty( $output ) ) {
		$output[] = array(
			'label' => 'Default Blue',
			'value' => 'linear-gradient(135deg, #3b82f6, #06b6d4)',
		);
		$output[] = array(
			'label' => 'Default Purple',
			'value' => 'linear-gradient(135deg, #8b5cf6, #a855f7)',
		);
		$output[] = array(
			'label' => 'Default Green',
			'value' => 'linear-gradient(135deg, #22c55e, #10b981)',
		);
	}

	return $output;
}

function fwp_headless_app_register_rest_routes() {
	register_rest_route(
		FWP_HEADLESS_APP_REST_NAMESPACE,
		'/settings',
		array(
			'methods' => 'GET',
			'callback' => 'fwp_headless_app_rest_get_settings',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		FWP_HEADLESS_APP_REST_NAMESPACE,
		'/theme',
		array(
			'methods' => 'GET',
			'callback' => 'fwp_headless_app_rest_get_theme',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		FWP_HEADLESS_APP_REST_NAMESPACE,
		'/skills',
		array(
			'methods' => 'GET',
			'callback' => 'fwp_headless_app_rest_get_skills',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		FWP_HEADLESS_APP_REST_NAMESPACE,
		'/services',
		array(
			'methods' => 'GET',
			'callback' => 'fwp_headless_app_rest_get_services',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		FWP_HEADLESS_APP_REST_NAMESPACE,
		'/experience',
		array(
			'methods' => 'GET',
			'callback' => 'fwp_headless_app_rest_get_experience',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		FWP_HEADLESS_APP_REST_NAMESPACE,
		'/projects',
		array(
			'methods' => 'GET',
			'callback' => 'fwp_headless_app_rest_get_projects',
			'permission_callback' => '__return_true',
		)
	);
}

function fwp_headless_app_register_contact_routes() {
	register_rest_route(
		FWP_HEADLESS_APP_REST_NAMESPACE,
		'/contact',
		array(
			'methods' => 'POST',
			'callback' => 'fwp_headless_app_rest_contact_submit',
			'permission_callback' => '__return_true',
		)
	);
}

function fwp_headless_app_rest_contact_submit( $request ) {
	$params = $request->get_json_params();
	$name = sanitize_text_field( $params['name'] ?? '' );
	$email = sanitize_email( $params['email'] ?? '' );
	$message = sanitize_textarea_field( $params['message'] ?? '' );

	if ( empty( $email ) || empty( $message ) ) {
		return new WP_Error(
			'fwp_headless_app_contact_invalid',
			__( 'Email and message are required.', '4wp-headless-app' ),
			array( 'status' => 422 )
		);
	}

	$settings = get_option( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA, array() );
	$recipient = $settings['contact']['email'] ?? get_option( 'admin_email' );
	$subject = 'New contact message';

	$body = "Name: {$name}\n";
	$body .= "Email: {$email}\n\n";
	$body .= $message;

	$headers = array();
	if ( $email ) {
		$headers[] = 'Reply-To: ' . $email;
	}

	wp_mail( $recipient, $subject, $body, $headers );

	return rest_ensure_response(
		array(
			'success' => true,
		)
	);
}

function fwp_headless_app_get_active_seed_section( $section ) {
	$app_key = get_option( FWP_HEADLESS_APP_OPTION_APP_KEY, '' );
	$seed = fwp_headless_app_get_app_seed( $app_key );
	if ( empty( $seed ) ) {
		return new WP_Error(
			'fwp_headless_app_no_seed',
			__( 'No seed data found for the selected app.', '4wp-headless-app' ),
			array( 'status' => 404 )
		);
	}

	if ( $section === 'projects' ) {
		return array(
			'projects' => $seed['projects'] ?? array(),
			'categories' => $seed['categories'] ?? array(),
		);
	}

	return $seed[ $section ] ?? array();
}

function fwp_headless_app_rest_get_settings() {
	$settings = get_option( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA, array() );
	if ( ! empty( $settings ) ) {
		return rest_ensure_response( $settings );
	}
	return rest_ensure_response( fwp_headless_app_get_active_seed_section( 'settings' ) );
}

function fwp_headless_app_rest_get_theme() {
	$theme = get_option( FWP_HEADLESS_APP_OPTION_THEME_JSON, array() );
	if ( ! empty( $theme ) ) {
		return rest_ensure_response( $theme );
	}
	return rest_ensure_response( array() );
}

function fwp_headless_app_rest_get_skills() {
	$skills = fwp_headless_app_get_cpt_data( 'fwp_skill' );
	if ( ! empty( $skills ) ) {
		return rest_ensure_response( $skills );
	}
	return rest_ensure_response( fwp_headless_app_get_active_seed_section( 'skills' ) );
}

function fwp_headless_app_rest_get_services() {
	$services = fwp_headless_app_get_cpt_data( 'fwp_service' );
	if ( ! empty( $services ) ) {
		return rest_ensure_response( $services );
	}
	return rest_ensure_response( fwp_headless_app_get_active_seed_section( 'services' ) );
}

function fwp_headless_app_rest_get_experience() {
	$experience = fwp_headless_app_get_cpt_data( 'fwp_experience' );
	if ( ! empty( $experience ) ) {
		return rest_ensure_response( $experience );
	}
	return rest_ensure_response( fwp_headless_app_get_active_seed_section( 'experience' ) );
}

function fwp_headless_app_rest_get_projects() {
	$projects = fwp_headless_app_get_projects_data();
	if ( ! empty( $projects['projects'] ) ) {
		return rest_ensure_response( $projects );
	}
	return rest_ensure_response( fwp_headless_app_get_active_seed_section( 'projects' ) );
}

function fwp_headless_app_apply_profile() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed', '4wp-headless-app' ) );
	}

	check_admin_referer( '4wp_headless_app_apply_profile', '4wp_headless_app_apply_nonce' );

	$app_key = get_option( FWP_HEADLESS_APP_OPTION_APP_KEY, '' );
	$seed = fwp_headless_app_get_app_seed( $app_key );
	if ( empty( $seed ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=4wp-headless-app' ) );
		exit;
	}

	if ( isset( $seed['settings'] ) ) {
		update_option( FWP_HEADLESS_APP_OPTION_SETTINGS_DATA, $seed['settings'] );
		if ( ! empty( $seed['settings']['site']['title'] ) ) {
			update_option( 'blogname', $seed['settings']['site']['title'] );
		}
	}

	$theme_json = fwp_headless_app_get_app_theme_json( $app_key );
	if ( ! empty( $theme_json ) ) {
		update_option( FWP_HEADLESS_APP_OPTION_THEME_JSON, $theme_json );
	}

	fwp_headless_app_import_cpt_items( 'fwp_skill', $seed['skills'] ?? array(), 'name' );
	fwp_headless_app_import_cpt_items( 'fwp_service', $seed['services'] ?? array(), 'title' );
	fwp_headless_app_import_cpt_items( 'fwp_experience', $seed['experience'] ?? array(), 'role' );
	fwp_headless_app_import_projects( $seed['projects'] ?? array(), $seed['categories'] ?? array() );

	set_transient( FWP_HEADLESS_APP_APPLY_NOTICE, '1', 60 );
	wp_safe_redirect( admin_url( 'admin.php?page=4wp-headless-app' ) );
	exit;
}

function fwp_headless_app_import_cpt_items( $post_type, $items, $title_key ) {
	foreach ( $items as $item ) {
		if ( empty( $item['id'] ) ) {
			continue;
		}
		$existing_id = fwp_headless_app_find_post_by_seed_id( $post_type, $item['id'] );
		if ( $existing_id ) {
			continue;
		}

		$title = $item[ $title_key ] ?? 'Item ' . $item['id'];
		$post_id = wp_insert_post(
			array(
				'post_type' => $post_type,
				'post_title' => $title,
				'post_status' => 'publish',
				'post_name' => $item['slug'] ?? sanitize_title( $title ),
				'post_content' => $item['description'] ?? '',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			continue;
		}

		update_post_meta( $post_id, 'fwp_seed_id', $item['id'] );
		foreach ( $item as $key => $value ) {
			if ( in_array( $key, array( 'id', 'slug', 'description', $title_key ), true ) ) {
				continue;
			}
			update_post_meta( $post_id, $key, $value );
		}
	}
}

function fwp_headless_app_import_projects( $projects, $categories ) {
	foreach ( $categories as $category ) {
		if ( empty( $category['slug'] ) ) {
			continue;
		}
		wp_insert_term(
			$category['name'] ?? $category['slug'],
			'fwp_project_category',
			array( 'slug' => $category['slug'] )
		);
	}

	foreach ( $projects as $project ) {
		if ( empty( $project['id'] ) ) {
			continue;
		}
		$existing_id = fwp_headless_app_find_post_by_seed_id( 'fwp_project', $project['id'] );
		if ( $existing_id ) {
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type' => 'fwp_project',
				'post_title' => $project['title'] ?? 'Project ' . $project['id'],
				'post_status' => 'publish',
				'post_name' => $project['slug'] ?? sanitize_title( $project['title'] ?? 'project' ),
				'post_content' => $project['description'] ?? '',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			continue;
		}

		update_post_meta( $post_id, 'fwp_seed_id', $project['id'] );
		foreach ( $project as $key => $value ) {
			if ( in_array( $key, array( 'id', 'slug', 'description', 'title', 'category' ), true ) ) {
				continue;
			}
			update_post_meta( $post_id, $key, $value );
		}

		if ( ! empty( $project['category'] ) ) {
			wp_set_object_terms( $post_id, $project['category'], 'fwp_project_category', false );
		}
	}
}

function fwp_headless_app_find_post_by_seed_id( $post_type, $seed_id ) {
	$query = new WP_Query(
		array(
			'post_type' => $post_type,
			'posts_per_page' => 1,
			'post_status' => 'any',
			'meta_key' => 'fwp_seed_id',
			'meta_value' => $seed_id,
			'fields' => 'ids',
		)
	);

	if ( ! empty( $query->posts ) ) {
		return $query->posts[0];
	}

	return 0;
}

function fwp_headless_app_get_cpt_data( $post_type ) {
	$query = new WP_Query(
		array(
			'post_type' => $post_type,
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'orderby' => 'menu_order',
			'order' => 'ASC',
		)
	);

	$items = array();
	foreach ( $query->posts as $post ) {
		$meta = get_post_meta( $post->ID );
		$items[] = fwp_headless_app_normalize_meta( $post, $meta );
	}

	return $items;
}

function fwp_headless_app_get_projects_data() {
	$projects = fwp_headless_app_get_cpt_data( 'fwp_project' );
	$terms = get_terms(
		array(
			'taxonomy' => 'fwp_project_category',
			'hide_empty' => false,
		)
	);

	$categories = array();
	foreach ( $terms as $term ) {
		$gradient = get_term_meta( $term->term_id, 'gradient', true );
		$categories[] = array(
			'id' => $term->term_id,
			'name' => $term->name,
			'slug' => $term->slug,
			'gradient' => $gradient,
		);
	}

	return array(
		'projects' => $projects,
		'categories' => $categories,
	);
}

function fwp_headless_app_normalize_meta( $post, $meta ) {
	$raw_content = $post->post_content;
	$clean_content = wp_strip_all_tags( do_blocks( $raw_content ) );
	$clean_excerpt = wp_strip_all_tags( do_blocks( $post->post_excerpt ) );
	$description = $clean_excerpt ? $clean_excerpt : $clean_content;

	$item = array(
		'id' => (int) ( $meta['fwp_seed_id'][0] ?? $post->ID ),
		'title' => $post->post_title,
		'slug' => $post->post_name,
		'description' => $description,
	);

	foreach ( $meta as $key => $value ) {
		if ( in_array( $key, array( 'fwp_seed_id' ), true ) ) {
			continue;
		}
		if ( count( $value ) === 1 ) {
			$item[ $key ] = maybe_unserialize( $value[0] );
		} else {
			$item[ $key ] = array_map( 'maybe_unserialize', $value );
		}
	}

	if ( $post->post_type === 'fwp_project' ) {
		$thumbnail = get_the_post_thumbnail_url( $post, 'large' );
		if ( $thumbnail ) {
			$item['image'] = $thumbnail;
		}

		$terms = get_the_terms( $post, 'fwp_project_category' );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$item['category'] = $terms[0]->slug;
			$item['categories'] = array_map(
				function( $term ) {
					return array(
						'slug' => $term->slug,
						'name' => $term->name,
					);
				},
				$terms
			);
		}
	}

	return $item;
}

function fwp_headless_app_render_project_category_add_fields() {
	$gradients = fwp_headless_app_get_reserved_gradients();
	?>
	<div class="form-field">
		<label for="fwp_project_category_gradient"><?php echo esc_html__( 'Gradient', '4wp-headless-app' ); ?></label>
		<select id="fwp_project_category_gradient" name="fwp_project_category_gradient" class="widefat">
			<?php foreach ( $gradients as $gradient ) : ?>
				<option value="<?php echo esc_attr( $gradient['value'] ); ?>">
					<?php echo esc_html( $gradient['label'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
}

function fwp_headless_app_render_project_category_edit_fields( $term ) {
	$gradients = fwp_headless_app_get_reserved_gradients();
	$current = get_term_meta( $term->term_id, 'gradient', true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="fwp_project_category_gradient"><?php echo esc_html__( 'Gradient', '4wp-headless-app' ); ?></label></th>
		<td>
			<select id="fwp_project_category_gradient" name="fwp_project_category_gradient" class="widefat">
				<?php foreach ( $gradients as $gradient ) : ?>
					<option value="<?php echo esc_attr( $gradient['value'] ); ?>" <?php selected( $current, $gradient['value'] ); ?>>
						<?php echo esc_html( $gradient['label'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<?php
}

function fwp_headless_app_save_project_category_fields( $term_id ) {
	if ( ! isset( $_POST['fwp_project_category_gradient'] ) ) {
		return;
	}
	$gradient = sanitize_text_field( wp_unslash( $_POST['fwp_project_category_gradient'] ) );
	update_term_meta( $term_id, 'gradient', $gradient );
}

