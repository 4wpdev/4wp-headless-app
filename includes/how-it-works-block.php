<?php
/**
 * How It Works Gutenberg block.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array<string, string>
 */
function fwp_headless_app_get_how_it_works_icon_choices() {
	return array(
		'phone'        => 'Phone',
		'file-text'    => 'File text',
		'hammer'       => 'Hammer',
		'check-circle' => 'Check circle',
		'wrench'       => 'Wrench',
		'building2'    => 'Building',
		'clipboard'    => 'Clipboard',
		'handshake'    => 'Handshake',
	);
}

/**
 * @return array<int, array<string, string>>
 */
function fwp_headless_app_how_it_works_default_steps() {
	return array(
		array(
			'icon'   => 'phone',
			'title'  => 'Консультація',
			'desc'   => "Зв'яжіться з нами — безкоштовна консультація щодо вашого проєкту. Обговорюємо бажання, терміни та бюджет.",
			'detail' => "Наш менеджер відповідає протягом 1 години. Ми виїжджаємо на об'єкт для оцінки, прослуховуємо всі ваші побажання та пропонуємо оптимальне рішення.",
		),
		array(
			'icon'   => 'file-text',
			'title'  => 'Кошторис та договір',
			'desc'   => 'Складаємо детальний кошторис та підписуємо договір з чітко прописаними термінами та гарантіями.',
			'detail' => 'Кошторис фіксований — ніяких прихованих платежів у процесі. У договорі прописані всі матеріали, терміни та гарантійні зобов\'язання.',
		),
		array(
			'icon'   => 'hammer',
			'title'  => 'Виконання робіт',
			'desc'   => 'Розпочинаємо будівництво. Ви отримуєте регулярні звіти про хід виконання робіт на кожному етапі.',
			'detail' => 'Щотижневі фото- та відеозвіти з майданчика. Ви завжди знаєте, що відбувається. Виїзд на перевірку — у будь-який момент.',
		),
		array(
			'icon'   => 'check-circle',
			'title'  => "Здача об'єкту",
			'desc'   => "Приймання готового об'єкту, оформлення гарантійних документів та подальша підтримка.",
			'detail' => 'Фінальний огляд разом із замовником. Надаємо гарантію на всі роботи. Після здачі — підтримка та консультації протягом 12 місяців.',
		),
	);
}

/**
 * @param array<int, mixed>|null $steps Raw steps.
 * @return array<int, array<string, string>>
 */
function fwp_headless_app_sanitize_how_it_works_steps( $steps ) {
	$choices = fwp_headless_app_get_how_it_works_icon_choices();
	$output  = array();

	if ( ! is_array( $steps ) ) {
		return $output;
	}

	foreach ( $steps as $step ) {
		if ( ! is_array( $step ) ) {
			continue;
		}

		$icon = sanitize_key( $step['icon'] ?? '' );
		if ( ! isset( $choices[ $icon ] ) ) {
			$icon = 'phone';
		}

		$title = sanitize_text_field( $step['title'] ?? '' );
		if ( '' === $title ) {
			continue;
		}

		$output[] = array(
			'icon'   => $icon,
			'title'  => $title,
			'desc'   => sanitize_textarea_field( $step['desc'] ?? '' ),
			'detail' => sanitize_textarea_field( $step['detail'] ?? '' ),
		);
	}

	return $output;
}

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_how_it_works_from_attrs( $attrs ) {
	$defaults = array(
		'sub_title' => 'Процес роботи',
		'title'     => 'Як це працює',
		'intro'     => "Чітка та прозора система взаємодії — від першого дзвінка до здачі готового об'єкту",
		'steps'     => fwp_headless_app_how_it_works_default_steps(),
	);

	$attrs = is_array( $attrs ) ? $attrs : array();
	$steps = ! empty( $attrs['steps'] ) && is_array( $attrs['steps'] )
		? fwp_headless_app_sanitize_how_it_works_steps( $attrs['steps'] )
		: fwp_headless_app_sanitize_how_it_works_steps( $defaults['steps'] );

	if ( empty( $steps ) ) {
		$steps = fwp_headless_app_sanitize_how_it_works_steps( $defaults['steps'] );
	}

	return array(
		'type'      => 'how_it_works',
		'sub_title' => ! empty( $attrs['subTitle'] ) ? sanitize_text_field( $attrs['subTitle'] ) : $defaults['sub_title'],
		'title'     => ! empty( $attrs['title'] ) ? sanitize_text_field( $attrs['title'] ) : $defaults['title'],
		'intro'     => ! empty( $attrs['intro'] ) ? sanitize_textarea_field( $attrs['intro'] ) : $defaults['intro'],
		'steps'     => $steps,
	);
}

/**
 * @param array<string, mixed> $section Seed section.
 * @return string
 */
function fwp_headless_app_how_it_works_block_markup( $section = array() ) {
	$defaults = fwp_headless_app_how_it_works_from_attrs( array() );
	$steps    = $defaults['steps'];

	if ( ! empty( $section['steps'] ) && is_array( $section['steps'] ) ) {
		$mapped = array();
		foreach ( $section['steps'] as $step ) {
			$mapped[] = array(
				'icon'   => $step['icon'] ?? 'phone',
				'title'  => $step['title'] ?? '',
				'desc'   => $step['desc'] ?? '',
				'detail' => $step['detail'] ?? '',
			);
		}
		$steps = $mapped;
	}

	$attrs = array(
		'subTitle' => $section['sub_title'] ?? $defaults['sub_title'],
		'title'    => $section['title'] ?? $defaults['title'],
		'intro'    => $section['intro'] ?? $defaults['intro'],
		'steps'    => $steps,
	);

	return '<!-- wp:grv/how-it-works ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Register block type.
 */
function fwp_headless_app_register_how_it_works_block() {
	if ( ! function_exists( 'register_block_type' ) || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_block_type(
		'grv/how-it-works',
		array(
			'api_version' => 3,
			'attributes'  => array(
				'subTitle' => array(
					'type'    => 'string',
					'default' => 'Процес роботи',
				),
				'title'    => array(
					'type'    => 'string',
					'default' => 'Як це працює',
				),
				'intro'    => array(
					'type'    => 'string',
					'default' => "Чітка та прозора система взаємодії — від першого дзвінка до здачі готового об'єкту",
				),
				'steps'    => array(
					'type'    => 'array',
					'default' => array(),
				),
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_how_it_works_block' );

/**
 * Enqueue editor script.
 */
function fwp_headless_app_enqueue_how_it_works_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/how-it-works/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'grv-how-it-works-editor',
		plugins_url( 'blocks/how-it-works/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		(string) filemtime( $script_path ),
		true
	);

	$icons = array();
	foreach ( fwp_headless_app_get_how_it_works_icon_choices() as $slug => $label ) {
		$icons[] = array( 'slug' => $slug, 'label' => $label );
	}

	wp_localize_script(
		'grv-how-it-works-editor',
		'grvHowItWorksData',
		array(
			'icons'    => $icons,
			'defaults' => array(
				'subTitle' => 'Процес роботи',
				'title'    => 'Як це працює',
				'intro'    => "Чітка та прозора система взаємодії — від першого дзвінка до здачі готового об'єкту",
				'steps'    => fwp_headless_app_how_it_works_default_steps(),
			),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_how_it_works_block_editor_assets' );
