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

    if ( function_exists( 'be_schema_is_dry_run' ) && be_schema_is_dry_run() ) {
        if ( function_exists( 'be_schema_log_dry_run' ) ) {
            be_schema_log_dry_run(
                'breadcrumbs',
                array(
                    'url' => home_url( add_query_arg( array(), '' ) ),
                )
            );
        }
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

    $items = be_schema_get_breadcrumb_items();
    if ( empty( $items ) ) {
        return;
    }

    // Ensure home is first.
    array_unshift(
        $items,
        array(
            'name' => $home_name,
            'url'  => $home_url,
        )
    );

    $item_list = array();
    $position  = 1;
    foreach ( $items as $item ) {
        if ( empty( $item['name'] ) || empty( $item['url'] ) ) {
            continue;
        }
        $item_list[] = array(
            '@type'    => 'ListItem',
            'position' => $position,
            'name'     => $item['name'],
            'item'     => be_schema_breadcrumb_clean_url( $item['url'] ),
        );
        $position++;
    }

    if ( count( $item_list ) < 2 ) {
        return;
    }

    $breadcrumb = array(
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $item_list,
    );

    if ( function_exists( 'be_schema_apply_preview_marker' ) ) {
        $breadcrumb = be_schema_apply_preview_marker( $breadcrumb );
    }

    // Collect for debug logging.
    be_schema_debug_collect( $breadcrumb );

    echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb ) . '</script>' . "\n";
}

/**
 * Determine the breadcrumb items for current context (excluding home).
 *
 * @return array<int,array{name:string,url:string}>
 */
function be_schema_get_breadcrumb_items() {
    $items = array();

    if ( is_singular() ) {
        $post = get_post();
        if ( ! $post ) {
            return $items;
        }
        $ancestors = array_reverse( get_post_ancestors( $post ) );
        foreach ( $ancestors as $ancestor_id ) {
            $items[] = array(
                'name' => get_the_title( $ancestor_id ),
                'url'  => get_permalink( $ancestor_id ),
            );
        }

        // For posts, include primary category chain.
        if ( 'post' === $post->post_type ) {
            $cats = get_the_category( $post->ID );
            if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
                $primary = $cats[0];
                $cat_chain = array_reverse( get_ancestors( $primary->term_id, 'category' ) );
                foreach ( $cat_chain as $cat_id ) {
                    $term = get_term( $cat_id, 'category' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $items[] = array(
                            'name' => $term->name,
                            'url'  => get_term_link( $term ),
                        );
                    }
                }
                $items[] = array(
                    'name' => $primary->name,
                    'url'  => get_term_link( $primary ),
                );
            }
        }

        $items[] = array(
            'name' => get_the_title( $post ),
            'url'  => get_permalink( $post ),
        );
    } elseif ( is_home() ) {
        $page_for_posts = (int) get_option( 'page_for_posts' );
        if ( $page_for_posts ) {
            $items[] = array(
                'name' => get_the_title( $page_for_posts ),
                'url'  => get_permalink( $page_for_posts ),
            );
        } else {
            $items[] = array(
                'name' => __( 'Blog', 'beseo' ),
                'url'  => get_permalink(),
            );
        }
    } elseif ( is_category() || is_tag() || is_tax() ) {
        $term = get_queried_object();
        if ( $term && ! is_wp_error( $term ) ) {
            $ancestors = array_reverse( get_ancestors( $term->term_id, $term->taxonomy ) );
            foreach ( $ancestors as $ancestor_id ) {
                $ancestor = get_term( $ancestor_id, $term->taxonomy );
                if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                    $items[] = array(
                        'name' => $ancestor->name,
                        'url'  => get_term_link( $ancestor ),
                    );
                }
            }
            $items[] = array(
                'name' => single_term_title( '', false ),
                'url'  => get_term_link( $term ),
            );
        }
    } elseif ( is_post_type_archive() ) {
        $name      = post_type_archive_title( '', false );
        $post_type = get_post_type();
        if ( $post_type ) {
            $items[] = array(
                'name' => $name,
                'url'  => get_post_type_archive_link( $post_type ),
            );
        }
    } elseif ( is_search() ) {
        $items[] = array(
            'name' => sprintf(
                /* translators: %s is the search query. */
                __( 'Search results for "%s"', 'beseo' ),
                get_search_query( false )
            ),
            'url'  => get_search_link(),
        );
    } elseif ( is_author() ) {
        $author = get_queried_object();
        if ( $author && isset( $author->ID ) ) {
            $items[] = array(
                'name' => $author->display_name,
                'url'  => get_author_posts_url( $author->ID ),
            );
        }
    } elseif ( is_date() ) {
        $items[] = array(
            'name' => get_the_archive_title(),
            'url'  => home_url( add_query_arg( null, null ) ),
        );
    } else {
        // Fallback for other archives (including 404/search variants).
        $items[] = array(
            'name' => wp_get_document_title(),
            'url'  => home_url( add_query_arg( null, null ) ),
        );
    }

    // Normalize names and drop empties.
    $items = array_filter(
        array_map(
            function( $item ) {
                $name = trim( wp_strip_all_tags( (string) $item['name'] ) );
                $url  = isset( $item['url'] ) ? (string) $item['url'] : '';
                if ( '' === $name || '' === $url ) {
                    return null;
                }
                return array(
                    'name' => $name,
                    'url'  => $url,
                );
            },
            $items
        )
    );

    return array_values( $items );
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
