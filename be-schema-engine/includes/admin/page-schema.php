<?php
/**
 * Schema Admin Page
 *
 * Submenu: BE SEO → Schema
 *
 * Tabs:
 *  - Settings  (global plugin toggles, Elementor toggle, debug, snapshot)
 *  - Website   (site entities: Global / Person / Organisation / Publisher)
 *
 * User test
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Save main BE Schema Engine settings.
 *
 * Option name: be_schema_engine_settings
 */
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

    // Global toggles.
    $settings['enabled']           = isset( $_POST['be_schema_enabled'] ) ? '1' : '0';
    $settings['elementor_enabled'] = isset( $_POST['be_schema_elementor_enabled'] ) ? '1' : '0';
    $settings['debug']             = isset( $_POST['be_schema_debug'] ) ? '1' : '0';

    // Person.
    $settings['person_enabled'] = isset( $_POST['be_schema_person_enabled'] ) ? '1' : '0';

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

    $settings['org_url'] = isset( $_POST['be_schema_org_url'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_org_url'] ) )
        : '';

    // Shared site logo.
    $settings['org_logo'] = isset( $_POST['be_schema_org_logo'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_org_logo'] ) )
        : '';

    // WebSite featured images (16:9, 4:3, 1:1).
    $settings['website_image_16_9'] = isset( $_POST['be_schema_website_image_16_9'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_website_image_16_9'] ) )
        : '';

    $settings['website_image_4_3'] = isset( $_POST['be_schema_website_image_4_3'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_website_image_4_3'] ) )
        : '';

    $settings['website_image_1_1'] = isset( $_POST['be_schema_website_image_1_1'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_website_image_1_1'] ) )
        : '';

    // Publisher.
    $settings['publisher_enabled'] = isset( $_POST['be_schema_publisher_enabled'] ) ? '1' : '0';

    $settings['copyright_year'] = isset( $_POST['be_schema_copyright_year'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_copyright_year'] ) )
        : '';

    $settings['license_url'] = isset( $_POST['be_schema_license_url'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_license_url'] ) )
        : '';

    $settings['publishing_principles'] = isset( $_POST['be_schema_publishing_principles'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_publishing_principles'] ) )
        : '';

    $settings['corrections_policy'] = isset( $_POST['be_schema_corrections_policy'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_corrections_policy'] ) )
        : '';

    $settings['ownership_funding'] = isset( $_POST['be_schema_ownership_funding'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_ownership_funding'] ) )
        : '';

    $settings['publisher_custom_name'] = isset( $_POST['be_schema_publisher_custom_name'] )
        ? sanitize_text_field( wp_unslash( $_POST['be_schema_publisher_custom_name'] ) )
        : '';

    $settings['publisher_custom_url'] = isset( $_POST['be_schema_publisher_custom_url'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_publisher_custom_url'] ) )
        : '';

    $settings['publisher_custom_logo'] = isset( $_POST['be_schema_publisher_custom_logo'] )
        ? esc_url_raw( wp_unslash( $_POST['be_schema_publisher_custom_logo'] ) )
        : '';

    update_option( 'be_schema_engine_settings', $settings );
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

    // Person.
    $person_enabled          = ! empty( $settings['person_enabled'] ) && '1' === $settings['person_enabled'];
    $person_image_url        = isset( $settings['person_image_url'] ) ? $settings['person_image_url'] : '';
    $person_honorific_prefix = isset( $settings['person_honorific_prefix'] ) ? $settings['person_honorific_prefix'] : '';
    $person_honorific_suffix = isset( $settings['person_honorific_suffix'] ) ? $settings['person_honorific_suffix'] : '';
    $person_sameas_raw       = isset( $settings['person_sameas_raw'] ) ? $settings['person_sameas_raw'] : '';

    // Organisation.
    $organization_enabled = ! empty( $settings['organization_enabled'] ) && '1' === $settings['organization_enabled'];
    $org_name             = isset( $settings['org_name'] ) ? $settings['org_name'] : '';
    $org_legal_name       = isset( $settings['org_legal_name'] ) ? $settings['org_legal_name'] : '';
    $org_url              = isset( $settings['org_url'] ) ? $settings['org_url'] : '';
    $org_logo             = isset( $settings['org_logo'] ) ? $settings['org_logo'] : '';

    // WebSite featured images.
    $website_image_16_9 = isset( $settings['website_image_16_9'] ) ? $settings['website_image_16_9'] : '';
    $website_image_4_3  = isset( $settings['website_image_4_3'] ) ? $settings['website_image_4_3'] : '';
    $website_image_1_1  = isset( $settings['website_image_1_1'] ) ? $settings['website_image_1_1'] : '';

    // Publisher.
    $publisher_enabled     = ! empty( $settings['publisher_enabled'] ) && '1' === $settings['publisher_enabled'];
    $copyright_year        = isset( $settings['copyright_year'] ) ? $settings['copyright_year'] : '';
    $license_url           = isset( $settings['license_url'] ) ? $settings['license_url'] : '';
    $publishing_principles = isset( $settings['publishing_principles'] ) ? $settings['publishing_principles'] : '';
    $corrections_policy    = isset( $settings['corrections_policy'] ) ? $settings['corrections_policy'] : '';
    $ownership_funding     = isset( $settings['ownership_funding'] ) ? $settings['ownership_funding'] : '';
    $publisher_custom_name = isset( $settings['publisher_custom_name'] ) ? $settings['publisher_custom_name'] : '';
    $publisher_custom_url  = isset( $settings['publisher_custom_url'] ) ? $settings['publisher_custom_url'] : '';
    $publisher_custom_logo = isset( $settings['publisher_custom_logo'] ) ? $settings['publisher_custom_logo'] : '';

    // Constants / overrides for messaging.
    $const_disable_all       = defined( 'BE_SCHEMA_DISABLE_ALL' ) && BE_SCHEMA_DISABLE_ALL;
    $const_disable_elementor = defined( 'BE_SCHEMA_DISABLE_ELEMENTOR' ) && BE_SCHEMA_DISABLE_ELEMENTOR;
    $const_debug             = defined( 'BE_SCHEMA_DEBUG' ) && BE_SCHEMA_DEBUG;

    ?>
    <div class="wrap be-schema-engine-wrap be-schema-schema-wrap">
        <h1><?php esc_html_e( 'BE SEO – Schema', 'be-schema-engine' ); ?></h1>

        <p class="description">
            <?php esc_html_e(
                'Configure site-wide schema behavior and site entities for JSON-LD output. Page-level schema is still opt-in via Elementor controls and per-page settings.',
                'be-schema-engine'
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
                max-width: 760px;
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

            .be-schema-conditional-block {
                border-left: 3px solid #ccd0d4;
                padding-left: 12px;
                margin-top: 8px;
            }

            .be-schema-conditional-block.is-disabled {
                opacity: 0.55;
            }

            .be-schema-settings-snapshot {
                max-width: 800px;
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
        </style>

        <p>
            <span class="be-schema-status-pill <?php echo $enabled ? '' : 'off'; ?>">
                <?php echo $enabled ? esc_html__( 'Schema engine: ON', 'be-schema-engine' ) : esc_html__( 'Schema engine: OFF', 'be-schema-engine' ); ?>
            </span>
            <span class="be-schema-status-pill <?php echo $elementor_enabled ? '' : 'off'; ?>">
                <?php echo $elementor_enabled ? esc_html__( 'Elementor schema: ON', 'be-schema-engine' ) : esc_html__( 'Elementor schema: OFF', 'be-schema-engine' ); ?>
            </span>
            <span class="be-schema-status-pill <?php echo $debug_enabled ? '' : 'off'; ?>">
                <?php echo $debug_enabled ? esc_html__( 'Plugin debug: ON', 'be-schema-engine' ) : esc_html__( 'Plugin debug: OFF', 'be-schema-engine' ); ?>
            </span>
        </p>

        <p class="description be-schema-description">
            <?php esc_html_e(
                'Schema is still controlled by several layers: global plugin settings, optional overrides in wp-config.php, per-page disable meta, and Elementor page controls. This page configures site-wide defaults.',
                'be-schema-engine'
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
                            <?php esc_html_e( 'Settings', 'be-schema-engine' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#be-schema-tab-website"
                           class="be-schema-tab-link"
                           data-schema-tab="website">
                            <?php esc_html_e( 'Website', 'be-schema-engine' ); ?>
                        </a>
                    </li>
                </ul>

                <!-- SETTINGS TAB -->
                <div id="be-schema-tab-settings"
                     class="be-schema-tab-panel be-schema-tab-panel-active">
                    <h2><?php esc_html_e( 'Global Settings', 'be-schema-engine' ); ?></h2>

                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Enable schema engine', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="be_schema_enabled"
                                               value="1"
                                               <?php checked( $enabled ); ?> />
                                        <?php esc_html_e(
                                            'Allow BE SEO to output JSON-LD schema.',
                                            'be-schema-engine'
                                        ); ?>
                                    </label>
                                    <p class="description be-schema-description">
                                        <?php esc_html_e(
                                            'If this is disabled, the plugin will not output any schema, regardless of page or Elementor settings.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Enable Elementor schema', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="be_schema_elementor_enabled"
                                               value="1"
                                               <?php checked( $elementor_enabled ); ?> />
                                        <?php esc_html_e(
                                            'Allow Elementor-driven schema for supported widgets and page types.',
                                            'be-schema-engine'
                                        ); ?>
                                    </label>
                                    <p class="description be-schema-description">
                                        <?php esc_html_e(
                                            'When enabled, Elementor page settings and widget controls can emit additional JSON-LD (subject to per-page safety checks).',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Debug logging', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="be_schema_debug"
                                               value="1"
                                               <?php checked( $debug_enabled ); ?> />
                                        <?php esc_html_e(
                                            'Log a combined @graph snapshot to the PHP error log on each request (when WP_DEBUG is true).',
                                            'be-schema-engine'
                                        ); ?>
                                    </label>
                                    <p class="description be-schema-description">
                                        <?php esc_html_e(
                                            'When enabled (and WP_DEBUG is on), the plugin writes a single BE_SCHEMA_DEBUG_GRAPH payload to the error log. This is useful for validating what the plugin thinks it is outputting.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>

                                    <p class="description be-schema-description">
                                        <?php esc_html_e(
                                            'You can also force debug via the BE_SCHEMA_DEBUG constant in wp-config.php. Constants always win over admin settings.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'wp-config.php overrides', 'be-schema-engine' ); ?>
                                </th>
                                <td>
                                    <ul class="be-schema-description">
                                        <li>
                                            <code>BE_SCHEMA_DISABLE_ALL</code>:
                                            <?php esc_html_e(
                                                'When true, disables all schema output from this plugin, regardless of admin settings.',
                                                'be-schema-engine'
                                            ); ?>
                                            <?php if ( $const_disable_all ) : ?>
                                                <strong><?php esc_html_e( '(Currently active)', 'be-schema-engine' ); ?></strong>
                                            <?php endif; ?>
                                        </li>
                                        <li>
                                            <code>BE_SCHEMA_DISABLE_ELEMENTOR</code>:
                                            <?php esc_html_e(
                                                'When true, disables only Elementor-specific schema.',
                                                'be-schema-engine'
                                            ); ?>
                                            <?php if ( $const_disable_elementor ) : ?>
                                                <strong><?php esc_html_e( '(Currently active)', 'be-schema-engine' ); ?></strong>
                                            <?php endif; ?>
                                        </li>
                                        <li>
                                            <code>BE_SCHEMA_DEBUG</code>:
                                            <?php esc_html_e(
                                                'When true, forces debug logging even if the admin setting is off.',
                                                'be-schema-engine'
                                            ); ?>
                                            <?php if ( $const_debug ) : ?>
                                                <strong><?php esc_html_e( '(Currently active)', 'be-schema-engine' ); ?></strong>
                                            <?php endif; ?>
                                        </li>
                                    </ul>
                                    <p class="description be-schema-description">
                                        <?php esc_html_e(
                                            'Use these constants sparingly, for emergency switches or local development. For day-to-day control, prefer the admin settings above.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Settings snapshot -->
                    <div class="be-schema-settings-snapshot">
                        <h2><?php esc_html_e( 'Settings snapshot (read-only)', 'be-schema-engine' ); ?></h2>
                        <p class="description be-schema-description">
                            <?php esc_html_e(
                                'A compact view of the current be_schema_engine_settings option, useful for debugging and verifying that values are saved as expected.',
                                'be-schema-engine'
                            ); ?>
                        </p>
                        <?php if ( ! empty( $settings ) && is_array( $settings ) ) : ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th class="be-schema-settings-snapshot-key">
                                            <?php esc_html_e( 'Key', 'be-schema-engine' ); ?>
                                        </th>
                                        <th class="be-schema-settings-snapshot-value">
                                            <?php esc_html_e( 'Value', 'be-schema-engine' ); ?>
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
                                                    echo $value ? esc_html__( 'true', 'be-schema-engine' ) : esc_html__( 'false', 'be-schema-engine' );
                                                } elseif ( is_array( $value ) ) {
                                                    echo esc_html( wp_json_encode( $value ) );
                                                } else {
                                                    $string_value = (string) $value;
                                                    if ( mb_strlen( $string_value ) > 140 ) {
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
                            <p><em><?php esc_html_e( 'No settings found for be_schema_engine_settings.', 'be-schema-engine' ); ?></em></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- WEBSITE TAB -->
                <div id="be-schema-tab-website" class="be-schema-tab-panel">
                    <h2><?php esc_html_e( 'Website entities', 'be-schema-engine' ); ?></h2>
                    <p class="description be-schema-description">
                        <?php esc_html_e(
                            'Configure the Person, Organisation, Publisher, and shared logo/images used by the site-level JSON-LD graph.',
                            'be-schema-engine'
                        ); ?>
                    </p>

                    <div class="be-schema-website-layout">
                        <div class="be-schema-website-nav">
                            <ul>
                                <li>
                                    <a href="#be-schema-website-global"
                                       class="be-schema-website-tab-link be-schema-website-tab-active"
                                       data-website-tab="global">
                                        <?php esc_html_e( 'Global', 'be-schema-engine' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-website-person"
                                       class="be-schema-website-tab-link"
                                       data-website-tab="person">
                                        <?php esc_html_e( 'Person', 'be-schema-engine' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-website-organization"
                                       class="be-schema-website-tab-link"
                                       data-website-tab="organization">
                                        <?php esc_html_e( 'Organisation', 'be-schema-engine' ); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#be-schema-website-publisher"
                                       class="be-schema-website-tab-link"
                                       data-website-tab="publisher">
                                        <?php esc_html_e( 'Publisher', 'be-schema-engine' ); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="be-schema-website-panels">

                            <!-- GLOBAL PANEL -->
                            <div id="be-schema-website-global"
                                 class="be-schema-website-panel be-schema-website-panel-active">
                                <h3><?php esc_html_e( 'Global', 'be-schema-engine' ); ?></h3>

                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <?php esc_html_e( 'Site logo (shared)', 'be-schema-engine' ); ?>
                                            </th>
                                            <td>
                                                <div class="be-schema-image-field">
                                                    <input type="text"
                                                           id="be_schema_org_logo"
                                                           name="be_schema_org_logo"
                                                           value="<?php echo esc_url( $org_logo ); ?>"
                                                           class="regular-text" />
                                                    <button type="button"
                                                            class="button be-schema-image-select"
                                                            data-target-input="be_schema_org_logo"
                                                            data-target-preview="be_schema_org_logo_preview">
                                                        <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                                    </button>
                                                    <button type="button"
                                                            class="button be-schema-image-clear"
                                                            data-target-input="be_schema_org_logo"
                                                            data-target-preview="be_schema_org_logo_preview">
                                                        <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                                    </button>
                                                </div>
                                                <p class="description be-schema-description">
                                                    <?php esc_html_e(
                                                        'This logo is used by the Organisation entity, the WebSite entity, and as a fallback for the Person image when a dedicated profile picture is not provided.',
                                                        'be-schema-engine'
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
                                            <th scope="row">
                                                <?php esc_html_e( 'WebSite featured image (16:9)', 'be-schema-engine' ); ?>
                                            </th>
                                            <td>
                                                <div class="be-schema-image-field">
                                                    <input type="text"
                                                           id="be_schema_website_image_16_9"
                                                           name="be_schema_website_image_16_9"
                                                           value="<?php echo esc_url( $website_image_16_9 ); ?>"
                                                           class="regular-text" />
                                                    <button type="button"
                                                            class="button be-schema-image-select"
                                                            data-target-input="be_schema_website_image_16_9"
                                                            data-target-preview="be_schema_website_image_16_9_preview">
                                                        <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                                    </button>
                                                    <button type="button"
                                                            class="button be-schema-image-clear"
                                                            data-target-input="be_schema_website_image_16_9"
                                                            data-target-preview="be_schema_website_image_16_9_preview">
                                                        <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                                    </button>
                                                </div>
                                                <p class="description be-schema-description">
                                                    <?php esc_html_e(
                                                        'Optional. A 16:9 aspect ratio image that can be used by the WebSite or WebPage schema when a featured image is needed.',
                                                        'be-schema-engine'
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
                                                <?php esc_html_e( 'WebSite featured image (4:3)', 'be-schema-engine' ); ?>
                                            </th>
                                            <td>
                                                <div class="be-schema-image-field">
                                                    <input type="text"
                                                           id="be_schema_website_image_4_3"
                                                           name="be_schema_website_image_4_3"
                                                           value="<?php echo esc_url( $website_image_4_3 ); ?>"
                                                           class="regular-text" />
                                                    <button type="button"
                                                            class="button be-schema-image-select"
                                                            data-target-input="be_schema_website_image_4_3"
                                                            data-target-preview="be_schema_website_image_4_3_preview">
                                                        <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                                    </button>
                                                    <button type="button"
                                                            class="button be-schema-image-clear"
                                                            data-target-input="be_schema_website_image_4_3"
                                                            data-target-preview="be_schema_website_image_4_3_preview">
                                                        <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                                    </button>
                                                </div>
                                                <p class="description be-schema-description">
                                                    <?php esc_html_e(
                                                        'Optional. A 4:3 aspect ratio image for WebSite/WebPage schema where that shape is appropriate.',
                                                        'be-schema-engine'
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
                                                <?php esc_html_e( 'WebSite featured image (1:1)', 'be-schema-engine' ); ?>
                                            </th>
                                            <td>
                                                <div class="be-schema-image-field">
                                                    <input type="text"
                                                           id="be_schema_website_image_1_1"
                                                           name="be_schema_website_image_1_1"
                                                           value="<?php echo esc_url( $website_image_1_1 ); ?>"
                                                           class="regular-text" />
                                                    <button type="button"
                                                            class="button be-schema-image-select"
                                                            data-target-input="be_schema_website_image_1_1"
                                                            data-target-preview="be_schema_website_image_1_1_preview">
                                                        <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                                    </button>
                                                    <button type="button"
                                                            class="button be-schema-image-clear"
                                                            data-target-input="be_schema_website_image_1_1"
                                                            data-target-preview="be_schema_website_image_1_1_preview">
                                                        <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                                    </button>
                                                </div>
                                                <p class="description be-schema-description">
                                                    <?php esc_html_e(
                                                        'Optional. A square (1:1) featured image for schema use.',
                                                        'be-schema-engine'
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

                            <!-- PERSON PANEL -->
                            <div id="be-schema-website-person" class="be-schema-website-panel">
                                <h3><?php esc_html_e( 'Person', 'be-schema-engine' ); ?></h3>

                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <?php esc_html_e( 'Enable Person entity', 'be-schema-engine' ); ?>
                                            </th>
                                            <td>
                                                <label>
                                                    <input type="checkbox"
                                                           name="be_schema_person_enabled"
                                                           value="1"
                                                           class="be-schema-toggle-block"
                                                           data-target-block="be-schema-person-block"
                                                           <?php checked( $person_enabled ); ?> />
                                                    <?php esc_html_e(
                                                        'Include a Person node in the site-level schema.',
                                                        'be-schema-engine'
                                                    ); ?>
                                                </label>
                                                <p class="description be-schema-description">
                                                    <?php esc_html_e(
                                                        'When enabled, the site will include a Person entity (usually the primary individual behind the site). The name itself is derived from other context, such as the site name or additional configuration.',
                                                        'be-schema-engine'
                                                    ); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div id="be-schema-person-block"
                                     class="be-schema-conditional-block <?php echo $person_enabled ? '' : 'is-disabled'; ?>">
                                    <table class="form-table">
                                        <tbody>
                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Profile image', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
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
                                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                                        </button>
                                                        <button type="button"
                                                                class="button be-schema-image-clear"
                                                                data-target-input="be_schema_person_image_url"
                                                                data-target-preview="be_schema_person_image_url_preview">
                                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                                        </button>
                                                    </div>
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. If left empty, the Person entity can fall back to the shared site logo.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                    <div id="be_schema_person_image_url_preview"
                                                         class="be-schema-image-preview">
                                                        <?php if ( $person_image_url ) : ?>
                                                            <img src="<?php echo esc_url( $person_image_url ); ?>" alt="" />
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Honorific prefix', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <input type="text"
                                                           name="be_schema_person_honorific_prefix"
                                                           value="<?php echo esc_attr( $person_honorific_prefix ); ?>"
                                                           class="regular-text" />
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. For example: Dr, Prof, Mr, Ms, etc.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Honorific suffix', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <input type="text"
                                                           name="be_schema_person_honorific_suffix"
                                                           value="<?php echo esc_attr( $person_honorific_suffix ); ?>"
                                                           class="regular-text" />
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. For example: PhD, MD, CPA, etc.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'sameAs URLs', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <textarea
                                                        name="be_schema_person_sameas_raw"
                                                        rows="5"
                                                        class="large-text code"><?php echo esc_textarea( $person_sameas_raw ); ?></textarea>
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'One URL per line, pointing to authoritative profiles for this person (for example, knowledge panels or professional profiles). These are used as Person.sameAs and are separate from social sharing settings.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- ORGANISATION PANEL -->
                            <div id="be-schema-website-organization" class="be-schema-website-panel">
                                <h3><?php esc_html_e( 'Organisation', 'be-schema-engine' ); ?></h3>

                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <?php esc_html_e( 'Enable Organisation entity', 'be-schema-engine' ); ?>
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
                                                        'be-schema-engine'
                                                    ); ?>
                                                </label>
                                                <p class="description be-schema-description">
                                                    <?php esc_html_e(
                                                        'When enabled, the site will include an Organisation entity that can be used as the primary about/publisher for the WebSite, and as the default publisher for BlogPosting.',
                                                        'be-schema-engine'
                                                    ); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div id="be-schema-organization-block"
                                     class="be-schema-conditional-block <?php echo $organization_enabled ? '' : 'is-disabled'; ?>">
                                    <table class="form-table">
                                        <tbody>
                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Organisation name', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <input type="text"
                                                           name="be_schema_org_name"
                                                           value="<?php echo esc_attr( $org_name ); ?>"
                                                           class="regular-text" />
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'The public name of the organisation.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Legal name', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <input type="text"
                                                           name="be_schema_org_legal_name"
                                                           value="<?php echo esc_attr( $org_legal_name ); ?>"
                                                           class="regular-text" />
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. The legal name of the organisation, if different from the public name.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Organisation URL', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <input type="text"
                                                           name="be_schema_org_url"
                                                           value="<?php echo esc_url( $org_url ); ?>"
                                                           class="regular-text" />
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. If empty, the site URL is used.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Shared logo (read-only)', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'The Organisation uses the shared site logo configured on the Global tab.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                    <div class="be-schema-image-preview">
                                                        <?php if ( $org_logo ) : ?>
                                                            <img src="<?php echo esc_url( $org_logo ); ?>" alt="" />
                                                        <?php else : ?>
                                                            <em><?php esc_html_e( 'No shared logo selected yet.', 'be-schema-engine' ); ?></em>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- PUBLISHER PANEL -->
                            <div id="be-schema-website-publisher" class="be-schema-website-panel">
                                <h3><?php esc_html_e( 'Publisher', 'be-schema-engine' ); ?></h3>

                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <?php esc_html_e( 'Enable WebSite.publisher', 'be-schema-engine' ); ?>
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
                                                        'be-schema-engine'
                                                    ); ?>
                                                </label>
                                                <p class="description be-schema-description">
                                                    <?php esc_html_e(
                                                        'When enabled, the WebSite.publisher property can reference either the site Person, the Organisation, or a dedicated custom publisher organisation, depending on your configuration.',
                                                        'be-schema-engine'
                                                    ); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div id="be-schema-publisher-block"
                                     class="be-schema-conditional-block <?php echo $publisher_enabled ? '' : 'is-disabled'; ?>">
                                    <table class="form-table">
                                        <tbody>
                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Copyright year', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <input type="text"
                                                           name="be_schema_copyright_year"
                                                           value="<?php echo esc_attr( $copyright_year ); ?>"
                                                           class="regular-text"
                                                           style="max-width: 120px;" />
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. Used for descriptive publishing metadata; not all validators require this.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'License URL', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <input type="text"
                                                           name="be_schema_license_url"
                                                           value="<?php echo esc_url( $license_url ); ?>"
                                                           class="regular-text" />
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. A URL describing the license under which the site content is published.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Publishing principles URL', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <input type="text"
                                                           name="be_schema_publishing_principles"
                                                           value="<?php echo esc_url( $publishing_principles ); ?>"
                                                           class="regular-text" />
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. A page describing your editorial standards or publishing principles.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Corrections policy URL', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <input type="text"
                                                           name="be_schema_corrections_policy"
                                                           value="<?php echo esc_url( $corrections_policy ); ?>"
                                                           class="regular-text" />
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. A page explaining how corrections or updates are handled.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Ownership / funding info URL', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <input type="text"
                                                           name="be_schema_ownership_funding"
                                                           value="<?php echo esc_url( $ownership_funding ); ?>"
                                                           class="regular-text" />
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. A page describing ownership or funding information for the publisher.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Custom publisher organisation name', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <input type="text"
                                                           name="be_schema_publisher_custom_name"
                                                           value="<?php echo esc_attr( $publisher_custom_name ); ?>"
                                                           class="regular-text" />
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. If set, the site can treat this as a dedicated publisher organisation instead of re-using the main Organisation.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Custom publisher URL', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
                                                    <input type="text"
                                                           name="be_schema_publisher_custom_url"
                                                           value="<?php echo esc_url( $publisher_custom_url ); ?>"
                                                           class="regular-text" />
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. The URL for the custom publisher organisation.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row">
                                                    <?php esc_html_e( 'Custom publisher logo', 'be-schema-engine' ); ?>
                                                </th>
                                                <td>
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
                                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                                        </button>
                                                        <button type="button"
                                                                class="button be-schema-image-clear"
                                                                data-target-input="be_schema_publisher_custom_logo"
                                                                data-target-preview="be_schema_publisher_custom_logo_preview">
                                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                                        </button>
                                                    </div>
                                                    <p class="description be-schema-description">
                                                        <?php esc_html_e(
                                                            'Optional. A dedicated logo for the custom publisher organisation. If empty, the shared site logo may still be used depending on the site-entity logic.',
                                                            'be-schema-engine'
                                                        ); ?>
                                                    </p>
                                                    <div id="be_schema_publisher_custom_logo_preview"
                                                         class="be-schema-image-preview">
                                                        <?php if ( $publisher_custom_logo ) : ?>
                                                            <img src="<?php echo esc_url( $publisher_custom_logo ); ?>" alt="" />
                                                        <?php endif; ?>
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

            <?php submit_button( __( 'Save Schema Settings', 'be-schema-engine' ) ); ?>
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

                websiteLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-website-tab');
                        activateWebsiteTab(tabKey);
                    });
                });

                // Conditional blocks (Person / Organisation / Publisher).
                var toggles = document.querySelectorAll('.be-schema-toggle-block');

                function updateConditionalBlock(toggle) {
                    var targetId = toggle.getAttribute('data-target-block');
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