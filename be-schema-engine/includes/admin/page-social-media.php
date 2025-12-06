<?php
/**
 * Social Media Admin Page
 *
 * Submenu: BE SEO → Social Media
 *
 * Tabs:
 *  - Settings (global enable flags + global fallback image + tiny image summary)
 *  - Facebook (FB page, FB default image, app id, notes)
 *  - Twitter (handle, card type, Twitter default image, notes)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Save BE Social Media settings.
 *
 * Option name: be_schema_social_settings
 */
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

    // SETTINGS TAB -------------------------------.

    // Global enables.
    $settings['og_enabled']      = isset( $_POST['be_schema_og_enabled'] ) ? '1' : '0';
    $settings['twitter_enabled'] = isset( $_POST['be_schema_twitter_enabled'] ) ? '1' : '0';

    // Global default fallback image.
    $settings['global_default_image'] = isset( $_POST['be_schema_global_default_image'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_global_default_image'] ) )
        : '';

    // FACEBOOK TAB ------------------------------.

    $settings['facebook_page_url'] = isset( $_POST['be_schema_facebook_page_url'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_facebook_page_url'] ) )
        : '';

    $settings['facebook_default_image'] = isset( $_POST['be_schema_facebook_default_image'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_facebook_default_image'] ) )
        : '';

    $settings['facebook_app_id'] = isset( $_POST['be_schema_facebook_app_id'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_facebook_app_id'] ) )
        : '';

    $settings['facebook_notes'] = isset( $_POST['be_schema_facebook_notes'] )
        ? wp_kses_post( wp_unslash( $_POST['be_schema_facebook_notes'] ) )
        : '';

    // TWITTER TAB -------------------------------.

    $settings['twitter_handle'] = isset( $_POST['be_schema_twitter_handle'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_twitter_handle'] ) )
        : '';

    $settings['twitter_card_type'] = isset( $_POST['be_schema_twitter_card_type'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_twitter_card_type'] ) )
        : 'summary_large_image';

    $settings['twitter_default_image'] = isset( $_POST['be_schema_twitter_default_image'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_twitter_default_image'] ) )
        : '';

    $settings['twitter_notes'] = isset( $_POST['be_schema_twitter_notes'] )
        ? wp_kses_post( wp_unslash( $_POST['be_schema_twitter_notes'] ) )
        : '';

    update_option( 'be_schema_social_settings', $settings );
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

    // Simple access helpers.
    $og_enabled      = ! empty( $settings['og_enabled'] ) && '1' === $settings['og_enabled'];
    $twitter_enabled = ! empty( $settings['twitter_enabled'] ) && '1' === $settings['twitter_enabled'];

    $global_default_image   = isset( $settings['global_default_image'] ) ? $settings['global_default_image'] : '';
    $facebook_page_url      = isset( $settings['facebook_page_url'] ) ? $settings['facebook_page_url'] : '';
    $facebook_default_image = isset( $settings['facebook_default_image'] ) ? $settings['facebook_default_image'] : '';
    $facebook_app_id        = isset( $settings['facebook_app_id'] ) ? $settings['facebook_app_id'] : '';
    $facebook_notes         = isset( $settings['facebook_notes'] ) ? $settings['facebook_notes'] : '';

    $twitter_handle        = isset( $settings['twitter_handle'] ) ? $settings['twitter_handle'] : '';
    $twitter_card_type     = isset( $settings['twitter_card_type'] ) ? $settings['twitter_card_type'] : 'summary_large_image';
    $twitter_default_image = isset( $settings['twitter_default_image'] ) ? $settings['twitter_default_image'] : '';
    $twitter_notes         = isset( $settings['twitter_notes'] ) ? $settings['twitter_notes'] : '';

    ?>
    <div class="wrap be-schema-engine-wrap be-schema-social-wrap">
        <h1><?php esc_html_e( 'BE SEO – Social Media', 'be-schema-engine' ); ?></h1>

        <p class="description">
            <?php esc_html_e(
                'Configure OpenGraph and Twitter Card defaults. This module controls social meta only; it does not change JSON-LD schema or sameAs arrays.',
                'be-schema-engine'
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
        </style>

        <p>
            <span class="be-schema-social-status-pill <?php echo $og_enabled ? '' : 'off'; ?>">
                <?php echo $og_enabled ? esc_html__( 'OpenGraph: ON', 'be-schema-engine' ) : esc_html__( 'OpenGraph: OFF', 'be-schema-engine' ); ?>
            </span>
            <span class="be-schema-social-status-pill <?php echo $twitter_enabled ? '' : 'off'; ?>">
                <?php echo $twitter_enabled ? esc_html__( 'Twitter Cards: ON', 'be-schema-engine' ) : esc_html__( 'Twitter Cards: OFF', 'be-schema-engine' ); ?>
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
                            <?php esc_html_e( 'Settings', 'be-schema-engine' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#be-schema-social-tab-facebook"
                           class="be-schema-social-tab-link"
                           data-social-tab="facebook">
                            <?php esc_html_e( 'Facebook', 'be-schema-engine' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#be-schema-social-tab-twitter"
                           class="be-schema-social-tab-link"
                           data-social-tab="twitter">
                            <?php esc_html_e( 'Twitter', 'be-schema-engine' ); ?>
                        </a>
                    </li>
                </ul>

                <!-- SETTINGS TAB -->
                <div id="be-schema-social-tab-settings"
                     class="be-schema-social-tab-panel be-schema-social-tab-panel-active">
                    <h2><?php esc_html_e( 'Global Social Settings', 'be-schema-engine' ); ?></h2>

                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Enable OpenGraph', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="be_schema_og_enabled"
                                               value="1"
                                               <?php checked( $og_enabled ); ?> />
                                        <?php esc_html_e(
                                            'Output og:* tags for supported pages.',
                                            'be-schema-engine'
                                        ); ?>
                                    </label>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'When enabled, the plugin will output OpenGraph tags for pages and posts using the rules described below.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Enable Twitter Cards', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="be_schema_twitter_enabled"
                                               value="1"
                                               <?php checked( $twitter_enabled ); ?> />
                                        <?php esc_html_e(
                                            'Output twitter:* tags for supported pages.',
                                            'be-schema-engine'
                                        ); ?>
                                    </label>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'When enabled, the plugin will output Twitter Card tags for pages and posts using the rules described below.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Global default image', 'be-schema-engine' ); ?>
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
                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                        </button>
                                        <button type="button"
                                                class="button be-schema-image-clear"
                                                data-target-input="be_schema_global_default_image"
                                                data-target-preview="be_schema_global_default_image_preview">
                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                        </button>
                                    </div>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Used as a final fallback when there is no featured image and no network-specific default image. This applies to both OpenGraph and Twitter.',
                                            'be-schema-engine'
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

                    <!-- Tiny "what image did we pick?" style summary -->
                    <div class="be-schema-social-mini-summary">
                        <h3><?php esc_html_e( 'Image selection summary', 'be-schema-engine' ); ?></h3>
                        <p class="be-schema-social-description">
                            <?php esc_html_e(
                                'On any given page, the plugin chooses social images in this order. This applies independently to OpenGraph and Twitter:',
                                'be-schema-engine'
                            ); ?>
                        </p>
                        <ul>
                            <li><?php esc_html_e( 'If the page has a featured image, that is always used first.', 'be-schema-engine' ); ?></li>
                            <li><?php esc_html_e( 'For OpenGraph (Facebook, etc.): if there is no featured image, use the Facebook default image; if that is empty, use the Global default image.', 'be-schema-engine' ); ?></li>
                            <li><?php esc_html_e( 'For Twitter: if there is no featured image, use the Twitter default image; if that is empty, use the Global default image.', 'be-schema-engine' ); ?></li>
                        </ul>

                        <p class="be-schema-social-description">
                            <?php esc_html_e(
                                'Below is a quick preview of the images that will be used when there is no featured image on a page:',
                                'be-schema-engine'
                            ); ?>
                        </p>

                        <div class="be-schema-social-mini-summary-images">
                            <div>
                                <strong><?php esc_html_e( 'Global fallback image', 'be-schema-engine' ); ?></strong>
                                <?php if ( $global_default_image ) : ?>
                                    <div class="be-schema-image-preview">
                                        <img src="<?php echo esc_url( $global_default_image ); ?>" alt="" />
                                    </div>
                                    <code><?php echo esc_html( $global_default_image ); ?></code>
                                <?php else : ?>
                                    <em><?php esc_html_e( 'Not set – no final fallback.', 'be-schema-engine' ); ?></em>
                                <?php endif; ?>
                            </div>

                            <div>
                                <strong><?php esc_html_e( 'Facebook default OG image', 'be-schema-engine' ); ?></strong>
                                <?php if ( $facebook_default_image ) : ?>
                                    <div class="be-schema-image-preview">
                                        <img src="<?php echo esc_url( $facebook_default_image ); ?>" alt="" />
                                    </div>
                                    <code><?php echo esc_html( $facebook_default_image ); ?></code>
                                <?php else : ?>
                                    <em><?php esc_html_e( 'Not set – OpenGraph will fall back to Global image (if any).', 'be-schema-engine' ); ?></em>
                                <?php endif; ?>
                            </div>

                            <div>
                                <strong><?php esc_html_e( 'Twitter default card image', 'be-schema-engine' ); ?></strong>
                                <?php if ( $twitter_default_image ) : ?>
                                    <div class="be-schema-image-preview">
                                        <img src="<?php echo esc_url( $twitter_default_image ); ?>" alt="" />
                                    </div>
                                    <code><?php echo esc_html( $twitter_default_image ); ?></code>
                                <?php else : ?>
                                    <em><?php esc_html_e( 'Not set – Twitter will fall back to Global image (if any).', 'be-schema-engine' ); ?></em>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p class="be-schema-social-description" style="margin-top: 8px;">
                            <?php esc_html_e(
                                'To see the exact image chosen for a specific URL, view that page on the front end and, if debug is enabled, check the BE_SOCIAL_DEBUG entry in your PHP error log.',
                                'be-schema-engine'
                            ); ?>
                        </p>
                    </div>
                </div>

                <!-- FACEBOOK TAB -->
                <div id="be-schema-social-tab-facebook" class="be-schema-social-tab-panel">
                    <h2><?php esc_html_e( 'Facebook Settings', 'be-schema-engine' ); ?></h2>

                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Facebook Page URL', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <input type="text"
                                           name="be_schema_facebook_page_url"
                                           value="<?php echo esc_url( $facebook_page_url ); ?>"
                                           class="regular-text" />
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Optional. A public Facebook Page URL for your site or organisation.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Default Facebook OG image', 'be-schema-engine' ); ?>
                                </th>
                                <td>
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
                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                        </button>
                                        <button type="button"
                                                class="button be-schema-image-clear"
                                                data-target-input="be_schema_facebook_default_image"
                                                data-target-preview="be_schema_facebook_default_image_preview">
                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                        </button>
                                    </div>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Used for og:image when there is no featured image on a page. If empty, OpenGraph falls back to the Global default image (if set).',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                    <div id="be_schema_facebook_default_image_preview"
                                         class="be-schema-image-preview">
                                        <?php if ( $facebook_default_image ) : ?>
                                            <img src="<?php echo esc_url( $facebook_default_image ); ?>" alt="" />
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Facebook App ID', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <input type="text"
                                           name="be_schema_facebook_app_id"
                                           value="<?php echo esc_attr( $facebook_app_id ); ?>"
                                           class="regular-text" />
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Optional. When set, the plugin outputs fb:app_id for Facebook debugging and analytics.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Notes (admin-only)', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <textarea
                                        name="be_schema_facebook_notes"
                                        rows="4"
                                        class="large-text code"><?php echo esc_textarea( $facebook_notes ); ?></textarea>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Free-form notes for your own reference. This is never output on the front end.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- TWITTER TAB -->
                <div id="be-schema-social-tab-twitter" class="be-schema-social-tab-panel">
                    <h2><?php esc_html_e( 'Twitter Settings', 'be-schema-engine' ); ?></h2>

                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Twitter handle (without @)', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <input type="text"
                                           name="be_schema_twitter_handle"
                                           value="<?php echo esc_attr( $twitter_handle ); ?>"
                                           class="regular-text" />
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Used to populate twitter:site and twitter:creator (with @ prefix) when Twitter Cards are enabled.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Default card type', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <select name="be_schema_twitter_card_type">
                                        <option value="summary"
                                            <?php selected( $twitter_card_type, 'summary' ); ?>>
                                            <?php esc_html_e( 'summary', 'be-schema-engine' ); ?>
                                        </option>
                                        <option value="summary_large_image"
                                            <?php selected( $twitter_card_type, 'summary_large_image' ); ?>>
                                            <?php esc_html_e( 'summary_large_image', 'be-schema-engine' ); ?>
                                        </option>
                                    </select>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'This value is used for twitter:card on pages that do not specify a per-post override.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Default Twitter card image', 'be-schema-engine' ); ?>
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
                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                        </button>
                                        <button type="button"
                                                class="button be-schema-image-clear"
                                                data-target-input="be_schema_twitter_default_image"
                                                data-target-preview="be_schema_twitter_default_image_preview">
                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                        </button>
                                    </div>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Used for twitter:image when there is no featured image on a page. If empty, Twitter falls back to the Global default image (if set).',
                                            'be-schema-engine'
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
                                    <?php esc_html_e( 'Notes (admin-only)', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <textarea
                                        name="be_schema_twitter_notes"
                                        rows="4"
                                        class="large-text code"><?php echo esc_textarea( $twitter_notes ); ?></textarea>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Free-form notes for your own reference. This is never output on the front end.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <?php submit_button( __( 'Save Social Settings', 'be-schema-engine' ) ); ?>
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

                // Media pickers.
                var selectButtons = document.querySelectorAll('.be-schema-image-select');
                var clearButtons = document.querySelectorAll('.be-schema-image-clear');

                function openMediaFrame(targetInputId, targetPreviewId) {
                    if (typeof wp === 'undefined' || ! wp.media) {
                        return;
                    }

                    var frame = wp.media({
                        title: '<?php echo esc_js( __( 'Select image', 'be-schema-engine' ) ); ?>',
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