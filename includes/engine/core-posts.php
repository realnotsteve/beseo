<?php
/**
 * BE Schema Engine - Blog posts (BlogPosting).
 *
 * Responsibilities:
 * - Emit JSON-LD BlogPosting schema for eligible single posts.
 * - Emit a paired WebPage node whose @id is the post permalink.
 * - Respect:
 *     - Global disable via be_schema_globally_disabled().
 *     - Per-page / Elementor disable via be_schema_is_disabled_for_current_page().
 *     - Post-type eligibility via be_schema_is_singular_eligible().
 * - Use site-level entities (Person, Publisher/Organisation) via @id references.
 *
 * @package BE_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve the primary image URL for a BlogPosting:
 *
 * Fallback chain:
 *   1. Featured image of the post.
 *   2. be_schema_get_default_image_url() helper if present.
 *
 * @param int $post_id Post ID.
 *
 * @return string Empty string if none available.
 */
function be_schema_get_post_image_url( $post_id ) {
	$post_id = (int) $post_id;

	if ( $post_id <= 0 ) {
		return '';
	}

	if ( has_post_thumbnail( $post_id ) ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
		if ( $src && ! empty( $src[0] ) ) {
			return (string) $src[0];
		}
	}

	// Optional: global default image helper, if defined elsewhere.
	if ( function_exists( 'be_schema_get_default_image_url' ) ) {
		$fallback = be_schema_get_default_image_url();
		if ( $fallback ) {
			return (string) $fallback;
		}
	}

	return '';
}

/**
 * Build the WebPage node that represents the post’s page.
 *
 * @param WP_Post $post The post object.
 *
 * @return array
 */
function be_schema_build_post_webpage_node( $post ) {
	$post_id = (int) $post->ID;
	$url     = get_permalink( $post_id );
	$language = get_bloginfo( 'language' );

	$name = get_the_title( $post );
	if ( '' === $name ) {
		$name = get_bloginfo( 'name', 'display' );
	}

	$description = '';
	$max_length  = 320;

	if ( has_excerpt( $post ) ) {
		$description = be_schema_normalize_text( $post->post_excerpt, $max_length );
	} elseif ( ! empty( $post->post_content ) ) {
		$description = be_schema_normalize_text( $post->post_content, $max_length );
	}

	$webpage_id = $url; // For posts, we use the permalink as the @id for WebPage.

	$node = array(
		'@type' => 'WebPage',
		'@id'   => $webpage_id,
		'url'   => $url,
		'name'  => $name,
	);

	if ( $language ) {
		$node['inLanguage'] = $language;
	}

	if ( $description ) {
		$node['description'] = $description;
	}

	// Link to site-level WebSite node by @id.
	$node['isPartOf'] = array(
		'@id' => be_schema_id( 'website' ),
	);

	// about → Person / Organisation by @id, if present.
	$node['about'] = array(
		array( '@id' => be_schema_id( 'organisation' ) ),
		array( '@id' => be_schema_id( 'person' ) ),
	);

	$image_url = be_schema_get_post_image_url( $post_id );
	if ( $image_url ) {
		$node['primaryImageOfPage'] = array(
			'@type' => 'ImageObject',
			'url'   => $image_url,
		);
	}

	return $node;
}

/**
 * Resolve publisher @id for a BlogPosting.
 *
 * Preference order:
 *   1. Explicit Publisher entity (#publisher).
 *   2. Organisation entity (#organisation).
 *   3. Person entity (#person).
 *
 * We return an array with @id, suitable for embedding directly in the
 * BlogPosting node.
 *
 * @return array|null
 */
function be_schema_get_publisher_reference() {
	// In a more advanced implementation, you might inspect settings to see
	// whether a custom publisher org is configured or enabled. For now, we
	// rely on stable @ids.
	$publisher_id    = be_schema_id( 'publisher' );
	$organisation_id = be_schema_id( 'organisation' );
	$person_id       = be_schema_id( 'person' );

	// Very conservative: always return something, but in an order that
	// respects your design (publisher → organisation → person).
	// If no matching node is ultimately emitted elsewhere, it’s still safe
	// to reference by @id; the graph will simply lack that node.
	if ( $publisher_id ) {
		return array( '@id' => $publisher_id );
	}

	if ( $organisation_id ) {
		return array( '@id' => $organisation_id );
	}

	if ( $person_id ) {
		return array( '@id' => $person_id );
	}

	return null;
}

/**
 * Build a BlogPosting node for the current post.
 *
 * @param WP_Post $post The post object.
 *
 * @return array
 */
function be_schema_build_blogposting_node( $post ) {
	$post_id = (int) $post->ID;
	$url     = get_permalink( $post_id );
	$language = get_bloginfo( 'language' );

	$headline = get_the_title( $post );
	if ( '' === $headline ) {
		$headline = get_bloginfo( 'name', 'display' );
	}
	// Keep headline within common validator limits (~110 chars).
	if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
		if ( mb_strlen( $headline ) > 110 ) {
			$headline = mb_substr( $headline, 0, 107 ) . '…';
		}
	} elseif ( strlen( $headline ) > 110 ) {
		$headline = substr( $headline, 0, 107 ) . '…';
	}

	$max_length  = 320;
	$description = '';

	if ( has_excerpt( $post ) ) {
		$description = be_schema_normalize_text( $post->post_excerpt, $max_length );
	} elseif ( ! empty( $post->post_content ) ) {
		$description = be_schema_normalize_text( $post->post_content, $max_length );
	}

	$date_published = get_the_date( DATE_W3C, $post );
	$date_modified  = get_the_modified_date( DATE_W3C, $post );

	$image_objects = array();
	$image_url     = '';
	if ( has_post_thumbnail( $post_id ) ) {
		$thumb_id   = get_post_thumbnail_id( $post_id );
		$image_obj  = be_schema_engine_build_image_object( $thumb_id );
		if ( $image_obj ) {
			$image_objects[] = $image_obj;
			$image_url       = isset( $image_obj['contentUrl'] ) ? $image_obj['contentUrl'] : '';
		}
	}

	if ( ! $image_url ) {
		$image_url = be_schema_get_post_image_url( $post_id );
		if ( $image_url ) {
			$image_objects[] = array(
				'@type'      => 'ImageObject',
				'url'        => $image_url,
				'contentUrl' => $image_url,
			);
		}
	}

	// Stable @id for the article node.
	$article_id = be_schema_id( 'article-' . $post_id );

	// Author (per-post) with URL/image when available.
	$author_id   = (int) $post->post_author;
	$author_name = get_the_author_meta( 'display_name', $author_id );
	$author_url  = get_author_posts_url( $author_id );
	$author_img  = get_avatar_url( $author_id, array( 'size' => 256 ) );
	$author_node = array(
		'@type' => 'Person',
		'name'  => $author_name ? $author_name : get_bloginfo( 'name', 'display' ),
	);
	if ( $author_url ) {
		$author_node['@id'] = $author_url . '#author';
		$author_node['url'] = $author_url;
	} else {
		$author_node['@id'] = be_schema_id( 'person' );
	}
	if ( $author_img ) {
		$author_node['image'] = array(
			'@type' => 'ImageObject',
			'url'   => $author_img,
		);
	}

	// Editor (last editor) if available.
	$editor_id = (int) get_post_meta( $post_id, '_edit_last', true );
	$editor_node = null;
	if ( $editor_id ) {
		$editor_name = get_the_author_meta( 'display_name', $editor_id );
		$editor_url  = get_author_posts_url( $editor_id );
		$editor_img  = get_avatar_url( $editor_id, array( 'size' => 256 ) );
		$editor_node = array(
			'@type' => 'Person',
			'name'  => $editor_name ? $editor_name : '',
		);
		if ( $editor_url ) {
			$editor_node['@id'] = $editor_url . '#editor';
			$editor_node['url'] = $editor_url;
		}
		if ( $editor_img ) {
			$editor_node['image'] = array(
				'@type' => 'ImageObject',
				'url'   => $editor_img,
			);
		}
	}

	// Categories → articleSection.
	$article_section = '';
	$categories      = get_the_category( $post_id );
	if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
		$article_section = $categories[0]->name;
	}

	// Tags → keywords.
	$tag_terms = get_the_tags( $post_id );
	$keywords  = array();
	if ( ! empty( $tag_terms ) && ! is_wp_error( $tag_terms ) ) {
		foreach ( $tag_terms as $tag_term ) {
			$keywords[] = $tag_term->name;
		}
	}

	// VideoObject attachments.
	$video_objects = array();
	$video_children = get_children(
		array(
			'post_type'      => 'attachment',
			'post_parent'    => $post_id,
			'post_mime_type' => 'video',
			'numberposts'    => 5,
			'fields'         => 'ids',
		)
	);
	if ( $video_children ) {
		$thumb_fallback = $image_url ? $image_url : be_schema_get_post_image_url( $post_id );
		$org_id         = be_schema_id( 'organisation' );
		foreach ( $video_children as $vid_id ) {
			$vid_url = wp_get_attachment_url( $vid_id );
			if ( ! $vid_url ) {
				continue;
			}
			$vid_post   = get_post( $vid_id );
			$vid_meta   = wp_get_attachment_metadata( $vid_id );
			$uploadDate = $vid_post ? $vid_post->post_date_gmt : '';
			if ( ! $uploadDate && $vid_post ) {
				$uploadDate = $vid_post->post_date;
			}
			$uploadDate = $uploadDate ? gmdate( DATE_W3C, strtotime( $uploadDate ) ) : '';

			$duration_iso = '';
			if ( is_array( $vid_meta ) ) {
				if ( isset( $vid_meta['length'] ) && is_numeric( $vid_meta['length'] ) ) {
					$seconds      = (int) $vid_meta['length'];
					$duration_iso = 'PT' . $seconds . 'S';
				} elseif ( isset( $vid_meta['length_formatted'] ) ) {
					// Convert mm:ss or hh:mm:ss to PT#M#S form.
					$parts = array_map( 'intval', explode( ':', $vid_meta['length_formatted'] ) );
					if ( count( $parts ) === 3 ) {
						list( $hours, $minutes, $seconds_part ) = $parts;
						$duration_iso = 'PT' . $hours . 'H' . $minutes . 'M' . $seconds_part . 'S';
					} elseif ( count( $parts ) === 2 ) {
						list( $minutes, $seconds_part ) = $parts;
						$duration_iso = 'PT' . $minutes . 'M' . $seconds_part . 'S';
					}
				}
			}

			$views_meta = (int) get_post_meta( $vid_id, 'post_views_count', true );

			$video_obj = array(
				'@type'         => 'VideoObject',
				'name'          => $vid_post ? get_the_title( $vid_post ) : $headline,
				'description'   => $vid_post ? be_schema_normalize_text( $vid_post->post_content, 320 ) : $description,
				'contentUrl'    => $vid_url,
				'embedUrl'      => $vid_url,
				'uploadDate'    => $uploadDate,
				'isFamilyFriendly' => true,
			);
			if ( $thumb_fallback ) {
				$video_obj['thumbnailUrl'] = $thumb_fallback;
			}
			if ( $duration_iso ) {
				$video_obj['duration'] = $duration_iso;
			}
			if ( $views_meta > 0 ) {
				$video_obj['interactionStatistic'] = array(
					'@type'                => 'InteractionCounter',
					'interactionType'      => array( '@type' => 'WatchAction' ),
					'userInteractionCount' => $views_meta,
				);
			}
			if ( $org_id ) {
				$video_obj['publisher'] = array(
					'@type' => 'Organization',
					'@id'   => $org_id,
				);
			}
			$video_objects[] = $video_obj;
		}
	}

	$node = array(
		'@type'            => 'BlogPosting',
		'@id'              => $article_id,
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => $url,
		),
		'headline'         => $headline,
		'url'              => $url,
	);

	if ( $language ) {
		$node['inLanguage'] = $language;
	}

	if ( $description ) {
		$node['description'] = $description;
	}

	if ( $date_published ) {
		$node['datePublished'] = $date_published;
	}
	if ( $date_modified ) {
		$node['dateModified'] = $date_modified;
	}

	$node['author'] = $author_node;

	if ( $editor_node ) {
		$node['editor'] = $editor_node;
	}

	// Publisher: resolved via helper.
	$publisher_ref = be_schema_get_publisher_reference();
	if ( $publisher_ref ) {
		$node['publisher'] = $publisher_ref;
	}

	if ( $image_objects ) {
		$node['image'] = $image_objects;
	}

	if ( $video_objects ) {
		$node['video'] = $video_objects;
	}

	if ( $article_section ) {
		$node['articleSection'] = $article_section;
	}

	if ( $keywords ) {
		$node['keywords'] = $keywords;
	}

	$node['speakable'] = array(
		'@type'       => 'SpeakableSpecification',
		'cssSelector' => array(
			'header h1',
			'article h1',
			'article h2',
			'title',
		),
	);

	return $node;
}

/**
 * Emit BlogPosting + WebPage schema for eligible singular posts.
 *
 * Respects:
 * - Global disable.
 * - Singular eligibility via be_schema_is_singular_eligible().
 * - Per-page / Elementor gating via be_schema_is_disabled_for_current_page().
 *
 * @return void
 */
function be_schema_output_post_schema() {
	// Never emit in admin, AJAX, feeds, or JSON endpoints.
	if ( is_admin() || wp_doing_ajax() || is_feed() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
		return;
	}

	if ( function_exists( 'be_schema_globally_disabled' ) && be_schema_globally_disabled() ) {
		if ( function_exists( 'be_schema_debug_event' ) ) {
			be_schema_debug_event(
				'post_schema_skipped',
				array(
					'reason' => 'global_disabled',
					'url'    => home_url( add_query_arg( array(), '' ) ),
				)
			);
		}
		return;
	}

	if ( ! is_singular() ) {
		return;
	}

	$post = get_post();
	if ( ! $post ) {
		return;
	}

	$post_id = (int) $post->ID;

	if ( function_exists( 'be_schema_is_singular_eligible' ) && ! be_schema_is_singular_eligible() ) {
		if ( function_exists( 'be_schema_debug_event' ) ) {
			be_schema_debug_event(
				'post_schema_skipped',
				array(
					'reason'  => 'post_type_not_eligible',
					'post_id' => $post_id,
					'url'     => get_permalink( $post_id ),
					'post_type' => get_post_type( $post ),
				)
			);
		}
		return;
	}

	if ( function_exists( 'be_schema_is_disabled_for_current_page' ) && be_schema_is_disabled_for_current_page() ) {
		if ( function_exists( 'be_schema_debug_event' ) ) {
			be_schema_debug_event(
				'post_schema_skipped',
				array(
					'reason'  => 'per_page_or_elementor_disabled',
					'post_id' => $post_id,
					'url'     => get_permalink( $post_id ),
				)
			);
		}
		return;
	}

	if ( function_exists( 'be_schema_debug_event' ) ) {
		be_schema_debug_event(
			'schema_graph_attempt',
			array(
				'context' => 'post',
				'post_id' => $post_id,
				'url'     => get_permalink( $post_id ),
			)
		);
	}

	if ( function_exists( 'be_schema_is_dry_run' ) && be_schema_is_dry_run() ) {
		if ( function_exists( 'be_schema_log_dry_run' ) ) {
			be_schema_log_dry_run(
				'post',
				array(
					'post_id' => $post_id,
					'url'     => get_permalink( $post_id ),
				)
			);
		}
		return;
	}

	$webpage    = be_schema_build_post_webpage_node( $post );
	$blogposting = be_schema_build_blogposting_node( $post );

	$graph = array(
		$webpage,
		$blogposting,
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
				'context' => 'post',
				'post_id' => $post_id,
				'url'     => get_permalink( $post_id ),
			)
		);
	}
}
