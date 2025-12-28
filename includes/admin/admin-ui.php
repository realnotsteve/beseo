<?php
/**
 * Shared admin UI helpers (tabs, sections, status pills).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'be_schema_engine_admin_render_nav_tabs' ) ) {
    function be_schema_engine_admin_render_nav_tabs( array $tabs, $active_key = '', array $args = array() ) {
        if ( empty( $tabs ) ) {
            return;
        }

        $wrapper_tag   = isset( $args['wrapper_tag'] ) ? $args['wrapper_tag'] : 'h2';
        $wrapper_class = 'nav-tab-wrapper';
        if ( ! empty( $args['wrapper_class'] ) ) {
            $wrapper_class .= ' ' . $args['wrapper_class'];
        }
        $link_class   = isset( $args['link_class'] ) ? $args['link_class'] : 'nav-tab';
        $active_class = isset( $args['active_class'] ) ? $args['active_class'] : 'nav-tab-active';

        echo '<' . esc_attr( $wrapper_tag ) . ' class="' . esc_attr( trim( $wrapper_class ) ) . '">';
        foreach ( $tabs as $tab ) {
            $key      = isset( $tab['key'] ) ? $tab['key'] : '';
            $href     = isset( $tab['href'] ) ? $tab['href'] : '#';
            $label    = isset( $tab['label'] ) ? $tab['label'] : '';
            $extra    = isset( $tab['class'] ) ? $tab['class'] : '';
            $classes  = trim( $link_class . ( (string) $key === (string) $active_key ? ' ' . $active_class : '' ) . ( $extra ? ' ' . $extra : '' ) );
            $data     = isset( $tab['data'] ) && is_array( $tab['data'] ) ? $tab['data'] : array();
            $data_out = '';
            foreach ( $data as $data_key => $data_value ) {
                if ( '' === (string) $data_key ) {
                    continue;
                }
                $data_out .= ' data-' . esc_attr( sanitize_key( $data_key ) ) . '="' . esc_attr( $data_value ) . '"';
            }

            echo '<a href="' . esc_attr( $href ) . '" class="' . esc_attr( $classes ) . '"' . $data_out . '>' . esc_html( $label ) . '</a>';
        }
        echo '</' . esc_attr( $wrapper_tag ) . '>';
    }
}

if ( ! function_exists( 'be_schema_engine_admin_render_list_tabs' ) ) {
    function be_schema_engine_admin_render_list_tabs( array $tabs, $active_key = '', array $args = array() ) {
        if ( empty( $tabs ) ) {
            return;
        }

        $wrapper_class = isset( $args['wrapper_class'] ) ? $args['wrapper_class'] : '';
        $link_class    = isset( $args['link_class'] ) ? $args['link_class'] : '';
        $active_class  = isset( $args['active_class'] ) ? $args['active_class'] : '';
        $wrapper_attr  = $wrapper_class ? ' class="' . esc_attr( $wrapper_class ) . '"' : '';

        echo '<ul' . $wrapper_attr . '>';
        foreach ( $tabs as $tab ) {
            $key      = isset( $tab['key'] ) ? $tab['key'] : '';
            $href     = isset( $tab['href'] ) ? $tab['href'] : '#';
            $label    = isset( $tab['label'] ) ? $tab['label'] : '';
            $extra    = isset( $tab['class'] ) ? $tab['class'] : '';
            $classes  = trim( $link_class . ( (string) $key === (string) $active_key ? ' ' . $active_class : '' ) . ( $extra ? ' ' . $extra : '' ) );
            $data     = isset( $tab['data'] ) && is_array( $tab['data'] ) ? $tab['data'] : array();
            $data_out = '';
            foreach ( $data as $data_key => $data_value ) {
                if ( '' === (string) $data_key ) {
                    continue;
                }
                $data_out .= ' data-' . esc_attr( sanitize_key( $data_key ) ) . '="' . esc_attr( $data_value ) . '"';
            }

            echo '<li><a href="' . esc_attr( $href ) . '" class="' . esc_attr( $classes ) . '"' . $data_out . '>' . esc_html( $label ) . '</a></li>';
        }
        echo '</ul>';
    }
}

if ( ! function_exists( 'be_schema_engine_admin_render_section_open' ) ) {
    function be_schema_engine_admin_render_section_open( $title = '', array $args = array() ) {
        $section_class = isset( $args['section_class'] ) ? $args['section_class'] : 'be-schema-global-section';
        $title_class   = isset( $args['title_class'] ) ? $args['title_class'] : 'be-schema-section-title';
        $title_tag     = isset( $args['title_tag'] ) ? $args['title_tag'] : 'h4';

        echo '<div class="' . esc_attr( $section_class ) . '">';
        if ( '' !== (string) $title ) {
            echo '<' . esc_attr( $title_tag ) . ' class="' . esc_attr( $title_class ) . '">' . esc_html( $title ) . '</' . esc_attr( $title_tag ) . '>';
        }
    }
}

if ( ! function_exists( 'be_schema_engine_admin_render_section_close' ) ) {
    function be_schema_engine_admin_render_section_close() {
        echo '</div>';
    }
}

if ( ! function_exists( 'be_schema_engine_admin_render_status_pill' ) ) {
    function be_schema_engine_admin_render_status_pill( $label, $is_on = true, array $args = array() ) {
        $base_class  = isset( $args['class'] ) ? $args['class'] : 'be-schema-status-pill';
        $off_class   = isset( $args['off_class'] ) ? $args['off_class'] : 'off';
        $extra_class = isset( $args['extra_class'] ) ? $args['extra_class'] : '';
        $classes     = trim( $base_class . ( $is_on ? '' : ' ' . $off_class ) . ( $extra_class ? ' ' . $extra_class : '' ) );

        return '<span class="' . esc_attr( $classes ) . '">' . esc_html( $label ) . '</span>';
    }
}

if ( ! function_exists( 'be_schema_engine_admin_enqueue_ui_styles' ) ) {
    function be_schema_engine_admin_enqueue_ui_styles() {
        if ( ! defined( 'BE_SCHEMA_ENGINE_PLUGIN_URL' ) ) {
            return;
        }

        $page = '';
        if ( isset( $_GET['page'] ) ) {
            $page = sanitize_key( wp_unslash( $_GET['page'] ) );
        }
        $allowed = array( 'beseo-schema', 'beseo-tools', 'beseo-settings', 'beseo-sitemap' );
        if ( ! in_array( $page, $allowed, true ) ) {
            return;
        }

        wp_enqueue_style(
            'be-schema-admin-ui',
            BE_SCHEMA_ENGINE_PLUGIN_URL . 'assets/css/admin-ui.css',
            array(),
            BE_SCHEMA_ENGINE_VERSION
        );
    }
    add_action( 'admin_enqueue_scripts', 'be_schema_engine_admin_enqueue_ui_styles' );
}
