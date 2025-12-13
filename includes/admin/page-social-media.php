<?php
/**
 * Social Media Admin Page
 *
 * Submenu: BE SEO → Social Media
 *
 * Tabs:
 *  - Dashboard (global fallback image + tiny image summary)
 *  - Facebook (overview toggle/status + FB page, FB default image, app id, notes, tools)
 *  - Twitter (overview toggle/status + handle, card type, Twitter default image, notes, tools)
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

    // Load current settings from helper if available.
    if ( function_exists( 'be_schema_social_get_settings' ) ) {
        $settings = be_schema_social_get_settings();
    } else {
        $settings = get_option( 'be_schema_social_settings', array() );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }
    }

    $validation_errors = array();

    // DASHBOARD TAB ------------------------------.

    // Global enables (collected from the Facebook/Twitter Overview panels; must align with core-social.php defaults).
    $settings['social_enable_og']      = isset( $_POST['be_schema_og_enabled'] ) ? '1' : '0';
    $settings['social_enable_twitter'] = isset( $_POST['be_schema_twitter_enabled'] ) ? '1' : '0';
    $settings['dry_run']               = isset( $_POST['be_schema_social_dry_run'] ) ? '1' : '0';

    // Optional: keep legacy keys in sync if they existed before.
    $settings['og_enabled']      = $settings['social_enable_og'];
    $settings['twitter_enabled'] = $settings['social_enable_twitter'];

    // Global default fallback image (must align with social_default_image).
    $settings['social_default_image'] = be_schema_social_validate_url_field(
        isset( $_POST['be_schema_global_default_image'] ) ? wp_unslash( $_POST['be_schema_global_default_image'] ) : '',
        __( 'Global Default Image', 'beseo' ),
        $validation_errors
    );

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

    $settings['twitter_notes'] = isset( $_POST['be_schema_twitter_notes'] )
        ? wp_kses_post( wp_unslash( $_POST['be_schema_twitter_notes'] ) )
        : '';

    $settings['twitter_optional'] = isset( $_POST['be_schema_twitter_optional'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_twitter_optional'] ) )
        : '';

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

    // Enqueue media for image pickers.
    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

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

    $global_default_image   = isset( $settings['social_default_image'] ) ? $settings['social_default_image'] : '';
    $facebook_page_url      = isset( $settings['facebook_page_url'] ) ? $settings['facebook_page_url'] : '';
    $facebook_default_image = isset( $settings['facebook_default_image'] ) ? $settings['facebook_default_image'] : '';
    $facebook_app_id        = isset( $settings['facebook_app_id'] ) ? $settings['facebook_app_id'] : '';
    $facebook_notes         = isset( $settings['facebook_notes'] ) ? $settings['facebook_notes'] : '';
    $facebook_optional_raw  = isset( $settings['facebook_optional'] ) ? $settings['facebook_optional'] : '';

    $twitter_handle        = isset( $settings['twitter_handle'] ) ? $settings['twitter_handle'] : '';
    $twitter_card_type     = isset( $settings['twitter_card_type'] ) ? $settings['twitter_card_type'] : 'summary_large_image';
    $twitter_default_image = isset( $settings['twitter_default_image'] ) ? $settings['twitter_default_image'] : '';
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
    if ( ! empty( $facebook_page_url ) && ! in_array( 'facebook_page_url', $facebook_optional_props, true ) ) {
        $facebook_optional_props[] = 'facebook_page_url';
    }
    if ( ! empty( $facebook_default_image ) && ! in_array( 'facebook_default_image', $facebook_optional_props, true ) ) {
        $facebook_optional_props[] = 'facebook_default_image';
    }
    if ( ! empty( $facebook_app_id ) && ! in_array( 'facebook_app_id', $facebook_optional_props, true ) ) {
        $facebook_optional_props[] = 'facebook_app_id';
    }
    if ( ! empty( $facebook_notes ) && ! in_array( 'facebook_notes', $facebook_optional_props, true ) ) {
        $facebook_optional_props[] = 'facebook_notes';
    }
    $facebook_optional_serialized = implode( ',', $facebook_optional_props );

    $twitter_optional_props = array();
    if ( ! empty( $twitter_optional_raw ) ) {
        $twitter_optional_props = array_filter(
            array_map(
                'trim',
                explode( ',', $twitter_optional_raw )
            )
        );
    }
    if ( ! empty( $twitter_handle ) && ! in_array( 'twitter_handle', $twitter_optional_props, true ) ) {
        $twitter_optional_props[] = 'twitter_handle';
    }
    if ( ! empty( $twitter_default_image ) && ! in_array( 'twitter_default_image', $twitter_optional_props, true ) ) {
        $twitter_optional_props[] = 'twitter_default_image';
    }
    if ( ! empty( $twitter_notes ) && ! in_array( 'twitter_notes', $twitter_optional_props, true ) ) {
        $twitter_optional_props[] = 'twitter_notes';
    }
    $twitter_optional_serialized = implode( ',', $twitter_optional_props );

    ?>
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
            }

            .be-schema-optional-field.is-hidden {
                display: none;
            }

            .be-schema-optional-remove {
                margin-bottom: 6px;
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
                </ul>

                <!-- DASHBOARD TAB -->
                <div id="be-schema-social-tab-settings"
                     class="be-schema-social-tab-panel be-schema-social-tab-panel-active">
                    <h2><?php esc_html_e( 'Social Dashboard', 'beseo' ); ?></h2>

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
                                                'Used as a final fallback when there is no featured image and no network-specific default image. This applies to both OpenGraph and Twitter.',
                                                'beseo'
                                            ); ?>
                                        </p>
                                        <div id="be_schema_global_default_image_preview"
                                             class="be-schema-image-preview">
                                            <?php if ( $global_default_image ) : ?>
                                                <img src="<?php echo esc_url( $global_default_image ); ?>" alt="" />
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

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

                    <div class="be-schema-social-section">
                        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Safety', 'beseo' ); ?></h4>
                        <p class="description be-schema-social-description" style="margin-top:0;">
                            <?php esc_html_e( 'Use dry run to compute values but skip outputting OpenGraph and Twitter meta tags on the front end.', 'beseo' ); ?>
                        </p>
                        <label>
                            <input type="checkbox"
                                   name="be_schema_social_dry_run"
                                   value="1"
                                   <?php checked( $social_dry_run ); ?> />
                            <?php esc_html_e( 'Enable social dry run (do not output meta tags)', 'beseo' ); ?>
                        </label>
                    </div>

                    <div class="be-schema-social-section">
                        <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Image Selection Summary', 'beseo' ); ?></h4>
                        <div class="be-schema-social-mini-summary" style="margin-top:0;">
                            <p class="be-schema-social-description">
                                <?php esc_html_e(
                                    'On any given page, the plugin chooses social images in this order. This applies independently to OpenGraph and Twitter:',
                                    'beseo'
                                ); ?>
                            </p>
                            <ul>
                                <li><?php esc_html_e( 'If the page has a featured image, that is always used first.', 'beseo' ); ?></li>
                                <li><?php esc_html_e( 'For OpenGraph (Facebook, etc.): if there is no featured image, use the Facebook default image; if that is empty, use the Global default image.', 'beseo' ); ?></li>
                                <li><?php esc_html_e( 'For Twitter: if there is no featured image, use the Twitter default image; if that is empty, use the Global default image.', 'beseo' ); ?></li>
                            </ul>

                            <p class="be-schema-social-description">
                                <?php esc_html_e(
                                    'Below is a quick preview of the images that will be used when there is no featured image on a page:',
                                    'beseo'
                                ); ?>
                            </p>

                            <div class="be-schema-social-mini-summary-images">
                                <div>
                                    <strong><?php esc_html_e( 'Global Fallback Image', 'beseo' ); ?></strong>
                                    <?php if ( $global_default_image ) : ?>
                                        <div class="be-schema-image-preview">
                                            <img src="<?php echo esc_url( $global_default_image ); ?>" alt="" />
                                        </div>
                                        <code><?php echo esc_html( $global_default_image ); ?></code>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set – no final fallback.', 'beseo' ); ?></em>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <strong><?php esc_html_e( 'Facebook Default OG Image', 'beseo' ); ?></strong>
                                    <?php if ( $facebook_default_image ) : ?>
                                        <div class="be-schema-image-preview">
                                            <img src="<?php echo esc_url( $facebook_default_image ); ?>" alt="" />
                                        </div>
                                        <code><?php echo esc_html( $facebook_default_image ); ?></code>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set – OpenGraph will fall back to Global image (if any).', 'beseo' ); ?></em>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <strong><?php esc_html_e( 'Twitter Default Card Image', 'beseo' ); ?></strong>
                                    <?php if ( $twitter_default_image ) : ?>
                                        <div class="be-schema-image-preview">
                                            <img src="<?php echo esc_url( $twitter_default_image ); ?>" alt="" />
                                        </div>
                                        <code><?php echo esc_html( $twitter_default_image ); ?></code>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set – Twitter will fall back to Global image (if any).', 'beseo' ); ?></em>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <p class="be-schema-social-description" style="margin-top: 8px;">
                                <?php esc_html_e(
                                    'To see the exact image chosen for a specific URL, view that page on the front end and, if debug is enabled, check the BE_SOCIAL_DEBUG entry in your PHP error log.',
                                    'beseo'
                                ); ?>
                            </p>
                        </div>
                    </div>
                </div>

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
                                    <a href="#be-schema-facebook-status"
                                       class="be-schema-social-subtab"
                                       data-fb-tab="status">
                                        <?php esc_html_e( 'Status', 'beseo' ); ?>
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
                                </div>

                                <div id="be-schema-facebook-status" class="be-schema-social-panel">
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
                                                    <div class="be-schema-optional-controls" data-optional-scope="facebook">
                                                        <label class="screen-reader-text" for="be-schema-facebook-optional"><?php esc_html_e( 'Add optional Facebook property', 'beseo' ); ?></label>
                                                        <select id="be-schema-facebook-optional" aria-label="<?php esc_attr_e( 'Add optional Facebook property', 'beseo' ); ?>">
                                                            <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                            <option value="facebook_page_url"><?php esc_html_e( 'Facebook Page URL', 'beseo' ); ?></option>
                                                            <option value="facebook_default_image"><?php esc_html_e( 'Default Facebook OG Image', 'beseo' ); ?></option>
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
                                                                    'Optional. A public Facebook Page URL for your site or organisation.',
                                                                    'beseo'
                                                                ); ?>
                                                            </p>
                                                        </div>

                                                        <div class="be-schema-optional-field<?php echo in_array( 'facebook_default_image', $facebook_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="facebook_default_image">
                                                            <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="facebook_default_image">−</button>
                                                            <label for="be_schema_facebook_default_image" class="screen-reader-text"><?php esc_html_e( 'Default Facebook OG Image', 'beseo' ); ?></label>
                                                            <div class="be-schema-image-field">
                                                                <input type="text"
                                                                       id="be_schema_facebook_default_image"
                                                                       name="be_schema_facebook_default_image"
                                                                       value="<?php echo esc_url( $facebook_default_image ); ?>"
                                                                       class="regular-text" />
                                                                <button type="button"
                                                                        class="button be-schema-image-select"
                                                                        data-target-input="be_schema_facebook_default_image"
                                                                        data-target-preview="be_schema_facebook_default_image_preview">
                                                                    <?php esc_html_e( 'Select Image', 'beseo' ); ?>
                                                                </button>
                                                                <button type="button"
                                                                        class="button be-schema-image-clear"
                                                                        data-target-input="be_schema_facebook_default_image"
                                                                        data-target-preview="be_schema_facebook_default_image_preview">
                                                                    <?php esc_html_e( 'Clear', 'beseo' ); ?>
                                                                </button>
                                                            </div>
                                                            <p class="description be-schema-social-description">
                                                                <?php esc_html_e(
                                                                    'Used for og:image when there is no featured image on a page. If empty, OpenGraph falls back to the Global default image (if set).',
                                                                    'beseo'
                                                                ); ?>
                                                            </p>
                                                            <div id="be_schema_facebook_default_image_preview"
                                                                 class="be-schema-image-preview">
                                                                <?php if ( $facebook_default_image ) : ?>
                                                                    <img src="<?php echo esc_url( $facebook_default_image ); ?>" alt="" />
                                                                <?php endif; ?>
                                                            </div>
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
                                                                    'Optional. When set, the plugin outputs fb:app_id for Facebook debugging and analytics.',
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
                                    <a href="#be-schema-twitter-status"
                                       class="be-schema-social-subtab"
                                       data-twitter-tab="status">
                                        <?php esc_html_e( 'Status', 'beseo' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-twitter-content"
                                       class="be-schema-social-subtab"
                                       data-twitter-tab="content">
                                        <?php esc_html_e( 'Content', 'beseo' ); ?>
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
                            </div>

                            <div id="be-schema-twitter-status" class="be-schema-social-panel">
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
                                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Content', 'beseo' ); ?></h4>
                                    <table class="form-table">
                                        <tbody>
                                            <tr class="be-schema-optional-row">
                                                <th scope="row">
                                                    <?php esc_html_e( 'Optional Properties', 'beseo' ); ?>
                                                </th>
                                                <td>
                                                    <div class="be-schema-optional-controls" data-optional-scope="twitter">
                                                        <label class="screen-reader-text" for="be-schema-twitter-optional"><?php esc_html_e( 'Add optional Twitter property', 'beseo' ); ?></label>
                                                        <select id="be-schema-twitter-optional" aria-label="<?php esc_attr_e( 'Add optional Twitter property', 'beseo' ); ?>">
                                                            <option value=""><?php esc_html_e( 'Select an optional property…', 'beseo' ); ?></option>
                                                            <option value="twitter_handle"><?php esc_html_e( 'Twitter Handle (Without @)', 'beseo' ); ?></option>
                                                            <option value="twitter_default_image"><?php esc_html_e( 'Default Twitter Card Image', 'beseo' ); ?></option>
                                                            <option value="twitter_notes"><?php esc_html_e( 'Notes (Admin-Only)', 'beseo' ); ?></option>
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
                                                        <div class="be-schema-optional-field<?php echo in_array( 'twitter_handle', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_handle">
                                                            <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="twitter_handle">−</button>
                                                            <label for="be_schema_twitter_handle" class="screen-reader-text"><?php esc_html_e( 'Twitter Handle (Without @)', 'beseo' ); ?></label>
                                                            <input type="text"
                                                                   name="be_schema_twitter_handle"
                                                                   id="be_schema_twitter_handle"
                                                                   value="<?php echo esc_attr( $twitter_handle ); ?>"
                                                                   class="regular-text" />
                                                            <p class="description be-schema-social-description">
                                                                <?php esc_html_e(
                                                                    'Used to populate twitter:site and twitter:creator (with @ prefix) when Twitter Cards are enabled.',
                                                                    'beseo'
                                                                ); ?>
                                                            </p>
                                                        </div>

                                                        <div class="be-schema-optional-field<?php echo in_array( 'twitter_default_image', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_default_image">
                                                            <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="twitter_default_image">−</button>
                                                            <label for="be_schema_twitter_default_image" class="screen-reader-text"><?php esc_html_e( 'Default Twitter Card Image', 'beseo' ); ?></label>
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
                                                        </div>

                                                        <div class="be-schema-optional-field<?php echo in_array( 'twitter_notes', $twitter_optional_props, true ) ? '' : ' is-hidden'; ?>" data-optional-prop="twitter_notes">
                                                            <button type="button" class="button-link be-schema-optional-remove" data-optional-remove="twitter_notes">−</button>
                                                            <label for="be_schema_twitter_notes" class="screen-reader-text"><?php esc_html_e( 'Notes (Admin-Only)', 'beseo' ); ?></label>
                                                            <textarea
                                                                name="be_schema_twitter_notes"
                                                                id="be_schema_twitter_notes"
                                                                rows="4"
                                                                class="large-text code"><?php echo esc_textarea( $twitter_notes ); ?></textarea>
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

                            <div id="be-schema-twitter-tools" class="be-schema-social-panel">
                                <div class="be-schema-social-section">
                                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Tools', 'beseo' ); ?></h4>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Use Twitter Card Validator to preview and clear cache after changing images or titles.',
                                            'beseo'
                                        ); ?>
                                        <br />
                                        <a href="https://cards-dev.twitter.com/validator"
                                           target="_blank" rel="noopener noreferrer">
                                            <?php esc_html_e( 'Open Twitter Card Validator', 'beseo' ); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                    scope: 'facebook',
                    containerId: 'be-schema-facebook-optional-fields',
                    selectId: 'be-schema-facebook-optional',
                    hiddenInputId: 'be_schema_facebook_optional',
                    props: ['facebook_page_url', 'facebook_default_image', 'facebook_app_id', 'facebook_notes'],
                    singletons: ['facebook_page_url', 'facebook_default_image', 'facebook_app_id', 'facebook_notes'],
                    previewIds: {
                        facebook_default_image: 'be_schema_facebook_default_image_preview'
                    },
                    propHasValue: function (prop) {
                        var map = {
                            facebook_page_url: document.getElementById('be_schema_facebook_page_url'),
                            facebook_app_id: document.getElementById('be_schema_facebook_app_id'),
                            facebook_notes: document.getElementById('be_schema_facebook_notes')
                        };
                        if (prop === 'facebook_default_image') {
                            var image = document.getElementById('be_schema_facebook_default_image');
                            return !! (image && image.value.trim().length > 0);
                        }
                        var input = map[prop];
                        return !! (input && input.value.trim().length > 0);
                    }
                });

                initOptionalProperties({
                    scope: 'twitter',
                    containerId: 'be-schema-twitter-optional-fields',
                    selectId: 'be-schema-twitter-optional',
                    hiddenInputId: 'be_schema_twitter_optional',
                    props: ['twitter_handle', 'twitter_default_image', 'twitter_notes'],
                    singletons: ['twitter_handle', 'twitter_default_image', 'twitter_notes'],
                    previewIds: {
                        twitter_default_image: 'be_schema_twitter_default_image_preview'
                    },
                    propHasValue: function (prop) {
                        var map = {
                            twitter_handle: document.getElementById('be_schema_twitter_handle'),
                            twitter_notes: document.getElementById('be_schema_twitter_notes')
                        };
                        if (prop === 'twitter_default_image') {
                            var img = document.getElementById('be_schema_twitter_default_image');
                            return !! (img && img.value.trim().length > 0);
                        }
                        var input = map[prop];
                        return !! (input && input.value.trim().length > 0);
                    }
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
            });
        </script>
    </div>
    <?php
}
