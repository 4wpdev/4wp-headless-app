<?php
/**
 * Services section Gutenberg block.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string, string>
 */
function fwp_headless_app_get_services_icon_choices() {
	return array(
		'home'       => 'Home',
		'building2'  => 'Building',
		'hammer'     => 'Hammer',
		'wrench'     => 'Wrench',
		'paintbrush' => 'Paintbrush',
		'layers'     => 'Layers',
		'brick'      => 'Brick',
	);
}

/**
 * @return array<int, array<string, mixed>>
 */
function fwp_headless_app_services_section_default_items() {
	return array(
		array(
			'icon'        => 'home',
			'title'       => 'Будівництво будинків',
			'imageUrl'    => 'https://media.base44.com/images/public/6a1311bd1062b12420e3c449/24f055dbd_1779640019874869.jpg',
			'imageId'     => 0,
			'description' => '<ul><li>Будівництво будинків (фундамент, коробка, дах, фасад)</li><li>Будівництво приватних будинків з гарантією якості</li><li>Покрівельні роботи будь-якої складності</li><li>Монтаж та ремонт дахів</li></ul>',
			'link'        => '/budivnytstvo',
		),
		array(
			'icon'        => 'building2',
			'title'       => 'Фасадні роботи',
			'imageUrl'    => 'https://media.base44.com/images/public/6a1311bd1062b12420e3c449/023cb03f4_1779640493773383.jpg',
			'imageId'     => 0,
			'description' => '<ul><li>Утеплення фасадів (пінопласт, мінеральна вата, PIR-плити)</li><li>Декоративна штукатурка (короїд, барашок)</li><li>Клінкерна плитка та натуральний камінь</li><li>Монтаж водостоків та підшивка софітів</li></ul>',
			'link'        => '/fasad',
		),
		array(
			'icon'        => 'hammer',
			'title'       => 'Ремонт та реконструкція',
			'imageUrl'    => 'https://media.base44.com/images/public/6a1311bd1062b12420e3c449/9ece18ba7_1779639888200139.jpg',
			'imageId'     => 0,
			'description' => '<ul><li>Капітальний ремонт приміщень</li><li>Реконструкція будинків</li><li>Внутрішні ремонтні роботи</li><li>Чистові роботи</li></ul>',
			'link'        => '/remont',
		),
	);
}

/**
 * @param string $html Raw HTML.
 * @return string
 */
function fwp_headless_app_sanitize_services_description( $html ) {
	return wp_kses(
		(string) $html,
		array(
			'ul'     => array(),
			'ol'     => array(),
			'li'     => array(),
			'b'      => array(),
			'strong' => array(),
			'i'      => array(),
			'em'     => array(),
			'br'     => array(),
			'p'      => array(),
		)
	);
}

/**
 * @param array<int, mixed>|null $items Raw items.
 * @return array<int, array<string, mixed>>
 */
function fwp_headless_app_sanitize_services_section_items( $items ) {
	$choices = fwp_headless_app_get_services_icon_choices();
	$output  = array();

	if ( ! is_array( $items ) ) {
		return $output;
	}

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$icon = sanitize_key( $item['icon'] ?? '' );
		if ( ! isset( $choices[ $icon ] ) ) {
			$icon = 'home';
		}

		$title = sanitize_text_field( $item['title'] ?? '' );
		$link  = trim( (string) ( $item['link'] ?? '' ) );
		if ( '' === $title || '' === $link ) {
			continue;
		}

		$image_id  = isset( $item['imageId'] ) ? (int) $item['imageId'] : 0;
		$image_url = esc_url_raw( $item['imageUrl'] ?? '' );
		if ( $image_id > 0 ) {
			$attachment_url = wp_get_attachment_url( $image_id );
			if ( $attachment_url ) {
				$image_url = $attachment_url;
			}
		}

		$output[] = array(
			'icon'        => $icon,
			'title'       => $title,
			'image_url'   => $image_url,
			'image_id'    => $image_id,
			'description' => fwp_headless_app_sanitize_services_description( $item['description'] ?? '' ),
			'link'        => str_starts_with( $link, '/' ) ? sanitize_text_field( $link ) : esc_url_raw( $link ),
		);
	}

	return $output;
}

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_services_section_from_attrs( $attrs ) {
	$defaults = array(
		'sub_title' => 'Що ми робимо',
		'title'     => 'Перелік послуг',
		'items'     => fwp_headless_app_services_section_default_items(),
	);

	$attrs = is_array( $attrs ) ? $attrs : array();
	$items = ! empty( $attrs['items'] ) && is_array( $attrs['items'] )
		? fwp_headless_app_sanitize_services_section_items( $attrs['items'] )
		: fwp_headless_app_sanitize_services_section_items( $defaults['items'] );

	if ( empty( $items ) ) {
		$items = fwp_headless_app_sanitize_services_section_items( $defaults['items'] );
	}

	return array(
		'type'      => 'services_section',
		'sub_title' => ! empty( $attrs['subTitle'] ) ? sanitize_text_field( $attrs['subTitle'] ) : $defaults['sub_title'],
		'title'     => ! empty( $attrs['title'] ) ? sanitize_text_field( $attrs['title'] ) : $defaults['title'],
		'items'     => $items,
	);
}

/**
 * @param array<string, mixed> $section Seed section.
 * @return string
 */
function fwp_headless_app_services_section_block_markup( $section = array() ) {
	$defaults = fwp_headless_app_services_section_from_attrs( array() );
	$items    = $defaults['items'];

	if ( ! empty( $section['items'] ) && is_array( $section['items'] ) ) {
		$mapped = array();
		foreach ( $section['items'] as $item ) {
			$mapped[] = array(
				'icon'        => $item['icon'] ?? 'home',
				'title'       => $item['title'] ?? '',
				'imageUrl'    => $item['image_url'] ?? ( $item['imageUrl'] ?? '' ),
				'imageId'     => (int) ( $item['image_id'] ?? ( $item['imageId'] ?? 0 ) ),
				'description' => $item['description'] ?? '',
				'link'        => $item['link'] ?? '',
			);
		}
		$items = $mapped;
	}

	$attrs = array(
		'subTitle' => $section['sub_title'] ?? $defaults['sub_title'],
		'title'    => $section['title'] ?? $defaults['title'],
		'items'    => $items,
	);

	return '<!-- wp:grv/services-section ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Register block type.
 */
function fwp_headless_app_register_services_section_block() {
	if ( ! function_exists( 'register_block_type' ) || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_block_type(
		'grv/services-section',
		array(
			'api_version' => 3,
			'attributes'  => array(
				'subTitle' => array(
					'type'    => 'string',
					'default' => 'Що ми робимо',
				),
				'title'    => array(
					'type'    => 'string',
					'default' => 'Перелік послуг',
				),
				'items'    => array(
					'type'    => 'array',
					'default' => array(),
				),
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_services_section_block' );

/**
 * Enqueue editor script.
 */
function fwp_headless_app_enqueue_services_section_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/services-section/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_script(
		'grv-services-section-editor',
		plugins_url( 'blocks/services-section/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-rich-text' ),
		(string) filemtime( $script_path ),
		true
	);

	$icons = array();
	foreach ( fwp_headless_app_get_services_icon_choices() as $slug => $label ) {
		$icons[] = array( 'slug' => $slug, 'label' => $label );
	}

	wp_localize_script(
		'grv-services-section-editor',
		'grvServicesSectionData',
		array(
			'icons'    => $icons,
			'defaults' => array(
				'subTitle' => 'Що ми робимо',
				'title'    => 'Перелік послуг',
				'items'    => fwp_headless_app_services_section_default_items(),
			),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_services_section_block_editor_assets' );
