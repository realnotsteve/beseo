<?php
/**
 * Playfair admin handlers (AJAX + helpers).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX: Run a Playfair capture.
 */
function be_schema_playfair_capture_ajax() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'beseo' ) ), 403 );
    }

    check_ajax_referer( 'be_schema_playfair_capture', 'nonce' );

    $target = isset( $_POST['url'] ) ? trim( (string) wp_unslash( $_POST['url'] ) ) : '';
    if ( '' === $target && isset( $_POST['target'] ) ) {
        $target = trim( (string) wp_unslash( $_POST['target'] ) );
    }

    $mode    = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : '';
    $profile = isset( $_POST['profile'] ) ? sanitize_text_field( wp_unslash( $_POST['profile'] ) ) : '';
    $wait_ms = isset( $_POST['wait_ms'] ) ? absint( $_POST['wait_ms'] ) : null;

    $args = array();
    if ( $mode ) {
        $args['mode'] = $mode;
    }
    if ( $profile ) {
        $args['profile'] = $profile;
    }
    if ( null !== $wait_ms ) {
        $args['wait_ms'] = $wait_ms;
    }

    $result = be_schema_playfair_capture( $target, $args );
    if ( empty( $result['ok'] ) ) {
        $message = isset( $result['message'] ) ? $result['message'] : __( 'Capture failed.', 'beseo' );
        $status  = isset( $result['status'] ) ? (int) $result['status'] : 400;
        wp_send_json_error( array( 'message' => $message, 'details' => $result ), $status );
    }

    wp_send_json_success( $result );
}
add_action( 'wp_ajax_be_schema_playfair_capture', 'be_schema_playfair_capture_ajax' );
