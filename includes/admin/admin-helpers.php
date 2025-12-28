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

if ( ! function_exists( 'be_schema_admin_get_validator_targets' ) ) {
    function be_schema_admin_get_validator_targets( $args = array() ) {
        $defaults = array(
            'page_args' => array(
                'post_status' => 'publish',
            ),
            'post_args' => array(
                'post_type'   => 'post',
                'numberposts' => -1,
                'post_status' => 'publish',
            ),
        );
        $args = wp_parse_args( (array) $args, $defaults );

        $pages = get_pages( $args['page_args'] );
        $posts = get_posts( $args['post_args'] );

        $page_data = array();
        foreach ( (array) $pages as $page ) {
            $page_data[] = array(
                'id'    => $page->ID,
                'title' => get_the_title( $page ),
                'url'   => get_permalink( $page ),
                'type'  => 'page',
            );
        }

        $post_data = array();
        foreach ( (array) $posts as $post_item ) {
            $post_data[] = array(
                'id'    => $post_item->ID,
                'title' => get_the_title( $post_item ),
                'url'   => get_permalink( $post_item ),
                'type'  => 'post',
            );
        }

        return array(
            'pages' => $page_data,
            'posts' => $post_data,
        );
    }
}

if ( ! function_exists( 'be_schema_admin_get_playfair_defaults' ) ) {
    /**
     * Retrieve Playfair capture defaults from settings.
     *
     * @param array|null $settings Optional settings override.
     * @return array
     */
    function be_schema_admin_get_playfair_defaults( $settings = null ) {
        if ( null === $settings ) {
            if ( function_exists( 'be_schema_engine_get_settings' ) ) {
                $settings = be_schema_engine_get_settings();
            } else {
                $settings = get_option( 'be_schema_engine_settings', array() );
            }
        }

        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        return array(
            'mode'         => isset( $settings['playfair_mode'] ) ? $settings['playfair_mode'] : 'auto',
            'profile'      => isset( $settings['playfair_default_profile'] ) ? $settings['playfair_default_profile'] : 'desktop_chromium',
            'wait_ms'      => isset( $settings['playfair_default_wait_ms'] ) ? (int) $settings['playfair_default_wait_ms'] : 1500,
            'include_html' => ! empty( $settings['playfair_include_html_default'] ),
            'include_logs' => ! empty( $settings['playfair_include_logs_default'] ),
            'locale'       => isset( $settings['playfair_default_locale'] ) ? $settings['playfair_default_locale'] : '',
            'timezone'     => isset( $settings['playfair_default_timezone'] ) ? $settings['playfair_default_timezone'] : '',
        );
    }
}
