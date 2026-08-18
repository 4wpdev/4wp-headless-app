<?php
/**
 * geo_area taxonomy — link city terms to headless region pages.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

const FWP_HEADLESS_APP_GEO_PAGE_META = 'page_id';
const FWP_HEADLESS_APP_GEO_LAT_META  = 'geo_latitude';
const FWP_HEADLESS_APP_GEO_LNG_META  = 'geo_longitude';

add_action( 'geo_area_add_form_fields', 'fwp_headless_app_geo_area_add_page_field' );
add_action( 'geo_area_edit_form_fields', 'fwp_headless_app_geo_area_edit_page_field', 10, 2 );
add_action( 'created_geo_area', 'fwp_headless_app_save_geo_area_page_field' );
add_action( 'edited_geo_area', 'fwp_headless_app_save_geo_area_page_field' );
add_filter( 'manage_edit-geo_area_columns', 'fwp_headless_app_geo_area_page_column' );
add_filter( 'manage_geo_area_custom_column', 'fwp_headless_app_geo_area_page_column_content', 10, 3 );

/**
 * @return array<int, array{id: int, title: string, slug: string}>
 */
function fwp_headless_app_get_region_page_choices() {
	$choices = array();
	$pages   = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		)
	);

	foreach ( $pages as $page ) {
		$choices[] = array(
			'id'    => (int) $page->ID,
			'title' => $page->post_title,
			'slug'  => fwp_headless_app_page_export_slug( $page ),
		);
	}

	return $choices;
}

/**
 * Published pages indexed by ID and export slug.
 *
 * @return array{by_id: array<int, string>, by_slug: array<string, int>}
 */
function fwp_headless_app_get_published_page_slug_map() {
	$by_id   = array();
	$by_slug = array();
	$pages   = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);

	foreach ( $pages as $page ) {
		$slug = fwp_headless_app_page_export_slug( $page );
		$by_id[ (int) $page->ID ] = $slug;
		$by_slug[ $slug ]         = (int) $page->ID;
	}

	return array(
		'by_id'   => $by_id,
		'by_slug' => $by_slug,
	);
}

/**
 * Resolve frontend href for a geo_area city term.
 *
 * @param int    $term_id   City term ID.
 * @param string $city_slug City term slug.
 * @param array  $slug_map  From fwp_headless_app_get_published_page_slug_map().
 * @return string
 */
function fwp_headless_app_geo_city_href( $term_id, $city_slug, $slug_map ) {
	$page_id = (int) get_term_meta( $term_id, FWP_HEADLESS_APP_GEO_PAGE_META, true );
	if ( $page_id > 0 && ! empty( $slug_map['by_id'][ $page_id ] ) ) {
		return $slug_map['by_id'][ $page_id ];
	}

	$candidates = array(
		'/region/region-' . $city_slug,
		'/region/' . $city_slug,
		'/region-' . $city_slug,
	);

	foreach ( $candidates as $candidate ) {
		if ( isset( $slug_map['by_slug'][ $candidate ] ) ) {
			return $candidate;
		}
	}

	return '/region/region-' . $city_slug;
}

/**
 * Geo metadata for a page linked from geo_area (city term).
 *
 * @param int $page_id Page ID.
 * @return array<string, string>
 */
function fwp_headless_app_page_geo_export_meta( $page_id ) {
	$page_id = (int) $page_id;
	if ( $page_id <= 0 ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'geo_area',
			'hide_empty' => false,
			'meta_query' => array(
				array(
					'key'     => FWP_HEADLESS_APP_GEO_PAGE_META,
					'value'   => $page_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		$terms = fwp_headless_app_geo_terms_for_page_post( $page_id );
	}

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	$city = $terms[0];
	if ( ! $city instanceof WP_Term ) {
		return array();
	}

	$oblast_name = '';
	if ( $city->parent ) {
		$parent = get_term( (int) $city->parent, 'geo_area' );
		if ( $parent instanceof WP_Term && ! is_wp_error( $parent ) ) {
			$oblast_name = $parent->name;
		}
	}

	return array(
		'geo_area_slug'     => $city->slug,
		'geo_name'          => $city->name,
		'geo_name_locative' => (string) get_term_meta( $city->term_id, 'name_locative', true ),
		'geo_display_name'  => (string) get_term_meta( $city->term_id, 'display_name', true ),
		'geo_oblast'        => $oblast_name,
		'geo_latitude'      => (string) get_term_meta( $city->term_id, FWP_HEADLESS_APP_GEO_LAT_META, true ),
		'geo_longitude'     => (string) get_term_meta( $city->term_id, FWP_HEADLESS_APP_GEO_LNG_META, true ),
	);
}

/**
 * Match geo city term from region page post_name (region-{slug}).
 *
 * @param int $page_id Page ID.
 * @return array<int, WP_Term>
 */
function fwp_headless_app_geo_terms_for_page_post( $page_id ) {
	$post = get_post( (int) $page_id );
	if ( ! $post || 'page' !== $post->post_type ) {
		return array();
	}

	$slug = (string) $post->post_name;
	if ( preg_match( '/^region-(.+)$/i', $slug, $matches ) ) {
		$term = get_term_by( 'slug', sanitize_title( $matches[1] ), 'geo_area' );
		if ( $term instanceof WP_Term && ! is_wp_error( $term ) && (int) $term->parent > 0 ) {
			return array( $term );
		}
	}

	return array();
}

/**
 * @param string $taxonomy Taxonomy slug.
 */
function fwp_headless_app_geo_area_add_page_field( $taxonomy ) {
	if ( 'geo_area' !== $taxonomy ) {
		return;
	}
	?>
	<div class="form-field">
		<label for="fwp_geo_page_id"><?php esc_html_e( 'Region page', '4wp-headless-app' ); ?></label>
		<?php fwp_headless_app_render_geo_page_select( 0 ); ?>
		<p class="description"><?php esc_html_e( 'Headless landing page for this city/region (e.g. /region/region-lutsk/).', '4wp-headless-app' ); ?></p>
	</div>
	<?php
}

/**
 * @param WP_Term $term     Term being edited.
 * @param string  $taxonomy Taxonomy slug.
 */
function fwp_headless_app_geo_area_edit_page_field( $term, $taxonomy ) {
	if ( 'geo_area' !== $taxonomy || ! ( $term instanceof WP_Term ) ) {
		return;
	}

	if ( 0 === (int) $term->parent ) {
		return;
	}

	$page_id = (int) get_term_meta( $term->term_id, FWP_HEADLESS_APP_GEO_PAGE_META, true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="fwp_geo_page_id"><?php esc_html_e( 'Region page', '4wp-headless-app' ); ?></label></th>
		<td>
			<?php fwp_headless_app_render_geo_page_select( $page_id ); ?>
			<p class="description"><?php esc_html_e( 'Assign a published page. URL comes from page hierarchy (parent /region/ → child region-lutsk).', '4wp-headless-app' ); ?></p>
		</td>
	</tr>
	<?php
	$lat = (string) get_term_meta( $term->term_id, FWP_HEADLESS_APP_GEO_LAT_META, true );
	$lng = (string) get_term_meta( $term->term_id, FWP_HEADLESS_APP_GEO_LNG_META, true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="fwp_geo_lat"><?php esc_html_e( 'Latitude', '4wp-headless-app' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" name="fwp_geo_latitude" id="fwp_geo_lat" value="<?php echo esc_attr( $lat ); ?>" placeholder="50.7472" />
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="fwp_geo_lng"><?php esc_html_e( 'Longitude', '4wp-headless-app' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" name="fwp_geo_longitude" id="fwp_geo_lng" value="<?php echo esc_attr( $lng ); ?>" placeholder="25.3254" />
			<p class="description"><?php esc_html_e( 'For Schema.org GeoCoordinates (optional).', '4wp-headless-app' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * @param int $selected_page_id Selected page ID.
 */
function fwp_headless_app_render_geo_page_select( $selected_page_id ) {
	$choices = fwp_headless_app_get_region_page_choices();
	?>
	<select name="fwp_geo_page_id" id="fwp_geo_page_id">
		<option value="0"><?php esc_html_e( '— Auto-detect by slug —', '4wp-headless-app' ); ?></option>
		<?php foreach ( $choices as $choice ) : ?>
			<option value="<?php echo esc_attr( (string) $choice['id'] ); ?>" <?php selected( $selected_page_id, $choice['id'] ); ?>>
				<?php echo esc_html( $choice['title'] . ' (' . $choice['slug'] . ')' ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * @param int $term_id Term ID.
 */
function fwp_headless_app_save_geo_area_page_field( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	$page_id = isset( $_POST['fwp_geo_page_id'] ) ? absint( wp_unslash( $_POST['fwp_geo_page_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $page_id > 0 && 'page' !== get_post_type( $page_id ) ) {
		$page_id = 0;
	}

	if ( $page_id > 0 ) {
		update_term_meta( $term_id, FWP_HEADLESS_APP_GEO_PAGE_META, $page_id );
	} else {
		delete_term_meta( $term_id, FWP_HEADLESS_APP_GEO_PAGE_META );
	}

	$lat = isset( $_POST['fwp_geo_latitude'] ) ? sanitize_text_field( wp_unslash( $_POST['fwp_geo_latitude'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$lng = isset( $_POST['fwp_geo_longitude'] ) ? sanitize_text_field( wp_unslash( $_POST['fwp_geo_longitude'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( '' !== $lat && is_numeric( $lat ) ) {
		update_term_meta( $term_id, FWP_HEADLESS_APP_GEO_LAT_META, $lat );
	} else {
		delete_term_meta( $term_id, FWP_HEADLESS_APP_GEO_LAT_META );
	}

	if ( '' !== $lng && is_numeric( $lng ) ) {
		update_term_meta( $term_id, FWP_HEADLESS_APP_GEO_LNG_META, $lng );
	} else {
		delete_term_meta( $term_id, FWP_HEADLESS_APP_GEO_LNG_META );
	}
}

/**
 * @param array<string, string> $columns Term columns.
 * @return array<string, string>
 */
function fwp_headless_app_geo_area_page_column( $columns ) {
	$columns['grv_page'] = __( 'Region page', '4wp-headless-app' );
	return $columns;
}

/**
 * @param string $content     Column content.
 * @param string $column_name Column key.
 * @param int    $term_id     Term ID.
 * @return string
 */
function fwp_headless_app_geo_area_page_column_content( $content, $column_name, $term_id ) {
	if ( 'grv_page' !== $column_name ) {
		return $content;
	}

	$term = get_term( $term_id, 'geo_area' );
	if ( ! $term instanceof WP_Term || 0 === (int) $term->parent ) {
		return '—';
	}

	$page_id = (int) get_term_meta( $term_id, FWP_HEADLESS_APP_GEO_PAGE_META, true );
	if ( $page_id <= 0 ) {
		return '<span style="color:#888;">' . esc_html__( 'Auto', '4wp-headless-app' ) . '</span>';
	}

	$post = get_post( $page_id );
	if ( ! $post ) {
		return '—';
	}

	$slug = fwp_headless_app_page_export_slug( $post );
	return esc_html( $post->post_title ) . '<br><code>' . esc_html( $slug ) . '</code>';
}
