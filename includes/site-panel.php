<?php
/**
 * Branded site admin panel — top-level menu per active headless profile.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

const FWP_HEADLESS_APP_SITE_PANEL_SLUG = '4wp-headless-site';

/**
 * Whether the branded site panel should appear.
 */
function fwp_headless_app_should_show_site_panel() {
	$app_key = get_option( FWP_HEADLESS_APP_OPTION_APP_KEY, '' );
	if ( '' === $app_key ) {
		return false;
	}

	$model = fwp_headless_app_get_content_model();
	return in_array( 'site_settings', $model['options'] ?? array(), true );
}

/**
 * Display name for the top-level menu (e.g. "GRV Build").
 */
function fwp_headless_app_get_site_panel_title() {
	$app_key = get_option( FWP_HEADLESS_APP_OPTION_APP_KEY, '' );
	$apps    = fwp_headless_app_get_apps();

	if ( '' === $app_key || empty( $apps[ $app_key ]['name'] ) ) {
		return __( 'Headless Site', '4wp-headless-app' );
	}

	return $apps[ $app_key ]['name'];
}

/**
 * Menu icon — REST / headless metaphor.
 */
function fwp_headless_app_get_headless_menu_icon() {
	return 'dashicons-rest-api';
}

/**
 * Register branded site panel (high in sidebar).
 */
function fwp_headless_app_register_site_panel() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$title = fwp_headless_app_get_site_panel_title();

	add_menu_page(
		$title,
		$title,
		'manage_options',
		FWP_HEADLESS_APP_SITE_PANEL_SLUG,
		'fwp_headless_app_render_site_general_page',
		fwp_headless_app_get_headless_menu_icon(),
		3
	);

	add_submenu_page(
		FWP_HEADLESS_APP_SITE_PANEL_SLUG,
		__( 'General Settings', '4wp-headless-app' ),
		__( 'General Settings', '4wp-headless-app' ),
		'manage_options',
		FWP_HEADLESS_APP_SITE_PANEL_SLUG,
		'fwp_headless_app_render_site_general_page'
	);
}

/**
 * Register site panel settings.
 */
function fwp_headless_app_register_site_panel_settings() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_setting(
		'4wp_headless_app_site_panel',
		FWP_HEADLESS_APP_SITE_OPTION_SETTINGS,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'fwp_headless_app_sanitize_site_settings',
			'default'           => array(),
		)
	);
}

/**
 * @param array<string, mixed> $input Raw POST data.
 * @return array<string, mixed>
 */
function fwp_headless_app_sanitize_site_settings( $input ) {
	$existing = fwp_headless_app_get_site_option_value(
		FWP_HEADLESS_APP_SITE_OPTION_SETTINGS,
		'4wp_headless_app_grv_site_settings',
		array()
	);
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}
	if ( ! is_array( $input ) ) {
		return $existing;
	}

	$output                      = $existing;
	$output['logo']              = sanitize_text_field( $input['logo'] ?? ( $existing['logo'] ?? '' ) );
	$logo_id                     = absint( $input['logo_id'] ?? 0 );
	$output['logo_id']           = ( $logo_id && wp_attachment_is_image( $logo_id ) ) ? $logo_id : 0;
	$output['slogan']            = sanitize_text_field( $input['slogan'] ?? ( $existing['slogan'] ?? '' ) );
	$output['short_description'] = sanitize_textarea_field( $input['short_description'] ?? ( $existing['short_description'] ?? '' ) );
	$output['phone']             = sanitize_text_field( $input['phone'] ?? ( $existing['phone'] ?? '' ) );
	$output['phone_2']           = sanitize_text_field( $input['phone_2'] ?? ( $existing['phone_2'] ?? '' ) );
	$output['email']             = sanitize_email( $input['email'] ?? ( $existing['email'] ?? '' ) );
	$output['address']           = sanitize_text_field( $input['address'] ?? ( $existing['address'] ?? '' ) );
	$output['working_hours']     = sanitize_text_field( $input['working_hours'] ?? ( $existing['working_hours'] ?? '' ) );
	$output['copyright']         = sanitize_text_field( $input['copyright'] ?? ( $existing['copyright'] ?? '' ) );

	if ( function_exists( 'fwp_headless_app_sanitize_site_seo_fields' ) ) {
		$output = fwp_headless_app_sanitize_site_seo_fields( $input, $output );
	}

	if ( ! empty( $output['logo'] ) ) {
		update_option( 'blogname', $output['logo'] );
	}
	if ( ! empty( $output['slogan'] ) ) {
		update_option( 'blogdescription', $output['slogan'] );
	}

	return $output;
}

/**
 * Media uploader for site logo on the branded panel.
 *
 * @param string $hook_suffix Admin page hook.
 */
function fwp_headless_app_enqueue_site_panel_assets( $hook_suffix ) {
	if ( 'toplevel_page_' . FWP_HEADLESS_APP_SITE_PANEL_SLUG !== $hook_suffix ) {
		return;
	}

	wp_enqueue_media();

	$js = <<<'JS'
jQuery(function ($) {
  var frame;
  var $input = $('#fwp_grv_logo_id');
  var $preview = $('#fwp_grv_logo_preview');

  function renderPreview(url) {
    if (url) {
      $preview.html('<img src="' + url + '" alt="" style="max-width:240px;height:auto;display:block;" />');
    } else {
      $preview.empty();
    }
  }

  $('#fwp_grv_logo_upload').on('click', function (e) {
    e.preventDefault();
    if (frame) {
      frame.open();
      return;
    }
    frame = wp.media({
      title: 'Site Logo',
      button: { text: 'Use as logo' },
      library: { type: 'image' },
      multiple: false
    });
    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      $input.val(attachment.id);
      renderPreview(attachment.url);
    });
    frame.open();
  });

  $('#fwp_grv_logo_remove').on('click', function (e) {
    e.preventDefault();
    $input.val('');
    renderPreview('');
  });
});
JS;

	wp_add_inline_script( 'jquery', $js );
}

/**
 * Resolve logo URL for site_settings export / admin preview.
 *
 * @param array<string, mixed> $settings Site settings option.
 * @return array<string, mixed>
 */
function fwp_headless_app_site_settings_with_logo_url( $settings ) {
	if ( ! is_array( $settings ) ) {
		return array();
	}

	$logo_id = isset( $settings['logo_id'] ) ? (int) $settings['logo_id'] : 0;
	if ( $logo_id > 0 ) {
		$url = wp_get_attachment_image_url( $logo_id, 'full' );
		$settings['logo_url'] = $url ? $url : '';
	} else {
		$settings['logo_url'] = '';
	}

	return $settings;
}

/**
 * General Settings — site_settings (minimal v1).
 */
function fwp_headless_app_render_site_general_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = fwp_headless_app_get_site_option_value(
		FWP_HEADLESS_APP_SITE_OPTION_SETTINGS,
		'4wp_headless_app_grv_site_settings',
		array()
	);
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	$app_key  = get_option( FWP_HEADLESS_APP_OPTION_APP_KEY, '' );
	$logo_id  = isset( $settings['logo_id'] ) ? (int) $settings['logo_id'] : 0;
	$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
	?>
	<div class="wrap">
		<h1><?php echo esc_html( fwp_headless_app_get_site_panel_title() ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Headless site settings — synced to the public frontend via REST API.', '4wp-headless-app' ); ?>
			<code><?php echo esc_url( rest_url( '4wp/v1' . fwp_headless_app_get_site_export_rest_path() ) ); ?></code>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( '4wp_headless_app_site_panel' ); ?>

			<h2><?php esc_html_e( 'General Settings', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="fwp_grv_logo"><?php esc_html_e( 'Logo text', '4wp-headless-app' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="fwp_grv_logo"
							class="regular-text"
							name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[logo]"
							value="<?php echo esc_attr( $settings['logo'] ?? '' ); ?>"
						/>
						<p class="description"><?php esc_html_e( 'Short brand label shown in the header (e.g. GRV). Used as alt text when a site logo image is set.', '4wp-headless-app' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="fwp_grv_logo_id"><?php esc_html_e( 'Site logo', '4wp-headless-app' ); ?></label>
					</th>
					<td>
						<input
							type="hidden"
							id="fwp_grv_logo_id"
							name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[logo_id]"
							value="<?php echo esc_attr( (string) $logo_id ); ?>"
						/>
						<div id="fwp_grv_logo_preview" style="margin-bottom:10px;">
							<?php if ( $logo_url ) : ?>
								<img src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-width:240px;height:auto;display:block;" />
							<?php endif; ?>
						</div>
						<button type="button" class="button" id="fwp_grv_logo_upload">
							<?php esc_html_e( 'Select logo', '4wp-headless-app' ); ?>
						</button>
						<button type="button" class="button" id="fwp_grv_logo_remove">
							<?php esc_html_e( 'Remove logo', '4wp-headless-app' ); ?>
						</button>
						<p class="description"><?php esc_html_e( 'Image shown in the site header, footer, and login screen. Falls back to logo text when empty.', '4wp-headless-app' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="fwp_grv_slogan"><?php esc_html_e( 'Slogan', '4wp-headless-app' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="fwp_grv_slogan"
							class="large-text"
							name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[slogan]"
							value="<?php echo esc_attr( $settings['slogan'] ?? '' ); ?>"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="fwp_grv_short_description"><?php esc_html_e( 'Short description', '4wp-headless-app' ); ?></label>
					</th>
					<td>
						<textarea
							id="fwp_grv_short_description"
							class="large-text"
							rows="3"
							name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[short_description]"
						><?php echo esc_textarea( $settings['short_description'] ?? '' ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Short brand text shown in the footer near the logo.', '4wp-headless-app' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="fwp_grv_phone"><?php esc_html_e( 'Phone', '4wp-headless-app' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="fwp_grv_phone"
							class="regular-text"
							name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[phone]"
							value="<?php echo esc_attr( $settings['phone'] ?? '' ); ?>"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="fwp_grv_phone_2"><?php esc_html_e( 'Phone 2', '4wp-headless-app' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="fwp_grv_phone_2"
							class="regular-text"
							name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[phone_2]"
							value="<?php echo esc_attr( $settings['phone_2'] ?? '' ); ?>"
							placeholder="+380…"
						/>
						<p class="description"><?php esc_html_e( 'Другий телефон (підвал і контакти). Можна залишити порожнім.', '4wp-headless-app' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="fwp_grv_email"><?php esc_html_e( 'Email', '4wp-headless-app' ); ?></label>
					</th>
					<td>
						<input
							type="email"
							id="fwp_grv_email"
							class="regular-text"
							name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[email]"
							value="<?php echo esc_attr( $settings['email'] ?? '' ); ?>"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="fwp_grv_address"><?php esc_html_e( 'Address', '4wp-headless-app' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="fwp_grv_address"
							class="large-text"
							name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[address]"
							value="<?php echo esc_attr( $settings['address'] ?? '' ); ?>"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="fwp_grv_copyright"><?php esc_html_e( 'Copyright', '4wp-headless-app' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="fwp_grv_copyright"
							class="large-text"
							name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[copyright]"
							value="<?php echo esc_attr( $settings['copyright'] ?? '' ); ?>"
						/>
						<p class="description"><?php esc_html_e( 'Footer copyright line (e.g. © 2024 GRV BUILD. Всі права захищені.).', '4wp-headless-app' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="fwp_grv_hours"><?php esc_html_e( 'Working hours', '4wp-headless-app' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="fwp_grv_hours"
							class="regular-text"
							name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[working_hours]"
							value="<?php echo esc_attr( $settings['working_hours'] ?? '' ); ?>"
						/>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>

		<hr />
		<p class="description">
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: link to plugin setup page */
					__( 'Profile setup and re-import: %s', '4wp-headless-app' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=4wp-headless-app' ) ) . '">4WP Headless App</a>'
				)
			);
			?>
		</p>
	</div>
	<?php
}
