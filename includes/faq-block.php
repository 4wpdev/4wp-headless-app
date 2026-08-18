<?php
/**
 * GRV FAQ Gutenberg block and page association for faq_item CPT.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * Export slug for a WP page (front page → `/`).
 *
 * @param WP_Post $post Page post.
 * @return string
 */
function fwp_headless_app_page_export_slug( $post ) {
	$front_id = (int) get_option( 'page_on_front' );
	if ( $front_id && (int) $post->ID === $front_id ) {
		return '/';
	}

	$segments = array();
	$current  = $post;
	$guard    = 0;

	while ( $current instanceof WP_Post && $guard < 20 ) {
		++$guard;
		if ( $current->post_name ) {
			array_unshift( $segments, $current->post_name );
		}
		if ( ! $current->post_parent ) {
			break;
		}
		$current = get_post( (int) $current->post_parent );
	}

	if ( empty( $segments ) ) {
		return '/';
	}

	return '/' . implode( '/', $segments );
}

/**
 * Headless page template (meta or front page fallback).
 *
 * @param WP_Post $post Page post.
 * @return string
 */
function fwp_headless_app_resolve_page_template( $post ) {
	$wp_slug = get_page_template_slug( $post );
	if ( $wp_slug ) {
		$api = fwp_headless_app_wp_slug_to_api_template( $wp_slug );
		if ( $api !== '' ) {
			return $api;
		}
	}

	$legacy = get_post_meta( $post->ID, 'page_template_slug', true );
	if ( is_string( $legacy ) && $legacy !== '' ) {
		$normalized = fwp_headless_app_normalize_page_template( $legacy, $post );
		if ( $normalized !== '' ) {
			return $normalized;
		}
	}

	return fwp_headless_app_get_default_page_template();
}

/**
 * List pages for FAQ assignment UI.
 *
 * @return array<int, array{slug: string, label: string}>
 */
function fwp_headless_app_get_faq_page_choices() {
	$choices = array();
	$pages   = get_posts(
		array(
			'post_type'      => 'page',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	foreach ( $pages as $page ) {
		$choices[] = array(
			'slug'  => fwp_headless_app_page_export_slug( $page ),
			'label' => $page->post_title . ' (' . fwp_headless_app_page_export_slug( $page ) . ')',
		);
	}

	return $choices;
}

/**
 * Decode faq_pages meta to slug array.
 *
 * @param mixed $raw Meta value.
 * @return string[]
 */
function fwp_headless_app_decode_faq_pages( $raw ) {
	if ( is_array( $raw ) ) {
		return array_values( array_filter( array_map( 'strval', $raw ) ) );
	}
	if ( is_string( $raw ) && $raw !== '' ) {
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			return array_values( array_filter( array_map( 'strval', $decoded ) ) );
		}
	}
	return array();
}

/**
 * Parse Gutenberg blocks from page content into headless sections.
 *
/**
 * Attach optional Gutenberg HTML anchor → section `id` (only when set).
 *
 * @param array<string, mixed> $section Section payload.
 * @param array<string, mixed> $attrs   Block attributes.
 * @return array<string, mixed>
 */
function fwp_headless_app_section_with_anchor( $section, $attrs ) {
	if ( ! is_array( $section ) ) {
		return $section;
	}
	$attrs = is_array( $attrs ) ? $attrs : array();
	if ( empty( $attrs['anchor'] ) || ! is_string( $attrs['anchor'] ) ) {
		return $section;
	}
	$id = sanitize_title( $attrs['anchor'] );
	if ( $id !== '' ) {
		$section['id'] = $id;
	}
	return $section;
}

/**
 * @param int $post_id Page ID.
 * @return array<int, array<string, mixed>>
 */
function fwp_headless_app_parse_page_sections( $post_id ) {
	$content = get_post_field( 'post_content', $post_id );
	if ( ! is_string( $content ) || $content === '' ) {
		return array();
	}

	$sections = array();
	$push     = static function ( $section, $attrs ) use ( &$sections ) {
		if ( ! is_array( $section ) ) {
			return;
		}
		$sections[] = fwp_headless_app_section_with_anchor( $section, $attrs );
	};

	foreach ( parse_blocks( $content ) as $block ) {
		$name  = $block['blockName'] ?? '';
		$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();

		if ( $name === 'grv/faq-section' ) {
			$faq_ids = array();
			if ( ! empty( $attrs['faqIds'] ) && is_array( $attrs['faqIds'] ) ) {
				$faq_ids = array_values( array_filter( array_map( 'intval', $attrs['faqIds'] ) ) );
			}

			$section = array(
				'type'  => 'faq',
				'title' => ! empty( $attrs['title'] ) ? (string) $attrs['title'] : 'Часті запитання',
			);
			if ( ! empty( $faq_ids ) ) {
				$section['faq_ids'] = $faq_ids;
			}
			$push( $section, $attrs );
			continue;
		}

		if ( $name === 'grv/cta-strip' ) {
			$push(
				array(
					'type'         => 'cta_strip',
					'title'        => ! empty( $attrs['title'] ) ? (string) $attrs['title'] : 'Готові розпочати ваш проект?',
					'button_label' => ! empty( $attrs['buttonLabel'] ) ? (string) $attrs['buttonLabel'] : "Зв'язатись з нами",
					'button_href'  => ! empty( $attrs['buttonHref'] ) ? (string) $attrs['buttonHref'] : '/contacts',
				),
				$attrs
			);
			continue;
		}

		if ( $name === 'grv/cta-card' ) {
			$push( fwp_headless_app_cta_card_from_attrs( $attrs ), $attrs );
			continue;
		}

		if ( $name === 'grv/cta-advanced' ) {
			$push( fwp_headless_app_cta_advanced_section_from_attrs( $attrs ), $attrs );
			continue;
		}

		if ( $name === 'grv/services-section' ) {
			$push( fwp_headless_app_services_section_from_attrs( $attrs ), $attrs );
			continue;
		}

		if ( $name === 'grv/how-it-works' ) {
			$push( fwp_headless_app_how_it_works_from_attrs( $attrs ), $attrs );
			continue;
		}

		if ( $name === 'grv/linked-section' ) {
			$push( fwp_headless_app_linked_section_from_attrs( $attrs ), $attrs );
			continue;
		}

		if ( $name === 'grv/regions-section' ) {
			$push( fwp_headless_app_regions_section_from_attrs( $attrs ), $attrs );
			continue;
		}

		if ( $name === 'grv/hero-slider' ) {
			$push( fwp_headless_app_hero_slider_from_attrs( $attrs ), $attrs );
			continue;
		}

		if ( $name === 'grv/social-links-bar' ) {
			$push( fwp_headless_app_social_links_bar_from_attrs( $attrs ), $attrs );
			continue;
		}

		if ( $name === 'grv/works-gallery' ) {
			$push( fwp_headless_app_works_gallery_from_attrs( $attrs ), $attrs );
			continue;
		}

		if ( $name === 'grv/team-section' ) {
			$push( fwp_headless_app_team_section_from_attrs( $attrs ), $attrs );
			continue;
		}

		if ( $name === 'grv/cta-related' ) {
			$push( fwp_headless_app_cta_related_from_attrs( $attrs ), $attrs );
			continue;
		}

		if ( $name === 'grv/steps-section' ) {
			$push( fwp_headless_app_steps_section_from_attrs( $attrs ), $attrs );
			continue;
		}

		if ( $name === 'grv/contacts-section' ) {
			$push( fwp_headless_app_contacts_section_from_attrs( $attrs ), $attrs );
			continue;
		}

		if ( $name === 'grv/rich-text' ) {
			$section = fwp_headless_app_rich_text_from_block( $attrs, $block );
			if ( $section['html'] !== '' || ! empty( $section['title'] ) ) {
				$push( $section, $attrs );
			}
			continue;
		}

		if ( $name === 'core/spacer' ) {
			$height = fwp_headless_app_spacer_height_from_attrs( $attrs );
			$push(
				array(
					'type'   => 'spacer',
					'height' => $height,
				),
				$attrs
			);
		}
	}

	return $sections;
}

/**
 * Normalize core/spacer height to CSS length (e.g. 100px).
 *
 * @param array<string, mixed> $attrs Block attributes.
 * @return string
 */
function fwp_headless_app_spacer_height_from_attrs( $attrs ) {
	$attrs = is_array( $attrs ) ? $attrs : array();

	if ( ! empty( $attrs['height'] ) ) {
		$raw = $attrs['height'];
		if ( is_numeric( $raw ) ) {
			return absint( $raw ) . 'px';
		}
		if ( is_string( $raw ) ) {
			$raw = trim( $raw );
			if ( $raw !== '' && preg_match( '/^\d+(\.\d+)?(px|rem|em|vh|%)$/', $raw ) ) {
				return $raw;
			}
			if ( $raw !== '' && preg_match( '/^\d+(\.\d+)?$/', $raw ) ) {
				return $raw . 'px';
			}
		}
	}

	// Fallback from style attribute / innerHTML height.
	if ( ! empty( $attrs['style']['spacing']['height'] ) && is_string( $attrs['style']['spacing']['height'] ) ) {
		return sanitize_text_field( $attrs['style']['spacing']['height'] );
	}

	return '100px';
}

/**
 * FAQ rows for the block editor picker.
 *
 * @return array<int, array{id: int, question: string, answer: string, sort_order: int}>
 */
function fwp_headless_app_get_faq_editor_choices() {
	$choices = array();
	$posts   = get_posts(
		array(
			'post_type'      => 'faq_item',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	foreach ( $posts as $post ) {
		$choices[] = array(
			'id'         => (int) $post->ID,
			'question'   => $post->post_title,
			'answer'     => wp_trim_words( wp_strip_all_tags( $post->post_content ), 24, '…' ),
			'sort_order' => (int) $post->menu_order,
		);
	}

	return $choices;
}

/**
 * Default block markup when seeding a page.
 *
 * @param array<string, mixed> $page Seed page row.
 * @return string
 */
function fwp_headless_app_faq_block_markup( $section ) {
	$title = ! empty( $section['title'] ) ? (string) $section['title'] : 'Часті запитання';
	$attrs = array( 'title' => $title );
	if ( ! empty( $section['faq_ids'] ) && is_array( $section['faq_ids'] ) ) {
		$attrs['faqIds'] = array_values( array_map( 'intval', $section['faq_ids'] ) );
	}
	return '<!-- wp:grv/faq-section ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * @param array<string, mixed> $section Seed section row.
 */
function fwp_headless_app_cta_strip_block_markup( $section ) {
	$attrs = array(
		'title'       => $section['title'] ?? 'Готові розпочати ваш проект?',
		'buttonLabel' => $section['button_label'] ?? "Зв'язатись з нами",
		'buttonHref'  => $section['button_href'] ?? '/contacts',
	);
	return '<!-- wp:grv/cta-strip ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE ) . ' /-->';
}

/**
 * Default Gutenberg content when seeding a page from sections[].
 *
 * @param array<string, mixed> $page Seed page row.
 * @return string
 */
function fwp_headless_app_default_page_content( $page ) {
	$parts = array();

	foreach ( $page['sections'] ?? array() as $section ) {
		$type = $section['type'] ?? '';
		if ( $type === 'faq' ) {
			$parts[] = fwp_headless_app_faq_block_markup( $section );
		} elseif ( $type === 'cta_strip' ) {
			$parts[] = fwp_headless_app_cta_strip_block_markup( $section );
		} elseif ( $type === 'cta_card' ) {
			$parts[] = fwp_headless_app_cta_card_block_markup( $section );
		} elseif ( $type === 'cta_advanced' ) {
			$parts[] = fwp_headless_app_cta_advanced_block_markup( $section );
		} elseif ( $type === 'services_section' ) {
			$parts[] = fwp_headless_app_services_section_block_markup( $section );
		} elseif ( $type === 'how_it_works' ) {
			$parts[] = fwp_headless_app_how_it_works_block_markup( $section );
		} elseif ( $type === 'linked_section' ) {
			$parts[] = fwp_headless_app_linked_section_block_markup( $section );
		} elseif ( $type === 'regions_section' ) {
			$parts[] = fwp_headless_app_regions_section_block_markup( $section );
		} elseif ( $type === 'hero_slider' ) {
			$parts[] = fwp_headless_app_hero_slider_block_markup( $section );
		} elseif ( $type === 'social_links_bar' ) {
			$parts[] = fwp_headless_app_social_links_bar_block_markup( $section );
		} elseif ( $type === 'works_gallery' ) {
			$parts[] = fwp_headless_app_works_gallery_block_markup( $section );
		} elseif ( $type === 'team_section' ) {
			$parts[] = fwp_headless_app_team_section_block_markup( $section );
		} elseif ( $type === 'cta_related' ) {
			$parts[] = fwp_headless_app_cta_related_block_markup( $section );
		} elseif ( $type === 'steps_section' ) {
			$parts[] = fwp_headless_app_steps_section_block_markup( $section );
		} elseif ( $type === 'contacts_section' ) {
			$parts[] = fwp_headless_app_contacts_section_block_markup( $section );
		} elseif ( $type === 'rich_text' ) {
			$parts[] = fwp_headless_app_rich_text_block_markup( $section );
		} elseif ( $type === 'spacer' ) {
			$height = ! empty( $section['height'] ) ? (string) $section['height'] : '100px';
			$parts[] = '<!-- wp:spacer {"height":' . wp_json_encode( $height ) . '} -->'
				. "\n" . '<div style="height:' . esc_attr( $height ) . '" aria-hidden="true" class="wp-block-spacer"></div>'
				. "\n" . '<!-- /wp:spacer -->';
		}
	}

	return implode( "\n\n", $parts );
}

/**
 * GRV block category in the inserter.
 *
 * @param array<int, array<string, mixed>> $categories Block categories.
 * @return array<int, array<string, mixed>>
 */
function fwp_headless_app_register_block_categories( $categories ) {
	$exists = false;
	foreach ( $categories as $category ) {
		if ( ( $category['slug'] ?? '' ) === 'grv' ) {
			$exists = true;
			break;
		}
	}

	if ( ! $exists ) {
		array_unshift(
			$categories,
			array(
				'slug'  => 'grv',
				'title' => __( 'GRV BUILD', '4wp-headless-app' ),
				'icon'  => null,
			)
		);
	}

	return $categories;
}
add_filter( 'block_categories_all', 'fwp_headless_app_register_block_categories' );

/**
 * Server-side block registration (attributes for parse_blocks / REST).
 */
function fwp_headless_app_register_faq_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	$model = fwp_headless_app_get_content_model();
	if ( ! fwp_headless_app_model_uses_post_type( 'faq_item', $model ) ) {
		return;
	}

	register_block_type(
		'grv/faq-section',
		array(
			'api_version' => 3,
			'attributes'  => array(
				'title' => array(
					'type'    => 'string',
					'default' => 'Часті запитання',
				),
				'faqIds' => array(
					'type'    => 'array',
					'default' => array(),
					'items'   => array(
						'type' => 'number',
					),
				),
			),
		)
	);
}
add_action( 'init', 'fwp_headless_app_register_faq_block' );

/**
 * Enqueue editor script (full block UI is registered in editor.js).
 */
function fwp_headless_app_enqueue_faq_block_editor_assets() {
	$model = fwp_headless_app_get_content_model();
	if ( ! fwp_headless_app_model_uses_post_type( 'faq_item', $model ) ) {
		return;
	}

	$plugin_root = dirname( __DIR__ );
	$script_path = $plugin_root . '/blocks/faq-section/editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'grv-faq-section-editor',
		plugins_url( 'blocks/faq-section/editor.js', $plugin_root . '/4wp-headless-app.php' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		(string) filemtime( $script_path ),
		true
	);

	wp_localize_script(
		'grv-faq-section-editor',
		'grvFaqBlockData',
		array(
			'items'      => fwp_headless_app_get_faq_editor_choices(),
			'manageUrl'  => admin_url( 'edit.php?post_type=faq_item' ),
			'newItemUrl' => admin_url( 'post-new.php?post_type=faq_item' ),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'fwp_headless_app_enqueue_faq_block_editor_assets' );

/**
 * FAQ pages meta box.
 */
function fwp_headless_app_register_faq_meta_boxes() {
	$model = fwp_headless_app_get_content_model();
	if ( ! fwp_headless_app_model_uses_post_type( 'faq_item', $model ) ) {
		return;
	}

	add_meta_box(
		'grv_faq_pages',
		__( 'FAQ Pages', '4wp-headless-app' ),
		'fwp_headless_app_render_faq_pages_meta_box',
		'faq_item',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'fwp_headless_app_register_faq_meta_boxes' );

/**
 * @param WP_Post $post FAQ post.
 */
function fwp_headless_app_render_faq_pages_meta_box( $post ) {
	wp_nonce_field( 'grv_faq_pages', 'grv_faq_pages_nonce' );
	$selected = fwp_headless_app_decode_faq_pages( get_post_meta( $post->ID, 'faq_pages', true ) );
	$choices  = fwp_headless_app_get_faq_page_choices();

	if ( empty( $choices ) ) {
		echo '<p>' . esc_html__( 'No pages found. Create pages first.', '4wp-headless-app' ) . '</p>';
		return;
	}
	?>
	<p class="description">
		<?php esc_html_e( 'Select which pages show this FAQ item.', '4wp-headless-app' ); ?>
	</p>
	<?php foreach ( $choices as $choice ) : ?>
		<label style="display:block;margin-bottom:6px;">
			<input
				type="checkbox"
				name="grv_faq_pages[]"
				value="<?php echo esc_attr( $choice['slug'] ); ?>"
				<?php checked( in_array( $choice['slug'], $selected, true ) ); ?>
			/>
			<?php echo esc_html( $choice['label'] ); ?>
		</label>
	<?php endforeach; ?>
	<?php
}

/**
 * @param int $post_id Post ID.
 */
function fwp_headless_app_save_faq_item_meta( $post_id ) {
	if ( ! isset( $_POST['grv_faq_pages_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['grv_faq_pages_nonce'] ) ), 'grv_faq_pages' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$pages = array();
	if ( isset( $_POST['grv_faq_pages'] ) && is_array( $_POST['grv_faq_pages'] ) ) {
		$pages = array_values(
			array_unique(
				array_map(
					'sanitize_text_field',
					array_map( 'wp_unslash', $_POST['grv_faq_pages'] )
				)
			)
		);
	}

	update_post_meta( $post_id, 'faq_pages', wp_json_encode( $pages ) );
}
add_action( 'save_post_faq_item', 'fwp_headless_app_save_faq_item_meta' );

