<?php
/**
 * Analyser service logic and AJAX handlers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_ajax_be_schema_analyser_run', 'be_schema_engine_handle_analyser_run' );
add_action( 'wp_ajax_be_schema_analyser_start', 'be_schema_engine_handle_analyser_start' );
add_action( 'wp_ajax_be_schema_analyser_step', 'be_schema_engine_handle_analyser_step' );
add_action( 'wp_ajax_be_schema_analyser_stop', 'be_schema_engine_handle_analyser_stop' );
add_action( 'wp_ajax_be_schema_analyser_history', 'be_schema_engine_handle_analyser_history' );
add_action( 'wp_ajax_be_schema_analyser_list_pages', 'be_schema_engine_handle_analyser_list_pages' );

/**
 * Simple per-user analyser state storage.
 */
function be_schema_engine_analyser_state_key() {
    $user_id = get_current_user_id();
    return 'be_schema_analyser_state_' . ( $user_id ? $user_id : 'guest' );
}

function be_schema_engine_analyser_history_key() {
    $user_id = get_current_user_id();
    return 'be_schema_analyser_history_' . ( $user_id ? $user_id : 'guest' );
}

function be_schema_engine_analyser_state_get() {
    $key   = be_schema_engine_analyser_state_key();
    $state = get_transient( $key );
    if ( ! is_array( $state ) ) {
        return array();
    }
    return $state;
}

function be_schema_engine_analyser_state_set( $state ) {
    $key = be_schema_engine_analyser_state_key();
    set_transient( $key, $state, MINUTE_IN_SECONDS * 20 );
}

function be_schema_engine_analyser_state_clear() {
    delete_transient( be_schema_engine_analyser_state_key() );
}

function be_schema_engine_analyser_history_get() {
    $key     = be_schema_engine_analyser_history_key();
    $history = get_transient( $key );
    if ( ! is_array( $history ) ) {
        return array();
    }
    return $history;
}

function be_schema_engine_analyser_history_push( $entry ) {
    $key     = be_schema_engine_analyser_history_key();
    $history = be_schema_engine_analyser_history_get();
    array_unshift( $history, $entry );
    $history = array_slice( $history, 0, 10 );
    set_transient( $key, $history, DAY_IN_SECONDS * 14 );
}

/**
 * Lightweight analyser: fetch a single URL and emit issues.
 */
function be_schema_engine_handle_analyser_run() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Not allowed.', 'beseo' ) ), 403 );
    }
    check_ajax_referer( 'be_schema_analyser', 'nonce' );

    $url = isset( $_POST['url'] ) ? trim( (string) wp_unslash( $_POST['url'] ) ) : '';
    $local = ! empty( $_POST['local'] );
    if ( ! $url || ! wp_http_validate_url( $url ) ) {
        wp_send_json_error( array( 'message' => __( 'Provide a valid URL (http/https).', 'beseo' ) ) );
    }

    if ( $local ) {
        $mapped = be_schema_engine_analyser_map_to_local( $url );
        if ( $mapped ) {
            $url = $mapped;
        }
    }

    $result = be_schema_engine_analyse_url( $url );
    wp_send_json_success( $result );
}

/**
 * Start a crawl: seed queue.
 */
function be_schema_engine_handle_analyser_start() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Not allowed.', 'beseo' ) ), 403 );
    }
    check_ajax_referer( 'be_schema_analyser', 'nonce' );
    $url      = isset( $_POST['url'] ) ? trim( (string) wp_unslash( $_POST['url'] ) ) : '';
    $local    = ! empty( $_POST['local'] );
    $max      = isset( $_POST['max'] ) ? (int) $_POST['max'] : 20;
    $max      = max( 1, min( 100, $max ) );
    if ( ! $url || ! wp_http_validate_url( $url ) ) {
        wp_send_json_error( array( 'message' => __( 'Provide a valid URL.', 'beseo' ) ) );
    }
    if ( $local ) {
        $mapped = be_schema_engine_analyser_map_to_local( $url );
        if ( $mapped ) {
            $url = $mapped;
        }
    }
    $parsed = wp_parse_url( $url );
    if ( ! $parsed || empty( $parsed['host'] ) ) {
        wp_send_json_error( array( 'message' => __( 'URL must include a host.', 'beseo' ) ) );
    }

    $state = array(
        'queue'     => array( $url ),
        'visited'   => array(),
        'results'   => array(),
        'start'     => time(),
        'max'       => $max,
        'processed' => 0,
        'host'      => $parsed['host'],
        'errors'    => 0,
        'titles'    => array(),
    );
    be_schema_engine_analyser_state_set( $state );

    wp_send_json_success(
        array(
            'message' => __( 'Crawl started.', 'beseo' ),
            'state'   => array(
                'processed' => 0,
                'queued'    => 1,
                'max'       => $max,
                'start'     => $state['start'],
                'errors'    => 0,
            ),
        )
    );
}

/**
 * Process one URL from the crawl queue.
 */
function be_schema_engine_handle_analyser_step() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Not allowed.', 'beseo' ) ), 403 );
    }
    check_ajax_referer( 'be_schema_analyser', 'nonce' );
    $state = be_schema_engine_analyser_state_get();
    if ( empty( $state['queue'] ) || ! isset( $state['processed'] ) ) {
        wp_send_json_success(
            array(
                'done' => true,
                'state'=> array(
                    'processed' => 0,
                    'queued'    => 0,
                    'max'       => 0,
                    'errors'    => 0,
                    'start'     => isset( $state['start'] ) ? $state['start'] : 0,
                ),
            )
        );
    }

    $url = array_shift( $state['queue'] );
    if ( isset( $state['visited'][ $url ] ) ) {
        be_schema_engine_analyser_state_set( $state );
        wp_send_json_success(
            array(
                'done' => empty( $state['queue'] ) || $state['processed'] >= $state['max'],
                'state'=> array(
                    'processed' => $state['processed'],
                    'queued'    => count( $state['queue'] ),
                    'max'       => $state['max'],
                    'errors'    => isset( $state['errors'] ) ? $state['errors'] : 0,
                    'start'     => isset( $state['start'] ) ? $state['start'] : 0,
                ),
                'last' => null,
            )
        );
    }

    $state['visited'][ $url ] = true;
    $analysis = be_schema_engine_analyse_url( $url );
    if ( ! isset( $state['titles'] ) || ! is_array( $state['titles'] ) ) {
        $state['titles'] = array();
    }
    $title_key = '';
    if ( ! empty( $analysis['title'] ) ) {
        $title_key = strtolower( trim( $analysis['title'] ) );
    }
    if ( $title_key ) {
        if ( isset( $state['titles'][ $title_key ] ) && $state['titles'][ $title_key ] && $state['titles'][ $title_key ] !== $url ) {
            $analysis['issues'][] = array(
                'severity' => 'warn',
                'type'     => 'metadata',
                'message'  => sprintf( __( 'Duplicate title also used on %s', 'beseo' ), $state['titles'][ $title_key ] ),
            );
        } else {
            $state['titles'][ $title_key ] = $url;
        }
    }
    $state['processed']      += 1;
    $state['results'][ $url ] = $analysis['issues'];

    // Build summary counts.
    $summary = array();
    foreach ( $state['results'] as $page_url => $page_issues ) {
        foreach ( (array) $page_issues as $issue ) {
            $key = ( isset( $issue['severity'] ) ? $issue['severity'] : 'info' ) . '|' . ( isset( $issue['type'] ) ? $issue['type'] : 'generic' );
            if ( ! isset( $summary[ $key ] ) ) {
                $summary[ $key ] = array(
                    'severity' => isset( $issue['severity'] ) ? $issue['severity'] : 'info',
                    'type'     => isset( $issue['type'] ) ? $issue['type'] : 'generic',
                    'count'    => 0,
                    'pages'    => array(),
                );
            }
            $summary[ $key ]['count']++;
            $summary[ $key ]['pages'][] = $page_url;
        }
    }

    if ( ! empty( $analysis['internal'] ) && isset( $state['host'] ) ) {
        foreach ( $analysis['internal'] as $link ) {
            if ( count( $state['visited'] ) + count( $state['queue'] ) >= $state['max'] ) {
                break;
            }
            $parsed = wp_parse_url( $link );
            if ( $parsed && isset( $parsed['host'] ) && $parsed['host'] === $state['host'] && ! isset( $state['visited'][ $link ] ) ) {
                $state['queue'][] = $link;
            }
        }
    }

    $error_count = 0;
    foreach ( $state['results'] as $page_issues ) {
        foreach ( (array) $page_issues as $issue ) {
            if ( isset( $issue['severity'] ) && 'error' === $issue['severity'] ) {
                $error_count++;
            }
        }
    }
    $state['errors'] = $error_count;

    $done = ( $state['processed'] >= $state['max'] ) || empty( $state['queue'] );
    if ( $done && empty( $state['queue'] ) && $state['processed'] < $state['max'] ) {
        $state['max'] = $state['processed'];
    }
    if ( $done ) {
        be_schema_engine_analyser_history_push(
            array(
                'timestamp' => time(),
                'summary'   => array_values( $summary ),
                'pages'     => $state['results'],
                'stats'     => array(
                    'processed' => $state['processed'],
                    'max'       => $state['max'],
                    'errors'    => $error_count,
                    'start'     => isset( $state['start'] ) ? $state['start'] : time(),
                ),
            )
        );
        be_schema_engine_analyser_state_clear();
    } else {
        be_schema_engine_analyser_state_set( $state );
    }

    wp_send_json_success(
        array(
            'done'      => $done,
            'last'      => array(
                'url'     => $url,
                'issues'  => $analysis['issues'],
                'status'  => isset( $analysis['status'] ) ? $analysis['status'] : 0,
                'duration'=> isset( $analysis['duration'] ) ? $analysis['duration'] : 0,
            ),
            'state'     => array(
                'processed' => $state['processed'],
                'queued'    => count( $state['queue'] ),
                'max'       => $state['max'],
                'errors'    => $error_count,
                'start'     => isset( $state['start'] ) ? $state['start'] : time(),
            ),
            'summary'   => array_values( $summary ),
            'pages'     => $state['results'],
        )
    );
}

/**
 * Stop/clear crawl state.
 */
function be_schema_engine_handle_analyser_stop() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Not allowed.', 'beseo' ) ), 403 );
    }
    check_ajax_referer( 'be_schema_analyser', 'nonce' );
    be_schema_engine_analyser_state_clear();
    wp_send_json_success( array( 'message' => __( 'Crawl stopped.', 'beseo' ) ) );
}

/**
 * Return analyser history.
 */
function be_schema_engine_handle_analyser_history() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Not allowed.', 'beseo' ) ), 403 );
    }
    check_ajax_referer( 'be_schema_analyser', 'nonce' );
    $history = be_schema_engine_analyser_history_get();
    wp_send_json_success( array( 'history' => $history ) );
}

/**
 * AJAX: List sitemap pages for a site.
 */
function be_schema_engine_handle_analyser_list_pages() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Not allowed.', 'beseo' ) ), 403 );
    }

    check_ajax_referer( 'be_schema_analyser', 'nonce' );

    $url = isset( $_POST['url'] ) ? trim( (string) wp_unslash( $_POST['url'] ) ) : '';
    $local = ! empty( $_POST['local'] );
    $max = isset( $_POST['max'] ) ? (int) $_POST['max'] : 25;
    $max = max( 1, min( 500, $max ) );

    $parsed = wp_parse_url( $url );
    if ( empty( $parsed['scheme'] ) || empty( $parsed['host'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Provide a valid URL.', 'beseo' ) ), 400 );
    }
    $scheme = strtolower( (string) $parsed['scheme'] );
    if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
        wp_send_json_error( array( 'message' => __( 'Provide a valid URL (http/https).', 'beseo' ) ), 400 );
    }

    if ( $local ) {
        $mapped = be_schema_engine_analyser_map_to_local( $url );
        if ( $mapped ) {
            $url = $mapped;
        }
    }

    $base_url = be_schema_engine_analyser_base_url( $url );
    if ( ! $base_url ) {
        wp_send_json_error( array( 'message' => __( 'Unable to determine the site base URL.', 'beseo' ) ), 400 );
    }

    $sitemap_url = be_schema_engine_analyser_discover_sitemap( $base_url );
    if ( ! $sitemap_url ) {
        wp_send_json_error( array( 'message' => __( 'No sitemap was found for this site.', 'beseo' ) ), 400 );
    }

    $urls = be_schema_engine_analyser_collect_sitemap_urls( $sitemap_url, $max, $base_url );
    if ( empty( $urls ) ) {
        wp_send_json_error( array( 'message' => __( 'No sitemap pages were found.', 'beseo' ) ), 400 );
    }

    $pages = be_schema_engine_analyser_build_page_list( $urls, $base_url, $max );
    if ( empty( $pages ) ) {
        wp_send_json_error( array( 'message' => __( 'No sitemap pages were found.', 'beseo' ) ), 400 );
    }

    wp_send_json_success(
        array(
            'sitemap' => $sitemap_url,
            'pages'   => $pages,
            'count'   => count( $pages ),
        )
    );
}

/**
 * Normalize base URL.
 *
 * @param string $url Input URL.
 * @return string
 */
function be_schema_engine_analyser_base_url( $url ) {
    $parsed = wp_parse_url( $url );
    if ( ! $parsed || empty( $parsed['scheme'] ) || empty( $parsed['host'] ) ) {
        return '';
    }
    $base = $parsed['scheme'] . '://' . $parsed['host'];
    if ( ! empty( $parsed['port'] ) ) {
        $base .= ':' . $parsed['port'];
    }
    return trailingslashit( $base );
}

/**
 * Map a URL to the local site base while preserving the path/query/fragment.
 *
 * @param string $url Target URL.
 * @return string
 */
function be_schema_engine_analyser_map_to_local( $url ) {
    $local_base = be_schema_engine_analyser_base_url( home_url( '/' ) );
    if ( ! $local_base ) {
        return '';
    }
    $parsed = wp_parse_url( $url );
    if ( ! $parsed ) {
        return '';
    }
    $path = isset( $parsed['path'] ) ? (string) $parsed['path'] : '/';
    if ( '' === $path ) {
        $path = '/';
    }
    $query = isset( $parsed['query'] ) ? '?' . $parsed['query'] : '';
    $fragment = isset( $parsed['fragment'] ) ? '#' . $parsed['fragment'] : '';
    return rtrim( $local_base, '/' ) . $path . $query . $fragment;
}

/**
 * Discover sitemap URL from robots.txt or common defaults.
 *
 * @param string $base_url Base site URL.
 * @return string
 */
function be_schema_engine_analyser_discover_sitemap( $base_url ) {
    $robots_url = trailingslashit( $base_url ) . 'robots.txt';
    $robots = be_schema_engine_analyser_fetch_url( $robots_url );
    if ( $robots['ok'] && ! empty( $robots['body'] ) ) {
        $lines = preg_split( '/\\r\\n|\\r|\\n/', $robots['body'] );
        foreach ( $lines as $line ) {
            if ( preg_match( '/^\\s*sitemap\\s*:\\s*(\\S+)/i', $line, $matches ) ) {
                $candidate = trim( $matches[1] );
                if ( wp_http_validate_url( $candidate ) ) {
                    return $candidate;
                }
            }
        }
    }

    $candidates = array(
        trailingslashit( $base_url ) . 'sitemap_index.xml',
        trailingslashit( $base_url ) . 'sitemap.xml',
        trailingslashit( $base_url ) . 'wp-sitemap.xml',
    );
    foreach ( $candidates as $candidate ) {
        $response = be_schema_engine_analyser_fetch_url( $candidate );
        if ( $response['ok'] && ! empty( $response['body'] ) ) {
            return $candidate;
        }
    }

    return '';
}

/**
 * Fetch a URL for sitemap discovery.
 *
 * @param string $url Target URL.
 * @return array {ok, body}
 */
function be_schema_engine_analyser_fetch_url( $url ) {
    $sslverify = true;
    if ( function_exists( 'be_schema_playfair_is_private_target' ) && be_schema_playfair_is_private_target( $url ) ) {
        $sslverify = false;
    }
    $response = wp_remote_get(
        $url,
        array(
            'timeout'     => 10,
            'redirection' => 3,
            'sslverify'   => $sslverify,
        )
    );
    if ( is_wp_error( $response ) ) {
        return array(
            'ok'   => false,
            'body' => '',
        );
    }
    $status = (int) wp_remote_retrieve_response_code( $response );
    if ( $status < 200 || $status >= 400 ) {
        return array(
            'ok'   => false,
            'body' => '',
        );
    }
    $body = (string) wp_remote_retrieve_body( $response );
    if ( '' === $body ) {
        return array(
            'ok'   => false,
            'body' => '',
        );
    }

    $decoded = be_schema_engine_analyser_maybe_decode_gzip( $body, $response, $url );
    return array(
        'ok'   => true,
        'body' => $decoded,
    );
}

/**
 * Attempt to decode gzip sitemap responses.
 *
 * @param string $body Response body.
 * @param array  $response HTTP response.
 * @param string $url Target URL.
 * @return string
 */
function be_schema_engine_analyser_maybe_decode_gzip( $body, $response, $url ) {
    $encoding = wp_remote_retrieve_header( $response, 'content-encoding' );
    $content_type = wp_remote_retrieve_header( $response, 'content-type' );
    $looks_gz = ( false !== strpos( (string) $encoding, 'gzip' ) ) || ( false !== strpos( (string) $content_type, 'gzip' ) );
    if ( ! $looks_gz && substr( $body, 0, 2 ) === "\x1f\x8b" ) {
        $looks_gz = true;
    }
    if ( ! $looks_gz && substr( $url, -3 ) === '.gz' ) {
        $looks_gz = true;
    }
    if ( $looks_gz && function_exists( 'gzdecode' ) ) {
        $decoded = gzdecode( $body );
        if ( false !== $decoded ) {
            return $decoded;
        }
    }
    return $body;
}

/**
 * Collect URLs from sitemap(s).
 *
 * @param string $sitemap_url Sitemap URL.
 * @param int    $max Max URLs.
 * @param string $base_url Base site URL.
 * @return array
 */
function be_schema_engine_analyser_collect_sitemap_urls( $sitemap_url, $max, $base_url ) {
    $queue = array( $sitemap_url );
    $seen = array();
    $urls = array();
    $base_host = wp_parse_url( $base_url, PHP_URL_HOST );
    $max = max( 1, (int) $max );

    while ( ! empty( $queue ) && count( $urls ) < $max ) {
        $current = array_shift( $queue );
        if ( isset( $seen[ $current ] ) ) {
            continue;
        }
        $seen[ $current ] = true;

        $response = be_schema_engine_analyser_fetch_url( $current );
        if ( ! $response['ok'] || empty( $response['body'] ) ) {
            continue;
        }

        $parsed = be_schema_engine_analyser_parse_sitemap( $response['body'] );
        if ( ! empty( $parsed['urls'] ) ) {
            foreach ( $parsed['urls'] as $entry ) {
                if ( count( $urls ) >= $max ) {
                    break;
                }
                if ( ! wp_http_validate_url( $entry ) ) {
                    continue;
                }
                $entry_host = wp_parse_url( $entry, PHP_URL_HOST );
                if ( $base_host && $entry_host && strtolower( $entry_host ) !== strtolower( (string) $base_host ) ) {
                    continue;
                }
                if ( ! isset( $seen[ $entry ] ) ) {
                    $urls[] = $entry;
                    $seen[ $entry ] = true;
                }
            }
            continue;
        }
        if ( ! empty( $parsed['sitemaps'] ) ) {
            foreach ( $parsed['sitemaps'] as $child ) {
                if ( ! isset( $seen[ $child ] ) ) {
                    $queue[] = $child;
                }
            }
        }
    }

    return $urls;
}

/**
 * Parse sitemap XML into URL lists.
 *
 * @param string $body XML body.
 * @return array
 */
function be_schema_engine_analyser_parse_sitemap( $body ) {
    $urls = array();
    $sitemaps = array();
    if ( '' === $body ) {
        return array(
            'urls'     => $urls,
            'sitemaps' => $sitemaps,
        );
    }
    libxml_use_internal_errors( true );
    $doc = new DOMDocument();
    if ( ! $doc->loadXML( $body ) ) {
        libxml_clear_errors();
        return array(
            'urls'     => $urls,
            'sitemaps' => $sitemaps,
        );
    }
    $xpath = new DOMXPath( $doc );
    $loc_nodes = $xpath->query( '//*[local-name()="url"]/*[local-name()="loc"]' );
    if ( $loc_nodes && $loc_nodes->length ) {
        foreach ( $loc_nodes as $node ) {
            $value = trim( $node->textContent );
            if ( $value ) {
                $urls[] = $value;
            }
        }
        libxml_clear_errors();
        return array(
            'urls'     => $urls,
            'sitemaps' => $sitemaps,
        );
    }
    $sitemap_nodes = $xpath->query( '//*[local-name()="sitemap"]/*[local-name()="loc"]' );
    if ( $sitemap_nodes && $sitemap_nodes->length ) {
        foreach ( $sitemap_nodes as $node ) {
            $value = trim( $node->textContent );
            if ( $value ) {
                $sitemaps[] = $value;
            }
        }
    }
    libxml_clear_errors();
    return array(
        'urls'     => $urls,
        'sitemaps' => $sitemaps,
    );
}

/**
 * Build display list from sitemap URLs.
 *
 * @param array  $urls Sitemap URLs.
 * @param string $base_url Base site URL.
 * @param int    $max Max results.
 * @return array
 */
function be_schema_engine_analyser_build_page_list( $urls, $base_url, $max ) {
    $max = max( 1, (int) $max );
    $base_host = wp_parse_url( $base_url, PHP_URL_HOST );
    $home_norm = be_schema_engine_analyser_normalize_url( $base_url );

    $pages = array();
    $home_url = $base_url;
    foreach ( $urls as $url ) {
        if ( ! wp_http_validate_url( $url ) ) {
            continue;
        }
        $host = wp_parse_url( $url, PHP_URL_HOST );
        if ( $base_host && $host && strtolower( $host ) !== strtolower( (string) $base_host ) ) {
            continue;
        }
        $norm = be_schema_engine_analyser_normalize_url( $url );
        if ( $norm && $home_norm && $norm === $home_norm ) {
            $home_url = $url;
            continue;
        }
        $pages[] = array(
            'url'   => $url,
            'label' => be_schema_engine_analyser_label_from_url( $url ),
        );
    }

    usort(
        $pages,
        function( $a, $b ) {
            return strcasecmp( (string) $a['label'], (string) $b['label'] );
        }
    );

    $remaining_limit = max( 0, $max - 1 );
    if ( count( $pages ) > $remaining_limit ) {
        $pages = array_slice( $pages, 0, $remaining_limit );
    }

    array_unshift(
        $pages,
        array(
            'url'     => $home_url,
            'label'   => __( 'Home page', 'beseo' ),
            'is_home' => true,
        )
    );

    return $pages;
}

/**
 * Normalize URL for comparisons (host + path, no trailing slash).
 *
 * @param string $url URL to normalize.
 * @return string
 */
function be_schema_engine_analyser_normalize_url( $url ) {
    $parsed = wp_parse_url( $url );
    if ( ! $parsed || empty( $parsed['host'] ) ) {
        return '';
    }
    $host = strtolower( (string) $parsed['host'] );
    $path = isset( $parsed['path'] ) ? (string) $parsed['path'] : '/';
    if ( '/' !== $path ) {
        $path = rtrim( $path, '/' );
    }
    return $host . $path;
}

/**
 * Create a readable label from URL path.
 *
 * @param string $url URL to label.
 * @return string
 */
function be_schema_engine_analyser_label_from_url( $url ) {
    $path = wp_parse_url( $url, PHP_URL_PATH );
    if ( ! $path ) {
        return __( 'Home page', 'beseo' );
    }
    $path = trim( (string) $path, '/' );
    if ( '' === $path ) {
        return __( 'Home page', 'beseo' );
    }
    $segments = explode( '/', $path );
    $labels = array();
    foreach ( $segments as $segment ) {
        $segment = urldecode( $segment );
        $segment = str_replace( array( '-', '_' ), ' ', $segment );
        $segment = trim( preg_replace( '/\\s+/', ' ', $segment ) );
        if ( '' === $segment ) {
            continue;
        }
        $labels[] = ucwords( $segment );
    }
    return $labels ? implode( ' / ', $labels ) : __( 'Home page', 'beseo' );
}

/**
 * Core analyser logic for a single URL.
 */
function be_schema_engine_analyse_url( $url ) {
    $issues      = array();
    $page_start  = microtime( true );
    $response    = wp_remote_get(
        $url,
        array(
            'timeout'     => 12,
            'redirection' => 5,
            'user-agent'  => sprintf( 'BESEO Analyser/1.0 (%s)', home_url() ),
        )
    );
    $page_end    = microtime( true );
    $duration_ms = (int) round( ( $page_end - $page_start ) * 1000 );

    if ( is_wp_error( $response ) ) {
        $issues[] = array(
            'severity' => 'error',
            'type'     => 'fetch',
            'message'  => sprintf( __( 'Fetch failed: %s', 'beseo' ), $response->get_error_message() ),
        );
        return array(
            'url'      => $url,
            'duration' => $duration_ms,
            'issues'   => $issues,
        );
    }

    $status = (int) wp_remote_retrieve_response_code( $response );
    $body   = wp_remote_retrieve_body( $response );
    if ( $status >= 400 ) {
        $issues[] = array(
            'severity' => 'error',
            'type'     => 'http',
            'message'  => sprintf( __( 'HTTP %d returned.', 'beseo' ), $status ),
        );
    }
    if ( empty( $body ) ) {
        $issues[] = array(
            'severity' => 'error',
            'type'     => 'content',
            'message'  => __( 'Empty response body.', 'beseo' ),
        );
    }

    libxml_use_internal_errors( true );
    $doc = new DOMDocument();
    $doc->loadHTML( $body );
    $xpath = new DOMXPath( $doc );
    libxml_clear_errors();

    $title_node = $xpath->query( '//head/title' );
    $title_text = ( $title_node && $title_node->length ) ? trim( wp_strip_all_tags( $title_node->item( 0 )->textContent ) ) : '';
    if ( '' === $title_text ) {
        $issues[] = array(
            'severity' => 'warn',
            'type'     => 'metadata',
            'message'  => __( 'Missing <title>.', 'beseo' ),
        );
    } elseif ( strlen( $title_text ) > 65 ) {
        $issues[] = array(
            'severity' => 'info',
            'type'     => 'metadata',
            'message'  => __( 'Title likely to truncate (over 65 characters).', 'beseo' ),
        );
    }

    $meta_desc = '';
    $desc_node = $xpath->query( '//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]' );
    if ( $desc_node && $desc_node->length ) {
        $meta_desc = trim( wp_strip_all_tags( $desc_node->item( 0 )->getAttribute( 'content' ) ) );
    }
    if ( '' === $meta_desc ) {
        $issues[] = array(
            'severity' => 'warn',
            'type'     => 'metadata',
            'message'  => __( 'Meta description missing.', 'beseo' ),
        );
    } elseif ( strlen( $meta_desc ) > 170 ) {
        $issues[] = array(
            'severity' => 'info',
            'type'     => 'metadata',
            'message'  => __( 'Meta description may truncate (over ~170 characters).', 'beseo' ),
        );
    }

    $h1_nodes = $xpath->query( '//h1' );
    $h1_count = $h1_nodes ? $h1_nodes->length : 0;
    if ( 0 === $h1_count ) {
        $issues[] = array(
            'severity' => 'warn',
            'type'     => 'content',
            'message'  => __( 'No H1 found on the page.', 'beseo' ),
        );
    } elseif ( $h1_count > 1 ) {
        $issues[] = array(
            'severity' => 'info',
            'type'     => 'content',
            'message'  => sprintf( __( 'Multiple H1 tags found (%d).', 'beseo' ), $h1_count ),
        );
    }

    $canonical_nodes = $xpath->query( '//link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="canonical"]' );
    $canonical       = '';
    if ( $canonical_nodes && $canonical_nodes->length ) {
        $canonical = trim( $canonical_nodes->item( 0 )->getAttribute( 'href' ) );
        if ( $canonical && ! wp_http_validate_url( $canonical ) ) {
            $issues[] = array(
                'severity' => 'warn',
                'type'     => 'metadata',
                'message'  => __( 'Canonical URL is invalid.', 'beseo' ),
            );
        } else {
            $canonical_host = wp_parse_url( $canonical, PHP_URL_HOST );
            $page_host      = wp_parse_url( $url, PHP_URL_HOST );
            if ( $canonical_host && $page_host && $canonical_host !== $page_host ) {
                $issues[] = array(
                    'severity' => 'warn',
                    'type'     => 'metadata',
                    'message'  => __( 'Canonical URL points off-domain.', 'beseo' ),
                );
            }
        }
    } else {
        $issues[] = array(
            'severity' => 'info',
            'type'     => 'metadata',
            'message'  => __( 'Canonical link missing.', 'beseo' ),
        );
    }

    $robots_node = $xpath->query( '//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="robots"]' );
    if ( $robots_node && $robots_node->length ) {
        $robots_content = strtolower( (string) $robots_node->item( 0 )->getAttribute( 'content' ) );
        if ( false !== strpos( $robots_content, 'noindex' ) ) {
            $issues[] = array(
                'severity' => 'warn',
                'type'     => 'index',
                'message'  => __( 'Robots meta contains noindex (ensure sitemaps exclude this URL).', 'beseo' ),
            );
        }
    }

    $links = $xpath->query( '//a[@href]' );
    $link_count = $links ? $links->length : 0;
    if ( $link_count < 5 ) {
        $issues[] = array(
            'severity' => 'info',
            'type'     => 'links',
            'message'  => __( 'Very few links found on the page; check internal linking.', 'beseo' ),
        );
    }
    $internal_links = array();
    $host = wp_parse_url( $url, PHP_URL_HOST );
    $checked_broken = 0;
    $broken_limit   = 5;
    if ( $links && $host ) {
        foreach ( $links as $node ) {
            $href = trim( $node->getAttribute( 'href' ) );
            if ( ! $href ) {
                continue;
            }
            if ( 0 === strpos( $href, '#' ) ) {
                continue;
            }
            if ( 0 === strpos( $href, 'mailto:' ) || 0 === strpos( $href, 'tel:' ) ) {
                continue;
            }
            if ( 0 === strpos( $href, '//' ) ) {
                $href = 'https:' . $href;
            }
            if ( 0 === strpos( $href, '/' ) ) {
                $href = trailingslashit( rtrim( home_url(), '/' ) ) . ltrim( $href, '/' );
            }
            $parsed = wp_parse_url( $href );
            if ( $parsed && isset( $parsed['host'] ) && $parsed['host'] === $host && wp_http_validate_url( $href ) ) {
                $internal_links[] = $href;
                if ( $checked_broken < $broken_limit ) {
                    $checked_broken++;
                    $head = wp_remote_head(
                        $href,
                        array(
                            'timeout'     => 5,
                            'redirection' => 3,
                        )
                    );
                    if ( is_wp_error( $head ) ) {
                        $issues[] = array(
                            'severity' => 'warn',
                            'type'     => 'links',
                            'message'  => sprintf( __( 'Broken internal link: %s (%s)', 'beseo' ), $href, $head->get_error_message() ),
                        );
                    } else {
                        $code = (int) wp_remote_retrieve_response_code( $head );
                        if ( $code >= 400 ) {
                            $issues[] = array(
                                'severity' => 'error',
                                'type'     => 'links',
                                'message'  => sprintf( __( 'Broken internal link: %s (HTTP %d)', 'beseo' ), $href, $code ),
                            );
                        }
                    }
                }
            }
        }
    }

    $has_og_title       = (bool) $xpath->query( '//meta[translate(@property,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="og:title"]' )->length;
    $has_og_description = (bool) $xpath->query( '//meta[translate(@property,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="og:description"]' )->length;
    $has_og_image       = (bool) $xpath->query( '//meta[translate(@property,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="og:image"]' )->length;
    if ( ! $has_og_title || ! $has_og_description || ! $has_og_image ) {
        if ( ! $has_og_title ) {
            $issues[] = array(
                'severity' => 'warn',
                'type'     => 'social',
                'message'  => __( 'Open Graph title missing.', 'beseo' ),
            );
        }
        if ( ! $has_og_description ) {
            $issues[] = array(
                'severity' => 'warn',
                'type'     => 'social',
                'message'  => __( 'Open Graph description missing.', 'beseo' ),
            );
        }
        if ( ! $has_og_image ) {
            $issues[] = array(
                'severity' => 'warn',
                'type'     => 'social',
                'message'  => __( 'Open Graph image missing.', 'beseo' ),
            );
        }
    }

    $has_tw_card        = (bool) $xpath->query( '//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="twitter:card"]' )->length;
    $has_tw_title       = (bool) $xpath->query( '//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="twitter:title"]' )->length;
    $has_tw_description = (bool) $xpath->query( '//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="twitter:description"]' )->length;
    $has_tw_image       = (bool) $xpath->query( '//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="twitter:image"]' )->length;
    if ( ! $has_tw_card || ! $has_tw_title || ! $has_tw_description || ! $has_tw_image ) {
        if ( ! $has_tw_card ) {
            $issues[] = array(
                'severity' => 'warn',
                'type'     => 'social',
                'message'  => __( 'Twitter card type missing.', 'beseo' ),
            );
        }
        if ( ! $has_tw_title ) {
            $issues[] = array(
                'severity' => 'warn',
                'type'     => 'social',
                'message'  => __( 'Twitter title missing.', 'beseo' ),
            );
        }
        if ( ! $has_tw_description ) {
            $issues[] = array(
                'severity' => 'warn',
                'type'     => 'social',
                'message'  => __( 'Twitter description missing.', 'beseo' ),
            );
        }
        if ( ! $has_tw_image ) {
            $issues[] = array(
                'severity' => 'warn',
                'type'     => 'social',
                'message'  => __( 'Twitter image missing.', 'beseo' ),
            );
        }
    }

    return array(
        'url'      => $url,
        'duration' => $duration_ms,
        'status'   => $status,
        'issues'   => $issues,
        'body'     => $body,
        'title'    => $title_text,
        'internal' => array_values( array_unique( $internal_links ) ),
    );
}
