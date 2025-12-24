<?php
/**
 * Playfair capture integration.
 *
 * Client for local/remote Playfair endpoints that return JSON responses.
 *
 * Author: Bill Evans
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize mode values.
 *
 * @param string $mode Mode string.
 * @return string
 */
function be_schema_playfair_normalize_mode( $mode ) {
    $mode = strtolower( trim( (string) $mode ) );
    if ( 'vps' === $mode ) {
        $mode = 'remote';
    }
    if ( in_array( $mode, array( 'remote', 'local', 'auto' ), true ) ) {
        return $mode;
    }
    return 'auto';
}

/**
 * Build a Playfair endpoint URL from a base URL + path.
 *
 * @param string $base Base URL.
 * @param string $path Path like '/capture' or '/health'.
 * @return string
 */
function be_schema_playfair_build_endpoint( $base, $path ) {
    $base = trim( (string) $base );
    if ( '' === $base ) {
        return '';
    }

    $path = '/' . ltrim( (string) $path, '/' );

    if ( preg_match( '#/(capture|health)$#', $base ) ) {
        if ( substr( $base, -strlen( $path ) ) === $path ) {
            return $base;
        }
        $base = preg_replace( '#/(capture|health)$#', '', $base );
    }

    return rtrim( $base, '/' ) . $path;
}

/**
 * Check if IP is private/local.
 *
 * @param string $ip IP address.
 * @return bool
 */
function be_schema_playfair_is_private_ip( $ip ) {
    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
        $parts = array_map( 'intval', explode( '.', $ip ) );
        if ( 4 !== count( $parts ) ) {
            return false;
        }
        if ( 10 === $parts[0] ) {
            return true;
        }
        if ( 127 === $parts[0] ) {
            return true;
        }
        if ( 0 === $parts[0] ) {
            return true;
        }
        if ( 169 === $parts[0] && 254 === $parts[1] ) {
            return true;
        }
        if ( 172 === $parts[0] && $parts[1] >= 16 && $parts[1] <= 31 ) {
            return true;
        }
        if ( 192 === $parts[0] && 168 === $parts[1] ) {
            return true;
        }
        return false;
    }

    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
        $ip = strtolower( $ip );
        if ( '::1' === $ip ) {
            return true;
        }
        if ( 0 === strpos( $ip, 'fe80:' ) ) {
            return true;
        }
        if ( 0 === strpos( $ip, 'fc' ) || 0 === strpos( $ip, 'fd' ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Check if a host is private/local.
 *
 * @param string $host Hostname.
 * @return bool
 */
function be_schema_playfair_is_private_host( $host ) {
    $host = strtolower( trim( (string) $host ) );
    if ( '' === $host ) {
        return false;
    }

    if ( 'localhost' === $host || '127.0.0.1' === $host || '::1' === $host ) {
        return true;
    }

    if ( '.local' === substr( $host, -6 ) ) {
        return true;
    }

    if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
        return be_schema_playfair_is_private_ip( $host );
    }

    $resolved = gethostbyname( $host );
    if ( $resolved && $resolved !== $host ) {
        return be_schema_playfair_is_private_ip( $resolved );
    }

    return false;
}

/**
 * Check if a target URL is private/local.
 *
 * @param string $url Target URL.
 * @return bool
 */
function be_schema_playfair_is_private_target( $url ) {
    $host = parse_url( $url, PHP_URL_HOST );
    if ( ! $host ) {
        return false;
    }
    return be_schema_playfair_is_private_host( $host );
}

/**
 * Validate target URL and SSRF rules.
 *
 * @param string $url Target URL.
 * @param bool   $allow_private Whether private targets are allowed.
 * @return array {ok, message}
 */
function be_schema_playfair_validate_target( $url, $allow_private ) {
    if ( ! wp_http_validate_url( $url ) ) {
        return array(
            'ok'      => false,
            'message' => __( 'Target URL must be a valid http/https URL.', 'beseo' ),
        );
    }

    if ( ! $allow_private && be_schema_playfair_is_private_target( $url ) ) {
        return array(
            'ok'      => false,
            'message' => __( 'Target URL is private or local. Enable developer mode to allow private targets.', 'beseo' ),
        );
    }

    return array( 'ok' => true );
}

/**
 * Normalize schema list entries to {raw, parsed}.
 *
 * @param mixed $entries Entries list.
 * @return array
 */
function be_schema_playfair_normalize_schema_entries( $entries ) {
    if ( empty( $entries ) || ! is_array( $entries ) ) {
        return array();
    }

    $normalized = array();
    foreach ( $entries as $entry ) {
        if ( is_string( $entry ) ) {
            $parsed = json_decode( $entry, true );
            $normalized[] = array(
                'raw'    => $entry,
                'parsed' => is_array( $parsed ) ? $parsed : null,
            );
            continue;
        }

        if ( is_array( $entry ) ) {
            if ( array_key_exists( 'raw', $entry ) || array_key_exists( 'parsed', $entry ) ) {
                $raw = isset( $entry['raw'] ) ? (string) $entry['raw'] : '';
                $parsed = isset( $entry['parsed'] ) && is_array( $entry['parsed'] ) ? $entry['parsed'] : null;
                if ( '' === $raw && $parsed ) {
                    $raw = wp_json_encode( $parsed );
                }
                $normalized[] = array(
                    'raw'    => $raw,
                    'parsed' => $parsed,
                );
                continue;
            }

            $normalized[] = array(
                'raw'    => wp_json_encode( $entry ),
                'parsed' => $entry,
            );
        }
    }

    return $normalized;
}

/**
 * Normalize Open Graph list entries.
 *
 * @param mixed $entries Entries list.
 * @return array
 */
function be_schema_playfair_normalize_og_entries( $entries ) {
    if ( empty( $entries ) || ! is_array( $entries ) ) {
        return array();
    }

    $normalized = array();
    foreach ( $entries as $entry ) {
        if ( is_array( $entry ) ) {
            $normalized[] = $entry;
        } elseif ( is_string( $entry ) ) {
            $normalized[] = array( 'raw' => $entry );
        }
    }

    return $normalized;
}

/**
 * Build request arguments.
 *
 * @param array $settings Settings.
 * @param array $overrides Overrides.
 * @return array
 */
function be_schema_playfair_build_request_options( array $settings, array $overrides ) {
    $profile = isset( $overrides['profile'] ) ? sanitize_text_field( $overrides['profile'] ) : ( $settings['playfair_default_profile'] ?? 'desktop_chromium' );
    if ( ! in_array( $profile, array( 'desktop_chromium', 'mobile_chromium', 'webkit' ), true ) ) {
        $profile = 'desktop_chromium';
    }

    $wait_ms = isset( $overrides['wait_ms'] ) ? absint( $overrides['wait_ms'] ) : (int) ( $settings['playfair_default_wait_ms'] ?? 1500 );
    if ( $wait_ms > 60000 ) {
        $wait_ms = 60000;
    }

    $include_html = isset( $overrides['include_html'] ) ? (bool) $overrides['include_html'] : ! empty( $settings['playfair_include_html_default'] );
    $include_logs = isset( $overrides['include_logs'] ) ? (bool) $overrides['include_logs'] : ! empty( $settings['playfair_include_logs_default'] );

    $locale = isset( $overrides['locale'] ) ? sanitize_text_field( $overrides['locale'] ) : ( $settings['playfair_default_locale'] ?? '' );
    $timezone = isset( $overrides['timezone_id'] ) ? sanitize_text_field( $overrides['timezone_id'] ) : ( $settings['playfair_default_timezone'] ?? '' );

    $payload = array(
        'profile'    => $profile,
        'waitMs'     => $wait_ms,
        'includeHtml' => $include_html,
        'includeLogs' => $include_logs,
    );

    if ( $locale ) {
        $payload['locale'] = $locale;
    }

    if ( $timezone ) {
        $payload['timezoneId'] = $timezone;
    }

    return array(
        'payload'      => $payload,
        'profile'      => $profile,
        'wait_ms'      => $wait_ms,
        'include_html' => $include_html,
        'include_logs' => $include_logs,
        'locale'       => $locale,
        'timezone_id'  => $timezone,
    );
}

/**
 * Perform HTTP request to Playfair.
 *
 * @param string $method GET/POST.
 * @param string $url URL.
 * @param array  $headers Headers.
 * @param array|null $body JSON body.
 * @param int    $timeout Timeout seconds.
 * @return array
 */
function be_schema_playfair_http_request( $method, $url, array $headers, $body, $timeout ) {
    $args = array(
        'timeout' => $timeout,
        'headers' => $headers,
    );

    if ( 'POST' === $method ) {
        $args['body'] = wp_json_encode( $body );
    }

    $response = ( 'POST' === $method ) ? wp_remote_post( $url, $args ) : wp_remote_get( $url, $args );
    if ( is_wp_error( $response ) ) {
        return array(
            'ok'      => false,
            'status'  => 0,
            'message' => $response->get_error_message(),
        );
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $raw_body    = wp_remote_retrieve_body( $response );
    $decoded     = null;
    if ( $raw_body ) {
        $decoded = json_decode( $raw_body, true );
    }

    if ( 200 !== (int) $status_code ) {
        $error_message = __( 'Playfair request failed.', 'beseo' );
        if ( is_array( $decoded ) && ! empty( $decoded['error'] ) ) {
            $error_message = (string) $decoded['error'];
        }
        return array(
            'ok'      => false,
            'status'  => $status_code,
            'message' => $error_message,
            'data'    => $decoded,
        );
    }

    if ( ! is_array( $decoded ) ) {
        return array(
            'ok'      => false,
            'status'  => $status_code,
            'message' => __( 'Playfair returned invalid JSON.', 'beseo' ),
        );
    }

    if ( empty( $decoded['ok'] ) ) {
        $error_message = ! empty( $decoded['error'] ) ? (string) $decoded['error'] : __( 'Playfair returned ok=false.', 'beseo' );
        return array(
            'ok'      => false,
            'status'  => $status_code,
            'message' => $error_message,
            'data'    => $decoded,
        );
    }

    return array(
        'ok'     => true,
        'status' => $status_code,
        'data'   => $decoded,
    );
}

/**
 * Resolve Playfair endpoints based on mode.
 *
 * @param array $settings Settings.
 * @param string $mode Mode.
 * @return array
 */
function be_schema_playfair_get_endpoints( array $settings, $mode ) {
    $remote_base = $settings['playfair_remote_base_url'] ?? '';
    $local_base  = $settings['playfair_local_base_url'] ?? '';

    return array(
        'remote' => be_schema_playfair_build_endpoint( $remote_base, '/capture' ),
        'local'  => be_schema_playfair_build_endpoint( $local_base, '/capture' ),
    );
}

/**
 * Resolve Playfair health endpoints based on mode.
 *
 * @param array $settings Settings.
 * @return array
 */
function be_schema_playfair_get_health_endpoints( array $settings ) {
    $remote_base = $settings['playfair_remote_base_url'] ?? '';
    $local_base  = $settings['playfair_local_base_url'] ?? '';

    return array(
        'remote' => be_schema_playfair_build_endpoint( $remote_base, '/health' ),
        'local'  => be_schema_playfair_build_endpoint( $local_base, '/health' ),
    );
}

/**
 * Run a Playfair health check.
 *
 * @param array  $settings Settings.
 * @param string $mode Mode override.
 * @return array
 */
function be_schema_playfair_health( array $settings, $mode = '' ) {
    $mode = be_schema_playfair_normalize_mode( $mode ? $mode : ( $settings['playfair_mode'] ?? 'auto' ) );
    $timeout = isset( $settings['playfair_timeout_seconds'] ) ? (int) $settings['playfair_timeout_seconds'] : 60;
    if ( $timeout < 5 ) {
        $timeout = 5;
    }

    $endpoints = be_schema_playfair_get_health_endpoints( $settings );
    $token = $settings['playfair_remote_token'] ?? '';

    $attempt = function( $target_mode ) use ( $endpoints, $token, $timeout ) {
        $endpoint = $endpoints[ $target_mode ] ?? '';
        if ( '' === $endpoint ) {
            return array(
                'ok'      => false,
                'message' => __( 'Playfair endpoint is not configured.', 'beseo' ),
            );
        }

        $headers = array();
        if ( 'remote' === $target_mode && '' !== $token ) {
            $headers['X-Playfair-Token'] = $token;
        }

        $response = be_schema_playfair_http_request( 'GET', $endpoint, $headers, null, $timeout );
        $response['endpoint'] = $endpoint;
        $response['mode'] = $target_mode;
        return $response;
    };

    if ( 'auto' !== $mode ) {
        return $attempt( $mode );
    }

    $remote = $attempt( 'remote' );
    if ( ! empty( $remote['ok'] ) ) {
        $remote['fallback'] = false;
        return $remote;
    }

    $local = $attempt( 'local' );
    $local['fallback'] = true;
    $local['fallback_error'] = $remote['message'] ?? '';

    return $local;
}

/**
 * Run a Playfair capture for the provided URL.
 *
 * @param string $target_url Target URL or post ID.
 * @param array  $args Optional overrides: mode, profile, wait_ms, include_html, include_logs, locale, timezone_id.
 * @return array
 */
function be_schema_playfair_capture( $target_url, array $args = array() ) {
    $target_url = trim( (string) $target_url );
    if ( '' === $target_url ) {
        return array(
            'ok'      => false,
            'message' => __( 'Target URL is required.', 'beseo' ),
        );
    }

    if ( is_numeric( $target_url ) ) {
        $permalink = get_permalink( (int) $target_url );
        if ( $permalink ) {
            $target_url = $permalink;
        }
    }

    $settings = function_exists( 'be_schema_engine_get_settings' ) ? be_schema_engine_get_settings() : array();

    $allow_private = ! empty( $settings['playfair_allow_private_targets'] );
    $validation = be_schema_playfair_validate_target( $target_url, $allow_private );
    if ( empty( $validation['ok'] ) ) {
        return array(
            'ok'      => false,
            'message' => $validation['message'],
        );
    }

    $mode = be_schema_playfair_normalize_mode( isset( $args['mode'] ) ? $args['mode'] : ( $settings['playfair_mode'] ?? 'auto' ) );
    $timeout = isset( $settings['playfair_timeout_seconds'] ) ? (int) $settings['playfair_timeout_seconds'] : 60;
    if ( $timeout < 5 ) {
        $timeout = 5;
    }

    $request_options = be_schema_playfair_build_request_options( $settings, $args );
    $payload = $request_options['payload'];
    $payload['url'] = $target_url;

    $token = $settings['playfair_remote_token'] ?? '';
    $endpoints = be_schema_playfair_get_endpoints( $settings, $mode );

    $attempt = function( $target_mode ) use ( $endpoints, $token, $timeout, $payload ) {
        $endpoint = $endpoints[ $target_mode ] ?? '';
        if ( '' === $endpoint ) {
            return array(
                'ok'      => false,
                'message' => __( 'Playfair endpoint is not configured.', 'beseo' ),
            );
        }

        $headers = array(
            'Content-Type' => 'application/json',
        );
        if ( 'remote' === $target_mode && '' !== $token ) {
            $headers['X-Playfair-Token'] = $token;
        }

        $response = be_schema_playfair_http_request( 'POST', $endpoint, $headers, $payload, $timeout );
        $response['endpoint'] = $endpoint;
        $response['mode'] = $target_mode;
        return $response;
    };

    if ( 'auto' !== $mode ) {
        $response = $attempt( $mode );
    } else {
        $remote = $attempt( 'remote' );
        if ( ! empty( $remote['ok'] ) ) {
            $response = $remote;
            $response['fallback'] = false;
        } else {
            $local = $attempt( 'local' );
            $local['fallback'] = true;
            $local['fallback_error'] = $remote['message'] ?? '';
            $response = $local;
        }
    }

    if ( empty( $response['ok'] ) ) {
        return array(
            'ok'      => false,
            'message' => $response['message'] ?? __( 'Capture failed.', 'beseo' ),
            'status'  => $response['status'] ?? 0,
            'meta'    => array(
                'target' => $target_url,
                'mode'   => $response['mode'] ?? $mode,
                'endpoint' => $response['endpoint'] ?? '',
            ),
        );
    }

    $data = $response['data'] ?? array();

    $result = array(
        'ok'      => true,
        'message' => __( 'Capture completed.', 'beseo' ),
        'meta'    => array(
            'target'       => $target_url,
            'mode'         => $response['mode'] ?? $mode,
            'endpoint'     => $response['endpoint'] ?? '',
            'profile'      => $data['profile'] ?? $request_options['profile'],
            'wait_ms'      => $data['waitMs'] ?? $request_options['wait_ms'],
            'include_html' => $request_options['include_html'],
            'include_logs' => $request_options['include_logs'],
            'locale'       => $request_options['locale'],
            'timezone_id'  => $request_options['timezone_id'],
            'fallback'     => isset( $response['fallback'] ) ? (bool) $response['fallback'] : false,
            'fallback_error' => $response['fallback_error'] ?? '',
        ),
        'options' => $data['options'] ?? array(),
        'opengraph' => array(
            'dom'    => be_schema_playfair_normalize_og_entries( $data['opengraph']['dom'] ?? array() ),
            'server' => be_schema_playfair_normalize_og_entries( $data['opengraph']['server'] ?? array() ),
        ),
        'schema' => array(
            'dom'    => be_schema_playfair_normalize_schema_entries( $data['schema']['dom'] ?? array() ),
            'server' => be_schema_playfair_normalize_schema_entries( $data['schema']['server'] ?? array() ),
        ),
        'logs' => array(
            'console'       => is_array( $data['logs']['console'] ?? null ) ? $data['logs']['console'] : array(),
            'pageErrors'    => is_array( $data['logs']['pageErrors'] ?? null ) ? $data['logs']['pageErrors'] : array(),
            'requestFailed' => is_array( $data['logs']['requestFailed'] ?? null ) ? $data['logs']['requestFailed'] : array(),
        ),
    );

    if ( ! empty( $data['html'] ) && is_array( $data['html'] ) ) {
        $result['html'] = array(
            'server' => isset( $data['html']['server'] ) ? (string) $data['html']['server'] : '',
            'dom'    => isset( $data['html']['dom'] ) ? (string) $data['html']['dom'] : '',
        );
    }

    return $result;
}

/**
 * Simple service wrapper for Playfair capture.
 */
class BE_Schema_Playfair_Service {
    /**
     * Run a capture and return the structured result.
     *
     * @param string $target_url Target URL.
     * @param array  $args Optional overrides.
     * @return array
     */
    public static function capture( $target_url, array $args = array() ) {
        return be_schema_playfair_capture( $target_url, $args );
    }

    /**
     * Run a health check.
     *
     * @param array  $settings Settings.
     * @param string $mode Mode override.
     * @return array
     */
    public static function health( array $settings, $mode = '' ) {
        return be_schema_playfair_health( $settings, $mode );
    }
}
