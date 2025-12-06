<?php
/**
 * Plugin Name: BE Schema Engine
 * Description: Central, safety-first JSON-LD schema manager for site-level entities, special pages, blog posts, breadcrumbs, and Elementor widgets.
 * Author: Bill Evans
 * Author URI: https://billevans.be
 * Version: 1.2.1
 * Text Domain: be-schema-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'BE_SCHEMA_ENGINE_VERSION' ) ) {
    define( 'BE_SCHEMA_ENGINE_VERSION', '1.2.2' );
}

/**
 * -------------------------------------------------------------------------
 * Core plugin constants
 * -------------------------------------------------------------------------
 */

if ( ! defined( 'BE_SCHEMA_ENGINE_PLUGIN_FILE' ) ) {
    define( 'BE_SCHEMA_ENGINE_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'BE_SCHEMA_ENGINE_PLUGIN_DIR' ) ) {
    define( 'BE_SCHEMA_ENGINE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'BE_SCHEMA_ENGINE_PLUGIN_URL' ) ) {
    define( 'BE_SCHEMA_ENGINE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * -------------------------------------------------------------------------
 * Engine includes (front-end / runtime behavior)
 * -------------------------------------------------------------------------
 *
 * These files implement the actual schema logic and helpers. They are kept
 * separate from the admin UI so the runtime behavior is easy to reason about.
 */

require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-helpers.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-debug.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-site-entities.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-special-pages.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-posts.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-breadcrumbs.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-elementor.php';

/**
 * -------------------------------------------------------------------------
 * Front-end hooks
 * -------------------------------------------------------------------------
 *
 * These hooks connect WordPress events (wp_head, shutdown, etc.) to the
 * engine functions defined in the core-* files.
 */

/**
 * Output schema for the home page and special pages early in <head>.
 */
add_action( 'wp_head', 'be_schema_output_homepage_schema', 1 );
add_action( 'wp_head', 'be_schema_output_special_page_schema', 1 );

/**
 * Output BlogPosting schema for single posts (and any other eligible
 * singular types controlled by be_schema_is_singular_eligible()).
 */
add_action( 'wp_head', 'be_schema_output_post_schema', 1 );

/**
 * Output BreadcrumbList schema for the current context (singular, archives,
 * search, etc.). This respects global/page-level safety rules.
 */
add_action( 'wp_head', 'be_schema_output_breadcrumb_schema', 1 );

/**
 * Output Elementor widget-level schema (ImageObject / VideoObject / ItemList).
 */
add_action( 'wp_head', 'be_schema_output_elementor_schema', 1 );

/**
 * Elementor integration bootstrap.
 *
 * The concrete implementation of BE_Elementor_Schema_Plugin lives in
 * includes/engine/core-elementor.php. Here we just hook its init() method
 * once Elementor is loaded.
 */
add_action(
    'plugins_loaded',
    function () {
        // If Elementor is already loaded, init immediately.
        if ( did_action( 'elementor/loaded' ) ) {
            if ( class_exists( 'BE_Elementor_Schema_Plugin' ) ) {
                BE_Elementor_Schema_Plugin::init();
            }
            return;
        }

        // Otherwise, wait for Elementor to load.
        add_action(
            'elementor/loaded',
            function () {
                if ( class_exists( 'BE_Elementor_Schema_Plugin' ) ) {
                    BE_Elementor_Schema_Plugin::init();
                }
            }
        );
    },
    20
);

/**
 * Debug graph logging is registered inside core-debug.php via a shutdown hook:
 *
 * add_action( 'shutdown', 'be_schema_debug_shutdown_logger', 20 );
 *
 * That file also exposes:
 * - be_schema_debug_collect( $nodes )
 * - be_schema_debug_enabled()
 *
 * which are used by the engine to collect and log a full @graph when
 * WP_DEBUG and the debug flag are both enabled.
 */

/**
 * -------------------------------------------------------------------------
 * Admin UI includes
 * -------------------------------------------------------------------------
 *
 * The admin side is responsible for:
 * - Registering menus and submenus.
 * - Rendering the dashboard, Schema, Social Media, and Tools pages.
 * - Saving settings to be_schema_engine_settings.
 */

if ( is_admin() ) {
    require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/admin-menu.php';
}

/**
 * -------------------------------------------------------------------------
 * Activation / deactivation
 * -------------------------------------------------------------------------
 *
 * Right now we don’t need to create custom tables or migrate data, but
 * stubs are here in case you want to add checks or defaults later.
 */

/**
 * Runs on plugin activation.
 */
function be_schema_engine_activate() {
    // Ensure default settings exist.
    $existing = get_option( 'be_schema_engine_settings', null );

    if ( null === $existing ) {
        // Prime with defaults from the shared helper.
        $defaults = be_schema_engine_get_settings();
        update_option( 'be_schema_engine_settings', $defaults );
    }
}
register_activation_hook( BE_SCHEMA_ENGINE_PLUGIN_FILE, 'be_schema_engine_activate' );

/**
 * Runs on plugin deactivation.
 *
 * We intentionally do NOT delete settings here; uninstall.php or a dedicated
 * uninstall routine can handle permanent cleanup if desired.
 */
function be_schema_engine_deactivate() {
    // No-op for now (keeps settings in place).
}
register_deactivation_hook( BE_SCHEMA_ENGINE_PLUGIN_FILE, 'be_schema_engine_deactivate' );