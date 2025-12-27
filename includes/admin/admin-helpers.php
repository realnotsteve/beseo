<?php
/**
 * Admin shared helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'be_schema_admin_validate_url_field' ) ) {
    function be_schema_admin_validate_url_field( $raw_value, $label, &$errors ) {
        $raw_value = isset( $raw_value ) ? trim( (string) $raw_value ) : '';
        if ( '' === $raw_value ) {
            return '';
        }

        $sanitized = esc_url_raw( $raw_value );
        if ( ! $sanitized || ! wp_http_validate_url( $sanitized ) ) {
            $errors[] = sprintf( /* translators: %s: field label */ __( '%s must be a valid URL (http/https).', 'beseo' ), $label );
            return '';
        }

        return $sanitized;
    }
}
