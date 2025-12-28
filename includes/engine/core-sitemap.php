<?php
/**
 * Front-end helpers for sitemap exposure.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return the last generated sitemap metadata (saved by admin page).
 *
 * @return array
 */
function be_schema_engine_get_last_sitemap_meta() {
    $meta = get_option( 'be_schema_sitemap_last', array() );
    return is_array( $meta ) ? $meta : array();
}

/**
 * Append sitemap URLs to robots.txt automatically.
 *
 * @param string $output Existing robots.txt output.
 * @param bool   $public Whether site is public.
 *
 * @return string
 */
function be_schema_engine_filter_robots_txt( $output, $public ) {
    if ( ! $public ) {
        return $output;
    }

    $meta = be_schema_engine_get_last_sitemap_meta();
    if ( ! $meta ) {
        return $output;
    }

    $lines = array_filter( array_map( 'trim', explode( "\n", (string) $output ) ) );
    $sitemap_urls = array();

    // Friendly alias.
    $sitemap_urls[] = home_url( '/sitemap.xml' );

    if ( ! empty( $meta['index_url'] ) ) {
        $sitemap_urls[] = $meta['index_url'];
    } elseif ( ! empty( $meta['primary_url'] ) ) {
        $sitemap_urls[] = $meta['primary_url'];
    }

    $sitemap_urls = array_unique( array_filter( $sitemap_urls ) );
    foreach ( $sitemap_urls as $sitemap_url ) {
        $lines[] = 'Sitemap: ' . $sitemap_url;
    }

    return implode( "\n", array_unique( $lines ) );
}
add_filter( 'robots_txt', 'be_schema_engine_filter_robots_txt', 10, 2 );

/**
 * Serve the latest generated sitemap (index or main) at /sitemap.xml.
 * This provides a simple rewrite/alias without touching server config.
 */
function be_schema_engine_maybe_serve_sitemap_alias() {
    if ( is_admin() ) {
        return;
    }

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $path       = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
    $home_path  = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
    $path_lower = strtolower( $path );
    if ( $home_path ) {
        $home_path = strtolower( $home_path );
        if ( 0 === strpos( $path_lower, $home_path . '/' ) ) {
            $path_lower = substr( $path_lower, strlen( $home_path ) + 1 );
        } elseif ( $path_lower === $home_path ) {
            $path_lower = '';
        }
    }

    if ( ! in_array( $path_lower, array( 'sitemap.xml', 'sitemap_index.xml' ), true ) ) {
        return;
    }

    $meta        = be_schema_engine_get_last_sitemap_meta();
    $target_path = '';

    if ( 'sitemap_index.xml' === $path_lower ) {
        if ( ! empty( $meta['index_path'] ) && file_exists( $meta['index_path'] ) ) {
            $target_path = $meta['index_path'];
        }
    } else {
        if ( ! empty( $meta['index_path'] ) && file_exists( $meta['index_path'] ) ) {
            $target_path = $meta['index_path'];
        } elseif ( ! empty( $meta['sitemaps'][0]['path'] ) && file_exists( $meta['sitemaps'][0]['path'] ) ) {
            $target_path = $meta['sitemaps'][0]['path'];
        } elseif ( ! empty( $meta['primary_path'] ) && file_exists( $meta['primary_path'] ) ) {
            $target_path = $meta['primary_path'];
        } elseif ( ! empty( $meta['html_path'] ) && file_exists( $meta['html_path'] ) ) {
            // If only HTML exists, don't serve it as XML; bail.
            return;
        }
    }

    if ( ! $target_path ) {
        return;
    }

    header( 'Content-Type: application/xml; charset=UTF-8' );
    // Avoid getting indexed as a page.
    header( 'X-Robots-Tag: noindex', true );

    readfile( $target_path );
    exit;
}
add_action( 'template_redirect', 'be_schema_engine_maybe_serve_sitemap_alias', 1 );
