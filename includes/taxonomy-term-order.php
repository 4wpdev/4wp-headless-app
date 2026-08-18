<?php
/**
 * Drag-and-drop term order for geo_area taxonomy.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

const FWP_HEADLESS_APP_TERM_ORDER_META = 'term_order';

/**
 * Register term order meta.
 */
function fwp_headless_app_register_geo_area_term_order_meta() {
	if ( ! taxonomy_exists( 'geo_area' ) ) {
		return;
	}

	register_term_meta(
		'geo_area',
		FWP_HEADLESS_APP_TERM_ORDER_META,
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_geo_area_term_order_meta', 20 );

/**
 * @param array<string, mixed> $args get_terms args.
 * @return array<string, mixed>
 */
function fwp_headless_app_geo_area_ordered_terms_args( $args = array() ) {
	$defaults = array(
		'taxonomy'         => 'geo_area',
		'hide_empty'       => false,
		'fwp_term_order'   => true,
	);

	return array_merge( $defaults, $args );
}

/**
 * @param array<string, mixed> $args get_terms args.
 * @return array<int, WP_Term>|WP_Error
 */
function fwp_headless_app_get_geo_area_terms_ordered( $args = array() ) {
	return get_terms( fwp_headless_app_geo_area_ordered_terms_args( $args ) );
}

/**
 * @param array<string, mixed> $args       get_terms args.
 * @param array<int, string>   $taxonomies Taxonomies.
 * @return array<string, mixed>
 */
function fwp_headless_app_geo_area_get_terms_args( $args, $taxonomies ) {
	$taxonomies = (array) $taxonomies;
	if ( ! in_array( 'geo_area', $taxonomies, true ) ) {
		return $args;
	}

	if ( empty( $args['fwp_term_order'] ) ) {
		if ( fwp_headless_app_is_geo_area_terms_admin_screen() ) {
			$args['fwp_term_order'] = true;
		} else {
			return $args;
		}
	}

	$args['orderby'] = 'name';

	return $args;
}
add_filter( 'get_terms_args', 'fwp_headless_app_geo_area_get_terms_args', 20, 2 );

/**
 * @return bool
 */
function fwp_headless_app_is_geo_area_terms_admin_screen() {
	if ( ! is_admin() ) {
		return false;
	}

	if ( function_exists( 'get_current_screen' ) ) {
		$screen = get_current_screen();
		if ( $screen && 'edit-tags' === $screen->base && 'geo_area' === $screen->taxonomy ) {
			return true;
		}
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( $_GET['taxonomy'] ) && 'geo_area' === sanitize_key( wp_unslash( $_GET['taxonomy'] ) );
}

/**
 * @param array<string, string> $clauses    SQL clauses.
 * @param array<int, string>    $taxonomies Taxonomies.
 * @param array<string, mixed>  $args       get_terms args.
 * @return array<string, string>
 */
function fwp_headless_app_geo_area_terms_clauses( $clauses, $taxonomies, $args ) {
	if ( empty( $args['fwp_term_order'] ) ) {
		return $clauses;
	}

	$taxonomies = (array) $taxonomies;
	if ( ! in_array( 'geo_area', $taxonomies, true ) ) {
		return $clauses;
	}

	global $wpdb;

	$clauses['join']    .= " LEFT JOIN {$wpdb->termmeta} AS fwp_tm_order ON (t.term_id = fwp_tm_order.term_id AND fwp_tm_order.meta_key = '" . esc_sql( FWP_HEADLESS_APP_TERM_ORDER_META ) . "')";
	$clauses['orderby']  = 'ORDER BY CAST(COALESCE(fwp_tm_order.meta_value, 0) AS UNSIGNED) ASC, t.name ASC';
	$clauses['order']    = '';

	return $clauses;
}
add_filter( 'terms_clauses', 'fwp_headless_app_geo_area_terms_clauses', 20, 3 );

/**
 * Drag handle column.
 *
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function fwp_headless_app_geo_area_term_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'cb' === $key ) {
			$new['fwp_drag'] = '';
		}
	}
	return $new;
}
add_filter( 'manage_edit-geo_area_columns', 'fwp_headless_app_geo_area_term_columns' );

/**
 * @param string $content     Column content.
 * @param string $column_name Column key.
 * @param int    $term_id     Term ID.
 * @return string
 */
function fwp_headless_app_geo_area_term_column_content( $content, $column_name, $term_id ) {
	if ( 'fwp_drag' === $column_name ) {
		return '<button type="button" class="fwp-term-drag-handle" aria-label="' . esc_attr__( 'Drag to reorder', '4wp-headless-app' ) . '"><span class="dashicons dashicons-menu"></span></button>';
	}
	return $content;
}
add_filter( 'manage_geo_area_custom_column', 'fwp_headless_app_geo_area_term_column_content', 10, 3 );

/**
 * Enqueue sortable UI on Geo Area list.
 *
 * @param string $hook_suffix Admin hook.
 */
function fwp_headless_app_enqueue_geo_area_term_order_assets( $hook_suffix ) {
	if ( 'edit-tags.php' !== $hook_suffix || ! fwp_headless_app_is_geo_area_terms_admin_screen() ) {
		return;
	}

	fwp_headless_app_maybe_bootstrap_geo_area_term_order();

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/assets/admin/geo-area-term-order.js';
	$style_path  = $plugin_root . '/assets/admin/geo-area-term-order.css';

	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script(
		'fwp-geo-area-term-order',
		plugins_url( 'assets/admin/geo-area-term-order.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'jquery', 'jquery-ui-sortable' ),
		(string) filemtime( $script_path ),
		true
	);

	if ( is_readable( $style_path ) ) {
		wp_enqueue_style(
			'fwp-geo-area-term-order',
			plugins_url( 'assets/admin/geo-area-term-order.css', $plugin_root . '/4wp-headless-app.php' ),
			array(),
			(string) filemtime( $style_path )
		);
	}

	$terms = fwp_headless_app_get_geo_area_terms_ordered();
	$map   = array();
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$map[ (int) $term->term_id ] = (int) $term->parent;
		}
	}

	wp_localize_script(
		'fwp-geo-area-term-order',
		'fwpGeoTermOrder',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'fwp_geo_area_term_order' ),
			'parents' => $map,
			'i18n'    => array(
				'saved'   => __( 'Order saved.', '4wp-headless-app' ),
				'invalid' => __( 'Terms can only be reordered within the same level and parent.', '4wp-headless-app' ),
				'error'   => __( 'Could not save order. Please try again.', '4wp-headless-app' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'fwp_headless_app_enqueue_geo_area_term_order_assets' );

/**
 * Assign initial term_order from current list when missing.
 */
function fwp_headless_app_maybe_bootstrap_geo_area_term_order() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'geo_area',
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return;
	}

	$needs_bootstrap = false;
	foreach ( $terms as $term ) {
		$meta = get_term_meta( $term->term_id, FWP_HEADLESS_APP_TERM_ORDER_META, true );
		if ( '' === $meta ) {
			$needs_bootstrap = true;
			break;
		}
	}

	if ( ! $needs_bootstrap ) {
		return;
	}

	$parents = array();
	$children = array();
	foreach ( $terms as $term ) {
		if ( 0 === (int) $term->parent ) {
			$parents[] = $term;
		} else {
			$children[ (int) $term->parent ][] = $term;
		}
	}

	usort(
		$parents,
		static function ( $a, $b ) {
			return strcasecmp( $a->name, $b->name );
		}
	);

	$order = 0;
	foreach ( $parents as $parent ) {
		update_term_meta( $parent->term_id, FWP_HEADLESS_APP_TERM_ORDER_META, $order );
		$order += 10;

		$siblings = $children[ (int) $parent->term_id ] ?? array();
		usort(
			$siblings,
			static function ( $a, $b ) {
				return strcasecmp( $a->name, $b->name );
			}
		);
		$child_order = 0;
		foreach ( $siblings as $child ) {
			update_term_meta( $child->term_id, FWP_HEADLESS_APP_TERM_ORDER_META, $child_order );
			$child_order += 10;
		}
	}
}

/**
 * Save term order from AJAX.
 */
function fwp_headless_app_ajax_save_geo_area_term_order() {
	check_ajax_referer( 'fwp_geo_area_term_order', 'nonce' );

	$taxonomy = get_taxonomy( 'geo_area' );
	if ( ! $taxonomy || ! current_user_can( $taxonomy->cap->edit_terms ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', '4wp-headless-app' ) ), 403 );
	}

	$raw = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : '';
	$items = json_decode( $raw, true );
	if ( ! is_array( $items ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid payload.', '4wp-headless-app' ) ), 400 );
	}

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$term_id = isset( $item['id'] ) ? (int) $item['id'] : 0;
		$order   = isset( $item['order'] ) ? (int) $item['order'] : 0;
		if ( $term_id <= 0 ) {
			continue;
		}
		$term = get_term( $term_id, 'geo_area' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		update_term_meta( $term_id, FWP_HEADLESS_APP_TERM_ORDER_META, $order );
	}

	wp_send_json_success( array( 'message' => __( 'Order saved.', '4wp-headless-app' ) ) );
}
add_action( 'wp_ajax_fwp_save_geo_area_term_order', 'fwp_headless_app_ajax_save_geo_area_term_order' );

/**
 * Persist term_order when seeding geo_area.
 *
 * @param int $term_id Term ID.
 * @param int $order   Sort index.
 */
function fwp_headless_app_set_geo_area_term_order( $term_id, $order ) {
	if ( $term_id <= 0 ) {
		return;
	}
	update_term_meta( $term_id, FWP_HEADLESS_APP_TERM_ORDER_META, (int) $order );
}
