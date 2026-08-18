<?php
/**
 * Branded site panel — SEO, analytics, Schema.org defaults.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

const FWP_HEADLESS_APP_SITE_SEO_SLUG = '4wp-headless-site-seo';

add_action( 'admin_menu', 'fwp_headless_app_register_site_seo_submenu', 12 );
add_action( 'admin_enqueue_scripts', 'fwp_headless_app_enqueue_site_seo_assets' );

/**
 * Register SEO & Analytics submenu.
 */
function fwp_headless_app_register_site_seo_submenu() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	add_submenu_page(
		FWP_HEADLESS_APP_SITE_PANEL_SLUG,
		__( 'SEO & Analytics', '4wp-headless-app' ),
		__( 'SEO & Analytics', '4wp-headless-app' ),
		'manage_options',
		FWP_HEADLESS_APP_SITE_SEO_SLUG,
		'fwp_headless_app_render_site_seo_page'
	);
}

/**
 * @param string $hook_suffix Admin page hook.
 */
function fwp_headless_app_enqueue_site_seo_assets( $hook_suffix ) {
	if ( 'grv-build_page_' . FWP_HEADLESS_APP_SITE_SEO_SLUG !== $hook_suffix
		&& 'toplevel_page_' . FWP_HEADLESS_APP_SITE_PANEL_SLUG !== $hook_suffix
		&& strpos( $hook_suffix, FWP_HEADLESS_APP_SITE_SEO_SLUG ) === false ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( FWP_HEADLESS_APP_SITE_SEO_SLUG !== $page ) {
			return;
		}
	}

	wp_enqueue_media();

	$js = <<<'JS'
jQuery(function ($) {
  var frame;
  var $input = $('#fwp_grv_og_image_id');
  var $preview = $('#fwp_grv_og_image_preview');

  function renderPreview(url) {
    if (url) {
      $preview.html('<img src="' + url + '" alt="" style="max-width:320px;height:auto;display:block;" />');
    } else {
      $preview.empty();
    }
  }

  $('#fwp_grv_og_image_upload').on('click', function (e) {
    e.preventDefault();
    if (frame) {
      frame.open();
      return;
    }
    frame = wp.media({
      title: 'Default OG image',
      button: { text: 'Use image' },
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

  $('#fwp_grv_og_image_remove').on('click', function (e) {
    e.preventDefault();
    $input.val('');
    renderPreview('');
  });
});
JS;

	wp_add_inline_script( 'jquery', $js );
}

/**
 * Sanitize tracking / schema fields (trusted admins only).
 *
 * @param array<string, mixed> $input Raw POST fragment.
 * @param array<string, mixed> $existing Existing settings.
 * @return array<string, mixed>
 */
function fwp_headless_app_sanitize_site_seo_fields( $input, $existing ) {
	$output = is_array( $existing ) ? $existing : array();

	$output['gtm_container_id'] = sanitize_text_field( $input['gtm_container_id'] ?? ( $existing['gtm_container_id'] ?? '' ) );
	$output['ga_measurement_id'] = sanitize_text_field( $input['ga_measurement_id'] ?? ( $existing['ga_measurement_id'] ?? '' ) );
	$output['scripts_head'] = isset( $input['scripts_head'] ) ? (string) wp_unslash( $input['scripts_head'] ) : ( $existing['scripts_head'] ?? '' );
	$output['scripts_body'] = isset( $input['scripts_body'] ) ? (string) wp_unslash( $input['scripts_body'] ) : ( $existing['scripts_body'] ?? '' );

	$org_type = sanitize_key( $input['schema_org_type'] ?? ( $existing['schema_org_type'] ?? 'local_business' ) );
	$output['schema_org_type'] = in_array( $org_type, array( 'organization', 'local_business' ), true ) ? $org_type : 'local_business';
	$output['schema_name'] = sanitize_text_field( $input['schema_name'] ?? ( $existing['schema_name'] ?? '' ) );
	$output['schema_description'] = sanitize_textarea_field( $input['schema_description'] ?? ( $existing['schema_description'] ?? '' ) );
	$output['site_public_url'] = esc_url_raw( $input['site_public_url'] ?? ( $existing['site_public_url'] ?? home_url( '/' ) ) );
	$output['twitter_site'] = sanitize_text_field( $input['twitter_site'] ?? ( $existing['twitter_site'] ?? '' ) );
	$output['facebook_url'] = esc_url_raw( $input['facebook_url'] ?? ( $existing['facebook_url'] ?? '' ) );

	$og_id = absint( $input['og_default_image_id'] ?? 0 );
	$output['og_default_image_id'] = ( $og_id && wp_attachment_is_image( $og_id ) ) ? $og_id : 0;

	return $output;
}

/**
 * Enrich site_settings with OG image URL for API export.
 *
 * @param array<string, mixed> $settings Site settings.
 * @return array<string, mixed>
 */
function fwp_headless_app_site_settings_with_seo_urls( $settings ) {
	if ( ! is_array( $settings ) ) {
		return array();
	}

	$og_id = isset( $settings['og_default_image_id'] ) ? (int) $settings['og_default_image_id'] : 0;
	if ( $og_id > 0 ) {
		$url = wp_get_attachment_image_url( $og_id, 'large' );
		$settings['og_default_image_url'] = $url ? $url : '';
	} else {
		$settings['og_default_image_url'] = '';
	}

	if ( empty( $settings['site_public_url'] ) ) {
		$settings['site_public_url'] = home_url( '/' );
	}

	return $settings;
}

/**
 * Render SEO & Analytics settings page.
 */
function fwp_headless_app_render_site_seo_page() {
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

	$og_id  = isset( $settings['og_default_image_id'] ) ? (int) $settings['og_default_image_id'] : 0;
	$og_url = $og_id ? wp_get_attachment_image_url( $og_id, 'medium' ) : '';
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SEO & Analytics', '4wp-headless-app' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Tracking codes, Schema.org defaults, and social metadata for the headless frontend.', '4wp-headless-app' ); ?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( '4wp_headless_app_site_panel' ); ?>

			<h2><?php esc_html_e( 'Google Tag Manager / Analytics', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="fwp_grv_gtm"><?php esc_html_e( 'GTM Container ID', '4wp-headless-app' ); ?></label></th>
					<td>
						<input type="text" id="fwp_grv_gtm" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[gtm_container_id]" value="<?php echo esc_attr( $settings['gtm_container_id'] ?? '' ); ?>" placeholder="GTM-XXXXXXX" />
						<p class="description"><?php esc_html_e( 'Google Tag Manager. Якщо задано — GA4 краще підключати через GTM.', '4wp-headless-app' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_grv_ga"><?php esc_html_e( 'GA4 Measurement ID', '4wp-headless-app' ); ?></label></th>
					<td>
						<input type="text" id="fwp_grv_ga" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[ga_measurement_id]" value="<?php echo esc_attr( $settings['ga_measurement_id'] ?? '' ); ?>" placeholder="G-XXXXXXXXXX" />
						<p class="description"><?php esc_html_e( 'Прямий gtag.js (якщо GTM не використовується).', '4wp-headless-app' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_grv_scripts_head"><?php esc_html_e( 'Scripts in &lt;head&gt;', '4wp-headless-app' ); ?></label></th>
					<td>
						<textarea id="fwp_grv_scripts_head" class="large-text code" rows="5" name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[scripts_head]"><?php echo esc_textarea( $settings['scripts_head'] ?? '' ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_grv_scripts_body"><?php esc_html_e( 'Scripts after &lt;body&gt;', '4wp-headless-app' ); ?></label></th>
					<td>
						<textarea id="fwp_grv_scripts_body" class="large-text code" rows="5" name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[scripts_body]"><?php echo esc_textarea( $settings['scripts_body'] ?? '' ); ?></textarea>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Schema.org & Social defaults', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="fwp_grv_public_url"><?php esc_html_e( 'Public site URL', '4wp-headless-app' ); ?></label></th>
					<td>
						<input type="url" id="fwp_grv_public_url" class="large-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[site_public_url]" value="<?php echo esc_attr( $settings['site_public_url'] ?? home_url( '/' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Canonical frontend URL (напр. https://grvbuild.com/).', '4wp-headless-app' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_grv_schema_type"><?php esc_html_e( 'Organization type', '4wp-headless-app' ); ?></label></th>
					<td>
						<select id="fwp_grv_schema_type" name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[schema_org_type]">
							<?php
							$type = $settings['schema_org_type'] ?? 'local_business';
							foreach ( array(
								'local_business' => __( 'LocalBusiness', '4wp-headless-app' ),
								'organization'   => __( 'Organization', '4wp-headless-app' ),
							) as $value => $label ) :
								?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_grv_schema_name"><?php esc_html_e( 'Schema name', '4wp-headless-app' ); ?></label></th>
					<td>
						<input type="text" id="fwp_grv_schema_name" class="large-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[schema_name]" value="<?php echo esc_attr( $settings['schema_name'] ?? '' ); ?>" placeholder="GRV BUILD" />
						<p class="description"><?php esc_html_e( 'Порожнє — logo text з General Settings.', '4wp-headless-app' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_grv_schema_desc"><?php esc_html_e( 'Schema description', '4wp-headless-app' ); ?></label></th>
					<td>
						<textarea id="fwp_grv_schema_desc" class="large-text" rows="3" name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[schema_description]"><?php echo esc_textarea( $settings['schema_description'] ?? '' ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_grv_og_image_id"><?php esc_html_e( 'Default OG image', '4wp-headless-app' ); ?></label></th>
					<td>
						<input type="hidden" id="fwp_grv_og_image_id" name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[og_default_image_id]" value="<?php echo esc_attr( (string) $og_id ); ?>" />
						<div id="fwp_grv_og_image_preview" style="margin-bottom:10px;">
							<?php if ( $og_url ) : ?>
								<img src="<?php echo esc_url( $og_url ); ?>" alt="" style="max-width:320px;height:auto;display:block;" />
							<?php endif; ?>
						</div>
						<button type="button" class="button" id="fwp_grv_og_image_upload"><?php esc_html_e( 'Select image', '4wp-headless-app' ); ?></button>
						<button type="button" class="button" id="fwp_grv_og_image_remove"><?php esc_html_e( 'Remove', '4wp-headless-app' ); ?></button>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_grv_twitter"><?php esc_html_e( 'Twitter / X handle', '4wp-headless-app' ); ?></label></th>
					<td>
						<input type="text" id="fwp_grv_twitter" class="regular-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[twitter_site]" value="<?php echo esc_attr( $settings['twitter_site'] ?? '' ); ?>" placeholder="grv_build" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_grv_facebook"><?php esc_html_e( 'Facebook page URL', '4wp-headless-app' ); ?></label></th>
					<td>
						<input type="url" id="fwp_grv_facebook" class="large-text" name="<?php echo esc_attr( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS ); ?>[facebook_url]" value="<?php echo esc_attr( $settings['facebook_url'] ?? '' ); ?>" placeholder="https://facebook.com/..." />
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
