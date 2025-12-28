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

require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/admin-helpers.php';

/**
 * Save BE Social Media settings.
 *
 * Option name: be_schema_social_settings
 */
if ( ! function_exists( 'be_schema_social_validate_url_field' ) ) {
    function be_schema_social_validate_url_field( $raw_value, $label, &$errors ) {
        return be_schema_admin_validate_url_field( $raw_value, $label, $errors );
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
    $social_open_graph_partial = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/social-open-graph.php';
    $social_platforms_partial  = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/social-platforms.php';
    $social_facebook_partial   = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/social-facebook.php';
    $social_twitter_partial    = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/social-twitter.php';

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

                </div>
                <?php if ( ! $is_platforms ) : ?>
                    <?php
                    if ( file_exists( $social_open_graph_partial ) ) {
                        include $social_open_graph_partial;
                    }
                    if ( file_exists( $social_platforms_partial ) ) {
                        include $social_platforms_partial;
                    }
                    ?>
                <?php endif; ?>

                <?php if ( $is_platforms ) { ?>
                    <?php
                    if ( file_exists( $social_facebook_partial ) ) {
                        include $social_facebook_partial;
                    }
                    if ( file_exists( $social_twitter_partial ) ) {
                        include $social_twitter_partial;
                    }
                    ?>

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
            </div>

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
