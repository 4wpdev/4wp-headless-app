<?php
/**
 * Linked section Gutenberg block (text + video/gallery).
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string, mixed>
 */
function fwp_headless_app_linked_section_preset_build() {
	return array(
		'sub_title'              => 'Будівництво',
		'align'                  => 'left',
		'media_type'             => 'video',
		'video_url'              => 'https://media.base44.com/videos/public/6a1311bd1062b12420e3c449/db7dd2762_fasad.mp4',
		'gallery'                => array(),
		'heading_line1'          => 'Будівництво',
		'heading_highlight'      => 'будинків під ключ',
		'intro'                  => "Від закладки фундаменту до фінального оздоблення — ведемо ваш об'єкт на кожному етапі. Якість матеріалів, чіткі терміни, прозора ціна.",
		'bullets'                => array(
			'Фундамент, коробка, дах — під ключ',
			'Покрівельні роботи будь-якої складності',
			'Утеплення та фасадне оздоблення',
			"Внутрішнє оздоблення та здача об'єкту",
		),
		'show_stats'             => true,
		'stats'                  => array(
			array( 'value' => '30+', 'label' => 'Будинків' ),
			array( 'value' => '10+', 'label' => 'Років' ),
			array( 'value' => '99%', 'label' => 'Задоволених' ),
		),
		'primary_button_label'   => 'Дізнатись більше',
		'primary_button_href'    => '/budivnytstvo',
		'secondary_button_label' => 'Консультація',
		'secondary_button_href'  => '/contacts',
	);
}

/**
 * @return array<string, mixed>
 */
function fwp_headless_app_linked_section_preset_facade() {
	return array(
		'sub_title'              => 'Фасад',
		'align'                  => 'right',
		'media_type'             => 'gallery',
		'video_url'              => '',
		'gallery'                => array(
			'https://media.base44.com/images/public/6a1311bd1062b12420e3c449/24f055dbd_1779640019874869.jpg',
			'https://media.base44.com/images/public/6a1311bd1062b12420e3c449/023cb03f4_1779640493773383.jpg',
			'https://media.base44.com/images/public/6a1311bd1062b12420e3c449/9ece18ba7_1779639888200139.jpg',
			'https://media.base44.com/images/public/6a1311bd1062b12420e3c449/8802deb7b_1779639871749925.jpg',
		),
		'heading_line1'          => 'Фасадні роботи',
		'heading_highlight'      => '',
		'intro'                  => 'Виконуємо повний цикл фасадних робіт — від утеплення до фінального оздоблення. Використовуємо сучасні матеріали провідних виробників з гарантією якості та довговічності.',
		'bullets'                => array(
			'Утеплення фасадів (пінопласт, мінеральна вата, PIR-плити)',
			'Декоративна штукатурка (короїд, барашок)',
			'Клінкерна плитка та натуральний камінь',
			'Монтаж водостоків та підшивка софітів',
			'Фарбування та захисне покриття фасадів',
		),
		'show_stats'             => false,
		'stats'                  => array(),
		'primary_button_label'   => 'Детальніше',
		'primary_button_href'    => '/fasad',
		'secondary_button_label' => 'Консультація',
		'secondary_button_href'  => '/contacts',
	);
}

/**
 * @return array<string, mixed>
 */
function fwp_headless_app_linked_section_preset_repair() {
	return array(
		'sub_title'              => 'Ремонт',
		'align'                  => 'left',
		'media_type'             => 'video',
		'video_url'              => 'https://media.base44.com/videos/public/6a1311bd1062b12420e3c449/80b395520_inhouse.mp4',
		'gallery'                => array(),
		'heading_line1'          => 'Ремонт та',
		'heading_highlight'      => 'реконструкція',
		'intro'                  => 'Виконуємо капітальний ремонт, реконструкцію та добудову приміщень. Від планування до завершення — беремо на себе всю відповідальність.',
		'bullets'                => array(
			'Капітальний ремонт приміщень',
			'Реконструкція будинків та добудова',
			'Внутрішні ремонтні роботи (штукатурка, стяжка)',
			'Чистові роботи (фарбування, плитка, ламінат)',
		),
		'show_stats'             => true,
		'stats'                  => array(
			array( 'value' => '50+', 'label' => 'Проектів' ),
			array( 'value' => '10+', 'label' => 'Років' ),
			array( 'value' => '75%', 'label' => 'Повторних замовлень' ),
		),
		'primary_button_label'   => 'Дізнатись більше',
		'primary_button_href'    => '/remont',
		'secondary_button_label' => 'Консультація',
		'secondary_button_href'  => '/contacts',
	);
}

/**
 * @param array<int, mixed>|null $items String list.
 * @return array<int, string>
 */
function fwp_headless_app_sanitize_linked_section_strings( $items ) {
	$output = array();
	if ( ! is_array( $items ) ) {
		return $output;
	}
	foreach ( $items as $item ) {
		$text = sanitize_text_field( is_array( $item ) ? ( $item['text'] ?? $item['label'] ?? '' ) : (string) $item );
		if ( '' !== $text ) {
			$output[] = $text;
		}
	}
	return $output;
}

/**
 * @param array<int, mixed>|null $stats Raw stats.
 * @return array<int, array<string, string>>
 */
function fwp_headless_app_sanitize_linked_section_stats( $stats ) {
	$output = array();
	if ( ! is_array( $stats ) ) {
		return $output;
	}
	foreach ( $stats as $stat ) {
		if ( ! is_array( $stat ) ) {
			continue;
		}
		$value = sanitize_text_field( $stat['value'] ?? '' );
		$label = sanitize_text_field( $stat['label'] ?? '' );
		if ( '' === $value || '' === $label ) {
			continue;
		}
		$output[] = array(
			'value' => $value,
			'label' => $label,
		);
	}
	return $output;
}

/**
 * @param array<int, mixed>|null $gallery Raw gallery rows.
 * @return array<int, string>
 */
function fwp_headless_app_sanitize_linked_section_gallery( $gallery ) {
	$output = array();
	if ( ! is_array( $gallery ) ) {
		return $output;
	}
	foreach ( $gallery as $item ) {
		if ( is_string( $item ) ) {
			$url = esc_url_raw( $item );
		} elseif ( is_array( $item ) ) {
			$image_id = isset( $item['imageId'] ) ? (int) $item['imageId'] : 0;
			$url      = esc_url_raw( $item['imageUrl'] ?? '' );
			if ( $image_id > 0 ) {
				$attachment_url = wp_get_attachment_url( $image_id );
				if ( $attachment_url ) {
					$url = $attachment_url;
				}
			}
		} else {
			continue;
		}
		if ( $url ) {
			$output[] = $url;
		}
	}
	return $output;
}

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_linked_section_from_attrs( $attrs ) {
	$defaults = fwp_headless_app_linked_section_preset_build();
	$attrs    = is_array( $attrs ) ? $attrs : array();

	$align      = sanitize_key( $attrs['align'] ?? $defaults['align'] );
	$align      = in_array( $align, array( 'left', 'right' ), true ) ? $align : 'left';
	$media_type = sanitize_key( $attrs['mediaType'] ?? $defaults['media_type'] );
	$media_type = in_array( $media_type, array( 'video', 'gallery' ), true ) ? $media_type : 'video';

	$gallery = fwp_headless_app_sanitize_linked_section_gallery( $attrs['gallery'] ?? array() );
	if ( empty( $gallery ) && ! empty( $defaults['gallery'] ) ) {
		$gallery = fwp_headless_app_sanitize_linked_section_gallery( $defaults['gallery'] );
	}

	$bullets = fwp_headless_app_sanitize_linked_section_strings( $attrs['bullets'] ?? array() );
	if ( empty( $bullets ) ) {
		$bullets = fwp_headless_app_sanitize_linked_section_strings( $defaults['bullets'] );
	}

	/*
	 * Gutenberg often omits boolean `false` from saved block JSON when it matches
	 * the registered default (showStats default = false). Do NOT fall back to
	 * preset show_stats=true — that made stats always appear (e.g. Facade).
	 */
	$show_stats = array_key_exists( 'showStats', $attrs )
		? (bool) $attrs['showStats']
		: false;

	$stats = fwp_headless_app_sanitize_linked_section_stats( $attrs['stats'] ?? array() );
	if ( $show_stats && empty( $stats ) ) {
		$stats = fwp_headless_app_sanitize_linked_section_stats( $defaults['stats'] );
	}

	$heading_level = sanitize_key( $attrs['headingLevel'] ?? 'h2' );
	$heading_level = in_array( $heading_level, array( 'h2', 'h3' ), true ) ? $heading_level : 'h2';

	/*
	 * Button labels: if attribute is present (even empty), respect it so cleared
	 * buttons stay hidden on the front. Missing key → preset defaults (old blocks).
	 */
	$primary_label = array_key_exists( 'primaryButtonLabel', $attrs )
		? sanitize_text_field( (string) $attrs['primaryButtonLabel'] )
		: (string) ( $defaults['primary_button_label'] ?? '' );
	$primary_href  = array_key_exists( 'primaryButtonHref', $attrs )
		? sanitize_text_field( (string) $attrs['primaryButtonHref'] )
		: (string) ( $defaults['primary_button_href'] ?? '/' );
	$secondary_label = array_key_exists( 'secondaryButtonLabel', $attrs )
		? sanitize_text_field( (string) $attrs['secondaryButtonLabel'] )
		: (string) ( $defaults['secondary_button_label'] ?? '' );
	$secondary_href  = array_key_exists( 'secondaryButtonHref', $attrs )
		? sanitize_text_field( (string) $attrs['secondaryButtonHref'] )
		: (string) ( $defaults['secondary_button_href'] ?? '/contacts' );

	return array(
		'type'                   => 'linked_section',
		'sub_title'              => ! empty( $attrs['subTitle'] ) ? sanitize_text_field( $attrs['subTitle'] ) : $defaults['sub_title'],
		'align'                  => $align,
		'media_type'             => $media_type,
		'video_url'              => ! empty( $attrs['videoUrl'] ) ? esc_url_raw( $attrs['videoUrl'] ) : $defaults['video_url'],
		'gallery'                => $gallery,
		'heading_line1'          => ! empty( $attrs['headingLine1'] ) ? sanitize_text_field( $attrs['headingLine1'] ) : $defaults['heading_line1'],
		'heading_highlight'      => array_key_exists( 'headingHighlight', $attrs )
			? sanitize_text_field( (string) $attrs['headingHighlight'] )
			: $defaults['heading_highlight'],
		'heading_level'          => $heading_level,
		'intro'                  => ! empty( $attrs['intro'] ) ? sanitize_textarea_field( $attrs['intro'] ) : $defaults['intro'],
		'bullets'                => $bullets,
		'show_stats'             => $show_stats,
		'stats'                  => $stats,
		'primary_button_label'   => $primary_label,
		'primary_button_href'    => $primary_href,
		'secondary_button_label' => $secondary_label,
		'secondary_button_href'  => $secondary_href,
	);
}

/**
 * Map seed preset slug to defaults.
 *
 * @param array<string, mixed> $section Seed section.
 * @return array<string, mixed>
 */
function fwp_headless_app_linked_section_seed_defaults( $section ) {
	$preset = sanitize_key( $section['preset'] ?? '' );
	if ( 'facade' === $preset ) {
		return fwp_headless_app_linked_section_preset_facade();
	}
	if ( 'repair' === $preset ) {
		return fwp_headless_app_linked_section_preset_repair();
	}
	if ( 'build' === $preset ) {
		return fwp_headless_app_linked_section_preset_build();
	}
	return fwp_headless_app_linked_section_preset_build();
}

/**
 * @param array<string, mixed> $section Seed section.
 * @return string
 */
function fwp_headless_app_linked_section_block_markup( $section = array() ) {
	$defaults = fwp_headless_app_linked_section_seed_defaults( $section );
	$mapped   = fwp_headless_app_linked_section_from_attrs(
		array(
			'subTitle'              => $section['sub_title'] ?? $defaults['sub_title'],
			'align'                 => $section['align'] ?? $defaults['align'],
			'mediaType'             => $section['media_type'] ?? $defaults['media_type'],
			'videoUrl'              => $section['video_url'] ?? $defaults['video_url'],
			'gallery'               => ! empty( $section['gallery'] ) ? $section['gallery'] : $defaults['gallery'],
			'headingLine1'          => $section['heading_line1'] ?? $defaults['heading_line1'],
			'headingHighlight'      => $section['heading_highlight'] ?? $defaults['heading_highlight'],
			'headingLevel'          => $section['heading_level'] ?? 'h2',
			'intro'                 => $section['intro'] ?? $defaults['intro'],
			'bullets'               => ! empty( $section['bullets'] ) ? $section['bullets'] : $defaults['bullets'],
			'showStats'             => isset( $section['show_stats'] ) ? (bool) $section['show_stats'] : $defaults['show_stats'],
			'stats'                 => ! empty( $section['stats'] ) ? $section['stats'] : $defaults['stats'],
			'primaryButtonLabel'    => $section['primary_button_label'] ?? $defaults['primary_button_label'],
			'primaryButtonHref'     => $section['primary_button_href'] ?? $defaults['primary_button_href'],
			'secondaryButtonLabel'  => $section['secondary_button_label'] ?? $defaults['secondary_button_label'],
			'secondaryButtonHref'   => $section['secondary_button_href'] ?? $defaults['secondary_button_href'],
		)
	);

	$attrs = array(
		'subTitle'             => $mapped['sub_title'],
		'align'                => $mapped['align'],
		'mediaType'            => $mapped['media_type'],
		'videoUrl'             => $mapped['video_url'],
		'gallery'              => array_map(
			function ( $url ) {
				return array( 'imageUrl' => $url, 'imageId' => 0 );
			},
			$mapped['gallery']
		),
		'headingLine1'         => $mapped['heading_line1'],
		'headingHighlight'     => $mapped['heading_highlight'],
		'headingLevel'         => $mapped['heading_level'],
		'intro'                => $mapped['intro'],
		'bullets'              => $mapped['bullets'],
		'showStats'            => $mapped['show_stats'],
		'stats'                => $mapped['stats'],
		'primaryButtonLabel'   => $mapped['primary_button_label'],
		'primaryButtonHref'    => $mapped['primary_button_href'],
		'secondaryButtonLabel' => $mapped['secondary_button_label'],
		'secondaryButtonHref'  => $mapped['secondary_button_href'],
	);

	return '<!-- wp:grv/linked-section ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Register block type.
 */
function fwp_headless_app_register_linked_section_block() {
	if ( ! function_exists( 'register_block_type' ) || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_block_type(
		'grv/linked-section',
		array(
			'api_version' => 3,
			'attributes'  => array(
				'subTitle'             => array( 'type' => 'string', 'default' => 'Будівництво' ),
				'align'                => array( 'type' => 'string', 'default' => 'left' ),
				'mediaType'            => array( 'type' => 'string', 'default' => 'video' ),
				'videoUrl'             => array( 'type' => 'string', 'default' => '' ),
				'videoId'              => array( 'type' => 'number', 'default' => 0 ),
				'gallery'              => array( 'type' => 'array', 'default' => array() ),
				'headingLine1'         => array( 'type' => 'string', 'default' => '' ),
				'headingHighlight'     => array( 'type' => 'string', 'default' => '' ),
				'headingLevel'         => array( 'type' => 'string', 'default' => 'h2' ),
				'intro'                => array( 'type' => 'string', 'default' => '' ),
				'bullets'              => array( 'type' => 'array', 'default' => array() ),
				'showStats'            => array( 'type' => 'boolean', 'default' => false ),
				'stats'                => array( 'type' => 'array', 'default' => array() ),
				'primaryButtonLabel'   => array( 'type' => 'string', 'default' => 'Дізнатись більше' ),
				'primaryButtonHref'    => array( 'type' => 'string', 'default' => '/' ),
				'secondaryButtonLabel' => array( 'type' => 'string', 'default' => 'Консультація' ),
				'secondaryButtonHref'  => array( 'type' => 'string', 'default' => '/contacts' ),
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_linked_section_block' );

/**
 * Enqueue editor script.
 */
function fwp_headless_app_enqueue_linked_section_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/linked-section/editor.js';
	$style_path  = $plugin_root . '/blocks/linked-section/editor.css';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	if ( is_readable( $style_path ) ) {
		wp_enqueue_style(
			'grv-linked-section-editor',
			plugins_url( 'blocks/linked-section/editor.css', $plugin_root . '/4wp-headless-app.php' ),
			array(),
			(string) filemtime( $style_path )
		);
	}

	wp_enqueue_media();
	wp_enqueue_script(
		'grv-linked-section-editor',
		plugins_url( 'blocks/linked-section/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		(string) filemtime( $script_path ),
		true
	);

	wp_localize_script(
		'grv-linked-section-editor',
		'grvLinkedSectionData',
		array(
			'defaults' => fwp_headless_app_linked_section_preset_build(),
			'presets'  => array(
				'build'  => fwp_headless_app_linked_section_preset_build(),
				'facade' => fwp_headless_app_linked_section_preset_facade(),
				'repair' => fwp_headless_app_linked_section_preset_repair(),
			),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_linked_section_block_editor_assets' );
