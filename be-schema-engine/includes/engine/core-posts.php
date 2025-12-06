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
 *      Person#person
 * - publisher:
 *      Publisher (custom), Organisation, or Person, depending on site entities.
 */
function be_add_post_schema() {
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

    $post = get_post();
    if ( ! $post instanceof WP_Post ) {
        return;
    }

    $post_id   = $post->ID;
    $permalink = get_permalink( $post_id );
    if ( ! $permalink ) {
        return;
    }

    // Site/global entities.
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

    // Dates.
    $date_published = get_post_time( 'c', true, $post );
    $date_modified  = get_post_modified_time( 'c', true, $post );

    // Author: Person#person if available.
    $author = null;
    if ( ! empty( $entities['person'] ) && is_array( $entities['person'] ) && ! empty( $entities['person']['@id'] ) ) {
        $author = array(
            '@id' => $entities['person']['@id'],
        );
    }

    // Publisher reference: Publisher node, Organization, or Person.
    $publisher_ref = null;
    if ( ! empty( $entities['publisher'] ) && is_array( $entities['publisher'] ) && ! empty( $entities['publisher']['@id'] ) ) {
        $publisher_ref = array(
            '@id' => $entities['publisher']['@id'],
        );
    } elseif ( ! empty( $entities['organization'] ) && is_array( $entities['organization'] ) && ! empty( $entities['organization']['@id'] ) ) {
        $publisher_ref = array(
            '@id' => $entities['organization']['@id'],
        );
    } elseif ( ! empty( $entities['person'] ) && is_array( $entities['person'] ) && ! empty( $entities['person']['@id'] ) ) {
        $publisher_ref = array(
            '@id' => $entities['person']['@id'],
        );
    }

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
        $img_node = be_schema_engine_build_image_object( $settings['org_logo'], '#logo' );
        if ( $img_node && ! empty( $img_node['contentUrl'] ) ) {
            $image_url = $img_node['contentUrl'];
        }
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

    // Language (if available).
    $language = get_bloginfo( 'language' );
    if ( $language ) {
        $blogposting['inLanguage'] = $language;
    }

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
     * @param array   $blogposting The BlogPosting node.
     * @param WP_Post $post        The post object.
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
     * - WebSite
     * - BlogPosting
     */
    $graph = array();

    // Person and Person image.
    if ( ! empty( $entities['person'] ) && is_array( $entities['person'] ) ) {
        $graph[] = $entities['person'];
    }
    if ( ! empty( $entities['person_image'] ) && is_array( $entities['person_image'] ) ) {
        $graph[] = $entities['person_image'];
    }

    // Organisation.
    if ( ! empty( $entities['organization'] ) && is_array( $entities['organization'] ) ) {
        $graph[] = $entities['organization'];
    }

    // Logo.
    if ( ! empty( $entities['logo'] ) && is_array( $entities['logo'] ) ) {
        $graph[] = $entities['logo'];
    }

    // Publisher logo and Publisher node.
    if ( ! empty( $entities['publisher_logo'] ) && is_array( $entities['publisher_logo'] ) ) {
        $graph[] = $entities['publisher_logo'];
    }
    if ( ! empty( $entities['publisher'] ) && is_array( $entities['publisher'] ) && isset( $entities['publisher']['@type'] ) ) {
        $graph[] = $entities['publisher'];
    }

    // WebSite.
    if ( ! empty( $entities['website'] ) && is_array( $entities['website'] ) ) {
        $graph[] = $entities['website'];
    }

    // Finally, the BlogPosting node.
    $graph[] = $blogposting;

    /**
     * Filter the final @graph for BlogPosting output.
     *
     * @param array  $graph     List of graph nodes.
     * @param WP_Post $post     The post object.
     * @param array  $entities  Site entities from be_schema_get_site_entities().
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