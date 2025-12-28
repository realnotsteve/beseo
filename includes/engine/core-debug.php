<?php
/**
 * BE Schema Engine - Core debug utilities.
 *
 * Responsible for:
 * - Central debug gating (WP_DEBUG + plugin flag / constant).
 * - Structured debug events.
 * - Optional JSON log payloads for schema/social engines.
 * - Shutdown summary of graphs emitted / skipped by reason.
 *
 * @package BE_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether schema/social debug logging is enabled.
 *
 * Requires:
 * - WP_DEBUG true, AND
 * - plugin-level debug flag or BE_SCHEMA_DEBUG constant (if present).
 *
 * The plugin-level flag is expected in the main settings array as:
 *   be_schema_engine_settings['debug_enabled']
 *
 * This should be conservative: "on" only when explicitly requested.
 *
 * @return bool
 */
function be_schema_is_debug_enabled() {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return false;
	}

	if ( defined( 'BE_SCHEMA_DEBUG' ) && BE_SCHEMA_DEBUG ) {
		return true;
	}

	if ( function_exists( 'be_schema_engine_get_settings' ) ) {
		$settings = be_schema_engine_get_settings();
	} else {
		$settings = get_option( 'be_schema_engine_settings', array() );
	}

	if ( ! empty( $settings['debug_enabled'] ) || ! empty( $settings['debug'] ) ) {
		return true;
	}

	return false;
}

/**
 * Internal accumulator for debug events within this request.
 *
 * We store events in a static variable so that:
 * - Multiple calls can accumulate context.
 * - A shutdown handler can emit a summary.
 *
 * @param string $event Event name.
 * @param array  $data  Event data.
 *
 * @return array All recorded events so far.
 */
function be_schema_debug_store( $event, array $data = array() ) {
	static $events = null;

	if ( null === $events ) {
		$events = array();
	}

	// Do not record the synthetic "__read__" event.
	if ( '__read__' !== $event ) {
		$events[] = array(
			'event' => (string) $event,
			'data'  => $data,
		);
	}

	return $events;
}

/**
 * Record a structured debug event (in-memory) and optionally log immediately.
 *
 * Intended usage:
 *
 *   be_schema_debug_event( 'homepage_schema_skipped', array(
 *       'reason' => 'not_front_page',
 *       'url'    => home_url( '/' ),
 *   ) );
 *
 * @param string $event           Event name.
 * @param array  $data            Event payload.
 * @param bool   $log_immediately Whether to emit an error_log line immediately.
 *
 * @return void
 */
function be_schema_debug_event( $event, array $data = array(), $log_immediately = false ) {
	if ( ! be_schema_is_debug_enabled() ) {
		return;
	}

	$event = (string) $event;

	// Store in the in-request accumulator.
	be_schema_debug_store( $event, $data );

	if ( $log_immediately ) {
		error_log(
			'BE_SCHEMA_DEBUG_EVENT ' . wp_json_encode(
				array(
					'event' => $event,
					'data'  => $data,
				)
			)
		);
	}
}

/**
 * Retrieve all accumulated debug events for this request.
 *
 * @return array
 */
function be_schema_debug_get_events() {
	return be_schema_debug_store( '__read__', array() );
}

/**
 * Collect an emitted schema graph for debugging.
 *
 * Stores the graph and logs immediately when debug is enabled.
 *
 * @param array $graph Graph payload (@graph or single node array).
 * @return void
 */
function be_schema_debug_collect( array $graph ) {
	if ( ! be_schema_is_debug_enabled() ) {
		return;
	}

	// Store for shutdown summary visibility.
	be_schema_debug_store(
		'schema_graph_emitted',
		array(
			'graph' => $graph,
		)
	);

	// Emit a lightweight log line for inspection.
	error_log(
		'BE_SCHEMA_DEBUG_GRAPH ' . wp_json_encode(
			array(
				'graph' => $graph,
			)
		)
	);

}

/**
 * Helper: does the event name end with a given suffix?
 *
 * Compatible with PHP 7.4+ (no str_ends_with).
 *
 * @param string $haystack Full string.
 * @param string $needle   Suffix to test.
 *
 * @return bool
 */
function be_schema_str_ends_with( $haystack, $needle ) {
	$haystack = (string) $haystack;
	$needle   = (string) $needle;

	if ( '' === $needle ) {
		return true;
	}

	$len_h = strlen( $haystack );
	$len_n = strlen( $needle );

	if ( $len_n > $len_h ) {
		return false;
	}

	return substr( $haystack, - $len_n ) === $needle;
}

/**
 * Emit a summary debug event at shutdown.
 *
 * We look at all recorded events and compute:
 * - graphs_attempted
 * - graphs_emitted
 * - skips_by_reason (reason => count)
 *
 * Events are expected to use the following conventions:
 * - 'schema_graph_attempt' records that some emitter attempted to build a graph.
 * - 'schema_graph_emitted' records a successful JSON-LD output.
 * - Any event whose name ends with '_schema_skipped' should provide 'reason' in $data.
 *
 * This does not replace any existing BE_SCHEMA_DEBUG_GRAPH payloads; it complements them.
 *
 * @return void
 */
function be_schema_debug_shutdown_summary() {
	if ( ! be_schema_is_debug_enabled() ) {
		return;
	}

	$events = be_schema_debug_get_events();
	if ( empty( $events ) || ! is_array( $events ) ) {
		return;
	}

	$graphs_attempted = 0;
	$graphs_emitted   = 0;
	$skips_by_reason  = array();

	foreach ( $events as $event ) {
		if ( ! is_array( $event ) ) {
			continue;
		}

		$name = isset( $event['event'] ) ? (string) $event['event'] : '';
		$data = isset( $event['data'] ) ? (array) $event['data'] : array();

		if ( 'schema_graph_attempt' === $name ) {
			$graphs_attempted++;
		} elseif ( 'schema_graph_emitted' === $name ) {
			$graphs_emitted++;
		} elseif ( be_schema_str_ends_with( $name, '_schema_skipped' ) ) {
			$reason = isset( $data['reason'] ) ? (string) $data['reason'] : 'unknown';
			if ( ! isset( $skips_by_reason[ $reason ] ) ) {
				$skips_by_reason[ $reason ] = 0;
			}
			$skips_by_reason[ $reason ]++;
		}
	}

	$summary = array(
		'graphs_attempted' => $graphs_attempted,
		'graphs_emitted'   => $graphs_emitted,
		'skips_by_reason'  => $skips_by_reason,
	);

	error_log( 'BE_SCHEMA_DEBUG_SUMMARY ' . wp_json_encode( $summary ) );
}

add_action( 'shutdown', 'be_schema_debug_shutdown_summary', 9999 );

/**
 * Convenience wrapper used by schema emitters to mark the start of a graph attempt.
 *
 * Example usage in emitters:
 *
 *   be_schema_debug_event( 'schema_graph_attempt', array(
 *       'context' => 'homepage',
 *       'url'     => home_url( '/' ),
 *   ) );
 *
 * and on success:
 *
 *   be_schema_debug_event( 'schema_graph_emitted', array(
 *       'context' => 'homepage',
 *       'url'     => home_url( '/' ),
 *   ) );
 *
 * For skips, use event names that end with '_schema_skipped' and provide 'reason':
 *
 *   be_schema_debug_event( 'homepage_schema_skipped', array(
 *       'reason' => 'not_front_page',
 *       'url'    => home_url( '/' ),
 *   ) );
 */
