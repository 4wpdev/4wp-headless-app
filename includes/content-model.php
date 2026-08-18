<?php
/**
 * Per-app content model — which CPTs and taxonomies each profile uses.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

const FWP_HEADLESS_APP_OPTION_CONTENT_MODEL = '4wp_headless_app_content_model';

/**
 * Default content models keyed by app profile.
 *
 * @return array<string, array<string, mixed>>
 */
function fwp_headless_app_get_default_content_models() {
	return array(
		'portfolio-v1' => array(
			'app'         => 'portfolio-v1',
			'version'     => 1,
			'post_types'  => array( 'fwp_skill', 'fwp_experience', 'fwp_service', 'fwp_project' ),
			'taxonomies'  => array( 'fwp_project_category' ),
			'options'     => array(),
		),
		'headless-site' => array(
			'app'         => 'headless-site',
			'version'     => 1,
			'post_types'  => array( 'work_item', 'team_member', 'faq_item', 'page' ),
			'taxonomies'  => array( 'catalog_line', 'geo_area' ),
			'options'     => array( 'site_settings', 'site_brand', 'nav_menus' ),
		),
	);
}

/**
 * Resolve content model for an app profile.
 *
 * Priority: saved option (after apply) → seed JSON → registry → inherited parent → defaults.
 *
 * @param string $app_key App profile key.
 * @return array<string, mixed>
 */
function fwp_headless_app_get_content_model( $app_key = '' ) {
	if ( '' === $app_key ) {
		$app_key = get_option( FWP_HEADLESS_APP_OPTION_APP_KEY, '' );
	}

	$empty = array(
		'app'        => '',
		'version'    => 1,
		'post_types' => array(),
		'taxonomies' => array(),
		'options'    => array(),
	);

	if ( '' === $app_key ) {
		return $empty;
	}

	$saved = get_option( FWP_HEADLESS_APP_OPTION_CONTENT_MODEL, array() );
	if ( ! empty( $saved['app'] ) && $saved['app'] === $app_key ) {
		return $saved;
	}

	$seed = fwp_headless_app_get_app_seed( $app_key );
	if ( ! empty( $seed['content_model'] ) && is_array( $seed['content_model'] ) ) {
		return fwp_headless_app_normalize_content_model( $seed['content_model'], $app_key );
	}

	$apps    = fwp_headless_app_get_apps();
	$app     = $apps[ $app_key ] ?? array();
	$defaults = fwp_headless_app_get_default_content_models();

	if ( ! empty( $app['content_model'] ) ) {
		return fwp_headless_app_normalize_content_model( $app['content_model'], $app_key );
	}

	if ( ! empty( $app['content_model_key'] ) && ! empty( $defaults[ $app['content_model_key'] ] ) ) {
		$base = $defaults[ $app['content_model_key'] ];
		return fwp_headless_app_normalize_content_model(
			array_merge( $base, array( 'app' => $app_key ) ),
			$app_key
		);
	}

	if ( ! empty( $defaults[ $app_key ] ) ) {
		return $defaults[ $app_key ];
	}

	if ( ! empty( $app['inherits'] ) ) {
		return fwp_headless_app_get_content_model( $app['inherits'] );
	}

	return $empty;
}

/**
 * Normalize and validate a content model array.
 *
 * @param array<string, mixed> $model   Raw model.
 * @param string               $app_key App key fallback.
 * @return array<string, mixed>
 */
function fwp_headless_app_normalize_content_model( $model, $app_key = '' ) {
	return array(
		'app'        => $model['app'] ?? $app_key,
		'version'    => (int) ( $model['version'] ?? 1 ),
		'post_types' => array_values( array_unique( (array) ( $model['post_types'] ?? array() ) ) ),
		'taxonomies' => array_values( array_unique( (array) ( $model['taxonomies'] ?? array() ) ) ),
		'options'    => array_values( array_unique( (array) ( $model['options'] ?? array() ) ) ),
	);
}

/**
 * Persist content model after profile apply.
 *
 * @param array<string, mixed> $model Content model.
 */
function fwp_headless_app_save_content_model( $model ) {
	if ( empty( $model ) ) {
		return;
	}
	update_option( FWP_HEADLESS_APP_OPTION_CONTENT_MODEL, fwp_headless_app_normalize_content_model( $model, $model['app'] ?? '' ) );
}

/**
 * @param string               $post_type Post type slug.
 * @param array<string, mixed> $model     Content model.
 */
function fwp_headless_app_model_uses_post_type( $post_type, $model = null ) {
	if ( null === $model ) {
		$model = fwp_headless_app_get_content_model();
	}
	return in_array( $post_type, $model['post_types'] ?? array(), true );
}

/**
 * @param string               $taxonomy Taxonomy slug.
 * @param array<string, mixed> $model    Content model.
 */
function fwp_headless_app_model_uses_taxonomy( $taxonomy, $model = null ) {
	if ( null === $model ) {
		$model = fwp_headless_app_get_content_model();
	}
	return in_array( $taxonomy, $model['taxonomies'] ?? array(), true );
}

/**
 * Whether an app profile uses the headless site export (site_settings, REST /export).
 *
 * @param string $app_key App profile key.
 */
function fwp_headless_app_profile_uses_site_export( $app_key = '' ) {
	if ( '' === $app_key ) {
		$app_key = get_option( FWP_HEADLESS_APP_OPTION_APP_KEY, '' );
	}
	if ( '' === $app_key ) {
		return false;
	}

	$model = fwp_headless_app_get_content_model( $app_key );
	return in_array( 'site_settings', $model['options'] ?? array(), true );
}
