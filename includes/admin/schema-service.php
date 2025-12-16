<?php
/**
 * Schema admin helpers and persistence.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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

/**
 * Save main BE Schema Engine settings.
 *
 * Option name: be_schema_engine_settings
 */
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
    $identity_person_enabled       = isset( $settings['site_identity_person_enabled'] ) ? '1' === $settings['site_identity_person_enabled'] : true;
    $identity_organisation_enabled = isset( $settings['site_identity_organisation_enabled'] ) ? '1' === $settings['site_identity_organisation_enabled'] : true;
    $identity_publisher_enabled    = isset( $settings['site_identity_publisher_enabled'] ) ? '1' === $settings['site_identity_publisher_enabled'] : true;

    if ( 'person' === $mode_current && ! $identity_person_enabled ) {
        $mode_current = $identity_organisation_enabled ? 'organisation' : ( $identity_publisher_enabled ? 'publisher' : 'person' );
    } elseif ( 'organisation' === $mode_current && ! $identity_organisation_enabled ) {
        $mode_current = $identity_person_enabled ? 'person' : ( $identity_publisher_enabled ? 'publisher' : 'organisation' );
    } elseif ( 'publisher' === $mode_current && ! $identity_publisher_enabled ) {
        $mode_current = $identity_person_enabled ? 'person' : ( $identity_organisation_enabled ? 'organisation' : 'publisher' );
    }
    $settings['site_identity_mode'] = $mode_current;

    // Person.
    $settings['person_enabled']          = isset( $_POST['be_schema_person_enabled'] ) ? '1' : '0';
    $settings['person_name']             = isset( $_POST['be_schema_person_name'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_person_name'] ) ) : '';
    $settings['person_description']      = isset( $_POST['be_schema_person_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['be_schema_person_description'] ) ) : '';
    $settings['person_optional']         = isset( $_POST['be_schema_person_optional'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_person_optional'] ) ) : '';
    $settings['person_url']              = isset( $_POST['be_schema_person_url'] ) ? esc_url_raw( wp_unslash( $_POST['be_schema_person_url'] ) ) : '';
    $settings['person_alumni_of']        = be_schema_admin_sanitize_text_list( isset( $_POST['be_schema_person_alumni_of'] ) ? $_POST['be_schema_person_alumni_of'] : array() );
    $settings['person_job_title']        = be_schema_admin_sanitize_text_list( isset( $_POST['be_schema_person_job_title'] ) ? $_POST['be_schema_person_job_title'] : array() );
    $settings['person_affiliation']      = be_schema_admin_sanitize_text_list( isset( $_POST['be_schema_person_affiliation'] ) ? $_POST['be_schema_person_affiliation'] : array() );
    $settings['person_image_url']        = isset( $_POST['be_schema_person_image_url'] ) ? esc_url_raw( wp_unslash( $_POST['be_schema_person_image_url'] ) ) : '';
    $settings['person_images_optional']  = isset( $_POST['be_schema_person_images_optional'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_person_images_optional'] ) ) : '';
    $settings['person_image_16_9']       = isset( $_POST['be_schema_person_image_16_9'] ) ? esc_url_raw( wp_unslash( $_POST['be_schema_person_image_16_9'] ) ) : '';
    $settings['person_image_4_3']        = isset( $_POST['be_schema_person_image_4_3'] ) ? esc_url_raw( wp_unslash( $_POST['be_schema_person_image_4_3'] ) ) : '';
    $settings['person_image_1_1']        = isset( $_POST['be_schema_person_image_1_1'] ) ? esc_url_raw( wp_unslash( $_POST['be_schema_person_image_1_1'] ) ) : '';
    $settings['person_image_3_4']        = isset( $_POST['be_schema_person_image_3_4'] ) ? esc_url_raw( wp_unslash( $_POST['be_schema_person_image_3_4'] ) ) : '';
    $settings['person_image_9_16']       = isset( $_POST['be_schema_person_image_9_16'] ) ? esc_url_raw( wp_unslash( $_POST['be_schema_person_image_9_16'] ) ) : '';
    $settings['person_image_16_9_enabled'] = isset( $_POST['be_schema_person_image_16_9_enabled'] ) ? '1' : '0';
    $settings['person_image_4_3_enabled']  = isset( $_POST['be_schema_person_image_4_3_enabled'] ) ? '1' : '0';
    $settings['person_image_1_1_enabled']  = isset( $_POST['be_schema_person_image_1_1_enabled'] ) ? '1' : '0';
    $settings['person_image_3_4_enabled']  = isset( $_POST['be_schema_person_image_3_4_enabled'] ) ? '1' : '0';
    $settings['person_image_9_16_enabled'] = isset( $_POST['be_schema_person_image_9_16_enabled'] ) ? '1' : '0';
    $settings['person_honorific_prefix']   = be_schema_admin_sanitize_text_list( isset( $_POST['be_schema_person_honorific_prefix'] ) ? $_POST['be_schema_person_honorific_prefix'] : array() );
    $settings['person_honorific_suffix']   = be_schema_admin_sanitize_text_list( isset( $_POST['be_schema_person_honorific_suffix'] ) ? $_POST['be_schema_person_honorific_suffix'] : array() );
    $settings['person_sameas_raw']         = isset( $_POST['be_schema_person_sameas_raw'] ) ? sanitize_textarea_field( wp_unslash( $_POST['be_schema_person_sameas_raw'] ) ) : '';

    // Organisation.
    $settings['organisation_enabled']     = isset( $_POST['be_schema_organisation_enabled'] ) ? '1' : '0';
    $settings['organisation_name']        = isset( $_POST['be_schema_organisation_name'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_organisation_name'] ) ) : '';
    $settings['organisation_description'] = isset( $_POST['be_schema_organisation_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['be_schema_organisation_description'] ) ) : '';
    $settings['organisation_url']         = isset( $_POST['be_schema_organisation_url'] ) ? be_schema_engine_validate_url_field( wp_unslash( $_POST['be_schema_organisation_url'] ), __( 'Organisation URL', 'beseo' ), $validation_errors ) : '';
    $settings['organisation_logo']        = isset( $_POST['be_schema_organisation_logo'] ) ? esc_url_raw( wp_unslash( $_POST['be_schema_organisation_logo'] ) ) : '';
    $settings['organisation_logo_id']     = isset( $_POST['be_schema_organisation_logo_id'] ) ? absint( $_POST['be_schema_organisation_logo_id'] ) : 0;
    $settings['organisation_secondary_logo']    = isset( $_POST['be_schema_organisation_secondary_logo'] ) ? esc_url_raw( wp_unslash( $_POST['be_schema_organisation_secondary_logo'] ) ) : '';
    $settings['organisation_secondary_logo_id'] = isset( $_POST['be_schema_organisation_secondary_logo_id'] ) ? absint( $_POST['be_schema_organisation_secondary_logo_id'] ) : 0;
    $settings['organisation_optional']          = isset( $_POST['be_schema_organisation_optional'] ) ? sanitize_textarea_field( wp_unslash( $_POST['be_schema_organisation_optional'] ) ) : '';
    $settings['organisation_sameas_raw']        = isset( $_POST['be_schema_organisation_sameas_raw'] ) ? sanitize_textarea_field( wp_unslash( $_POST['be_schema_organisation_sameas_raw'] ) ) : '';

    // Publisher.
    $settings['publisher_enabled']     = isset( $_POST['be_schema_publisher_enabled'] ) ? '1' : '0';
    $settings['publisher_name']        = isset( $_POST['be_schema_publisher_name'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_publisher_name'] ) ) : '';
    $settings['publisher_description'] = isset( $_POST['be_schema_publisher_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['be_schema_publisher_description'] ) ) : '';
    $settings['publisher_logo']        = isset( $_POST['be_schema_publisher_logo'] ) ? esc_url_raw( wp_unslash( $_POST['be_schema_publisher_logo'] ) ) : '';
    $settings['publisher_logo_id']     = isset( $_POST['be_schema_publisher_logo_id'] ) ? absint( $_POST['be_schema_publisher_logo_id'] ) : 0;
    $settings['publisher_secondary_logo']    = isset( $_POST['be_schema_publisher_secondary_logo'] ) ? esc_url_raw( wp_unslash( $_POST['be_schema_publisher_secondary_logo'] ) ) : '';
    $settings['publisher_secondary_logo_id'] = isset( $_POST['be_schema_publisher_secondary_logo_id'] ) ? absint( $_POST['be_schema_publisher_secondary_logo_id'] ) : 0;
    $settings['publisher_optional']          = isset( $_POST['be_schema_publisher_optional'] ) ? sanitize_textarea_field( wp_unslash( $_POST['be_schema_publisher_optional'] ) ) : '';
    $settings['publisher_sameas_raw']        = isset( $_POST['be_schema_publisher_sameas_raw'] ) ? sanitize_textarea_field( wp_unslash( $_POST['be_schema_publisher_sameas_raw'] ) ) : '';
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

    foreach ( $validation_errors as $message ) {
        add_settings_error( 'be_schema_engine', 'be_schema_engine_validation', $message, 'error' );
    }
}
}
