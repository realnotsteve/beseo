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
     * : auto, local, or remote.
     *
     * [--profile=<profile>]
     * : desktop_chromium, mobile_chromium, or webkit.
     *
     * [--wait-ms=<ms>]
     * : Wait time in milliseconds.
     *
     * [--include-html]
     * : Include HTML in the response.
     *
     * [--include-logs]
     * : Include console/network logs in the response.
     *
     * [--locale=<locale>]
     * : Locale override (e.g. en-US).
     *
     * [--timezone-id=<tz>]
     * : Timezone ID (e.g. America/New_York).
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
        if ( isset( $assoc_args['include-html'] ) ) {
            $options['include_html'] = true;
        }
        if ( isset( $assoc_args['include-logs'] ) ) {
            $options['include_logs'] = true;
        }
        if ( ! empty( $assoc_args['locale'] ) ) {
            $options['locale'] = $assoc_args['locale'];
        }
        if ( ! empty( $assoc_args['timezone-id'] ) ) {
            $options['timezone_id'] = $assoc_args['timezone-id'];
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
        $schema = $result['schema'] ?? array();
        $og     = $result['opengraph'] ?? array();
        $logs   = $result['logs'] ?? array();
        $html   = $result['html'] ?? array();

        WP_CLI::success( 'Capture complete.' );
        WP_CLI::line( 'Target: ' . ( $meta['target'] ?? '' ) );
        WP_CLI::line( 'Mode: ' . ( $meta['mode'] ?? '' ) );
        WP_CLI::line( 'Endpoint: ' . ( $meta['endpoint'] ?? '' ) );
        WP_CLI::line( 'Profile: ' . ( $meta['profile'] ?? '' ) );
        WP_CLI::line( 'Wait: ' . ( isset( $meta['wait_ms'] ) ? $meta['wait_ms'] . 'ms' : '' ) );
        WP_CLI::line( 'Schema DOM: ' . count( $schema['dom'] ?? array() ) );
        WP_CLI::line( 'Schema Server: ' . count( $schema['server'] ?? array() ) );
        WP_CLI::line( 'OG DOM: ' . count( $og['dom'] ?? array() ) );
        WP_CLI::line( 'OG Server: ' . count( $og['server'] ?? array() ) );
        WP_CLI::line( 'HTML DOM: ' . ( empty( $html['dom'] ) ? 'none' : 'yes' ) );
        WP_CLI::line( 'HTML Server: ' . ( empty( $html['server'] ) ? 'none' : 'yes' ) );
        WP_CLI::line( 'Console logs: ' . count( $logs['console'] ?? array() ) );
        WP_CLI::line( 'Page errors: ' . count( $logs['pageErrors'] ?? array() ) );
        WP_CLI::line( 'Request failed: ' . count( $logs['requestFailed'] ?? array() ) );
    }
}

WP_CLI::add_command( 'beseo playfair', 'BE_Schema_Playfair_CLI' );
