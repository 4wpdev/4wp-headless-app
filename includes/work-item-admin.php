<?php
/**
 * Work Item admin — meta box (cover / gallery / labels / icon) + list filters.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icon choices for work card (matches Lucide slugs on the front).
 *
 * @return array<string, string> slug => label
 */
function fwp_headless_app_work_item_icon_choices() {
	return array(
		'moon'      => __( 'Місяць (темний)', '4wp-headless-app' ),
		'sun'       => __( 'Сонце (світлий)', '4wp-headless-app' ),
		'building2' => __( 'Будівля', '4wp-headless-app' ),
		'layers'    => __( 'Шари / комбо', '4wp-headless-app' ),
	);
}

/**
 * Register work_item edit meta box.
 */
function fwp_headless_app_register_work_item_meta_boxes() {
	$model = fwp_headless_app_get_content_model();
	if ( ! fwp_headless_app_model_uses_post_type( 'work_item', $model ) ) {
		return;
	}

	add_meta_box(
		'grv_work_item_media',
		__( 'GRV — фото, підписи, іконка', '4wp-headless-app' ),
		'fwp_headless_app_render_work_item_meta_box',
		'work_item',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'fwp_headless_app_register_work_item_meta_boxes' );

/**
 * Enqueue media library on work_item edit screen.
 *
 * @param string $hook_suffix Current admin page.
 */
function fwp_headless_app_enqueue_work_item_admin_assets( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'work_item' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();

	$js = <<<'JS'
jQuery(function ($) {
  function parseGalleryIds(raw) {
    return String(raw || '')
      .split(/[\s,]+/)
      .map(function (id) { return parseInt(id, 10); })
      .filter(function (id) { return id > 0; });
  }

  function uniqueIds(ids) {
    var seen = {};
    return ids.filter(function (id) {
      if (seen[id]) {
        return false;
      }
      seen[id] = true;
      return true;
    });
  }

  function thumbHtml(a) {
    var thumb = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : (a.url || '');
    if (a.type === 'video') {
      return '<div class="fwp-wi-thumb" data-id="' + a.id + '" style="width:72px;height:72px;border:1px solid #ccd0d4;display:flex;align-items:center;justify-content:center;font-size:11px;background:#f0f0f1;">VIDEO</div>';
    }
    return '<div class="fwp-wi-thumb" data-id="' + a.id + '"><img src="' + thumb + '" alt="" style="width:72px;height:72px;object-fit:cover;display:block;border:1px solid #ccd0d4;" /></div>';
  }

  function appendGalleryAttachments(attachments) {
    var $input = $('#fwp_wi_gallery_ids');
    var $preview = $('#fwp_wi_gallery_preview');
    var ids = uniqueIds(parseGalleryIds($input.val()));
    var htmlParts = [];

    attachments.forEach(function (a) {
      if (!a || !a.id || ids.indexOf(a.id) !== -1) {
        return;
      }
      ids.push(a.id);
      htmlParts.push(thumbHtml(a));
    });

    if (!htmlParts.length) {
      return;
    }

    $input.val(ids.join(','));
    $preview.append(htmlParts.join('')).css({ display: 'flex', gap: '8px', flexWrap: 'wrap' });
  }

  function bindPicker(opts) {
    var frame;
    $(opts.openBtn).on('click', function (e) {
      e.preventDefault();
      if (frame) {
        frame.dispose();
        frame = null;
      }
      frame = wp.media({
        title: opts.title,
        button: { text: opts.button },
        library: opts.library || { type: ['image', 'video'] },
        multiple: opts.multiple ? 'add' : false
      });
      frame.on('select', function () {
        var selection = frame.state().get('selection');
        if (opts.multiple) {
          var attachments = [];
          selection.each(function (att) {
            attachments.push(att.toJSON());
          });
          appendGalleryAttachments(attachments);
        } else {
          var a = selection.first().toJSON();
          $(opts.input).val(a.id);
          var url = (a.sizes && a.sizes.medium) ? a.sizes.medium.url : a.url;
          $(opts.preview).html('<img src="' + url + '" alt="" style="max-width:240px;height:auto;display:block;border:1px solid #ccd0d4;" />');
        }
      });
      frame.open();
    });

    $(opts.clearBtn).on('click', function (e) {
      e.preventDefault();
      $(opts.input).val('');
      $(opts.preview).empty();
    });
  }

  bindPicker({
    openBtn: '#fwp_wi_gallery_pick',
    clearBtn: '#fwp_wi_gallery_clear',
    input: '#fwp_wi_gallery_ids',
    preview: '#fwp_wi_gallery_preview',
    title: 'Галерея роботи',
    button: 'Додати до галереї',
    multiple: true
  });

  bindPicker({
    openBtn: '#fwp_wi_cover_pick',
    clearBtn: '#fwp_wi_cover_clear',
    input: '#fwp_wi_cover_id',
    preview: '#fwp_wi_cover_preview',
    title: 'Обкладинка роботи',
    button: 'Використати',
    library: { type: 'image' },
    multiple: false
  });
});
JS;

	wp_add_inline_script( 'jquery', $js );
}
add_action( 'admin_enqueue_scripts', 'fwp_headless_app_enqueue_work_item_admin_assets' );

/**
 * @param WP_Post $post Work item post.
 */
function fwp_headless_app_render_work_item_meta_box( $post ) {
	wp_nonce_field( 'grv_work_item_meta', 'grv_work_item_meta_nonce' );

	$cover_id = (int) get_post_meta( $post->ID, 'cover_id', true );
	if ( ! $cover_id ) {
		$cover_id = (int) get_post_thumbnail_id( $post->ID );
	}

	$gallery_ids = get_post_meta( $post->ID, 'gallery_ids', true );
	if ( ! is_array( $gallery_ids ) ) {
		$gallery_ids = array();
	}
	$gallery_ids = array_values( array_filter( array_map( 'intval', $gallery_ids ) ) );

	$location     = (string) get_post_meta( $post->ID, 'location_label', true );
	$photo_labels = get_post_meta( $post->ID, 'photo_labels', true );
	if ( ! is_array( $photo_labels ) ) {
		$photo_labels = array();
	}
	$icon     = (string) get_post_meta( $post->ID, 'card_icon', true );
	$icons    = fwp_headless_app_work_item_icon_choices();
	if ( ! $icon || ! isset( $icons[ $icon ] ) ) {
		$icon = 'moon';
	}
	?>
	<p class="description" style="margin-top:0;">
		<?php esc_html_e( 'Ці поля потрапляють у API для каруселі «Наші роботи» та портфоліо.', '4wp-headless-app' ); ?>
	</p>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="fwp_wi_cover_id"><?php esc_html_e( 'Обкладинка (головне фото)', '4wp-headless-app' ); ?></label></th>
			<td>
				<input type="hidden" id="fwp_wi_cover_id" name="fwp_wi_cover_id" value="<?php echo esc_attr( (string) $cover_id ); ?>" />
				<div id="fwp_wi_cover_preview" style="margin-bottom:8px;">
					<?php
					if ( $cover_id ) {
						echo wp_get_attachment_image( $cover_id, 'medium', false, array( 'style' => 'max-width:240px;height:auto;display:block;border:1px solid #ccd0d4;' ) );
					}
					?>
				</div>
				<p>
					<button type="button" class="button" id="fwp_wi_cover_pick"><?php esc_html_e( 'Вибрати фото', '4wp-headless-app' ); ?></button>
					<button type="button" class="button" id="fwp_wi_cover_clear"><?php esc_html_e( 'Очистити', '4wp-headless-app' ); ?></button>
				</p>
				<p class="description"><?php esc_html_e( 'Показується в каруселі на головній. Також синхронізується з «Зображення запису» справа.', '4wp-headless-app' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="fwp_wi_gallery_ids"><?php esc_html_e( 'Галерея', '4wp-headless-app' ); ?></label></th>
			<td>
				<input type="hidden" id="fwp_wi_gallery_ids" name="fwp_wi_gallery_ids" value="<?php echo esc_attr( implode( ',', $gallery_ids ) ); ?>" />
				<div id="fwp_wi_gallery_preview" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
					<?php foreach ( $gallery_ids as $aid ) : ?>
						<?php
						$mime = get_post_mime_type( $aid );
						if ( $mime && 0 === strpos( (string) $mime, 'video/' ) ) :
							?>
							<div class="fwp-wi-thumb" data-id="<?php echo esc_attr( (string) $aid ); ?>" style="width:72px;height:72px;border:1px solid #ccd0d4;display:flex;align-items:center;justify-content:center;font-size:11px;background:#f0f0f1;">VIDEO</div>
						<?php else : ?>
							<div class="fwp-wi-thumb" data-id="<?php echo esc_attr( (string) $aid ); ?>">
								<?php echo wp_get_attachment_image( $aid, 'thumbnail', false, array( 'style' => 'width:72px;height:72px;object-fit:cover;display:block;border:1px solid #ccd0d4;' ) ); ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
				<p>
					<button type="button" class="button" id="fwp_wi_gallery_pick"><?php esc_html_e( 'Додати фото / відео', '4wp-headless-app' ); ?></button>
					<button type="button" class="button" id="fwp_wi_gallery_clear"><?php esc_html_e( 'Очистити галерею', '4wp-headless-app' ); ?></button>
				</p>
				<p class="description"><?php esc_html_e( 'Натисніть кілька мініатюр по черзі, потім «Додати до галереї». Нові файли додаються до вже обраних.', '4wp-headless-app' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="fwp_wi_location_label"><?php esc_html_e( 'Локація / підпис обʼєкта', '4wp-headless-app' ); ?></label></th>
			<td>
				<input type="text" class="regular-text" id="fwp_wi_location_label" name="fwp_wi_location_label" value="<?php echo esc_attr( $location ); ?>" placeholder="<?php esc_attr_e( 'напр. Стурміка, Луцьк', '4wp-headless-app' ); ?>" />
				<p class="description"><?php esc_html_e( 'Назва місця під фото (якщо немає — береться заголовок запису).', '4wp-headless-app' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="fwp_wi_photo_labels"><?php esc_html_e( 'Підписи зверху (теги)', '4wp-headless-app' ); ?></label></th>
			<td>
				<textarea class="large-text" rows="4" id="fwp_wi_photo_labels" name="fwp_wi_photo_labels" placeholder="<?php esc_attr_e( "Внутрішні роботи\nПідготовка стін", '4wp-headless-app' ); ?>"><?php echo esc_textarea( implode( "\n", $photo_labels ) ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Один підпис на рядок — кожен рядок окремий тег зверху справа на картці. Також використовуються як підписи до фото галереї (по порядку).', '4wp-headless-app' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="fwp_wi_card_icon"><?php esc_html_e( 'Іконка на картці', '4wp-headless-app' ); ?></label></th>
			<td>
				<select id="fwp_wi_card_icon" name="fwp_wi_card_icon">
					<?php foreach ( $icons as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $icon, $slug ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Маленька іконка зліва зверху на картці в каруселі.', '4wp-headless-app' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save work_item media / labels / icon.
 *
 * @param int $post_id Post ID.
 */
function fwp_headless_app_save_work_item_meta( $post_id ) {
	if ( ! isset( $_POST['grv_work_item_meta_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['grv_work_item_meta_nonce'] ) ), 'grv_work_item_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( 'work_item' !== get_post_type( $post_id ) ) {
		return;
	}

	$cover_id = isset( $_POST['fwp_wi_cover_id'] ) ? absint( $_POST['fwp_wi_cover_id'] ) : 0;
	update_post_meta( $post_id, 'cover_id', $cover_id );
	if ( $cover_id ) {
		set_post_thumbnail( $post_id, $cover_id );
	} else {
		delete_post_thumbnail( $post_id );
	}

	$gallery_raw = isset( $_POST['fwp_wi_gallery_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['fwp_wi_gallery_ids'] ) ) : '';
	$gallery_ids = array();
	if ( '' !== $gallery_raw ) {
		$gallery_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', preg_split( '/[\s,]+/', $gallery_raw ) )
				)
			)
		);
	}
	update_post_meta( $post_id, 'gallery_ids', $gallery_ids );

	$location = isset( $_POST['fwp_wi_location_label'] ) ? sanitize_text_field( wp_unslash( $_POST['fwp_wi_location_label'] ) ) : '';
	update_post_meta( $post_id, 'location_label', $location );

	$labels_raw = isset( $_POST['fwp_wi_photo_labels'] ) ? (string) wp_unslash( $_POST['fwp_wi_photo_labels'] ) : '';
	$labels     = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $labels_raw ) as $line ) {
		$line = sanitize_text_field( $line );
		if ( '' !== $line ) {
			$labels[] = $line;
		}
	}
	update_post_meta( $post_id, 'photo_labels', $labels );

	$icons = fwp_headless_app_work_item_icon_choices();
	$icon  = isset( $_POST['fwp_wi_card_icon'] ) ? sanitize_key( wp_unslash( $_POST['fwp_wi_card_icon'] ) ) : 'moon';
	if ( ! isset( $icons[ $icon ] ) ) {
		$icon = 'moon';
	}
	update_post_meta( $post_id, 'card_icon', $icon );
}
add_action( 'save_post_work_item', 'fwp_headless_app_save_work_item_meta' );

/**
 * Dropdown filters on work_item list table.
 *
 * @param string $post_type Current post type.
 */
function fwp_headless_app_work_item_taxonomy_filters( $post_type ) {
	if ( 'work_item' !== $post_type ) {
		return;
	}

	$taxonomies = array(
		'catalog_line' => __( 'Усі Catalog Line', '4wp-headless-app' ),
		'geo_area'     => __( 'Усі Geo Area', '4wp-headless-app' ),
	);

	foreach ( $taxonomies as $taxonomy => $all_label ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$selected = isset( $_GET[ $taxonomy ] ) ? sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) ) : '';

		wp_dropdown_categories(
			array(
				'show_option_all' => $all_label,
				'taxonomy'        => $taxonomy,
				'name'            => $taxonomy,
				'orderby'         => 'name',
				'selected'        => $selected,
				'hierarchical'    => true,
				'depth'           => 0,
				'show_count'      => true,
				'hide_empty'      => false,
				'value_field'     => 'slug',
			)
		);
	}
}
add_action( 'restrict_manage_posts', 'fwp_headless_app_work_item_taxonomy_filters' );

/**
 * Apply taxonomy filters from dropdowns (slug-based).
 *
 * @param WP_Query $query Query.
 */
function fwp_headless_app_work_item_parse_taxonomy_filters( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'edit-work_item' !== $screen->id ) {
		return;
	}

	$tax_query = array();

	foreach ( array( 'catalog_line', 'geo_area' ) as $taxonomy ) {
		if ( empty( $_GET[ $taxonomy ] ) ) {
			continue;
		}
		$slug = sanitize_title( wp_unslash( $_GET[ $taxonomy ] ) );
		if ( $slug === '' || $slug === '0' ) {
			continue;
		}
		$tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => array( $slug ),
		);
	}

	if ( empty( $tax_query ) ) {
		return;
	}

	if ( count( $tax_query ) > 1 ) {
		$tax_query['relation'] = 'AND';
	}

	$query->set( 'tax_query', $tax_query );
}
add_action( 'parse_query', 'fwp_headless_app_work_item_parse_taxonomy_filters' );

/**
 * Make taxonomy columns sortable (by term name via title-like join).
 *
 * @param array<string, string> $columns Sortable columns.
 * @return array<string, string>
 */
function fwp_headless_app_work_item_sortable_columns( $columns ) {
	$columns['taxonomy-catalog_line'] = 'catalog_line';
	$columns['taxonomy-geo_area']     = 'geo_area';
	return $columns;
}
add_filter( 'manage_edit-work_item_sortable_columns', 'fwp_headless_app_work_item_sortable_columns' );

/**
 * Order work_item list by taxonomy term name when column header is clicked.
 *
 * @param WP_Query $query Query.
 */
function fwp_headless_app_work_item_orderby_taxonomy( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'edit-work_item' !== $screen->id ) {
		return;
	}

	$orderby = $query->get( 'orderby' );
	if ( ! in_array( $orderby, array( 'catalog_line', 'geo_area' ), true ) ) {
		return;
	}

	$taxonomy = $orderby;
	$order    = strtoupper( (string) $query->get( 'order' ) ) === 'DESC' ? 'DESC' : 'ASC';

	global $wpdb;

	$query->set( 'orderby', 'taxonomy_name' );
	$query->set( 'order', $order );

	add_filter(
		'posts_clauses',
		static function ( $clauses ) use ( $wpdb, $taxonomy, $order ) {
			$clauses['join'] .= $wpdb->prepare(
				" LEFT JOIN (
					SELECT tr.object_id, MIN(t.name) AS taxonomy_name
					FROM {$wpdb->term_relationships} tr
					INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = %s
					INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
					GROUP BY tr.object_id
				) AS fwp_tax_order ON ({$wpdb->posts}.ID = fwp_tax_order.object_id) ",
				$taxonomy
			);
			$clauses['orderby'] = "fwp_tax_order.taxonomy_name {$order}, {$wpdb->posts}.post_title ASC";
			return $clauses;
		},
		20
	);
}
add_action( 'pre_get_posts', 'fwp_headless_app_work_item_orderby_taxonomy' );
