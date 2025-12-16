<?php
/**
 * Social Media Admin Page
 *
 * Submenu: BE SEO → Social Media
 *
 * Tabs:
 *  - Dashboard (global fallback image + tiny image summary)
 *  - Platforms (navigation for per-network settings)
 *  - Facebook (overview toggle/status + FB page, FB default image, app id, notes, tools)
 *  - Twitter (overview toggle/status + handle, card type, Twitter default image, notes, tools)
 *  - Instagram (placeholder)
 *  - TicTok (placeholder)
 *  - LinkedIn (placeholder)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Save BE Social Media settings.
 *
 * Option name: be_schema_social_settings
 */
if ( ! function_exists( 'be_schema_social_validate_url_field' ) ) {
    function be_schema_social_validate_url_field( $raw_value, $label, &$errors ) {
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

function be_schema_engine_save_social_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if (
        ! isset( $_POST['be_schema_social_settings_nonce'] ) ||
        ! wp_verify_nonce( $_POST['be_schema_social_settings_nonce'], 'be_schema_engine_save_social_settings' )
    ) {
        return;
    }

    // Load current settings with defaults via helper when available.
    if ( function_exists( 'be_schema_social_get_settings' ) ) {
        $settings = be_schema_social_get_settings();
    } else {
        $settings = get_option( 'be_schema_social_settings', array() );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }
        if ( function_exists( 'be_schema_social_merge_defaults' ) ) {
            $settings = be_schema_social_merge_defaults( $settings );
        }
    }

    $validation_errors = array();

    // DASHBOARD TAB ------------------------------.

    // Global enables (collected from the Facebook/Twitter Overview panels; must align with core-social.php defaults).
    $settings['social_enable_og']      = isset( $_POST['be_schema_og_enabled'] ) ? '1' : '0';
    $settings['social_enable_twitter'] = isset( $_POST['be_schema_twitter_enabled'] ) ? '1' : '0';
    $settings['dry_run']               = isset( $_POST['be_schema_social_dry_run'] ) ? '1' : '0';
    $settings['twitter_dry_run']       = isset( $_POST['be_schema_twitter_dry_run'] ) ? '1' : '0';

    // Optional: keep legacy keys in sync if they existed before.
    $settings['og_enabled']      = $settings['social_enable_og'];
    $settings['twitter_enabled'] = $settings['social_enable_twitter'];
    $settings['enabled']         = ( '1' === $settings['social_enable_og'] || '1' === $settings['social_enable_twitter'] ) ? '1' : '0';

    // Re-merge defaults in case new keys were added in code (prevents notices).
    if ( function_exists( 'be_schema_social_merge_defaults' ) ) {
        $settings = be_schema_social_merge_defaults( $settings );
    }

    // Global default fallback image (must align with social_default_image).
    $settings['social_default_image'] = be_schema_social_validate_url_field(
        isset( $_POST['be_schema_global_default_image'] ) ? wp_unslash( $_POST['be_schema_global_default_image'] ) : '',
        __( 'Global Default Image', 'beseo' ),
        $validation_errors
    );
    $settings['global_default_image_alt'] = isset( $_POST['be_schema_global_default_image_alt'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_global_default_image_alt'] ) )
        : '';

    $settings['global_images_optional'] = isset( $_POST['be_schema_global_images_optional'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_global_images_optional'] ) )
        : '';

    $global_image_variants = array(
        'global_image_16_9'   => 'be_schema_global_image_16_9',
        'global_image_5_4'    => 'be_schema_global_image_5_4',
        'global_image_1_1'    => 'be_schema_global_image_1_1',
        'global_image_4_5'    => 'be_schema_global_image_4_5',
        'global_image_1_1_91' => 'be_schema_global_image_1_1_91',
        'global_image_9_16'   => 'be_schema_global_image_9_16',
    );

    foreach ( $global_image_variants as $settings_key => $field_name ) {
        $settings[ $settings_key ] = be_schema_social_validate_url_field(
            isset( $_POST[ $field_name ] ) ? wp_unslash( $_POST[ $field_name ] ) : '',
            __( 'Global Default Image', 'beseo' ),
            $validation_errors
        );
    }

    // Optional legacy alias for older code, if any.
    $settings['global_default_image'] = $settings['social_default_image'];

    // FACEBOOK TAB ------------------------------.

    $settings['facebook_page_url'] = be_schema_social_validate_url_field(
        isset( $_POST['be_schema_facebook_page_url'] ) ? wp_unslash( $_POST['be_schema_facebook_page_url'] ) : '',
        __( 'Facebook Page URL', 'beseo' ),
        $validation_errors
    );

    $settings['facebook_default_image'] = be_schema_social_validate_url_field(
        isset( $_POST['be_schema_facebook_default_image'] ) ? wp_unslash( $_POST['be_schema_facebook_default_image'] ) : '',
        __( 'Default Facebook OG Image', 'beseo' ),
        $validation_errors
    );

    $settings['facebook_app_id'] = isset( $_POST['be_schema_facebook_app_id'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_facebook_app_id'] ) )
        : '';

    $settings['facebook_notes'] = isset( $_POST['be_schema_facebook_notes'] )
        ? wp_kses_post( wp_unslash( $_POST['be_schema_facebook_notes'] ) )
        : '';

    $settings['facebook_optional'] = isset( $_POST['be_schema_facebook_optional'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_facebook_optional'] ) )
        : '';

    $settings['facebook_optional_images'] = isset( $_POST['be_schema_facebook_optional_images'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_facebook_optional_images'] ) )
        : '';

    $settings['facebook_images_optional'] = isset( $_POST['be_schema_facebook_images_optional'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_facebook_images_optional'] ) )
        : '';

    $facebook_image_variants = array(
        'facebook_image_16_9'   => 'be_schema_facebook_image_16_9',
        'facebook_image_5_4'    => 'be_schema_facebook_image_5_4',
        'facebook_image_1_1'    => 'be_schema_facebook_image_1_1',
        'facebook_image_4_5'    => 'be_schema_facebook_image_4_5',
        'facebook_image_1_1_91' => 'be_schema_facebook_image_1_1_91',
        'facebook_image_9_16'   => 'be_schema_facebook_image_9_16',
    );

    foreach ( $facebook_image_variants as $settings_key => $field_name ) {
        $settings[ $settings_key ] = be_schema_social_validate_url_field(
            isset( $_POST[ $field_name ] ) ? wp_unslash( $_POST[ $field_name ] ) : '',
            __( 'Default Facebook OG Image', 'beseo' ),
            $validation_errors
        );
    }

    // TWITTER TAB -------------------------------.

    $settings['twitter_handle'] = isset( $_POST['be_schema_twitter_handle'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_twitter_handle'] ) )
        : '';

    $settings['twitter_card_type'] = isset( $_POST['be_schema_twitter_card_type'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_twitter_card_type'] ) )
        : 'summary_large_image';

    $settings['twitter_default_image'] = be_schema_social_validate_url_field(
        isset( $_POST['be_schema_twitter_default_image'] ) ? wp_unslash( $_POST['be_schema_twitter_default_image'] ) : '',
        __( 'Default Twitter Card Image', 'beseo' ),
        $validation_errors
    );
    $settings['twitter_default_image_alt'] = be_schema_social_validate_url_field(
        isset( $_POST['be_schema_twitter_default_image_alt'] ) ? wp_unslash( $_POST['be_schema_twitter_default_image_alt'] ) : '',
        __( 'Alternate Twitter Card Image', 'beseo' ),
        $validation_errors
    );

    $settings['twitter_notes'] = isset( $_POST['be_schema_twitter_notes'] )
        ? wp_kses_post( wp_unslash( $_POST['be_schema_twitter_notes'] ) )
        : '';

    $settings['twitter_optional'] = isset( $_POST['be_schema_twitter_optional'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_twitter_optional'] ) )
        : '';
    $settings['twitter_site']    = isset( $_POST['be_schema_twitter_site'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_twitter_site'] ) ) : '';
    $settings['twitter_creator'] = isset( $_POST['be_schema_twitter_creator'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_twitter_creator'] ) ) : '';
    $settings['twitter_image_alt'] = isset( $_POST['be_schema_twitter_image_alt'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_twitter_image_alt'] ) ) : '';

    update_option( 'be_schema_social_settings', $settings );

    foreach ( $validation_errors as $message ) {
        add_settings_error( 'be_schema_social', 'be_schema_social_validation', $message, 'error' );
    }
}

/**
 * Render the Social Media admin page (BE SEO → Social Media).
 */
function be_schema_engine_render_social_media_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $current_page  = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    $is_platforms  = ( 'beseo-platforms' === $current_page );

    // Enqueue media for image pickers.
    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }
    // Enqueue shared optional field helper.
    wp_enqueue_script(
        'be-schema-optional-fields',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'includes/admin/js/be-optional-fields.js',
        array(),
        BE_SCHEMA_ENGINE_VERSION,
        true
    );
    wp_enqueue_script(
        'be-schema-twitter-handles',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'includes/admin/js/be-twitter-handles.js',
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

    // Save on POST.
    if ( isset( $_POST['be_schema_social_settings_submitted'] ) ) {
        be_schema_engine_save_social_settings();
    }

    // Settings snapshot from canonical helper.
    if ( function_exists( 'be_schema_social_get_settings' ) ) {
        $settings = be_schema_social_get_settings();
    } else {
        $settings = get_option( 'be_schema_social_settings', array() );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }
    }

    // Simple access helpers (read the same keys core-social.php uses).
    $og_enabled      = ! empty( $settings['social_enable_og'] ) && '1' === $settings['social_enable_og'];
    $twitter_enabled = ! empty( $settings['social_enable_twitter'] ) && '1' === $settings['social_enable_twitter'];
    $social_dry_run  = ! empty( $settings['dry_run'] ) && '1' === $settings['dry_run'];

    $global_default_image       = isset( $settings['social_default_image'] ) ? $settings['social_default_image'] : '';
    $global_default_image_alt   = isset( $settings['global_default_image_alt'] ) ? $settings['global_default_image_alt'] : '';
    $global_images_optional_raw = isset( $settings['global_images_optional'] ) ? $settings['global_images_optional'] : '';
    $global_image_16_9          = isset( $settings['global_image_16_9'] ) ? $settings['global_image_16_9'] : '';
    $global_image_5_4           = isset( $settings['global_image_5_4'] ) ? $settings['global_image_5_4'] : '';
    $global_image_1_1           = isset( $settings['global_image_1_1'] ) ? $settings['global_image_1_1'] : '';
    $global_image_4_5           = isset( $settings['global_image_4_5'] ) ? $settings['global_image_4_5'] : '';
    $global_image_1_1_91        = isset( $settings['global_image_1_1_91'] ) ? $settings['global_image_1_1_91'] : '';
    $global_image_9_16          = isset( $settings['global_image_9_16'] ) ? $settings['global_image_9_16'] : '';
    $facebook_page_url      = isset( $settings['facebook_page_url'] ) ? $settings['facebook_page_url'] : '';
    $facebook_default_image = isset( $settings['facebook_default_image'] ) ? $settings['facebook_default_image'] : '';
    $facebook_app_id        = isset( $settings['facebook_app_id'] ) ? $settings['facebook_app_id'] : '';
    $facebook_notes         = isset( $settings['facebook_notes'] ) ? $settings['facebook_notes'] : '';
    $facebook_optional_raw  = isset( $settings['facebook_optional'] ) ? $settings['facebook_optional'] : '';
    $facebook_optional_images_raw = isset( $settings['facebook_optional_images'] ) ? $settings['facebook_optional_images'] : '';
    $facebook_images_optional_raw = isset( $settings['facebook_images_optional'] ) ? $settings['facebook_images_optional'] : '';

    $twitter_handle        = isset( $settings['twitter_handle'] ) ? $settings['twitter_handle'] : '';
    $twitter_site          = isset( $settings['twitter_site'] ) ? $settings['twitter_site'] : '';
    $twitter_creator       = isset( $settings['twitter_creator'] ) ? $settings['twitter_creator'] : '';
    $twitter_image_alt     = isset( $settings['twitter_image_alt'] ) ? $settings['twitter_image_alt'] : '';
    $twitter_card_type     = isset( $settings['twitter_card_type'] ) ? $settings['twitter_card_type'] : 'summary_large_image';
    $twitter_default_image = isset( $settings['twitter_default_image'] ) ? $settings['twitter_default_image'] : '';
    $twitter_default_image_alt = isset( $settings['twitter_default_image_alt'] ) ? $settings['twitter_default_image_alt'] : '';
    $twitter_dry_run       = ! empty( $settings['twitter_dry_run'] ) && '1' === (string) $settings['twitter_dry_run'];
    $twitter_notes         = isset( $settings['twitter_notes'] ) ? $settings['twitter_notes'] : '';
    $twitter_optional_raw  = isset( $settings['twitter_optional'] ) ? $settings['twitter_optional'] : '';
    $facebook_optional_props = array();
    if ( ! empty( $facebook_optional_raw ) ) {
        $facebook_optional_props = array_filter(
            array_map(
                'trim',
                explode( ',', $facebook_optional_raw )
            )
        );
    }
    // Legacy compatibility: default image now lives under Optional Images.
    $facebook_optional_props = array_values( array_diff( $facebook_optional_props, array( 'facebook_default_image' ) ) );
    if ( ! empty( $facebook_page_url ) && ! in_array( 'facebook_page_url', $facebook_optional_props, true ) ) {
        $facebook_optional_props[] = 'facebook_page_url';
    }
    if ( ! empty( $facebook_app_id ) && ! in_array( 'facebook_app_id', $facebook_optional_props, true ) ) {
        $facebook_optional_props[] = 'facebook_app_id';
    }
    if ( ! empty( $facebook_notes ) && ! in_array( 'facebook_notes', $facebook_optional_props, true ) ) {
        $facebook_optional_props[] = 'facebook_notes';
    }
    $facebook_optional_serialized = implode( ',', $facebook_optional_props );

    // Default image is now a dedicated field; optional images list unused.
    $facebook_optional_image_props      = array();
    $facebook_optional_images_serialized = '';

    $facebook_images_optional_props = array();
    if ( ! empty( $facebook_images_optional_raw ) ) {
        $facebook_images_optional_props = array_filter(
            array_map(
                'trim',
                explode( ',', $facebook_images_optional_raw )
            )
        );
    }

    $facebook_image_flags = array(
        'image_16_9'   => isset( $settings['facebook_image_16_9'] ) ? $settings['facebook_image_16_9'] : '',
        'image_5_4'    => isset( $settings['facebook_image_5_4'] ) ? $settings['facebook_image_5_4'] : '',
        'image_1_1'    => isset( $settings['facebook_image_1_1'] ) ? $settings['facebook_image_1_1'] : '',
        'image_4_5'    => isset( $settings['facebook_image_4_5'] ) ? $settings['facebook_image_4_5'] : '',
        'image_1_1_91' => isset( $settings['facebook_image_1_1_91'] ) ? $settings['facebook_image_1_1_91'] : '',
        'image_9_16'   => isset( $settings['facebook_image_9_16'] ) ? $settings['facebook_image_9_16'] : '',
    );

    foreach ( $facebook_image_flags as $key => $value ) {
        if ( ! empty( $value ) && ! in_array( $key, $facebook_images_optional_props, true ) ) {
            $facebook_images_optional_props[] = $key;
        }
    }

    $facebook_images_optional_serialized = implode( ',', $facebook_images_optional_props );

    $global_images_optional_props = array();
    if ( ! empty( $global_images_optional_raw ) ) {
        $global_images_optional_props = array_filter(
            array_map(
                'trim',
                explode( ',', $global_images_optional_raw )
            )
        );
    }

    $global_image_flags = array(
        'image_16_9'   => $global_image_16_9,
        'image_5_4'    => $global_image_5_4,
        'image_1_1'    => $global_image_1_1,
        'image_4_5'    => $global_image_4_5,
        'image_1_1_91' => $global_image_1_1_91,
        'image_9_16'   => $global_image_9_16,
    );

    foreach ( $global_image_flags as $key => $value ) {
        if ( ! empty( $value ) && ! in_array( $key, $global_images_optional_props, true ) ) {
            $global_images_optional_props[] = $key;
        }
    }

    $global_images_optional_serialized = implode( ',', $global_images_optional_props );
    $facebook_optional_images_serialized = implode( ',', $facebook_optional_image_props );

    $twitter_optional_props = array();
    if ( ! empty( $twitter_optional_raw ) ) {
        $twitter_optional_props = array_filter(
            array_map(
                'trim',
                explode( ',', $twitter_optional_raw )
            )
        );
    }
    // Always include the new keys when values exist.
    if ( ! empty( $twitter_site ) && ! in_array( 'twitter_site', $twitter_optional_props, true ) ) {
        $twitter_optional_props[] = 'twitter_site';
    }
    if ( ! empty( $twitter_creator ) && ! in_array( 'twitter_creator', $twitter_optional_props, true ) ) {
        $twitter_optional_props[] = 'twitter_creator';
    }
    if ( ! empty( $twitter_image_alt ) && ! in_array( 'twitter_image_alt', $twitter_optional_props, true ) ) {
        $twitter_optional_props[] = 'twitter_image_alt';
    }
    $twitter_optional_serialized = implode( ',', $twitter_optional_props );

    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var cardRadios = document.querySelectorAll('input[name="be_schema_twitter_card_type"]');
            var largeInput = document.getElementById('be_schema_twitter_default_image');
            var largeButtons = document.querySelectorAll('[data-target-input="be_schema_twitter_default_image"]');
            var summaryInput = document.getElementById('be_schema_twitter_default_image_alt');
            var summaryButtons = document.querySelectorAll('[data-target-input="be_schema_twitter_default_image_alt"]');

            function setEnabled(el, enable) {
                if (!el) return;
                el.disabled = !enable;
            }
            function setButtonGroup(btns, enable) {
                btns.forEach(function (btn) { btn.disabled = !enable; });
            }
            function syncTwitterCardInputs() {
                var type = document.querySelector('input[name="be_schema_twitter_card_type"]:checked');
                var isLarge = type && type.value === 'summary_large_image';
                var isSummary = type && type.value === 'summary';
                setEnabled(largeInput, isLarge);
                setButtonGroup(largeButtons, isLarge);
                setEnabled(summaryInput, isSummary);
                setButtonGroup(summaryButtons, isSummary);
            }
            cardRadios.forEach(function (radio) {
                radio.addEventListener('change', syncTwitterCardInputs);
            });
            syncTwitterCardInputs();
        });
    </script>
    <div class="wrap beseo-wrap beseo-social-wrap">
        <h1><?php esc_html_e( 'BE SEO – Social Media', 'beseo' ); ?></h1>

        <?php settings_errors( 'be_schema_social' ); ?>

        <p class="description">
            <?php esc_html_e(
                'Configure OpenGraph and Twitter Card defaults. This module controls social meta only; it does not change JSON-LD schema or sameAs arrays.',
                'beseo'
            ); ?>
        </p>

        <style>
            .be-schema-help-accent {
                color: #00a0d2;
            }
            .be-schema-social-tabs {
                margin-top: 20px;
            }

            .be-schema-social-tabs-nav {
                display: flex;
                gap: 8px;
                border-bottom: 1px solid #ccd0d4;
                margin-bottom: 0;
                padding-left: 0;
                list-style: none;
            }

            .be-schema-social-tabs-nav a {
                display: inline-block;
                padding: 8px 14px;
                text-decoration: none;
                border: 1px solid transparent;
                border-bottom: none;
                background: #f3f4f5;
                color: #555;
                cursor: pointer;
            }

            .be-schema-social-tabs-nav a:hover {
                background: #e5e5e5;
            }

            .be-schema-social-tabs-nav a.be-schema-social-tab-active {
                background: #fff;
                border-color: #ccd0d4;
                border-bottom: 1px solid #fff;
                color: #000;
                font-weight: 600;
            }

            .be-schema-social-tab-panel {
                display: none;
                padding: 16px 12px 12px;
                border: 1px solid #ccd0d4;
                border-top: none;
                background: #fff;
            }

            .be-schema-social-tab-panel-active {
                display: block;
            }

            .be-schema-social-description {
                max-width: 760px;
            }

            .be-schema-social-status-pill {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 999px;
                font-size: 11px;
                margin-right: 6px;
                background: #e5f5e0;
                color: #13610b;
            }

            .be-schema-social-status-pill.off {
                background: #fbeaea;
                color: #8a1f11;
            }

            #be-schema-facebook-content .be-schema-optional-row th,
            #be-schema-facebook-content .be-schema-optional-row td,
            #be-schema-twitter-content .be-schema-optional-row th,
            #be-schema-twitter-content .be-schema-optional-row td {
                vertical-align: middle;
            }

            .be-schema-optional-controls {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
                margin: 8px 0 12px;
                padding-left: 0;
            }

            .be-schema-optional-controls select {
                min-width: 220px;
            }

            .be-schema-optional-controls.is-disabled {
                opacity: 0.55;
            }

            .be-schema-optional-fields .be-schema-optional-field {
                border-left: 0;
                padding-left: 0;
                margin: 0 0 16px;
                position: relative;
            }

            .be-schema-optional-label {
                display: inline-block;
                margin-left: 4px;
                font-weight: 600;
            }

            .be-schema-optional-field.is-hidden {
                display: none;
            }

            .be-schema-optional-field .be-schema-optional-remove {
                position: absolute;
                left: -64px;
                top: 0;
                margin: 0;
                display: inline-flex;
                align-items: center;
            }

            .be-schema-image-field {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 10px;
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

            .be-schema-social-mini-summary {
                margin-top: 20px;
                padding: 12px 10px;
                border-left: 4px solid #ccd0d4;
                background: #f8f9fa;
                max-width: 760px;
            }

            .be-schema-social-mini-summary h3 {
                margin-top: 0;
                margin-bottom: 8px;
            }

            .be-schema-social-mini-summary ul {
                margin: 0 0 8px 18px;
                padding: 0;
                list-style: disc;
            }

            .be-schema-social-mini-summary li {
                margin-bottom: 2px;
            }

            .be-schema-social-mini-summary-images {
                display: flex;
                flex-wrap: wrap;
                gap: 14px;
                margin-top: 6px;
            }

            .be-schema-social-mini-summary-images > div {
                font-size: 12px;
            }

            .be-schema-social-mini-summary-images strong {
                display: block;
                margin-bottom: 2px;
            }

            .be-schema-social-layout {
                display: flex;
                gap: 24px;
                margin-top: 12px;
            }

            .be-schema-social-nav {
                width: 200px;
                border-right: 1px solid #ccd0d4;
            }

            .be-schema-social-nav ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .be-schema-social-nav li {
                margin: 0;
                padding: 0;
            }

            .be-schema-social-nav a {
                display: block;
                padding: 8px 10px;
                text-decoration: none;
                color: #444;
                cursor: pointer;
            }

            .be-schema-social-nav a:hover {
                background: #f1f1f1;
            }

            .be-schema-social-nav a.be-schema-social-subtab-active {
                background: #2271b1;
                color: #fff;
            }

            .be-schema-social-panels {
                flex: 1;
            }

            .be-schema-social-panel {
                display: none;
            }

            .be-schema-social-panel-active {
                display: block;
            }

            .be-schema-social-section {
                border: 1px solid #ccd0d4;
                border-radius: 6px;
                padding: 15px;
                margin-bottom: 16px;
                background: #f9fafb;
                color: #111;
            }
            .be-schema-social-panel-disabled {
                opacity: 0.55;
                pointer-events: none;
            }

            .be-schema-social-section-title {
                display: block;
                margin: -15px -15px 12px;
                padding: 12px 15px;
                background: #e1e4e8;
                color: #111;
            }
        </style>

        <p>
            <span class="be-schema-social-status-pill <?php echo $og_enabled ? '' : 'off'; ?>">
                <?php echo $og_enabled ? esc_html__( 'OpenGraph: ON', 'beseo' ) : esc_html__( 'OpenGraph: OFF', 'beseo' ); ?>
            </span>
            <span class="be-schema-social-status-pill <?php echo $twitter_enabled ? '' : 'off'; ?>">
                <?php echo $twitter_enabled ? esc_html__( 'Twitter Cards: ON', 'beseo' ) : esc_html__( 'Twitter Cards: OFF', 'beseo' ); ?>
            </span>
        </p>

        <form method="post">
            <?php wp_nonce_field( 'be_schema_engine_save_social_settings', 'be_schema_social_settings_nonce' ); ?>
            <input type="hidden" name="be_schema_social_settings_submitted" value="1" />

            <div class="be-schema-social-tabs">
                <ul class="be-schema-social-tabs-nav">
                    <li>
                        <a href="#be-schema-social-tab-settings"
                           class="be-schema-social-tab-link be-schema-social-tab-active"
                           data-social-tab="settings">
                            <?php esc_html_e( 'Dashboard', 'beseo' ); ?>
                        </a>
                    </li>
                    <?php if ( ! $is_platforms ) : ?>
                        <li>
                            <a href="#be-schema-social-tab-content"
                               class="be-schema-social-tab-link"
                               data-social-tab="content">
                                <?php esc_html_e( 'Open Graph', 'beseo' ); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#be-schema-social-tab-platforms"
                               class="be-schema-social-tab-link"
                               data-social-tab="platforms">
                                <?php esc_html_e( 'Platforms', 'beseo' ); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ( $is_platforms ) : ?>
                        <li>
                            <a href="#be-schema-social-tab-facebook"
                               class="be-schema-social-tab-link"
                               data-social-tab="facebook">
                                <?php esc_html_e( 'Facebook', 'beseo' ); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#be-schema-social-tab-twitter"
                               class="be-schema-social-tab-link"
                               data-social-tab="twitter">
                                <?php esc_html_e( 'Twitter', 'beseo' ); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#be-schema-social-tab-instagram"
                               class="be-schema-social-tab-link"
                               data-social-tab="instagram">
                                <?php esc_html_e( 'Instagram', 'beseo' ); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#be-schema-social-tab-tictok"
                               class="be-schema-social-tab-link"
                               data-social-tab="tictok">
                                <?php esc_html_e( 'TikTok', 'beseo' ); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#be-schema-social-tab-linkedin"
                               class="be-schema-social-tab-link"
                               data-social-tab="linkedin">
                                <?php esc_html_e( 'LinkedIn', 'beseo' ); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- DASHBOARD TAB -->
                <div id="be-schema-social-tab-settings"
                     class="be-schema-social-tab-panel be-schema-social-tab-panel-active">
                    <h2><?php esc_html_e( 'Social Dashboard', 'beseo' ); ?></h2>

                    <?php if ( $is_platforms ) : ?>
                    <div class="be-schema-social-section">
                        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Overview', 'beseo' ); ?></h4>
                        <p class="description be-schema-social-description">
                            <?php esc_html_e(
                                'Global default images now live in Social Media → Open Graph.',
                                'beseo'
                            ); ?>
                        </p>
                    </div>
                    <div class="be-schema-social-section">
                        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Platforms', 'beseo' ); ?></h4>
                        <div class="be-schema-social-layout">
                            <div class="be-schema-social-nav">
                                <ul>
                                    <li>
                                        <a href="#be-schema-social-tab-facebook"
                                           class="be-schema-platforms-jump"
                                           data-target-tab="facebook">
                                            <?php esc_html_e( 'Facebook', 'beseo' ); ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#be-schema-social-tab-instagram"
                                           class="be-schema-platforms-jump"
                                           data-target-tab="instagram">
                                            <?php esc_html_e( 'Instagram', 'beseo' ); ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#be-schema-social-tab-linkedin"
                                           class="be-schema-platforms-jump"
                                           data-target-tab="linkedin">
                                            <?php esc_html_e( 'LinkedIn', 'beseo' ); ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#be-schema-social-tab-tictok"
                                           class="be-schema-platforms-jump"
                                           data-target-tab="tictok">
                                            <?php esc_html_e( 'TikTok', 'beseo' ); ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#be-schema-social-tab-twitter"
                                           class="be-schema-platforms-jump"
                                           data-target-tab="twitter">
                                            <?php esc_html_e( 'Twitter', 'beseo' ); ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="be-schema-social-panels">
                                <p class="description be-schema-social-description" style="margin-top:0;">
                                    <?php esc_html_e(
                                        'Jump to platform-specific settings to manage handles, defaults, and debugging links.',
                                        'beseo'
                                    ); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="be-schema-social-section">
                        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Last Social Debug Snapshot', 'beseo' ); ?></h4>
                        <?php
                        $last_social = get_transient( 'be_social_last_debug' );
                        if ( $last_social && isset( $last_social['snapshot'] ) ) :
                            $last_social_time = isset( $last_social['time'] ) ? (int) $last_social['time'] : 0;
                            ?>
                            <p class="description be-schema-social-description" style="margin-top:0;">
                                <?php
                                if ( $last_social_time ) {
                                    /* translators: %s: human time diff */
                                    printf( esc_html__( 'Captured %s ago.', 'beseo' ), esc_html( human_time_diff( $last_social_time, time() ) ) );
                                } else {
                                    esc_html_e( 'Captured recently.', 'beseo' );
                                }
                                ?>
                            </p>
                            <pre class="be-schema-settings-snapshot-pre" style="max-height: 260px; overflow:auto;"><?php echo esc_html( wp_json_encode( $last_social['snapshot'], JSON_PRETTY_PRINT ) ); ?></pre>
                        <?php else : ?>
                            <p><em><?php esc_html_e( 'No social debug snapshot found. Enable debug to capture the next one.', 'beseo' ); ?></em></p>
                        <?php endif; ?>
                    </div>

                </div>

                <?php if ( ! $is_platforms ) : ?>
                <div id="be-schema-social-tab-content" class="be-schema-social-tab-panel">
                    <h2><?php esc_html_e( 'Open Graph', 'beseo' ); ?></h2>

                    <div class="be-schema-social-section">
                        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Global Defaults', 'beseo' ); ?></h4>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <?php esc_html_e( 'Global Default Image', 'beseo' ); ?>
                                    </th>
                                    <td>
                                        <div class="be-schema-image-field">
                                            <input type="text"
                                                   id="be_schema_global_default_image"
                                                   name="be_schema_global_default_image"
                                                   value="<?php echo esc_url( $global_default_image ); ?>"
                                                   class="regular-text" />
                                            <button type="button"
                                                    class="button be-schema-image-select"
                                                    data-target-input="be_schema_global_default_image"
                                                    data-target-preview="be_schema_global_default_image_preview">
                                                <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                            </button>
                                            <button type="button"
                                                    class="button be-schema-image-clear"
                                                    data-target-input="be_schema_global_default_image"
                                                    data-target-preview="be_schema_global_default_image_preview">
                                                <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                            </button>
                                        </div>
                                        <p class="description be-schema-social-description">
                                            <?php esc_html_e(
                                                'Used as a final fallback when there is no featured image and no platform-specific default image. Recommended specs | Aspect Ratio: 1.91:1 | Resolution: 1200x630 | Formats: JPEG or PNG.',
                                                'beseo'
                                            ); ?>
                                        </p>
                                        <div class="be-schema-image-field" style="margin-top: 12px;">
                                            <label for="be_schema_global_image_1_1" class="screen-reader-text"><?php esc_html_e( 'Square Default Image (1:1 @ 1200x1200)', 'beseo' ); ?></label>
                                            <input type="text"
                                                   id="be_schema_global_image_1_1"
                                                   name="be_schema_global_image_1_1"
                                                   value="<?php echo esc_url( $global_image_1_1 ); ?>"
                                                   class="regular-text" />
                                            <button type="button"
                                                    class="button be-schema-image-select"
                                                    data-target-input="be_schema_global_image_1_1"
                                                    data-target-preview="be_schema_global_image_1_1_preview">
                                                <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                            </button>
                                            <button type="button"
                                                    class="button be-schema-image-clear"
                                                    data-target-input="be_schema_global_image_1_1"
                                                    data-target-preview="be_schema_global_image_1_1_preview">
                                                <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                            </button>
                                        </div>
                                        <p class="description be-schema-social-description">
                                            <?php esc_html_e(
                                                'Optional square Open Graph image for platforms that prefer 1:1. Recommended size: 1200x1200.',
                                                'beseo'
                                            ); ?>
                                        </p>
                                        <div id="be_schema_global_image_1_1_preview"
                                             class="be-schema-image-preview">
                                            <?php if ( $global_image_1_1 ) : ?>
                                                <img src="<?php echo esc_url( $global_image_1_1 ); ?>" alt="" />
                                            <?php endif; ?>
                                        </div>
                                        <div class="be-schema-optional-controls"
                                             data-optional-scope="global-default-images"
                                             data-optional-hidden="be_schema_global_images_optional"
                                             data-optional-singleton="image_16_9,image_5_4,image_4_5,image_1_1_91,image_9_16">
                                            <label class="screen-reader-text" for="be-schema-global-images-optional"><?php esc_html_e( 'Add global default image', 'beseo' ); ?></label>
                                            <select id="be-schema-global-images-optional" aria-label="<?php esc_attr_e( 'Add global default image', 'beseo' ); ?>">
                                                <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                <option value="image_1_91" disabled><?php esc_html_e( '1.91:1 @ 1200x630 (default)', 'beseo' ); ?></option>
                                                <option value="image_1_1" disabled><?php esc_html_e( '1:1 @ 1200x1200 (square)', 'beseo' ); ?></option>
                                                <option value="image_16_9"><?php esc_html_e( '16:9 @ 1200x675', 'beseo' ); ?></option>
                                                <option value="image_5_4"><?php esc_html_e( '5:4 @ 1350x1080', 'beseo' ); ?></option>
                                                <option value="image_4_5"><?php esc_html_e( '4:5 @ 1080x1350', 'beseo' ); ?></option>
                                                <option value="image_1_1_91"><?php esc_html_e( '1:1.91 @ 630x1200', 'beseo' ); ?></option>
                                                <option value="image_9_16"><?php esc_html_e( '9:16 @ 675x1200', 'beseo' ); ?></option>
                                            </select>
                                            <button type="button"
                                                    class="button be-schema-optional-add"
                                                    data-optional-add="global-default-images"
                                                    disabled>
                                                +
                                            </button>
                                            <input type="hidden" name="be_schema_global_images_optional" id="be_schema_global_images_optional" value="<?php echo esc_attr( $global_images_optional_serialized ); ?>" />
                                        </div>
                                        <div class="be-schema-optional-fields" id="be-schema-global-images-optional-fields">
                                            <div class="be-schema-optional-field<?php echo in_array( 'image_16_9', $global_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_16_9">
                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_16_9">−</button>
                                                <div class="be-schema-image-field">
                                                    <span class="be-schema-optional-label"><?php esc_html_e( '16:9 @ 1200x675', 'beseo' ); ?></span>
                                                    <label for="be_schema_global_image_16_9" class="screen-reader-text"><?php esc_html_e( '16:9 @ 1200x675', 'beseo' ); ?></label>
                                                    <input type="text"
                                                           id="be_schema_global_image_16_9"
                                                           name="be_schema_global_image_16_9"
                                                           value="<?php echo esc_url( $global_image_16_9 ); ?>"
                                                           class="regular-text" />
                                                    <button type="button"
                                                            class="button be-schema-image-select"
                                                            data-target-input="be_schema_global_image_16_9"
                                                            data-target-preview="be_schema_global_image_16_9_preview">
                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                    </button>
                                                    <button type="button"
                                                            class="button be-schema-image-clear"
                                                            data-target-input="be_schema_global_image_16_9"
                                                            data-target-preview="be_schema_global_image_16_9_preview">
                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                    </button>
                                                </div>
                                                <div id="be_schema_global_image_16_9_preview"
                                                     class="be-schema-image-preview">
                                                    <?php if ( $global_image_16_9 ) : ?>
                                                        <img src="<?php echo esc_url( $global_image_16_9 ); ?>" alt="" />
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="be-schema-optional-field<?php echo in_array( 'image_5_4', $global_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_5_4">
                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_5_4">−</button>
                                                <div class="be-schema-image-field">
                                                    <span class="be-schema-optional-label"><?php esc_html_e( '5:4 @ 1350x1080', 'beseo' ); ?></span>
                                                    <label for="be_schema_global_image_5_4" class="screen-reader-text"><?php esc_html_e( '5:4 @ 1350x1080', 'beseo' ); ?></label>
                                                    <input type="text"
                                                           id="be_schema_global_image_5_4"
                                                           name="be_schema_global_image_5_4"
                                                           value="<?php echo esc_url( $global_image_5_4 ); ?>"
                                                           class="regular-text" />
                                                    <button type="button"
                                                            class="button be-schema-image-select"
                                                            data-target-input="be_schema_global_image_5_4"
                                                            data-target-preview="be_schema_global_image_5_4_preview">
                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                    </button>
                                                    <button type="button"
                                                            class="button be-schema-image-clear"
                                                            data-target-input="be_schema_global_image_5_4"
                                                            data-target-preview="be_schema_global_image_5_4_preview">
                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                    </button>
                                                </div>
                                                <div id="be_schema_global_image_5_4_preview"
                                                     class="be-schema-image-preview">
                                                    <?php if ( $global_image_5_4 ) : ?>
                                                        <img src="<?php echo esc_url( $global_image_5_4 ); ?>" alt="" />
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="be-schema-optional-field<?php echo in_array( 'image_4_5', $global_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_4_5">
                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_4_5">−</button>
                                                <div class="be-schema-image-field">
                                                    <span class="be-schema-optional-label"><?php esc_html_e( '4:5 @ 1080x1350', 'beseo' ); ?></span>
                                                    <label for="be_schema_global_image_4_5" class="screen-reader-text"><?php esc_html_e( '4:5 @ 1080x1350', 'beseo' ); ?></label>
                                                    <input type="text"
                                                           id="be_schema_global_image_4_5"
                                                           name="be_schema_global_image_4_5"
                                                           value="<?php echo esc_url( $global_image_4_5 ); ?>"
                                                           class="regular-text" />
                                                    <button type="button"
                                                            class="button be-schema-image-select"
                                                            data-target-input="be_schema_global_image_4_5"
                                                            data-target-preview="be_schema_global_image_4_5_preview">
                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                    </button>
                                                    <button type="button"
                                                            class="button be-schema-image-clear"
                                                            data-target-input="be_schema_global_image_4_5"
                                                            data-target-preview="be_schema_global_image_4_5_preview">
                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                    </button>
                                                </div>
                                                <div id="be_schema_global_image_4_5_preview"
                                                     class="be-schema-image-preview">
                                                    <?php if ( $global_image_4_5 ) : ?>
                                                        <img src="<?php echo esc_url( $global_image_4_5 ); ?>" alt="" />
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="be-schema-optional-field<?php echo in_array( 'image_1_1_91', $global_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_1_1_91">
                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_1_1_91">−</button>
                                                <div class="be-schema-image-field">
                                                    <span class="be-schema-optional-label"><?php esc_html_e( '1:1.91 @ 630x1200', 'beseo' ); ?></span>
                                                    <label for="be_schema_global_image_1_1_91" class="screen-reader-text"><?php esc_html_e( '1:1.91 @ 630x1200', 'beseo' ); ?></label>
                                                    <input type="text"
                                                           id="be_schema_global_image_1_1_91"
                                                           name="be_schema_global_image_1_1_91"
                                                           value="<?php echo esc_url( $global_image_1_1_91 ); ?>"
                                                           class="regular-text" />
                                                    <button type="button"
                                                            class="button be-schema-image-select"
                                                            data-target-input="be_schema_global_image_1_1_91"
                                                            data-target-preview="be_schema_global_image_1_1_91_preview">
                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                    </button>
                                                    <button type="button"
                                                            class="button be-schema-image-clear"
                                                            data-target-input="be_schema_global_image_1_1_91"
                                                            data-target-preview="be_schema_global_image_1_1_91_preview">
                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                    </button>
                                                </div>
                                                <div id="be_schema_global_image_1_1_91_preview"
                                                     class="be-schema-image-preview">
                                                    <?php if ( $global_image_1_1_91 ) : ?>
                                                        <img src="<?php echo esc_url( $global_image_1_1_91 ); ?>" alt="" />
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="be-schema-optional-field<?php echo in_array( 'image_9_16', $global_images_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="image_9_16">
                                                <button type="button" class="button be-schema-optional-remove" data-optional-remove="image_9_16">−</button>
                                                <div class="be-schema-image-field">
                                                    <span class="be-schema-optional-label"><?php esc_html_e( '9:16 @ 675x1200', 'beseo' ); ?></span>
                                                    <label for="be_schema_global_image_9_16" class="screen-reader-text"><?php esc_html_e( '9:16 @ 675x1200', 'beseo' ); ?></label>
                                                    <input type="text"
                                                           id="be_schema_global_image_9_16"
                                                           name="be_schema_global_image_9_16"
                                                           value="<?php echo esc_url( $global_image_9_16 ); ?>"
                                                           class="regular-text" />
                                                    <button type="button"
                                                            class="button be-schema-image-select"
                                                            data-target-input="be_schema_global_image_9_16"
                                                            data-target-preview="be_schema_global_image_9_16_preview">
                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                    </button>
                                                    <button type="button"
                                                            class="button be-schema-image-clear"
                                                            data-target-input="be_schema_global_image_9_16"
                                                            data-target-preview="be_schema_global_image_9_16_preview">
                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                    </button>
                                                </div>
                                                <div id="be_schema_global_image_9_16_preview"
                                                     class="be-schema-image-preview">
                                                    <?php if ( $global_image_9_16 ) : ?>
                                                        <img src="<?php echo esc_url( $global_image_9_16 ); ?>" alt="" />
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
                <div id="be-schema-social-tab-platforms" class="be-schema-social-tab-panel">
                    <h2><?php esc_html_e( 'Platforms', 'beseo' ); ?></h2>
                    <div class="be-schema-social-layout">
                        <div class="be-schema-social-nav">
                            <ul>
                                <li>
                                    <a href="#be-schema-platforms-facebook"
                                       class="be-schema-social-subtab be-schema-platforms-subtab be-schema-social-subtab-active"
                                       data-platform-tab="facebook">
                                        <?php esc_html_e( 'Facebook', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-platforms-instagram"
                                       class="be-schema-social-subtab be-schema-platforms-subtab"
                                       data-platform-tab="instagram">
                                        <?php esc_html_e( 'Instagram', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-platforms-linkedin"
                                       class="be-schema-social-subtab be-schema-platforms-subtab"
                                       data-platform-tab="linkedin">
                                        <?php esc_html_e( 'LinkedIn', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-platforms-tictok"
                                       class="be-schema-social-subtab be-schema-platforms-subtab"
                                       data-platform-tab="tictok">
                                        <?php esc_html_e( 'TikTok', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-platforms-twitter"
                                       class="be-schema-social-subtab be-schema-platforms-subtab"
                                       data-platform-tab="twitter">
                                        <?php esc_html_e( 'Twitter', 'beseo' ); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="be-schema-social-panels">
                            <div id="be-schema-platforms-facebook" class="be-schema-social-panel be-schema-social-panel-active be-schema-platforms-parent-panel">
                                <div class="be-schema-social-layout">
                                    <div class="be-schema-social-nav">
                                        <ul>
                                            <li>
                                                <a href="#be-schema-platforms-facebook-overview"
                                                   class="be-schema-social-subtab be-schema-platforms-facebook-subtab be-schema-social-subtab-active"
                                                   data-platform-fb-tab="overview">
                                                    <?php esc_html_e( 'Overview', 'beseo' ); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#be-schema-platforms-facebook-content"
                                                   class="be-schema-social-subtab be-schema-platforms-facebook-subtab"
                                                   data-platform-fb-tab="content">
                                                    <?php esc_html_e( 'Content', 'beseo' ); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#be-schema-platforms-facebook-tools"
                                                   class="be-schema-social-subtab be-schema-platforms-facebook-subtab"
                                                   data-platform-fb-tab="tools">
                                                    <?php esc_html_e( 'Tools', 'beseo' ); ?>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="be-schema-social-panels">
                                        <div id="be-schema-platforms-facebook-overview" class="be-schema-social-panel be-schema-social-panel-active">
                                            <div class="be-schema-social-section">
                                                <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Overview', 'beseo' ); ?></h4>
                                                <label>
                                                    <input type="checkbox"
                                                           name="be_schema_og_enabled"
                                                           value="1"
                                                           <?php checked( $og_enabled ); ?> />
                                                    <?php esc_html_e(
                                                        'Enable OpenGraph output (og:* tags) for supported pages.',
                                                        'beseo'
                                                    ); ?>
                                                </label>
                                                <p class="description be-schema-social-description">
                                                    <?php esc_html_e(
                                                        'When enabled, the plugin will output OpenGraph tags for pages and posts using the rules described below.',
                                                        'beseo'
                                                    ); ?>
                                                </p>
                                            </div>
                                            <div class="be-schema-social-section">
                                                <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Status', 'beseo' ); ?></h4>
                                                <p>
                                                    <span class="be-schema-social-status-pill <?php echo $og_enabled ? '' : 'off'; ?>">
                                                        <?php echo $og_enabled ? esc_html__( 'OpenGraph: ON', 'beseo' ) : esc_html__( 'OpenGraph: OFF', 'beseo' ); ?>
                                                    </span>
                                                </p>
                                                <p class="description be-schema-social-description">
                                                    <?php esc_html_e(
                                                        'Status reflects the current admin toggle; page-level availability still depends on featured images and defaults.',
                                                        'beseo'
                                                    ); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div id="be-schema-platforms-facebook-content" class="be-schema-social-panel">
                                            <div class="be-schema-social-section">
                                                <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Content', 'beseo' ); ?></h4>
                                                <table class="form-table">
                                                    <tbody>
                                                        <tr class="be-schema-optional-row">
                                                            <th scope="row">
                                                                <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                                                            </th>
                                                            <td>
                                                                <div class="be-schema-optional-controls"
                                                                     data-optional-scope="facebook"
                                                                     data-optional-hidden="be_schema_facebook_optional"
                                                                     data-optional-singleton="facebook_page_url,facebook_app_id,facebook_notes">
                                                                    <label class="screen-reader-text" for="be-schema-facebook-optional"><?php esc_html_e( 'Add optional Facebook property', 'beseo' ); ?></label>
                                                                    <select id="be-schema-facebook-optional" aria-label="<?php esc_attr_e( 'Add optional Facebook property', 'beseo' ); ?>">
                                                                        <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                                        <option value="facebook_page_url"><?php esc_html_e( 'Facebook Page URL', 'beseo' ); ?></option>
                                                                        <option value="facebook_app_id"><?php esc_html_e( 'Facebook App ID', 'beseo' ); ?></option>
                                                                        <option value="facebook_notes"><?php esc_html_e( 'Notes (Admin-Only)', 'beseo' ); ?></option>
                                                                    </select>
                                                                    <button type="button"
                                                                            class="button be-schema-optional-add"
                                                                            data-optional-add="facebook"
                                                                            disabled>
                                                                        +
                                                                    </button>
                                                                    <input type="hidden" name="be_schema_facebook_optional" id="be_schema_facebook_optional" value="<?php echo esc_attr( $facebook_optional_serialized ); ?>" />
                                                                </div>

                                                                <div class="be-schema-optional-fields" id="be-schema-facebook-optional-fields">
                                                                    <div class="be-schema-optional-field<?php echo in_array( 'facebook_page_url', $facebook_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="facebook_page_url">
                                                                        <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="facebook_page_url">−</button>
                                                                        <label for="be_schema_facebook_page_url" class="screen-reader-text"><?php esc_html_e( 'Facebook Page URL', 'beseo' ); ?></label>
                                                                        <input type="text"
                                                                               name="be_schema_facebook_page_url"
                                                                               id="be_schema_facebook_page_url"
                                                                               value="<?php echo esc_url( $facebook_page_url ); ?>"
                                                                               class="regular-text" />
                                                                        <p class="description be-schema-social-description">
                                                                            <?php esc_html_e(
                                                                                'A public Facebook Page URL for your site or organisation.',
                                                                                'beseo'
                                                                            ); ?>
                                                                        </p>
                                                                    </div>

                                                                    <div class="be-schema-optional-field<?php echo in_array( 'facebook_app_id', $facebook_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="facebook_app_id">
                                                                        <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="facebook_app_id">−</button>
                                                                        <label for="be_schema_facebook_app_id" class="screen-reader-text"><?php esc_html_e( 'Facebook App ID', 'beseo' ); ?></label>
                                                                        <input type="text"
                                                                               name="be_schema_facebook_app_id"
                                                                               id="be_schema_facebook_app_id"
                                                                               value="<?php echo esc_attr( $facebook_app_id ); ?>"
                                                                               class="regular-text" />
                                                                        <p class="description be-schema-social-description">
                                                                            <?php esc_html_e(
                                                                                'When set, the plugin outputs fb:app_id for Facebook debugging and analytics.',
                                                                                'beseo'
                                                                            ); ?>
                                                                        </p>
                                                                    </div>

                                                                    <div class="be-schema-optional-field<?php echo in_array( 'facebook_notes', $facebook_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="facebook_notes">
                                                                        <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="facebook_notes">−</button>
                                                                        <label for="be_schema_facebook_notes" class="screen-reader-text"><?php esc_html_e( 'Notes (Admin-Only)', 'beseo' ); ?></label>
                                                                        <textarea
                                                                            name="be_schema_facebook_notes"
                                                                            id="be_schema_facebook_notes"
                                                                            rows="4"
                                                                            class="large-text code"><?php echo esc_textarea( $facebook_notes ); ?></textarea>
                                                                        <p class="description be-schema-social-description">
                                                                            <?php esc_html_e(
                                                                                'Free-form notes for your own reference. This is never output on the front end.',
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

                                        <div id="be-schema-platforms-facebook-tools" class="be-schema-social-panel">
                                            <div class="be-schema-social-section">
                                                <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Tools', 'beseo' ); ?></h4>
                                                <p class="description be-schema-social-description">
                                                    <?php esc_html_e(
                                                        'Use Facebook Sharing Debugger to refresh scraped data after changing images or titles.',
                                                        'beseo'
                                                    ); ?>
                                                    <br />
                                                    <a href="https://developers.facebook.com/tools/debug/"
                                                       target="_blank" rel="noopener noreferrer">
                                                        <?php esc_html_e( 'Open Facebook Sharing Debugger', 'beseo' ); ?>
                                                    </a>
                                                </p>
                                            </div>
                                            <div class="be-schema-social-section">
                                                <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Safety', 'beseo' ); ?></h4>
                                                <p class="description be-schema-social-description" style="margin-top:0;">
                                                    <?php esc_html_e( 'Use dry run to compute values but skip outputting OpenGraph meta tags on the front end.', 'beseo' ); ?>
                                                </p>
                                                <label>
                                                    <input type="checkbox"
                                                           name="be_schema_social_dry_run"
                                                           value="1"
                                                           <?php checked( $social_dry_run ); ?> />
                                                    <?php esc_html_e( 'Enable OpenGraph dry run (do not output og:* tags)', 'beseo' ); ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="be-schema-platforms-instagram" class="be-schema-social-panel be-schema-platforms-parent-panel">
                                <div class="be-schema-social-section">
                                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Instagram', 'beseo' ); ?></h4>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e( 'Instagram settings placeholder. Add handles and defaults when available.', 'beseo' ); ?>
                                    </p>
                                </div>
                            </div>
                            <div id="be-schema-platforms-linkedin" class="be-schema-social-panel be-schema-platforms-parent-panel">
                                <div class="be-schema-social-section">
                                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'LinkedIn', 'beseo' ); ?></h4>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e( 'LinkedIn settings placeholder for upcoming options.', 'beseo' ); ?>
                                    </p>
                                </div>
                            </div>
                            <div id="be-schema-platforms-tictok" class="be-schema-social-panel be-schema-platforms-parent-panel">
                                <div class="be-schema-social-section">
                                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'TikTok', 'beseo' ); ?></h4>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e( 'TikTok settings placeholder for future defaults.', 'beseo' ); ?>
                                    </p>
                                </div>
                            </div>
                            <div id="be-schema-platforms-twitter" class="be-schema-social-panel be-schema-platforms-parent-panel">
                                <div class="be-schema-social-layout">
                                    <div class="be-schema-social-nav">
                                        <ul>
                                            <li>
                                                <a href="#be-schema-platforms-twitter-overview"
                                                   class="be-schema-social-subtab be-schema-platforms-twitter-subtab be-schema-social-subtab-active"
                                                   data-platform-twitter-tab="overview">
                                                    <?php esc_html_e( 'Overview', 'beseo' ); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#be-schema-platforms-twitter-content"
                                                   class="be-schema-social-subtab be-schema-platforms-twitter-subtab"
                                                   data-platform-twitter-tab="content">
                                                    <?php esc_html_e( 'Cards', 'beseo' ); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#be-schema-platforms-twitter-tools"
                                                   class="be-schema-social-subtab be-schema-platforms-twitter-subtab"
                                                   data-platform-twitter-tab="tools">
                                                    <?php esc_html_e( 'Tools', 'beseo' ); ?>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="be-schema-social-panels">
                                        <div id="be-schema-platforms-twitter-overview" class="be-schema-social-panel be-schema-social-panel-active">
                                            <div class="be-schema-social-section">
                                                <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Overview', 'beseo' ); ?></h4>
                                                <label>
                                                    <input type="checkbox"
                                                           name="be_schema_twitter_enabled"
                                                           value="1"
                                                           <?php checked( $twitter_enabled ); ?> />
                                                    <?php esc_html_e(
                                                        'Enable Twitter Cards (twitter:* tags) for supported pages.',
                                                        'beseo'
                                                    ); ?>
                                                </label>
                                                <p class="description be-schema-social-description">
                                                    <?php esc_html_e(
                                                        'When enabled, the plugin will output Twitter Card tags for pages and posts using the rules described below.',
                                                        'beseo'
                                                    ); ?>
                                                </p>
                                            </div>
                                            <div class="be-schema-social-section">
                                                <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Status', 'beseo' ); ?></h4>
                                                <p>
                                                    <span class="be-schema-social-status-pill <?php echo $twitter_enabled ? '' : 'off'; ?>">
                                                        <?php echo $twitter_enabled ? esc_html__( 'Twitter Cards: ON', 'beseo' ) : esc_html__( 'Twitter Cards: OFF', 'beseo' ); ?>
                                                    </span>
                                                </p>
                                                <p class="description be-schema-social-description">
                                                    <?php esc_html_e(
                                                        'Status reflects the current admin toggle; page-level availability still depends on featured images and defaults.',
                                                        'beseo'
                                                    ); ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div id="be-schema-platforms-twitter-content" class="be-schema-social-panel">
                                            <div class="be-schema-social-section">
                                                <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Cards', 'beseo' ); ?></h4>
                                                <table class="form-table">
                                                    <tbody>
                                                        <tr class="be-schema-optional-row">
                                                            <th scope="row">
                                                                <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                                                            </th>
                                                            <td>
                                                                <div class="be-schema-optional-controls"
                                                                     data-optional-scope="twitter"
                                                                     data-optional-hidden="be_schema_twitter_optional"
                                                                     data-optional-singleton="twitter_site,twitter_creator,twitter_image_alt">
                                                                    <label class="screen-reader-text" for="be-schema-twitter-optional"><?php esc_html_e( 'Add optional Twitter property', 'beseo' ); ?></label>
                                                                    <select id="be-schema-twitter-optional" aria-label="<?php esc_attr_e( 'Add optional Twitter property', 'beseo' ); ?>">
                                                                        <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                                        <option value="twitter_site"><?php esc_html_e( '@Your Handle', 'beseo' ); ?></option>
                                                                        <option value="twitter_creator"><?php esc_html_e( '@Author Handle', 'beseo' ); ?></option>
                                                                        <option value="twitter_image_alt"><?php esc_html_e( 'Accessible Image Description', 'beseo' ); ?></option>
                                                                    </select>
                                                                    <button type="button"
                                                                            class="button be-schema-optional-add"
                                                                            data-optional-add="twitter"
                                                                            disabled>
                                                                        +
                                                                    </button>
                                                                    <input type="hidden" name="be_schema_twitter_optional" id="be_schema_twitter_optional" value="<?php echo esc_attr( $twitter_optional_serialized ); ?>" />
                                                                </div>

                                                                <div class="be-schema-optional-fields" id="be-schema-twitter-optional-fields">
                                                                    <div class="be-schema-optional-field<?php echo in_array( 'twitter_site', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_site">
                                                                        <button type="button" class="button be-schema-optional-remove" data-optional-remove="twitter_site">−</button>
                                                                        <label for="be_schema_twitter_site" class="screen-reader-text"><?php esc_html_e( 'Twitter Site Handle', 'beseo' ); ?></label>
                                                                        <input type="text"
                                                                               name="be_schema_twitter_site"
                                                                               id="be_schema_twitter_site"
                                                                               value="<?php echo esc_attr( $twitter_site ); ?>"
                                                                               class="regular-text" />
                                                                        <p class="description be-schema-social-description">
                                                                            <?php esc_html_e(
                                                                                'Outputs <meta name="twitter:site" content="@…"> using this handle (with @ added if missing).',
                                                                                'beseo'
                                                                            ); ?>
                                                                        </p>
                                                                    </div>

                                                                    <div class="be-schema-optional-field<?php echo in_array( 'twitter_creator', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_creator">
                                                                        <button type="button" class="button be-schema-optional-remove" data-optional-remove="twitter_creator">−</button>
                                                                        <label for="be_schema_twitter_creator" class="screen-reader-text"><?php esc_html_e( 'Twitter Creator Handle', 'beseo' ); ?></label>
                                                                        <input type="text"
                                                                               name="be_schema_twitter_creator"
                                                                               id="be_schema_twitter_creator"
                                                                               value="<?php echo esc_attr( $twitter_creator ); ?>"
                                                                               class="regular-text" />
                                                                        <p class="description be-schema-social-description">
                                                                            <?php esc_html_e(
                                                                                'Outputs <meta name="twitter:creator" content="@…"> using this handle (with @ added if missing).',
                                                                                'beseo'
                                                                            ); ?>
                                                                        </p>
                                                                    </div>

                                                                    <div class="be-schema-optional-field<?php echo in_array( 'twitter_image_alt', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_image_alt">
                                                                        <button type="button" class="button be-schema-optional-remove" data-optional-remove="twitter_image_alt">−</button>
                                                                        <label for="be_schema_twitter_image_alt" class="screen-reader-text"><?php esc_html_e( 'Twitter Image Alt Text', 'beseo' ); ?></label>
                                                                        <input type="text"
                                                                               name="be_schema_twitter_image_alt"
                                                                               id="be_schema_twitter_image_alt"
                                                                               value="<?php echo esc_attr( $twitter_image_alt ); ?>"
                                                                               class="regular-text" />
                                                                        <p class="description be-schema-social-description">
                                                                            <?php esc_html_e(
                                                                                'Outputs <meta name="twitter:image:alt" content="..."> when provided.',
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

                                            <div class="be-schema-social-section">
                                                <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Images', 'beseo' ); ?></h4>
                                                <table class="form-table">
                                                    <tbody>
                                                        <tr>
                                                            <th scope="row"><?php esc_html_e( 'Card Type', 'beseo' ); ?></th>
                                                            <td>
                                                                <fieldset>
                                                                    <label style="display:block; margin-bottom:6px;">
                                                                        <input type="radio"
                                                                               name="be_schema_twitter_card_type"
                                                                               value="summary_large_image"
                                                                               <?php checked( 'summary_large_image', $twitter_card_type ); ?>
                                                                               data-target-enable="be_schema_twitter_default_image"
                                                                               data-target-disable="be_schema_twitter_default_image_alt" />
                                                                        <?php esc_html_e( 'Large Summary Card', 'beseo' ); ?>
                                                                    </label>
                                                                    <label style="display:block;">
                                                                        <input type="radio"
                                                                               name="be_schema_twitter_card_type"
                                                                               value="summary"
                                                                               <?php checked( 'summary', $twitter_card_type ); ?>
                                                                               data-target-enable="be_schema_twitter_default_image_alt"
                                                                               data-target-disable="be_schema_twitter_default_image" />
                                                                        <?php esc_html_e( 'Summary Card', 'beseo' ); ?>
                                                                    </label>
                                                                </fieldset>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <th scope="row">
                                                                <?php esc_html_e( 'Large Summary Card', 'beseo' ); ?>
                                                            </th>
                                                            <td>
                                                                <div class="be-schema-image-field">
                                                                    <input type="text"
                                                                           id="be_schema_twitter_default_image"
                                                                           name="be_schema_twitter_default_image"
                                                                           value="<?php echo esc_url( $twitter_default_image ); ?>"
                                                                           class="regular-text" />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            data-target-input="be_schema_twitter_default_image"
                                                                            data-target-preview="be_schema_twitter_default_image_preview">
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            data-target-input="be_schema_twitter_default_image"
                                                                            data-target-preview="be_schema_twitter_default_image_preview">
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                </div>
                                                                <p class="description be-schema-social-description">
                                                                    <?php esc_html_e(
                                                                        'Used for twitter:image when there is no featured image on a page. If empty, Twitter falls back to the Global default image (if set).',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                                <div id="be_schema_twitter_default_image_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $twitter_default_image ) : ?>
                                                                        <img src="<?php echo esc_url( $twitter_default_image ); ?>" alt="" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <th scope="row">
                                                                <?php esc_html_e( 'Summary Card', 'beseo' ); ?>
                                                            </th>
                                                            <td>
                                                                <div class="be-schema-image-field">
                                                                    <input type="text"
                                                                           id="be_schema_twitter_default_image_alt"
                                                                           name="be_schema_twitter_default_image_alt"
                                                                           value="<?php echo esc_url( $twitter_default_image_alt ); ?>"
                                                                           class="regular-text" />
                                                                    <button type="button"
                                                                            class="button be-schema-image-select"
                                                                            data-target-input="be_schema_twitter_default_image_alt"
                                                                            data-target-preview="be_schema_twitter_default_image_alt_preview">
                                                                        <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                            class="button be-schema-image-clear"
                                                                            data-target-input="be_schema_twitter_default_image_alt"
                                                                            data-target-preview="be_schema_twitter_default_image_alt_preview">
                                                                        <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                    </button>
                                                                </div>
                                                                <p class="description be-schema-social-description">
                                                                    <?php esc_html_e(
                                                                        'Used for twitter:image when the Summary Card type is selected. If empty, Twitter falls back to the Global default image (if set).',
                                                                        'beseo'
                                                                    ); ?>
                                                                </p>
                                                                <div id="be_schema_twitter_default_image_alt_preview"
                                                                     class="be-schema-image-preview">
                                                                    <?php if ( $twitter_default_image_alt ) : ?>
                                                                        <img src="<?php echo esc_url( $twitter_default_image_alt ); ?>" alt="" />
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div id="be-schema-platforms-twitter-tools" class="be-schema-social-panel">
                                            <div class="be-schema-social-section">
                                                <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Tools', 'beseo' ); ?></h4>
                                                <p class="description be-schema-social-description">
                                                    <?php esc_html_e(
                                                        'Use Twitter Card Validator to rescrape after changing images or titles.',
                                                        'beseo'
                                                    ); ?>
                                                    <br />
                                                    <a href="https://cards-dev.twitter.com/validator"
                                                       target="_blank" rel="noopener noreferrer">
                                                        <?php esc_html_e( 'Open Twitter Card Validator', 'beseo' ); ?>
                                                    </a>
                                                </p>
                                            </div>
                                            <div class="be-schema-social-section">
                                                <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Safety', 'beseo' ); ?></h4>
                                                <p class="description be-schema-social-description" style="margin-top:0;">
                                                    <?php esc_html_e( 'Use dry run to compute values but skip outputting Twitter Card meta tags on the front end.', 'beseo' ); ?>
                                                </p>
                                                <label>
                                                    <input type="checkbox"
                                                           name="be_schema_twitter_dry_run"
                                                           value="1"
                                                           <?php checked( $twitter_dry_run ); ?> />
                                                    <?php esc_html_e( 'Enable Twitter dry run (do not output twitter:* tags)', 'beseo' ); ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $is_platforms ) { ?>
                <!-- FACEBOOK TAB -->
                <div id="be-schema-social-tab-facebook" class="be-schema-social-tab-panel">
                    <h2><?php esc_html_e( 'Facebook Settings', 'beseo' ); ?></h2>

                    <div class="be-schema-social-layout">
                        <div class="be-schema-social-nav">
                            <ul>
                                <li>
                                    <a href="#be-schema-facebook-overview"
                                       class="be-schema-social-subtab be-schema-social-subtab-active"
                                       data-fb-tab="overview">
                                        <?php esc_html_e( 'Overview', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-facebook-content"
                                       class="be-schema-social-subtab"
                                       data-fb-tab="content">
                                        <?php esc_html_e( 'Content', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-facebook-tools"
                                       class="be-schema-social-subtab"
                                       data-fb-tab="tools">
                                        <?php esc_html_e( 'Tools', 'beseo' ); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                            <div class="be-schema-social-panels">
                                <div id="be-schema-facebook-overview" class="be-schema-social-panel be-schema-social-panel-active">
                                    <div class="be-schema-social-section">
                                        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Overview', 'beseo' ); ?></h4>
                                        <label>
                                            <input type="checkbox"
                                                   name="be_schema_og_enabled"
                                                   value="1"
                                                   <?php checked( $og_enabled ); ?> />
                                            <?php esc_html_e(
                                                'Enable OpenGraph output (og:* tags) for supported pages.',
                                                'beseo'
                                            ); ?>
                                        </label>
                                        <p class="description be-schema-social-description">
                                            <?php esc_html_e(
                                                'When enabled, the plugin will output OpenGraph tags for pages and posts using the rules described below.',
                                                'beseo'
                                            ); ?>
                                        </p>
                                    </div>
                                    <div class="be-schema-social-section">
                                        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Status', 'beseo' ); ?></h4>
                                        <p>
                                            <span class="be-schema-social-status-pill <?php echo $og_enabled ? '' : 'off'; ?>">
                                                <?php echo $og_enabled ? esc_html__( 'OpenGraph: ON', 'beseo' ) : esc_html__( 'OpenGraph: OFF', 'beseo' ); ?>
                                            </span>
                                        </p>
                                        <p class="description be-schema-social-description">
                                            <?php esc_html_e(
                                                'Status reflects the current admin toggle; page-level availability still depends on featured images and defaults.',
                                                'beseo'
                                            ); ?>
                                        </p>
                                    </div>
                                </div>
                            <div id="be-schema-facebook-content" class="be-schema-social-panel">
                                <div class="be-schema-social-section">
                                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Content', 'beseo' ); ?></h4>
                                    <table class="form-table">
                                        <tbody>
                                            <tr class="be-schema-optional-row">
                                                <th scope="row">
                                                    <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                                                </th>
                                                <td>
                                                    <div class="be-schema-optional-controls"
                                                         data-optional-scope="facebook"
                                                         data-optional-hidden="be_schema_facebook_optional"
                                                         data-optional-singleton="facebook_page_url,facebook_app_id,facebook_notes">
                                                        <label class="screen-reader-text" for="be-schema-facebook-optional"><?php esc_html_e( 'Add optional Facebook property', 'beseo' ); ?></label>
                                                        <select id="be-schema-facebook-optional" aria-label="<?php esc_attr_e( 'Add optional Facebook property', 'beseo' ); ?>">
                                                            <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                            <option value="facebook_page_url"><?php esc_html_e( 'Facebook Page URL', 'beseo' ); ?></option>
                                                            <option value="facebook_app_id"><?php esc_html_e( 'Facebook App ID', 'beseo' ); ?></option>
                                                            <option value="facebook_notes"><?php esc_html_e( 'Notes (Admin-Only)', 'beseo' ); ?></option>
                                                        </select>
                                                        <button type="button"
                                                                class="button be-schema-optional-add"
                                                                data-optional-add="facebook"
                                                                disabled>
                                                            +
                                                        </button>
                                                        <input type="hidden" name="be_schema_facebook_optional" id="be_schema_facebook_optional" value="<?php echo esc_attr( $facebook_optional_serialized ); ?>" />
                                                    </div>

                                                    <div class="be-schema-optional-fields" id="be-schema-facebook-optional-fields">
                                                        <div class="be-schema-optional-field<?php echo in_array( 'facebook_page_url', $facebook_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="facebook_page_url">
                                                            <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="facebook_page_url">−</button>
                                                            <label for="be_schema_facebook_page_url" class="screen-reader-text"><?php esc_html_e( 'Facebook Page URL', 'beseo' ); ?></label>
                                                            <input type="text"
                                                                   name="be_schema_facebook_page_url"
                                                                   id="be_schema_facebook_page_url"
                                                                   value="<?php echo esc_url( $facebook_page_url ); ?>"
                                                                   class="regular-text" />
                                                            <p class="description be-schema-social-description">
                                                                <?php esc_html_e(
                                                                    'A public Facebook Page URL for your site or organisation.',
                                                                    'beseo'
                                                                ); ?>
                                                            </p>
                                                        </div>

                                                        <div class="be-schema-optional-field<?php echo in_array( 'facebook_app_id', $facebook_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="facebook_app_id">
                                                            <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="facebook_app_id">−</button>
                                                            <label for="be_schema_facebook_app_id" class="screen-reader-text"><?php esc_html_e( 'Facebook App ID', 'beseo' ); ?></label>
                                                            <input type="text"
                                                                   name="be_schema_facebook_app_id"
                                                                   id="be_schema_facebook_app_id"
                                                                   value="<?php echo esc_attr( $facebook_app_id ); ?>"
                                                                   class="regular-text" />
                                                            <p class="description be-schema-social-description">
                                                                <?php esc_html_e(
                                                                    'When set, the plugin outputs fb:app_id for Facebook debugging and analytics.',
                                                                    'beseo'
                                                                ); ?>
                                                            </p>
                                                        </div>

                                                        <div class="be-schema-optional-field<?php echo in_array( 'facebook_notes', $facebook_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="facebook_notes">
                                                            <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="facebook_notes">−</button>
                                                            <label for="be_schema_facebook_notes" class="screen-reader-text"><?php esc_html_e( 'Notes (Admin-Only)', 'beseo' ); ?></label>
                                                            <textarea
                                                                name="be_schema_facebook_notes"
                                                                id="be_schema_facebook_notes"
                                                                rows="4"
                                                                class="large-text code"><?php echo esc_textarea( $facebook_notes ); ?></textarea>
                                                            <p class="description be-schema-social-description">
                                                                <?php esc_html_e(
                                                                    'Free-form notes for your own reference. This is never output on the front end.',
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

                                <div id="be-schema-facebook-tools" class="be-schema-social-panel">
                                    <div class="be-schema-social-section">
                                        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Tools', 'beseo' ); ?></h4>
                                        <p class="description be-schema-social-description">
                                            <?php esc_html_e(
                                                'Use Facebook Sharing Debugger to refresh scraped data after changing images or titles.',
                                                'beseo'
                                            ); ?>
                                            <br />
                                            <a href="https://developers.facebook.com/tools/debug/"
                                               target="_blank" rel="noopener noreferrer">
                                                <?php esc_html_e( 'Open Facebook Sharing Debugger', 'beseo' ); ?>
                                            </a>
                                        </p>
                                    </div>
                                    <div class="be-schema-social-section">
                                        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Safety', 'beseo' ); ?></h4>
                                        <p class="description be-schema-social-description" style="margin-top:0;">
                                            <?php esc_html_e( 'Use dry run to compute values but skip outputting OpenGraph meta tags on the front end.', 'beseo' ); ?>
                                        </p>
                                        <label>
                                            <input type="checkbox"
                                                   name="be_schema_social_dry_run"
                                                   value="1"
                                                   <?php checked( $social_dry_run ); ?> />
                                            <?php esc_html_e( 'Enable OpenGraph dry run (do not output og:* tags)', 'beseo' ); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- TWITTER TAB -->
                <div id="be-schema-social-tab-twitter" class="be-schema-social-tab-panel">
                    <h2><?php esc_html_e( 'Twitter Settings', 'beseo' ); ?></h2>

                    <div class="be-schema-social-layout">
                        <div class="be-schema-social-nav">
                            <ul>
                                <li>
                                    <a href="#be-schema-twitter-overview"
                                       class="be-schema-social-subtab be-schema-social-subtab-active"
                                       data-twitter-tab="overview">
                                        <?php esc_html_e( 'Overview', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-twitter-content"
                                       class="be-schema-social-subtab"
                                       data-twitter-tab="content">
                                        <?php esc_html_e( 'Cards', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-twitter-tools"
                                       class="be-schema-social-subtab"
                                       data-twitter-tab="tools">
                                        <?php esc_html_e( 'Tools', 'beseo' ); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="be-schema-social-panels">
                            <div id="be-schema-twitter-overview" class="be-schema-social-panel be-schema-social-panel-active">
                                <div class="be-schema-social-section">
                                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Overview', 'beseo' ); ?></h4>
                                    <label>
                                        <input type="checkbox"
                                               name="be_schema_twitter_enabled"
                                               value="1"
                                               <?php checked( $twitter_enabled ); ?> />
                                        <?php esc_html_e(
                                            'Enable Twitter Cards (twitter:* tags) for supported pages.',
                                            'beseo'
                                        ); ?>
                                    </label>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'When enabled, the plugin will output Twitter Card tags for pages and posts using the rules described below.',
                                            'beseo'
                                        ); ?>
                                    </p>
                                </div>
                                <div class="be-schema-social-section">
                                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Status', 'beseo' ); ?></h4>
                                    <p>
                                        <span class="be-schema-social-status-pill <?php echo $twitter_enabled ? '' : 'off'; ?>">
                                            <?php echo $twitter_enabled ? esc_html__( 'Twitter Cards: ON', 'beseo' ) : esc_html__( 'Twitter Cards: OFF', 'beseo' ); ?>
                                        </span>
                                    </p>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Status reflects the current admin toggle; page-level availability still depends on featured images and defaults.',
                                            'beseo'
                                        ); ?>
                                    </p>
                                </div>
                            </div>

                            <div id="be-schema-twitter-content" class="be-schema-social-panel">
                                <div class="be-schema-social-section">
                                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Cards', 'beseo' ); ?></h4>
                                    <table class="form-table">
                                        <tbody>
                                            <tr class="be-schema-optional-row">
                                                <th scope="row">
                                                    <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                                                </th>
                                                <td>
                                                    <div class="be-schema-optional-controls"
                                                         data-optional-scope="twitter"
                                                         data-optional-hidden="be_schema_twitter_optional"
                                                         data-optional-singleton="twitter_site,twitter_creator,twitter_image_alt">
                                                        <label class="screen-reader-text" for="be-schema-twitter-optional"><?php esc_html_e( 'Add optional Twitter property', 'beseo' ); ?></label>
                                                        <select id="be-schema-twitter-optional" aria-label="<?php esc_attr_e( 'Add optional Twitter property', 'beseo' ); ?>">
                                                            <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                            <option value="twitter_site"><?php esc_html_e( '@Your Handle', 'beseo' ); ?></option>
                                                            <option value="twitter_creator"><?php esc_html_e( '@Author Handle', 'beseo' ); ?></option>
                                                            <option value="twitter_image_alt"><?php esc_html_e( 'Accessible Image Description', 'beseo' ); ?></option>
                                                        </select>
                                                        <button type="button"
                                                                class="button be-schema-optional-add"
                                                                data-optional-add="twitter"
                                                                disabled>
                                                            +
                                                        </button>
                                                        <input type="hidden" name="be_schema_twitter_optional" id="be_schema_twitter_optional" value="<?php echo esc_attr( $twitter_optional_serialized ); ?>" />
                                                    </div>

                                                    <div class="be-schema-optional-fields" id="be-schema-twitter-optional-fields">
                                                        <div class="be-schema-optional-field<?php echo in_array( 'twitter_site', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_site">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="twitter_site">−</button>
                                                            <label for="be_schema_twitter_site" class="screen-reader-text"><?php esc_html_e( 'Twitter Site Handle', 'beseo' ); ?></label>
                                                            <input type="text"
                                                                   name="be_schema_twitter_site"
                                                                   id="be_schema_twitter_site"
                                                                   value="<?php echo esc_attr( $twitter_site ); ?>"
                                                                   class="regular-text" />
                                                            <p class="description be-schema-social-description">
                                                                <?php esc_html_e(
                                                                    'Outputs <meta name="twitter:site" content="@…"> using this handle (with @ added if missing).',
                                                                    'beseo'
                                                                ); ?>
                                                            </p>
                                                        </div>

                                                        <div class="be-schema-optional-field<?php echo in_array( 'twitter_creator', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_creator">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="twitter_creator">−</button>
                                                            <label for="be_schema_twitter_creator" class="screen-reader-text"><?php esc_html_e( 'Twitter Creator Handle', 'beseo' ); ?></label>
                                                            <input type="text"
                                                                   name="be_schema_twitter_creator"
                                                                   id="be_schema_twitter_creator"
                                                                   value="<?php echo esc_attr( $twitter_creator ); ?>"
                                                                   class="regular-text" />
                                                            <p class="description be-schema-social-description">
                                                                <?php esc_html_e(
                                                                    'Outputs <meta name="twitter:creator" content="@…"> using this handle (with @ added if missing).',
                                                                    'beseo'
                                                                ); ?>
                                                            </p>
                                                        </div>

                                                        <div class="be-schema-optional-field<?php echo in_array( 'twitter_image_alt', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_image_alt">
                                                            <button type="button" class="button be-schema-optional-remove" data-optional-remove="twitter_image_alt">−</button>
                                                            <label for="be_schema_twitter_image_alt" class="screen-reader-text"><?php esc_html_e( 'Twitter Image Alt Text', 'beseo' ); ?></label>
                                                            <input type="text"
                                                                   name="be_schema_twitter_image_alt"
                                                                   id="be_schema_twitter_image_alt"
                                                                   value="<?php echo esc_attr( $twitter_image_alt ); ?>"
                                                                   class="regular-text" />
                                                            <p class="description be-schema-social-description">
                                                                <?php esc_html_e(
                                                                    'Outputs <meta name="twitter:image:alt" content="..."> when provided.',
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

                                <div class="be-schema-social-section">
                                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Images', 'beseo' ); ?></h4>
                                    <table class="form-table">
                                        <tbody>
                                            <tr>
                                                <th scope="row"><?php esc_html_e( 'Card Type', 'beseo' ); ?></th>
                                                <td>
                                                    <fieldset>
                                                        <label style="display:block; margin-bottom:6px;">
                                                            <input type="radio"
                                                                   name="be_schema_twitter_card_type"
                                                                   value="summary_large_image"
                                                                   <?php checked( 'summary_large_image', $twitter_card_type ); ?>
                                                                   data-target-enable="be_schema_twitter_default_image"
                                                                   data-target-disable="be_schema_twitter_default_image_alt" />
                                                            <?php esc_html_e( 'Large Summary Card', 'beseo' ); ?>
                                                        </label>
                                                        <label style="display:block;">
                                                            <input type="radio"
                                                                   name="be_schema_twitter_card_type"
                                                                   value="summary"
                                                                   <?php checked( 'summary', $twitter_card_type ); ?>
                                                                   data-target-enable="be_schema_twitter_default_image_alt"
                                                                   data-target-disable="be_schema_twitter_default_image" />
                                                            <?php esc_html_e( 'Summary Card', 'beseo' ); ?>
                                                        </label>
                                                    </fieldset>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Large Summary Card', 'beseo' ); ?>
                                                </th>
                                                <td>
                                                    <div class="be-schema-image-field">
                                                        <input type="text"
                                                               id="be_schema_twitter_default_image"
                                                               name="be_schema_twitter_default_image"
                                                               value="<?php echo esc_url( $twitter_default_image ); ?>"
                                                               class="regular-text" />
                                                        <button type="button"
                                                                class="button be-schema-image-select"
                                                                data-target-input="be_schema_twitter_default_image"
                                                                data-target-preview="be_schema_twitter_default_image_preview">
                                                            <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                        </button>
                                                        <button type="button"
                                                                class="button be-schema-image-clear"
                                                                data-target-input="be_schema_twitter_default_image"
                                                                data-target-preview="be_schema_twitter_default_image_preview">
                                                            <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                        </button>
                                                    </div>
                                                    <p class="description be-schema-social-description">
                                                        <?php esc_html_e(
                                                            'Used for twitter:image when there is no featured image on a page. If empty, Twitter falls back to the Global default image (if set).',
                                                            'beseo'
                                                        ); ?>
                                                    </p>
                                                    <div id="be_schema_twitter_default_image_preview"
                                                         class="be-schema-image-preview">
                                                        <?php if ( $twitter_default_image ) : ?>
                                                            <img src="<?php echo esc_url( $twitter_default_image ); ?>" alt="" />
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Summary Card', 'beseo' ); ?>
                                                </th>
                                                <td>
                                                    <div class="be-schema-image-field">
                                                        <input type="text"
                                                               id="be_schema_twitter_default_image_alt"
                                                               name="be_schema_twitter_default_image_alt"
                                                               value="<?php echo esc_url( $twitter_default_image_alt ); ?>"
                                                               class="regular-text" />
                                                        <button type="button"
                                                                class="button be-schema-image-select"
                                                                data-target-input="be_schema_twitter_default_image_alt"
                                                                data-target-preview="be_schema_twitter_default_image_alt_preview">
                                                            <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                        </button>
                                                        <button type="button"
                                                                class="button be-schema-image-clear"
                                                                data-target-input="be_schema_twitter_default_image_alt"
                                                                data-target-preview="be_schema_twitter_default_image_alt_preview">
                                                            <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                        </button>
                                                    </div>
                                                    <p class="description be-schema-social-description">
                                                        <?php esc_html_e(
                                                            'Optional secondary fallback for twitter:image. If empty, Twitter follows the usual order: featured image → Large Summary Card image → Global default.',
                                                            'beseo'
                                                        ); ?>
                                                    </p>
                                                    <div id="be_schema_twitter_default_image_alt_preview"
                                                         class="be-schema-image-preview">
                                                        <?php if ( $twitter_default_image_alt ) : ?>
                                                            <img src="<?php echo esc_url( $twitter_default_image_alt ); ?>" alt="" />
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div id="be-schema-twitter-tools" class="be-schema-social-panel">
                                <div class="be-schema-social-section">
                                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Tools', 'beseo' ); ?></h4>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Use the Validator tab under Tools for Twitter Card validation and previews.',
                                            'beseo'
                                        ); ?>
                                    </p>
                                </div>
                                <div class="be-schema-social-section">
                                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Dry Run', 'beseo' ); ?></h4>
                                    <p class="description be-schema-social-description" style="margin-top:0;">
                                        <?php esc_html_e( 'Use dry run to compute values but skip outputting Twitter meta tags on the front end.', 'beseo' ); ?>
                                    </p>
                                    <label>
                                        <input type="checkbox"
                                               name="be_schema_twitter_dry_run"
                                               value="1"
                                               <?php checked( $twitter_dry_run ); ?> />
                                        <?php esc_html_e( 'Enable Twitter native content dry run. (Do not output meta tags.)', 'beseo' ); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            
<!-- INSTAGRAM TAB -->
            <div id="be-schema-social-tab-instagram" class="be-schema-social-tab-panel">
                <h2><?php esc_html_e( 'Instagram Settings', 'beseo' ); ?></h2>
                <div class="be-schema-social-section">
                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Overview', 'beseo' ); ?></h4>
                    <p class="description be-schema-social-description">
                        <?php esc_html_e(
                            'Instagram support placeholder. Configure when network-specific options are added.',
                            'beseo'
                        ); ?>
                    </p>
                </div>
            </div>

            <!-- TIKTOK TAB -->
            <div id="be-schema-social-tab-tictok" class="be-schema-social-tab-panel">
                <h2><?php esc_html_e( 'TikTok Settings', 'beseo' ); ?></h2>
                <div class="be-schema-social-section">
                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Overview', 'beseo' ); ?></h4>
                    <p class="description be-schema-social-description">
                        <?php esc_html_e(
                            'TikTok support placeholder. Configure when platform-specific options are added.',
                            'beseo'
                        ); ?>
                    </p>
                </div>
            </div>

            <!-- LINKEDIN TAB -->
            <div id="be-schema-social-tab-linkedin" class="be-schema-social-tab-panel">
                <h2><?php esc_html_e( 'LinkedIn Settings', 'beseo' ); ?></h2>
                <div class="be-schema-social-section">
                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Overview', 'beseo' ); ?></h4>
                    <p class="description be-schema-social-description">
                        <?php esc_html_e(
                            'LinkedIn support placeholder. Configure when platform-specific options are added.',
                            'beseo'
                        ); ?>
                    </p>
                </div>
            </div>
            <?php } ?>

            <?php submit_button( __( 'Save Social Settings', 'beseo' ) ); ?>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Top-level social tabs.
                var socialTabLinks = document.querySelectorAll('.be-schema-social-tab-link');
                var socialTabPanels = document.querySelectorAll('.be-schema-social-tab-panel');

                function activateSocialTab(tabKey) {
                    socialTabLinks.forEach(function (link) {
                        if (link.getAttribute('data-social-tab') === tabKey) {
                            link.classList.add('be-schema-social-tab-active');
                        } else {
                            link.classList.remove('be-schema-social-tab-active');
                        }
                    });

                    socialTabPanels.forEach(function (panel) {
                        if (panel.id === 'be-schema-social-tab-' + tabKey) {
                            panel.classList.add('be-schema-social-tab-panel-active');
                        } else {
                            panel.classList.remove('be-schema-social-tab-panel-active');
                        }
                    });
                }

                socialTabLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-social-tab');
                        activateSocialTab(tabKey);
                    });
                });

                // Facebook vertical subtabs.
                var fbLinks = document.querySelectorAll('.be-schema-social-subtab[data-fb-tab]');
                var fbPanels = document.querySelectorAll('#be-schema-social-tab-facebook .be-schema-social-panel');

                function activateFbTab(tabKey) {
                    fbLinks.forEach(function (link) {
                        if (link.getAttribute('data-fb-tab') === tabKey) {
                            link.classList.add('be-schema-social-subtab-active');
                        } else {
                            link.classList.remove('be-schema-social-subtab-active');
                        }
                    });

                    fbPanels.forEach(function (panel) {
                        if (panel.id === 'be-schema-' + 'facebook-' + tabKey) {
                            panel.classList.add('be-schema-social-panel-active');
                        } else {
                            panel.classList.remove('be-schema-social-panel-active');
                        }
                    });
                }

                fbLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-fb-tab');
                        activateFbTab(tabKey);
                    });
                });

                // Twitter vertical subtabs.
                var twLinks = document.querySelectorAll('.be-schema-social-subtab[data-twitter-tab]');
                var twPanels = document.querySelectorAll('#be-schema-social-tab-twitter .be-schema-social-panel');
                var twEnableCheckbox = document.querySelector('input[name="be_schema_twitter_enabled"]');
                var twContentPanels = document.querySelectorAll('#be-schema-twitter-content, #be-schema-platforms-twitter-content');

                function activateTwitterTab(tabKey) {
                    twLinks.forEach(function (link) {
                        if (link.getAttribute('data-twitter-tab') === tabKey) {
                            link.classList.add('be-schema-social-subtab-active');
                        } else {
                            link.classList.remove('be-schema-social-subtab-active');
                        }
                    });

                    twPanels.forEach(function (panel) {
                        if (panel.id === 'be-schema-' + 'twitter-' + tabKey) {
                            panel.classList.add('be-schema-social-panel-active');
                        } else {
                            panel.classList.remove('be-schema-social-panel-active');
                        }
                    });
                }

                twLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-twitter-tab');
                        activateTwitterTab(tabKey);
                    });
                });

                // Platforms vertical subtabs (Social Media -> Platforms tab).
                var platformLinks = document.querySelectorAll('.be-schema-platforms-subtab');
                var platformPanels = document.querySelectorAll('#be-schema-social-tab-platforms .be-schema-platforms-parent-panel');

                function activatePlatformTab(tabKey) {
                    platformLinks.forEach(function (link) {
                        if (link.getAttribute('data-platform-tab') === tabKey) {
                            link.classList.add('be-schema-social-subtab-active');
                        } else {
                            link.classList.remove('be-schema-social-subtab-active');
                        }
                    });

                    platformPanels.forEach(function (panel) {
                        if (panel.id === 'be-schema-platforms-' + tabKey) {
                            panel.classList.add('be-schema-social-panel-active');
                        } else {
                            panel.classList.remove('be-schema-social-panel-active');
                        }
                    });
                }

                platformLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-platform-tab');
                        activatePlatformTab(tabKey);
                    });
                });

                // Platforms → Facebook nested subtabs.
                var platformFbLinks = document.querySelectorAll('#be-schema-platforms-facebook .be-schema-platforms-facebook-subtab');
                var platformFbPanels = document.querySelectorAll('#be-schema-platforms-facebook .be-schema-social-panel');

                function activatePlatformFacebookTab(tabKey) {
                    platformFbLinks.forEach(function (link) {
                        if (link.getAttribute('data-platform-fb-tab') === tabKey) {
                            link.classList.add('be-schema-social-subtab-active');
                        } else {
                            link.classList.remove('be-schema-social-subtab-active');
                        }
                    });

                    platformFbPanels.forEach(function (panel) {
                        if (panel.id === 'be-schema-platforms-facebook-' + tabKey) {
                            panel.classList.add('be-schema-social-panel-active');
                        } else {
                            panel.classList.remove('be-schema-social-panel-active');
                        }
                    });
                }

                platformFbLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-platform-fb-tab');
                        activatePlatformFacebookTab(tabKey);
                    });
                });

                // Platforms → Twitter nested subtabs.
                var platformTwitterLinks = document.querySelectorAll('#be-schema-platforms-twitter .be-schema-platforms-twitter-subtab');
                var platformTwitterPanels = document.querySelectorAll('#be-schema-platforms-twitter .be-schema-social-panel');

                function activatePlatformTwitterTab(tabKey) {
                    platformTwitterLinks.forEach(function (link) {
                        if (link.getAttribute('data-platform-twitter-tab') === tabKey) {
                            link.classList.add('be-schema-social-subtab-active');
                        } else {
                            link.classList.remove('be-schema-social-subtab-active');
                        }
                    });

                    platformTwitterPanels.forEach(function (panel) {
                        if (panel.id === 'be-schema-platforms-twitter-' + tabKey) {
                            panel.classList.add('be-schema-social-panel-active');
                        } else {
                            panel.classList.remove('be-schema-social-panel-active');
                        }
                    });
                }

                platformTwitterLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-platform-twitter-tab');
                        activatePlatformTwitterTab(tabKey);
                    });
                });

                function toggleTwitterCards(enabled) {
                    if (!twContentPanels || !twContentPanels.length) {
                        return;
                    }
                    twContentPanels.forEach(function (panel) {
                        var inputs = panel.querySelectorAll('input, select, textarea, button');
                        inputs.forEach(function (el) {
                            el.disabled = !enabled;
                        });
                        panel.classList.toggle('be-schema-social-panel-disabled', !enabled);
                    });
                }

                if (twEnableCheckbox) {
                    toggleTwitterCards(twEnableCheckbox.checked);
                    twEnableCheckbox.addEventListener('change', function () {
                        toggleTwitterCards(twEnableCheckbox.checked);
                    });
                }


                // Twitter validator embed loader.
                if (window.beSchemaInitAllOptionalGroups) {
                    window.beSchemaInitAllOptionalGroups();
                }

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
            });
        </script>
    </div>
    <?php
}
