<?php
/**
 * Playfair capture integration.
 *
 * Handles remote/local capture endpoints and parsing of result bundles.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Determine whether a host is private or localhost.
 *
 * @param string $host Hostname or IPv4 string.
 * @return bool
 */
function be_schema_playfair_is_local_host( $host ) {
    $host = strtolower( trim( (string) $host ) );
    if ( '' === $host ) {
        return false;
    }

    if ( 'localhost' === $host || '127.0.0.1' === $host ) {
        return true;
    }

    if ( '.local' === substr( $host, -6 ) ) {
        return true;
    }

    if ( filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
        $parts = array_map( 'intval', explode( '.', $host ) );
        if ( 4 === count( $parts ) ) {
            if ( 10 === $parts[0] ) {
                return true;
            }
            if ( 127 === $parts[0] ) {
                return true;
            }
            if ( 192 === $parts[0] && 168 === $parts[1] ) {
                return true;
            }
            if ( 172 === $parts[0] && $parts[1] >= 16 && $parts[1] <= 31 ) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Check if a target URL should be treated as local/private.
 *
 * @param string $url Target URL.
 * @return bool
 */
function be_schema_playfair_is_local_target( $url ) {
    $host = parse_url( $url, PHP_URL_HOST );
    if ( ! $host ) {
        return false;
    }

    return be_schema_playfair_is_local_host( $host );
}

/**
 * Normalize target mode.
 *
 * @param string $mode Mode.
 * @return string
 */
function be_schema_playfair_normalize_mode( $mode ) {
    $mode = strtolower( trim( (string) $mode ) );
    if ( in_array( $mode, array( 'local', 'vps', 'auto' ), true ) ) {
        return $mode;
    }
    return 'auto';
}

/**
 * Resolve Playfair endpoint based on settings and target URL.
 *
 * @param string $target_url Target URL.
 * @param array  $settings   Schema settings.
 * @param string $mode_override Optional mode override.
 * @return array {mode, endpoint, error?}
 */
function be_schema_playfair_resolve_endpoint( $target_url, array $settings, $mode_override = '' ) {
    $mode = $mode_override ? be_schema_playfair_normalize_mode( $mode_override ) : be_schema_playfair_normalize_mode( $settings['playfair_target_mode'] ?? 'auto' );

    if ( 'auto' === $mode ) {
        $mode = be_schema_playfair_is_local_target( $target_url ) ? 'local' : 'vps';
    }

    $endpoint = ( 'local' === $mode )
        ? ( $settings['playfair_local_endpoint'] ?? '' )
        : ( $settings['playfair_vps_endpoint'] ?? '' );

    if ( empty( $endpoint ) ) {
        return array(
            'error' => __( 'Playfair endpoint is not configured.', 'beseo' ),
        );
    }

    return array(
        'mode'     => $mode,
        'endpoint' => $endpoint,
    );
}

/**
 * Extract a zip to a destination directory (safe, file-by-file).
 *
 * @param ZipArchive $zip Zip archive.
 * @param string     $dest Destination directory.
 * @return array Extracted files map (relative name => path).
 */
function be_schema_playfair_extract_zip( ZipArchive $zip, $dest ) {
    $files = array();
    $dest  = rtrim( (string) $dest, '/\\' );

    for ( $i = 0; $i < $zip->numFiles; $i++ ) {
        $stat = $zip->statIndex( $i );
        if ( empty( $stat['name'] ) ) {
            continue;
        }
        $name = ltrim( (string) $stat['name'], '/\\' );
        if ( '' === $name || '/' === substr( $name, -1 ) ) {
            continue;
        }
        if ( false !== strpos( $name, '..' ) ) {
            continue;
        }
        $target = $dest . '/' . $name;
        if ( 0 !== strpos( $target, $dest ) ) {
            continue;
        }

        $dir = dirname( $target );
        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        $stream = $zip->getStream( $stat['name'] );
        if ( ! $stream ) {
            continue;
        }
        $out = fopen( $target, 'w' );
        if ( ! $out ) {
            fclose( $stream );
            continue;
        }
        while ( ! feof( $stream ) ) {
            fwrite( $out, fread( $stream, 8192 ) );
        }
        fclose( $out );
        fclose( $stream );

        $files[ $name ] = $target;
    }

    return $files;
}

/**
 * Decode a JSON file.
 *
 * @param string $path File path.
 * @return array|null
 */
function be_schema_playfair_read_json_file( $path ) {
    if ( empty( $path ) || ! file_exists( $path ) ) {
        return null;
    }
    $raw = file_get_contents( $path );
    if ( false === $raw ) {
        return null;
    }
    $decoded = json_decode( $raw, true );
    return is_array( $decoded ) ? $decoded : null;
}

/**
 * Decode a JSONL file (line-delimited JSON).
 *
 * @param string $path File path.
 * @return array
 */
function be_schema_playfair_read_json_lines( $path ) {
    if ( empty( $path ) || ! file_exists( $path ) ) {
        return array();
    }
    $handle = fopen( $path, 'r' );
    if ( ! $handle ) {
        return array();
    }
    $items = array();
    while ( ( $line = fgets( $handle ) ) !== false ) {
        $line = trim( $line );
        if ( '' === $line ) {
            continue;
        }
        $decoded = json_decode( $line, true );
        if ( is_array( $decoded ) ) {
            $items[] = $decoded;
        } else {
            $items[] = array( 'raw' => $line );
        }
    }
    fclose( $handle );

    return $items;
}

/**
 * Run a Playfair capture for the provided URL.
 *
 * @param string $target_url Target URL.
 * @param array  $args Optional overrides: mode, profile, wait_ms, out_dir.
 * @return array Result payload.
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

    if ( ! wp_http_validate_url( $target_url ) ) {
        return array(
            'ok'      => false,
            'message' => __( 'Target URL must be a valid http/https URL.', 'beseo' ),
        );
    }

    $settings = function_exists( 'be_schema_engine_get_settings' ) ? be_schema_engine_get_settings() : array();
    $mode_override = isset( $args['mode'] ) ? (string) $args['mode'] : '';

    $endpoint_data = be_schema_playfair_resolve_endpoint( $target_url, $settings, $mode_override );
    if ( ! empty( $endpoint_data['error'] ) ) {
        return array(
            'ok'      => false,
            'message' => $endpoint_data['error'],
        );
    }

    $mode     = $endpoint_data['mode'];
    $endpoint = $endpoint_data['endpoint'];

    if ( 'vps' === $mode && be_schema_playfair_is_local_target( $target_url ) ) {
        $allow_local = apply_filters( 'be_schema_playfair_allow_vps_local_targets', false, $target_url );
        if ( ! $allow_local ) {
            return array(
                'ok'      => false,
                'message' => __( 'Target appears to be local/private. Use Auto or Force Local mode.', 'beseo' ),
            );
        }
    }

    $profile = isset( $args['profile'] ) ? sanitize_text_field( $args['profile'] ) : ( $settings['playfair_default_profile'] ?? 'desktop_chromium' );
    if ( ! in_array( $profile, array( 'desktop_chromium', 'mobile_chromium', 'webkit' ), true ) ) {
        $profile = 'desktop_chromium';
    }

    $wait_ms = isset( $args['wait_ms'] ) ? absint( $args['wait_ms'] ) : (int) ( $settings['playfair_default_wait_ms'] ?? 1500 );
    if ( $wait_ms > 60000 ) {
        $wait_ms = 60000;
    }

    $request_body = array(
        'url'     => $target_url,
        'waitMs'  => $wait_ms,
        'profile' => $profile,
    );
    if ( ! empty( $args['out_dir'] ) ) {
        $request_body['outDir'] = (string) $args['out_dir'];
    }

    $headers = array(
        'Content-Type' => 'application/json',
    );
    if ( 'vps' === $mode ) {
        $token = $settings['playfair_vps_token'] ?? '';
        if ( '' === $token ) {
            return array(
                'ok'      => false,
                'message' => __( 'Playfair VPS token is missing.', 'beseo' ),
            );
        }
        $headers['X-Playfair-Token'] = $token;
    }

    $response = wp_remote_post(
        $endpoint,
        array(
            'timeout' => 300,
            'headers' => $headers,
            'body'    => wp_json_encode( $request_body ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return array(
            'ok'      => false,
            'message' => $response->get_error_message(),
        );
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $body        = wp_remote_retrieve_body( $response );

    if ( 200 !== (int) $status_code ) {
        $error_message = __( 'Playfair capture failed.', 'beseo' );
        if ( $body ) {
            $decoded = json_decode( $body, true );
            if ( is_array( $decoded ) && ! empty( $decoded['error'] ) ) {
                $error_message = (string) $decoded['error'];
            }
        }
        return array(
            'ok'      => false,
            'message' => $error_message,
            'status'  => $status_code,
        );
    }

    if ( empty( $body ) ) {
        return array(
            'ok'      => false,
            'message' => __( 'Playfair returned an empty response.', 'beseo' ),
        );
    }

    if ( ! class_exists( 'ZipArchive' ) ) {
        return array(
            'ok'      => false,
            'message' => __( 'ZipArchive is not available on this server.', 'beseo' ),
        );
    }

    $uploads = wp_upload_dir();
    if ( ! empty( $uploads['error'] ) ) {
        return array(
            'ok'      => false,
            'message' => $uploads['error'],
        );
    }

    $base_dir = trailingslashit( $uploads['basedir'] ) . 'playfair';
    if ( ! wp_mkdir_p( $base_dir ) ) {
        return array(
            'ok'      => false,
            'message' => __( 'Failed to create Playfair uploads directory.', 'beseo' ),
        );
    }

    $capture_id  = 'capture-' . gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 6, false, false );
    $capture_dir = $base_dir . '/' . $capture_id;
    if ( ! wp_mkdir_p( $capture_dir ) ) {
        return array(
            'ok'      => false,
            'message' => __( 'Failed to create capture directory.', 'beseo' ),
        );
    }

    $zip_path = $capture_dir . '/capture.zip';
    $bytes    = file_put_contents( $zip_path, $body );
    if ( false === $bytes ) {
        return array(
            'ok'      => false,
            'message' => __( 'Failed to write capture zip.', 'beseo' ),
        );
    }

    $zip = new ZipArchive();
    if ( true !== $zip->open( $zip_path ) ) {
        return array(
            'ok'      => false,
            'message' => __( 'Failed to open capture zip.', 'beseo' ),
        );
    }

    $extract_dir = $capture_dir . '/extract';
    wp_mkdir_p( $extract_dir );
    $files = be_schema_playfair_extract_zip( $zip, $extract_dir );
    $zip->close();

    $base_url  = trailingslashit( $uploads['baseurl'] ) . 'playfair/' . $capture_id . '/extract/';
    $file_urls = array();
    foreach ( $files as $name => $path ) {
        $file_urls[ $name ] = $base_url . str_replace( '\\', '/', $name );
    }

    $result = array(
        'ok'      => true,
        'message' => __( 'Capture completed.', 'beseo' ),
        'meta'    => array(
            'target'   => $target_url,
            'mode'     => $mode,
            'endpoint' => $endpoint,
            'profile'  => $profile,
            'wait_ms'  => $wait_ms,
        ),
        'paths'   => array(
            'capture_id' => $capture_id,
            'capture_dir' => $capture_dir,
            'zip'         => $zip_path,
            'extract_dir' => $extract_dir,
            'files'       => $files,
            'file_urls'   => $file_urls,
        ),
        'schema' => array(
            'dom'    => be_schema_playfair_read_json_file( $files['schema.dom.json'] ?? '' ),
            'server' => be_schema_playfair_read_json_file( $files['schema.server.json'] ?? '' ),
        ),
        'opengraph' => array(
            'dom'    => be_schema_playfair_read_json_file( $files['opengraph.dom.json'] ?? '' ),
            'server' => be_schema_playfair_read_json_file( $files['opengraph.server.json'] ?? '' ),
        ),
        'logs' => array(
            'console'       => be_schema_playfair_read_json_lines( $files['console.jsonl'] ?? '' ),
            'pageerrors'    => be_schema_playfair_read_json_lines( $files['pageerrors.jsonl'] ?? '' ),
            'requestfailed' => be_schema_playfair_read_json_lines( $files['requestfailed.jsonl'] ?? '' ),
        ),
    );

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
}
