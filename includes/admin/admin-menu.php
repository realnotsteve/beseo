<?php
/**
 * Admin menu registration for BE SEO.
 *
 * Top-level menu: "BE SEO"
 * Submenus:
 *  - Home         (default landing)
 *  - Schema       (main configuration page)
 *  - Open Graph (Open Graph & Twitter Cards)
 *  - Platforms    (placeholder for additional networks)
 *  - Tools        (placeholder for future utilities / validators)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Bring in the admin page renderers.
 *
 * - be_schema_engine_render_overview_page()
 * - be_schema_engine_render_schema_page()
 * - be_schema_engine_render_social_media_page()
 */
if ( defined( 'BE_SCHEMA_ENGINE_PLUGIN_DIR' ) ) {
    require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/page-overview.php';
    require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/page-schema.php';
    require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/page-social-media.php';
}

/**
 * Register the BE SEO admin menu and submenus.
 */
function be_schema_engine_register_admin_menu() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $capability    = 'manage_options';
    $top_level_slug = 'beseo';

    // Top-level menu: BE SEO.
    add_menu_page(
        __( 'BE SEO', 'beseo' ),              // Page title.
        __( 'BE SEO', 'beseo' ),              // Menu title.
        $capability,                                     // Capability.
        $top_level_slug,                                 // Menu slug.
        'be_schema_engine_render_overview_page',         // Callback (default landing).
        'dashicons-chart-area',                          // Icon.
        58                                               // Position (near SEO-ish tools).
    );

    // "Home" submenu (same slug as top-level, becomes default).
    add_submenu_page(
        $top_level_slug,                                 // Parent slug.
        __( 'Home', 'beseo' ),                // Page title.
        __( 'Home', 'beseo' ),                // Menu title.
        $capability,                                     // Capability.
        $top_level_slug,                                 // Menu slug (same as top-level).
        'be_schema_engine_render_overview_page'          // Callback.
    );

    // "Schema" submenu.
    add_submenu_page(
        $top_level_slug,
        __( 'Schema', 'beseo' ),
        __( 'Schema', 'beseo' ),
        $capability,
        'beseo-schema',
        'be_schema_engine_render_schema_page'
    );

    // "Open Graph" submenu.
    add_submenu_page(
        $top_level_slug,
        __( 'Open Graph', 'beseo' ),
        __( 'Open Graph', 'beseo' ),
        $capability,
        'beseo-social-media',
        'be_schema_engine_render_social_media_page'
    );

    // "Platforms" submenu (placeholder between Open Graph and Tools).
    add_submenu_page(
        $top_level_slug,
        __( 'Platforms', 'beseo' ),
        __( 'Platforms', 'beseo' ),
        $capability,
        'beseo-platforms',
        'be_schema_engine_render_social_media_page'
    );

    // "Tools" submenu (simple placeholder for now).
    add_submenu_page(
        $top_level_slug,
        __( 'Tools', 'beseo' ),
        __( 'Tools', 'beseo' ),
        $capability,
        'beseo-tools',
        'be_schema_engine_render_tools_page'
    );
}
add_action( 'admin_menu', 'be_schema_engine_register_admin_menu' );

/**
 * Render the "Tools" admin page.
 *
 * Currently a lightweight placeholder, intended for:
 *  - Links to external validators (Rich Results Test, OG Debugger, etc.).
 *  - Future debug/inspection utilities.
 */
function be_schema_engine_render_tools_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap beseo-wrap beseo-tools-wrap">
        <h1><?php esc_html_e( 'BE SEO – Tools', 'beseo' ); ?></h1>

        <p class="description">
            <?php esc_html_e(
                'This page is reserved for future tools, such as quick links to schema and social validators, and internal debug helpers.',
                'beseo'
            ); ?>
        </p>

        <p>
            <?php esc_html_e(
                'For now, use the Schema and Open Graph pages to configure JSON-LD and Open Graph/Twitter output. Debug logs are written to the PHP error log when debug is enabled.',
                'beseo'
            ); ?>
        </p>
    </div>
    <?php
}

/**
 * Render the "Platforms" admin page (placeholder).
 */
function be_schema_engine_render_platforms_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap beseo-wrap beseo-platforms-wrap">
        <h1><?php esc_html_e( 'BE SEO – Platforms', 'beseo' ); ?></h1>

        <p class="description">
            <?php esc_html_e(
                'This page is reserved for future platform-specific controls beyond Open Graph and Twitter.',
                'beseo'
            ); ?>
        </p>

        <p>
            <?php esc_html_e(
                'For now, configure Open Graph and Twitter under the Open Graph menu. Additional networks will appear here when available.',
                'beseo'
            ); ?>
        </p>
    </div>
    <?php
}
