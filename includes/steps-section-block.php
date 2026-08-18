<?php
/**
 * Steps / Construction stages Gutenberg block.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<int, array<string, mixed>>
 */
function fwp_headless_app_steps_section_default_steps() {
	return array(
		array(
			'label'       => 'Фундаментні роботи',
			'description' => "Фундамент — основа вашого будинку. Ми проводимо детальний аналіз ґрунту та підбираємо оптимальний тип фундаменту для кожного об'єкта.",
			'image_url'   => '',
			'image_id'    => 0,
			'items'       => array(
				'Геологічне дослідження ґрунту',
				'Стрічковий фундамент (мілкого та глибокого закладання)',
				'Плитний монолітний фундамент',
				'Пальово-ростверковий фундамент',
				'Гідроізоляція та утеплення фундаменту',
				'Дренажна система навколо будинку',
			),
		),
		array(
			'label'       => 'Зведення каркасу',
			'description' => 'Зводимо міцні та теплоефективні стіни з перевірених матеріалів. Суворий контроль якості на кожному етапі — від першого ряду кладки до армопояса.',
			'image_url'   => '',
			'image_id'    => 0,
			'items'       => array(
				'Кладка з газоблоку, цегли, керамічного блоку',
				'Монолітні перекриття та перемички',
				'Армопояс та монолітні колони',
				'Монтаж вікон та дверних коробок',
				'Внутрішні перегородки',
				'Зовнішня гідроізоляція стін',
			),
		),
		array(
			'label'       => 'Покрівельні роботи',
			'description' => 'Надійна покрівля захищає весь будинок. Виконуємо дахи будь-якої конфігурації з гарантією на 5 років.',
			'image_url'   => '',
			'image_id'    => 0,
			'items'       => array(
				"Монтаж крокв'яної системи будь-якої складності",
				'Односхилий, двосхилий, вальмовий, шатровий дах',
				"Металочерепиця, профнастил, м'яка покрівля",
				'Утеплення покрівлі мінеральною ватою',
				'Монтаж водостічної системи',
				'Підшивка карнизів та фронтонів',
			),
		),
	);
}

/**
 * @param array<int, mixed>|null $steps Raw steps.
 * @return array<int, array<string, mixed>>
 */
function fwp_headless_app_sanitize_steps_section_steps( $steps ) {
	$output = array();

	if ( ! is_array( $steps ) ) {
		return $output;
	}

	foreach ( $steps as $step ) {
		if ( ! is_array( $step ) ) {
			continue;
		}

		$label = sanitize_text_field( $step['label'] ?? '' );
		if ( $label === '' ) {
			continue;
		}

		$items_raw = $step['items'] ?? array();
		$items     = array();
		if ( is_string( $items_raw ) ) {
			$items_raw = preg_split( '/\r\n|\r|\n/', $items_raw );
		}
		if ( is_array( $items_raw ) ) {
			foreach ( $items_raw as $item ) {
				$item = sanitize_text_field( $item );
				if ( $item !== '' ) {
					$items[] = $item;
				}
			}
		}

		$image_id  = isset( $step['imageId'] ) ? (int) $step['imageId'] : (int) ( $step['image_id'] ?? 0 );
		$image_url = '';
		if ( $image_id > 0 ) {
			$url = wp_get_attachment_image_url( $image_id, 'large' );
			if ( is_string( $url ) ) {
				$image_url = $url;
			}
		}
		if ( $image_url === '' ) {
			$image_url = esc_url_raw( $step['imageUrl'] ?? ( $step['image_url'] ?? '' ) );
		}

		$output[] = array(
			'label'       => $label,
			'description' => sanitize_textarea_field( $step['description'] ?? '' ),
			'image_url'   => $image_url,
			'image_id'    => $image_id,
			'items'       => $items,
		);
	}

	return $output;
}

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_steps_section_from_attrs( $attrs ) {
	$defaults = array(
		'eyebrow' => 'Етапи будівництва',
		'title'   => 'Як ми будуємо',
		'steps'   => fwp_headless_app_steps_section_default_steps(),
	);

	$attrs = is_array( $attrs ) ? $attrs : array();
	$steps = ! empty( $attrs['steps'] ) && is_array( $attrs['steps'] )
		? fwp_headless_app_sanitize_steps_section_steps( $attrs['steps'] )
		: fwp_headless_app_sanitize_steps_section_steps( $defaults['steps'] );

	if ( empty( $steps ) ) {
		$steps = fwp_headless_app_sanitize_steps_section_steps( $defaults['steps'] );
	}

	return array(
		'type'    => 'steps_section',
		'eyebrow' => ! empty( $attrs['eyebrow'] ) ? sanitize_text_field( $attrs['eyebrow'] ) : $defaults['eyebrow'],
		'title'   => ! empty( $attrs['title'] ) ? sanitize_text_field( $attrs['title'] ) : $defaults['title'],
		'steps'   => $steps,
	);
}

/**
 * @param array<string, mixed> $section Seed section.
 * @return string
 */
function fwp_headless_app_steps_section_block_markup( $section = array() ) {
	$defaults = fwp_headless_app_steps_section_from_attrs( array() );
	$steps    = $defaults['steps'];

	if ( ! empty( $section['steps'] ) && is_array( $section['steps'] ) ) {
		$mapped = array();
		foreach ( $section['steps'] as $step ) {
			$mapped[] = array(
				'label'       => $step['label'] ?? '',
				'description' => $step['description'] ?? '',
				'imageUrl'    => $step['image_url'] ?? '',
				'imageId'     => (int) ( $step['image_id'] ?? 0 ),
				'items'       => $step['items'] ?? array(),
			);
		}
		$steps = $mapped;
	} else {
		$mapped = array();
		foreach ( $steps as $step ) {
			$mapped[] = array(
				'label'       => $step['label'],
				'description' => $step['description'],
				'imageUrl'    => $step['image_url'],
				'imageId'     => (int) $step['image_id'],
				'items'       => $step['items'],
			);
		}
		$steps = $mapped;
	}

	$attrs = array(
		'eyebrow' => $section['eyebrow'] ?? $defaults['eyebrow'],
		'title'   => $section['title'] ?? $defaults['title'],
		'steps'   => $steps,
	);

	return '<!-- wp:grv/steps-section ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Register block type.
 */
function fwp_headless_app_register_steps_section_block() {
	if ( ! function_exists( 'register_block_type' ) || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_block_type(
		'grv/steps-section',
		array(
			'api_version' => 3,
			'attributes'  => array(
				'eyebrow' => array(
					'type'    => 'string',
					'default' => 'Етапи будівництва',
				),
				'title'   => array(
					'type'    => 'string',
					'default' => 'Як ми будуємо',
				),
				'steps'   => array(
					'type'    => 'array',
					'default' => array(),
				),
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_steps_section_block' );

/**
 * Enqueue editor script.
 */
function fwp_headless_app_enqueue_steps_section_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/steps-section/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'grv-steps-section-editor',
		plugins_url( 'blocks/steps-section/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		(string) filemtime( $script_path ),
		true
	);

	$defaults = fwp_headless_app_steps_section_default_steps();
	$mapped   = array();
	foreach ( $defaults as $step ) {
		$mapped[] = array(
			'label'       => $step['label'],
			'description' => $step['description'],
			'imageUrl'    => $step['image_url'],
			'imageId'     => 0,
			'items'       => $step['items'],
		);
	}

	wp_localize_script(
		'grv-steps-section-editor',
		'grvStepsSectionData',
		array(
			'defaults' => array(
				'eyebrow' => 'Етапи будівництва',
				'title'   => 'Як ми будуємо',
				'steps'   => $mapped,
			),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_steps_section_block_editor_assets' );
