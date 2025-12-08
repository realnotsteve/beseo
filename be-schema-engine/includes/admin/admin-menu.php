<?php
/**
 * Admin menu registration for BE SEO.
 *
 * Top-level menu: "BE SEO"
 * Submenus:
 *  - Overview     (default landing)
 *  - Schema       (main configuration page)
 *  - Social Media (OpenGraph & Twitter Cards)
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
    $top_level_slug = 'be-schema-engine';

    // Top-level menu: BE SEO.
    add_menu_page(
        __( 'BE SEO', 'be-schema-engine' ),              // Page title.
        __( 'BE SEO', 'be-schema-engine' ),              // Menu title.
        $capability,                                     // Capability.
        $top_level_slug,                                 // Menu slug.
        'be_schema_engine_render_overview_page',         // Callback (default landing).
        'dashicons-chart-area',                          // Icon.
        58                                               // Position (near SEO-ish tools).
    );

    // "Overview" submenu (same slug as top-level, becomes default).
    add_submenu_page(
        $top_level_slug,                                 // Parent slug.
        __( 'Overview', 'be-schema-engine' ),            // Page title.
        __( 'Overview', 'be-schema-engine' ),            // Menu title.
        $capability,                                     // Capability.
        $top_level_slug,                                 // Menu slug (same as top-level).
        'be_schema_engine_render_overview_page'          // Callback.
    );

    // "Schema" submenu.
    add_submenu_page(
        $top_level_slug,
        __( 'Schema', 'be-schema-engine' ),
        __( 'Schema', 'be-schema-engine' ),
        $capability,
        'be-schema-engine-schema',
        'be_schema_engine_render_schema_page'
    );

    // "Social Media" submenu.
    add_submenu_page(
        $top_level_slug,
        __( 'Social Media', 'be-schema-engine' ),
        __( 'Social Media', 'be-schema-engine' ),
        $capability,
        'be-schema-engine-social-media',
        'be_schema_engine_render_social_media_page'
    );

    // "Tools" submenu (simple placeholder for now).
    add_submenu_page(
        $top_level_slug,
        __( 'Tools', 'be-schema-engine' ),
        __( 'Tools', 'be-schema-engine' ),
        $capability,
        'be-schema-engine-tools',
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
    <div class="wrap be-schema-engine-wrap be-schema-tools-wrap">
        <h1><?php esc_html_e( 'BE SEO – Tools', 'be-schema-engine' ); ?></h1>

        <p class="description">
            <?php esc_html_e(
                'This page is reserved for future tools, such as quick links to schema and social validators, and internal debug helpers.',
                'be-schema-engine'
            ); ?>
        </p>

        <p>
            <?php esc_html_e(
                'For now, use the Schema and Social Media pages to configure JSON-LD and OpenGraph/Twitter output. Debug logs are written to the PHP error log when debug is enabled.',
                'be-schema-engine'
            ); ?>
        </p>
    </div>
    <?php
}
