<?php
/**
 * Schema Admin Page
 *
 * Submenu: BE SEO → Schema
 *
 * Tabs:
 *  - Settings  (global plugin toggles, Elementor toggle, debug, snapshot, health check)
 *  - Website   (site identity mode plus site entities: Global / Person / Organisation / Publisher)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Save main BE Schema Engine settings.
 *
 * Option name: be_schema_engine_settings
 */

// Basic URL validator for admin inputs (http/https only).
if ( ! function_exists( 'be_schema_engine_validate_url_field' ) ) {
    function be_schema_engine_validate_url_field( $raw_value, $label, &$errors ) {
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

function be_schema_engine_save_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if (
        ! isset( $_POST['be_schema_engine_settings_nonce'] ) ||
        ! wp_verify_nonce( $_POST['be_schema_engine_settings_nonce'], 'be_schema_engine_save_settings' )
    ) {
        return;
    }

    // Load current settings and defaults via the canonical helper.
    if ( function_exists( 'be_schema_engine_get_settings' ) ) {
        $settings = be_schema_engine_get_settings();
    } else {
        $settings = array();
    }

    $validation_errors = array();

    // Global toggles.
    $settings['enabled']           = isset( $_POST['be_schema_enabled'] ) ? '1' : '0';
    $settings['elementor_enabled'] = isset( $_POST['be_schema_elementor_enabled'] ) ? '1' : '0';
    $settings['debug']             = isset( $_POST['be_schema_debug'] ) ? '1' : '0';
    $settings['dry_run']           = isset( $_POST['be_schema_dry_run'] ) ? '1' : '0';

    // Mirror debug into a dedicated debug_enabled key for engine helpers.
    $settings['debug_enabled'] = $settings['debug'];

    // Site identity mode (person / organisation / publisher).
    if ( isset( $_POST['be_schema_site_identity_mode'] ) ) {
        $mode    = sanitize_text_field( wp_unslash( $_POST['be_schema_site_identity_mode'] ) );
        $allowed = array( 'person', 'organisation', 'publisher' );
        if ( ! in_array( $mode, $allowed, true ) ) {
            $mode = 'publisher';
        }
        $settings['site_identity_mode'] = $mode;
    }

    // Site identity checkboxes (persist UI state).
    $settings['site_identity_person_enabled']       = isset( $_POST['be_schema_identity_person_enabled'] ) ? '1' : '0';
    $settings['site_identity_organisation_enabled'] = isset( $_POST['be_schema_identity_org_enabled'] ) ? '1' : '0';
    $settings['site_identity_publisher_enabled']    = isset( $_POST['be_schema_identity_publisher_enabled'] ) ? '1' : '0';

    // Ensure site identity mode aligns with enabled options.
    $mode_current  = isset( $settings['site_identity_mode'] ) ? $settings['site_identity_mode'] : 'publisher';
    $mode_allowed  = array( 'person', 'organisation', 'publisher' );
    if ( ! in_array( $mode_current, $mode_allowed, true ) ) {
        $mode_current = 'publisher';
    }
    if ( 'person' === $mode_current && '1' !== $settings['site_identity_person_enabled'] ) {
        $mode_current = '1' === $settings['site_identity_organisation_enabled'] ? 'organisation' : ( '1' === $settings['site_identity_publisher_enabled'] ? 'publisher' : 'person' );
    } elseif ( 'organisation' === $mode_current && '1' !== $settings['site_identity_organisation_enabled'] ) {
        $mode_current = '1' === $settings['site_identity_person_enabled'] ? 'person' : ( '1' === $settings['site_identity_publisher_enabled'] ? 'publisher' : 'organisation' );
    } elseif ( 'publisher' === $mode_current && '1' !== $settings['site_identity_publisher_enabled'] ) {
        $mode_current = '1' === $settings['site_identity_person_enabled'] ? 'person' : ( '1' === $settings['site_identity_organisation_enabled'] ? 'organisation' : 'publisher' );
    }
    $settings['site_identity_mode'] = $mode_current;

    // Person.
    $settings['person_enabled'] = isset( $_POST['be_schema_person_enabled'] ) ? '1' : '0';

    $settings['person_name'] = isset( $_POST['be_schema_person_name'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_person_name'] ) )
        : '';

    $settings['person_description'] = isset( $_POST['be_schema_person_description'] )
        ? wp_kses_post( wp_unslash( $_POST['be_schema_person_description'] ) )
        : '';

    $settings['person_optional'] = isset( $_POST['be_schema_person_optional'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_person_optional'] ) )
        : '';

    $settings['person_image_url'] = isset( $_POST['be_schema_person_image_url'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_person_image_url'] ) )
        : '';

    $settings['person_honorific_prefix'] = isset( $_POST['be_schema_person_honorific_prefix'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_person_honorific_prefix'] ) )
        : '';

    $settings['person_honorific_suffix'] = isset( $_POST['be_schema_person_honorific_suffix'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_person_honorific_suffix'] ) )
        : '';

    $settings['person_sameas_raw'] = isset( $_POST['be_schema_person_sameas_raw'] )
        ? wp_kses_post( wp_unslash( $_POST['be_schema_person_sameas_raw'] ) )
        : '';

    // Organisation.
    $settings['organization_enabled'] = isset( $_POST['be_schema_organization_enabled'] ) ? '1' : '0';

    $settings['org_name'] = isset( $_POST['be_schema_org_name'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_org_name'] ) )
        : '';

    $settings['org_legal_name'] = isset( $_POST['be_schema_org_legal_name'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_org_legal_name'] ) )
        : '';

    $settings['organization_optional'] = isset( $_POST['be_schema_org_optional'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_org_optional'] ) )
        : '';

    $settings['org_url'] = be_schema_engine_validate_url_field(
        isset( $_POST['be_schema_org_url'] ) ? wp_unslash( $_POST['be_schema_org_url'] ) : '',
        __( 'Organisation URL', 'beseo' ),
        $validation_errors
    );

    // Shared site logo.
    $settings['org_logo_enabled'] = isset( $_POST['be_schema_org_logo_enabled'] ) ? '1' : '0';
    if ( '1' === $settings['org_logo_enabled'] && isset( $_POST['be_schema_org_logo'] ) ) {
        $settings['org_logo'] = esc_url_raw( wp_unslash( $_POST['be_schema_org_logo'] ) );
    }

    // WebSite featured images (16:9, 4:3, 1:1).
    $settings['website_image_16_9_enabled'] = isset( $_POST['be_schema_website_image_16_9_enabled'] ) ? '1' : '0';
    if ( '1' === $settings['website_image_16_9_enabled'] && isset( $_POST['be_schema_website_image_16_9'] ) ) {
        $settings['website_image_16_9'] = esc_url_raw( wp_unslash( $_POST['be_schema_website_image_16_9'] ) );
    }

    $settings['website_image_4_3_enabled'] = isset( $_POST['be_schema_website_image_4_3_enabled'] ) ? '1' : '0';
    if ( '1' === $settings['website_image_4_3_enabled'] && isset( $_POST['be_schema_website_image_4_3'] ) ) {
        $settings['website_image_4_3'] = esc_url_raw( wp_unslash( $_POST['be_schema_website_image_4_3'] ) );
    }

    $settings['website_image_1_1_enabled'] = isset( $_POST['be_schema_website_image_1_1_enabled'] ) ? '1' : '0';
    if ( '1' === $settings['website_image_1_1_enabled'] && isset( $_POST['be_schema_website_image_1_1'] ) ) {
        $settings['website_image_1_1'] = esc_url_raw( wp_unslash( $_POST['be_schema_website_image_1_1'] ) );
    }

    // Publisher.
    $settings['publisher_enabled'] = isset( $_POST['be_schema_publisher_enabled'] ) ? '1' : '0';
    $settings['publisher_dedicated_enabled'] = isset( $_POST['be_schema_publisher_dedicated_enabled'] ) ? '1' : '0';

    $settings['publisher_entity_optional'] = isset( $_POST['be_schema_publisher_entity_optional'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_publisher_entity_optional'] ) )
        : '';

    $settings['copyright_year'] = isset( $_POST['be_schema_copyright_year'] )
        ? preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['be_schema_copyright_year'] ) ) )
        : '';
    if ( $settings['copyright_year'] && strlen( $settings['copyright_year'] ) < 4 ) {
        $validation_errors[] = __( 'Copyright Year should be 4 digits (e.g., 2024).', 'beseo' );
        $settings['copyright_year'] = '';
    }

    $settings['license_url'] = be_schema_engine_validate_url_field(
        isset( $_POST['be_schema_license_url'] ) ? wp_unslash( $_POST['be_schema_license_url'] ) : '',
        __( 'License URL', 'beseo' ),
        $validation_errors
    );

    $settings['publishing_principles'] = be_schema_engine_validate_url_field(
        isset( $_POST['be_schema_publishing_principles'] ) ? wp_unslash( $_POST['be_schema_publishing_principles'] ) : '',
        __( 'Publishing Principles URL', 'beseo' ),
        $validation_errors
    );

    $settings['corrections_policy'] = be_schema_engine_validate_url_field(
        isset( $_POST['be_schema_corrections_policy'] ) ? wp_unslash( $_POST['be_schema_corrections_policy'] ) : '',
        __( 'Corrections Policy URL', 'beseo' ),
        $validation_errors
    );

    $settings['ownership_funding'] = be_schema_engine_validate_url_field(
        isset( $_POST['be_schema_ownership_funding'] ) ? wp_unslash( $_POST['be_schema_ownership_funding'] ) : '',
        __( 'Ownership / Funding Info URL', 'beseo' ),
        $validation_errors
    );

    $settings['publisher_custom_name'] = isset( $_POST['be_schema_publisher_custom_name'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_publisher_custom_name'] ) )
        : '';

    $settings['publisher_custom_url'] = be_schema_engine_validate_url_field(
        isset( $_POST['be_schema_publisher_custom_url'] ) ? wp_unslash( $_POST['be_schema_publisher_custom_url'] ) : '',
        __( 'Custom Publisher URL', 'beseo' ),
        $validation_errors
    );

    $settings['publisher_custom_logo'] = isset( $_POST['be_schema_publisher_custom_logo'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_publisher_custom_logo'] ) )
        : '';

    $settings['publisher_dedicated_optional'] = isset( $_POST['be_schema_publisher_dedicated_optional'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_publisher_dedicated_optional'] ) )
        : '';

    update_option( 'be_schema_engine_settings', $settings );

    foreach ( $validation_errors as $message ) {
        add_settings_error( 'be_schema_engine', 'be_schema_engine_validation', $message, 'error' );
    }
}

/**
 * Render the Schema admin page (BE SEO → Schema).
 */
function be_schema_engine_render_schema_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Enqueue media for image pickers.
    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

    // Save on POST.
    if ( isset( $_POST['be_schema_engine_settings_submitted'] ) ) {
        be_schema_engine_save_settings();
    }

    // Settings snapshot from canonical helper.
    if ( function_exists( 'be_schema_engine_get_settings' ) ) {
        $settings = be_schema_engine_get_settings();
    } else {
        $settings = array();
    }

    $enabled           = ! empty( $settings['enabled'] ) && '1' === $settings['enabled'];
    $elementor_enabled = ! empty( $settings['elementor_enabled'] ) && '1' === $settings['elementor_enabled'];
    $debug_enabled     = ! empty( $settings['debug'] ) && '1' === $settings['debug'];
    $dry_run           = ! empty( $settings['dry_run'] ) && '1' === $settings['dry_run'];
    $wp_debug          = defined( 'WP_DEBUG' ) && WP_DEBUG;

    // Ensure debug_enabled mirrors debug for consistency in code.
    if ( isset( $settings['debug'] ) ) {
        $settings['debug_enabled'] = $settings['debug'];
    }

    // Site identity mode and toggles.
    $site_identity_mode = isset( $settings['site_identity_mode'] ) ? $settings['site_identity_mode'] : 'publisher';
    $identity_allowed   = array( 'person', 'organisation', 'publisher' );
    if ( ! in_array( $site_identity_mode, $identity_allowed, true ) ) {
        $site_identity_mode = 'publisher';
    }
    $identity_person_enabled       = isset( $settings['site_identity_person_enabled'] ) ? '1' === $settings['site_identity_person_enabled'] : true;
    $identity_organisation_enabled = isset( $settings['site_identity_organisation_enabled'] ) ? '1' === $settings['site_identity_organisation_enabled'] : true;
    $identity_publisher_enabled    = isset( $settings['site_identity_publisher_enabled'] ) ? '1' === $settings['site_identity_publisher_enabled'] : true;

    if ( 'person' === $site_identity_mode && ! $identity_person_enabled ) {
        $site_identity_mode = $identity_organisation_enabled ? 'organisation' : ( $identity_publisher_enabled ? 'publisher' : 'person' );
    } elseif ( 'organisation' === $site_identity_mode && ! $identity_organisation_enabled ) {
        $site_identity_mode = $identity_person_enabled ? 'person' : ( $identity_publisher_enabled ? 'publisher' : 'organisation' );
    } elseif ( 'publisher' === $site_identity_mode && ! $identity_publisher_enabled ) {
        $site_identity_mode = $identity_person_enabled ? 'person' : ( $identity_organisation_enabled ? 'organisation' : 'publisher' );
    }

    // Person.
    $person_enabled          = ! empty( $settings['person_enabled'] ) && '1' === $settings['person_enabled'];
    $person_name             = isset( $settings['person_name'] ) ? $settings['person_name'] : '';
    $person_description      = isset( $settings['person_description'] ) ? $settings['person_description'] : '';
    $person_optional_raw     = isset( $settings['person_optional'] ) ? $settings['person_optional'] : '';
    $person_image_url        = isset( $settings['person_image_url'] ) ? $settings['person_image_url'] : '';
    $person_honorific_prefix = isset( $settings['person_honorific_prefix'] ) ? $settings['person_honorific_prefix'] : '';
    $person_honorific_suffix = isset( $settings['person_honorific_suffix'] ) ? $settings['person_honorific_suffix'] : '';
    $person_sameas_raw       = isset( $settings['person_sameas_raw'] ) ? $settings['person_sameas_raw'] : '';

    $person_optional_props = array();
    if ( ! empty( $person_optional_raw ) ) {
        $person_optional_props = array_filter(
            array_map(
                'trim',
                explode( ',', $person_optional_raw )
            )
        );
    }
    if ( ! empty( $person_description ) && ! in_array( 'description', $person_optional_props, true ) ) {
        $person_optional_props[] = 'description';
    }
    if ( ! empty( $person_image_url ) && ! in_array( 'profile_image', $person_optional_props, true ) ) {
        $person_optional_props[] = 'profile_image';
    }
    if (
        ( ! empty( $person_honorific_prefix ) || ! empty( $person_honorific_suffix ) )
        && ! in_array( 'honorifics', $person_optional_props, true )
    ) {
        $person_optional_props[] = 'honorifics';
    }

    $person_optional_serialized = implode( ',', $person_optional_props );

    // Organisation.
    $organization_enabled = ! empty( $settings['organization_enabled'] ) && '1' === $settings['organization_enabled'];
    $org_name             = isset( $settings['org_name'] ) ? $settings['org_name'] : '';
    $org_legal_name       = isset( $settings['org_legal_name'] ) ? $settings['org_legal_name'] : '';
    $org_url              = isset( $settings['org_url'] ) ? $settings['org_url'] : '';
    $org_logo_enabled     = isset( $settings['org_logo_enabled'] ) ? '1' === $settings['org_logo_enabled'] : true;
    $org_logo             = isset( $settings['org_logo'] ) ? $settings['org_logo'] : '';
    $organization_optional_raw = isset( $settings['organization_optional'] ) ? $settings['organization_optional'] : '';

    $organization_optional_props = array();
    if ( ! empty( $organization_optional_raw ) ) {
        $organization_optional_props = array_filter(
            array_map(
                'trim',
                explode( ',', $organization_optional_raw )
            )
        );
    }
    if ( ! empty( $org_legal_name ) && ! in_array( 'legal_name', $organization_optional_props, true ) ) {
        $organization_optional_props[] = 'legal_name';
    }
    if ( ! empty( $org_url ) && ! in_array( 'org_url', $organization_optional_props, true ) ) {
        $organization_optional_props[] = 'org_url';
    }

    $organization_optional_serialized = implode( ',', $organization_optional_props );

    // WebSite featured images.
    $website_image_16_9_enabled = isset( $settings['website_image_16_9_enabled'] ) ? '1' === $settings['website_image_16_9_enabled'] : true;
    $website_image_4_3_enabled  = isset( $settings['website_image_4_3_enabled'] ) ? '1' === $settings['website_image_4_3_enabled'] : true;
    $website_image_1_1_enabled  = isset( $settings['website_image_1_1_enabled'] ) ? '1' === $settings['website_image_1_1_enabled'] : true;

    $website_image_16_9 = isset( $settings['website_image_16_9'] ) ? $settings['website_image_16_9'] : '';
    $website_image_4_3  = isset( $settings['website_image_4_3'] ) ? $settings['website_image_4_3'] : '';
    $website_image_1_1  = isset( $settings['website_image_1_1'] ) ? $settings['website_image_1_1'] : '';

    // Publisher.
    $publisher_enabled             = ! empty( $settings['publisher_enabled'] ) && '1' === $settings['publisher_enabled'];
    $publisher_dedicated_enabled   = isset( $settings['publisher_dedicated_enabled'] ) ? '1' === $settings['publisher_dedicated_enabled'] : false;
    $copyright_year                = isset( $settings['copyright_year'] ) ? $settings['copyright_year'] : '';
    $license_url                   = isset( $settings['license_url'] ) ? $settings['license_url'] : '';
    $publishing_principles         = isset( $settings['publishing_principles'] ) ? $settings['publishing_principles'] : '';
    $corrections_policy            = isset( $settings['corrections_policy'] ) ? $settings['corrections_policy'] : '';
    $ownership_funding             = isset( $settings['ownership_funding'] ) ? $settings['ownership_funding'] : '';
    $publisher_custom_name         = isset( $settings['publisher_custom_name'] ) ? $settings['publisher_custom_name'] : '';
    $publisher_custom_url          = isset( $settings['publisher_custom_url'] ) ? $settings['publisher_custom_url'] : '';
    $publisher_custom_logo         = isset( $settings['publisher_custom_logo'] ) ? $settings['publisher_custom_logo'] : '';
    $publisher_entity_optional_raw = isset( $settings['publisher_entity_optional'] ) ? $settings['publisher_entity_optional'] : '';
    $publisher_dedicated_optional_raw = isset( $settings['publisher_dedicated_optional'] ) ? $settings['publisher_dedicated_optional'] : '';

    $publisher_entity_optional_props = array();
    if ( ! empty( $publisher_entity_optional_raw ) ) {
        $publisher_entity_optional_props = array_filter(
            array_map(
                'trim',
                explode( ',', $publisher_entity_optional_raw )
            )
        );
    }

    if ( ! empty( $copyright_year ) && ! in_array( 'copyright_year', $publisher_entity_optional_props, true ) ) {
        $publisher_entity_optional_props[] = 'copyright_year';
    }
    if ( ! empty( $license_url ) && ! in_array( 'license_url', $publisher_entity_optional_props, true ) ) {
        $publisher_entity_optional_props[] = 'license_url';
    }
    if ( ! empty( $publishing_principles ) && ! in_array( 'publishing_principles', $publisher_entity_optional_props, true ) ) {
        $publisher_entity_optional_props[] = 'publishing_principles';
    }
    if ( ! empty( $corrections_policy ) && ! in_array( 'corrections_policy', $publisher_entity_optional_props, true ) ) {
        $publisher_entity_optional_props[] = 'corrections_policy';
    }
    if ( ! empty( $ownership_funding ) && ! in_array( 'ownership_funding', $publisher_entity_optional_props, true ) ) {
        $publisher_entity_optional_props[] = 'ownership_funding';
    }

    $publisher_entity_optional_serialized = implode( ',', $publisher_entity_optional_props );

    $publisher_dedicated_optional_props = array();
    if ( ! empty( $publisher_dedicated_optional_raw ) ) {
        $publisher_dedicated_optional_props = array_filter(
            array_map(
                'trim',
                explode( ',', $publisher_dedicated_optional_raw )
            )
        );
    }
    if ( ! empty( $publisher_custom_name ) && ! in_array( 'custom_name', $publisher_dedicated_optional_props, true ) ) {
        $publisher_dedicated_optional_props[] = 'custom_name';
    }
    if ( ! empty( $publisher_custom_url ) && ! in_array( 'custom_url', $publisher_dedicated_optional_props, true ) ) {
        $publisher_dedicated_optional_props[] = 'custom_url';
    }
    if ( ! empty( $publisher_custom_logo ) && ! in_array( 'custom_logo', $publisher_dedicated_optional_props, true ) ) {
        $publisher_dedicated_optional_props[] = 'custom_logo';
    }

    $publisher_dedicated_optional_serialized = implode( ',', $publisher_dedicated_optional_props );

    if ( $publisher_enabled ) {
        $publisher_type_label = $publisher_dedicated_enabled
            ? __( 'Publisher Type: Dedicated', 'beseo' )
            : __( 'Publisher Type: Reference', 'beseo' );
        $publisher_type_class = '';
    } else {
        $publisher_type_label = __( 'Publisher Type: None', 'beseo' );
        $publisher_type_class = 'off';
    }

    // Constants / overrides for messaging.
    $const_disable_all       = defined( 'BE_SCHEMA_DISABLE_ALL' ) && BE_SCHEMA_DISABLE_ALL;
    $const_disable_elementor = defined( 'BE_SCHEMA_DISABLE_ELEMENTOR' ) && BE_SCHEMA_DISABLE_ELEMENTOR;
    $const_debug             = defined( 'BE_SCHEMA_DEBUG' ) && BE_SCHEMA_DEBUG;

    // Health check: Person & Organisation.
    $person_name_effective = get_bloginfo( 'name', 'display' ); // Person name defaults to site title.
    $person_image_ok       = ! empty( $person_image_url );

    $org_name_trim = trim( (string) $org_name );
    $org_logo_ok   = ! empty( $org_logo );

    // Publisher effective name/logo resolution (simplified: custom -> org -> person).
    $publisher_name_effective = '';
    if ( ! empty( $publisher_custom_name ) ) {
        $publisher_name_effective = $publisher_custom_name;
    } elseif ( $org_name_trim ) {
        $publisher_name_effective = $org_name_trim;
    } elseif ( $person_name_effective ) {
        $publisher_name_effective = $person_name_effective;
    }

    $publisher_logo_ok = false;
    if ( ! empty( $publisher_custom_logo ) ) {
        $publisher_logo_ok = true;
    } elseif ( $org_logo_ok ) {
        $publisher_logo_ok = true;
    } elseif ( $person_image_ok ) {
        $publisher_logo_ok = true;
    }

    // Surface any validation errors from save.
    settings_errors( 'be_schema_engine' );

    ?>
    <div class="wrap beseo-wrap beseo-schema-wrap">
        <h1><?php esc_html_e( 'BE SEO – Schema', 'beseo' ); ?></h1>

        <p class="description">
            <?php esc_html_e(
                'Configure site-wide schema behavior and site entities for JSON-LD output. Page-level schema is still opt-in via Elementor controls and per-page settings.',
                'beseo'
            ); ?>
        </p>

        <style>
            .be-schema-tabs {
                margin-top: 20px;
            }

            .be-schema-tabs-nav {
                display: flex;
                gap: 8px;
                border-bottom: 1px solid #ccd0d4;
                margin-bottom: 0;
                padding-left: 0;
                list-style: none;
            }

            .be-schema-tabs-nav a {
                display: inline-block;
                padding: 8px 14px;
                text-decoration: none;
                border: 1px solid transparent;
                border-bottom: none;
                background: #f3f4f5;
                color: #555;
                cursor: pointer;
            }

            .be-schema-tabs-nav a:hover {
                background: #e5e5e5;
            }

            .be-schema-tabs-nav a.be-schema-tab-active {
                background: #fff;
                border-color: #ccd0d4;
                border-bottom: 1px solid #fff;
                color: #000;
                font-weight: 600;
            }

            .be-schema-tab-panel {
                display: none;
                padding: 16px 12px 12px;
                border: 1px solid #ccd0d4;
                border-top: none;
                background: #fff;
            }

            .be-schema-tab-panel-active {
                display: block;
            }

            .be-schema-description {
                max-width: none;
            }

            .be-schema-status-pill {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 999px;
                font-size: 11px;
                margin-right: 6px;
                background: #e5f5e0;
                color: #13610b;
            }

            .be-schema-status-pill.off {
                background: #fbeaea;
                color: #8a1f11;
            }

            .be-schema-info-box {
                margin-top: 12px;
                padding: 12px;
                border-left: 4px solid #ccd0d4;
                background: #f8f9fa;
            }

            .be-schema-health-link {
                display: inline-block;
                margin-top: 6px;
            }

            /* Vertical nav inside Website tab */
            .be-schema-website-layout {
                display: flex;
                gap: 24px;
                margin-top: 10px;
            }

            .be-schema-website-nav {
                width: 220px;
                border-right: 1px solid #ccd0d4;
            }

            .be-schema-website-nav ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .be-schema-website-nav li {
                margin: 0;
                padding: 0;
            }

            .be-schema-website-nav a {
                display: block;
                padding: 8px 10px;
                text-decoration: none;
                color: #444;
                cursor: pointer;
            }

            .be-schema-website-nav a:hover {
                background: #f1f1f1;
            }

            .be-schema-website-nav a.be-schema-website-tab-active {
                background: #2271b1;
                color: #fff;
            }

            .be-schema-website-panels {
                flex: 1;
            }

            .be-schema-website-panel {
                display: none;
            }

            .be-schema-website-panel-active {
                display: block;
            }

            /* Vertical nav inside Overview tab */
            .be-schema-overview-layout {
                display: flex;
                gap: 24px;
                margin-top: 10px;
            }

            .be-schema-overview-nav {
                width: 220px;
                border-right: 1px solid #ccd0d4;
            }

            .be-schema-overview-nav ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .be-schema-overview-nav li {
                margin: 0;
                padding: 0;
            }

            .be-schema-overview-nav a {
                display: block;
                padding: 8px 10px;
                text-decoration: none;
                color: #444;
                cursor: pointer;
            }

            .be-schema-overview-nav a:hover {
                background: #f1f1f1;
            }

            .be-schema-overview-nav a.be-schema-overview-tab-active {
                background: #2271b1;
                color: #fff;
            }

            .be-schema-overview-panels {
                flex: 1;
            }

            .be-schema-overview-panel {
                display: none;
            }

            .be-schema-overview-panel-active {
                display: block;
            }

            /* Global tab sections */
            .be-schema-global-section {
                border: 1px solid #ccd0d4;
                border-radius: 6px;
                padding: 15px;
                margin-bottom: 16px;
                background: #f9fafb;
                color: #111;
            }

            .be-schema-global-section .form-table th {
                white-space: nowrap;
                width: 1%;
            }

            .be-schema-section-title {
                display: block;
                margin: -15px -15px 12px;
                padding: 12px 15px;
                background: #e1e4e8;
                color: #111;
            }

            .be-schema-global-section h4 {
                margin: 0;
            }

            .be-schema-global-divider {
                margin: 16px 0;
                border: 0;
                border-top: 1px solid #dcdcde;
            }

            .be-schema-identity-options {
                display: flex;
                flex-direction: column;
                gap: 8px;
                padding: 12px;
            }

            .be-schema-identity-option {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: nowrap;
            }

            .be-schema-identity-toggle {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                flex: 0 0 380px;
            }

            .be-schema-identity-toggle label {
                white-space: nowrap;
            }

            .be-schema-identity-priority {
                display: flex;
                align-items: center;
                justify-content: flex-start;
            }

            .be-schema-identity-toggle label,
            .be-schema-identity-priority label {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin: 0;
            }

            .be-schema-identity-toggle label {
                font-weight: 600;
            }

            .be-schema-identity-priority label {
                font-weight: 400;
            }

            .be-schema-identity-priority label.be-identity-radio-active {
                font-weight: 700;
            }

            .be-schema-website-nav a.be-schema-website-tab-disabled {
                opacity: 0.5;
                pointer-events: none;
                cursor: default;
            }

            .be-schema-image-field {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 10px;
            }

            .be-schema-image-field .be-schema-image-enable-label {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                margin: 0;
                font-weight: 600;
                align-self: center;
            }

            .be-schema-image-field input[type="checkbox"] {
                vertical-align: middle;
                margin: 0;
            }

            .be-schema-image-field input[type="text"] {
                width: 360px;
            }

            .be-schema-image-preview {
                margin-top: 8px;
            }

            .be-schema-image-preview img {
                max-width: 150px;
                height: auto;
                border: 1px solid #ccd0d4;
                padding: 2px;
                background: #fff;
            }

            .be-schema-conditional-block {
                border-left: 3px solid #ccd0d4;
                padding-left: 12px;
                margin-top: 8px;
            }

            /* Person block */
            #be-schema-person-block {
                border-left: 0;
                padding-left: 0;
            }

            #be-schema-person-links-block {
                border-left: 0;
                padding-left: 0;
            }

            /* Organisation block */
            #be-schema-organization-block {
                border-left: 0;
                padding-left: 0;
            }

            /* Publisher block */
            #be-schema-publisher-block {
                border-left: 0;
                padding-left: 0;
            }

            #be-schema-person-block table.form-table th,
            #be-schema-person-block table.form-table td {
                padding-left: 0 !important;
                text-align: left;
                vertical-align: top;
            }

            #be-schema-person-block table.form-table th {
                padding-right: 12px !important;
                white-space: nowrap;
            }

            #be-schema-person-block table.form-table td {
                padding-right: 0 !important;
            }

            .be-schema-optional-row th,
            .be-schema-optional-row td {
                vertical-align: middle;
            }

            .be-schema-optional-row th {
                line-height: 32px;
            }

            .be-schema-person-enable-row th,
            .be-schema-person-enable-row td {
                vertical-align: baseline !important;
                padding-left: 0 !important;
                text-align: left;
            }

            .be-schema-person-enable-row td label {
                display: inline-flex;
                align-items: baseline;
                gap: 6px;
            }

            .be-schema-optional-controls {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
                margin: 8px 0 12px;
                padding-left: 0;
            }

            .be-schema-optional-controls.is-disabled {
                opacity: 0.55;
            }

            .be-schema-optional-controls select {
                min-width: 200px;
            }

            .be-schema-optional-fields .be-schema-optional-field {
                border-left: 0;
                padding-left: 0;
                margin: 0 0 16px;
            }

            .be-schema-optional-field.is-hidden {
                display: none;
            }

            .be-schema-optional-remove {
                margin-bottom: 6px;
            }

            .be-schema-honorifics {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 12px;
            }

            .be-schema-honorific-field {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .be-schema-honorific-field span {
                font-weight: 600;
            }

            .be-schema-honorific-field input[type="text"] {
                width: 180px;
            }

            .be-schema-status-pill {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 999px;
                font-size: 11px;
                margin-right: 6px;
                background: #e5f5e0;
                color: #13610b;
            }

            .be-schema-status-pill.off {
                background: #fbeaea;
                color: #8a1f11;
            }

            .be-schema-status-pill.neutral {
                background: #eef1f6;
                color: #1f2d3d;
            }

            .be-schema-conditional-block.is-disabled {
                opacity: 0.55;
            }

            .be-schema-settings-snapshot {
                margin-top: 24px;
            }

            .be-schema-settings-snapshot table {
                border-collapse: collapse;
                width: 100%;
                background: #fff;
            }

            .be-schema-settings-snapshot th,
            .be-schema-settings-snapshot td {
                border: 1px solid #e2e4e7;
                padding: 4px 6px;
                font-size: 12px;
            }

            .be-schema-settings-snapshot th {
                background: #f3f4f5;
                text-align: left;
            }

            .be-schema-settings-snapshot-key {
                width: 32%;
                font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
            }

            .be-schema-settings-snapshot-value {
                width: 68%;
            }

            /* Health check table */
            .be-schema-health-table {
                margin-top: 16px;
            }

            .be-schema-health-table table {
                border-collapse: collapse;
                width: 100%;
                background: #fff;
            }

            .be-schema-health-table th,
            .be-schema-health-table td {
                border: 1px solid #e2e4e7;
                padding: 4px 6px;
                font-size: 12px;
            }

            .be-schema-health-table th {
                background: #f3f4f5;
                text-align: left;
            }
        </style>

        <p class="description be-schema-description">
            <?php esc_html_e(
                'Schema is still controlled by several layers: global plugin settings, optional overrides in wp-config.php, per-page disable meta, and Elementor page controls. This page configures site-wide defaults.',
                'beseo'
            ); ?>
        </p>

        <form method="post">
            <?php wp_nonce_field( 'be_schema_engine_save_settings', 'be_schema_engine_settings_nonce' ); ?>
            <input type="hidden" name="be_schema_engine_settings_submitted" value="1" />

            <div class="be-schema-tabs">
                <ul class="be-schema-tabs-nav">
                    <li>
                        <a href="#be-schema-tab-settings"
                           class="be-schema-tab-link be-schema-tab-active"
                           data-schema-tab="settings">
                            <?php esc_html_e( 'Dashboard', 'beseo' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#be-schema-tab-overview"
                           class="be-schema-tab-link"
                           data-schema-tab="overview">
                            <?php esc_html_e( 'Status', 'beseo' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#be-schema-tab-website"
                           class="be-schema-tab-link"
                           data-schema-tab="website">
                            <?php esc_html_e( 'Website', 'beseo' ); ?>
                        </a>
                    </li>
                </ul>

                <!-- DASHBOARD TAB -->
                <div id="be-schema-tab-settings"
                     class="be-schema-tab-panel be-schema-tab-panel-active">
                    <h2><?php esc_html_e( 'Global Dashboard', 'beseo' ); ?></h2>

                    <div class="be-schema-global-section">
                        <h4 class="be-schema-section-title"><?php esc_html_e( 'Engine', 'beseo' ); ?></h4>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <?php esc_html_e( 'Enable Schema Engine', 'beseo' ); ?>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox"
                                                   name="be_schema_enabled"
                                                   value="1"
                                                   <?php checked( $enabled ); ?> />
                                            <?php esc_html_e(
                                                'Allow BE SEO to output JSON-LD schema.',
                                                'beseo'
                                            ); ?>
                                        </label>
                                        <p class="description be-schema-description">
                                            <?php esc_html_e(
                                                'If this is disabled, the plugin will not output any schema, regardless of page or Elementor settings.',
                                                'beseo'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <?php esc_html_e( 'Enable Elementor Schema', 'beseo' ); ?>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox"
                                                   name="be_schema_elementor_enabled"
                                                   value="1"
                                                   <?php checked( $elementor_enabled ); ?> />
                                            <?php esc_html_e(
                                                'Allow Elementor-driven schema for supported widgets and page types.',
                                                'beseo'
                                            ); ?>
                                        </label>
                                        <p class="description be-schema-description">
                                            <?php esc_html_e(
                                                'When enabled, Elementor page settings and widget controls can emit additional JSON-LD (subject to per-page safety checks).',
                                                'beseo'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="be-schema-global-section">
                        <h4 class="be-schema-section-title"><?php esc_html_e( 'Debug & Safety', 'beseo' ); ?></h4>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <?php esc_html_e( 'Debug Logging', 'beseo' ); ?>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox"
                                                   name="be_schema_debug"
                                                   value="1"
                                                   <?php checked( $debug_enabled ); ?> />
                                            <?php esc_html_e(
                                                'Log a combined @graph snapshot to the PHP error log on each request (when WP_DEBUG is true).',
                                                'beseo'
                                            ); ?>
                                        </label>
                                        <p class="description be-schema-description">
                                            <?php esc_html_e(
                                                'When enabled (and WP_DEBUG is on), the plugin writes a single BE_SCHEMA_DEBUG_GRAPH payload to the error log. This is useful for validating what the plugin thinks it is outputting.',
                                                'beseo'
                                            ); ?>
                                        </p>

                                        <p class="description be-schema-description">
                                            <?php esc_html_e(
                                                'You can also force debug via the BE_SCHEMA_DEBUG constant in wp-config.php. Constants always win over admin settings.',
                                                'beseo'
                                            ); ?>
                                        </p>

                                        <div class="be-schema-info-box">
                                            <p style="margin: 0 0 6px;">
                                                <strong><?php esc_html_e( 'Debug readiness', 'beseo' ); ?></strong>
                                            </p>
                                            <p style="margin: 0;">
                                                <span class="be-schema-status-pill <?php echo $wp_debug ? '' : 'off'; ?>">
                                                    <?php echo $wp_debug ? esc_html__( 'WP_DEBUG: ON', 'beseo' ) : esc_html__( 'WP_DEBUG: OFF', 'beseo' ); ?>
                                                </span>
                                                <span class="be-schema-status-pill <?php echo $debug_enabled ? '' : 'off'; ?>">
                                                    <?php echo $debug_enabled ? esc_html__( 'Admin Debug: ON', 'beseo' ) : esc_html__( 'Admin Debug: OFF', 'beseo' ); ?>
                                                </span>
                                                <span class="be-schema-status-pill <?php echo $const_debug ? '' : 'off'; ?>">
                                                    <?php echo $const_debug ? esc_html__( 'BE_SCHEMA_DEBUG constant: ON', 'beseo' ) : esc_html__( 'Constant: OFF', 'beseo' ); ?>
                                                </span>
                                            </p>
                                            <?php if ( ! $wp_debug ) : ?>
                                                <p class="description" style="margin: 6px 0 0;">
                                                    <?php esc_html_e( 'Debug output requires WP_DEBUG to be enabled.', 'beseo' ); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">
                                        <hr class="be-schema-global-divider" />
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <?php esc_html_e( 'Schema Dry Run (No Output)', 'beseo' ); ?>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox"
                                                   name="be_schema_dry_run"
                                                   value="1"
                                                   <?php checked( $dry_run ); ?> />
                                            <?php esc_html_e(
                                                'Compute and log schema (if debug is on) but do not emit JSON-LD markup.',
                                                'beseo'
                                            ); ?>
                                        </label>
                                        <p class="description be-schema-description">
                                            <?php esc_html_e(
                                                'Use this as a safety toggle when testing—no schema will be printed on the front end while enabled.',
                                                'beseo'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

                <!-- OVERVIEW TAB -->
                <div id="be-schema-tab-overview" class="be-schema-tab-panel">
                    <h2><?php esc_html_e( 'Status', 'beseo' ); ?></h2>
                    <p class="description be-schema-description">
                        <?php esc_html_e(
                            'Quick, read-only views of the schema engine state, WordPress overrides, and site health.',
                            'beseo'
                        ); ?>
                    </p>

                    <div class="be-schema-overview-layout">
                        <div class="be-schema-overview-nav">
                            <ul>
                                <li>
                                    <a href="#be-schema-overview-health"
                                       class="be-schema-overview-tab-link"
                                       data-overview-tab="health">
                                        <?php esc_html_e( 'Health Check', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-overview-snapshots"
                                       class="be-schema-overview-tab-link be-schema-overview-tab-active"
                                       data-overview-tab="snapshots">
                                        <?php esc_html_e( 'Individual Schema', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-overview-wordpress"
                                       class="be-schema-overview-tab-link"
                                       data-overview-tab="wordpress">
                                        <?php esc_html_e( 'WordPress', 'beseo' ); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="be-schema-overview-panels">
                            <div id="be-schema-overview-snapshots"
                                 class="be-schema-overview-panel be-schema-overview-panel-active">
                                <div class="be-schema-global-section">
                                    <h4 class="be-schema-section-title"><?php esc_html_e( 'Last Debug Snapshot', 'beseo' ); ?></h4>
                                    <?php
                                    $last_debug = get_transient( 'be_schema_last_debug_graph' );
                                    if ( $last_debug && isset( $last_debug['graph'] ) ) :
                                        $last_debug_time = isset( $last_debug['time'] ) ? (int) $last_debug['time'] : 0;
                                        ?>
                                        <p class="description be-schema-description">
                                            <?php
                                            if ( $last_debug_time ) {
                                                /* translators: %s: human time diff */
                                                printf( esc_html__( 'Captured %s ago.', 'beseo' ), esc_html( human_time_diff( $last_debug_time, time() ) ) );
                                            } else {
                                                esc_html_e( 'Captured recently.', 'beseo' );
                                            }
                                            ?>
                                        </p>
                                        <pre class="be-schema-settings-snapshot-pre" style="max-height: 260px; overflow:auto;"><?php echo esc_html( wp_json_encode( $last_debug['graph'], JSON_PRETTY_PRINT ) ); ?></pre>
                                    <?php else : ?>
                                        <p><em><?php esc_html_e( 'No debug snapshot found. Enable debug to capture the next graph.', 'beseo' ); ?></em></p>
                                    <?php endif; ?>
                                </div>

                                <div class="be-schema-global-section">
                                    <h4 class="be-schema-section-title"><?php esc_html_e( 'Individual Schema', 'beseo' ); ?></h4>
                                    <div class="be-schema-settings-snapshot">
                                        <p class="description be-schema-description">
                                            <?php esc_html_e(
                                                'A compact view of the current be_schema_engine_settings option, useful for debugging and verifying that values are saved as expected.',
                                                'beseo'
                                            ); ?>
                                        </p>
                                        <?php if ( ! empty( $settings ) && is_array( $settings ) ) : ?>
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th class="be-schema-settings-snapshot-key">
                                                            <?php esc_html_e( 'Key', 'beseo' ); ?>
                                                        </th>
                                                        <th class="be-schema-settings-snapshot-value">
                                                            <?php esc_html_e( 'Value', 'beseo' ); ?>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ( $settings as $key => $value ) : ?>
                                                        <tr>
                                                            <td class="be-schema-settings-snapshot-key">
                                                                <?php echo esc_html( $key ); ?>
                                                            </td>
                                                            <td class="be-schema-settings-snapshot-value">
                                                                <?php
                                                                if ( is_bool( $value ) ) {
                                                                    echo $value ? esc_html__( 'true', 'beseo' ) : esc_html__( 'false', 'beseo' );
                                                                } elseif ( is_array( $value ) ) {
                                                                    echo empty( $value ) ? '-' : esc_html( wp_json_encode( $value ) );
                                                                } else {
                                                                    $string_value = (string) $value;
                                                                    if ( '' === trim( $string_value ) ) {
                                                                        $string_value = '-';
                                                                    } elseif ( mb_strlen( $string_value ) > 140 ) {
                                                                        $string_value = mb_substr( $string_value, 0, 140 ) . '…';
                                                                    }
                                                                    echo esc_html( $string_value );
                                                                }
                                                                ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php else : ?>
                                            <p><em><?php esc_html_e( 'No settings found for be_schema_engine_settings.', 'beseo' ); ?></em></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div id="be-schema-overview-wordpress" class="be-schema-overview-panel">
                                <div class="be-schema-global-section">
                                    <h4 class="be-schema-section-title"><?php esc_html_e( 'wp-config Overrides', 'beseo' ); ?></h4>
                                    <div class="be-schema-settings-snapshot">
                                        <ul class="be-schema-description">
                                            <li>
                                                <code>BE_SCHEMA_DISABLE_ALL</code>:
                                                <?php esc_html_e(
                                                    'When true, disables all schema output from this plugin, regardless of admin settings.',
                                                    'beseo'
                                                ); ?>
                                                <?php if ( $const_disable_all ) : ?>
                                                    <strong><?php esc_html_e( '(Currently active)', 'beseo' ); ?></strong>
                                                <?php endif; ?>
                                            </li>
                                            <li>
                                                <code>BE_SCHEMA_DISABLE_ELEMENTOR</code>:
                                                <?php esc_html_e(
                                                    'When true, disables only Elementor-specific schema.',
                                                    'beseo'
                                                ); ?>
                                                <?php if ( $const_disable_elementor ) : ?>
                                                    <strong><?php esc_html_e( '(Currently active)', 'beseo' ); ?></strong>
                                                <?php endif; ?>
                                            </li>
                                            <li>
                                                <code>BE_SCHEMA_DEBUG</code>:
                                                <?php esc_html_e(
                                                    'When true, forces debug logging even if the admin setting is off.',
                                                    'beseo'
                                                ); ?>
                                                <?php if ( $const_debug ) : ?>
                                                    <strong><?php esc_html_e( '(Currently active)', 'beseo' ); ?></strong>
                                                <?php endif; ?>
                                            </li>
                                        </ul>
                                        <p class="description be-schema-description">
                                            <?php esc_html_e(
                                                'Use these constants sparingly, for emergency switches or local development. For day-to-day control, prefer the admin settings above.',
                                                'beseo'
                                            ); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div id="be-schema-overview-health" class="be-schema-overview-panel">
                                <div class="be-schema-global-section">
                                    <h4 class="be-schema-section-title"><?php esc_html_e( 'Schema Health Check', 'beseo' ); ?></h4>
                                    <div class="be-schema-health-table">
                                        <p class="description be-schema-description">
                                            <?php esc_html_e(
                                                'A quick, read-only summary of core site entities used by the schema engine.',
                                                'beseo'
                                            ); ?>
                                        </p>
                                        <table>
                                            <thead>
                                                <tr class="be-schema-person-enable-row">
                                                <th><?php esc_html_e( 'Entity', 'beseo' ); ?></th>
                                                <th><?php esc_html_e( 'Enabled', 'beseo' ); ?></th>
                                                <th><?php esc_html_e( 'Name', 'beseo' ); ?></th>
                                                <th><?php esc_html_e( 'Image / Logo', 'beseo' ); ?></th>
                                                <th><?php esc_html_e( 'Issues', 'beseo' ); ?></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="be-schema-person-enable-row">
                                                    <td><?php esc_html_e( 'Person', 'beseo' ); ?></td>
                                                    <td><?php echo $person_enabled ? '✅' : '⛔'; ?></td>
                                                    <td>
                                                        <?php
                                                        if ( $person_name_effective ) {
                                                            printf(
                                                                /* translators: %s: site title used as person name */
                                                                esc_html__( 'Site title: %s', 'beseo' ),
                                                                esc_html( $person_name_effective )
                                                            );
                                                        } else {
                                                            echo '⚠ ' . esc_html__( 'No effective name resolved', 'beseo' );
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if ( $person_image_ok ) {
                                                            echo '✅';
                                                        } else {
                                                            echo esc_html__( 'Not set (allowed; logo fallback may be used)', 'beseo' );
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $person_enabled ? esc_html__( 'OK', 'beseo' ) : esc_html__( 'Disabled', 'beseo' ); ?>
                                                    </td>
                                                </tr>
                                                <tr class="be-schema-person-enable-row">
                                                    <td><?php esc_html_e( 'Organisation', 'beseo' ); ?></td>
                                                    <td><?php echo $organization_enabled ? '✅' : '⛔'; ?></td>
                                                    <td>
                                                        <?php
                                                        if ( $org_name_trim ) {
                                                            echo esc_html( $org_name_trim );
                                                        } else {
                                                            echo '⚠ ' . esc_html__( 'Missing organisation name', 'beseo' );
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if ( $org_logo_ok ) {
                                                            echo '✅';
                                                        } else {
                                                            echo esc_html__( 'Not set (allowed)', 'beseo' );
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $org_issues = array();
                                                        if ( $organization_enabled && ! $org_name_trim ) {
                                                            $org_issues[] = sprintf(
                                                                '<span class="be-schema-status-pill off">%s</span>',
                                                                esc_html__( 'Name missing', 'beseo' )
                                                            );
                                                        }
                                                        if ( $organization_enabled && ! $org_logo_ok ) {
                                                            $org_issues[] = sprintf(
                                                                '<span class="be-schema-status-pill off">%s</span>',
                                                                esc_html__( 'Logo missing', 'beseo' )
                                                            );
                                                        }
                                                        if ( empty( $org_issues ) ) {
                                                            echo esc_html__( 'OK', 'beseo' );
                                                        } else {
                                                            echo implode( '<br />', $org_issues );
                                                            echo '<br /><a class="be-schema-website-tab-link be-schema-health-link" data-website-tab="organization" href="#be-schema-website-organization">' . esc_html__( 'Go to Organisation', 'beseo' ) . '</a>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><?php esc_html_e( 'Publisher', 'beseo' ); ?></td>
                                                    <td><?php echo $publisher_enabled ? '✅' : '⛔'; ?></td>
                                                    <td>
                                                        <?php
                                                        if ( $publisher_name_effective ) {
                                                            echo esc_html( $publisher_name_effective );
                                                        } else {
                                                            echo '⚠ ' . esc_html__( 'No publisher resolved', 'beseo' );
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if ( $publisher_logo_ok ) {
                                                            echo '✅';
                                                        } else {
                                                            echo esc_html__( 'Not set (allowed)', 'beseo' );
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $publisher_issues = array();
                                                        if ( $publisher_enabled && ! $publisher_name_effective ) {
                                                            $publisher_issues[] = sprintf(
                                                                '<span class="be-schema-status-pill off">%s</span>',
                                                                esc_html__( 'Name missing', 'beseo' )
                                                            );
                                                        }
                                                        if ( $publisher_enabled && ! $publisher_logo_ok ) {
                                                            $publisher_issues[] = sprintf(
                                                                '<span class="be-schema-status-pill off">%s</span>',
                                                                esc_html__( 'Logo missing', 'beseo' )
                                                            );
                                                        }
                                                        if ( empty( $publisher_issues ) ) {
                                                            echo esc_html__( 'OK', 'beseo' );
                                                        } else {
                                                            echo implode( '<br />', $publisher_issues );
                                                            echo '<br /><a class="be-schema-website-tab-link be-schema-health-link" data-website-tab="publisher" href="#be-schema-website-publisher">' . esc_html__( 'Go to Publisher', 'beseo' ) . '</a>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WEBSITE TAB -->
                <div id="be-schema-tab-website" class="be-schema-tab-panel">
                    <h2><?php esc_html_e( 'Website Entities', 'beseo' ); ?></h2>
                    <p class="description be-schema-description">
                        <?php esc_html_e(
                            'Configure site identity mode plus the Person, Organisation, Publisher, and shared logo/images used by the site-level JSON-LD graph.',
                            'beseo'
                        ); ?>
                    </p>

                    <div class="be-schema-website-layout">
                        <div class="be-schema-website-nav">
                            <ul>
                                <li>
                                    <a href="#be-schema-website-global"
                                       class="be-schema-website-tab-link be-schema-website-tab-active"
                                       data-website-tab="global">
                                        <?php esc_html_e( 'Global', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-website-person"
                                       class="be-schema-website-tab-link<?php echo $identity_person_enabled ? '' : ' be-schema-website-tab-disabled'; ?>"
                                       data-website-tab="person"
                                       <?php echo $identity_person_enabled ? '' : 'aria-disabled="true"'; ?>>
                                        <?php esc_html_e( 'Person', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-website-organization"
                                       class="be-schema-website-tab-link<?php echo $identity_organisation_enabled ? '' : ' be-schema-website-tab-disabled'; ?>"
                                       data-website-tab="organization"
                                       <?php echo $identity_organisation_enabled ? '' : 'aria-disabled="true"'; ?>>
                                        <?php esc_html_e( 'Organisation', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-website-publisher"
                                       class="be-schema-website-tab-link<?php echo $identity_publisher_enabled ? '' : ' be-schema-website-tab-disabled'; ?>"
                                       data-website-tab="publisher"
                                       <?php echo $identity_publisher_enabled ? '' : 'aria-disabled="true"'; ?>>
                                        <?php esc_html_e( 'Publisher', 'beseo' ); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                            <div class="be-schema-website-panels">

                                <!-- GLOBAL PANEL -->
                                <div id="be-schema-website-global"
                                     class="be-schema-website-panel be-schema-website-panel-active">
                                    <div class="be-schema-global-section">
                                        <h4 class="be-schema-section-title"><?php esc_html_e( 'Site Identity Mode', 'beseo' ); ?></h4>
                                    <table class="form-table">
                                        <tbody>
                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Identity', 'beseo' ); ?>
                                                </th>
                                                <td>
                                                    <div class="be-schema-identity-options">
                                                        <div class="be-schema-identity-option">
                                                            <div class="be-schema-identity-toggle">
                                                                <label for="be_schema_identity_person_checkbox">
                                                                    <input type="checkbox"
                                                                           class="be-schema-identity-checkbox"
                                                                           id="be_schema_identity_person_checkbox"
                                                                           name="be_schema_identity_person_enabled"
                                                                           data-target-radio="be_schema_identity_person_radio"
                                                                           data-target-tab="person"
                                                                           <?php checked( $identity_person_enabled ); ?> />
                                                                    <?php esc_html_e( 'Person-First (Personal Brand)', 'beseo' ); ?>
                                                                </label>
                                                            </div>
                                                            <div class="be-schema-identity-priority">
                                                                <label for="be_schema_identity_person_radio">
                                                                    <input type="radio"
                                                                           id="be_schema_identity_person_radio"
                                                                           class="be-schema-identity-radio"
                                                                           name="be_schema_site_identity_mode"
                                                                           value="person"
                                                                           <?php checked( 'person', $site_identity_mode ); ?>
                                                                           <?php disabled( ! $identity_person_enabled ); ?> />
                                                                    <?php esc_html_e( 'Priority', 'beseo' ); ?>
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <div class="be-schema-identity-option">
                                                            <div class="be-schema-identity-toggle">
                                                                <label for="be_schema_identity_org_checkbox">
                                                                    <input type="checkbox"
                                                                           class="be-schema-identity-checkbox"
                                                                           id="be_schema_identity_org_checkbox"
                                                                           name="be_schema_identity_org_enabled"
                                                                           data-target-radio="be_schema_identity_org_radio"
                                                                           data-target-tab="organization"
                                                                           <?php checked( $identity_organisation_enabled ); ?> />
                                                                    <?php esc_html_e( 'Organisation-First (Company / Organisation)', 'beseo' ); ?>
                                                                </label>
                                                            </div>
                                                            <div class="be-schema-identity-priority">
                                                                <label for="be_schema_identity_org_radio">
                                                                    <input type="radio"
                                                                           id="be_schema_identity_org_radio"
                                                                           class="be-schema-identity-radio"
                                                                           name="be_schema_site_identity_mode"
                                                                           value="organisation"
                                                                           <?php checked( 'organisation', $site_identity_mode ); ?>
                                                                           <?php disabled( ! $identity_organisation_enabled ); ?> />
                                                                    <?php esc_html_e( 'Priority', 'beseo' ); ?>
                                                                </label>
                                                            </div>
                                                        </div>

                                                    <div class="be-schema-identity-option">
                                                        <div class="be-schema-identity-toggle">
                                                            <label for="be_schema_identity_publisher_checkbox">
                                                                <input type="checkbox"
                                                                       class="be-schema-identity-checkbox"
                                                                       id="be_schema_identity_publisher_checkbox"
                                                                       name="be_schema_identity_publisher_enabled"
                                                                       data-target-radio="be_schema_identity_publisher_radio"
                                                                       data-target-tab="publisher"
                                                                       <?php checked( $identity_publisher_enabled ); ?>
                                                                       <?php disabled( ! $publisher_enabled ); ?> />
                                                                <?php esc_html_e( 'Publisher (Use Publisher Entity When Available)', 'beseo' ); ?>
                                                            </label>
                                                        </div>
                                                        <div class="be-schema-identity-priority">
                                                            <label for="be_schema_identity_publisher_radio">
                                                                <input type="radio"
                                                                       id="be_schema_identity_publisher_radio"
                                                                       class="be-schema-identity-radio"
                                                                       name="be_schema_site_identity_mode"
                                                                       value="publisher"
                                                                       <?php checked( 'publisher', $site_identity_mode ); ?>
                                                                       <?php disabled( ! $identity_publisher_enabled || ! $publisher_enabled ); ?> />
                                                                <?php esc_html_e( 'Priority', 'beseo' ); ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Controls which identity is prioritised for WebSite.about / WebSite.publisher. Other enabled entities remain in the graph as fallbacks.',
                                                            'beseo'
                                                        ); ?>
                                                    </p>
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Publisher identity can only be selected when WebSite.publisher is enabled on the Publisher tab; dedicated publisher is optional.',
                                                            'beseo'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="be-schema-global-section">
                                    <h4 class="be-schema-section-title"><?php esc_html_e( 'Images', 'beseo' ); ?></h4>
                                    <table class="form-table">
                                        <tbody>
                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Site Logo (Shared)', 'beseo' ); ?>
                                                </th>
                                                <td>
                                                    <div class="be-schema-image-field">
                                                        <label class="be-schema-image-enable-label">
                                                            <input type="checkbox"
                                                                   class="be-schema-image-enable"
                                                                   data-target-input="be_schema_org_logo"
                                                                   data-target-select="be_schema_org_logo_select"
                                                                   data-target-clear="be_schema_org_logo_clear"
                                                                   name="be_schema_org_logo_enabled"
                                                                   <?php checked( $org_logo_enabled ); ?> />
                                                            <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                        </label>
                                                        <input type="text"
                                                               id="be_schema_org_logo"
                                                               name="be_schema_org_logo"
                                                               value="<?php echo esc_url( $org_logo ); ?>"
                                                               class="regular-text"
                                                               <?php disabled( ! $org_logo_enabled ); ?> />
                                                        <button type="button"
                                                                class="button be-schema-image-select"
                                                                id="be_schema_org_logo_select"
                                                                data-target-input="be_schema_org_logo"
                                                                data-target-preview="be_schema_org_logo_preview"
                                                                <?php disabled( ! $org_logo_enabled ); ?>>
                                                            <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                        </button>
                                                        <button type="button"
                                                                class="button be-schema-image-clear"
                                                                id="be_schema_org_logo_clear"
                                                                data-target-input="be_schema_org_logo"
                                                                data-target-preview="be_schema_org_logo_preview"
                                                                <?php disabled( ! $org_logo_enabled ); ?>>
                                                            <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                        </button>
                                                    </div>
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'This logo is used by the Organisation entity, the WebSite entity, and as a fallback for the Person image when a dedicated profile picture is not provided.',
                                                            'beseo'
                                                        ); ?>
                                                    </p>
                                                    <div id="be_schema_org_logo_preview"
                                                         class="be-schema-image-preview">
                                                        <?php if ( $org_logo ) : ?>
                                                            <img src="<?php echo esc_url( $org_logo ); ?>" alt="" />
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td colspan="2">
                                                    <hr class="be-schema-global-divider" />
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'WebSite Featured Image (16:9)', 'beseo' ); ?>
                                                </th>
                                                <td>
                                                    <div class="be-schema-image-field">
                                                        <label class="be-schema-image-enable-label">
                                                            <input type="checkbox"
                                                                   class="be-schema-image-enable"
                                                                   data-target-input="be_schema_website_image_16_9"
                                                                   data-target-select="be_schema_website_image_16_9_select"
                                                                   data-target-clear="be_schema_website_image_16_9_clear"
                                                                   name="be_schema_website_image_16_9_enabled"
                                                                   <?php checked( $website_image_16_9_enabled ); ?> />
                                                            <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                        </label>
                                                        <input type="text"
                                                               id="be_schema_website_image_16_9"
                                                               name="be_schema_website_image_16_9"
                                                               value="<?php echo esc_url( $website_image_16_9 ); ?>"
                                                               class="regular-text"
                                                               <?php disabled( ! $website_image_16_9_enabled ); ?> />
                                                        <button type="button"
                                                                class="button be-schema-image-select"
                                                                id="be_schema_website_image_16_9_select"
                                                                data-target-input="be_schema_website_image_16_9"
                                                                data-target-preview="be_schema_website_image_16_9_preview"
                                                                <?php disabled( ! $website_image_16_9_enabled ); ?>>
                                                            <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                        </button>
                                                        <button type="button"
                                                                class="button be-schema-image-clear"
                                                                id="be_schema_website_image_16_9_clear"
                                                                data-target-input="be_schema_website_image_16_9"
                                                                data-target-preview="be_schema_website_image_16_9_preview"
                                                                <?php disabled( ! $website_image_16_9_enabled ); ?>>
                                                            <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                        </button>
                                                    </div>
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. A 16:9 aspect ratio image that can be used by the WebSite or WebPage schema when a featured image is needed.',
                                                            'beseo'
                                                        ); ?>
                                                    </p>
                                                    <div id="be_schema_website_image_16_9_preview"
                                                         class="be-schema-image-preview">
                                                        <?php if ( $website_image_16_9 ) : ?>
                                                            <img src="<?php echo esc_url( $website_image_16_9 ); ?>" alt="" />
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                <?php esc_html_e( 'WebSite Featured Image (4:3)', 'beseo' ); ?>
                                            </th>
                                            <td>
                                                <div class="be-schema-image-field">
                                                    <label class="be-schema-image-enable-label">
                                                        <input type="checkbox"
                                                               class="be-schema-image-enable"
                                                               data-target-input="be_schema_website_image_4_3"
                                                               data-target-select="be_schema_website_image_4_3_select"
                                                               data-target-clear="be_schema_website_image_4_3_clear"
                                                               name="be_schema_website_image_4_3_enabled"
                                                               <?php checked( $website_image_4_3_enabled ); ?> />
                                                        <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                    </label>
                                                    <input type="text"
                                                           id="be_schema_website_image_4_3"
                                                           name="be_schema_website_image_4_3"
                                                           value="<?php echo esc_url( $website_image_4_3 ); ?>"
                                                           class="regular-text"
                                                           <?php disabled( ! $website_image_4_3_enabled ); ?> />
                                                    <button type="button"
                                                            class="button be-schema-image-select"
                                                            id="be_schema_website_image_4_3_select"
                                                            data-target-input="be_schema_website_image_4_3"
                                                            data-target-preview="be_schema_website_image_4_3_preview"
                                                            <?php disabled( ! $website_image_4_3_enabled ); ?>>
                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                    </button>
                                                    <button type="button"
                                                            class="button be-schema-image-clear"
                                                            id="be_schema_website_image_4_3_clear"
                                                            data-target-input="be_schema_website_image_4_3"
                                                            data-target-preview="be_schema_website_image_4_3_preview"
                                                            <?php disabled( ! $website_image_4_3_enabled ); ?>>
                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                    </button>
                                                </div>
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. A 4:3 aspect ratio image for WebSite/WebPage schema where that shape is appropriate.',
                                                            'beseo'
                                                        ); ?>
                                                    </p>
                                                    <div id="be_schema_website_image_4_3_preview"
                                                         class="be-schema-image-preview">
                                                        <?php if ( $website_image_4_3 ) : ?>
                                                            <img src="<?php echo esc_url( $website_image_4_3 ); ?>" alt="" />
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                <?php esc_html_e( 'WebSite Featured Image (1:1)', 'beseo' ); ?>
                                            </th>
                                            <td>
                                                <div class="be-schema-image-field">
                                                    <label class="be-schema-image-enable-label">
                                                        <input type="checkbox"
                                                               class="be-schema-image-enable"
                                                               data-target-input="be_schema_website_image_1_1"
                                                               data-target-select="be_schema_website_image_1_1_select"
                                                               data-target-clear="be_schema_website_image_1_1_clear"
                                                               name="be_schema_website_image_1_1_enabled"
                                                               <?php checked( $website_image_1_1_enabled ); ?> />
                                                        <?php esc_html_e( 'Enable', 'beseo' ); ?>
                                                    </label>
                                                    <input type="text"
                                                           id="be_schema_website_image_1_1"
                                                           name="be_schema_website_image_1_1"
                                                           value="<?php echo esc_url( $website_image_1_1 ); ?>"
                                                           class="regular-text"
                                                           <?php disabled( ! $website_image_1_1_enabled ); ?> />
                                                    <button type="button"
                                                            class="button be-schema-image-select"
                                                            id="be_schema_website_image_1_1_select"
                                                            data-target-input="be_schema_website_image_1_1"
                                                            data-target-preview="be_schema_website_image_1_1_preview"
                                                            <?php disabled( ! $website_image_1_1_enabled ); ?>>
                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                    </button>
                                                    <button type="button"
                                                            class="button be-schema-image-clear"
                                                            id="be_schema_website_image_1_1_clear"
                                                            data-target-input="be_schema_website_image_1_1"
                                                            data-target-preview="be_schema_website_image_1_1_preview"
                                                            <?php disabled( ! $website_image_1_1_enabled ); ?>>
                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                    </button>
                                                </div>
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. A square (1:1) featured image for schema use.',
                                                            'beseo'
                                                        ); ?>
                                                    </p>
                                                    <div id="be_schema_website_image_1_1_preview"
                                                         class="be-schema-image-preview">
                                                        <?php if ( $website_image_1_1 ) : ?>
                                                            <img src="<?php echo esc_url( $website_image_1_1 ); ?>" alt="" />
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- PERSON PANEL -->
                            <div id="be-schema-website-person" class="be-schema-website-panel">
                                <table class="form-table be-schema-person-enable-table">
                                    <tbody>
                                        <tr class="be-schema-person-enable-row">
                                            <th scope="row">
                                                <?php esc_html_e( 'Enable Person Entity', 'beseo' ); ?>
                                            </th>
                                            <td>
                                                <label>
                                                    <input type="checkbox"
                                                           name="be_schema_person_enabled"
                                                           value="1"
                                                           class="be-schema-toggle-block"
                                                           data-target-block="be-schema-person-block be-schema-person-links-block"
                                                           <?php checked( $person_enabled ); ?> />
                                                    <?php esc_html_e(
                                                        'Include a Person node in the site-level schema.',
                                                        'beseo'
                                                    ); ?>
                                                </label>
                                                <p class="description be-schema-description">
                                                    <?php esc_html_e(
                                                        'When enabled, the site will include a Person entity (usually the primary individual behind the site). The name itself is derived from other context, such as the site name or additional configuration.',
                                                        'beseo'
                                                    ); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="be-schema-global-section">
                                    <h4 class="be-schema-section-title"><?php esc_html_e( 'Person Details', 'beseo' ); ?></h4>
                                    <p>
                                        <span class="be-schema-status-pill <?php echo $person_enabled ? '' : 'off'; ?>">
                                            <?php echo $person_enabled ? esc_html__( 'Person: ON', 'beseo' ) : esc_html__( 'Person: OFF', 'beseo' ); ?>
                                        </span>
                                    </p>
                                    <div id="be-schema-person-block"
                                         class="be-schema-conditional-block <?php echo $person_enabled ? '' : 'is-disabled'; ?>">
                                        <table class="form-table">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Person Name', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <input type="text"
                                                               name="be_schema_person_name"
                                                               value="<?php echo esc_attr( $person_name ); ?>"
                                                               class="regular-text" />
                                                        <p class="description be-schema-description">
                                                            <?php esc_html_e(
                                                                'If empty, defaults to the Site Title.',
                                                                'beseo'
                                                            ); ?>
                                                        </p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"></td>
                                                </tr>

                                                <tr class="be-schema-optional-row">
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <div class="be-schema-optional-controls" data-optional-scope="person">
                                                            <label class="screen-reader-text" for="be-schema-person-optional"><?php esc_html_e( 'Add optional Person property', 'beseo' ); ?></label>
                                                            <select id="be-schema-person-optional" aria-label="<?php esc_attr_e( 'Add optional Person property', 'beseo' ); ?>">
                                                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                                <option value="description"><?php esc_html_e( 'Description', 'beseo' ); ?></option>
                                                                <option value="profile_image"><?php esc_html_e( 'Profile Image', 'beseo' ); ?></option>
                                                                <option value="honorifics"><?php esc_html_e( 'Honorifics', 'beseo' ); ?></option>
                                                            </select>
                                                            <button type="button"
                                                                    class="button be-schema-optional-add"
                                                                    data-optional-add="person"
                                                                    disabled>
                                                                +
                                                            </button>
                                                            <input type="hidden" name="be_schema_person_optional" id="be_schema_person_optional" value="<?php echo esc_attr( $person_optional_serialized ); ?>" />
                                                        </div>

                                                        <div class="be-schema-optional-fields" id="be-schema-person-optional-fields">
                                                            <div class="be-schema-optional-field<?php echo in_array( 'description', $person_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="description">
                                                                <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="description">−</button>
                                                                <label for="be_schema_person_description" class="screen-reader-text"><?php esc_html_e( 'Description', 'beseo' ); ?></label>
                                                                <textarea
                                                                    name="be_schema_person_description"
                                                                    id="be_schema_person_description"
                                                                    rows="3"
                                                                    class="large-text code"><?php echo esc_textarea( $person_description ); ?></textarea>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Optional. A short bio or summary for the Person entity.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'profile_image', $person_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="profile_image">
                                                                <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="profile_image">−</button>
                                                                <label for="be_schema_person_image_url" class="screen-reader-text"><?php esc_html_e( 'Profile Image', 'beseo' ); ?></label>
                                                                <div class="be-schema-image-field">
                                                                    <input type="text"
                                                                           id="be_schema_person_image_url"
                                                                           name="be_schema_person_image_url"
                                                                           value="<?php echo esc_url( $person_image_url ); ?>"
                                                                           class="regular-text" />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            data-target-input="be_schema_person_image_url"
                                                                            data-target-preview="be_schema_person_image_url_preview">
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            data-target-input="be_schema_person_image_url"
                                                                            data-target-preview="be_schema_person_image_url_preview">
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Optional. If left empty, the Person entity can fall back to the shared site logo.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                                <div id="be_schema_person_image_url_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $person_image_url ) : ?>
                                                                        <img src="<?php echo esc_url( $person_image_url ); ?>" alt="" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'honorifics', $person_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="honorifics">
                                                                <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="honorifics">−</button>
                                                                <div class="be-schema-honorifics">
                                                                    <label class="be-schema-honorific-field">
                                                                        <span><?php esc_html_e( 'Prefix', 'beseo' ); ?></span>
                                                                        <input type="text"
                                                                               name="be_schema_person_honorific_prefix"
                                                                               value="<?php echo esc_attr( $person_honorific_prefix ); ?>"
                                                                               class="regular-text" />
                                                                    </label>
                                                                    <label class="be-schema-honorific-field">
                                                                        <span><?php esc_html_e( 'Suffix', 'beseo' ); ?></span>
                                                                        <input type="text"
                                                                               name="be_schema_person_honorific_suffix"
                                                                               value="<?php echo esc_attr( $person_honorific_suffix ); ?>"
                                                                               class="regular-text" />
                                                                    </label>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Optional. Prefix examples: Dr, Prof, Mr, Ms. Suffix examples: PhD, MD, CPA.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="be-schema-global-section">
                                    <h4 class="be-schema-section-title"><?php esc_html_e( 'Person Links', 'beseo' ); ?></h4>
                                    <div id="be-schema-person-links-block"
                                         class="be-schema-conditional-block <?php echo $person_enabled ? '' : 'is-disabled'; ?>">
                                        <table class="form-table">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">
                                                        <?php esc_html_e( 'SameAs URLs', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <textarea
                                                            name="be_schema_person_sameas_raw"
                                                            rows="5"
                                                            class="large-text code"><?php echo esc_textarea( $person_sameas_raw ); ?></textarea>
                                                        <p class="description be-schema-description">
                                                            <?php esc_html_e(
                                                                'One URL per line, pointing to authoritative profiles for this person (for example, knowledge panels or professional profiles). These are used as Person.sameAs and are separate from social sharing settings.',
                                                                'beseo'
                                                            ); ?>
                                                        </p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- ORGANISATION PANEL -->
                            <div id="be-schema-website-organization" class="be-schema-website-panel">
                                <table class="form-table be-schema-person-enable-table">
                                    <tbody>
                                        <tr class="be-schema-person-enable-row">
                                            <th scope="row">
                                                <?php esc_html_e( 'Enable Organisation Entity', 'beseo' ); ?>
                                            </th>
                                            <td>
                                                <label>
                                                    <input type="checkbox"
                                                           name="be_schema_organization_enabled"
                                                           value="1"
                                                           class="be-schema-toggle-block"
                                                           data-target-block="be-schema-organization-block"
                                                           <?php checked( $organization_enabled ); ?> />
                                                    <?php esc_html_e(
                                                        'Include an Organisation node for this site.',
                                                        'beseo'
                                                    ); ?>
                                                </label>
                                                <p class="description be-schema-description">
                                                    <?php esc_html_e(
                                                        'When enabled, the site will include an Organisation entity that can be used as the primary about/publisher for the WebSite, and as the default publisher for BlogPosting.',
                                                        'beseo'
                                                    ); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                    <div class="be-schema-global-section">
                                        <h4 class="be-schema-section-title"><?php esc_html_e( 'Organisation Details', 'beseo' ); ?></h4>
                                        <p>
                                            <span class="be-schema-status-pill <?php echo $organization_enabled ? '' : 'off'; ?>">
                                                <?php echo $organization_enabled ? esc_html__( 'Organisation: ON', 'beseo' ) : esc_html__( 'Organisation: OFF', 'beseo' ); ?>
                                        </span>
                                    </p>
                                    <div id="be-schema-organization-block"
                                         class="be-schema-conditional-block <?php echo $organization_enabled ? '' : 'is-disabled'; ?>">
                                        <table class="form-table">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Organisation Name', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <input type="text"
                                                               name="be_schema_org_name"
                                                               value="<?php echo esc_attr( $org_name ); ?>"
                                                               class="regular-text" />
                                                        <p class="description be-schema-description">
                                                            <?php esc_html_e(
                                                                'The public name of the organisation.',
                                                                'beseo'
                                                            ); ?>
                                                        </p>
                                                    </td>
                                                </tr>

                                                <tr class="be-schema-optional-row">
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <div class="be-schema-optional-controls" data-optional-scope="organisation">
                                                            <label class="screen-reader-text" for="be-schema-org-optional"><?php esc_html_e( 'Add optional Organisation property', 'beseo' ); ?></label>
                                                            <select id="be-schema-org-optional" aria-label="<?php esc_attr_e( 'Add optional Organisation property', 'beseo' ); ?>">
                                                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                                <option value="legal_name"><?php esc_html_e( 'Legal Name', 'beseo' ); ?></option>
                                                                <option value="org_url"><?php esc_html_e( 'Organisation URL', 'beseo' ); ?></option>
                                                            </select>
                                                            <button type="button"
                                                                    class="button be-schema-optional-add"
                                                                    data-optional-add="organisation"
                                                                    disabled>
                                                                +
                                                            </button>
                                                            <input type="hidden" name="be_schema_org_optional" id="be_schema_org_optional" value="<?php echo esc_attr( $organization_optional_serialized ); ?>" />
                                                        </div>

                                                        <div class="be-schema-optional-fields" id="be-schema-org-optional-fields">
                                                            <div class="be-schema-optional-field<?php echo in_array( 'legal_name', $organization_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="legal_name">
                                                                <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="legal_name">−</button>
                                                                <label for="be_schema_org_legal_name" class="screen-reader-text"><?php esc_html_e( 'Legal Name', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_org_legal_name"
                                                                       id="be_schema_org_legal_name"
                                                                       value="<?php echo esc_attr( $org_legal_name ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Optional. The legal name of the organisation, if different from the public name.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'org_url', $organization_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="org_url">
                                                                <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="org_url">−</button>
                                                                <label for="be_schema_org_url" class="screen-reader-text"><?php esc_html_e( 'Organisation URL', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_org_url"
                                                                       id="be_schema_org_url"
                                                                       value="<?php echo esc_url( $org_url ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Optional. If empty, the site URL is used.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Shared Logo (Read-Only)', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <p class="description be-schema-description">
                                                            <?php esc_html_e(
                                                                'The Organisation uses the shared site logo configured on the Global tab.',
                                                                'beseo'
                                                            ); ?>
                                                        </p>
                                                        <div class="be-schema-image-preview">
                                                            <?php if ( $org_logo ) : ?>
                                                                <img src="<?php echo esc_url( $org_logo ); ?>" alt="" />
                                                            <?php else : ?>
                                                                <em><?php esc_html_e( 'No shared logo selected yet.', 'beseo' ); ?></em>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- PUBLISHER PANEL -->
                            <div id="be-schema-website-publisher" class="be-schema-website-panel">
                                <table class="form-table be-schema-person-enable-table">
                                    <tbody>
                                        <tr class="be-schema-person-enable-row">
                                            <th scope="row">
                                                <?php esc_html_e( 'Enable WebSite.publisher', 'beseo' ); ?>
                                            </th>
                                            <td>
                                                <label>
                                                    <input type="checkbox"
                                                           name="be_schema_publisher_enabled"
                                                           value="1"
                                                           class="be-schema-toggle-block"
                                                           data-target-block="be-schema-publisher-block"
                                                           <?php checked( $publisher_enabled ); ?> />
                                                    <?php esc_html_e(
                                                        'Attach a Publisher entity to the WebSite.',
                                                        'beseo'
                                                    ); ?>
                                                </label>
                                                <p class="description be-schema-description">
                                                    <?php esc_html_e(
                                                        'When enabled, the WebSite.publisher property can reference either the site Person, the Organisation, or a dedicated custom publisher organisation, depending on your configuration.',
                                                        'beseo'
                                                    ); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <p>
                                    <span class="be-schema-status-pill <?php echo $publisher_enabled ? '' : 'off'; ?>">
                                        <?php echo $publisher_enabled ? esc_html__( 'Publisher: ON', 'beseo' ) : esc_html__( 'Publisher: OFF', 'beseo' ); ?>
                                    </span>
                                    <span id="be-schema-publisher-type-pill" class="be-schema-status-pill <?php echo esc_attr( $publisher_type_class ); ?>">
                                        <?php echo esc_html( $publisher_type_label ); ?>
                                    </span>
                                </p>
                                <p class="description be-schema-description">
                                    <?php esc_html_e(
                                        'Use this tab to enable WebSite.publisher; once enabled, you can select Publisher in Site Identity on the Global tab (Dedicated is optional).',
                                        'beseo'
                                    ); ?>
                                </p>

                                <div id="be-schema-publisher-block"
                                     class="be-schema-conditional-block <?php echo $publisher_enabled ? '' : 'is-disabled'; ?>">
                                    <div class="be-schema-global-section">
                                        <h4 class="be-schema-section-title"><?php esc_html_e( 'Entity', 'beseo' ); ?></h4>
                                        <table class="form-table">
                                            <tbody>
                                                <tr class="be-schema-optional-row">
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <div class="be-schema-optional-controls" data-optional-scope="publisher-entity">
                                                            <label class="screen-reader-text" for="be-schema-publisher-entity-optional"><?php esc_html_e( 'Add optional Publisher property', 'beseo' ); ?></label>
                                                            <select id="be-schema-publisher-entity-optional" aria-label="<?php esc_attr_e( 'Add optional Publisher property', 'beseo' ); ?>">
                                                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                                <option value="copyright_year"><?php esc_html_e( 'Copyright Year', 'beseo' ); ?></option>
                                                                <option value="license_url"><?php esc_html_e( 'License URL', 'beseo' ); ?></option>
                                                                <option value="publishing_principles"><?php esc_html_e( 'Publishing Principles URL', 'beseo' ); ?></option>
                                                                <option value="corrections_policy"><?php esc_html_e( 'Corrections Policy URL', 'beseo' ); ?></option>
                                                                <option value="ownership_funding"><?php esc_html_e( 'Ownership / Funding Info URL', 'beseo' ); ?></option>
                                                            </select>
                                                            <button type="button"
                                                                    class="button be-schema-optional-add"
                                                                    data-optional-add="publisher-entity"
                                                                    disabled>
                                                                +
                                                            </button>
                                                            <input type="hidden" name="be_schema_publisher_entity_optional" id="be_schema_publisher_entity_optional" value="<?php echo esc_attr( $publisher_entity_optional_serialized ); ?>" />
                                                        </div>

                                                        <div class="be-schema-optional-fields" id="be-schema-publisher-entity-optional-fields">
                                                            <div class="be-schema-optional-field<?php echo in_array( 'copyright_year', $publisher_entity_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="copyright_year">
                                                                <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="copyright_year">−</button>
                                                                <label for="be_schema_copyright_year" class="screen-reader-text"><?php esc_html_e( 'Copyright Year', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_copyright_year"
                                                                       id="be_schema_copyright_year"
                                                                       value="<?php echo esc_attr( $copyright_year ); ?>"
                                                                       class="regular-text"
                                                                       style="max-width: 120px;" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Optional. Used for descriptive publishing metadata; not all validators require this.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'license_url', $publisher_entity_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="license_url">
                                                                <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="license_url">−</button>
                                                                <label for="be_schema_license_url" class="screen-reader-text"><?php esc_html_e( 'License URL', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_license_url"
                                                                       id="be_schema_license_url"
                                                                       value="<?php echo esc_url( $license_url ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Optional. A URL describing the license under which the site content is published.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'publishing_principles', $publisher_entity_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="publishing_principles">
                                                                <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="publishing_principles">−</button>
                                                                <label for="be_schema_publishing_principles" class="screen-reader-text"><?php esc_html_e( 'Publishing Principles URL', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_publishing_principles"
                                                                       id="be_schema_publishing_principles"
                                                                       value="<?php echo esc_url( $publishing_principles ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Optional. A page describing your editorial standards or publishing principles.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'corrections_policy', $publisher_entity_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="corrections_policy">
                                                                <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="corrections_policy">−</button>
                                                                <label for="be_schema_corrections_policy" class="screen-reader-text"><?php esc_html_e( 'Corrections Policy URL', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_corrections_policy"
                                                                       id="be_schema_corrections_policy"
                                                                       value="<?php echo esc_url( $corrections_policy ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Optional. A page explaining how corrections or updates are handled.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'ownership_funding', $publisher_entity_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="ownership_funding">
                                                                <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="ownership_funding">−</button>
                                                                <label for="be_schema_ownership_funding" class="screen-reader-text"><?php esc_html_e( 'Ownership / Funding Info URL', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_ownership_funding"
                                                                       id="be_schema_ownership_funding"
                                                                       value="<?php echo esc_url( $ownership_funding ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Optional. A page describing ownership or funding information for the publisher.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="be-schema-global-section">
                                        <h4 class="be-schema-section-title"><?php esc_html_e( 'Dedicated', 'beseo' ); ?></h4>
                                        <table class="form-table">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Dedicated Publisher', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <label>
                                                            <input type="checkbox"
                                                                   name="be_schema_publisher_dedicated_enabled"
                                                                   value="1"
                                                                   <?php checked( $publisher_dedicated_enabled ); ?> />
                                                            <?php esc_html_e( 'Use a dedicated publisher entity.', 'beseo' ); ?>
                                                        </label>
                                                    </td>
                                                </tr>

                                                <tr class="be-schema-optional-row">
                                                    <th scope="row">
                                                        <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                                                    </th>
                                                    <td>
                                                        <div class="be-schema-optional-controls" data-optional-scope="publisher-dedicated">
                                                            <label class="screen-reader-text" for="be-schema-publisher-dedicated-optional"><?php esc_html_e( 'Add optional dedicated publisher property', 'beseo' ); ?></label>
                                                            <select id="be-schema-publisher-dedicated-optional" aria-label="<?php esc_attr_e( 'Add optional dedicated publisher property', 'beseo' ); ?>">
                                                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                                <option value="custom_name"><?php esc_html_e( 'Custom Publisher Organisation Name', 'beseo' ); ?></option>
                                                                <option value="custom_url"><?php esc_html_e( 'Custom Publisher URL', 'beseo' ); ?></option>
                                                                <option value="custom_logo"><?php esc_html_e( 'Custom Publisher Logo', 'beseo' ); ?></option>
                                                            </select>
                                                            <button type="button"
                                                                    class="button be-schema-optional-add"
                                                                    data-optional-add="publisher-dedicated"
                                                                    disabled>
                                                                +
                                                            </button>
                                                            <input type="hidden" name="be_schema_publisher_dedicated_optional" id="be_schema_publisher_dedicated_optional" value="<?php echo esc_attr( $publisher_dedicated_optional_serialized ); ?>" />
                                                        </div>

                                                        <div class="be-schema-optional-fields" id="be-schema-publisher-dedicated-optional-fields">
                                                            <div class="be-schema-optional-field<?php echo in_array( 'custom_name', $publisher_dedicated_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="custom_name">
                                                                <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="custom_name">−</button>
                                                                <label for="be_schema_publisher_custom_name" class="screen-reader-text"><?php esc_html_e( 'Custom Publisher Organisation Name', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_publisher_custom_name"
                                                                       id="be_schema_publisher_custom_name"
                                                                       value="<?php echo esc_attr( $publisher_custom_name ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Optional. If set, the site can treat this as a dedicated publisher organisation instead of re-using the main Organisation.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'custom_url', $publisher_dedicated_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="custom_url">
                                                                <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="custom_url">−</button>
                                                                <label for="be_schema_publisher_custom_url" class="screen-reader-text"><?php esc_html_e( 'Custom Publisher URL', 'beseo' ); ?></label>
                                                                <input type="text"
                                                                       name="be_schema_publisher_custom_url"
                                                                       id="be_schema_publisher_custom_url"
                                                                       value="<?php echo esc_url( $publisher_custom_url ); ?>"
                                                                       class="regular-text" />
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Optional. The URL for the custom publisher organisation.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                            </div>

                                                            <div class="be-schema-optional-field<?php echo in_array( 'custom_logo', $publisher_dedicated_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="custom_logo">
                                                                <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="custom_logo">−</button>
                                                                <label for="be_schema_publisher_custom_logo" class="screen-reader-text"><?php esc_html_e( 'Custom Publisher Logo', 'beseo' ); ?></label>
                                                                <div class="be-schema-image-field">
                                                                    <input type="text"
                                                                           id="be_schema_publisher_custom_logo"
                                                                           name="be_schema_publisher_custom_logo"
                                                                           value="<?php echo esc_url( $publisher_custom_logo ); ?>"
                                                                           class="regular-text" />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            data-target-input="be_schema_publisher_custom_logo"
                                                                            data-target-preview="be_schema_publisher_custom_logo_preview">
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            data-target-input="be_schema_publisher_custom_logo"
                                                                            data-target-preview="be_schema_publisher_custom_logo_preview">
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                </div>
                                                                <p class="description be-schema-description">
                                                                    <?php esc_html_e(
                                                                        'Optional. A dedicated logo for the custom publisher organisation. If empty, the shared site logo may still be used depending on the site-entity logic.',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                                <div id="be_schema_publisher_custom_logo_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $publisher_custom_logo ) : ?>
                                                                        <img src="<?php echo esc_url( $publisher_custom_logo ); ?>" alt="" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <?php submit_button( __( 'Save Schema Settings', 'beseo' ) ); ?>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Top-level tabs.
                var tabLinks = document.querySelectorAll('.be-schema-tab-link');
                var tabPanels = document.querySelectorAll('.be-schema-tab-panel');

                function activateSchemaTab(tabKey) {
                    tabLinks.forEach(function (link) {
                        if (link.getAttribute('data-schema-tab') === tabKey) {
                            link.classList.add('be-schema-tab-active');
                        } else {
                            link.classList.remove('be-schema-tab-active');
                        }
                    });

                    tabPanels.forEach(function (panel) {
                        if (panel.id === 'be-schema-tab-' + tabKey) {
                            panel.classList.add('be-schema-tab-panel-active');
                        } else {
                            panel.classList.remove('be-schema-tab-panel-active');
                        }
                    });
                }

                // Overview vertical tabs.
                var overviewLinks = document.querySelectorAll('.be-schema-overview-tab-link');
                var overviewPanels = document.querySelectorAll('.be-schema-overview-panel');

                function activateOverviewTab(tabKey) {
                    overviewLinks.forEach(function (link) {
                        if (link.getAttribute('data-overview-tab') === tabKey) {
                            link.classList.add('be-schema-overview-tab-active');
                        } else {
                            link.classList.remove('be-schema-overview-tab-active');
                        }
                    });

                    overviewPanels.forEach(function (panel) {
                        if (panel.id === 'be-schema-overview-' + tabKey) {
                            panel.classList.add('be-schema-overview-panel-active');
                        } else {
                            panel.classList.remove('be-schema-overview-panel-active');
                        }
                    });
                }

                overviewLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-overview-tab');
                        activateOverviewTab(tabKey);
                    });
                });

                tabLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-schema-tab');
                        activateSchemaTab(tabKey);
                    });
                });

                // Vertical website tabs.
                var websiteLinks = document.querySelectorAll('.be-schema-website-tab-link');
                var websitePanels = document.querySelectorAll('.be-schema-website-panel');

                function activateWebsiteTab(tabKey) {
                    websiteLinks.forEach(function (link) {
                        if (link.getAttribute('data-website-tab') === tabKey) {
                            link.classList.add('be-schema-website-tab-active');
                        } else {
                            link.classList.remove('be-schema-website-tab-active');
                        }
                    });

                    websitePanels.forEach(function (panel) {
                        if (panel.id === 'be-schema-website-' + tabKey) {
                            panel.classList.add('be-schema-website-panel-active');
                        } else {
                            panel.classList.remove('be-schema-website-panel-active');
                        }
                    });
                }

                function getFirstEnabledWebsiteTab() {
                    var first = null;
                    websiteLinks.forEach(function (link) {
                        if (first) {
                            return;
                        }
                        if (! link.classList.contains('be-schema-website-tab-disabled')) {
                            first = link.getAttribute('data-website-tab');
                        }
                    });
                    return first;
                }

                function setWebsiteTabDisabled(tabKey, disabled) {
                    websiteLinks.forEach(function (link) {
                        if (link.getAttribute('data-website-tab') !== tabKey) {
                            return;
                        }
                        if (disabled) {
                            link.classList.add('be-schema-website-tab-disabled');
                            link.setAttribute('aria-disabled', 'true');
                            if (link.classList.contains('be-schema-website-tab-active')) {
                                var fallback = getFirstEnabledWebsiteTab();
                                if (fallback && fallback !== tabKey) {
                                    activateWebsiteTab(fallback);
                                }
                            }
                        } else {
                            link.classList.remove('be-schema-website-tab-disabled');
                            link.removeAttribute('aria-disabled');
                        }
                    });
                }

                websiteLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-website-tab');
                        activateWebsiteTab(tabKey);
                    });
                });

                // Health panel quick links to Website subtabs.
                var healthLinks = document.querySelectorAll('.be-schema-health-link');
                function openWebsiteTab(target) {
                    activateSchemaTab('website');
                    if (target) {
                        activateWebsiteTab(target);
                    }
                }
                healthLinks.forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        var target = link.getAttribute('data-website-tab');
                        openWebsiteTab(target);
                    });
                });

                // Conditional blocks (Person / Organisation / Publisher).
                var toggles = document.querySelectorAll('.be-schema-toggle-block');

                function updateConditionalBlock(toggle) {
                    var targetIds = toggle.getAttribute('data-target-block');
                    if (! targetIds) {
                        return;
                    }
                    targetIds.split(/\s+/).forEach(function (targetId) {
                        if (! targetId) {
                            return;
                        }
                        var block = document.getElementById(targetId);
                        if (! block) {
                            return;
                        }
                        if (toggle.checked) {
                            block.classList.remove('is-disabled');
                        } else {
                            block.classList.add('is-disabled');
                        }
                    });
                }

                toggles.forEach(function (toggle) {
                    updateConditionalBlock(toggle);
                    toggle.addEventListener('change', function () {
                        updateConditionalBlock(toggle);
                    });
                });

                // Media pickers.
                var selectButtons = document.querySelectorAll('.be-schema-image-select');
                var clearButtons = document.querySelectorAll('.be-schema-image-clear');

                function openMediaFrame(targetInputId, targetPreviewId) {
                    if (typeof wp === 'undefined' || ! wp.media) {
                        return;
                    }

                    var frame = wp.media({
                        title: '<?php echo esc_js( __( 'Select Image', 'beseo' ) ); ?>',
                        multiple: false
                    });

                    frame.on('select', function () {
                        var attachment = frame.state().get('selection').first().toJSON();
                        var url = attachment.url || '';

                        var input = document.getElementById(targetInputId);
                        var preview = document.getElementById(targetPreviewId);

                        if (input) {
                            input.value = url;
                        }

                        if (preview) {
                            if (url) {
                                preview.innerHTML = '<img src="' + url + '" alt="" />';
                            } else {
                                preview.innerHTML = '';
                            }
                        }
                    });

                    frame.open();
                }

                selectButtons.forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        var targetInputId = button.getAttribute('data-target-input');
                        var targetPreviewId = button.getAttribute('data-target-preview');
                        if (targetInputId && targetPreviewId) {
                            openMediaFrame(targetInputId, targetPreviewId);
                        }
                    });
                });

                clearButtons.forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        var targetInputId = button.getAttribute('data-target-input');
                        var targetPreviewId = button.getAttribute('data-target-preview');

                        var input = document.getElementById(targetInputId);
                        var preview = document.getElementById(targetPreviewId);

                        if (input) {
                            input.value = '';
                        }
                        if (preview) {
                            preview.innerHTML = '';
                        }
                    });
                });

                // Image enable/disable toggles (Global tab).
                var imageEnableToggles = document.querySelectorAll('.be-schema-image-enable');

                function toggleImageField(toggle) {
                    var targetInputId = toggle.getAttribute('data-target-input');
                    var targetSelectId = toggle.getAttribute('data-target-select');
                    var targetClearId = toggle.getAttribute('data-target-clear');

                    var input = document.getElementById(targetInputId);
                    var selectBtn = document.getElementById(targetSelectId);
                    var clearBtn = document.getElementById(targetClearId);

                    var enabled = toggle.checked;

                    if (input) {
                        input.disabled = ! enabled;
                    }
                    if (selectBtn) {
                        selectBtn.disabled = ! enabled;
                    }
                    if (clearBtn) {
                        clearBtn.disabled = ! enabled;
                    }
                }

                imageEnableToggles.forEach(function (toggle) {
                    toggle.addEventListener('change', function () {
                        toggleImageField(toggle);
                    });
                    toggleImageField(toggle);
                });

                function initOptionalProperties(config) {
                    var optionalContainer = document.getElementById(config.containerId);
                    var optionalSelect = document.getElementById(config.selectId);
                    var optionalAdd = document.querySelector('[data-optional-add="' + config.scope + '"]');
                    var optionalHidden = document.getElementById(config.hiddenInputId);

                    if (! optionalContainer || ! optionalSelect || ! optionalAdd || ! optionalHidden) {
                        return;
                    }

                    function getVisibleProps() {
                        var props = [];
                        optionalContainer.querySelectorAll('.be-schema-optional-field').forEach(function (field) {
                            if (! field.classList.contains('is-hidden')) {
                                var prop = field.getAttribute('data-optional-prop');
                                if (prop) {
                                    props.push(prop);
                                }
                            }
                        });
                        return props;
                    }

                    function syncHidden() {
                        optionalHidden.value = getVisibleProps().join(',');
                    }

                    function syncAddButton() {
                        var val = optionalSelect.value;
                        var exists = val && getVisibleProps().indexOf(val) !== -1;
                        var disabled = ! val || exists;
                        optionalAdd.disabled = disabled;
                        if (disabled) {
                            optionalAdd.classList.add('disabled');
                        } else {
                            optionalAdd.classList.remove('disabled');
                        }
                    }

                    function clearFields(prop) {
                        var field = optionalContainer.querySelector('[data-optional-prop="' + prop + '"]');
                        if (! field) {
                            return;
                        }
                        field.querySelectorAll('input[type="text"], textarea').forEach(function (input) {
                            input.value = '';
                        });
                        if (config.previewIds && config.previewIds[prop]) {
                            var preview = document.getElementById(config.previewIds[prop]);
                            if (preview) {
                                preview.innerHTML = '';
                            }
                        }
                        if (typeof config.onClear === 'function') {
                            config.onClear(prop, field);
                        }
                    }

                    function showProp(prop) {
                        var field = optionalContainer.querySelector('[data-optional-prop="' + prop + '"]');
                        if (! field) {
                            return;
                        }
                        field.classList.remove('is-hidden');
                        syncHidden();
                        syncAddButton();
                    }

                    function hideProp(prop) {
                        var field = optionalContainer.querySelector('[data-optional-prop="' + prop + '"]');
                        if (! field) {
                            return;
                        }
                        clearFields(prop);
                        field.classList.add('is-hidden');
                        syncHidden();
                        syncAddButton();
                    }

                    function propHasValue(prop) {
                        if (typeof config.propHasValue === 'function') {
                            return config.propHasValue(prop);
                        }
                        return false;
                    }

                    optionalSelect.addEventListener('change', syncAddButton);

                    optionalAdd.addEventListener('click', function (event) {
                        event.preventDefault();
                        var val = optionalSelect.value;
                        if (! val) {
                            return;
                        }
                        if (getVisibleProps().indexOf(val) !== -1) {
                            return;
                        }
                        showProp(val);
                        optionalSelect.value = '';
                        syncAddButton();
                    });

                    optionalContainer.querySelectorAll('.be-schema-optional-remove').forEach(function (btn) {
                        btn.addEventListener('click', function (event) {
                            event.preventDefault();
                            var prop = btn.getAttribute('data-optional-remove');
                            hideProp(prop);
                        });
                    });

                    var initial = [];
                    if (optionalHidden.value) {
                        initial = optionalHidden.value.split(',').map(function (s) {
                            return s.trim();
                        }).filter(Boolean);
                    }

                    var knownProps = config.props && config.props.length
                        ? config.props
                        : Array.prototype.map.call(optionalContainer.querySelectorAll('.be-schema-optional-field'), function (field) {
                            return field.getAttribute('data-optional-prop');
                        });

                    knownProps.forEach(function (prop) {
                        if (initial.indexOf(prop) !== -1 || propHasValue(prop)) {
                            showProp(prop);
                        }
                    });

                    syncHidden();
                    syncAddButton();
                }

                initOptionalProperties({
                    scope: 'person',
                    containerId: 'be-schema-person-optional-fields',
                    selectId: 'be-schema-person-optional',
                    hiddenInputId: 'be_schema_person_optional',
                    props: ['description', 'profile_image', 'honorifics'],
                    previewIds: {
                        profile_image: 'be_schema_person_image_url_preview'
                    },
                    propHasValue: function (prop) {
                        if (prop === 'description') {
                            var desc = document.getElementById('be_schema_person_description');
                            return desc && desc.value.trim().length > 0;
                        }
                        if (prop === 'profile_image') {
                            var img = document.getElementById('be_schema_person_image_url');
                            return img && img.value.trim().length > 0;
                        }
                        if (prop === 'honorifics') {
                            var pre = document.querySelector('[name="be_schema_person_honorific_prefix"]');
                            var suf = document.querySelector('[name="be_schema_person_honorific_suffix"]');
                            return (pre && pre.value.trim().length > 0) || (suf && suf.value.trim().length > 0);
                        }
                        return false;
                    }
                });

                initOptionalProperties({
                    scope: 'publisher-entity',
                    containerId: 'be-schema-publisher-entity-optional-fields',
                    selectId: 'be-schema-publisher-entity-optional',
                    hiddenInputId: 'be_schema_publisher_entity_optional',
                    props: ['copyright_year', 'license_url', 'publishing_principles', 'corrections_policy', 'ownership_funding'],
                    propHasValue: function (prop) {
                        var map = {
                            copyright_year: document.getElementById('be_schema_copyright_year'),
                            license_url: document.getElementById('be_schema_license_url'),
                            publishing_principles: document.getElementById('be_schema_publishing_principles'),
                            corrections_policy: document.getElementById('be_schema_corrections_policy'),
                            ownership_funding: document.getElementById('be_schema_ownership_funding')
                        };
                        var input = map[prop];
                        return !! (input && input.value.trim().length > 0);
                    }
                });

                initOptionalProperties({
                    scope: 'publisher-dedicated',
                    containerId: 'be-schema-publisher-dedicated-optional-fields',
                    selectId: 'be-schema-publisher-dedicated-optional',
                    hiddenInputId: 'be_schema_publisher_dedicated_optional',
                    props: ['custom_name', 'custom_url', 'custom_logo'],
                    previewIds: {
                        custom_logo: 'be_schema_publisher_custom_logo_preview'
                    },
                    propHasValue: function (prop) {
                        if (prop === 'custom_logo') {
                            var logo = document.getElementById('be_schema_publisher_custom_logo');
                            return logo && logo.value.trim().length > 0;
                        }
                        var map = {
                            custom_name: document.getElementById('be_schema_publisher_custom_name'),
                            custom_url: document.getElementById('be_schema_publisher_custom_url')
                        };
                        var input = map[prop];
                        return !! (input && input.value.trim().length > 0);
                    }
                });

                initOptionalProperties({
                    scope: 'organisation',
                    containerId: 'be-schema-org-optional-fields',
                    selectId: 'be-schema-org-optional',
                    hiddenInputId: 'be_schema_org_optional',
                    props: ['legal_name', 'org_url'],
                    propHasValue: function (prop) {
                        var map = {
                            legal_name: document.getElementById('be_schema_org_legal_name'),
                            org_url: document.getElementById('be_schema_org_url')
                        };
                        var input = map[prop];
                        return !! (input && input.value.trim().length > 0);
                    }
                });

                // Publisher dedicated optional: enable only when publisher + dedicated are on.
                (function () {
                    var publisherToggle = document.querySelector('input[name="be_schema_publisher_enabled"]');
                    var dedicatedToggle = document.querySelector('input[name="be_schema_publisher_dedicated_enabled"]');
                    var controls = document.querySelector('.be-schema-optional-controls[data-optional-scope="publisher-dedicated"]');
                    var select = document.getElementById('be-schema-publisher-dedicated-optional');
                    var add = document.querySelector('[data-optional-add="publisher-dedicated"]');
                    var fields = document.getElementById('be-schema-publisher-dedicated-optional-fields');
                    var typePill = document.getElementById('be-schema-publisher-type-pill');

                    function updateTypePill() {
                        if (! typePill) {
                            return;
                        }
                        var publisherOn = !! (publisherToggle && publisherToggle.checked);
                        var dedicatedOn = !! (publisherOn && dedicatedToggle && dedicatedToggle.checked);

                        var label = '';
                        if (! publisherOn) {
                            label = '<?php echo esc_js( __( 'Publisher Type: None', 'beseo' ) ); ?>';
                            typePill.classList.add('off');
                            typePill.classList.remove('neutral');
                        } else if (dedicatedOn) {
                            label = '<?php echo esc_js( __( 'Publisher Type: Dedicated', 'beseo' ) ); ?>';
                            typePill.classList.remove('off');
                            typePill.classList.remove('neutral');
                        } else {
                            label = '<?php echo esc_js( __( 'Publisher Type: Reference', 'beseo' ) ); ?>';
                            typePill.classList.remove('off');
                            typePill.classList.remove('neutral');
                        }

                        typePill.textContent = label;
                    }

                    function setDedicatedOptionalEnabled() {
                        var enabled = !! (publisherToggle && publisherToggle.checked && dedicatedToggle && dedicatedToggle.checked);

                        if (controls) {
                            controls.classList.toggle('is-disabled', ! enabled);
                        }

                        if (select) {
                            select.disabled = ! enabled;
                            if (enabled) {
                                select.dispatchEvent(new Event('change'));
                            }
                        }

                        if (add) {
                            add.disabled = ! enabled;
                            if (! enabled) {
                                add.classList.add('disabled');
                            }
                        }

                        if (fields) {
                            fields.querySelectorAll('.be-schema-optional-remove, .be-schema-image-select, .be-schema-image-clear').forEach(function (btn) {
                                btn.disabled = ! enabled;
                            });
                            fields.querySelectorAll('input[type="text"], textarea').forEach(function (input) {
                                input.readOnly = ! enabled;
                            });
                        }

                        updateTypePill();
                    }

                    if (publisherToggle) {
                        publisherToggle.addEventListener('change', setDedicatedOptionalEnabled);
                    }
                    if (dedicatedToggle) {
                        dedicatedToggle.addEventListener('change', setDedicatedOptionalEnabled);
                    }

                    setDedicatedOptionalEnabled();
                })();

                // Identity option enable/disable.
                var identityCheckboxes = document.querySelectorAll('.be-schema-identity-checkbox');

                function updateIdentityOption(checkbox) {
                    var radioId = checkbox.getAttribute('data-target-radio');
                    var radio = document.getElementById(radioId);
                    if (! radio) {
                        return;
                    }

                    if (checkbox.checked) {
                        radio.disabled = false;
                        return;
                    }

                    var wasChecked = radio.checked;
                    radio.disabled = true;
                    radio.checked = false;

                    if (wasChecked) {
                        var fallback = document.querySelector('.be-schema-identity-radio:not(:disabled)');
                        if (fallback) {
                            fallback.checked = true;
                        }
                    }
                }

                identityCheckboxes.forEach(function (checkbox) {
                    checkbox.addEventListener('change', function () {
                        updateIdentityOption(checkbox);
                    });
                    updateIdentityOption(checkbox);
                });

                function updateIdentityTabLink(checkbox) {
                    var tabKey = checkbox.getAttribute('data-target-tab');
                    if (! tabKey || tabKey === 'publisher') {
                        return;
                    }
                    setWebsiteTabDisabled(tabKey, ! checkbox.checked);
                }

                identityCheckboxes.forEach(function (checkbox) {
                    checkbox.addEventListener('change', function () {
                        updateIdentityTabLink(checkbox);
                    });
                    updateIdentityTabLink(checkbox);
                });

                // Identity radio font weight: highlight only the checked one.
                var identityRadios = document.querySelectorAll('.be-schema-identity-radio');

                function refreshIdentityRadios() {
                    identityRadios.forEach(function (radio) {
                        var label = radio.closest('label');
                        if (! label) {
                            return;
                        }
                        if (radio.checked) {
                            label.classList.add('be-identity-radio-active');
                        } else {
                            label.classList.remove('be-identity-radio-active');
                        }
                    });
                }

                identityRadios.forEach(function (radio) {
                    radio.addEventListener('change', refreshIdentityRadios);
                });
                refreshIdentityRadios();

                // Keep publisher identity availability in sync with the main publisher enable toggle.
                (function () {
                    var publisherToggle = document.querySelector('input[name="be_schema_publisher_enabled"]');
                    var publisherIdentityCheckbox = document.getElementById('be_schema_identity_publisher_checkbox');
                    var publisherIdentityRadio = document.getElementById('be_schema_identity_publisher_radio');

                    function syncPublisherIdentityAvailability() {
                        if (! publisherIdentityCheckbox || ! publisherIdentityRadio) {
                            return;
                        }
                        var enabled = !! (publisherToggle && publisherToggle.checked);
                        publisherIdentityCheckbox.disabled = ! enabled;
                        publisherIdentityRadio.disabled = ! enabled;

                        if (! enabled) {
                            publisherIdentityCheckbox.checked = false;
                            var wasChecked = publisherIdentityRadio.checked;
                            publisherIdentityRadio.checked = false;
                            var fallback = document.querySelector('.be-schema-identity-radio:not(:disabled)');
                            if (wasChecked && fallback) {
                                fallback.checked = true;
                            }
                        }

                        updateIdentityOption(publisherIdentityCheckbox);
                        updateIdentityTabLink(publisherIdentityCheckbox);
                        refreshIdentityRadios();
                    }

                    if (publisherToggle) {
                        publisherToggle.addEventListener('change', syncPublisherIdentityAvailability);
                    }
                    syncPublisherIdentityAvailability();
                })();
            });
        </script>
    </div>
    <?php
}
