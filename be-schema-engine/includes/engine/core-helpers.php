<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Canonical settings loader for BE Schema Engine.
 *
 * Used by both front-end engine code and admin pages.
 *
 * @return array
 */
function be_schema_engine_get_settings() {
    static $cached;

    if ( isset( $cached ) ) {
        return $cached;
    }

    $defaults = array(
        'enabled'               => '0',
        'elementor_enabled'     => '0',
        'debug'                 => '0',
        'person_enabled'        => '0',
        'organization_enabled'  => '0',
        'publisher_enabled'     => '0',
        'org_name'              => '',
        'org_legal_name'        => '',
        'org_url'               => '',
        'org_logo'              => '',
        'copyright_year'        => '',
        'license_url'           => '',
        'publishing_principles' => '',
        'corrections_policy'    => '',
        'ownership_funding'     => '',
        'publisher_custom_name' => '',
        'publisher_custom_url'  => '',
        'publisher_custom_logo' => '',
    );

    $settings = get_option( 'be_schema_engine_settings', array() );

    $cached = wp_parse_args( $settings, $defaults );

    return $cached;
}

/**
 * Hard/global disable for all schema.
 *
 * True if:
 * - BE_SCHEMA_DISABLE_ALL is defined and truthy; OR
 * - plugin setting 'enabled' !== '1'.
 *
 * @return bool
 */
function be_schema_globally_disabled() {
    if ( defined( 'BE_SCHEMA_DISABLE_ALL' ) && BE_SCHEMA_DISABLE_ALL ) {
        return true;
    }

    $settings = be_schema_engine_get_settings();

    return ( $settings['enabled'] !== '1' );
}

/**
 * Hard/global disable for Elementor-driven schema.
 *
 * True if:
 * - global schema is disabled; OR
 * - BE_SCHEMA_DISABLE_ELEMENTOR is defined and truthy; OR
 * - plugin setting 'elementor_enabled' !== '1'.
 *
 * @return bool
 */
function be_schema_elementor_disabled() {
    if ( be_schema_globally_disabled() ) {
        return true;
    }

    if ( defined( 'BE_SCHEMA_DISABLE_ELEMENTOR' ) && BE_SCHEMA_DISABLE_ELEMENTOR ) {
        return true;
    }

    $settings = be_schema_engine_get_settings();

    return ( $settings['elementor_enabled'] !== '1' );
}

/**
 * Returns true if schema should be considered disabled for the current
 * singular page, based on:
 * - _be_schema_disable meta
 * - Elementor page settings in _elementor_page_settings
 *
 * Safe-by-default:
 * - If no Elementor page settings exist, schema is disabled for that page
 *   unless you’re emitting non-page-specific things (e.g. breadcrumbs).
 *
 * @return bool
 */
function be_schema_is_disabled_for_current_page() {
    if ( ! is_singular() ) {
        // Page-level safety doesn’t apply to archives, home, search, etc.
        return false;
    }

    $post = get_post();
    if ( ! $post ) {
        return true;
    }

    // Hard per-page disable meta.
    $disable_meta = get_post_meta( $post->ID, '_be_schema_disable', true );
    if ( (string) $disable_meta === '1' ) {
        return true;
    }

    // Elementor page settings.
    $page_settings = get_post_meta( $post->ID, '_elementor_page_settings', true );

    // Safe-by-default: if there are no Elementor settings at all, we treat
    // that as "schema not explicitly enabled for this page".
    if ( empty( $page_settings ) || ! is_array( $page_settings ) ) {
        return true;
    }

    // be_schema_enable_page must be explicitly "yes".
    $enable_page = isset( $page_settings['be_schema_enable_page'] ) ? $page_settings['be_schema_enable_page'] : '';

    if ( $enable_page !== 'yes' ) {
        return true;
    }

    return false;
}

/**
 * Determine whether the current singular post is eligible for
 * singular-schema emission (BlogPosting, Elementor page schema, etc.).
 *
 * - Requires is_singular() === true.
 * - Uses a filter so consumers can gate per post_type.
 *
 * @return bool
 */
function be_schema_is_singular_eligible() {
    if ( ! is_singular() ) {
        return false;
    }

    $post = get_post();
    if ( ! $post ) {
        return false;
    }

    $post_type = get_post_type( $post );

    // Default: allow all post types. Developers can tighten this up.
    $allowed = true;

    /**
     * Filter whether BE Schema Engine is allowed to emit schema
     * for the given singular post type.
     *
     * @param bool   $allowed   Default true.
     * @param string $post_type The post type slug.
     */
    $allowed = apply_filters( 'be_schema_allow_post_type', $allowed, $post_type );

    return (bool) $allowed;
}