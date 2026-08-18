<?php
/**
 * Hero slider Gutenberg block.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<int, array<string, mixed>>
 */
function fwp_headless_app_hero_slider_default_slides() {
	return array(
		array(
			'id'             => 'budivnytstvo',
			'tag'            => 'Будівництво',
			'title'          => "Будинки\nпід ключ",
			'subtitle'       => 'Зводимо приватні будинки 1–1.5 поверху з нуля — від закладки фундаменту до фінального оздоблення. Якість, терміни, гарантія.',
			'cta'            => 'Дізнатись більше',
			'href'           => '/budivnytstvo',
			'imageUrl'       => 'https://media.base44.com/images/public/6a1311bd1062b12420e3c449/83ab24b3a_1779640495370728.jpg',
			'imageId'        => 0,
			'imagePosition'  => 'center',
		),
		array(
			'id'             => 'fasad',
			'tag'            => 'Фасадні роботи',
			'title'          => "Утеплення та\nоздоблення фасадів",
			'subtitle'       => 'Сучасні фасади з утепленням, облицюванням камінням та штукатуркою для збереження тепла та естетики',
			'cta'            => 'Фасадні роботи',
			'href'           => '/fasad',
			'imageUrl'       => 'https://media.base44.com/images/public/6a1311bd1062b12420e3c449/183fa433a_1779640019874869.jpg',
			'imageId'        => 0,
			'imagePosition'  => 'center 30%',
		),
		array(
			'id'             => 'remont',
			'tag'            => 'Ремонт',
			'title'          => "Ремонт\nта реконструкція",
			'subtitle'       => 'Капітальний ремонт квартир і приміщень',
			'cta'            => 'Ремонтні роботи',
			'href'           => '/remont',
			'imageUrl'       => 'https://media.base44.com/images/public/6a1311bd1062b12420e3c449/e95b94e13_generated_image.png',
			'imageId'        => 0,
			'imagePosition'  => 'center',
		),
	);
}

/**
 * Allow limited HTML in hero titles: newlines, <br>, <span>.
 *
 * @param mixed $raw Raw title.
 * @return string
 */
function fwp_headless_app_sanitize_hero_title( $raw ) {
	$title = wp_kses(
		(string) $raw,
		array(
			'br'   => array(),
			'span' => array(
				'class' => true,
			),
		)
	);
	// Normalize Windows newlines inside allowed markup.
	$title = str_replace( array( "\r\n", "\r" ), "\n", $title );
	return trim( $title );
}

/**
 * @param array<int, mixed>|null $slides Raw slides.
 * @return array<int, array<string, mixed>>
 */
function fwp_headless_app_sanitize_hero_slider_slides( $slides ) {
	$output = array();
	if ( ! is_array( $slides ) ) {
		return $output;
	}

	foreach ( $slides as $slide ) {
		if ( ! is_array( $slide ) ) {
			continue;
		}

		$title = fwp_headless_app_sanitize_hero_title( $slide['title'] ?? '' );
		if ( '' === $title ) {
			continue;
		}

		$href = trim( (string) ( $slide['href'] ?? '' ) );
		$id   = sanitize_key( $slide['id'] ?? '' );
		if ( '' === $id ) {
			$id = sanitize_title( wp_strip_all_tags( str_replace( "\n", ' ', $title ) ) );
		}

		$image_id  = isset( $slide['imageId'] ) ? (int) $slide['imageId'] : 0;
		$image_url = esc_url_raw( $slide['imageUrl'] ?? '' );
		if ( $image_id > 0 ) {
			$attachment_url = wp_get_attachment_url( $image_id );
			if ( $attachment_url ) {
				$image_url = $attachment_url;
			}
		}

		if ( '' !== $href ) {
			$href = str_starts_with( $href, '/' ) ? sanitize_text_field( $href ) : esc_url_raw( $href );
		}

		$output[] = array(
			'id'             => $id,
			'tag'            => sanitize_text_field( $slide['tag'] ?? '' ),
			'title'          => $title,
			'subtitle'       => sanitize_textarea_field( $slide['subtitle'] ?? '' ),
			'cta'            => sanitize_text_field( $slide['cta'] ?? '' ),
			'href'           => $href,
			'image_url'      => $image_url,
			'image_id'       => $image_id,
			'image_position' => sanitize_text_field( $slide['imagePosition'] ?? 'center' ),
		);
	}

	return $output;
}

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_hero_slider_from_attrs( $attrs ) {
	$defaults = array(
		'height_mode'            => 'full',
		'min_height'             => 640,
		'slides'                 => fwp_headless_app_hero_slider_default_slides(),
		'secondary_button_label' => 'Консультація',
		'secondary_button_href'  => '/contacts',
	);

	$attrs = is_array( $attrs ) ? $attrs : array();

	$height_mode = sanitize_key( $attrs['heightMode'] ?? $defaults['height_mode'] );
	if ( ! in_array( $height_mode, array( 'full', 'custom' ), true ) ) {
		$height_mode = 'full';
	}

	$min_height = isset( $attrs['minHeight'] ) ? (int) $attrs['minHeight'] : $defaults['min_height'];
	$min_height = max( 320, min( 1200, $min_height ) );

	$slides = ! empty( $attrs['slides'] ) && is_array( $attrs['slides'] )
		? fwp_headless_app_sanitize_hero_slider_slides( $attrs['slides'] )
		: fwp_headless_app_sanitize_hero_slider_slides( $defaults['slides'] );

	if ( empty( $slides ) ) {
		$slides = fwp_headless_app_sanitize_hero_slider_slides( $defaults['slides'] );
	}

	return fwp_headless_app_section_with_breadcrumb(
		array(
			'type'                   => 'hero_slider',
			'height_mode'            => $height_mode,
			'min_height'             => $min_height,
			'slides'                 => $slides,
			'secondary_button_label' => array_key_exists( 'secondaryButtonLabel', $attrs )
				? sanitize_text_field( (string) $attrs['secondaryButtonLabel'] )
				: $defaults['secondary_button_label'],
			'secondary_button_href'  => array_key_exists( 'secondaryButtonHref', $attrs )
				? sanitize_text_field( (string) $attrs['secondaryButtonHref'] )
				: $defaults['secondary_button_href'],
		),
		$attrs
	);
}

/**
 * @param array<string, mixed> $section Seed section.
 * @return string
 */
function fwp_headless_app_hero_slider_block_markup( $section = array() ) {
	$defaults = fwp_headless_app_hero_slider_from_attrs( array() );
	$slides   = $defaults['slides'];

	if ( ! empty( $section['slides'] ) && is_array( $section['slides'] ) ) {
		$mapped = array();
		foreach ( $section['slides'] as $slide ) {
			$mapped[] = array(
				'id'            => $slide['id'] ?? '',
				'tag'           => $slide['tag'] ?? '',
				'title'         => $slide['title'] ?? '',
				'subtitle'      => $slide['subtitle'] ?? '',
				'cta'           => $slide['cta'] ?? '',
				'href'          => $slide['href'] ?? '',
				'imageUrl'      => $slide['image_url'] ?? ( $slide['imageUrl'] ?? '' ),
				'imageId'       => (int) ( $slide['image_id'] ?? ( $slide['imageId'] ?? 0 ) ),
				'imagePosition' => $slide['image_position'] ?? ( $slide['imagePosition'] ?? 'center' ),
			);
		}
		$slides = $mapped;
	}

	$attrs = array(
		'heightMode'           => $section['height_mode'] ?? $defaults['height_mode'],
		'minHeight'            => $section['min_height'] ?? $defaults['min_height'],
		'slides'               => $slides,
		'secondaryButtonLabel' => $section['secondary_button_label'] ?? $defaults['secondary_button_label'],
		'secondaryButtonHref'  => $section['secondary_button_href'] ?? $defaults['secondary_button_href'],
	);

	return '<!-- wp:grv/hero-slider ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Register block type.
 */
function fwp_headless_app_register_hero_slider_block() {
	if ( ! function_exists( 'register_block_type' ) || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_block_type(
		'grv/hero-slider',
		array(
			'api_version' => 3,
			'attributes'  => array_merge(
				array(
				'heightMode'           => array(
					'type'    => 'string',
					'default' => 'full',
				),
				'minHeight'            => array(
					'type'    => 'number',
					'default' => 640,
				),
				'slides'               => array(
					'type'    => 'array',
					'default' => array(),
				),
				'secondaryButtonLabel' => array(
					'type'    => 'string',
					'default' => 'Консультація',
				),
				'secondaryButtonHref'  => array(
					'type'    => 'string',
					'default' => '/contacts',
				),
				),
				fwp_headless_app_breadcrumb_block_attributes()
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_hero_slider_block' );

/**
 * Enqueue editor script.
 */
function fwp_headless_app_enqueue_hero_slider_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/hero-slider/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_media();
	fwp_headless_app_enqueue_breadcrumb_block_editor();
	$style_path = $plugin_root . '/blocks/hero-slider/editor.css';
	if ( is_readable( $style_path ) ) {
		wp_enqueue_style(
			'grv-hero-slider-editor',
			plugins_url( 'blocks/hero-slider/editor.css', $plugin_root . '/4wp-headless-app.php' ),
			array(),
			(string) filemtime( $style_path )
		);
	}
	wp_enqueue_script(
		'grv-hero-slider-editor',
		plugins_url( 'blocks/hero-slider/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'grv-breadcrumb-inspector' ),
		(string) filemtime( $script_path ),
		true
	);

	wp_localize_script(
		'grv-hero-slider-editor',
		'grvHeroSliderData',
		array(
			'defaults' => array(
				'heightMode'           => 'full',
				'minHeight'            => 640,
				'slides'               => fwp_headless_app_hero_slider_default_slides(),
				'secondaryButtonLabel' => 'Консультація',
				'secondaryButtonHref'  => '/contacts',
			),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_hero_slider_block_editor_assets' );
