<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Output BlogPosting schema for single posts.
 *
 * Conditions:
 * - Global schema enabled.
 * - is_singular( 'post' ).
 * - be_schema_is_singular_eligible() returns true.
 * - Not disabled for current page (page-level safety).
 *
 * Logic:
 * - @id            = post_permalink#article
 * - headline       = post title
 * - description    = post excerpt OR first ~300 chars of stripped content
 * - datePublished  = get_post_time( 'c', true )   (UTC)
 * - dateModified   = get_post_modified_time( 'c', true ) (UTC)
 * - url            = post permalink
 * - mainEntityOfPage = { "@type": "WebPage", "@id": post_permalink }
 * - image:
 *      featured image (full URL) if set,
 *      else site logo URL (org_logo) if available
 * - author:
 *      if site Person entity exists → { "@id": "#person" }
 *      else inline Person with name = post author display name
 * - publisher:
 *      if site publisher entity exists → { "@id": publisher_id }
 *      else if Organisation exists     → { "@id": "#organization" }
 *
 * Output:
 * - Single <script type="application/ld+json"> with:
 *   {
 *     "@context": "https://schema.org",
 *     "@graph": [
 *       Person?, Organisation?, publisher node?, logo?, publisher_logo?, BlogPosting
 *     ]
 *   }
 */
function be_schema_output_post_schema() {
    if ( be_schema_globally_disabled() ) {
        return;
    }

    if ( ! is_singular( 'post' ) ) {
        return;
    }

    if ( ! be_schema_is_singular_eligible() ) {
        return;
    }

    if ( be_schema_is_disabled_for_current_page() ) {
        return;
    }

    // Avoid output in feeds / REST / embeds as an extra safeguard.
    if ( is_feed() || is_robots() || is_embed() ) {
        return;
    }

    $post = get_post();
    if ( ! $post ) {
        return;
    }

    $post_id   = $post->ID;
    $permalink = get_permalink( $post );
    if ( ! $permalink ) {
        return;
    }

    $site_url  = trailingslashit( home_url() );
    $entities  = be_schema_get_site_entities();
    $settings  = be_schema_engine_get_settings();

    /**
     * Build BlogPosting node
     * ------------------------------------------------------------------
     */

    // Headline.
    $headline = get_the_title( $post );
    if ( '' === $headline ) {
        $headline = get_bloginfo( 'name', 'display' );
    }

    // Description: excerpt or first ~300 chars of stripped content.
    if ( has_excerpt( $post ) ) {
        $raw_desc = get_the_excerpt( $post );
    } else {
        $raw_content = get_post_field( 'post_content', $post );
        $raw_desc    = $raw_content;
    }

    if ( function_exists( 'be_schema_normalize_text' ) ) {
        $description = be_schema_normalize_text( $raw_desc, 300 );
    } else {
        // Fallback normalization (in case file order ever changes).
        $description = wp_strip_all_tags( (string) $raw_desc );
        $description = preg_replace( '/\s+/', ' ', $description );
        $description = trim( $description );
        if ( strlen( $description ) > 300 ) {
            $description = substr( $description, 0, 300 );
            $last_space  = strrpos( $description, ' ' );
            if ( false !== $last_space && $last_space > 0 ) {
                $description = substr( $description, 0, $last_space );
            }
        }
    }

    // Dates in ISO 8601 (UTC).
    $date_published = get_post_time( 'c', true, $post );          // GMT = true
    $date_modified  = get_post_modified_time( 'c', true, $post ); // GMT = true

    // Image: featured or site logo.
    $image_url = '';

    if ( has_post_thumbnail( $post ) ) {
        $thumb_id = get_post_thumbnail_id( $post_id );
        if ( $thumb_id ) {
            $img = wp_get_attachment_image_src( $thumb_id, 'full' );
            if ( is_array( $img ) && ! empty( $img[0] ) ) {
                $image_url = $img[0];
            }
        }
    }

    if ( ! $image_url && ! empty( $settings['org_logo'] ) ) {
        $image_url = $settings['org_logo'];
    }

    // Author: prefer site-level Person entity, else inline Person.
    $author = null;

    if ( isset( $entities['person'] ) && is_array( $entities['person'] ) && isset( $entities['person']['@id'] ) ) {
        $author = array(
            '@id' => $entities['person']['@id'],
        );
    } else {
        $author_name = get_the_author_meta( 'display_name', $post->post_author );
        if ( '' === $author_name ) {
            $author_name = get_bloginfo( 'name', 'display' );
        }

        $author = array(
            '@type' => 'Person',
            'name'  => $author_name,
        );
    }

    // Publisher: use site-level publisher if defined, else Organisation.
    $publisher_ref      = null;
    $publisher_fullnode = null;

    if ( isset( $entities['publisher'] ) && is_array( $entities['publisher'] ) ) {
        if ( isset( $entities['publisher']['@id'] ) ) {
            $publisher_ref = array( '@id' => $entities['publisher']['@id'] );
        }

        // If this is a full node (custom publisher Organisation with @type),
        // we’ll include it in the @graph as well.
        if ( isset( $entities['publisher']['@type'] ) ) {
            $publisher_fullnode = $entities['publisher'];
        }
    } elseif ( isset( $entities['organization'] ) && is_array( $entities['organization'] ) && isset( $entities['organization']['@id'] ) ) {
        $publisher_ref = array(
            '@id' => $entities['organization']['@id'],
        );
    }

    // Build BlogPosting @id and WebPage mainEntityOfPage.
    $article_id   = $permalink . '#article';
    $webpage_id   = $permalink;
    $blogposting  = array(
        '@type'            => 'BlogPosting',
        '@id'              => $article_id,
        'headline'         => $headline,
        'url'              => $permalink,
        'mainEntityOfPage' => array(
            '@type' => 'WebPage',
            '@id'   => $webpage_id,
        ),
    );

    if ( $description !== '' ) {
        $blogposting['description'] = $description;
    }

    if ( $date_published ) {
        $blogposting['datePublished'] = $date_published;
    }

    if ( $date_modified ) {
        $blogposting['dateModified'] = $date_modified;
    }

    if ( $image_url ) {
        $blogposting['image'] = $image_url;
    }

    if ( $author ) {
        $blogposting['author'] = $author;
    }

    if ( $publisher_ref ) {
        $blogposting['publisher'] = $publisher_ref;
    }

    /**
     * Filter the BlogPosting node before it is added to the @graph.
     *
     * @param array  $blogposting The BlogPosting node.
     * @param WP_Post $post       The post object.
     */
    $blogposting = apply_filters( 'be_schema_blogposting_node', $blogposting, $post );

    /**
     * Build @graph
     * ------------------------------------------------------------------
     *
     * Includes:
     * - Person (if any)
     * - Organisation (if any)
     * - Publisher node (if distinct full node)
     * - logo (#logo)
     * - publisher_logo (#publisher-logo)
     * - BlogPosting
     */

    $graph = array();

    // Person node.
    if ( isset( $entities['person'] ) && is_array( $entities['person'] ) && ! empty( $entities['person'] ) ) {
        $graph[] = $entities['person'];
    }

    // Organisation node.
    if ( isset( $entities['organization'] ) && is_array( $entities['organization'] ) && ! empty( $entities['organization'] ) ) {
        $graph[] = $entities['organization'];
    }

    // Logo (#logo).
    if ( isset( $entities['logo'] ) && is_array( $entities['logo'] ) && ! empty( $entities['logo'] ) ) {
        $graph[] = $entities['logo'];
    }

    // Publisher logo (#publisher-logo).
    if ( isset( $entities['publisher_logo'] ) && is_array( $entities['publisher_logo'] ) && ! empty( $entities['publisher_logo'] ) ) {
        $graph[] = $entities['publisher_logo'];
    }

    // Publisher full node, only when it’s a distinct entity (custom publisher).
    if ( $publisher_fullnode && is_array( $publisher_fullnode ) ) {
        $graph[] = $publisher_fullnode;
    }

    // Finally, the BlogPosting itself.
    $graph[] = $blogposting;

    /**
     * Allow final filtering of the entire BlogPosting @graph.
     *
     * @param array  $graph      The @graph nodes.
     * @param WP_Post $post      The post object.
     * @param array  $entities   Site entities from be_schema_get_site_entities().
     */
    $graph = apply_filters( 'be_schema_blogposting_graph', $graph, $post, $entities );

    // Collect for debug logging.
    be_schema_debug_collect( $graph );

    $output = array(
        '@context' => 'https://schema.org',
        '@graph'   => $graph,
    );

    echo '<script type="application/ld+json">' . wp_json_encode( $output ) . '</script>' . "\n";
}