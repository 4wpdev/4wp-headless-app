<?php
/**
 * Branded site panel — configurable social links (icon + URL).
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

const FWP_HEADLESS_APP_SITE_SOCIAL_SLUG        = '4wp-headless-site-social';
const FWP_HEADLESS_APP_SITE_OPTION_SOCIAL_LINKS = '4wp_headless_app_site_social_links';

add_action( 'admin_menu', 'fwp_headless_app_register_site_social_submenu', 11 );
add_action( 'admin_init', 'fwp_headless_app_register_site_social_settings' );
add_action( 'admin_enqueue_scripts', 'fwp_headless_app_enqueue_site_social_assets' );

/**
 * Allowed social icon slugs => admin label.
 *
 * @return array<string, string>
 */
function fwp_headless_app_get_social_icon_choices() {
	return array(
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
		'tiktok'    => 'TikTok',
		'youtube'   => 'YouTube',
		'telegram'  => 'Telegram',
		'whatsapp'  => 'WhatsApp',
		'viber'     => 'Viber',
		'linkedin'  => 'LinkedIn',
		'github'    => 'GitHub',
		'twitter'   => 'X (Twitter)',
		'threads'   => 'Threads',
		'pinterest' => 'Pinterest',
		'vk'        => 'VK',
		'mail'      => 'Email',
		'website'   => 'Website',
		'link'      => 'Link',
	);
}

/**
 * @param string $url Raw URL.
 * @return string
 */
function fwp_headless_app_sanitize_social_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}

	if ( str_starts_with( $url, '/' ) ) {
		return sanitize_text_field( $url );
	}

	if ( preg_match( '#^(https?|viber|tel|mailto):#i', $url ) ) {
		return sanitize_text_field( $url );
	}

	return esc_url_raw( $url );
}

/**
 * @param array<int, mixed>|null $input     Raw rows from POST.
 * @param bool                   $drop_empty Skip rows without URL.
 * @return array<int, array{icon: string, url: string, label: string}>
 */
function fwp_headless_app_sanitize_social_links_array( $input, $drop_empty = true ) {
	$choices = fwp_headless_app_get_social_icon_choices();
	$output  = array();

	if ( ! is_array( $input ) ) {
		return $output;
	}

	foreach ( $input as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$icon = sanitize_key( $row['icon'] ?? '' );
		$url  = fwp_headless_app_sanitize_social_url( $row['url'] ?? '' );

		if ( ! isset( $choices[ $icon ] ) || ( $drop_empty && '' === $url ) ) {
			continue;
		}

		$output[] = array(
			'icon'  => $icon,
			'url'   => $url,
			'label' => sanitize_text_field( $row['label'] ?? '' ),
		);
	}

	return $output;
}

/**
 * Resolve social links from site_settings (array or legacy social object).
 *
 * @param array<string, mixed>|null $settings Site settings option.
 * @return array<int, array{icon: string, url: string, label: string}>
 */
function fwp_headless_app_get_site_social_links( $settings = null ) {
	// Dedicated option wins even when empty (admin intentionally cleared links).
	$saved = get_option( FWP_HEADLESS_APP_SITE_OPTION_SOCIAL_LINKS, false );
	if ( is_array( $saved ) ) {
		return fwp_headless_app_sanitize_social_links_array( $saved );
	}

	if ( null === $settings ) {
		$settings = fwp_headless_app_get_site_option_value(
			FWP_HEADLESS_APP_SITE_OPTION_SETTINGS,
			'4wp_headless_app_grv_site_settings',
			array()
		);
	}

	if ( ! is_array( $settings ) ) {
		return array();
	}

	if ( ! empty( $settings['social_links'] ) && is_array( $settings['social_links'] ) ) {
		return fwp_headless_app_sanitize_social_links_array( $settings['social_links'] );
	}

	if ( empty( $settings['social'] ) || ! is_array( $settings['social'] ) ) {
		return array();
	}

	$choices = fwp_headless_app_get_social_icon_choices();
	$links   = array();

	foreach ( $settings['social'] as $icon => $url ) {
		$icon = sanitize_key( $icon );
		$url  = fwp_headless_app_sanitize_social_url( $url );
		if ( ! isset( $choices[ $icon ] ) || '' === $url ) {
			continue;
		}
		$links[] = array(
			'icon'  => $icon,
			'url'   => $url,
			'label' => '',
		);
	}

	return $links;
}

/**
 * Merge social_links into site_settings for REST export.
 *
 * @param array<string, mixed> $settings Site settings.
 * @return array<string, mixed>
 */
function fwp_headless_app_site_settings_with_social_links( $settings ) {
	if ( ! is_array( $settings ) ) {
		return array();
	}

	$settings['social_links'] = fwp_headless_app_get_site_social_links( $settings );

	return $settings;
}

/**
 * @param array<int, mixed>|mixed $input Raw POST rows.
 * @return array<int, array{icon: string, url: string, label: string}>
 */
function fwp_headless_app_sanitize_site_social_links_option( $input ) {
	return fwp_headless_app_sanitize_social_links_array( $input );
}

/**
 * Migrate social_links from site_settings into dedicated option (once).
 */
function fwp_headless_app_maybe_migrate_site_social_links_option() {
	if ( get_option( '4wp_headless_app_social_links_migrated', false ) ) {
		return;
	}

	$existing = get_option( FWP_HEADLESS_APP_SITE_OPTION_SOCIAL_LINKS, null );
	if ( is_array( $existing ) && ! empty( $existing ) ) {
		update_option( '4wp_headless_app_social_links_migrated', 1, false );
		return;
	}

	$links = fwp_headless_app_get_site_social_links();
	if ( ! empty( $links ) ) {
		update_option( FWP_HEADLESS_APP_SITE_OPTION_SOCIAL_LINKS, $links, false );
	}

	update_option( '4wp_headless_app_social_links_migrated', 1, false );
}
add_action( 'admin_init', 'fwp_headless_app_maybe_migrate_site_social_links_option', 4 );

/**
 * Register Social Links submenu.
 */
function fwp_headless_app_register_site_social_submenu() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	add_submenu_page(
		FWP_HEADLESS_APP_SITE_PANEL_SLUG,
		__( 'Social Links', '4wp-headless-app' ),
		__( 'Social Links', '4wp-headless-app' ),
		'manage_options',
		FWP_HEADLESS_APP_SITE_SOCIAL_SLUG,
		'fwp_headless_app_render_site_social_page'
	);
}

/**
 * Register social links (stored in site_settings option).
 */
function fwp_headless_app_register_site_social_settings() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_setting(
		'4wp_headless_app_site_social',
		FWP_HEADLESS_APP_SITE_OPTION_SOCIAL_LINKS,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'fwp_headless_app_sanitize_site_social_links_option',
			'default'           => array(),
		)
	);
}

/**
 * @param string $hook_suffix Admin page hook.
 */
function fwp_headless_app_enqueue_site_social_assets( $hook_suffix ) {
	if ( strpos( $hook_suffix, FWP_HEADLESS_APP_SITE_SOCIAL_SLUG ) === false ) {
		return;
	}

	wp_enqueue_script( 'jquery-ui-sortable' );

	$css = <<<'CSS'
.fwp-social-sort-handle {
  cursor: grab;
  color: #787c82;
  text-align: center;
  vertical-align: middle;
}
.fwp-social-sort-handle:active {
  cursor: grabbing;
}
.fwp-social-rows tr.ui-sortable-helper {
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}
.fwp-social-rows tr.ui-sortable-placeholder {
  visibility: visible !important;
  background: #f6f7f7;
  outline: 1px dashed #c3c4c7;
}
CSS;

	wp_add_inline_style( 'wp-admin', $css );

	$js = <<<'JS'
jQuery(function ($) {
  function reindexSocialRows($tbody) {
    $tbody.find('tr').each(function (index) {
      var nameBase = '4wp_headless_app_site_social_links[' + index + ']';
      $(this).find('[data-field]').each(function () {
        var field = $(this).data('field');
        $(this).attr('name', nameBase + '[' + field + ']');
      });
    });
  }

  function socialRowHtml(index, icons) {
    var nameBase = '4wp_headless_app_site_social_links[' + index + ']';
    var options = '';
    icons.forEach(function (item) {
      options += '<option value="' + item.slug + '">' + item.label + '</option>';
    });
    return '<tr>' +
      '<td class="fwp-social-sort-handle" title="Drag to reorder">' +
        '<span class="dashicons dashicons-menu" aria-hidden="true"></span>' +
      '</td>' +
      '<td><select data-field="icon" name="' + nameBase + '[icon]">' + options + '</select></td>' +
      '<td><input type="text" class="regular-text" data-field="url" name="' + nameBase + '[url]" value="" placeholder="https://..." /></td>' +
      '<td><input type="text" class="regular-text" data-field="label" name="' + nameBase + '[label]" value="" placeholder="Optional" /></td>' +
      '<td><button type="button" class="button fwp-social-remove" aria-label="Remove">&times;</button></td>' +
      '</tr>';
  }

  var icons = window.fwpSocialIconChoices || [];

  $('.fwp-social-rows').each(function () {
    var $tbody = $(this);
    $tbody.sortable({
      axis: 'y',
      handle: '.fwp-social-sort-handle',
      items: 'tr',
      tolerance: 'pointer',
      update: function () {
        reindexSocialRows($tbody);
      }
    });
  });

  $(document).on('click', '.fwp-social-add', function (e) {
    e.preventDefault();
    var $tbody = $('.fwp-social-rows');
    var index = $tbody.find('tr').length;
    $tbody.append(socialRowHtml(index, icons));
  });

  $(document).on('click', '.fwp-social-remove', function (e) {
    e.preventDefault();
    var $tbody = $(this).closest('tbody');
    $(this).closest('tr').remove();
    reindexSocialRows($tbody);
  });

  $('form').on('submit', function () {
    reindexSocialRows($('.fwp-social-rows'));
  });
});
JS;

	$icon_choices_js = array();
	foreach ( fwp_headless_app_get_social_icon_choices() as $slug => $label ) {
		$icon_choices_js[] = array(
			'slug'  => $slug,
			'label' => $label,
		);
	}
	wp_add_inline_script(
		'jquery-ui-sortable',
		'window.fwpSocialIconChoices = ' . wp_json_encode( $icon_choices_js ) . ';',
		'before'
	);

	wp_add_inline_script( 'jquery-ui-sortable', $js );
}

/**
 * Social links admin page.
 */
function fwp_headless_app_render_site_social_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$links   = fwp_headless_app_get_site_social_links();
	$choices = fwp_headless_app_get_social_icon_choices();
	$option  = FWP_HEADLESS_APP_SITE_OPTION_SOCIAL_LINKS;

	if ( empty( $links ) ) {
		$links = array(
			array(
				'icon'  => 'instagram',
				'url'   => '',
				'label' => '',
			),
		);
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Social Links', '4wp-headless-app' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Instagram, TikTok, YouTube, Facebook тощо — у підвалі та Social Links Bar. Viber / Telegram / WhatsApp — у блоці Contacts («Написати напряму»). Видаліть рядок кнопкою × і збережіть.', '4wp-headless-app' ); ?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( '4wp_headless_app_site_social' ); ?>

			<table class="widefat striped" style="max-width:960px;">
				<thead>
					<tr>
						<th style="width:36px;" aria-label="<?php esc_attr_e( 'Order', '4wp-headless-app' ); ?>"></th>
						<th style="width:180px;"><?php esc_html_e( 'Icon', '4wp-headless-app' ); ?></th>
						<th><?php esc_html_e( 'URL', '4wp-headless-app' ); ?></th>
						<th style="width:200px;"><?php esc_html_e( 'Label (optional)', '4wp-headless-app' ); ?></th>
						<th style="width:48px;"></th>
					</tr>
				</thead>
				<tbody class="fwp-social-rows">
					<?php foreach ( $links as $i => $item ) : ?>
						<tr>
							<td class="fwp-social-sort-handle" title="<?php esc_attr_e( 'Drag to reorder', '4wp-headless-app' ); ?>">
								<span class="dashicons dashicons-menu" aria-hidden="true"></span>
							</td>
							<td>
								<select data-field="icon" name="<?php echo esc_attr( $option . '[' . $i . '][icon]' ); ?>">
									<?php foreach ( $choices as $slug => $label ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $item['icon'] ?? '', $slug ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
							<td>
								<input
									type="text"
									class="regular-text"
									data-field="url"
									name="<?php echo esc_attr( $option . '[' . $i . '][url]' ); ?>"
									value="<?php echo esc_attr( $item['url'] ?? '' ); ?>"
									placeholder="https://instagram.com/..."
								/>
							</td>
							<td>
								<input
									type="text"
									class="regular-text"
									data-field="label"
									name="<?php echo esc_attr( $option . '[' . $i . '][label]' ); ?>"
									value="<?php echo esc_attr( $item['label'] ?? '' ); ?>"
									placeholder="<?php esc_attr_e( 'Instagram', '4wp-headless-app' ); ?>"
								/>
							</td>
							<td>
								<button type="button" class="button fwp-social-remove" aria-label="<?php esc_attr_e( 'Remove', '4wp-headless-app' ); ?>">&times;</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p>
				<button type="button" class="button fwp-social-add">
					<?php esc_html_e( 'Add link', '4wp-headless-app' ); ?>
				</button>
			</p>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
