<?php
/**
 * CMS REST API key — optional gate for public headless export routes.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * Configured CMS API key (Bedrock .env: CMS_API_KEY).
 */
function fwp_headless_app_get_cms_api_key() {
	static $key = null;

	if ( null !== $key ) {
		return $key;
	}

	$raw = '';
	if ( function_exists( 'env' ) ) {
		$raw = env( 'CMS_API_KEY' );
	}
	if ( ! is_string( $raw ) || $raw === '' ) {
		$raw = getenv( 'CMS_API_KEY' );
	}

	$key = is_string( $raw ) ? trim( $raw ) : '';

	return $key;
}

/**
 * Whether export/read routes require a matching API key.
 */
function fwp_headless_app_cms_api_key_required() {
	return fwp_headless_app_get_cms_api_key() !== '';
}

/**
 * API key from request (header preferred).
 *
 * @param WP_REST_Request $request Request.
 */
function fwp_headless_app_get_request_cms_api_key( $request ) {
	$header = $request->get_header( 'x-cms-api-key' );
	if ( is_string( $header ) && $header !== '' ) {
		return trim( $header );
	}

	$auth = $request->get_header( 'authorization' );
	if ( is_string( $auth ) && stripos( $auth, 'bearer ' ) === 0 ) {
		return trim( substr( $auth, 7 ) );
	}

	return '';
}

/**
 * Permission callback for headless CMS read routes.
 *
 * @param WP_REST_Request $request Request.
 * @return true|WP_Error
 */
function fwp_headless_app_rest_cms_api_permission_callback( $request ) {
	if ( ! fwp_headless_app_cms_api_key_required() ) {
		return true;
	}

	$configured = fwp_headless_app_get_cms_api_key();
	$provided   = fwp_headless_app_get_request_cms_api_key( $request );

	if ( is_string( $provided ) && $provided !== '' && hash_equals( $configured, $provided ) ) {
		return true;
	}

	return new WP_Error(
		'fwp_headless_app_unauthorized',
		__( 'Invalid or missing CMS API key.', '4wp-headless-app' ),
		array( 'status' => 401 )
	);
}
