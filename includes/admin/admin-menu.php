<?php
/**
 * Admin menu registration for BE SEO.
 *
 * Top-level menu: "BE SEO"
 * Submenus:
 *  - Home         (default landing)
 *  - Schema       (main configuration page)
 *  - Social Media (Open Graph & Twitter Cards)
 *  - Sitemap      (placeholder for XML/HTML sitemap tools)
 *  - Analyser     (utilities / validators hub)
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
    require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/page-sitemap.php';
    require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/page-tools.php';
    require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/page-analyser.php';
    require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/page-help-text.php';
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

    // "Social Media" submenu.
    add_submenu_page(
        $top_level_slug,
        __( 'Social Media', 'beseo' ),
        __( 'Social Media', 'beseo' ),
        $capability,
        'beseo-social-media',
        'be_schema_engine_render_social_media_page'
    );

    // "Sitemap" submenu.
    add_submenu_page(
        $top_level_slug,
        __( 'Sitemap', 'beseo' ),
        __( 'Sitemap', 'beseo' ),
        $capability,
        'beseo-sitemap',
        'be_schema_engine_render_sitemap_page'
    );

    // "Analyser" submenu.
    add_submenu_page(
        $top_level_slug,
        __( 'Analyser', 'beseo' ),
        __( 'Analyser', 'beseo' ),
        $capability,
        'beseo-tools',
        'be_schema_engine_render_tools_page'
    );

    // "Settings" submenu (shortcut into Tools for quick toggles).
    add_submenu_page(
        $top_level_slug,
        __( 'Settings', 'beseo' ),
        __( 'Settings', 'beseo' ),
        $capability,
        'beseo-settings',
        'be_schema_engine_render_tools_page'
    );
}
add_action( 'admin_menu', 'be_schema_engine_register_admin_menu' );
