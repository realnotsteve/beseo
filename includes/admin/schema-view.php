<?php
/**
 * Schema Admin Page
 *
 * Submenu: BE SEO → Schema
 *
 * Tabs:
 *  - Status    (operation, snapshots, health check)
 *  - Preview   (graph preview for a specific page)
 *  - Website   (site identity mode plus site entities: Global / Person / Organisation / Publisher)
 *  - Defaults  (global author defaults)
 *  - Options   (debug + safety toggles)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Shared helpers live in the schema service.
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/schema-service.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/schema-view-settings.php';
require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/schema-view-preview.php';

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

if ( ! function_exists( 'be_schema_admin_sanitize_text_list' ) ) {
    /**
     * Sanitize a text field list (scalar or array) into a cleaned array.
     *
     * @param mixed $raw Raw value (string|array).
     * @return array
     */
    function be_schema_admin_sanitize_text_list( $raw ) {
        $values = array();

        if ( is_array( $raw ) ) {
            foreach ( $raw as $value ) {
                $clean = sanitize_text_field( wp_unslash( $value ) );
                if ( '' !== $clean ) {
                    $values[] = $clean;
                }
            }
        } else {
            $clean = sanitize_text_field( wp_unslash( $raw ) );
            if ( '' !== $clean ) {
                $values[] = $clean;
            }
        }

        return $values;
    }
}

if ( ! function_exists( 'be_schema_admin_normalize_text_list' ) ) {
    /**
     * Normalize a stored text field list (string or array) into an array of strings.
     *
     * @param mixed $stored Stored value.
     * @return array
     */
    function be_schema_admin_normalize_text_list( $stored ) {
        if ( empty( $stored ) ) {
            return array();
        }

        if ( is_array( $stored ) ) {
            return array_values(
                array_filter(
                    $stored,
                    function ( $val ) {
                        return '' !== $val && null !== $val;
                    }
                )
            );
        }

        $stored = trim( (string) $stored );

        return '' === $stored ? array() : array( $stored );
    }
}

if ( ! function_exists( 'be_schema_admin_ensure_list' ) ) {
    /**
     * Ensure a list has at least one (possibly empty) element for UI rendering.
     *
     * @param array $list Input list.
     * @return array
     */
    function be_schema_admin_ensure_list( $list ) {
        return ! empty( $list ) ? $list : array( '' );
    }
}

if ( ! function_exists( 'be_schema_engine_save_settings' ) ) {
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
    $settings['image_validation_enabled'] = isset( $_POST['be_schema_image_validation_enabled'] ) ? '1' : '0';

    // Playfair capture settings.
    $settings['playfair_remote_base_url'] = be_schema_engine_validate_url_field(
        isset( $_POST['be_schema_playfair_remote_base_url'] ) ? wp_unslash( $_POST['be_schema_playfair_remote_base_url'] ) : '',
        __( 'Playfair remote base URL', 'beseo' ),
        $validation_errors
    );
    $settings['playfair_local_base_url'] = be_schema_engine_validate_url_field(
        isset( $_POST['be_schema_playfair_local_base_url'] ) ? wp_unslash( $_POST['be_schema_playfair_local_base_url'] ) : '',
        __( 'Playfair local base URL', 'beseo' ),
        $validation_errors
    );
    if ( isset( $_POST['be_schema_playfair_mode'] ) ) {
        $mode = sanitize_text_field( wp_unslash( $_POST['be_schema_playfair_mode'] ) );
        if ( 'vps' === $mode ) {
            $mode = 'remote';
        }
        $settings['playfair_mode'] = in_array( $mode, array( 'auto', 'local', 'remote' ), true ) ? $mode : 'auto';
    }
    if ( isset( $_POST['be_schema_playfair_timeout_seconds'] ) ) {
        $timeout = absint( $_POST['be_schema_playfair_timeout_seconds'] );
        if ( $timeout < 5 ) {
            $timeout = 5;
        }
        if ( $timeout > 300 ) {
            $timeout = 300;
        }
        $settings['playfair_timeout_seconds'] = $timeout;
    }
    $settings['playfair_include_html_default'] = isset( $_POST['be_schema_playfair_include_html_default'] ) ? '1' : '0';
    $settings['playfair_include_logs_default'] = isset( $_POST['be_schema_playfair_include_logs_default'] ) ? '1' : '0';
    $settings['playfair_allow_private_targets'] = isset( $_POST['be_schema_playfair_allow_private_targets'] ) ? '1' : '0';
    if ( isset( $_POST['be_schema_playfair_default_profile'] ) ) {
        $profile = sanitize_text_field( wp_unslash( $_POST['be_schema_playfair_default_profile'] ) );
        $settings['playfair_default_profile'] = in_array( $profile, array( 'desktop_chromium', 'mobile_chromium', 'webkit' ), true ) ? $profile : 'desktop_chromium';
    }
    if ( isset( $_POST['be_schema_playfair_default_wait_ms'] ) ) {
        $wait_ms = absint( $_POST['be_schema_playfair_default_wait_ms'] );
        if ( $wait_ms > 60000 ) {
            $wait_ms = 60000;
        }
        $settings['playfair_default_wait_ms'] = $wait_ms;
    }
    $settings['playfair_default_locale'] = isset( $_POST['be_schema_playfair_default_locale'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_playfair_default_locale'] ) ) : '';
    $settings['playfair_default_timezone'] = isset( $_POST['be_schema_playfair_default_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_playfair_default_timezone'] ) ) : '';

    $token_new = isset( $_POST['be_schema_playfair_remote_token_new'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_playfair_remote_token_new'] ) ) : '';
    $token_clear = isset( $_POST['be_schema_playfair_remote_token_clear'] );
    if ( $token_clear ) {
        $settings['playfair_remote_token'] = '';
    } elseif ( '' !== $token_new ) {
        $settings['playfair_remote_token'] = $token_new;
    }

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

    $settings['person_url'] = isset( $_POST['be_schema_person_url'] )
        ? be_schema_engine_validate_url_field(
            wp_unslash( $_POST['be_schema_person_url'] ),
            __( 'Person URL', 'beseo' ),
            $validation_errors
        )
        : '';

    $settings['person_alumni_of'] = array();
    if ( isset( $_POST['be_schema_person_alumni_of'] ) ) {
        $settings['person_alumni_of'] = be_schema_admin_sanitize_text_list( $_POST['be_schema_person_alumni_of'] );
    }

    $settings['person_job_title'] = array();
    if ( isset( $_POST['be_schema_person_job_title'] ) ) {
        $settings['person_job_title'] = be_schema_admin_sanitize_text_list( $_POST['be_schema_person_job_title'] );
    }

    $settings['person_affiliation'] = array();
    if ( isset( $_POST['be_schema_person_affiliation'] ) ) {
        $settings['person_affiliation'] = be_schema_admin_sanitize_text_list( $_POST['be_schema_person_affiliation'] );
    }

    $settings['person_images_optional'] = isset( $_POST['be_schema_person_images_optional'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_person_images_optional'] ) )
        : '';

    $person_images = array(
        'image_16_9' => array(
            'enabled_key' => 'person_image_16_9_enabled',
            'value_key'   => 'person_image_16_9',
            'field_name'  => 'be_schema_person_image_16_9',
        ),
        'image_4_3'  => array(
            'enabled_key' => 'person_image_4_3_enabled',
            'value_key'   => 'person_image_4_3',
            'field_name'  => 'be_schema_person_image_4_3',
        ),
        'image_1_1'  => array(
            'enabled_key' => 'person_image_1_1_enabled',
            'value_key'   => 'person_image_1_1',
            'field_name'  => 'be_schema_person_image_1_1',
        ),
        'image_3_4'  => array(
            'enabled_key' => 'person_image_3_4_enabled',
            'value_key'   => 'person_image_3_4',
            'field_name'  => 'be_schema_person_image_3_4',
        ),
        'image_9_16' => array(
            'enabled_key' => 'person_image_9_16_enabled',
            'value_key'   => 'person_image_9_16',
            'field_name'  => 'be_schema_person_image_9_16',
        ),
    );

    foreach ( $person_images as $config ) {
        $enabled_key          = $config['enabled_key'];
        $value_key            = $config['value_key'];
        $field_name           = $config['field_name'];
        $settings[ $enabled_key ] = isset( $_POST[ $field_name . '_enabled' ] ) ? '1' : '0';

        if ( '1' === $settings[ $enabled_key ] && isset( $_POST[ $field_name ] ) ) {
            $settings[ $value_key ] = esc_url_raw( wp_unslash( $_POST[ $field_name ] ) );
        } else {
            $settings[ $value_key ] = '';
        }
    }

    // Derive the primary person image from the first enabled image, preferring square then widescreen.
    $person_image_priority = array( 'person_image_1_1', 'person_image_16_9', 'person_image_4_3', 'person_image_3_4', 'person_image_9_16' );
    $person_image_pick     = '';
    foreach ( $person_image_priority as $key ) {
        if ( ! empty( $settings[ $key ] ) ) {
            $person_image_pick = $settings[ $key ];
            break;
        }
    }

    $settings['person_image']        = $person_image_pick;
    $settings['person_image_url']    = $person_image_pick;
    $settings['person_image_enabled'] = $person_image_pick ? '1' : '0';

    $settings['person_honorific_prefix'] = array();
    if ( isset( $_POST['be_schema_person_honorific_prefix'] ) ) {
        $settings['person_honorific_prefix'] = be_schema_admin_sanitize_text_list( $_POST['be_schema_person_honorific_prefix'] );
    }

    $settings['person_honorific_suffix'] = array();
    if ( isset( $_POST['be_schema_person_honorific_suffix'] ) ) {
        $settings['person_honorific_suffix'] = be_schema_admin_sanitize_text_list( $_POST['be_schema_person_honorific_suffix'] );
    }

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

    $settings['org_sameas_raw'] = isset( $_POST['be_schema_org_sameas_raw'] )
        ? wp_kses_post( wp_unslash( $_POST['be_schema_org_sameas_raw'] ) )
        : '';

    $settings['org_url'] = be_schema_engine_validate_url_field(
        isset( $_POST['be_schema_org_url'] ) ? wp_unslash( $_POST['be_schema_org_url'] ) : '',
        __( 'Organisation URL', 'beseo' ),
        $validation_errors
    );

    // Shared site logo.
    $settings['org_logo_optional'] = isset( $_POST['be_schema_org_logo_optional'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_org_logo_optional'] ) )
        : '';

    $org_logo_variants = array(
        'image_16_9' => array(
            'enabled_key' => 'org_logo_image_16_9_enabled',
            'value_key'   => 'org_logo_image_16_9',
            'field_name'  => 'be_schema_org_logo_image_16_9',
        ),
        'image_4_3'  => array(
            'enabled_key' => 'org_logo_image_4_3_enabled',
            'value_key'   => 'org_logo_image_4_3',
            'field_name'  => 'be_schema_org_logo_image_4_3',
        ),
        'image_1_1'  => array(
            'enabled_key' => 'org_logo_image_1_1_enabled',
            'value_key'   => 'org_logo_image_1_1',
            'field_name'  => 'be_schema_org_logo_image_1_1',
        ),
        'image_3_4'  => array(
            'enabled_key' => 'org_logo_image_3_4_enabled',
            'value_key'   => 'org_logo_image_3_4',
            'field_name'  => 'be_schema_org_logo_image_3_4',
        ),
        'image_9_16' => array(
            'enabled_key' => 'org_logo_image_9_16_enabled',
            'value_key'   => 'org_logo_image_9_16',
            'field_name'  => 'be_schema_org_logo_image_9_16',
        ),
    );

    foreach ( $org_logo_variants as $config ) {
        $enabled_key            = $config['enabled_key'];
        $value_key              = $config['value_key'];
        $field_name             = $config['field_name'];
        $settings[ $enabled_key ] = isset( $_POST[ $field_name . '_enabled' ] ) ? '1' : '0';

        if ( '1' === $settings[ $enabled_key ] && isset( $_POST[ $field_name ] ) ) {
            $settings[ $value_key ] = esc_url_raw( wp_unslash( $_POST[ $field_name ] ) );
        } else {
            $settings[ $value_key ] = '';
        }
    }

    $org_logo_priority = array( 'org_logo_image_1_1', 'org_logo_image_16_9', 'org_logo_image_4_3', 'org_logo_image_3_4', 'org_logo_image_9_16' );
    $org_logo_pick     = '';
    foreach ( $org_logo_priority as $key ) {
        if ( ! empty( $settings[ $key ] ) ) {
            $org_logo_pick = $settings[ $key ];
            break;
        }
    }

    $settings['org_logo']         = $org_logo_pick;
    $settings['org_logo_enabled'] = $org_logo_pick ? '1' : '0';

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

    $settings['website_image_3_4_enabled'] = isset( $_POST['be_schema_website_image_3_4_enabled'] ) ? '1' : '0';
    if ( '1' === $settings['website_image_3_4_enabled'] && isset( $_POST['be_schema_website_image_3_4'] ) ) {
        $settings['website_image_3_4'] = esc_url_raw( wp_unslash( $_POST['be_schema_website_image_3_4'] ) );
    }

    $settings['website_image_9_16_enabled'] = isset( $_POST['be_schema_website_image_9_16_enabled'] ) ? '1' : '0';
    if ( '1' === $settings['website_image_9_16_enabled'] && isset( $_POST['be_schema_website_image_9_16'] ) ) {
        $settings['website_image_9_16'] = esc_url_raw( wp_unslash( $_POST['be_schema_website_image_9_16'] ) );
    }

    $settings['website_images_optional'] = isset( $_POST['be_schema_website_images_optional'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_website_images_optional'] ) )
        : '';

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
    $settings['publisher_dedicated_images_optional'] = isset( $_POST['be_schema_publisher_dedicated_images_optional'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_publisher_dedicated_images_optional'] ) )
        : '';
    $settings['publisher_image_16_9'] = isset( $_POST['be_schema_publisher_image_16_9'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_publisher_image_16_9'] ) )
        : '';
    $settings['publisher_image_4_3'] = isset( $_POST['be_schema_publisher_image_4_3'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_publisher_image_4_3'] ) )
        : '';
    $settings['publisher_image_1_1'] = isset( $_POST['be_schema_publisher_image_1_1'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_publisher_image_1_1'] ) )
        : '';
    $settings['publisher_image_3_4'] = isset( $_POST['be_schema_publisher_image_3_4'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_publisher_image_3_4'] ) )
        : '';
    $settings['publisher_image_9_16'] = isset( $_POST['be_schema_publisher_image_9_16'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_publisher_image_9_16'] ) )
        : '';

    update_option( 'be_schema_engine_settings', $settings );
    if ( function_exists( 'be_schema_engine_get_settings' ) ) {
        be_schema_engine_get_settings( true );
    }

    foreach ( $validation_errors as $message ) {
        add_settings_error( 'be_schema_engine', 'be_schema_engine_validation', $message, 'error' );
    }
}
}

/**
 * Render the Schema admin page (BE SEO → Schema).
 */
function be_schema_engine_render_schema_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Enqueue media for image pickers + shared helpers.
    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }
    wp_enqueue_script(
        'be-schema-optional-fields',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'includes/admin/js/be-optional-fields.js',
        array(),
        BE_SCHEMA_ENGINE_VERSION,
        true
    );
    wp_enqueue_script(
        'be-schema-help-accent',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'includes/admin/js/be-help-accent.js',
        array(),
        BE_SCHEMA_ENGINE_VERSION,
        true
    );
    wp_enqueue_script(
        'be-schema-image-pills',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'includes/admin/js/be-image-pills.js',
        array(),
        BE_SCHEMA_ENGINE_VERSION,
        true
    );

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
    $image_validation_enabled = isset( $settings['image_validation_enabled'] ) ? '1' === (string) $settings['image_validation_enabled'] : true;
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
    $person_url              = isset( $settings['person_url'] ) ? $settings['person_url'] : '';
    $person_alumni_of        = be_schema_admin_normalize_text_list( isset( $settings['person_alumni_of'] ) ? $settings['person_alumni_of'] : array() );
    $person_job_title        = be_schema_admin_normalize_text_list( isset( $settings['person_job_title'] ) ? $settings['person_job_title'] : array() );
    $person_affiliation      = be_schema_admin_normalize_text_list( isset( $settings['person_affiliation'] ) ? $settings['person_affiliation'] : array() );
    $person_image_url        = isset( $settings['person_image_url'] ) ? $settings['person_image_url'] : '';
    $person_images_optional_raw = isset( $settings['person_images_optional'] ) ? $settings['person_images_optional'] : '';
    $person_image_16_9       = isset( $settings['person_image_16_9'] ) ? $settings['person_image_16_9'] : '';
    $person_image_4_3        = isset( $settings['person_image_4_3'] ) ? $settings['person_image_4_3'] : '';
    $person_image_1_1        = isset( $settings['person_image_1_1'] ) ? $settings['person_image_1_1'] : '';
    $person_image_3_4        = isset( $settings['person_image_3_4'] ) ? $settings['person_image_3_4'] : '';
    $person_image_9_16       = isset( $settings['person_image_9_16'] ) ? $settings['person_image_9_16'] : '';
    $person_image_16_9_enabled = isset( $settings['person_image_16_9_enabled'] ) ? '1' === $settings['person_image_16_9_enabled'] : false;
    $person_image_4_3_enabled  = isset( $settings['person_image_4_3_enabled'] ) ? '1' === $settings['person_image_4_3_enabled'] : false;
    $person_image_1_1_enabled  = isset( $settings['person_image_1_1_enabled'] ) ? '1' === $settings['person_image_1_1_enabled'] : false;
    $person_image_3_4_enabled  = isset( $settings['person_image_3_4_enabled'] ) ? '1' === $settings['person_image_3_4_enabled'] : false;
    $person_image_9_16_enabled = isset( $settings['person_image_9_16_enabled'] ) ? '1' === $settings['person_image_9_16_enabled'] : false;
    $person_image_enabled    = $person_image_url ? true : false;
    $person_honorific_prefix = be_schema_admin_normalize_text_list( isset( $settings['person_honorific_prefix'] ) ? $settings['person_honorific_prefix'] : array() );
    $person_honorific_suffix = be_schema_admin_normalize_text_list( isset( $settings['person_honorific_suffix'] ) ? $settings['person_honorific_suffix'] : array() );
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
    $person_optional_props = array_values( array_diff( $person_optional_props, array( 'profile_image' ) ) );
    if ( ! empty( $person_description ) && ! in_array( 'description', $person_optional_props, true ) ) {
        $person_optional_props[] = 'description';
    }
    if ( ! empty( $person_honorific_prefix ) && ! in_array( 'honorific_prefix', $person_optional_props, true ) ) {
        $person_optional_props[] = 'honorific_prefix';
    }
    if ( ! empty( $person_honorific_suffix ) && ! in_array( 'honorific_suffix', $person_optional_props, true ) ) {
        $person_optional_props[] = 'honorific_suffix';
    }
    if ( ! empty( $person_url ) && ! in_array( 'person_url', $person_optional_props, true ) ) {
        $person_optional_props[] = 'person_url';
    }
    if ( ! empty( $person_alumni_of ) && ! in_array( 'alumni_of', $person_optional_props, true ) ) {
        $person_optional_props[] = 'alumni_of';
    }
    if ( ! empty( $person_job_title ) && ! in_array( 'job_title', $person_optional_props, true ) ) {
        $person_optional_props[] = 'job_title';
    }
    if ( ! empty( $person_affiliation ) && ! in_array( 'affiliation', $person_optional_props, true ) ) {
        $person_optional_props[] = 'affiliation';
    }
    if ( ! empty( $person_sameas_raw ) && ! in_array( 'sameas', $person_optional_props, true ) ) {
        $person_optional_props[] = 'sameas';
    }

    $person_optional_serialized = implode( ',', $person_optional_props );

    // Person images optional.
    $person_images_optional_props = array();
    if ( ! empty( $person_images_optional_raw ) ) {
        $person_images_optional_props = array_filter(
            array_map(
                'trim',
                explode( ',', $person_images_optional_raw )
            )
        );
    }

    $person_image_flags = array(
        'image_16_9' => array( $person_image_16_9_enabled, $person_image_16_9 ),
        'image_4_3'  => array( $person_image_4_3_enabled, $person_image_4_3 ),
        'image_1_1'  => array( $person_image_1_1_enabled, $person_image_1_1 ),
        'image_3_4'  => array( $person_image_3_4_enabled, $person_image_3_4 ),
        'image_9_16' => array( $person_image_9_16_enabled, $person_image_9_16 ),
    );

    foreach ( $person_image_flags as $key => $data ) {
        list( $enabled_flag, $value ) = $data;
        if ( $enabled_flag || ! empty( $value ) ) {
            if ( ! in_array( $key, $person_images_optional_props, true ) ) {
                $person_images_optional_props[] = $key;
            }
        }
    }

    $person_images_optional_serialized = implode( ',', $person_images_optional_props );

    // Organisation.
    $organization_enabled = ! empty( $settings['organization_enabled'] ) && '1' === $settings['organization_enabled'];
    $org_name             = isset( $settings['org_name'] ) ? $settings['org_name'] : '';
    $org_legal_name       = isset( $settings['org_legal_name'] ) ? $settings['org_legal_name'] : '';
    $org_url              = isset( $settings['org_url'] ) ? $settings['org_url'] : '';
    $org_sameas_raw       = isset( $settings['org_sameas_raw'] ) ? $settings['org_sameas_raw'] : '';
    $org_logo_enabled     = isset( $settings['org_logo_enabled'] ) ? '1' === $settings['org_logo_enabled'] : false;
    $org_logo             = isset( $settings['org_logo'] ) ? $settings['org_logo'] : '';
    $org_logo_optional_raw = isset( $settings['org_logo_optional'] ) ? $settings['org_logo_optional'] : '';
    $org_logo_image_16_9  = isset( $settings['org_logo_image_16_9'] ) ? $settings['org_logo_image_16_9'] : '';
    $org_logo_image_4_3   = isset( $settings['org_logo_image_4_3'] ) ? $settings['org_logo_image_4_3'] : '';
    $org_logo_image_1_1   = isset( $settings['org_logo_image_1_1'] ) ? $settings['org_logo_image_1_1'] : '';
    $org_logo_image_3_4   = isset( $settings['org_logo_image_3_4'] ) ? $settings['org_logo_image_3_4'] : '';
    $org_logo_image_9_16  = isset( $settings['org_logo_image_9_16'] ) ? $settings['org_logo_image_9_16'] : '';
    $org_logo_image_16_9_enabled = isset( $settings['org_logo_image_16_9_enabled'] ) ? '1' === $settings['org_logo_image_16_9_enabled'] : false;
    $org_logo_image_4_3_enabled  = isset( $settings['org_logo_image_4_3_enabled'] ) ? '1' === $settings['org_logo_image_4_3_enabled'] : false;
    $org_logo_image_1_1_enabled  = isset( $settings['org_logo_image_1_1_enabled'] ) ? '1' === $settings['org_logo_image_1_1_enabled'] : false;
    $org_logo_image_3_4_enabled  = isset( $settings['org_logo_image_3_4_enabled'] ) ? '1' === $settings['org_logo_image_3_4_enabled'] : false;
    $org_logo_image_9_16_enabled = isset( $settings['org_logo_image_9_16_enabled'] ) ? '1' === $settings['org_logo_image_9_16_enabled'] : false;
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
    if ( ! empty( $org_sameas_raw ) && ! in_array( 'org_sameas', $organization_optional_props, true ) ) {
        $organization_optional_props[] = 'org_sameas';
    }

    $organization_optional_serialized = implode( ',', $organization_optional_props );

    $org_logo_optional_props = array();
    if ( ! empty( $org_logo_optional_raw ) ) {
        $org_logo_optional_props = array_filter(
            array_map(
                'trim',
                explode( ',', $org_logo_optional_raw )
            )
        );
    }

    $org_logo_flags = array(
        'image_16_9' => array( $org_logo_image_16_9_enabled, $org_logo_image_16_9 ),
        'image_4_3'  => array( $org_logo_image_4_3_enabled, $org_logo_image_4_3 ),
        'image_1_1'  => array( $org_logo_image_1_1_enabled, $org_logo_image_1_1 ),
        'image_3_4'  => array( $org_logo_image_3_4_enabled, $org_logo_image_3_4 ),
        'image_9_16' => array( $org_logo_image_9_16_enabled, $org_logo_image_9_16 ),
    );

    foreach ( $org_logo_flags as $key => $data ) {
        list( $enabled_flag, $value ) = $data;
        if ( $enabled_flag || ! empty( $value ) ) {
            if ( ! in_array( $key, $org_logo_optional_props, true ) ) {
                $org_logo_optional_props[] = $key;
            }
        }
    }

    $org_logo_optional_serialized = implode( ',', $org_logo_optional_props );

    // WebSite featured images.
    $website_image_16_9_enabled = isset( $settings['website_image_16_9_enabled'] ) ? '1' === $settings['website_image_16_9_enabled'] : false;
    $website_image_4_3_enabled  = isset( $settings['website_image_4_3_enabled'] ) ? '1' === $settings['website_image_4_3_enabled'] : false;
    $website_image_1_1_enabled  = isset( $settings['website_image_1_1_enabled'] ) ? '1' === $settings['website_image_1_1_enabled'] : false;
    $website_image_3_4_enabled  = isset( $settings['website_image_3_4_enabled'] ) ? '1' === $settings['website_image_3_4_enabled'] : false;
    $website_image_9_16_enabled = isset( $settings['website_image_9_16_enabled'] ) ? '1' === $settings['website_image_9_16_enabled'] : false;

    $website_image_16_9 = isset( $settings['website_image_16_9'] ) ? $settings['website_image_16_9'] : '';
    $website_image_4_3  = isset( $settings['website_image_4_3'] ) ? $settings['website_image_4_3'] : '';
    $website_image_1_1  = isset( $settings['website_image_1_1'] ) ? $settings['website_image_1_1'] : '';
    $website_image_3_4  = isset( $settings['website_image_3_4'] ) ? $settings['website_image_3_4'] : '';
    $website_image_9_16 = isset( $settings['website_image_9_16'] ) ? $settings['website_image_9_16'] : '';
    $website_images_optional_raw = isset( $settings['website_images_optional'] ) ? $settings['website_images_optional'] : '';

    $website_images_optional_props = array();
    if ( ! empty( $website_images_optional_raw ) ) {
        $website_images_optional_props = array_filter(
            array_map(
                'trim',
                explode( ',', $website_images_optional_raw )
            )
        );
    }
    if ( $website_image_16_9_enabled || ! empty( $website_image_16_9 ) ) {
        if ( ! in_array( 'image_16_9', $website_images_optional_props, true ) ) {
            $website_images_optional_props[] = 'image_16_9';
        }
    }
    if ( $website_image_4_3_enabled || ! empty( $website_image_4_3 ) ) {
        if ( ! in_array( 'image_4_3', $website_images_optional_props, true ) ) {
            $website_images_optional_props[] = 'image_4_3';
        }
    }
    if ( $website_image_1_1_enabled || ! empty( $website_image_1_1 ) ) {
        if ( ! in_array( 'image_1_1', $website_images_optional_props, true ) ) {
            $website_images_optional_props[] = 'image_1_1';
        }
    }
    if ( $website_image_3_4_enabled || ! empty( $website_image_3_4 ) ) {
        if ( ! in_array( 'image_3_4', $website_images_optional_props, true ) ) {
            $website_images_optional_props[] = 'image_3_4';
        }
    }
    if ( $website_image_9_16_enabled || ! empty( $website_image_9_16 ) ) {
        if ( ! in_array( 'image_9_16', $website_images_optional_props, true ) ) {
            $website_images_optional_props[] = 'image_9_16';
        }
    }

    $website_images_optional_serialized = implode( ',', $website_images_optional_props );

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
    $publisher_image_16_9          = isset( $settings['publisher_image_16_9'] ) ? $settings['publisher_image_16_9'] : '';
    $publisher_image_4_3           = isset( $settings['publisher_image_4_3'] ) ? $settings['publisher_image_4_3'] : '';
    $publisher_image_1_1           = isset( $settings['publisher_image_1_1'] ) ? $settings['publisher_image_1_1'] : '';
    $publisher_image_3_4           = isset( $settings['publisher_image_3_4'] ) ? $settings['publisher_image_3_4'] : '';
    $publisher_image_9_16          = isset( $settings['publisher_image_9_16'] ) ? $settings['publisher_image_9_16'] : '';
    $publisher_entity_optional_raw = isset( $settings['publisher_entity_optional'] ) ? $settings['publisher_entity_optional'] : '';
    $publisher_dedicated_optional_raw = isset( $settings['publisher_dedicated_optional'] ) ? $settings['publisher_dedicated_optional'] : '';
    $publisher_dedicated_images_optional_raw = isset( $settings['publisher_dedicated_images_optional'] ) ? $settings['publisher_dedicated_images_optional'] : '';

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
    $publisher_dedicated_optional_props = array_values( array_diff( $publisher_dedicated_optional_props, array( 'custom_logo' ) ) );
    if ( ! empty( $publisher_custom_name ) && ! in_array( 'custom_name', $publisher_dedicated_optional_props, true ) ) {
        $publisher_dedicated_optional_props[] = 'custom_name';
    }
    if ( ! empty( $publisher_custom_url ) && ! in_array( 'custom_url', $publisher_dedicated_optional_props, true ) ) {
        $publisher_dedicated_optional_props[] = 'custom_url';
    }

    $publisher_dedicated_optional_serialized = implode( ',', $publisher_dedicated_optional_props );

    $publisher_dedicated_images_optional_props = array();
    if ( ! empty( $publisher_dedicated_images_optional_raw ) ) {
        $publisher_dedicated_images_optional_props = array_filter(
            array_map(
                'trim',
                explode( ',', $publisher_dedicated_images_optional_raw )
            )
        );
    }
    if ( ! empty( $publisher_custom_logo ) && ! in_array( 'custom_logo', $publisher_dedicated_images_optional_props, true ) ) {
        $publisher_dedicated_images_optional_props[] = 'custom_logo';
    }
    if ( ! empty( $publisher_image_16_9 ) && ! in_array( 'image_16_9', $publisher_dedicated_images_optional_props, true ) ) {
        $publisher_dedicated_images_optional_props[] = 'image_16_9';
    }
    if ( ! empty( $publisher_image_4_3 ) && ! in_array( 'image_4_3', $publisher_dedicated_images_optional_props, true ) ) {
        $publisher_dedicated_images_optional_props[] = 'image_4_3';
    }
    if ( ! empty( $publisher_image_1_1 ) && ! in_array( 'image_1_1', $publisher_dedicated_images_optional_props, true ) ) {
        $publisher_dedicated_images_optional_props[] = 'image_1_1';
    }
    if ( ! empty( $publisher_image_3_4 ) && ! in_array( 'image_3_4', $publisher_dedicated_images_optional_props, true ) ) {
        $publisher_dedicated_images_optional_props[] = 'image_3_4';
    }
    if ( ! empty( $publisher_image_9_16 ) && ! in_array( 'image_9_16', $publisher_dedicated_images_optional_props, true ) ) {
        $publisher_dedicated_images_optional_props[] = 'image_9_16';
    }

    $publisher_dedicated_images_optional_serialized = implode( ',', $publisher_dedicated_images_optional_props );

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
    $person_image_ok       = $person_image_enabled && ! empty( $person_image_url );

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
                        <a href="#be-schema-tab-overview"
                           class="be-schema-tab-link be-schema-tab-active"
                           data-schema-tab="overview">
                            <?php esc_html_e( 'Dashboard', 'beseo' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#be-schema-tab-preview"
                           class="be-schema-tab-link"
                           data-schema-tab="preview">
                            <?php esc_html_e( 'Tests', 'beseo' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#be-schema-tab-website"
                           class="be-schema-tab-link"
                           data-schema-tab="website">
                            <?php esc_html_e( 'Website', 'beseo' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#be-schema-tab-settings-author"
                           class="be-schema-tab-link"
                           data-schema-tab="settings-author">
                            <?php esc_html_e( 'Defaults', 'beseo' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#be-schema-tab-options"
                           class="be-schema-tab-link"
                           data-schema-tab="options">
                            <?php esc_html_e( 'Options', 'beseo' ); ?>
                        </a>
                    </li>
                </ul>

                <!-- OVERVIEW TAB -->
                <div id="be-schema-tab-overview" class="be-schema-tab-panel be-schema-tab-panel-active">
                    <h2><?php esc_html_e( 'Dashboard', 'beseo' ); ?></h2>
                    <p class="description be-schema-description">
                        <?php esc_html_e(
                            'Operational controls plus read-only views of the schema engine state, WordPress overrides, and site health.',
                            'beseo'
                        ); ?>
                    </p>

                    <div class="be-schema-overview-layout">
                        <div class="be-schema-overview-nav">
                            <ul>
                                <li>
                                    <a href="#be-schema-overview-operation"
                                       class="be-schema-overview-tab-link"
                                       data-overview-tab="operation">
                                        <?php esc_html_e( 'Operation', 'beseo' ); ?>
                                    </a>
                                </li>
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
                            <div id="be-schema-overview-operation" class="be-schema-overview-panel">
                                <div class="be-schema-global-section">
                                    <h4 class="be-schema-section-title"><?php esc_html_e( 'Operation', 'beseo' ); ?></h4>
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Enable BE Schema Engine', 'beseo' ); ?></th>
                                            <td>
                                                <label><input type="checkbox" name="be_schema_enabled" value="1" <?php checked( $enabled ); ?> /> <?php esc_html_e( 'Enable schema output', 'beseo' ); ?></label>
                                                <p class="description"><?php esc_html_e( 'Toggle global schema output on your site.', 'beseo' ); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Elementor integration', 'beseo' ); ?></th>
                                            <td>
                                                <label><input type="checkbox" name="be_schema_elementor_enabled" value="1" <?php checked( $elementor_enabled ); ?> /> <?php esc_html_e( 'Enable Elementor widgets', 'beseo' ); ?></label>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

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

                <!-- PREVIEW TAB -->
                <div id="be-schema-tab-preview" class="be-schema-tab-panel">
                    <?php be_schema_engine_render_schema_tab_preview(); ?>
                </div>

                <!-- SETTINGS TAB -->
                <div id="be-schema-tab-settings-author" class="be-schema-tab-panel">
                    <div class="be-schema-global-section">
                        <h4 class="be-schema-section-title"><?php esc_html_e( 'Global Author', 'beseo' ); ?></h4>
                        <p class="description be-schema-description">
                            <?php esc_html_e( 'Default author details applied when content needs an author and none is provided.', 'beseo' ); ?>
                        </p>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Mode', 'beseo' ); ?></th>
                                <td>
                                    <?php
                                    $global_author_mode = isset( $settings['global_author_mode'] ) ? $settings['global_author_mode'] : 'website';
                                    $use_override       = ( 'override' === $global_author_mode );
                                    $global_author_name = isset( $settings['global_author_name'] ) ? $settings['global_author_name'] : '';
                                    $global_author_url  = isset( $settings['global_author_url'] ) ? $settings['global_author_url'] : '';
                                    $global_author_type = isset( $settings['global_author_type'] ) ? $settings['global_author_type'] : 'Person';
                                    $global_author_type = in_array( $global_author_type, array( 'Person', 'Organisation' ), true ) ? $global_author_type : 'Person';

                                    $website_author_type = '';
                                    $website_author_name = '';
                                    $website_author_url  = '';

                                    if ( function_exists( 'be_schema_get_site_entities' ) ) {
                                        $site_entities = be_schema_get_site_entities();
                                        $identity      = isset( $settings['site_identity_mode'] ) ? $settings['site_identity_mode'] : 'publisher';
                                        $identity      = in_array( $identity, array( 'person', 'organisation', 'publisher' ), true ) ? $identity : 'publisher';
                                        $key           = ( 'organisation' === $identity ) ? 'organization' : $identity;
                                        $entity        = isset( $site_entities[ $key ] ) ? $site_entities[ $key ] : null;

                                        if ( $entity && is_array( $entity ) && empty( $entity['name'] ) ) {
                                            $entity = isset( $site_entities['organization'] ) ? $site_entities['organization'] : ( $site_entities['person'] ?? $entity );
                                        }

                                        if ( $entity && is_array( $entity ) ) {
                                            $entity_type = isset( $entity['@type'] ) ? $entity['@type'] : '';
                                            if ( 'Organization' === $entity_type || 'Organisation' === $entity_type ) {
                                                $website_author_type = 'Organisation';
                                            } elseif ( 'Person' === $entity_type ) {
                                                $website_author_type = 'Person';
                                            }
                                            if ( ! empty( $entity['name'] ) ) {
                                                $website_author_name = $entity['name'];
                                            }
                                            if ( ! empty( $entity['url'] ) ) {
                                                $website_author_url = $entity['url'];
                                            }
                                        }
                                    }

                                    $website_author_type_display = $website_author_type ? $website_author_type : 'Person';
                                    $author_display_type         = $use_override ? $global_author_type : $website_author_type_display;
                                    $author_display_name         = $use_override ? $global_author_name : $website_author_name;
                                    $author_display_url          = $use_override ? $global_author_url : $website_author_url;

                                    $author_warnings = array();
                                    if ( $use_override ) {
                                        if ( '' === trim( (string) $global_author_name ) ) {
                                            $author_warnings[] = __( 'Global Override is selected but Author name is empty. Image schema will not be produced for items without a local override until the fields have values.', 'beseo' );
                                        }
                                    } else {
                                        if ( '' === trim( (string) $website_author_name ) ) {
                                            $author_warnings[] = __( 'Website Entity is selected but no author name is available from the current Site Identity configuration. Image schema will not be produced for items without a local override until the fields have values.', 'beseo' );
                                        }
                                    }
                                    ?>
                                    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                                        <label>
                                            <input type="radio" name="be_schema_global_author_mode" value="website" <?php checked( ! $use_override ); ?> />
                                            <?php esc_html_e( 'Website Entity', 'beseo' ); ?>
                                        </label>
                                        <label>
                                            <input type="radio" name="be_schema_global_author_mode" value="override" <?php checked( $use_override ); ?> />
                                            <?php esc_html_e( 'Global Override', 'beseo' ); ?>
                                        </label>
                                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                            <button type="button" class="button" id="be-schema-populate-author-empty"><?php esc_html_e( 'Populate Empty', 'beseo' ); ?></button>
                                            <button type="button" class="button" id="be-schema-overwrite-author-globals"><?php esc_html_e( 'Overwrite Globals', 'beseo' ); ?></button>
                                            <span class="description" id="be-schema-populate-author-status"></span>
                                        </div>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Use Website Entity for author by default, or choose Global Override to edit fields below.', 'beseo' ); ?></p>
                                    <?php if ( ! empty( $author_warnings ) ) : ?>
                                        <div class="notice notice-warning inline" style="margin-top:8px;">
                                            <p><?php echo wp_kses_post( implode( '<br />', $author_warnings ) ); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <hr class="be-schema-global-divider" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Author type', 'beseo' ); ?></th>
                                <td>
                                    <select name="be_schema_global_author_type"
                                            data-override-value="<?php echo esc_attr( $global_author_type ); ?>"
                                            data-website-value="<?php echo esc_attr( $website_author_type_display ); ?>"
                                            <?php disabled( ! $use_override ); ?>>
                                        <option value="Person" <?php selected( 'Person', $author_display_type ); ?>><?php esc_html_e( 'Person', 'beseo' ); ?></option>
                                        <option value="Organisation" <?php selected( 'Organisation', $author_display_type ); ?>><?php esc_html_e( 'Organisation', 'beseo' ); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Choose whether the default author is a person or an organisation.', 'beseo' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Author name', 'beseo' ); ?></th>
                                <td>
                                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                        <input type="text"
                                               name="be_schema_global_author_name"
                                               class="regular-text"
                                               value="<?php echo esc_attr( $author_display_name ); ?>"
                                               data-override-value="<?php echo esc_attr( $global_author_name ); ?>"
                                               data-website-value="<?php echo esc_attr( $website_author_name ); ?>"
                                               <?php disabled( ! $use_override ); ?> />
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Name used as the default author when missing. “Populate Empty” writes name/type/URL to images without a Schema creator.', 'beseo' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Author URL', 'beseo' ); ?></th>
                                <td>
                                    <input type="url"
                                           name="be_schema_global_author_url"
                                           class="regular-text code"
                                           value="<?php echo esc_attr( $author_display_url ); ?>"
                                           data-override-value="<?php echo esc_attr( $global_author_url ); ?>"
                                           data-website-value="<?php echo esc_attr( $website_author_url ); ?>"
                                           <?php disabled( ! $use_override ); ?> />
                                    <p class="description"><?php esc_html_e( 'Optional URL for the author profile or organisation site.', 'beseo' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- OPTIONS TAB -->
                <div id="be-schema-tab-options" class="be-schema-tab-panel">
                    <?php be_schema_engine_render_schema_tab_options( $wp_debug, $debug_enabled, $dry_run, $image_validation_enabled ); ?>
                </div>

                <?php include BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/schema-view-website.php'; ?>
<?php submit_button( __( 'Save Schema Settings', 'beseo' ) ); ?>
        </form>

        
    </div>
    <script>
    jQuery(function($){
        var $authorMode = $('input[name="be_schema_global_author_mode"]');
        var $authorFields = $('select[name="be_schema_global_author_type"], input[name="be_schema_global_author_name"], input[name="be_schema_global_author_url"]');

        function applyAuthorValues(useOverride){
            $authorFields.each(function(){
                var $field = $(this);
                var overrideVal = $field.data('overrideValue');
                var websiteVal = $field.data('websiteValue');
                var nextVal = useOverride ? overrideVal : websiteVal;
                if (typeof nextVal !== 'undefined') {
                    $field.val(nextVal);
                }
            });
        }

        function syncAuthorFields(){
            var useOverride = $authorMode.filter(':checked').val() === 'override';
            $authorFields.prop('disabled', !useOverride);
            applyAuthorValues(useOverride);
        }
        if($authorMode.length){
            syncAuthorFields();
            $authorMode.on('change', syncAuthorFields);
        }

        $authorFields.on('input change', function(){
            if($authorMode.filter(':checked').val() === 'override'){
                $(this).data('overrideValue', $(this).val());
            }
        });

        var $btn = $('#be-schema-populate-author-empty');
        var $overwriteBtn = $('#be-schema-overwrite-author-globals');
        var $nameInput = $('input[name="be_schema_global_author_name"]');
        var $typeSelect = $('select[name="be_schema_global_author_type"]');
        var $status = $('#be-schema-populate-author-status');

        if($btn.length){
            $btn.on('click', function(e){
                e.preventDefault();
                var mode = $authorMode.filter(':checked').val() || 'website';
                var creator = '';
                var creatorType = $typeSelect.val() || 'Person';
                if(mode === 'override'){
                    creator = $nameInput.val().trim();
                    if(!creator){
                        alert('<?php echo esc_js( __( 'Set a Global author name first.', 'beseo' ) ); ?>');
                        return;
                    }
                }
                $btn.prop('disabled', true);
                if($status.length){ $status.text('<?php echo esc_js( __( 'Populating…', 'beseo' ) ); ?>'); }
                $.post(ajaxurl, {
                    action: 'be_schema_populate_creator_empty',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'be_schema_populate_creator_empty' ) ); ?>',
                    creator: creator,
                    creator_type: creatorType,
                    mode: mode
                }).done(function(resp){
                    var msg = (resp && resp.data && resp.data.message) ? resp.data.message : '<?php echo esc_js( __( 'Completed.', 'beseo' ) ); ?>';
                    if($status.length){ $status.text(msg); }
                }).fail(function(){
                    if($status.length){ $status.text('<?php echo esc_js( __( 'Populate failed.', 'beseo' ) ); ?>'); }
                }).always(function(){
                    $btn.prop('disabled', false);
                });
            });
        }

        if($overwriteBtn.length){
            $overwriteBtn.on('click', function(e){
                e.preventDefault();
                var mode = $authorMode.filter(':checked').val() || 'website';
                var creator = '';
                var creatorType = $typeSelect.val() || 'Person';
                if(mode === 'override'){
                    creator = $nameInput.val().trim();
                    if(!creator){
                        alert('<?php echo esc_js( __( 'Set a Global author name first.', 'beseo' ) ); ?>');
                        return;
                    }
                }
                $overwriteBtn.prop('disabled', true);
                if($status.length){ $status.text('<?php echo esc_js( __( 'Overwriting…', 'beseo' ) ); ?>'); }
                $.post(ajaxurl, {
                    action: 'be_schema_overwrite_creator_globals',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'be_schema_overwrite_creator_globals' ) ); ?>',
                    creator: creator,
                    creator_type: creatorType,
                    mode: mode
                }).done(function(resp){
                    var msg = (resp && resp.data && resp.data.message) ? resp.data.message : '<?php echo esc_js( __( 'Completed.', 'beseo' ) ); ?>';
                    if($status.length){ $status.text(msg); }
                }).fail(function(){
                    if($status.length){ $status.text('<?php echo esc_js( __( 'Overwrite failed.', 'beseo' ) ); ?>'); }
                }).always(function(){
                    $overwriteBtn.prop('disabled', false);
                });
            });
        }
    });
    </script>
    <?php
}
