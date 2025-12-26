<?php
/**
 * Schema admin loader.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/schema-service.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/schema-view.php';

/**
 * Enqueue assets for the Schema admin page.
 */
function be_schema_engine_enqueue_schema_assets() {
    $settings = function_exists( 'be_schema_engine_get_settings' ) ? be_schema_engine_get_settings() : array();
    $image_validation_enabled = isset( $settings['image_validation_enabled'] ) ? ( '1' === (string) $settings['image_validation_enabled'] ) : true;

    wp_enqueue_style(
        'be-schema-admin',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'assets/css/schema.css',
        array(),
        BE_SCHEMA_ENGINE_VERSION
    );

    wp_enqueue_script(
        'be-schema-admin',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'assets/js/schema.js',
        array( 'media-editor' ),
        BE_SCHEMA_ENGINE_VERSION,
        true
    );

    wp_localize_script(
        'be-schema-admin',
        'beSchemaSchemaData',
        array(
            'imageValidationEnabled' => $image_validation_enabled,
            'preview'                => array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'be_schema_preview_graph' ),
                'homeUrl' => home_url( '/' ),
                'listPagesNonce' => wp_create_nonce( 'be_schema_analyser' ),
                'playfairDefaultProfile' => isset( $settings['playfair_default_profile'] ) ? $settings['playfair_default_profile'] : 'desktop_chromium',
                'playfairNonce' => wp_create_nonce( 'be_schema_playfair_capture' ),
                'playfairAction' => 'be_schema_playfair_capture',
                'marker' => function_exists( 'be_schema_preview_marker_value' ) ? be_schema_preview_marker_value() : 'beseo-generated',
                'playfairHealthNonce' => wp_create_nonce( 'be_schema_playfair_health' ),
                'playfairTestUrl' => 'https://example.com',
            ),
            'labels'                 => array(
                'undefined'          => __( 'Undefined', 'beseo' ),
                'verified'           => __( 'Verified', 'beseo' ),
                'resolution'         => __( 'Resolution', 'beseo' ),
                'selectImage'        => __( 'Select Image', 'beseo' ),
                'publisherNone'      => __( 'Publisher Type: None', 'beseo' ),
                'publisherDedicated' => __( 'Publisher Type: Dedicated', 'beseo' ),
                'publisherReference' => __( 'Publisher Type: Reference', 'beseo' ),
            ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'be_schema_engine_enqueue_schema_assets' );
