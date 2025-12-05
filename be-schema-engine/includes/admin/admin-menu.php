<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Load individual admin pages.
 *
 * Each file defines one render_*() function:
 * - be_schema_engine_render_dashboard_page()
 * - be_schema_engine_render_schema_page()
 * - be_schema_engine_render_social_media_page()
 * - be_schema_engine_render_tools_page()
 */
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/page-dashboard.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/page-schema.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/page-social-media.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/page-tools.php';

/**
 * Hook admin menu.
 */
add_action( 'admin_menu', 'be_schema_engine_register_admin_menu' );

/**
 * Register BE Schema Engine admin menu + submenus.
 */
function be_schema_engine_register_admin_menu() {
    $capability  = 'manage_options';
    $parent_slug = 'be-schema-engine';

    // Top-level menu.
    add_menu_page(
        __( 'BE Schema Engine', 'be-schema-engine' ),
        __( 'BE Schema Engine', 'be-schema-engine' ),
        $capability,
        $parent_slug,
        'be_schema_engine_render_dashboard_page',
        'dashicons-editor-code',
        80
    );

    // Submenu: Schema.
    add_submenu_page(
        $parent_slug,
        __( 'Schema', 'be-schema-engine' ),
        __( 'Schema', 'be-schema-engine' ),
        $capability,
        'be-schema-engine-schema',
        'be_schema_engine_render_schema_page'
    );

    // Submenu: Social Media.
    add_submenu_page(
        $parent_slug,
        __( 'Social Media', 'be-schema-engine' ),
        __( 'Social Media', 'be-schema-engine' ),
        $capability,
        'be-schema-engine-social-media',
        'be_schema_engine_render_social_media_page'
    );

    // Submenu: Tools.
    add_submenu_page(
        $parent_slug,
        __( 'Tools', 'be-schema-engine' ),
        __( 'Tools', 'be-schema-engine' ),
        $capability,
        'be-schema-engine-tools',
        'be_schema_engine_render_tools_page'
    );
}