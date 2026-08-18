<?php
/**
 * Contacts section Gutenberg block (info + callback form).
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_contacts_section_from_attrs( $attrs ) {
	$attrs = is_array( $attrs ) ? $attrs : array();

	return array(
		'type'           => 'contacts_section',
		'eyebrow'        => ! empty( $attrs['eyebrow'] )
			? sanitize_text_field( $attrs['eyebrow'] )
			: 'Контакти',
		'title'          => ! empty( $attrs['title'] )
			? sanitize_text_field( $attrs['title'] )
			: 'Напишіть або зателефонуйте',
		'social_label'   => ! empty( $attrs['socialLabel'] )
			? sanitize_text_field( $attrs['socialLabel'] )
			: 'Написати напряму',
		'form_title'     => ! empty( $attrs['formTitle'] )
			? sanitize_text_field( $attrs['formTitle'] )
			: 'Замовити дзвінок',
		'form_subtitle'  => ! empty( $attrs['formSubtitle'] )
			? sanitize_text_field( $attrs['formSubtitle'] )
			: 'Залиште номер — ми передзвонимо протягом 30 хвилин',
		'button_label'   => ! empty( $attrs['buttonLabel'] )
			? sanitize_text_field( $attrs['buttonLabel'] )
			: 'Перетелефонуйте мені',
		'form_note'      => ! empty( $attrs['formNote'] )
			? sanitize_text_field( $attrs['formNote'] )
			: 'Передзвонюємо протягом 30 хвилин у робочий час',
		'show_messengers' => ! isset( $attrs['showMessengers'] ) || ! empty( $attrs['showMessengers'] ),
	);
}

/**
 * @param array<string, mixed> $section Seed section.
 * @return string
 */
function fwp_headless_app_contacts_section_block_markup( $section = array() ) {
	$defaults = fwp_headless_app_contacts_section_from_attrs( array() );
	$attrs    = array(
		'eyebrow'         => $section['eyebrow'] ?? $defaults['eyebrow'],
		'title'           => $section['title'] ?? $defaults['title'],
		'socialLabel'     => $section['social_label'] ?? $defaults['social_label'],
		'formTitle'       => $section['form_title'] ?? $defaults['form_title'],
		'formSubtitle'    => $section['form_subtitle'] ?? $defaults['form_subtitle'],
		'buttonLabel'     => $section['button_label'] ?? $defaults['button_label'],
		'formNote'        => $section['form_note'] ?? $defaults['form_note'],
		'showMessengers'  => array_key_exists( 'show_messengers', $section )
			? (bool) $section['show_messengers']
			: true,
	);

	return '<!-- wp:grv/contacts-section ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Register block type.
 */
function fwp_headless_app_register_contacts_section_block() {
	if ( ! function_exists( 'register_block_type' ) || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_block_type(
		'grv/contacts-section',
		array(
			'api_version' => 3,
			'attributes'  => array(
				'eyebrow'        => array(
					'type'    => 'string',
					'default' => 'Контакти',
				),
				'title'          => array(
					'type'    => 'string',
					'default' => 'Напишіть або зателефонуйте',
				),
				'socialLabel'    => array(
					'type'    => 'string',
					'default' => 'Написати напряму',
				),
				'formTitle'      => array(
					'type'    => 'string',
					'default' => 'Замовити дзвінок',
				),
				'formSubtitle'   => array(
					'type'    => 'string',
					'default' => 'Залиште номер — ми передзвонимо протягом 30 хвилин',
				),
				'buttonLabel'    => array(
					'type'    => 'string',
					'default' => 'Перетелефонуйте мені',
				),
				'formNote'       => array(
					'type'    => 'string',
					'default' => 'Передзвонюємо протягом 30 хвилин у робочий час',
				),
				'showMessengers' => array(
					'type'    => 'boolean',
					'default' => true,
				),
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_contacts_section_block' );

/**
 * Enqueue editor script.
 */
function fwp_headless_app_enqueue_contacts_section_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/contacts-section/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'grv-contacts-section-editor',
		plugins_url( 'blocks/contacts-section/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		(string) filemtime( $script_path ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_contacts_section_block_editor_assets' );
