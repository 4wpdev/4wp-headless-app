<?php
/**
 * Branded site panel — navigation menus (headless).
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

const FWP_HEADLESS_APP_SITE_MENUS_SLUG = '4wp-headless-site-menus';

add_action( 'admin_menu', 'fwp_headless_app_register_site_menus_submenu', 11 );
add_action( 'admin_init', 'fwp_headless_app_register_site_menus_settings' );
add_action( 'admin_enqueue_scripts', 'fwp_headless_app_enqueue_site_menus_assets' );

/**
 * @return array<string, array<int, array{label: string, href: string}>>
 */
function fwp_headless_app_get_site_nav_menus() {
	$menus = fwp_headless_app_get_site_option_value(
		FWP_HEADLESS_APP_SITE_OPTION_NAV,
		'4wp_headless_app_grv_nav_menus',
		array()
	);
	if ( ! is_array( $menus ) ) {
		$menus = array();
	}
	if ( empty( $menus['navigation'] ) && ! empty( $menus['additional'] ) ) {
		$menus['navigation'] = $menus['additional'];
	}
	if ( empty( $menus['header_cta'] ) || ! is_array( $menus['header_cta'] ) ) {
		$menus['header_cta'] = array(
			'label' => 'Консультація',
			'href'  => '/contacts',
		);
	}
	return $menus;
}

/**
 * @param array<string, mixed> $input Raw POST data.
 * @return array<string, mixed>
 */
function fwp_headless_app_sanitize_site_nav_menus( $input ) {
	$existing = fwp_headless_app_get_site_nav_menus();
	if ( ! is_array( $input ) ) {
		return $existing;
	}

	$output   = $existing;
	$sections = array( 'primary', 'services', 'regions', 'navigation' );

	foreach ( $sections as $section ) {
		$output[ $section ] = array();
		if ( empty( $input[ $section ] ) || ! is_array( $input[ $section ] ) ) {
			continue;
		}
		foreach ( $input[ $section ] as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$label = sanitize_text_field( $item['label'] ?? '' );
			$href  = trim( (string) ( $item['href'] ?? '' ) );
			if ( str_starts_with( $href, '/' ) ) {
				$href = sanitize_text_field( $href );
			} else {
				$href = esc_url_raw( $href );
			}
			if ( '' === $label || '' === $href ) {
				continue;
			}
			$output[ $section ][] = array(
				'label' => $label,
				'href'  => $href,
			);
		}
	}

	// Header CTA button (single item).
	$cta_in = isset( $input['header_cta'] ) && is_array( $input['header_cta'] )
		? $input['header_cta']
		: array();
	$cta_label = sanitize_text_field( $cta_in['label'] ?? '' );
	$cta_href  = trim( (string) ( $cta_in['href'] ?? '' ) );
	if ( str_starts_with( $cta_href, '/' ) ) {
		$cta_href = sanitize_text_field( $cta_href );
	} elseif ( '' !== $cta_href ) {
		$cta_href = esc_url_raw( $cta_href );
	}
	$output['header_cta'] = array(
		'label' => $cta_label,
		'href'  => $cta_href,
	);

	unset( $output['additional'] );

	return $output;
}

/**
 * Register Menus submenu.
 */
function fwp_headless_app_register_site_menus_submenu() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	add_submenu_page(
		FWP_HEADLESS_APP_SITE_PANEL_SLUG,
		__( 'Menus', '4wp-headless-app' ),
		__( 'Menus', '4wp-headless-app' ),
		'manage_options',
		FWP_HEADLESS_APP_SITE_MENUS_SLUG,
		'fwp_headless_app_render_site_menus_page'
	);
}

/**
 * Register nav menus option.
 */
function fwp_headless_app_register_site_menus_settings() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_setting(
		'4wp_headless_app_site_menus',
		FWP_HEADLESS_APP_SITE_OPTION_NAV,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'fwp_headless_app_sanitize_site_nav_menus',
			'default'           => array(),
		)
	);
}

/**
 * @param string $hook_suffix Admin page hook.
 */
function fwp_headless_app_enqueue_site_menus_assets( $hook_suffix ) {
	if ( strpos( $hook_suffix, FWP_HEADLESS_APP_SITE_MENUS_SLUG ) === false ) {
		return;
	}

	wp_enqueue_script( 'jquery-ui-sortable' );

	$css = <<<'CSS'
.fwp-nav-rows tr { cursor: default; }
.fwp-nav-sort-handle {
  width: 36px;
  text-align: center;
  vertical-align: middle;
  cursor: grab;
  color: #787c82;
}
.fwp-nav-sort-handle:active { cursor: grabbing; }
.fwp-nav-sort-handle .dashicons { margin-top: 4px; }
.fwp-nav-rows .ui-sortable-helper {
  display: table;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
}
.fwp-nav-rows .ui-sortable-placeholder {
  visibility: visible !important;
  background: #f0f6fc;
  outline: 1px dashed #c3c4c7;
}
CSS;

	wp_add_inline_style( 'wp-admin', $css );

	$js = <<<'JS'
jQuery(function ($) {
  function reindexNavRows($tbody) {
    var section = $tbody.data('section');
    $tbody.find('tr').each(function (index) {
      var nameBase = '4wp_headless_app_site_nav_menus[' + section + '][' + index + ']';
      $(this).find('input').each(function () {
        var name = $(this).attr('name') || '';
        var field = name.indexOf('[label]') !== -1 ? 'label' : 'href';
        $(this).attr('name', nameBase + '[' + field + ']');
      });
    });
  }

  function navRowHtml(section, index) {
    var nameBase = '4wp_headless_app_site_nav_menus[' + section + '][' + index + ']';
    return '<tr>' +
      '<td class="fwp-nav-sort-handle" title="Drag to reorder">' +
        '<span class="dashicons dashicons-menu" aria-hidden="true"></span>' +
      '</td>' +
      '<td><input type="text" class="regular-text" name="' + nameBase + '[label]" value="" /></td>' +
      '<td><input type="text" class="regular-text" name="' + nameBase + '[href]" value="" placeholder="/page" /></td>' +
      '<td><button type="button" class="button fwp-nav-remove" aria-label="Remove">&times;</button></td>' +
      '</tr>';
  }

  $('.fwp-nav-rows').each(function () {
    var $tbody = $(this);
    $tbody.sortable({
      axis: 'y',
      handle: '.fwp-nav-sort-handle',
      items: 'tr',
      tolerance: 'pointer',
      update: function () {
        reindexNavRows($tbody);
      }
    });
  });

  $(document).on('click', '.fwp-nav-add', function (e) {
    e.preventDefault();
    var section = $(this).data('section');
    var $tbody = $('.fwp-nav-rows[data-section="' + section + '"]');
    var index = $tbody.find('tr').length;
    $tbody.append(navRowHtml(section, index));
  });

  $(document).on('click', '.fwp-nav-remove', function (e) {
    e.preventDefault();
    var $tbody = $(this).closest('tbody');
    $(this).closest('tr').remove();
    reindexNavRows($tbody);
  });

  $('form').on('submit', function () {
    $('.fwp-nav-rows').each(function () {
      reindexNavRows($(this));
    });
  });
});
JS;

	wp_add_inline_script( 'jquery-ui-sortable', $js );
}

/**
 * @param string               $section Menu key.
 * @param string               $title   Section title.
 * @param array<int, mixed>    $items   Menu items.
 */
function fwp_headless_app_render_nav_menu_section( $section, $title, $items ) {
	$option = FWP_HEADLESS_APP_SITE_OPTION_NAV;
	?>
	<h2><?php echo esc_html( $title ); ?></h2>
	<table class="widefat striped" style="max-width:760px;">
		<thead>
			<tr>
				<th style="width:36px;" aria-label="<?php esc_attr_e( 'Order', '4wp-headless-app' ); ?>"></th>
				<th><?php esc_html_e( 'Label', '4wp-headless-app' ); ?></th>
				<th><?php esc_html_e( 'URL', '4wp-headless-app' ); ?></th>
				<th style="width:48px;"></th>
			</tr>
		</thead>
		<tbody class="fwp-nav-rows" data-section="<?php echo esc_attr( $section ); ?>">
			<?php
			if ( empty( $items ) ) {
				$items = array( array( 'label' => '', 'href' => '' ) );
			}
			foreach ( $items as $i => $item ) :
				?>
				<tr>
					<td class="fwp-nav-sort-handle" title="<?php esc_attr_e( 'Drag to reorder', '4wp-headless-app' ); ?>">
						<span class="dashicons dashicons-menu" aria-hidden="true"></span>
					</td>
					<td>
						<input
							type="text"
							class="regular-text"
							name="<?php echo esc_attr( $option . '[' . $section . '][' . $i . '][label]' ); ?>"
							value="<?php echo esc_attr( $item['label'] ?? '' ); ?>"
						/>
					</td>
					<td>
						<input
							type="text"
							class="regular-text"
							name="<?php echo esc_attr( $option . '[' . $section . '][' . $i . '][href]' ); ?>"
							value="<?php echo esc_attr( $item['href'] ?? '' ); ?>"
							placeholder="/page"
						/>
					</td>
					<td>
						<button type="button" class="button fwp-nav-remove" aria-label="<?php esc_attr_e( 'Remove', '4wp-headless-app' ); ?>">&times;</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p>
		<button type="button" class="button fwp-nav-add" data-section="<?php echo esc_attr( $section ); ?>">
			<?php esc_html_e( 'Add item', '4wp-headless-app' ); ?>
		</button>
	</p>
	<?php
}

/**
 * Menus admin page.
 */
function fwp_headless_app_render_site_menus_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$menus = fwp_headless_app_get_site_nav_menus();
	$cta   = is_array( $menus['header_cta'] ?? null ) ? $menus['header_cta'] : array();
	$option = FWP_HEADLESS_APP_SITE_OPTION_NAV;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Menus', '4wp-headless-app' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Navigation synced to the headless frontend via REST API.', '4wp-headless-app' ); ?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( '4wp_headless_app_site_menus' ); ?>

			<h2><?php esc_html_e( 'Header — CTA button', '4wp-headless-app' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Gold button in the header (e.g. «Консультація»). Leave label empty to hide.', '4wp-headless-app' ); ?>
			</p>
			<table class="form-table" role="presentation" style="max-width:760px;">
				<tr>
					<th scope="row">
						<label for="fwp_header_cta_label"><?php esc_html_e( 'Label', '4wp-headless-app' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							class="regular-text"
							id="fwp_header_cta_label"
							name="<?php echo esc_attr( $option . '[header_cta][label]' ); ?>"
							value="<?php echo esc_attr( $cta['label'] ?? 'Консультація' ); ?>"
							placeholder="<?php esc_attr_e( 'Консультація', '4wp-headless-app' ); ?>"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="fwp_header_cta_href"><?php esc_html_e( 'URL', '4wp-headless-app' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							class="regular-text"
							id="fwp_header_cta_href"
							name="<?php echo esc_attr( $option . '[header_cta][href]' ); ?>"
							value="<?php echo esc_attr( $cta['href'] ?? '/contacts' ); ?>"
							placeholder="/contacts"
						/>
						<p class="description">
							<?php esc_html_e( 'Path without domain, e.g. /contacts or tel:+380…', '4wp-headless-app' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php
			fwp_headless_app_render_nav_menu_section(
				'primary',
				__( 'Primary (header)', '4wp-headless-app' ),
				$menus['primary'] ?? array()
			);
			fwp_headless_app_render_nav_menu_section(
				'services',
				__( 'Footer — Services', '4wp-headless-app' ),
				$menus['services'] ?? array()
			);
			fwp_headless_app_render_nav_menu_section(
				'regions',
				__( 'Regions (city landing pages)', '4wp-headless-app' ),
				$menus['regions'] ?? array()
			);
			?>
			<p class="description">
				<?php esc_html_e( 'Region URLs are refreshed from geo_area + linked pages on each API export. Run migration once after plugin update to reparent pages under /region/.', '4wp-headless-app' ); ?>
			</p>
			<?php
			fwp_headless_app_render_nav_menu_section(
				'navigation',
				__( 'Footer — Navigation', '4wp-headless-app' ),
				$menus['navigation'] ?? array()
			);
			?>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
