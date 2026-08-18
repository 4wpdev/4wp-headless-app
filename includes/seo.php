<?php
/**
 * Normalized SEO for headless export (Yoast / Rank Math / fallback).
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public front URL for a page slug (`/` or `/about`).
 *
 * @param string $slug Export slug.
 * @return string
 */
function fwp_headless_app_seo_front_url( $slug ) {
	$home = untrailingslashit( home_url( '/' ) );
	$slug = is_string( $slug ) ? $slug : '/';
	if ( $slug === '/' || $slug === '' ) {
		return $home . '/';
	}
	$path = '/' . ltrim( $slug, '/' );
	return $home . $path;
}

/**
 * Normalize robots flags to { index: bool, follow: bool }.
 *
 * @param mixed $robots Yoast array, Rank Math list, or null.
 * @return array{index: bool, follow: bool}
 */
function fwp_headless_app_seo_normalize_robots( $robots ) {
	$index  = true;
	$follow = true;

	if ( is_array( $robots ) ) {
		$flat = array();
		foreach ( $robots as $key => $value ) {
			if ( is_string( $key ) && ! is_numeric( $key ) ) {
				$flat[] = strtolower( (string) $key );
				if ( is_string( $value ) ) {
					$flat[] = strtolower( $value );
				}
			} elseif ( is_string( $value ) ) {
				$flat[] = strtolower( $value );
			}
		}
		$joined = implode( ',', $flat );
		if ( strpos( $joined, 'noindex' ) !== false ) {
			$index = false;
		}
		if ( strpos( $joined, 'nofollow' ) !== false ) {
			$follow = false;
		}
	}

	return array(
		'index'  => $index,
		'follow' => $follow,
	);
}

/**
 * Build OG images list from a URL or Yoast image arrays.
 *
 * @param mixed $images URL string, list of URLs, or Yoast open_graph_images.
 * @return array<int, array{url: string}>
 */
function fwp_headless_app_seo_normalize_images( $images ) {
	$out = array();

	if ( is_string( $images ) && $images !== '' ) {
		$out[] = array( 'url' => $images );
		return $out;
	}

	if ( ! is_array( $images ) ) {
		return $out;
	}

	foreach ( $images as $item ) {
		if ( is_string( $item ) && $item !== '' ) {
			$out[] = array( 'url' => $item );
			continue;
		}
		if ( ! is_array( $item ) ) {
			continue;
		}
		$url = '';
		if ( ! empty( $item['url'] ) && is_string( $item['url'] ) ) {
			$url = $item['url'];
		} elseif ( ! empty( $item['og:image'] ) && is_string( $item['og:image'] ) ) {
			$url = $item['og:image'];
		}
		if ( $url !== '' ) {
			$out[] = array( 'url' => $url );
		}
	}

	return $out;
}

/**
 * Empty normalized SEO shell (filled by adapters).
 *
 * @param string $canonical Front canonical URL.
 * @return array
 */
function fwp_headless_app_seo_empty( $canonical ) {
	return array(
		'title'       => '',
		'description' => '',
		'canonical'   => $canonical,
		'robots'      => array(
			'index'  => true,
			'follow' => true,
		),
		'openGraph'   => array(
			'title'       => '',
			'description' => '',
			'url'         => $canonical,
			'type'        => 'website',
			'images'      => array(),
		),
		'twitter'     => array(
			'card'        => 'summary_large_image',
			'title'       => '',
			'description' => '',
			'image'       => '',
		),
	);
}

/**
 * Apply Yoast %%replace_vars%% when available.
 *
 * @param string     $value Raw meta string.
 * @param int|WP_Post $post Post ID or post.
 * @return string
 */
function fwp_headless_app_seo_yoast_replace( $value, $post ) {
	$value = is_string( $value ) ? $value : '';
	if ( $value === '' ) {
		return $value;
	}
	// Avoid wpseo_replace_vars here — it can pull Surfaces/indexables and hang
	// when the Yoast indexable table is empty. Titles/descriptions from the
	// editor are usually plain text without %%vars%%.
	return $value;
}

/**
 * Yoast adapter: post meta first (reliable for headless), Surfaces as fill-in.
 *
 * Surfaces (`for_post`) needs `wp_yoast_indexable` rows. On fresh installs those
 * can be empty, so we prefer `_yoast_wpseo_*` post meta the admin actually edits.
 *
 * @param int    $post_id Post ID.
 * @param string $canonical Front canonical.
 * @return array|null Normalized seo or null if Yoast inactive.
 */
function fwp_headless_app_seo_from_yoast( $post_id, $canonical ) {
	$yoast_active = function_exists( 'YoastSEO' ) || defined( 'WPSEO_VERSION' );
	if ( ! $yoast_active ) {
		return null;
	}

	$post_id = (int) $post_id;
	$post    = get_post( $post_id );

	$title = fwp_headless_app_seo_yoast_replace(
		(string) get_post_meta( $post_id, '_yoast_wpseo_title', true ),
		$post
	);
	$description = fwp_headless_app_seo_yoast_replace(
		(string) get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ),
		$post
	);

	$og_title = fwp_headless_app_seo_yoast_replace(
		(string) get_post_meta( $post_id, '_yoast_wpseo_opengraph-title', true ),
		$post
	);
	$og_desc = fwp_headless_app_seo_yoast_replace(
		(string) get_post_meta( $post_id, '_yoast_wpseo_opengraph-description', true ),
		$post
	);
	$og_image = (string) get_post_meta( $post_id, '_yoast_wpseo_opengraph-image', true );

	$tw_title = fwp_headless_app_seo_yoast_replace(
		(string) get_post_meta( $post_id, '_yoast_wpseo_twitter-title', true ),
		$post
	);
	$tw_desc = fwp_headless_app_seo_yoast_replace(
		(string) get_post_meta( $post_id, '_yoast_wpseo_twitter-description', true ),
		$post
	);
	$tw_image = (string) get_post_meta( $post_id, '_yoast_wpseo_twitter-image', true );
	$tw_card  = (string) get_post_meta( $post_id, '_yoast_wpseo_twitter-card', true );

	$robots = array(
		'index'  => true,
		'follow' => true,
	);
	$noindex  = (string) get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
	$nofollow = (string) get_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', true );
	if ( $noindex === '1' ) {
		$robots['index'] = false;
	}
	if ( $nofollow === '1' ) {
		$robots['follow'] = false;
	}

	$images = fwp_headless_app_seo_normalize_images( $og_image !== '' ? $og_image : array() );

	// Prefer post meta only. Surfaces needs indexables; empty table returns wrong titles.

	if ( $tw_image === '' && ! empty( $images[0]['url'] ) ) {
		$tw_image = $images[0]['url'];
	}

	return array(
		'title'       => $title,
		'description' => $description,
		'canonical'   => $canonical,
		'robots'      => $robots,
		'openGraph'   => array(
			'title'       => $og_title !== '' ? $og_title : $title,
			'description' => $og_desc !== '' ? $og_desc : $description,
			'url'         => $canonical,
			'type'        => 'website',
			'images'      => $images,
		),
		'twitter'     => array(
			'card'        => $tw_card !== '' ? $tw_card : 'summary_large_image',
			'title'       => $tw_title !== '' ? $tw_title : $title,
			'description' => $tw_desc !== '' ? $tw_desc : $description,
			'image'       => $tw_image,
		),
	);
}

/**
 * Rank Math post meta adapter.
 *
 * @param int    $post_id Post ID.
 * @param string $canonical Front canonical.
 * @return array|null Normalized seo or null if Rank Math inactive / empty.
 */
function fwp_headless_app_seo_from_rank_math( $post_id, $canonical ) {
	$active = defined( 'RANK_MATH_VERSION' )
		|| class_exists( 'RankMath' )
		|| function_exists( 'rank_math' );

	if ( ! $active ) {
		return null;
	}

	$title = (string) get_post_meta( $post_id, 'rank_math_title', true );
	$description = (string) get_post_meta( $post_id, 'rank_math_description', true );

	// If Rank Math is active but this post has no SEO fields, still prefer its robots/OG when present.
	$rm_robots = get_post_meta( $post_id, 'rank_math_robots', true );
	$fb_title  = (string) get_post_meta( $post_id, 'rank_math_facebook_title', true );
	$fb_desc   = (string) get_post_meta( $post_id, 'rank_math_facebook_description', true );
	$fb_image  = (string) get_post_meta( $post_id, 'rank_math_facebook_image', true );
	$tw_title  = (string) get_post_meta( $post_id, 'rank_math_twitter_title', true );
	$tw_desc   = (string) get_post_meta( $post_id, 'rank_math_twitter_description', true );
	$tw_image  = (string) get_post_meta( $post_id, 'rank_math_twitter_image', true );
	$tw_card   = (string) get_post_meta( $post_id, 'rank_math_twitter_card_type', true );

	$has_any = $title !== '' || $description !== '' || $fb_title !== '' || $fb_desc !== ''
		|| $fb_image !== '' || $tw_title !== '' || $tw_desc !== '' || $tw_image !== ''
		|| ( is_array( $rm_robots ) && ! empty( $rm_robots ) );

	if ( ! $has_any ) {
		return null;
	}

	$images = fwp_headless_app_seo_normalize_images( $fb_image !== '' ? $fb_image : array() );
	if ( $tw_image === '' && ! empty( $images[0]['url'] ) ) {
		$tw_image = $images[0]['url'];
	}

	return array(
		'title'       => $title,
		'description' => $description,
		'canonical'   => $canonical,
		'robots'      => fwp_headless_app_seo_normalize_robots( $rm_robots ),
		'openGraph'   => array(
			'title'       => $fb_title !== '' ? $fb_title : $title,
			'description' => $fb_desc !== '' ? $fb_desc : $description,
			'url'         => $canonical,
			'type'        => 'website',
			'images'      => $images,
		),
		'twitter'     => array(
			'card'        => $tw_card !== '' ? $tw_card : 'summary_large_image',
			'title'       => $tw_title !== '' ? $tw_title : $title,
			'description' => $tw_desc !== '' ? $tw_desc : $description,
			'image'       => $tw_image,
		),
	);
}

/**
 * Fallback from page meta / post title (no SEO plugin).
 *
 * @param int    $post_id Post ID.
 * @param string $canonical Front canonical.
 * @return array
 */
function fwp_headless_app_seo_from_fallback( $post_id, $canonical ) {
	$post = get_post( $post_id );
	$h1   = (string) get_post_meta( $post_id, 'h1', true );
	$title = $h1 !== '' ? $h1 : ( $post ? (string) $post->post_title : '' );
	$description = (string) get_post_meta( $post_id, 'meta_description', true );

	$seo = fwp_headless_app_seo_empty( $canonical );
	$seo['title']                 = $title;
	$seo['description']           = $description;
	$seo['openGraph']['title']    = $title;
	$seo['openGraph']['description'] = $description;
	$seo['twitter']['title']      = $title;
	$seo['twitter']['description'] = $description;

	return $seo;
}

/**
 * Fill blank string fields from a secondary seo array (e.g. plugin + fallback).
 *
 * @param array $primary Prefer this.
 * @param array $fallback Fill empties from this.
 * @return array
 */
function fwp_headless_app_seo_merge_fallback( array $primary, array $fallback ) {
	foreach ( array( 'title', 'description' ) as $key ) {
		if ( empty( $primary[ $key ] ) && ! empty( $fallback[ $key ] ) ) {
			$primary[ $key ] = $fallback[ $key ];
		}
	}

	foreach ( array( 'title', 'description' ) as $key ) {
		if ( empty( $primary['openGraph'][ $key ] ) && ! empty( $fallback['openGraph'][ $key ] ) ) {
			$primary['openGraph'][ $key ] = $fallback['openGraph'][ $key ];
		}
		if ( empty( $primary['twitter'][ $key ] ) && ! empty( $fallback['twitter'][ $key ] ) ) {
			$primary['twitter'][ $key ] = $fallback['twitter'][ $key ];
		}
	}

	if ( empty( $primary['openGraph']['images'] ) && ! empty( $fallback['openGraph']['images'] ) ) {
		$primary['openGraph']['images'] = $fallback['openGraph']['images'];
	}
	if ( empty( $primary['twitter']['image'] ) && ! empty( $fallback['twitter']['image'] ) ) {
		$primary['twitter']['image'] = $fallback['twitter']['image'];
	}

	$primary['canonical']         = $fallback['canonical'];
	$primary['openGraph']['url']  = $fallback['canonical'];

	return $primary;
}

/**
 * Normalized SEO for a post (Yoast → Rank Math → fallback).
 *
 * @param int $post_id Post ID.
 * @return array
 */
function fwp_headless_app_get_post_seo( $post_id ) {
	$post_id = (int) $post_id;
	$post    = get_post( $post_id );
	$slug    = $post ? fwp_headless_app_page_export_slug( $post ) : '/';
	$canonical = fwp_headless_app_seo_front_url( $slug );
	$fallback  = fwp_headless_app_seo_from_fallback( $post_id, $canonical );

	$from_yoast = fwp_headless_app_seo_from_yoast( $post_id, $canonical );
	if ( is_array( $from_yoast ) ) {
		return fwp_headless_app_seo_merge_fallback( $from_yoast, $fallback );
	}

	$from_rm = fwp_headless_app_seo_from_rank_math( $post_id, $canonical );
	if ( is_array( $from_rm ) ) {
		return fwp_headless_app_seo_merge_fallback( $from_rm, $fallback );
	}

	return $fallback;
}

/**
 * Internal headless CPTs — no standalone public URLs or Yoast sitemap entries.
 *
 * @param bool   $exclude   Whether to exclude.
 * @param string $post_type Post type slug.
 */
function fwp_headless_app_yoast_exclude_internal_cpt_from_sitemap( $exclude, $post_type ) {
	if ( in_array( $post_type, array( 'faq_item', 'team_member', 'work_item' ), true ) ) {
		return true;
	}
	return $exclude;
}
add_filter( 'wpseo_sitemap_exclude_post_type', 'fwp_headless_app_yoast_exclude_internal_cpt_from_sitemap', 10, 2 );
