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
    if ( ! $url || ! wp_http_validate_url( $url ) ) {
        wp_send_json_error( array( 'message' => __( 'Provide a valid URL (http/https).', 'beseo' ) ) );
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
    $max      = isset( $_POST['max'] ) ? (int) $_POST['max'] : 20;
    $max      = max( 1, min( 100, $max ) );
    if ( ! $url || ! wp_http_validate_url( $url ) ) {
        wp_send_json_error( array( 'message' => __( 'Provide a valid URL.', 'beseo' ) ) );
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
