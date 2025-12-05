<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add one or many nodes to the global debug graph.
 *
 * @param array $nodes Single node or list of nodes.
 */
function be_schema_debug_collect( $nodes ) {
    if ( empty( $nodes ) ) {
        return;
    }

    if ( ! isset( $GLOBALS['be_schema_debug_graph'] ) || ! is_array( $GLOBALS['be_schema_debug_graph'] ) ) {
        $GLOBALS['be_schema_debug_graph'] = array();
    }

    if ( isset( $nodes['@type'] ) || isset( $nodes['@id'] ) ) {
        // Single node.
        $GLOBALS['be_schema_debug_graph'][] = $nodes;
    } elseif ( is_array( $nodes ) ) {
        // List of nodes.
        foreach ( $nodes as $node ) {
            if ( is_array( $node ) ) {
                $GLOBALS['be_schema_debug_graph'][] = $node;
            }
        }
    }
}

/**
 * Determine whether debug graph logging is enabled at the plugin level.
 *
 * Priority:
 * - If BE_SCHEMA_DEBUG is defined, use that (bool cast).
 * - Otherwise, use the 'debug' setting.
 *
 * @return bool
 */
function be_schema_debug_enabled() {
    if ( defined( 'BE_SCHEMA_DEBUG' ) ) {
        return (bool) BE_SCHEMA_DEBUG;
    }

    $settings = be_schema_engine_get_settings();

    return ( isset( $settings['debug'] ) && $settings['debug'] === '1' );
}

/**
 * On shutdown, if:
 * - WP_DEBUG is true, AND
 * - be_schema_debug_enabled() is true, AND
 * - debug graph is non-empty,
 * then log the @graph to error_log().
 */
function be_schema_debug_shutdown_logger() {
    if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
        return;
    }

    if ( ! be_schema_debug_enabled() ) {
        return;
    }

    if ( empty( $GLOBALS['be_schema_debug_graph'] ) || ! is_array( $GLOBALS['be_schema_debug_graph'] ) ) {
        return;
    }

    $graph = array(
        '@context' => 'https://schema.org',
        '@graph'   => $GLOBALS['be_schema_debug_graph'],
    );

    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    error_log( 'BE_SCHEMA_DEBUG_GRAPH: ' . wp_json_encode( $graph ) );
}
add_action( 'shutdown', 'be_schema_debug_shutdown_logger', 20 );