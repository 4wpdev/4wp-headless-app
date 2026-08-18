<?php
/**
 * Team Member admin — meta box (nick / role / media).
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * Clearer title placeholder on Team edit screen.
 *
 * @param string $title Default title placeholder.
 * @return string
 */
function fwp_headless_app_team_member_enter_title( $title ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'team_member' === $screen->post_type ) {
		return __( 'Імʼя (напр. Віталій)', '4wp-headless-app' );
	}
	return $title;
}
add_filter( 'enter_title_here', 'fwp_headless_app_team_member_enter_title' );

/**
 * Register team_member edit meta box.
 */
function fwp_headless_app_register_team_member_meta_boxes() {
	$model = fwp_headless_app_get_content_model();
	if ( ! fwp_headless_app_model_uses_post_type( 'team_member', $model ) ) {
		return;
	}

	add_meta_box(
		'grv_team_member_meta',
		__( 'Дані члена команди', '4wp-headless-app' ),
		'fwp_headless_app_render_team_member_meta_box',
		'team_member',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'fwp_headless_app_register_team_member_meta_boxes' );

/**
 * Hide redundant Featured Image — media is managed in the profile box.
 */
function fwp_headless_app_team_member_remove_duplicate_boxes() {
	remove_meta_box( 'postimagediv', 'team_member', 'side' );
}
add_action( 'add_meta_boxes_team_member', 'fwp_headless_app_team_member_remove_duplicate_boxes', 20 );

/**
 * @param string $hook_suffix Current admin page.
 */
function fwp_headless_app_enqueue_team_member_admin_assets( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'team_member' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();

	$js = <<<'JS'
jQuery(function ($) {
  var frame;

  function setMediaType(type) {
    var isVideo = type === 'video';
    $('#fwp_tm_media_type').val(isVideo ? 'video' : 'photo');
  }

  $('#fwp_tm_media_pick').on('click', function (e) {
    e.preventDefault();
    if (frame) {
      frame.open();
      return;
    }
    frame = wp.media({
      title: 'Фото або відео',
      button: { text: 'Використати' },
      library: { type: ['image', 'video'] },
      multiple: false
    });
    frame.on('select', function () {
      var a = frame.state().get('selection').first().toJSON();
      var isVideo = a.type === 'video' || (a.mime && String(a.mime).indexOf('video/') === 0);
      setMediaType(isVideo ? 'video' : 'photo');
      $('#fwp_tm_media_id').val(a.id);
      $('#fwp_tm_media_url').val(a.url || '');
      var $preview = $('#fwp_tm_media_preview');
      if (isVideo) {
        $preview.html('<video src="' + a.url + '" controls style="max-width:320px;max-height:240px;display:block;background:#111;"></video>');
      } else {
        var url = (a.sizes && a.sizes.medium) ? a.sizes.medium.url : a.url;
        $preview.html('<img src="' + url + '" alt="" style="max-width:320px;height:auto;display:block;border:1px solid #ccd0d4;" />');
      }
    });
    frame.open();
  });

  $('#fwp_tm_media_clear').on('click', function (e) {
    e.preventDefault();
    $('#fwp_tm_media_id').val('0');
    $('#fwp_tm_media_url').val('');
    $('#fwp_tm_media_preview').empty();
  });
});
JS;

	wp_add_inline_script( 'jquery', $js );
}
add_action( 'admin_enqueue_scripts', 'fwp_headless_app_enqueue_team_member_admin_assets' );

/**
 * @param WP_Post $post Team member post.
 */
function fwp_headless_app_render_team_member_meta_box( $post ) {
	wp_nonce_field( 'grv_team_member_meta', 'grv_team_member_meta_nonce' );

	$nick       = (string) get_post_meta( $post->ID, 'nick', true );
	$role       = (string) get_post_meta( $post->ID, 'role_label', true );
	$subtitle   = (string) get_post_meta( $post->ID, 'subtitle', true );
	$media_type = sanitize_key( (string) get_post_meta( $post->ID, 'media_type', true ) );
	$media_type = in_array( $media_type, array( 'photo', 'video' ), true ) ? $media_type : 'photo';
	$media_id   = (int) get_post_meta( $post->ID, 'media_id', true );
	$media_url  = (string) get_post_meta( $post->ID, 'media_url', true );
	if ( $media_id > 0 ) {
		$attachment_url = wp_get_attachment_url( $media_id );
		if ( $attachment_url ) {
			$media_url = $attachment_url;
		}
	}
	?>
	<p class="description" style="margin-top:0;">
		<?php esc_html_e( 'Поле «Імʼя» зверху (Title) — імʼя на картці. Нижче — дані для блоку Team Section.', '4wp-headless-app' ); ?>
	</p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="fwp_tm_nick"><?php esc_html_e( 'Нік TikTok', '4wp-headless-app' ); ?></label></th>
			<td>
				<input type="text" class="regular-text" id="fwp_tm_nick" name="fwp_tm_nick" value="<?php echo esc_attr( $nick ); ?>" placeholder="@grv_build" />
				<p class="description"><?php esc_html_e( 'Показується золотим текстом зверху на картці.', '4wp-headless-app' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="fwp_tm_role"><?php esc_html_e( 'Посада', '4wp-headless-app' ); ?></label></th>
			<td>
				<input type="text" class="regular-text" id="fwp_tm_role" name="fwp_tm_role" value="<?php echo esc_attr( $role ); ?>" placeholder="<?php esc_attr_e( 'Добрий Бригадир', '4wp-headless-app' ); ?>" />
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="fwp_tm_subtitle"><?php esc_html_e( 'Слоган', '4wp-headless-app' ); ?></label></th>
			<td>
				<input type="text" class="regular-text" id="fwp_tm_subtitle" name="fwp_tm_subtitle" value="<?php echo esc_attr( $subtitle ); ?>" placeholder="<?php esc_attr_e( 'Його бояться цегли', '4wp-headless-app' ); ?>" />
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Фото або відео', '4wp-headless-app' ); ?></th>
			<td>
				<input type="hidden" id="fwp_tm_media_type" name="fwp_tm_media_type" value="<?php echo esc_attr( $media_type ); ?>" />
				<input type="hidden" id="fwp_tm_media_id" name="fwp_tm_media_id" value="<?php echo esc_attr( (string) $media_id ); ?>" />
				<input type="hidden" id="fwp_tm_media_url" name="fwp_tm_media_url" value="<?php echo esc_attr( $media_url ); ?>" />
				<div id="fwp_tm_media_preview" style="margin-bottom:8px;">
					<?php if ( $media_url && 'video' === $media_type ) : ?>
						<video src="<?php echo esc_url( $media_url ); ?>" controls style="max-width:320px;max-height:240px;display:block;background:#111;"></video>
						<p class="description"><?php esc_html_e( 'Тип: відео', '4wp-headless-app' ); ?></p>
					<?php elseif ( $media_id ) : ?>
						<?php echo wp_get_attachment_image( $media_id, 'medium', false, array( 'style' => 'max-width:320px;height:auto;display:block;border:1px solid #ccd0d4;' ) ); ?>
						<p class="description"><?php esc_html_e( 'Тип: фото', '4wp-headless-app' ); ?></p>
					<?php elseif ( $media_url ) : ?>
						<img src="<?php echo esc_url( $media_url ); ?>" alt="" style="max-width:320px;height:auto;display:block;border:1px solid #ccd0d4;" />
						<p class="description"><?php esc_html_e( 'Тип: фото', '4wp-headless-app' ); ?></p>
					<?php endif; ?>
				</div>
				<p>
					<button type="button" class="button button-primary" id="fwp_tm_media_pick"><?php esc_html_e( 'Вибрати фото / відео', '4wp-headless-app' ); ?></button>
					<button type="button" class="button" id="fwp_tm_media_clear"><?php esc_html_e( 'Очистити', '4wp-headless-app' ); ?></button>
				</p>
				<p class="description"><?php esc_html_e( 'Тип визначається автоматично з вибраного файлу.', '4wp-headless-app' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * @param int $post_id Post ID.
 */
function fwp_headless_app_save_team_member_meta( $post_id ) {
	if ( ! isset( $_POST['grv_team_member_meta_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['grv_team_member_meta_nonce'] ) ), 'grv_team_member_meta' ) ) {
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
	if ( 'team_member' !== get_post_type( $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, 'nick', isset( $_POST['fwp_tm_nick'] ) ? sanitize_text_field( wp_unslash( $_POST['fwp_tm_nick'] ) ) : '' );
	update_post_meta( $post_id, 'role_label', isset( $_POST['fwp_tm_role'] ) ? sanitize_text_field( wp_unslash( $_POST['fwp_tm_role'] ) ) : '' );
	update_post_meta( $post_id, 'subtitle', isset( $_POST['fwp_tm_subtitle'] ) ? sanitize_text_field( wp_unslash( $_POST['fwp_tm_subtitle'] ) ) : '' );

	$media_type = isset( $_POST['fwp_tm_media_type'] ) ? sanitize_key( wp_unslash( $_POST['fwp_tm_media_type'] ) ) : 'photo';
	if ( ! in_array( $media_type, array( 'photo', 'video' ), true ) ) {
		$media_type = 'photo';
	}
	update_post_meta( $post_id, 'media_type', $media_type );

	$media_id  = isset( $_POST['fwp_tm_media_id'] ) ? absint( $_POST['fwp_tm_media_id'] ) : 0;
	$media_url = isset( $_POST['fwp_tm_media_url'] ) ? esc_url_raw( wp_unslash( $_POST['fwp_tm_media_url'] ) ) : '';
	if ( $media_id > 0 ) {
		$attachment_url = wp_get_attachment_url( $media_id );
		if ( $attachment_url ) {
			$media_url = $attachment_url;
		}
	}
	update_post_meta( $post_id, 'media_id', $media_id );
	update_post_meta( $post_id, 'media_url', $media_url );

	if ( $media_id && 'photo' === $media_type ) {
		set_post_thumbnail( $post_id, $media_id );
	}
}
add_action( 'save_post_team_member', 'fwp_headless_app_save_team_member_meta' );

/**
 * Team list always sorted by menu_order (drag-and-drop).
 *
 * @param WP_Query $query Query.
 */
function fwp_headless_app_team_member_default_list_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'edit-team_member' !== $screen->id ) {
		return;
	}

	// Keep search usable; otherwise always show drag order (ignore title/date sorts).
	if ( $query->get( 's' ) ) {
		return;
	}

	$query->set( 'orderby', 'menu_order' );
	$query->set( 'order', 'ASC' );
}
add_action( 'pre_get_posts', 'fwp_headless_app_team_member_default_list_order' );

/**
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function fwp_headless_app_team_member_list_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( 'cb' === $key ) {
			$new['cb']        = $label;
			$new['fwp_order'] = __( 'Порядок', '4wp-headless-app' );
			continue;
		}
		$new[ $key ] = $label;
	}
	if ( ! isset( $new['fwp_order'] ) ) {
		$new = array_merge( array( 'fwp_order' => __( 'Порядок', '4wp-headless-app' ) ), $new );
	}
	return $new;
}
add_filter( 'manage_team_member_posts_columns', 'fwp_headless_app_team_member_list_columns' );

/**
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function fwp_headless_app_team_member_list_column_content( $column, $post_id ) {
	if ( 'fwp_order' !== $column ) {
		return;
	}

	$order = (int) get_post_field( 'menu_order', $post_id );
	echo '<span class="fwp-team-drag-handle dashicons dashicons-menu" aria-hidden="true" title="' . esc_attr__( 'Перетягніть рядок для зміни порядку', '4wp-headless-app' ) . '"></span>';
	echo '<span class="fwp-team-order-num">' . esc_html( (string) $order ) . '</span>';
}
add_action( 'manage_team_member_posts_custom_column', 'fwp_headless_app_team_member_list_column_content', 10, 2 );

/**
 * Narrow «Порядок» column.
 *
 * @param string $hook_suffix Admin page.
 */
function fwp_headless_app_team_member_order_column_css( $hook_suffix ) {
	if ( 'edit.php' !== $hook_suffix ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'edit-team_member' !== $screen->id ) {
		return;
	}
	echo '<style>
		.wp-list-table .column-fwp_order{width:72px;text-align:center;}
		.fwp-team-drag-handle{cursor:grab;color:#1d2327;font-size:20px;width:20px;height:20px;vertical-align:middle;}
		.fwp-team-drag-handle:active{cursor:grabbing;}
		.fwp-team-order-num{display:inline-block;margin-left:4px;color:#646970;vertical-align:middle;}
		#the-list tr[id^="post-"]{cursor:grab;}
		#the-list tr.ui-sortable-helper{cursor:grabbing;}
		.fwp-team-sort-placeholder{background:#f0f6fc;visibility:visible!important;}
		#the-list .ui-sortable-helper{background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.12);}
		.fwp-team-order-hint{margin:8px 0 0;}
	</style>';
}
add_action( 'admin_head', 'fwp_headless_app_team_member_order_column_css' );

/**
 * Hint above Team list.
 *
 * @param string $which top|bottom.
 */
function fwp_headless_app_team_member_list_hint( $which ) {
	if ( 'top' !== $which ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'edit-team_member' !== $screen->id ) {
		return;
	}
	echo '<div class="notice notice-info inline fwp-team-order-hint"><p>';
	echo esc_html__( 'Перетягніть рядок (або іконку ≡ у колонці «Порядок»), щоб змінити порядок команди на сайті.', '4wp-headless-app' );
	echo '</p></div>';
}
add_action( 'manage_posts_extra_tablenav', 'fwp_headless_app_team_member_list_hint' );

/**
 * @param array<string, string> $columns Sortable columns.
 * @return array<string, string>
 */
function fwp_headless_app_team_member_sortable_columns( $columns ) {
	$columns['fwp_order'] = 'menu_order';
	return $columns;
}
add_filter( 'manage_edit-team_member_sortable_columns', 'fwp_headless_app_team_member_sortable_columns' );

/**
 * Enqueue drag-and-drop order UI on Team list.
 *
 * @param string $hook_suffix Admin page.
 */
function fwp_headless_app_enqueue_team_member_order_assets( $hook_suffix ) {
	if ( 'edit.php' !== $hook_suffix ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'edit-team_member' !== $screen->id ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/assets/admin/team-member-order.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script(
		'grv-team-member-order',
		plugins_url( 'assets/admin/team-member-order.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'jquery', 'jquery-ui-sortable' ),
		(string) filemtime( $script_path ),
		true
	);

	wp_localize_script(
		'grv-team-member-order',
		'fwpTeamMemberOrder',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'fwp_team_member_order' ),
			'i18n'    => array(
				'saved' => __( 'Порядок команди збережено.', '4wp-headless-app' ),
				'error' => __( 'Не вдалося зберегти порядок.', '4wp-headless-app' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'fwp_headless_app_enqueue_team_member_order_assets' );

/**
 * AJAX: save team_member menu_order from list drag-and-drop.
 */
function fwp_headless_app_ajax_save_team_member_order() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	check_ajax_referer( 'fwp_team_member_order', 'nonce' );

	$raw = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : '';
	if ( is_string( $raw ) ) {
		$decoded = json_decode( $raw, true );
	} else {
		$decoded = $raw;
	}

	if ( ! is_array( $decoded ) ) {
		wp_send_json_error( array( 'message' => 'invalid' ), 400 );
	}

	foreach ( $decoded as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$id    = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
		$order = isset( $item['order'] ) ? absint( $item['order'] ) : 0;
		if ( ! $id || 'team_member' !== get_post_type( $id ) ) {
			continue;
		}
		if ( ! current_user_can( 'edit_post', $id ) ) {
			continue;
		}
		wp_update_post(
			array(
				'ID'         => $id,
				'menu_order' => $order,
			)
		);
	}

	wp_send_json_success( array( 'saved' => true ) );
}
add_action( 'wp_ajax_fwp_save_team_member_order', 'fwp_headless_app_ajax_save_team_member_order' );
