<?php
/**
 * BE Schema Engine - Social meta (OpenGraph + Twitter).
 *
 * Responsibilities:
 * - Output OpenGraph and Twitter Card <meta> tags.
 * - Use conservative, automatic defaults:
 *     - Title: wp_get_document_title()
 *     - Description:
 *          - Singular: excerpt → content snippet
 *          - Non-singular: site tagline
 *     - URL: canonical URL (cleaned) or permalink as fallback
 *     - Images:
 *          - Featured image (preferred)
 *          - Social defaults from settings
 *          - Global default image (if configured)
 * - Provide structured debug snapshots when debug mode is enabled.
 *
 * NOTE:
 * - This module does NOT output JSON-LD.
 * - This module does NOT manage canonical tags.
 *
 * @package BE_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Social settings with defaults.
 *
 * Stored as be_schema_social_settings in the options table.
 *
 * @return array
 */
function be_schema_social_get_settings() {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached;
	}

	$defaults = array(
		// Master enable for social meta.
		'enabled' => '0',
		'dry_run' => '0',
		'twitter_dry_run' => '0',

		// Default images (attachment IDs or URLs depending on UI).
		'default_facebook_image_id' => '',
		'default_twitter_image_id'  => '',
		'default_global_image_id'   => '',
		'global_default_image_alt'  => '',
		'global_images_optional'    => '',
		'global_image_16_9'         => '',
		'global_image_5_4'          => '',
		'global_image_1_1'          => '',
		'global_image_4_5'          => '',
		'global_image_1_1_91'       => '',
		'global_image_9_16'         => '',

		// Facebook additional aspect images.
		'facebook_images_optional' => '',
		'facebook_image_16_9'      => '',
		'facebook_image_5_4'       => '',
		'facebook_image_1_1'       => '',
		'facebook_image_4_5'       => '',
		'facebook_image_1_1_91'    => '',
		'facebook_image_9_16'      => '',

		// Twitter additional aspect images.
		'twitter_images_optional' => '',
		'twitter_image_16_9'      => '',
		'twitter_image_5_4'       => '',
		'twitter_image_1_1'       => '',
		'twitter_image_4_5'       => '',
		'twitter_image_1_1_91'    => '',
		'twitter_image_9_16'      => '',

		// Twitter handles (with or without @, we will normalize).
		'twitter_site'    => '',
		'twitter_creator' => '',

		// Card type: summary or summary_large_image.
		'twitter_card_type' => 'summary_large_image',

		// Optional Facebook app ID.
		'facebook_app_id' => '',
	);

	$settings = get_option( 'be_schema_social_settings', array() );
	$cached   = wp_parse_args( $settings, $defaults );

	return $cached;
}

/**
 * Determine whether social meta should be emitted at all.
 *
 * This is independent from schema JSON-LD; social can be enabled/disabled
 * separately from BE Schema Engine's core schema output.
 *
 * @return bool
 */
function be_schema_social_is_enabled() {
	$settings = be_schema_social_get_settings();

	return ( isset( $settings['enabled'] ) && '1' === (string) $settings['enabled'] );
}

/**
 * Clean a URL of tracking parameters and fragments.
 *
 * - Removes utm_* params
 * - Removes gclid, fbclid
 * - Strips URL fragment (#...)
 *
 * @param string $url URL to clean.
 *
 * @return string
 */
function be_schema_social_clean_url( $url ) {
	$url = (string) $url;
	if ( '' === $url ) {
		return $url;
	}

	$parts = wp_parse_url( $url );
	if ( false === $parts ) {
		return $url;
	}

	$query = array();
	if ( isset( $parts['query'] ) ) {
		parse_str( $parts['query'], $query );
	}

	if ( ! empty( $query ) ) {
		foreach ( $query as $key => $value ) {
			$key_lower = strtolower( $key );

			// Drop tracking params.
			if ( 0 === strpos( $key_lower, 'utm_' ) ) {
				unset( $query[ $key ] );
			} elseif ( in_array( $key_lower, array( 'gclid', 'fbclid' ), true ) ) {
				unset( $query[ $key ] );
			}
		}
	}

	$scheme   = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '';
	$host     = isset( $parts['host'] ) ? $parts['host'] : '';
	$port     = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
	$path     = isset( $parts['path'] ) ? $parts['path'] : '';
	$querystr = ! empty( $query ) ? '?' . http_build_query( $query ) : '';

	// Fragments are intentionally dropped.
	$clean = $scheme . $host . $port . $path . $querystr;

	return $clean;
}

/**
 * Resolve the canonical/primary URL for social meta.
 *
 * - Prefer wp_get_canonical_url() when available.
 * - Fallback to get_permalink() for singular.
 * - Fallback to home_url() otherwise.
 *
 * All outputs are cleaned of tracking params and fragments.
 *
 * @return string
 */
function be_schema_social_get_url() {
	$url = '';

	if ( function_exists( 'wp_get_canonical_url' ) ) {
		$url = wp_get_canonical_url();
	}

	if ( ! $url ) {
		if ( is_singular() ) {
			$url = get_permalink();
		} else {
			$url = home_url( add_query_arg( array(), '' ) );
		}
	}

	return be_schema_social_clean_url( $url );
}

/**
 * Resolve title for social meta.
 *
 * We use the same title WordPress would use for <title>.
 *
 * @return string
 */
function be_schema_social_get_title() {
	if ( function_exists( 'wp_get_document_title' ) ) {
		return wp_get_document_title();
	}

	// Fallback, very conservative.
	return get_bloginfo( 'name', 'display' );
}

/**
 * Resolve description for social meta.
 *
 * - Singular:
 *     - Use excerpt.
 *     - Fallback to content snippet.
 * - Non-singular:
 *     - Use site tagline (description).
 *
 * Always normalized with be_schema_normalize_text().
 *
 * @return string
 */
function be_schema_social_get_description() {
	$max_length = 320;

	if ( is_singular() ) {
		$post = get_post();
		if ( $post ) {
			$excerpt = has_excerpt( $post ) ? $post->post_excerpt : '';
			if ( $excerpt ) {
				return be_schema_normalize_text( $excerpt, $max_length );
			}

			$content = isset( $post->post_content ) ? $post->post_content : '';
			if ( $content ) {
				return be_schema_normalize_text( $content, $max_length );
			}
		}
	}

	// Non-singular or no useful content: site tagline.
	$tagline = get_bloginfo( 'description', 'display' );

	return be_schema_normalize_text( $tagline, $max_length );
}

/**
 * Get the featured image URL for the current post, if any.
 *
 * @return string Empty string if none available.
 */
function be_schema_social_get_featured_image_url() {
	if ( ! is_singular() ) {
		return '';
	}

	$post = get_post();
	if ( ! $post || ! has_post_thumbnail( $post ) ) {
		return '';
	}

	$image_id = get_post_thumbnail_id( $post );
	if ( ! $image_id ) {
		return '';
	}

	$src = wp_get_attachment_image_src( $image_id, 'full' );
	if ( ! $src || empty( $src[0] ) ) {
		return '';
	}

	return (string) $src[0];
}

/**
 * Resolve a default image URL from an attachment ID stored in settings.
 *
 * @param string $id_key Option key name for the attachment ID.
 *
 * @return string
 */
function be_schema_social_get_default_image_url_from_settings( $id_key ) {
	$settings = be_schema_social_get_settings();

	if ( empty( $settings[ $id_key ] ) ) {
		return '';
	}

	$attachment_id = (int) $settings[ $id_key ];
	if ( ! $attachment_id ) {
		return '';
	}

	$src = wp_get_attachment_image_src( $attachment_id, 'full' );
	if ( ! $src || empty( $src[0] ) ) {
		return '';
	}

	return (string) $src[0];
}

/**
 * Resolve the best image URL for OpenGraph.
 *
 * Fallback chain:
 *   1. Featured image (for singular).
 *   2. Default Facebook image.
 *   3. Default global image.
 *
 * @return string
 */
function be_schema_social_get_og_image_url() {
	// 1. Featured image first.
	$featured = be_schema_social_get_featured_image_url();
	if ( $featured ) {
		return $featured;
	}

	// 2. Default Facebook image.
	$fb_default = be_schema_social_get_default_image_url_from_settings( 'default_facebook_image_id' );
	if ( $fb_default ) {
		return $fb_default;
	}

	// 3. Global default.
	$global_default = be_schema_social_get_default_image_url_from_settings( 'default_global_image_id' );
	if ( $global_default ) {
		return $global_default;
	}

	return '';
}

/**
 * Resolve the best image URL for Twitter.
 *
 * Fallback chain:
 *   1. Featured image (for singular).
 *   2. Default Twitter image.
 *   3. Default global image.
 *
 * @return string
 */
function be_schema_social_get_twitter_image_url() {
	// 1. Featured image first.
	$featured = be_schema_social_get_featured_image_url();
	if ( $featured ) {
		return $featured;
	}

	// 2. Default Twitter image.
	$tw_default = be_schema_social_get_default_image_url_from_settings( 'default_twitter_image_id' );
	if ( $tw_default ) {
		return $tw_default;
	}

	// 3. Global default.
	$global_default = be_schema_social_get_default_image_url_from_settings( 'default_global_image_id' );
	if ( $global_default ) {
		return $global_default;
	}

	return '';
}

/**
 * Normalize a Twitter handle to always have a leading '@'.
 *
 * @param string $handle Raw handle.
 *
 * @return string
 */
function be_schema_social_normalize_twitter_handle( $handle ) {
	$handle = trim( (string) $handle );

	if ( '' === $handle ) {
		return '';
	}

	if ( '@' !== substr( $handle, 0, 1 ) ) {
		$handle = '@' . $handle;
	}

	return $handle;
}

/**
 * Determine OG type for the current context.
 *
 * - Singular posts/pages: article
 * - Everything else: website
 *
 * @return string
 */
function be_schema_social_get_og_type() {
	if ( is_singular() ) {
		return 'article';
	}

	return 'website';
}

/**
 * Determine the Twitter card type for the current context.
 *
 * - Uses plugin setting (default summary_large_image).
 *
 * @return string summary|summary_large_image
 */
function be_schema_social_get_twitter_card_type() {
	$settings       = be_schema_social_get_settings();
	$card_type      = isset( $settings['twitter_card_type'] ) ? $settings['twitter_card_type'] : 'summary_large_image';
	$allowed_types  = array( 'summary', 'summary_large_image' );

	if ( ! in_array( $card_type, $allowed_types, true ) ) {
		$card_type = 'summary_large_image';
	}

	return $card_type;
}

/**
 * Output OpenGraph + Twitter meta tags.
 *
 * Hooked into wp_head at priority 5.
 *
 * @return void
 */
function be_schema_output_social_meta() {
	// Never output in admin, AJAX, feeds, or JSON endpoints.
	if ( is_admin() || wp_doing_ajax() || is_feed() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
		return;
	}

	if ( ! be_schema_social_is_enabled() ) {
		return;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	$url         = be_schema_social_get_url();
	$title       = be_schema_social_get_title();
	$description = be_schema_social_get_description();
	$site_name   = get_bloginfo( 'name', 'display' );

	$og_type      = be_schema_social_get_og_type();
	$og_image_url = be_schema_social_get_og_image_url();

	$twitter_image_url = be_schema_social_get_twitter_image_url();
	$twitter_card_type = be_schema_social_get_twitter_card_type();

	$settings        = be_schema_social_get_settings();
	$twitter_site    = be_schema_social_normalize_twitter_handle( $settings['twitter_site'] );
	$twitter_creator = be_schema_social_normalize_twitter_handle( $settings['twitter_creator'] );
	$facebook_app_id = isset( $settings['facebook_app_id'] ) ? trim( (string) $settings['facebook_app_id'] ) : '';

	// Safety toggles.
	$og_dry_run       = ! empty( $settings['dry_run'] ) && '1' === (string) $settings['dry_run'];
	$twitter_dry_run  = ! empty( $settings['twitter_dry_run'] ) && '1' === (string) $settings['twitter_dry_run'];

	// Automatic values before any potential future overrides (for debug).
	$automatic = array(
		'title'       => $title,
		'description' => $description,
		'og_image'    => $og_image_url,
		'tw_image'    => $twitter_image_url,
	);

	// For now, "final" == "automatic"; no per-post overrides are implemented.
	$final = $automatic;

	$og_enabled      = true;
	$twitter_enabled = ! $twitter_dry_run;

	// Debug snapshot.
	if ( function_exists( 'be_schema_is_debug_enabled' ) && be_schema_is_debug_enabled() ) {
		$debug_snapshot = array(
			'url'               => $url,
			'title'             => $final['title'],
			'description'       => $final['description'],
			'site_name'         => $site_name,
			'og_enabled'        => $og_enabled,
			'twitter_enabled'   => $twitter_enabled,
			'og_type'           => $og_type,
			'twitter_card_type' => $twitter_card_type,
			'resolved_images'   => array(
				'og'      => $final['og_image'],
				'twitter' => $final['tw_image'],
			),
			'handles'           => array(
				'twitter_site'    => $twitter_site,
				'twitter_creator' => $twitter_creator,
			),
			'automatic'         => $automatic,
			'final'             => $final,
		);

		error_log( 'BE_SOCIAL_DEBUG ' . wp_json_encode( $debug_snapshot ) );

		set_transient(
			'be_social_last_debug',
			array(
				'time'     => time(),
				'snapshot' => $debug_snapshot,
			),
			DAY_IN_SECONDS
		);
	}

	if ( $og_dry_run || $twitter_dry_run ) {
		$dry_run_snapshot = array(
			'url'               => $url,
			'title'             => $final['title'],
			'description'       => $final['description'],
			'og_type'           => $og_type,
			'twitter_card_type' => $twitter_card_type,
			'og_image'          => $final['og_image'],
			'tw_image'          => $final['tw_image'],
			'og_dry_run'        => $og_dry_run,
			'twitter_dry_run'   => $twitter_dry_run,
		);
		error_log( 'BE_SOCIAL_DRY_RUN ' . wp_json_encode( $dry_run_snapshot ) );
	}

	// Output OG tags unless OpenGraph dry run is enabled.
	if ( $og_enabled && ! $og_dry_run ) {
		echo "\n" . '<meta property="og:title" content="' . esc_attr( $final['title'] ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $final['description'] ) . '" />' . "\n";
		echo '<meta property="og:type" content="' . esc_attr( $og_type ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_attr( $url ) . '" />' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\n";

		if ( $final['og_image'] ) {
			echo '<meta property="og:image" content="' . esc_url( $final['og_image'] ) . '" />' . "\n";
		}

		// Article extras for singular content.
		if ( is_singular() && 'article' === $og_type ) {
			$post = get_post();
			if ( $post ) {
				$published = get_the_date( DATE_W3C, $post );
				$modified  = get_the_modified_date( DATE_W3C, $post );

				if ( $published ) {
					echo '<meta property="article:published_time" content="' . esc_attr( $published ) . '" />' . "\n";
				}
				if ( $modified ) {
					echo '<meta property="article:modified_time" content="' . esc_attr( $modified ) . '" />' . "\n";
				}

				$author_name = get_the_author_meta( 'display_name', $post->post_author );
				if ( $author_name ) {
					echo '<meta property="article:author" content="' . esc_attr( $author_name ) . '" />' . "\n";
				}
			}
		}
	}

	// Optional Facebook app ID.
	if ( $facebook_app_id ) {
		echo '<meta property="fb:app_id" content="' . esc_attr( $facebook_app_id ) . '" />' . "\n";
	}

	// Twitter Card tags (skip when twitter dry run is enabled).
	if ( $twitter_enabled && ! $twitter_dry_run ) {
		echo '<meta name="twitter:card" content="' . esc_attr( $twitter_card_type ) . '" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $final['title'] ) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $final['description'] ) . '" />' . "\n";

		if ( $final['tw_image'] ) {
			echo '<meta name="twitter:image" content="' . esc_url( $final['tw_image'] ) . '" />' . "\n";
		}

		if ( $twitter_site ) {
			echo '<meta name="twitter:site" content="' . esc_attr( $twitter_site ) . '" />' . "\n";
		}

		if ( $twitter_creator ) {
			echo '<meta name="twitter:creator" content="' . esc_attr( $twitter_creator ) . '" />' . "\n";
		}
	}
}

// Hook social meta into the head early, but after core WordPress pieces.
add_action( 'wp_head', 'be_schema_output_social_meta', 5 );
