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
