<?php
/**
 * BE Schema Engine - Special pages (homepage + Contact/About/Privacy/Accessibility).
 *
 * Responsibilities:
 * - Emit JSON-LD for:
 *     - Homepage WebPage node, linking to site-level entities by @id.
 *     - Special pages: ContactPage, AboutPage, PrivacyPolicy, WebPage (accessibility).
 * - Respect global disable and per-page/Elementor gating rules.
 * - Use Elementor page type meta (be_schema_page_type) to identify special pages.
 * - Emit structured debug events and reason-coded skips.
 *
 * @package BE_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper: get Elementor page settings for a post.
 *
 * @param int $post_id Post ID.
 *
 * @return array
 */
function be_schema_get_elementor_page_settings( $post_id ) {
	$settings = get_post_meta( $post_id, '_elementor_page_settings', true );
	if ( empty( $settings ) || ! is_array( $settings ) ) {
		return array();
	}

	return $settings;
}

/**
 * Helper: get the Elementor page type for a post.
 *
 * Expected values:
 *   - 'contact'
 *   - 'about'
 *   - 'privacy-policy'
 *   - 'accessibility-statement'
 *
 * @param int $post_id Post ID.
 *
 * @return string Empty string if none set.
 */
function be_schema_get_elementor_page_type( $post_id ) {
	$settings = be_schema_get_elementor_page_settings( $post_id );
	if ( empty( $settings ) ) {
		return '';
	}

	if ( empty( $settings['be_schema_page_type'] ) ) {
		return '';
	}

	return (string) $settings['be_schema_page_type'];
}

/**
 * Helper: get the primary image URL for a given page (special page / homepage).
 *
 * For now, we conservatively use:
 *   - Featured image, if one exists.
 *
 * @param int $post_id Post ID.
 *
 * @return string Empty string if none.
 */
function be_schema_get_page_primary_image_url( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return '';
	}

	if ( ! has_post_thumbnail( $post_id ) ) {
		return '';
	}

	$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
	if ( ! $src || empty( $src[0] ) ) {
		return '';
	}

	return (string) $src[0];
}

/**
 * Build a WebPage-like node for a given post and schema.org type.
 *
 * For special pages, we use specific subtypes:
 *   - ContactPage
 *   - AboutPage
 *   - PrivacyPolicy
 *   - WebPage (for accessibility statement or generic)
 *
 * For the homepage, we build a WebPage that links to the WebSite and
 * site entities by @id.
 *
 * @param int    $post_id   Post ID (0 for homepage when not a static page).
 * @param string $schema_type Schema.org WebPage subtype.
 * @param array  $args      Additional arguments:
 *     - 'use_home_url' (bool) force URL to home_url().
 *     - 'id_suffix'    (string) suffix for be_schema_id() if non-empty.
 *
 * @return array WebPage node.
 */
function be_schema_build_webpage_node( $post_id, $schema_type, array $args = array() ) {
	$post_id     = (int) $post_id;
	$schema_type = $schema_type ? (string) $schema_type : 'WebPage';

	$use_home_url = ! empty( $args['use_home_url'] );
	$id_suffix    = isset( $args['id_suffix'] ) ? (string) $args['id_suffix'] : '';

	if ( $use_home_url ) {
		$url = home_url( '/' );
	} elseif ( $post_id > 0 ) {
		$url = get_permalink( $post_id );
	} else {
		$url = home_url( add_query_arg( array(), '' ) );
	}

	$name = '';
	if ( $post_id > 0 ) {
		$post = get_post( $post_id );
		if ( $post ) {
			$name = get_the_title( $post );
		}
	}
	if ( '' === $name ) {
		$name = get_bloginfo( 'name', 'display' );
	}

	$description = '';
	if ( $post_id > 0 ) {
		$post = get_post( $post_id );
		if ( $post ) {
			$excerpt = has_excerpt( $post ) ? $post->post_excerpt : '';
			if ( $excerpt ) {
				$description = be_schema_normalize_text( $excerpt, 320 );
			} elseif ( ! empty( $post->post_content ) ) {
				$description = be_schema_normalize_text( $post->post_content, 320 );
			}
		}
	}
	if ( '' === $description ) {
		// Fallback to site tagline if nothing useful exists on the page.
		$description = be_schema_normalize_text( get_bloginfo( 'description', 'display' ), 320 );
	}

	$image_url = '';
	if ( $post_id > 0 ) {
		$image_url = be_schema_get_page_primary_image_url( $post_id );
	}

	// Build @id.
	if ( '' !== $id_suffix ) {
		$id = be_schema_id( $id_suffix );
	} elseif ( $post_id > 0 ) {
		// Use the permalink with a #webpage suffix for stability.
		$id = trailingslashit( $url ) . '#webpage';
	} else {
		$id = be_schema_id( 'webpage' );
	}

	$webpage = array(
		'@type' => $schema_type,
		'@id'   => $id,
		'url'   => $url,
		'name'  => $name,
	);

	if ( $description ) {
		$webpage['description'] = $description;
	}

	// Link homepage/special pages back to the site WebSite node, using a stable @id.
	$webpage['isPartOf'] = array(
		'@id' => be_schema_id( 'website' ),
	);

	// For home and special pages, we can point about → Organisation / Person by @id.
	$webpage['about'] = array(
		array( '@id' => be_schema_id( 'organisation' ) ),
		array( '@id' => be_schema_id( 'person' ) ),
	);

	if ( $image_url ) {
		$webpage['primaryImageOfPage'] = array(
			'@type' => 'ImageObject',
			'url'   => $image_url,
		);
	}

	return $webpage;
}

/**
 * Output homepage schema.
 *
 * Emits a WebPage node for the front page, linking to the WebSite and
 * other site-level entities by @id. Site entities themselves are expected
 * to be emitted by core-site-entities.php.
 *
 * Respects:
 * - Global disable.
 * - Per-page disable for static front pages (but does NOT require Elementor).
 *
 * @return void
 */
function be_schema_output_homepage_schema() {
	// Never emit schema in admin, AJAX, feeds, or JSON endpoints.
	if ( is_admin() || wp_doing_ajax() || is_feed() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
		return;
	}

	if ( function_exists( 'be_schema_globally_disabled' ) && be_schema_globally_disabled() ) {
		if ( function_exists( 'be_schema_debug_event' ) ) {
			be_schema_debug_event(
				'homepage_schema_skipped',
				array(
					'reason' => 'global_disabled',
					'url'    => home_url( '/' ),
				)
			);
		}
		return;
	}

	if ( ! is_front_page() ) {
		if ( function_exists( 'be_schema_debug_event' ) ) {
			be_schema_debug_event(
				'homepage_schema_skipped',
				array(
					'reason' => 'not_front_page',
					'url'    => home_url( '/' ),
				)
			);
		}
		return;
	}

	if ( function_exists( 'be_schema_debug_event' ) ) {
		be_schema_debug_event(
			'schema_graph_attempt',
			array(
				'context' => 'homepage',
				'url'     => home_url( '/' ),
			)
		);
	}

	if ( function_exists( 'be_schema_is_dry_run' ) && be_schema_is_dry_run() ) {
		if ( function_exists( 'be_schema_log_dry_run' ) ) {
			be_schema_log_dry_run(
				'homepage',
				array(
					'url' => home_url( '/' ),
				)
			);
		}
		return;
	}

	// If the front page is a static page, respect a per-page disable meta.
	$post_id = 0;
	if ( is_singular() ) {
		$post = get_post();
		if ( $post ) {
			$post_id = (int) $post->ID;

			$disable_meta = get_post_meta( $post_id, '_be_schema_disable', true );
			if ( (string) $disable_meta === '1' ) {
				if ( function_exists( 'be_schema_debug_event' ) ) {
					be_schema_debug_event(
						'homepage_schema_skipped',
						array(
							'reason'  => 'per_page_disabled',
							'post_id' => $post_id,
							'url'     => get_permalink( $post_id ),
						)
					);
				}
				return;
			}
		}
	}

	// Build homepage WebPage node.
	$webpage = be_schema_build_webpage_node(
		$post_id,
		'WebPage',
		array(
			'use_home_url' => true,
			'id_suffix'    => 'homepage',
		)
	);

	$graph = array(
		$webpage,
	);

	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $payload ) . '</script>' . "\n";

	if ( function_exists( 'be_schema_debug_event' ) ) {
		be_schema_debug_event(
			'schema_graph_emitted',
			array(
				'context' => 'homepage',
				'url'     => home_url( '/' ),
			)
		);
	}
}

/**
 * Output schema for special pages:
 *
 * - ContactPage
 * - AboutPage
 * - PrivacyPolicy
 * - Accessibility (generic WebPage)
 *
 * Detection is based on Elementor page type meta:
 *   be_schema_page_type in _elementor_page_settings.
 *
 * Respects:
 * - Global disable.
 * - Per-page + Elementor gating via be_schema_is_disabled_for_current_page().
 *
 * @return void
 */
function be_schema_output_special_page_schema() {
	// Never emit schema in admin, AJAX, feeds, or JSON endpoints.
	if ( is_admin() || wp_doing_ajax() || is_feed() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
		return;
	}

	if ( function_exists( 'be_schema_globally_disabled' ) && be_schema_globally_disabled() ) {
		if ( function_exists( 'be_schema_debug_event' ) ) {
			be_schema_debug_event(
				'special_page_schema_skipped',
				array(
					'reason' => 'global_disabled',
					'url'    => home_url( add_query_arg( array(), '' ) ),
				)
			);
		}
		return;
	}

	if ( ! is_singular() ) {
		// Special pages are always singular.
		return;
	}

	$post = get_post();
	if ( ! $post ) {
		return;
	}

	$post_id = (int) $post->ID;

	if ( function_exists( 'be_schema_is_disabled_for_current_page' ) && be_schema_is_disabled_for_current_page() ) {
		if ( function_exists( 'be_schema_debug_event' ) ) {
			be_schema_debug_event(
				'special_page_schema_skipped',
				array(
					'reason'  => 'per_page_or_elementor_disabled',
					'post_id' => $post_id,
					'url'     => get_permalink( $post_id ),
				)
			);
		}
		return;
	}

	$page_type = be_schema_get_elementor_page_type( $post_id );

	// Map Elementor page_type values to schema.org types.
	$type_map = array(
		'contact'                => 'ContactPage',
		'about'                  => 'AboutPage',
		'privacy-policy'         => 'PrivacyPolicy',
		'accessibility-statement'=> 'WebPage',
	);

	if ( empty( $page_type ) || ! isset( $type_map[ $page_type ] ) ) {
		if ( function_exists( 'be_schema_debug_event' ) ) {
			be_schema_debug_event(
				'special_page_schema_skipped',
				array(
					'reason'   => 'no_special_page_type',
					'post_id'  => $post_id,
					'url'      => get_permalink( $post_id ),
					'metadata' => array(
						'page_type' => $page_type,
					),
				)
			);
		}
		return;
	}

	if ( function_exists( 'be_schema_debug_event' ) ) {
		be_schema_debug_event(
			'schema_graph_attempt',
			array(
				'context'   => 'special_page',
				'page_type' => $page_type,
				'post_id'   => $post_id,
				'url'       => get_permalink( $post_id ),
			)
		);
	}

	if ( function_exists( 'be_schema_is_dry_run' ) && be_schema_is_dry_run() ) {
		if ( function_exists( 'be_schema_log_dry_run' ) ) {
			be_schema_log_dry_run(
				'special_page',
				array(
					'page_type' => $page_type,
					'post_id'   => $post_id,
				)
			);
		}
		return;
	}

	$schema_type = $type_map[ $page_type ];

	$webpage = be_schema_build_webpage_node(
		$post_id,
		$schema_type,
		array(
			'use_home_url' => false,
			'id_suffix'    => 'page-' . $post_id,
		)
	);

	$graph = array(
		$webpage,
	);

	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $payload ) . '</script>' . "\n";

	if ( function_exists( 'be_schema_debug_event' ) ) {
		be_schema_debug_event(
			'schema_graph_emitted',
			array(
				'context'   => 'special_page',
				'page_type' => $page_type,
				'post_id'   => $post_id,
				'url'       => get_permalink( $post_id ),
			)
		);
	}
}
