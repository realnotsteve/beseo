<?php
/**
 * Plugin Name:       BE SEO
 * Plugin URI:        https://billevans.be/
 * Description:       Central, conservative, and controllable schema + social meta for WordPress (Elementor-first), with strong safety and debug tools.
 * Version:           1.3.26
 * Author:            Bill Evans
 * Author URI:        https://billevans.be/
 * Text Domain:       be-schema-engine
 * Requires at least: 5.8
 * Requires PHP:      7.4
 *
 * @package BE_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * -------------------------------------------------------------------------
 * Core constants
 * -------------------------------------------------------------------------
 */

// Main plugin file path.
if ( ! defined( 'BE_SCHEMA_ENGINE_PLUGIN_FILE' ) ) {
    define( 'BE_SCHEMA_ENGINE_PLUGIN_FILE', __FILE__ );
}

// Plugin directory (with trailing slash).
if ( ! defined( 'BE_SCHEMA_ENGINE_PLUGIN_DIR' ) ) {
    define( 'BE_SCHEMA_ENGINE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin URL (with trailing slash).
if ( ! defined( 'BE_SCHEMA_ENGINE_PLUGIN_URL' ) ) {
    define( 'BE_SCHEMA_ENGINE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Plugin version (used for cache-busting if needed).
if ( ! defined( 'BE_SCHEMA_ENGINE_VERSION' ) ) {
    define( 'BE_SCHEMA_ENGINE_VERSION', '1.3.26' );
}

/**
 * -------------------------------------------------------------------------
 * Core includes
 * -------------------------------------------------------------------------
 *
 * These files implement the core logic described in BE SEO Notes:
 * - Site entities (Person/Organisation/WebSite/Publisher/logo).
 * - Shared helpers and debug.
 * - Special pages (home/contact/about/privacy/accessibility).
 * - Blog posts (BlogPosting).
 * - Breadcrumbs.
 * - Elementor schema engine.
 * - Social meta (OpenGraph & Twitter Cards).
 */

// Core engine lives under includes/engine.
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-helpers.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-debug.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-site-entities.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-special-pages.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-posts.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-breadcrumbs.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-elementor.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/engine/core-social.php';

/**
 * -------------------------------------------------------------------------
 * Front-end hooks: schema
 * -------------------------------------------------------------------------
 *
 * These functions are defined in the core-* include files and are responsible
 * for emitting JSON-LD schema on the front end. They all respect:
 *
 * - Global disable flags (constants + admin settings).
 * - Per-page disable meta box.
 * - Elementor page-level enable/disable.
 * - Post-type eligibility filters.
 */

// Homepage (Person, Organisation, WebSite, WebPage).
add_action( 'wp_head', 'be_schema_output_homepage_schema', 1 );

// Special pages: ContactPage, AboutPage, PrivacyPolicy, Accessibility WebPage.
add_action( 'wp_head', 'be_schema_output_special_page_schema', 1 );

// BlogPosting and related WebPage entity for single posts / eligible types.
add_action( 'wp_head', 'be_schema_output_post_schema', 1 );

// BreadcrumbList for current context (singular, archives, search, etc.).
add_action( 'wp_head', 'be_schema_output_breadcrumb_schema', 1 );

// Elementor widget-level schema (ImageObject, ItemList, VideoObject, etc.).
add_action( 'wp_head', 'be_schema_output_elementor_schema', 1 );

/**
 * NOTE:
 * Social meta (OpenGraph & Twitter Cards) is hooked inside
 * includes/engine/core-social.php via:
 *
 *   add_action( 'wp_head', 'be_schema_output_social_meta', 5 );
 *
 * That engine uses be_schema_social_get_settings() and does NOT emit JSON-LD.
 */

/**
 * -------------------------------------------------------------------------
 * Elementor integration bootstrap
 * -------------------------------------------------------------------------
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
 * -------------------------------------------------------------------------
 * Admin UI includes
 * -------------------------------------------------------------------------
 *
 * The admin side is responsible for:
 * - Registering menus and submenus.
 * - Rendering the Schema, Social Media, Tools pages, etc.
 * - Saving settings to be_schema_engine_settings / be_schema_social_settings.
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
 * stubs are here in case we want to add checks or defaults later.
 */

/**
 * Runs on plugin activation.
 */
function be_schema_engine_activate() {
    // Ensure default settings exist.
    $existing = get_option( 'be_schema_engine_settings', null );

    if ( null === $existing ) {
        // Prime with defaults from the shared helper.
        if ( function_exists( 'be_schema_engine_get_settings' ) ) {
            $defaults = be_schema_engine_get_settings();
            update_option( 'be_schema_engine_settings', $defaults );
        } else {
            update_option( 'be_schema_engine_settings', array() );
        }
    }

    // Ensure social settings also exist.
    $social_existing = get_option( 'be_schema_social_settings', null );
    if ( null === $social_existing && function_exists( 'be_schema_social_get_settings' ) ) {
        $social_defaults = be_schema_social_get_settings();
        update_option( 'be_schema_social_settings', $social_defaults );
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
