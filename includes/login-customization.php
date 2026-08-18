<?php
/**
 * Branded wp-login — site logo, no language switcher, no privacy link.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether login branding applies (active headless profile with site_settings).
 */
function fwp_headless_app_should_customize_login() {
	return function_exists( 'fwp_headless_app_should_show_site_panel' )
		&& fwp_headless_app_should_show_site_panel();
}

/**
 * @return array<string, mixed>
 */
function fwp_headless_app_get_login_brand_settings() {
	static $settings = null;

	if ( null !== $settings ) {
		return $settings;
	}

	$settings = fwp_headless_app_get_site_option_value(
		FWP_HEADLESS_APP_SITE_OPTION_SETTINGS,
		'4wp_headless_app_grv_site_settings',
		array()
	);

	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	if ( function_exists( 'fwp_headless_app_site_settings_with_logo_url' ) ) {
		$settings = fwp_headless_app_site_settings_with_logo_url( $settings );
	}

	return $settings;
}

/**
 * Hide privacy policy link on login only.
 */
function fwp_headless_app_login_init() {
	if ( ! fwp_headless_app_should_customize_login() ) {
		return;
	}

	add_filter( 'privacy_policy_url', '__return_empty_string' );
}

/**
 * @param bool $display Whether to show the language dropdown.
 */
function fwp_headless_app_hide_login_language_dropdown( $display ) {
	if ( ! fwp_headless_app_should_customize_login() ) {
		return $display;
	}

	return false;
}

/**
 * @param string $url Login logo link URL.
 */
function fwp_headless_app_login_header_url( $url ) {
	if ( ! fwp_headless_app_should_customize_login() ) {
		return $url;
	}

	return home_url( '/' );
}

/**
 * @param string $text Login logo alt / title text.
 */
function fwp_headless_app_login_header_text( $text ) {
	if ( ! fwp_headless_app_should_customize_login() ) {
		return $text;
	}

	$settings = fwp_headless_app_get_login_brand_settings();
	$brand    = isset( $settings['logo'] ) ? trim( (string) $settings['logo'] ) : '';

	return '' !== $brand ? $brand : get_bloginfo( 'name' );
}

/**
 * Login styles — custom logo + mobile-friendly + hide leftover footer chrome.
 */
function fwp_headless_app_login_enqueue_scripts() {
	if ( ! fwp_headless_app_should_customize_login() ) {
		return;
	}

	$settings = fwp_headless_app_get_login_brand_settings();
	$logo_url = isset( $settings['logo_url'] ) ? (string) $settings['logo_url'] : '';

	$css = '
		.privacy-policy-page-link,
		.language-switcher { display: none !important; }
		#login { padding-top: 5vh; }
		#login form { margin-bottom: 16px; }
		#login .button-primary { width: 100%; min-height: 44px; font-size: 16px; }
		#login input[type="text"],
		#login input[type="password"] { font-size: 16px; min-height: 44px; }
	';

	if ( '' !== $logo_url ) {
		$css .= sprintf(
			'#login h1 a {
				background-image: url(%s);
				background-size: contain;
				background-repeat: no-repeat;
				background-position: center bottom;
				width: 100%%;
				max-width: 280px;
				height: 72px;
				margin: 0 auto 24px;
			}',
			esc_url( $logo_url )
		);
	}

	wp_add_inline_style( 'login', $css );
}

add_action( 'login_init', 'fwp_headless_app_login_init' );
add_filter( 'login_display_language_dropdown', 'fwp_headless_app_hide_login_language_dropdown' );
add_filter( 'login_headerurl', 'fwp_headless_app_login_header_url' );
add_filter( 'login_headertext', 'fwp_headless_app_login_header_text' );
add_action( 'login_enqueue_scripts', 'fwp_headless_app_login_enqueue_scripts' );
