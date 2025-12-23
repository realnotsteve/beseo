<?php
/**
 * WP-CLI integration for Playfair captures.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

/**
 * Playfair capture commands.
 */
class BE_Schema_Playfair_CLI {
    /**
     * Run a Playfair capture for a URL or post ID.
     *
     * ## OPTIONS
     *
     * <target>
     * : URL or post ID to capture.
     *
     * [--mode=<mode>]
     * : auto, local, or vps.
     *
     * [--profile=<profile>]
     * : desktop_chromium, mobile_chromium, or webkit.
     *
     * [--wait-ms=<ms>]
     * : Wait time in milliseconds.
     *
     * [--json]
     * : Output full JSON result.
     *
     * ## EXAMPLES
     *
     *     wp beseo playfair capture https://example.com --mode=auto --profile=desktop_chromium
     *
     * @param array $args Positional args.
     * @param array $assoc_args Assoc args.
     */
    public function capture( $args, $assoc_args ) {
        $target = isset( $args[0] ) ? $args[0] : '';
        if ( ! $target ) {
            WP_CLI::error( 'Target URL or post ID is required.' );
        }

        $options = array();
        if ( ! empty( $assoc_args['mode'] ) ) {
            $options['mode'] = $assoc_args['mode'];
        }
        if ( ! empty( $assoc_args['profile'] ) ) {
            $options['profile'] = $assoc_args['profile'];
        }
        if ( isset( $assoc_args['wait-ms'] ) ) {
            $options['wait_ms'] = (int) $assoc_args['wait-ms'];
        }

        $result = be_schema_playfair_capture( $target, $options );
        if ( empty( $result['ok'] ) ) {
            WP_CLI::error( $result['message'] ?? 'Capture failed.' );
        }

        if ( ! empty( $assoc_args['json'] ) ) {
            WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
            return;
        }

        $meta   = $result['meta'] ?? array();
        $paths  = $result['paths'] ?? array();
        $schema = $result['schema'] ?? array();
        $og     = $result['opengraph'] ?? array();
        $logs   = $result['logs'] ?? array();

        WP_CLI::success( 'Capture complete.' );
        WP_CLI::line( 'Target: ' . ( $meta['target'] ?? '' ) );
        WP_CLI::line( 'Mode: ' . ( $meta['mode'] ?? '' ) );
        WP_CLI::line( 'Endpoint: ' . ( $meta['endpoint'] ?? '' ) );
        WP_CLI::line( 'Profile: ' . ( $meta['profile'] ?? '' ) );
        WP_CLI::line( 'Wait: ' . ( isset( $meta['wait_ms'] ) ? $meta['wait_ms'] . 'ms' : '' ) );
        if ( ! empty( $paths['capture_id'] ) ) {
            WP_CLI::line( 'Capture ID: ' . $paths['capture_id'] );
        }
        WP_CLI::line( 'Schema DOM: ' . ( is_array( $schema['dom'] ?? null ) ? 'ok' : 'none' ) );
        WP_CLI::line( 'Schema Server: ' . ( is_array( $schema['server'] ?? null ) ? 'ok' : 'none' ) );
        WP_CLI::line( 'OG DOM: ' . ( is_array( $og['dom'] ?? null ) ? 'ok' : 'none' ) );
        WP_CLI::line( 'OG Server: ' . ( is_array( $og['server'] ?? null ) ? 'ok' : 'none' ) );
        WP_CLI::line( 'Console logs: ' . count( $logs['console'] ?? array() ) );
        WP_CLI::line( 'Page errors: ' . count( $logs['pageerrors'] ?? array() ) );
        WP_CLI::line( 'Request failed: ' . count( $logs['requestfailed'] ?? array() ) );
    }
}

WP_CLI::add_command( 'beseo playfair', 'BE_Schema_Playfair_CLI' );
