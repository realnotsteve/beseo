<?php
/**
 * Social Media Admin Page
 *
 * Submenu: BE SEO → Social Media
 *
 * Tabs:
 * - Settings  (global toggles + global default image)
 * - Facebook  (OG defaults)
 * - Twitter   (Twitter Card defaults)
 *
 * Stores settings in the be_schema_social_settings option.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get Social Media settings for OG/Twitter (admin side).
 *
 * Keep defaults in sync with:
 * - includes/engine/core-social.php -> be_schema_social_get_settings()
 *
 * @return array
 */
function be_schema_engine_get_social_settings() {
    $defaults = array(
        // Global settings.
        'social_enable_og'       => '0',
        'social_enable_twitter'  => '0',
        'social_default_image'   => '',

        // Facebook.
        'facebook_page_url'      => '',
        'facebook_default_image' => '',
        'facebook_app_id'        => '',
        'facebook_notes'         => '',

        // Twitter.
        'twitter_handle'         => '',
        'twitter_card_type'      => 'summary_large_image',
        'twitter_default_image'  => '',
        'twitter_notes'          => '',
    );

    $saved = get_option( 'be_schema_social_settings', array() );

    if ( ! is_array( $saved ) ) {
        $saved = array();
    }

    return wp_parse_args( $saved, $defaults );
}

/**
 * Save Social Media settings from the admin form.
 */
function be_schema_engine_save_social_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if (
        ! isset( $_POST['be_schema_social_settings_nonce'] ) ||
        ! wp_verify_nonce( $_POST['be_schema_social_settings_nonce'], 'be_schema_social_save_settings' )
    ) {
        return;
    }

    $settings = be_schema_engine_get_social_settings();

    // Global toggles.
    $settings['social_enable_og']      = isset( $_POST['be_schema_social_enable_og'] ) ? '1' : '0';
    $settings['social_enable_twitter'] = isset( $_POST['be_schema_social_enable_twitter'] ) ? '1' : '0';

    // Global default image (URL).
    $settings['social_default_image'] = isset( $_POST['be_schema_social_default_image'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_social_default_image'] ) )
        : '';

    // Facebook fields.
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

    // Twitter fields.
    $settings['twitter_handle'] = isset( $_POST['be_schema_twitter_handle'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_twitter_handle'] ) )
        : '';

    $allowed_card_types  = array( 'summary', 'summary_large_image' );
    $submitted_card_type = isset( $_POST['be_schema_twitter_card_type'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_twitter_card_type'] ) )
        : 'summary_large_image';

    if ( ! in_array( $submitted_card_type, $allowed_card_types, true ) ) {
        $submitted_card_type = 'summary_large_image';
    }

    $settings['twitter_card_type'] = $submitted_card_type;

    $settings['twitter_default_image'] = isset( $_POST['be_schema_twitter_default_image'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_twitter_default_image'] ) )
        : '';

    $settings['twitter_notes'] = isset( $_POST['be_schema_twitter_notes'] )
        ? wp_kses_post( wp_unslash( $_POST['be_schema_twitter_notes'] ) )
        : '';

    update_option( 'be_schema_social_settings', $settings );
}

/**
 * Render the "Social Media" admin page.
 */
function be_schema_engine_render_social_media_page() {

    // Enqueue media library for image pickers.
    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

    // Save on POST.
    if ( isset( $_POST['be_schema_social_settings_submitted'] ) ) {
        be_schema_engine_save_social_settings();
    }

    $settings = be_schema_engine_get_social_settings();

    $social_enable_og      = ( isset( $settings['social_enable_og'] ) && '1' === $settings['social_enable_og'] );
    $social_enable_twitter = ( isset( $settings['social_enable_twitter'] ) && '1' === $settings['social_enable_twitter'] );

    $social_default_image = isset( $settings['social_default_image'] ) ? $settings['social_default_image'] : '';

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
                'Configure global defaults for OpenGraph and Twitter Cards. This panel only controls social meta tags and does not change JSON-LD schema.',
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
                max-width: 720px;
            }

            .be-schema-social-image-field {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 10px;
            }

            .be-schema-social-image-field input[type="text"] {
                width: 360px;
            }

            .be-schema-social-image-preview {
                margin-top: 8px;
            }

            .be-schema-social-image-preview img {
                max-width: 150px;
                height: auto;
                border: 1px solid #ccd0d4;
                padding: 2px;
                background: #fff;
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
        </style>

        <p>
            <span class="be-schema-social-status-pill <?php echo $social_enable_og ? '' : 'off'; ?>">
                <?php echo $social_enable_og ? esc_html__( 'OpenGraph: ON', 'be-schema-engine' ) : esc_html__( 'OpenGraph: OFF', 'be-schema-engine' ); ?>
            </span>
            <span class="be-schema-social-status-pill <?php echo $social_enable_twitter ? '' : 'off'; ?>">
                <?php echo $social_enable_twitter ? esc_html__( 'Twitter Cards: ON', 'be-schema-engine' ) : esc_html__( 'Twitter Cards: OFF', 'be-schema-engine' ); ?>
            </span>
        </p>

        <form method="post">
            <?php wp_nonce_field( 'be_schema_social_save_settings', 'be_schema_social_settings_nonce' ); ?>
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

                <!-- Settings tab -->
                <div id="be-schema-social-tab-settings"
                     class="be-schema-social-tab-panel be-schema-social-tab-panel-active">
                    <h2><?php esc_html_e( 'Global Settings', 'be-schema-engine' ); ?></h2>

                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Enable OpenGraph tags', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="be_schema_social_enable_og"
                                               value="1"
                                               <?php checked( $social_enable_og ); ?> />
                                        <?php esc_html_e(
                                            'Output og:* meta tags in the document head.',
                                            'be-schema-engine'
                                        ); ?>
                                    </label>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'When enabled, the Social engine outputs OpenGraph tags using these settings as defaults. Per-page overrides can be added in a future version.',
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
                                               name="be_schema_social_enable_twitter"
                                               value="1"
                                               <?php checked( $social_enable_twitter ); ?> />
                                        <?php esc_html_e(
                                            'Output twitter:* meta tags in the document head.',
                                            'be-schema-engine'
                                        ); ?>
                                    </label>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'When enabled, the Social engine outputs Twitter Card meta tags using these settings as defaults.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Default fallback image', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <div class="be-schema-social-image-field">
                                        <input type="text"
                                               id="be_schema_social_default_image"
                                               name="be_schema_social_default_image"
                                               value="<?php echo esc_url( $social_default_image ); ?>"
                                               class="regular-text" />
                                        <button type="button"
                                                class="button be-schema-social-image-select"
                                                data-target-input="be_schema_social_default_image"
                                                data-target-preview="be_schema_social_default_image_preview">
                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                        </button>
                                        <button type="button"
                                                class="button be-schema-social-image-clear"
                                                data-target-input="be_schema_social_default_image"
                                                data-target-preview="be_schema_social_default_image_preview">
                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                        </button>
                                    </div>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Used as a global fallback for OpenGraph and Twitter Cards when no featured image and no network-specific default image are available.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                    <div id="be_schema_social_default_image_preview"
                                         class="be-schema-social-image-preview">
                                        <?php if ( $social_default_image ) : ?>
                                            <img src="<?php echo esc_url( $social_default_image ); ?>" alt="" />
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Facebook tab -->
                <div id="be-schema-social-tab-facebook" class="be-schema-social-tab-panel">
                    <h2><?php esc_html_e( 'Facebook / OpenGraph', 'be-schema-engine' ); ?></h2>

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
                                            'Optional. Your primary Facebook Page URL, used for reference and, in the future, for integrations such as article:publisher.',
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
                                    <div class="be-schema-social-image-field">
                                        <input type="text"
                                               id="be_schema_facebook_default_image"
                                               name="be_schema_facebook_default_image"
                                               value="<?php echo esc_url( $facebook_default_image ); ?>"
                                               class="regular-text" />
                                        <button type="button"
                                                class="button be-schema-social-image-select"
                                                data-target-input="be_schema_facebook_default_image"
                                                data-target-preview="be_schema_facebook_default_image_preview">
                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                        </button>
                                        <button type="button"
                                                class="button be-schema-social-image-clear"
                                                data-target-input="be_schema_facebook_default_image"
                                                data-target-preview="be_schema_facebook_default_image_preview">
                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                        </button>
                                    </div>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Optional. Preferred default image when Facebook reads your OpenGraph tags. If empty, the global fallback image is used.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                    <div id="be_schema_facebook_default_image_preview"
                                         class="be-schema-social-image-preview">
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
                                            'Optional. If set, the social engine can emit fb:app_id in OpenGraph meta for better integration with Facebook tools.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Notes', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <textarea
                                        name="be_schema_facebook_notes"
                                        rows="4"
                                        class="large-text"><?php echo esc_textarea( $facebook_notes ); ?></textarea>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Optional. For your own reference only. This is not used in any output.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Twitter tab -->
                <div id="be-schema-social-tab-twitter" class="be-schema-social-tab-panel">
                    <h2><?php esc_html_e( 'Twitter / X – Cards', 'be-schema-engine' ); ?></h2>

                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Twitter handle', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <span>@</span>
                                    <input type="text"
                                           name="be_schema_twitter_handle"
                                           value="<?php echo esc_attr( $twitter_handle ); ?>"
                                           class="regular-text"
                                           style="max-width: 240px; margin-left: 4px;" />
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Optional. Your Twitter username without the @. The social engine can use this to populate twitter:site and twitter:creator.',
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
                                    <fieldset>
                                        <label>
                                            <input type="radio"
                                                   name="be_schema_twitter_card_type"
                                                   value="summary_large_image"
                                                   <?php checked( $twitter_card_type, 'summary_large_image' ); ?> />
                                            <?php esc_html_e( 'Summary with large image (summary_large_image)', 'be-schema-engine' ); ?>
                                        </label>
                                        <br />
                                        <label>
                                            <input type="radio"
                                                   name="be_schema_twitter_card_type"
                                                   value="summary"
                                                   <?php checked( $twitter_card_type, 'summary' ); ?> />
                                            <?php esc_html_e( 'Summary (small image)', 'be-schema-engine' ); ?>
                                        </label>
                                    </fieldset>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Controls the default value of twitter:card. Per-page overrides can be added later if needed.',
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
                                    <div class="be-schema-social-image-field">
                                        <input type="text"
                                               id="be_schema_twitter_default_image"
                                               name="be_schema_twitter_default_image"
                                               value="<?php echo esc_url( $twitter_default_image ); ?>"
                                               class="regular-text" />
                                        <button type="button"
                                                class="button be-schema-social-image-select"
                                                data-target-input="be_schema_twitter_default_image"
                                                data-target-preview="be_schema_twitter_default_image_preview">
                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                        </button>
                                        <button type="button"
                                                class="button be-schema-social-image-clear"
                                                data-target_input="be_schema_twitter_default_image"
                                                data-target-preview="be_schema_twitter_default_image_preview">
                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                        </button>
                                    </div>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Optional. Preferred default image for Twitter Cards. If empty, the global fallback image is used.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                    <div id="be_schema_twitter_default_image_preview"
                                         class="be-schema-social-image-preview">
                                        <?php if ( $twitter_default_image ) : ?>
                                            <img src="<?php echo esc_url( $twitter_default_image ); ?>" alt="" />
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Notes', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <textarea
                                        name="be_schema_twitter_notes"
                                        rows="4"
                                        class="large-text"><?php echo esc_textarea( $twitter_notes ); ?></textarea>
                                    <p class="description be-schema-social-description">
                                        <?php esc_html_e(
                                            'Optional. For your own reference only. This is not used in any output.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <?php submit_button( __( 'Save Social Media Settings', 'be-schema-engine' ) ); ?>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Tabs
                var navLinks = document.querySelectorAll('.be-schema-social-tab-link');
                var panels   = document.querySelectorAll('.be-schema-social-tab-panel');

                function activateSocialTab(tabKey) {
                    navLinks.forEach(function (link) {
                        if (link.getAttribute('data-social-tab') === tabKey) {
                            link.classList.add('be-schema-social-tab-active');
                        } else {
                            link.classList.remove('be-schema-social-tab-active');
                        }
                    });

                    panels.forEach(function (panel) {
                        if (panel.id === 'be-schema-social-tab-' + tabKey) {
                            panel.classList.add('be-schema-social-tab-panel-active');
                        } else {
                            panel.classList.remove('be-schema-social-tab-panel-active');
                        }
                    });
                }

                navLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-social-tab');
                        activateSocialTab(tabKey);
                    });
                });

                // Media pickers
                var selectButtons = document.querySelectorAll('.be-schema-social-image-select');
                var clearButtons  = document.querySelectorAll('.be-schema-social-image-clear');

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
                        var url        = attachment.url || '';

                        var input   = document.getElementById(targetInputId);
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
                        var targetInputId   = button.getAttribute('data-target-input');
                        var targetPreviewId = button.getAttribute('data-target-preview');
                        if (targetInputId && targetPreviewId) {
                            openMediaFrame(targetInputId, targetPreviewId);
                        }
                    });
                });

                clearButtons.forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        var targetInputId   = button.getAttribute('data-target-input');
                        var targetPreviewId = button.getAttribute('data-target-preview');

                        var input   = document.getElementById(targetInputId);
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