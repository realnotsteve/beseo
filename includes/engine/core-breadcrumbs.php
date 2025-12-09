<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Output BreadcrumbList schema.
 *
 * Skips if:
 * - Global schema disabled.
 * - is_front_page().
 * - is_singular() and page is disabled by be_schema_is_disabled_for_current_page().
 *
 * Builds:
 * - Position 1: site root
 * - Position 2: current context (singular/archive/search/etc.)
 * - Strips utm_*, fbclid, gclid from query string.
 */
function be_schema_output_breadcrumb_schema() {
    if ( be_schema_globally_disabled() ) {
        return;
    }

    if ( is_front_page() ) {
        return;
    }

    if ( is_singular() && be_schema_is_disabled_for_current_page() ) {
        return;
    }

    // Avoid schema in feeds / robots / embeds as an extra precaution.
    if ( is_feed() || is_robots() || is_embed() ) {
        return;
    }

    $home_url  = home_url( '/' );
    $home_name = get_bloginfo( 'name', 'display' );

    $current = be_schema_get_breadcrumb_current_item();
    if ( ! $current || empty( $current['name'] ) || empty( $current['url'] ) ) {
        return;
    }

    $current_url_clean = be_schema_breadcrumb_clean_url( $current['url'] );

    $breadcrumb = array(
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => array(
            array(
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => $home_name,
                'item'     => $home_url,
            ),
            array(
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => $current['name'],
                'item'     => $current_url_clean,
            ),
        ),
    );

    // Collect for debug logging.
    be_schema_debug_collect( $breadcrumb );

    echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb ) . '</script>' . "\n";
}

/**
 * Determine the current context label + URL for breadcrumbs.
 *
 * @return array|null { name, url } or null on failure.
 */
function be_schema_get_breadcrumb_current_item() {
    $name = '';
    $url  = '';

    if ( is_singular() ) {
        $post = get_post();
        if ( ! $post ) {
            return null;
        }

        $name = get_the_title( $post );
        $url  = get_permalink( $post );
    } elseif ( is_home() ) {
        // Blog posts index (when home is not the front page).
        $page_for_posts = (int) get_option( 'page_for_posts' );
        if ( $page_for_posts ) {
            $name = get_the_title( $page_for_posts );
            $url  = get_permalink( $page_for_posts );
        } else {
            $name = __( 'Blog', 'beseo' );
            $url  = get_permalink();
        }
    } elseif ( is_category() || is_tag() || is_tax() ) {
        $term = get_queried_object();
        if ( $term && ! is_wp_error( $term ) ) {
            $name = single_term_title( '', false );
            $url  = get_term_link( $term );
        }
    } elseif ( is_post_type_archive() ) {
        $name = post_type_archive_title( '', false );
        $post_type = get_post_type();
        if ( $post_type ) {
            $url = get_post_type_archive_link( $post_type );
        }
    } elseif ( is_search() ) {
        $name = sprintf(
            /* translators: %s is the search query. */
            __( 'Search results for "%s"', 'beseo' ),
            get_search_query( false )
        );
        $url = get_search_link();
    } elseif ( is_author() ) {
        $author = get_queried_object();
        if ( $author && isset( $author->ID ) ) {
            $name = $author->display_name;
            $url  = get_author_posts_url( $author->ID );
        }
    } elseif ( is_date() ) {
        $name = get_the_archive_title();
        $url  = home_url( add_query_arg( null, null ) );
    } else {
        // Fallback for other archives.
        $name = wp_get_document_title();
        $url  = home_url( add_query_arg( null, null ) );
    }

    $name = trim( wp_strip_all_tags( (string) $name ) );

    if ( '' === $name || '' === $url ) {
        return null;
    }

    return array(
        'name' => $name,
        'url'  => $url,
    );
}

/**
 * Strip tracking parameters from a URL:
 * - utm_*
 * - fbclid
 * - gclid
 *
 * @param string $url
 * @return string
 */
function be_schema_breadcrumb_clean_url( $url ) {
    $parts = wp_parse_url( $url );
    if ( ! $parts || ! isset( $parts['query'] ) ) {
        return $url;
    }

    parse_str( $parts['query'], $query_vars );

    foreach ( $query_vars as $key => $value ) {
        if ( strpos( $key, 'utm_' ) === 0 ) {
            unset( $query_vars[ $key ] );
        }
        if ( 'fbclid' === $key || 'gclid' === $key ) {
            unset( $query_vars[ $key ] );
        }
    }

    $query = http_build_query( $query_vars );
    $clean = $parts['scheme'] . '://' . $parts['host'];

    if ( isset( $parts['port'] ) ) {
        $clean .= ':' . $parts['port'];
    }

    if ( isset( $parts['path'] ) ) {
        $clean .= $parts['path'];
    }

    if ( $query ) {
        $clean .= '?' . $query;
    }

    if ( isset( $parts['fragment'] ) ) {
        $clean .= '#' . $parts['fragment'];
    }

    return $clean;
}