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
    $settings['global_creator_name']      = isset( $_POST['be_schema_global_creator_name'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_global_creator_name'] ) ) : '';
    if ( isset( $_POST['be_schema_global_creator_type'] ) ) {
        $type = sanitize_text_field( wp_unslash( $_POST['be_schema_global_creator_type'] ) );
        $settings['global_creator_type'] = in_array( $type, array( 'Person', 'Organisation' ), true ) ? $type : 'Person';
    }

    // Playfair capture settings.
    $settings['playfair_vps_endpoint'] = be_schema_engine_validate_url_field(
        isset( $_POST['be_schema_playfair_vps_endpoint'] ) ? wp_unslash( $_POST['be_schema_playfair_vps_endpoint'] ) : '',
        __( 'Playfair VPS endpoint', 'beseo' ),
        $validation_errors
    );
    $settings['playfair_vps_token'] = isset( $_POST['be_schema_playfair_vps_token'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_playfair_vps_token'] ) ) : '';
    $settings['playfair_local_endpoint'] = be_schema_engine_validate_url_field(
        isset( $_POST['be_schema_playfair_local_endpoint'] ) ? wp_unslash( $_POST['be_schema_playfair_local_endpoint'] ) : '',
        __( 'Playfair local endpoint', 'beseo' ),
        $validation_errors
    );
    if ( isset( $_POST['be_schema_playfair_target_mode'] ) ) {
        $mode = sanitize_text_field( wp_unslash( $_POST['be_schema_playfair_target_mode'] ) );
        $settings['playfair_target_mode'] = in_array( $mode, array( 'auto', 'local', 'vps' ), true ) ? $mode : 'auto';
    }
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
    // Global author.
    $settings['global_author_mode'] = isset( $_POST['be_schema_global_author_mode'] ) && 'override' === $_POST['be_schema_global_author_mode'] ? 'override' : 'website';
    $settings['global_author_name'] = isset( $_POST['be_schema_global_author_name'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_global_author_name'] ) ) : '';
    $settings['global_author_url']  = isset( $_POST['be_schema_global_author_url'] ) ? esc_url_raw( wp_unslash( $_POST['be_schema_global_author_url'] ) ) : '';
    if ( isset( $_POST['be_schema_global_author_type'] ) ) {
        $a_type = sanitize_text_field( wp_unslash( $_POST['be_schema_global_author_type'] ) );
        $settings['global_author_type'] = in_array( $a_type, array( 'Person', 'Organisation' ), true ) ? $a_type : 'Person';
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
    if ( function_exists( 'be_schema_engine_get_settings' ) ) {
        be_schema_engine_get_settings( true );
    }

    foreach ( $validation_errors as $message ) {
        add_settings_error( 'be_schema_engine', 'be_schema_engine_validation', $message, 'error' );
    }
}
}

// AJAX: Populate empty image creator meta with the global author value.
add_action(
    'wp_ajax_be_schema_populate_creator_empty',
    function() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'beseo' ) ), 403 );
        }

        $nonce = isset( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : '';
        if ( ! wp_verify_nonce( $nonce, 'be_schema_populate_creator_empty' ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'beseo' ) ), 400 );
        }

        $mode         = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'website';
        $mode         = in_array( $mode, array( 'website', 'override' ), true ) ? $mode : 'website';
        $creator      = isset( $_POST['creator'] ) ? sanitize_text_field( wp_unslash( $_POST['creator'] ) ) : '';
        $creator_type = isset( $_POST['creator_type'] ) ? sanitize_text_field( wp_unslash( $_POST['creator_type'] ) ) : 'Person';
        $creator_type = in_array( $creator_type, array( 'Person', 'Organisation' ), true ) ? $creator_type : 'Person';
        $creator_url  = '';

        if ( 'website' === $mode ) {
            $settings = be_schema_engine_get_settings();
            $entities = be_schema_get_site_entities();
            $identity = isset( $settings['site_identity_mode'] ) ? $settings['site_identity_mode'] : 'publisher';
            $identity = in_array( $identity, array( 'person', 'organisation', 'publisher' ), true ) ? $identity : 'publisher';
            $key      = ( 'organisation' === $identity ) ? 'organization' : $identity;
            $entity   = isset( $entities[ $key ] ) ? $entities[ $key ] : null;

            if ( $entity && is_array( $entity ) && empty( $entity['name'] ) ) {
                $entity = isset( $entities['organization'] ) ? $entities['organization'] : ( $entities['person'] ?? $entity );
            }

            if ( $entity && is_array( $entity ) ) {
                $creator = isset( $entity['name'] ) ? $entity['name'] : '';
                $type_raw = isset( $entity['@type'] ) ? $entity['@type'] : '';
                if ( 'Organization' === $type_raw ) {
                    $creator_type = 'Organisation';
                } elseif ( 'Person' === $type_raw ) {
                    $creator_type = 'Person';
                }
                if ( ! empty( $entity['url'] ) ) {
                    $creator_url = $entity['url'];
                }
            }
        } elseif ( 'override' === $mode ) {
            $settings = be_schema_engine_get_settings();
            if ( ! empty( $settings['global_author_url'] ) ) {
                $creator_url = $settings['global_author_url'];
            }
        }

        if ( '' === $creator ) {
            wp_send_json_error( array( 'message' => __( 'Global author is empty.', 'beseo' ) ), 400 );
        }

        $query = new WP_Query(
            array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_mime_type' => 'image',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            )
        );

        $updated = 0;
        $skipped = 0;

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $attachment_id ) {
                $current = get_post_meta( $attachment_id, '_be_schema_creator_name', true );
                $enabled_flag = get_post_meta( $attachment_id, '_be_schema_creator_enabled', true );
                if ( '0' === $enabled_flag ) {
                    $skipped++;
                    continue;
                }
                if ( '' === trim( (string) $current ) ) {
                    update_post_meta( $attachment_id, '_be_schema_creator_name', $creator );
                    update_post_meta( $attachment_id, '_be_schema_creator_type', $creator_type );
                    update_post_meta( $attachment_id, '_be_schema_creator_enabled', '1' );
                    if ( $creator_url ) {
                        update_post_meta( $attachment_id, '_be_schema_creator_url', $creator_url );
                        update_post_meta( $attachment_id, '_be_schema_creator_url_enabled', '1' );
                    } else {
                        delete_post_meta( $attachment_id, '_be_schema_creator_url' );
                        update_post_meta( $attachment_id, '_be_schema_creator_url_enabled', '0' );
                    }
                    $updated++;
                } else {
                    $skipped++;
                }
            }
        }

        wp_send_json_success(
            array(
                'updated' => $updated,
                'skipped' => $skipped,
                'message' => sprintf(
                    /* translators: 1: count updated, 2: count skipped */
                    __( 'Updated %1$d images; skipped %2$d already set.', 'beseo' ),
                    $updated,
                    $skipped
                ),
            )
        );
    }
);

// AJAX: Overwrite global author onto images without overrides.
add_action(
    'wp_ajax_be_schema_overwrite_creator_globals',
    function() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'beseo' ) ), 403 );
        }

        $nonce = isset( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : '';
        if ( ! wp_verify_nonce( $nonce, 'be_schema_overwrite_creator_globals' ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'beseo' ) ), 400 );
        }

        $mode         = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'website';
        $mode         = in_array( $mode, array( 'website', 'override' ), true ) ? $mode : 'website';
        $creator      = isset( $_POST['creator'] ) ? sanitize_text_field( wp_unslash( $_POST['creator'] ) ) : '';
        $creator_type = isset( $_POST['creator_type'] ) ? sanitize_text_field( wp_unslash( $_POST['creator_type'] ) ) : 'Person';
        $creator_type = in_array( $creator_type, array( 'Person', 'Organisation' ), true ) ? $creator_type : 'Person';
        $creator_url  = '';

        if ( 'website' === $mode ) {
            $settings = be_schema_engine_get_settings();
            $entities = be_schema_get_site_entities();
            $identity = isset( $settings['site_identity_mode'] ) ? $settings['site_identity_mode'] : 'publisher';
            $identity = in_array( $identity, array( 'person', 'organisation', 'publisher' ), true ) ? $identity : 'publisher';
            $key      = ( 'organisation' === $identity ) ? 'organization' : $identity;
            $entity   = isset( $entities[ $key ] ) ? $entities[ $key ] : null;

            if ( $entity && is_array( $entity ) && empty( $entity['name'] ) ) {
                $entity = isset( $entities['organization'] ) ? $entities['organization'] : ( $entities['person'] ?? $entity );
            }

            if ( $entity && is_array( $entity ) ) {
                $creator = isset( $entity['name'] ) ? $entity['name'] : '';
                $type_raw = isset( $entity['@type'] ) ? $entity['@type'] : '';
                if ( 'Organization' === $type_raw ) {
                    $creator_type = 'Organisation';
                } elseif ( 'Person' === $type_raw ) {
                    $creator_type = 'Person';
                }
                if ( ! empty( $entity['url'] ) ) {
                    $creator_url = $entity['url'];
                }
            }
        } elseif ( 'override' === $mode ) {
            $settings = be_schema_engine_get_settings();
            if ( ! empty( $settings['global_author_url'] ) ) {
                $creator_url = $settings['global_author_url'];
            }
        }

        if ( '' === $creator ) {
            wp_send_json_error( array( 'message' => __( 'Global author is empty.', 'beseo' ) ), 400 );
        }

        $query = new WP_Query(
            array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_mime_type' => 'image',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            )
        );

        $updated = 0;
        $skipped = 0;

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $attachment_id ) {
                $current = get_post_meta( $attachment_id, '_be_schema_creator_name', true );
                $enabled_flag = get_post_meta( $attachment_id, '_be_schema_creator_enabled', true );
                $has_override = ( '1' === $enabled_flag ) || ( '' === $enabled_flag && '' !== trim( (string) $current ) );
                if ( $has_override ) {
                    $skipped++;
                    continue;
                }

                update_post_meta( $attachment_id, '_be_schema_creator_name', $creator );
                update_post_meta( $attachment_id, '_be_schema_creator_type', $creator_type );
                update_post_meta( $attachment_id, '_be_schema_creator_enabled', '1' );
                if ( $creator_url ) {
                    update_post_meta( $attachment_id, '_be_schema_creator_url', $creator_url );
                    update_post_meta( $attachment_id, '_be_schema_creator_url_enabled', '1' );
                } else {
                    delete_post_meta( $attachment_id, '_be_schema_creator_url' );
                    update_post_meta( $attachment_id, '_be_schema_creator_url_enabled', '0' );
                }
                $updated++;
            }
        }

        wp_send_json_success(
            array(
                'message' => sprintf(
                    /* translators: 1: updated count, 2: skipped count */
                    __( 'Updated %1$d images. Skipped %2$d with overrides.', 'beseo' ),
                    $updated,
                    $skipped
                ),
                'updated' => $updated,
                'skipped' => $skipped,
            )
        );
    }
);

if ( ! function_exists( 'be_schema_admin_is_disabled_for_post' ) ) {
    function be_schema_admin_is_disabled_for_post( $post_id ) {
        $post_id = (int) $post_id;
        if ( $post_id <= 0 ) {
            return true;
        }

        $disable_meta = get_post_meta( $post_id, '_be_schema_disable', true );
        if ( (string) $disable_meta === '1' ) {
            return true;
        }

        $page_settings = get_post_meta( $post_id, '_elementor_page_settings', true );
        if ( empty( $page_settings ) || ! is_array( $page_settings ) ) {
            return true;
        }

        $enable_page = isset( $page_settings['be_schema_enable_page'] ) ? $page_settings['be_schema_enable_page'] : '';
        if ( $enable_page !== 'yes' ) {
            return true;
        }

        return false;
    }
}

if ( ! function_exists( 'be_schema_admin_build_breadcrumb_node' ) ) {
    function be_schema_admin_build_breadcrumb_node( $post ) {
        if ( ! $post instanceof WP_Post ) {
            return null;
        }

        $items = array();
        $home_url  = home_url( '/' );
        $home_name = get_bloginfo( 'name', 'display' );

        $items[] = array(
            'name' => $home_name,
            'url'  => $home_url,
        );

        $ancestors = array_reverse( get_post_ancestors( $post ) );
        foreach ( $ancestors as $ancestor_id ) {
            $items[] = array(
                'name' => get_the_title( $ancestor_id ),
                'url'  => get_permalink( $ancestor_id ),
            );
        }

        if ( 'post' === $post->post_type ) {
            $cats = get_the_category( $post->ID );
            if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
                $primary = $cats[0];
                $cat_chain = array_reverse( get_ancestors( $primary->term_id, 'category' ) );
                foreach ( $cat_chain as $cat_id ) {
                    $term = get_term( $cat_id, 'category' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $items[] = array(
                            'name' => $term->name,
                            'url'  => get_term_link( $term ),
                        );
                    }
                }
                $items[] = array(
                    'name' => $primary->name,
                    'url'  => get_term_link( $primary ),
                );
            }
        }

        $items[] = array(
            'name' => get_the_title( $post ),
            'url'  => get_permalink( $post ),
        );

        $item_list = array();
        $position  = 1;
        foreach ( $items as $item ) {
            if ( empty( $item['name'] ) || empty( $item['url'] ) ) {
                continue;
            }
            $item_list[] = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => $item['name'],
                'item'     => be_schema_breadcrumb_clean_url( $item['url'] ),
            );
            $position++;
        }

        if ( count( $item_list ) < 2 ) {
            return null;
        }

        return array(
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $item_list,
        );
    }
}

if ( ! function_exists( 'be_schema_admin_dedupe_graph_nodes' ) ) {
    function be_schema_admin_dedupe_graph_nodes( array $nodes ) {
        $deduped = array();
        $seen    = array();

        foreach ( $nodes as $node ) {
            if ( empty( $node ) || ! is_array( $node ) ) {
                continue;
            }
            $id  = isset( $node['@id'] ) ? (string) $node['@id'] : '';
            $key = $id ? $id : md5( wp_json_encode( $node ) );
            if ( isset( $seen[ $key ] ) ) {
                continue;
            }
            $seen[ $key ] = true;
            $deduped[]    = $node;
        }

        return $deduped;
    }
}

if ( ! function_exists( 'be_schema_admin_resolve_preview_target' ) ) {
    function be_schema_admin_resolve_preview_target( $raw_target ) {
        $raw_target = trim( (string) $raw_target );
        $target     = array(
            'post_id' => 0,
            'url'     => '',
            'context' => 'post',
        );

        if ( '' === $raw_target ) {
            return $target;
        }

        if ( is_numeric( $raw_target ) ) {
            $target['post_id'] = absint( $raw_target );
            if ( $target['post_id'] ) {
                $target['url'] = get_permalink( $target['post_id'] );
                return $target;
            }
        }

        $maybe_url = esc_url_raw( $raw_target );
        if ( $maybe_url ) {
            $target['url'] = $maybe_url;
            $target['post_id'] = url_to_postid( $maybe_url );
        }

        $home_url = trailingslashit( home_url( '/' ) );
        $is_home  = ( empty( $target['post_id'] ) ) && ( trailingslashit( $raw_target ) === $home_url || trailingslashit( $target['url'] ) === $home_url || strtolower( $raw_target ) === 'home' );

        if ( $is_home ) {
            $front_page_id = (int) get_option( 'page_on_front' );
            if ( $front_page_id ) {
                $target['post_id'] = $front_page_id;
                $target['url']     = get_permalink( $front_page_id );
                $target['context'] = 'front';
            } else {
                $target['context'] = 'home';
                $target['url']     = $home_url;
            }
        }

        return $target;
    }
}

if ( ! function_exists( 'be_schema_admin_build_preview_graph' ) ) {
    function be_schema_admin_build_preview_graph( array $target ) {
        $warnings = array();

        if ( function_exists( 'be_schema_globally_disabled' ) && be_schema_globally_disabled() ) {
            return array(
                'message' => __( 'Schema is disabled globally.', 'beseo' ),
            );
        }

        $graph_nodes = function_exists( 'be_schema_get_site_entity_graph_nodes' ) ? be_schema_get_site_entity_graph_nodes() : array();

        if ( 'home' === $target['context'] && empty( $target['post_id'] ) ) {
            $graph_nodes[] = be_schema_build_webpage_node(
                0,
                'WebPage',
                array(
                    'use_home_url' => true,
                    'id_suffix'    => 'homepage',
                )
            );

            $graph_nodes = be_schema_admin_dedupe_graph_nodes( $graph_nodes );

            return array(
                'graph'   => array(
                    '@context' => 'https://schema.org',
                    '@graph'   => $graph_nodes,
                ),
                'target'  => array(
                    'url'   => home_url( '/' ),
                    'title' => get_bloginfo( 'name', 'display' ),
                ),
                'warnings' => $warnings,
            );
        }

        $post_id = isset( $target['post_id'] ) ? (int) $target['post_id'] : 0;
        $post    = $post_id ? get_post( $post_id ) : null;

        if ( ! $post ) {
            return array(
                'message' => __( 'No matching page found for that target.', 'beseo' ),
            );
        }

        $allowed = apply_filters( 'be_schema_allow_post_type', true, $post->post_type );
        if ( ! $allowed ) {
            return array(
                'message' => __( 'Schema is not enabled for this post type.', 'beseo' ),
            );
        }

        if ( be_schema_admin_is_disabled_for_post( $post_id ) ) {
            return array(
                'message' => __( 'Schema is disabled for this page by per-page settings.', 'beseo' ),
            );
        }

        $front_page_id = (int) get_option( 'page_on_front' );
        $is_front_page = ( $front_page_id && $post_id === $front_page_id );
        if ( $is_front_page ) {
            $graph_nodes[] = be_schema_build_webpage_node(
                $post_id,
                'WebPage',
                array(
                    'use_home_url' => true,
                    'id_suffix'    => 'homepage',
                )
            );
        }

        $page_type = function_exists( 'be_schema_get_elementor_page_type' ) ? be_schema_get_elementor_page_type( $post_id ) : '';
        $type_map  = array(
            'contact'                 => 'ContactPage',
            'about'                   => 'AboutPage',
            'privacy-policy'          => 'PrivacyPolicy',
            'accessibility-statement' => 'WebPage',
        );
        $is_special_page = ( $page_type && isset( $type_map[ $page_type ] ) );
        if ( $is_special_page ) {
            $graph_nodes[] = be_schema_build_webpage_node(
                $post_id,
                $type_map[ $page_type ],
                array(
                    'use_home_url' => false,
                    'id_suffix'    => 'page-' . $post_id,
                )
            );
        }

        if ( ! $is_front_page && ! $is_special_page ) {
            $graph_nodes[] = be_schema_build_post_webpage_node( $post );
            $graph_nodes[] = be_schema_build_blogposting_node( $post );
        }

        $faq_nodes = be_schema_build_faq_schema( $post );
        if ( $faq_nodes ) {
            $graph_nodes = array_merge( $graph_nodes, $faq_nodes );
        }

        $howto_nodes = be_schema_build_howto_schema( $post );
        if ( $howto_nodes ) {
            $graph_nodes = array_merge( $graph_nodes, $howto_nodes );
        }

        $include_breadcrumbs = ! ( $front_page_id && $post_id === $front_page_id );
        if ( $include_breadcrumbs ) {
            $breadcrumb = be_schema_admin_build_breadcrumb_node( $post );
            if ( $breadcrumb ) {
                $graph_nodes[] = $breadcrumb;
            }
        }

        if ( function_exists( 'be_schema_elementor_disabled' ) && be_schema_elementor_disabled() ) {
            $warnings[] = __( 'Elementor schema is disabled; widget nodes were skipped.', 'beseo' );
        } elseif ( function_exists( 'be_schema_elementor_get_nodes_for_post' ) ) {
            $elementor_nodes = be_schema_elementor_get_nodes_for_post( $post_id );
            if ( ! empty( $elementor_nodes ) ) {
                $graph_nodes = array_merge( $graph_nodes, $elementor_nodes );
            }
        }

        $graph_nodes = be_schema_admin_dedupe_graph_nodes( $graph_nodes );

        return array(
            'graph'   => array(
                '@context' => 'https://schema.org',
                '@graph'   => $graph_nodes,
            ),
            'target'  => array(
                'id'    => $post_id,
                'url'   => get_permalink( $post_id ),
                'title' => get_the_title( $post ),
                'type'  => $post->post_type,
            ),
            'warnings' => $warnings,
        );
    }
}

if ( ! function_exists( 'be_schema_preview_graph_ajax' ) ) {
    function be_schema_preview_graph_ajax() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'beseo' ) ), 403 );
        }

        check_ajax_referer( 'be_schema_preview_graph', 'nonce' );

        $raw_target = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '';
        if ( '' === $raw_target ) {
            wp_send_json_error( array( 'message' => __( 'Enter a URL or post ID to preview.', 'beseo' ) ) );
        }

        $target = be_schema_admin_resolve_preview_target( $raw_target );
        if ( empty( $target['post_id'] ) && empty( $target['url'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No matching page found for that target.', 'beseo' ) ) );
        }

        $result = be_schema_admin_build_preview_graph( $target );
        if ( isset( $result['message'] ) && empty( $result['graph'] ) ) {
            wp_send_json_success(
                array(
                    'message' => $result['message'],
                    'target'  => isset( $result['target'] ) ? $result['target'] : array(),
                    'warnings' => isset( $result['warnings'] ) ? $result['warnings'] : array(),
                )
            );
        }

        wp_send_json_success( $result );
    }
}
add_action( 'wp_ajax_be_schema_preview_graph', 'be_schema_preview_graph_ajax' );
