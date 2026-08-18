<?php
/**
 * Team Section Gutenberg block (CPT team_member carousel).
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param array<string, mixed> $attrs Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_team_section_from_attrs( $attrs ) {
	$attrs = is_array( $attrs ) ? $attrs : array();

	$member_ids = array();
	if ( ! empty( $attrs['memberIds'] ) && is_array( $attrs['memberIds'] ) ) {
		$member_ids = array_values( array_filter( array_map( 'intval', $attrs['memberIds'] ) ) );
	}

	$show_cta = array_key_exists( 'showCta', $attrs ) ? (bool) $attrs['showCta'] : true;

	$section = array(
		'type'             => 'team_section',
		'eyebrow'          => ! empty( $attrs['eyebrow'] )
			? sanitize_text_field( $attrs['eyebrow'] )
			: 'Наша команда',
		'title_line1'      => ! empty( $attrs['titleLine1'] )
			? sanitize_text_field( $attrs['titleLine1'] )
			: 'Люди, які',
		'title_highlight'  => ! empty( $attrs['titleHighlight'] )
			? sanitize_text_field( $attrs['titleHighlight'] )
			: 'будують твій дім',
		'show_cta'         => $show_cta,
		'cta_title'        => array_key_exists( 'ctaTitle', $attrs )
			? sanitize_text_field( (string) $attrs['ctaTitle'] )
			: 'Хочеш до',
		'cta_highlight'    => array_key_exists( 'ctaHighlight', $attrs )
			? sanitize_text_field( (string) $attrs['ctaHighlight'] )
			: 'команди?',
		'cta_text'         => array_key_exists( 'ctaText', $attrs )
			? sanitize_textarea_field( (string) $attrs['ctaText'] )
			: 'Набираємо хлопців, які вміють і хочуть працювати.',
		'cta_button_label' => array_key_exists( 'ctaButtonLabel', $attrs )
			? sanitize_text_field( (string) $attrs['ctaButtonLabel'] )
			: 'Доєднатись',
	);

	if ( ! empty( $member_ids ) ) {
		$section['member_ids'] = $member_ids;
	}

	return fwp_headless_app_section_with_breadcrumb( $section, $attrs );
}

/**
 * @param array<string, mixed> $section Seed section.
 * @return string
 */
function fwp_headless_app_team_section_block_markup( $section = array() ) {
	$defaults = fwp_headless_app_team_section_from_attrs( array() );
	$attrs    = array(
		'eyebrow'         => $section['eyebrow'] ?? $defaults['eyebrow'],
		'titleLine1'     => $section['title_line1'] ?? $defaults['title_line1'],
		'titleHighlight' => $section['title_highlight'] ?? $defaults['title_highlight'],
		'showCta'        => isset( $section['show_cta'] ) ? (bool) $section['show_cta'] : $defaults['show_cta'],
		'ctaTitle'       => $section['cta_title'] ?? $defaults['cta_title'],
		'ctaHighlight'   => $section['cta_highlight'] ?? $defaults['cta_highlight'],
		'ctaText'        => $section['cta_text'] ?? $defaults['cta_text'],
		'ctaButtonLabel' => $section['cta_button_label'] ?? $defaults['cta_button_label'],
	);
	if ( ! empty( $section['member_ids'] ) && is_array( $section['member_ids'] ) ) {
		$attrs['memberIds'] = array_values( array_map( 'intval', $section['member_ids'] ) );
	}

	return '<!-- wp:grv/team-section ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Register block type.
 */
function fwp_headless_app_register_team_section_block() {
	if ( ! function_exists( 'register_block_type' ) || ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_block_type(
		'grv/team-section',
		array(
			'api_version' => 3,
			'attributes'  => array_merge(
				array(
				'eyebrow'         => array( 'type' => 'string', 'default' => 'Наша команда' ),
				'titleLine1'     => array( 'type' => 'string', 'default' => 'Люди, які' ),
				'titleHighlight' => array( 'type' => 'string', 'default' => 'будують твій дім' ),
				'memberIds'      => array( 'type' => 'array', 'default' => array() ),
				'showCta'        => array( 'type' => 'boolean', 'default' => true ),
				'ctaTitle'       => array( 'type' => 'string', 'default' => 'Хочеш до' ),
				'ctaHighlight'   => array( 'type' => 'string', 'default' => 'команди?' ),
				'ctaText'        => array( 'type' => 'string', 'default' => 'Набираємо хлопців, які вміють і хочуть працювати.' ),
				'ctaButtonLabel' => array( 'type' => 'string', 'default' => 'Доєднатись' ),
				),
				fwp_headless_app_breadcrumb_block_attributes()
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_team_section_block' );

/**
 * @return array<int, array{id: int, title: string}>
 */
function fwp_headless_app_get_team_editor_choices() {
	$choices = array();
	$posts   = get_posts(
		array(
			'post_type'      => 'team_member',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	foreach ( $posts as $post ) {
		$choices[] = array(
			'id'    => (int) $post->ID,
			'title' => $post->post_title,
		);
	}

	return $choices;
}

/**
 * Enqueue editor script.
 */
function fwp_headless_app_enqueue_team_section_block_editor_assets() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/team-section/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'grv-team-section-editor',
		plugins_url( 'blocks/team-section/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'grv-breadcrumb-inspector' ),
		(string) filemtime( $script_path ),
		true
	);
	fwp_headless_app_enqueue_breadcrumb_block_editor();

	wp_localize_script(
		'grv-team-section-editor',
		'grvTeamSectionData',
		array(
			'members' => fwp_headless_app_get_team_editor_choices(),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_team_section_block_editor_assets' );
