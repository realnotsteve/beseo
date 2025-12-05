<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Save settings helper – handles the main Schema form submit.
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

    $settings = be_schema_engine_get_settings();

    // Global toggles.
    $settings['enabled']           = isset( $_POST['be_schema_enabled'] ) ? '1' : '0';
    $settings['elementor_enabled'] = isset( $_POST['be_schema_elementor_enabled'] ) ? '1' : '0';
    $settings['debug']             = isset( $_POST['be_schema_debug'] ) ? '1' : '0';

    // Organisation.
    $settings['organization_enabled'] = isset( $_POST['be_schema_organization_enabled'] ) ? '1' : '0';
    $settings['org_name']             = sanitize_text_field( $_POST['be_schema_org_name'] ?? '' );
    $settings['org_legal_name']       = sanitize_text_field( $_POST['be_schema_org_legal_name'] ?? '' );
    $settings['org_url']              = esc_url_raw( $_POST['be_schema_org_url'] ?? '' );
    $settings['org_logo']             = esc_url_raw( $_POST['be_schema_org_logo'] ?? '' );

    // Site featured images for the WebSite node.
    $settings['featured_image_16x9'] = esc_url_raw( $_POST['be_schema_featured_image_16x9'] ?? '' );
    $settings['featured_image_4x3']  = esc_url_raw( $_POST['be_schema_featured_image_4x3'] ?? '' );
    $settings['featured_image_1x1']  = esc_url_raw( $_POST['be_schema_featured_image_1x1'] ?? '' );

    // Person.
    $settings['person_enabled']          = isset( $_POST['be_schema_person_enabled'] ) ? '1' : '0';
    $settings['person_image_url']        = esc_url_raw( $_POST['be_schema_person_image_url'] ?? '' );
    $settings['person_honorific_prefix'] = sanitize_text_field( $_POST['be_schema_person_honorific_prefix'] ?? '' );
    $settings['person_honorific_suffix'] = sanitize_text_field( $_POST['be_schema_person_honorific_suffix'] ?? '' );
    $settings['person_sameas_raw']       = sanitize_textarea_field( $_POST['be_schema_person_sameas_raw'] ?? '' );

    // Publisher + publishing info.
    $settings['publisher_enabled']        = isset( $_POST['be_schema_publisher_enabled'] ) ? '1' : '0';
    $settings['publisher_custom_enabled'] = isset( $_POST['be_schema_publisher_custom_enabled'] ) ? '1' : '0';
    $settings['copyright_year']           = sanitize_text_field( $_POST['be_schema_copyright_year'] ?? '' );
    $settings['license_url']              = esc_url_raw( $_POST['be_schema_license_url'] ?? '' );
    $settings['publishing_principles']    = esc_url_raw( $_POST['be_schema_publishing_principles'] ?? '' );
    $settings['corrections_policy']       = esc_url_raw( $_POST['be_schema_corrections_policy'] ?? '' );
    $settings['ownership_funding']        = esc_url_raw( $_POST['be_schema_ownership_funding'] ?? '' );
    $settings['publisher_custom_name']    = sanitize_text_field( $_POST['be_schema_publisher_custom_name'] ?? '' );
    $settings['publisher_custom_url']     = esc_url_raw( $_POST['be_schema_publisher_custom_url'] ?? '' );
    $settings['publisher_custom_logo']    = esc_url_raw( $_POST['be_schema_publisher_custom_logo'] ?? '' );

    update_option( 'be_schema_engine_settings', $settings );
}

/**
 * Handle form submit on admin_init.
 */
add_action( 'admin_init', function () {
    if ( isset( $_POST['be_schema_engine_settings_submit'] ) ) {
        be_schema_engine_save_settings();
    }
} );

/**
 * Render the Schema submenu page (Settings + Website).
 */
function be_schema_engine_render_schema_page() {
    $settings = be_schema_engine_get_settings();

    // Make sure the media modal is available for logo/profile/featured image selectors.
    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

    // === Diagnostics / "Tools" logic moved into Settings tab ===

    // Raw settings-based flags.
    $global_enabled_setting    = ( isset( $settings['enabled'] ) && $settings['enabled'] === '1' );
    $elementor_enabled_setting = ( isset( $settings['elementor_enabled'] ) && $settings['elementor_enabled'] === '1' );
    $debug_setting             = ( isset( $settings['debug'] ) && $settings['debug'] === '1' );

    // Constants.
    $has_disable_all_constant         = defined( 'BE_SCHEMA_DISABLE_ALL' );
    $disable_all_constant_value       = $has_disable_all_constant ? (bool) BE_SCHEMA_DISABLE_ALL : null;
    $has_disable_elementor_constant   = defined( 'BE_SCHEMA_DISABLE_ELEMENTOR' );
    $disable_elementor_constant_value = $has_disable_elementor_constant ? (bool) BE_SCHEMA_DISABLE_ELEMENTOR : null;
    $has_debug_constant               = defined( 'BE_SCHEMA_DEBUG' );
    $debug_constant_value             = $has_debug_constant ? (bool) BE_SCHEMA_DEBUG : null;

    // Effective global enabled: setting overridden by BE_SCHEMA_DISABLE_ALL.
    if ( $has_disable_all_constant && $disable_all_constant_value ) {
        $effective_global_enabled = false;
    } else {
        $effective_global_enabled = $global_enabled_setting;
    }

    // Effective Elementor enabled.
    if ( $has_disable_all_constant && $disable_all_constant_value ) {
        $effective_elementor_enabled = false;
    } elseif ( $has_disable_elementor_constant && $disable_elementor_constant_value ) {
        $effective_elementor_enabled = false;
    } else {
        $effective_elementor_enabled = ( $effective_global_enabled && $elementor_enabled_setting );
    }

    // Effective debug flag (plugin-level, not considering WP_DEBUG yet).
    if ( $has_debug_constant ) {
        $effective_debug_flag = (bool) $debug_constant_value;
    } else {
        $effective_debug_flag = $debug_setting;
    }

    // Will debug actually log the graph? Requires WP_DEBUG as well.
    $wp_debug_enabled     = defined( 'WP_DEBUG' ) && WP_DEBUG;
    $will_log_debug_graph = ( $wp_debug_enabled && $effective_debug_flag );
    ?>

    <div class="wrap be-schema-engine-wrap">
        <h1><?php esc_html_e( 'BE Schema Engine – Schema', 'be-schema-engine' ); ?></h1>

        <form method="post">
            <?php wp_nonce_field( 'be_schema_engine_save_settings', 'be_schema_engine_settings_nonce' ); ?>

            <!-- Top-level tabs: Settings / Website -->
            <h2 class="nav-tab-wrapper">
                <a href="#be-schema-tab-settings" class="nav-tab nav-tab-active">
                    <?php esc_html_e( 'Settings', 'be-schema-engine' ); ?>
                </a>
                <a href="#be-schema-tab-website" class="nav-tab">
                    <?php esc_html_e( 'Website', 'be-schema-engine' ); ?>
                </a>
            </h2>

            <style>
                /* Diagnostics styling (moved from Tools) */
                .be-schema-status-badge {
                    display: inline-block;
                    padding: 2px 8px;
                    border-radius: 999px;
                    font-size: 11px;
                    font-weight: 600;
                    line-height: 1.4;
                    text-transform: uppercase;
                }
                .be-schema-status-ok {
                    background: #e6f4ea;
                    color: #137333;
                    border: 1px solid #c3e6cb;
                }
                .be-schema-status-off {
                    background: #fce8e6;
                    color: #c5221f;
                    border: 1px solid #f5c2c7;
                }
                .be-schema-status-warn {
                    background: #fff4e5;
                    color: #b06000;
                    border: 1px solid #ffe0b3;
                }
                .be-schema-tools-section {
                    margin-top: 30px;
                    margin-bottom: 30px;
                }
                .be-schema-tools-section h2 {
                    margin-bottom: 10px;
                }
                .be-schema-tools-table th {
                    width: 260px;
                }
                .be-schema-website-layout {
                    display: flex;
                    margin-top: 20px;
                }
                .be-schema-website-nav {
                    width: 180px;
                    margin-right: 24px;
                }
                .be-schema-website-nav .button {
                    display: block;
                    width: 100%;
                    margin-bottom: 6px;
                    text-align: left;
                }
                .be-schema-website-nav .button.button-primary {
                    box-shadow: none;
                }
                .be-schema-website-panels {
                    flex: 1;
                }
                .be-schema-website-table .be-schema-website-panel {
                    display: none;
                }
                .be-schema-website-table .be-schema-website-panel-active {
                    display: table-row-group;
                }
                .be-schema-logo-preview {
                    margin-top: 8px;
                }
                .be-schema-logo-preview img {
                    max-width: 160px;
                    height: auto;
                    border: 1px solid #ccd0d4;
                    padding: 4px;
                    background: #fff;
                }
                .be-schema-logo-meta {
                    font-size: 11px;
                    color: #555d66;
                    margin-top: 4px;
                }
            </style>

            <!-- SETTINGS TAB -->
            <div id="be-schema-tab-settings" class="be-schema-tab-panel" style="display:block;">
                <h2><?php esc_html_e( 'Global Settings', 'be-schema-engine' ); ?></h2>

                <p class="description">
                    <?php esc_html_e(
                        'You can also control behavior via wp-config constants: BE_SCHEMA_DISABLE_ALL, BE_SCHEMA_DISABLE_ELEMENTOR, BE_SCHEMA_DEBUG.',
                        'be-schema-engine'
                    ); ?>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enable BE Schema Engine', 'be-schema-engine' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="be_schema_enabled" value="1" <?php checked( $settings['enabled'], '1' ); ?> />
                                <?php esc_html_e( 'Emit schema for this site (subject to per-page and safety rules).', 'be-schema-engine' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enable Elementor schema', 'be-schema-engine' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="be_schema_elementor_enabled" value="1" <?php checked( $settings['elementor_enabled'], '1' ); ?> />
                                <?php esc_html_e( 'Allow Elementor-driven Image/Video schema, subject to page-level toggles.', 'be-schema-engine' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enable debug graph logging', 'be-schema-engine' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="be_schema_debug" value="1" <?php checked( $settings['debug'], '1' ); ?> />
                                <?php esc_html_e( 'When WP_DEBUG is true, log the full JSON-LD graph to error_log().', 'be-schema-engine' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <!-- DIAGNOSTICS / TOOLS MOVED HERE -->

                <div class="be-schema-tools-section">
                    <h2><?php esc_html_e( 'Effective Status Summary', 'be-schema-engine' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e(
                            'These values reflect the combination of plugin settings and any wp-config constants. They represent what the engine will actually do on the front end.',
                            'be-schema-engine'
                        ); ?>
                    </p>

                    <table class="widefat fixed striped be-schema-tools-table">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Global schema status', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( $effective_global_enabled ) : ?>
                                        <span class="be-schema-status-badge be-schema-status-ok">
                                            <?php esc_html_e( 'Enabled', 'be-schema-engine' ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="be-schema-status-badge be-schema-status-off">
                                            <?php esc_html_e( 'Disabled', 'be-schema-engine' ); ?>
                                        </span>
                                    <?php endif; ?>

                                    <p class="description">
                                        <?php
                                        if ( $has_disable_all_constant && $disable_all_constant_value ) {
                                            esc_html_e(
                                                'Global schema is hard-disabled via BE_SCHEMA_DISABLE_ALL in wp-config.php.',
                                                'be-schema-engine'
                                            );
                                        } elseif ( $global_enabled_setting ) {
                                            esc_html_e(
                                                'Plugin setting “Enable BE Schema Engine” is on and BE_SCHEMA_DISABLE_ALL is not forcing it off.',
                                                'be-schema-engine'
                                            );
                                        } else {
                                            esc_html_e(
                                                'Plugin setting “Enable BE Schema Engine” is off (or no settings have been saved yet).',
                                                'be-schema-engine'
                                            );
                                        }
                                        ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Elementor schema status', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( $effective_elementor_enabled ) : ?>
                                        <span class="be-schema-status-badge be-schema-status-ok">
                                            <?php esc_html_e( 'Enabled', 'be-schema-engine' ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="be-schema-status-badge be-schema-status-off">
                                            <?php esc_html_e( 'Disabled', 'be-schema-engine' ); ?>
                                        </span>
                                    <?php endif; ?>

                                    <p class="description">
                                        <?php
                                        if ( $has_disable_all_constant && $disable_all_constant_value ) {
                                            esc_html_e(
                                                'Elementor schema is disabled because all schema is hard-disabled via BE_SCHEMA_DISABLE_ALL.',
                                                'be-schema-engine'
                                            );
                                        } elseif ( $has_disable_elementor_constant && $disable_elementor_constant_value ) {
                                            esc_html_e(
                                                'Elementor schema is hard-disabled via BE_SCHEMA_DISABLE_ELEMENTOR in wp-config.php.',
                                                'be-schema-engine'
                                            );
                                        } elseif ( ! $global_enabled_setting ) {
                                            esc_html_e(
                                                'Elementor schema is effectively off because the global plugin setting is disabled.',
                                                'be-schema-engine'
                                            );
                                        } elseif ( $elementor_enabled_setting ) {
                                            esc_html_e(
                                                'Elementor schema is allowed by plugin settings and not blocked by wp-config constants (page-level toggles still apply).',
                                                'be-schema-engine'
                                            );
                                        } else {
                                            esc_html_e(
                                                'Plugin setting “Enable Elementor schema” is off.',
                                                'be-schema-engine'
                                            );
                                        }
                                        ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Debug graph logging status', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( $effective_debug_flag ) : ?>
                                        <span class="be-schema-status-badge be-schema-status-ok">
                                            <?php esc_html_e( 'Debug flag ON', 'be-schema-engine' ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="be-schema-status-badge be-schema-status-off">
                                            <?php esc_html_e( 'Debug flag OFF', 'be-schema-engine' ); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ( $will_log_debug_graph ) : ?>
                                        <span class="be-schema-status-badge be-schema-status-ok" style="margin-left:6px;">
                                            <?php esc_html_e( 'Will log @graph (WP_DEBUG on)', 'be-schema-engine' ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="be-schema-status-badge be-schema-status-warn" style="margin-left:6px;">
                                            <?php esc_html_e( 'Will NOT log @graph', 'be-schema-engine' ); ?>
                                        </span>
                                    <?php endif; ?>

                                    <p class="description">
                                        <?php
                                        if ( $has_debug_constant ) {
                                            if ( $debug_constant_value ) {
                                                esc_html_e(
                                                    'BE_SCHEMA_DEBUG is defined and truthy in wp-config.php, so it forces debug graph logging on at the plugin level.',
                                                    'be-schema-engine'
                                                );
                                            } else {
                                                esc_html_e(
                                                    'BE_SCHEMA_DEBUG is defined and falsey in wp-config.php, so it forces debug graph logging off at the plugin level.',
                                                    'be-schema-engine'
                                                );
                                            }
                                        } else {
                                            if ( $debug_setting ) {
                                                esc_html_e(
                                                    'Plugin setting “Enable debug graph logging” is on. Logging still requires WP_DEBUG to be true.',
                                                    'be-schema-engine'
                                                );
                                            } else {
                                                esc_html_e(
                                                    'Plugin setting “Enable debug graph logging” is off. You can override this with BE_SCHEMA_DEBUG in wp-config.php.',
                                                    'be-schema-engine'
                                                );
                                            }
                                        }
                                        ?>
                                    </p>

                                    <p class="description">
                                        <?php
                                        if ( $wp_debug_enabled ) {
                                            esc_html_e(
                                                'WP_DEBUG is currently ON. If the debug flag is also on, the full @graph will be logged to error_log() at shutdown.',
                                                'be-schema-engine'
                                            );
                                        } else {
                                            esc_html_e(
                                                'WP_DEBUG is currently OFF. Even if the debug flag is on, the debug graph will not be logged.',
                                                'be-schema-engine'
                                            );
                                        }
                                        ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="be-schema-tools-section">
                    <h2><?php esc_html_e( 'wp-config Constants Overview', 'be-schema-engine' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e(
                            'These constants are read from wp-config.php (or a similar early-loaded file). They override or hard-disable parts of the engine.',
                            'be-schema-engine'
                        ); ?>
                    </p>

                    <table class="widefat fixed striped be-schema-tools-table">
                        <tbody>
                            <tr>
                                <th scope="row"><code>BE_SCHEMA_DISABLE_ALL</code></th>
                                <td>
                                    <p>
                                        <?php
                                        if ( $has_disable_all_constant ) {
                                            printf(
                                                /* translators: %s is a boolean value rendered as 'true' or 'false'. */
                                                esc_html__( 'Defined, current value: %s', 'be-schema-engine' ),
                                                $disable_all_constant_value ? 'true' : 'false'
                                            );
                                        } else {
                                            esc_html_e( 'Not defined.', 'be-schema-engine' );
                                        }
                                        ?>
                                    </p>
                                    <p class="description">
                                        <?php esc_html_e(
                                            'When true, no schema will be emitted at all. This hard-disables the engine regardless of plugin settings.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><code>BE_SCHEMA_DISABLE_ELEMENTOR</code></th>
                                <td>
                                    <p>
                                        <?php
                                        if ( $has_disable_elementor_constant ) {
                                            printf(
                                                esc_html__( 'Defined, current value: %s', 'be-schema-engine' ),
                                                $disable_elementor_constant_value ? 'true' : 'false'
                                            );
                                        } else {
                                            esc_html_e( 'Not defined.', 'be-schema-engine' );
                                        }
                                        ?>
                                    </p>
                                    <p class="description">
                                        <?php esc_html_e(
                                            'When true, Elementor-driven widget schema (ImageObject / VideoObject / ItemList) is never emitted, even if Elementor schema is enabled in the plugin settings.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><code>BE_SCHEMA_DEBUG</code></th>
                                <td>
                                    <p>
                                        <?php
                                        if ( $has_debug_constant ) {
                                            printf(
                                                esc_html__( 'Defined, current value: %s', 'be-schema-engine' ),
                                                $debug_constant_value ? 'true' : 'false'
                                            );
                                        } else {
                                            esc_html_e( 'Not defined.', 'be-schema-engine' );
                                        }
                                        ?>
                                    </p>
                                    <p class="description">
                                        <?php esc_html_e(
                                            'When defined, this constant forces the plugin\'s debug graph flag on or off, overriding the admin setting. Logging still requires WP_DEBUG to be true.',
                                            'be-schema-engine'
                                        ); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="be-schema-tools-section">
                    <h2><?php esc_html_e( 'Settings Snapshot', 'be-schema-engine' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e(
                            'This is a read-only view of key values from the be_schema_engine_settings option. Use it to confirm what has actually been saved.',
                            'be-schema-engine'
                        ); ?>
                    </p>

                    <table class="widefat fixed striped be-schema-tools-table">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Enable BE Schema Engine', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php echo $global_enabled_setting ? esc_html__( 'On', 'be-schema-engine' ) : esc_html__( 'Off', 'be-schema-engine' ); ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Enable Elementor schema', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php echo $elementor_enabled_setting ? esc_html__( 'On', 'be-schema-engine' ) : esc_html__( 'Off', 'be-schema-engine' ); ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Enable debug graph logging', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php echo $debug_setting ? esc_html__( 'On', 'be-schema-engine' ) : esc_html__( 'Off', 'be-schema-engine' ); ?>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Person entity enabled', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php
                                    $person_enabled = isset( $settings['person_enabled'] ) && $settings['person_enabled'] === '1';
                                    echo $person_enabled ? esc_html__( 'Yes', 'be-schema-engine' ) : esc_html__( 'No', 'be-schema-engine' );
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Organisation entity enabled', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php
                                    $org_enabled = isset( $settings['organization_enabled'] ) && $settings['organization_enabled'] === '1';
                                    echo $org_enabled ? esc_html__( 'Yes', 'be-schema-engine' ) : esc_html__( 'No', 'be-schema-engine' );
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Publisher enabled', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php
                                    $publisher_enabled = isset( $settings['publisher_enabled'] ) && $settings['publisher_enabled'] === '1';
                                    echo $publisher_enabled ? esc_html__( 'Yes', 'be-schema-engine' ) : esc_html__( 'No', 'be-schema-engine' );
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Organisation name', 'be-schema-engine' ); ?></th>
                                <td><?php echo esc_html( $settings['org_name'] ); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Organisation legal name', 'be-schema-engine' ); ?></th>
                                <td><?php echo esc_html( $settings['org_legal_name'] ); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Organisation URL', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $settings['org_url'] ) ) : ?>
                                        <a href="<?php echo esc_url( $settings['org_url'] ); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html( $settings['org_url'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set.', 'be-schema-engine' ); ?></em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Site logo URL (org_logo)', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $settings['org_logo'] ) ) : ?>
                                        <a href="<?php echo esc_url( $settings['org_logo'] ); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html( $settings['org_logo'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set.', 'be-schema-engine' ); ?></em>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Site featured image 16:9 URL', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $settings['featured_image_16x9'] ) ) : ?>
                                        <a href="<?php echo esc_url( $settings['featured_image_16x9'] ); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html( $settings['featured_image_16x9'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set.', 'be-schema-engine' ); ?></em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Site featured image 4:3 URL', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $settings['featured_image_4x3'] ) ) : ?>
                                        <a href="<?php echo esc_url( $settings['featured_image_4x3'] ); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html( $settings['featured_image_4x3'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set.', 'be-schema-engine' ); ?></em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Site featured image 1:1 URL', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $settings['featured_image_1x1'] ) ) : ?>
                                        <a href="<?php echo esc_url( $settings['featured_image_1x1'] ); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html( $settings['featured_image_1x1'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set.', 'be-schema-engine' ); ?></em>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Copyright year', 'be-schema-engine' ); ?></th>
                                <td><?php echo esc_html( $settings['copyright_year'] ); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'License URL', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $settings['license_url'] ) ) : ?>
                                        <a href="<?php echo esc_url( $settings['license_url'] ); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html( $settings['license_url'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set.', 'be-schema-engine' ); ?></em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Publishing principles URL', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $settings['publishing_principles'] ) ) : ?>
                                        <a href="<?php echo esc_url( $settings['publishing_principles'] ); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html( $settings['publishing_principles'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set.', 'be-schema-engine' ); ?></em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Corrections policy URL', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $settings['corrections_policy'] ) ) : ?>
                                        <a href="<?php echo esc_url( $settings['corrections_policy'] ); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html( $settings['corrections_policy'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set.', 'be-schema-engine' ); ?></em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Ownership / funding URL', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $settings['ownership_funding'] ) ) : ?>
                                        <a href="<?php echo esc_url( $settings['ownership_funding'] ); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html( $settings['ownership_funding'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set.', 'be-schema-engine' ); ?></em>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e( 'Custom publisher name', 'be-schema-engine' ); ?></th>
                                <td><?php echo esc_html( $settings['publisher_custom_name'] ); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Custom publisher URL', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $settings['publisher_custom_url'] ) ) : ?>
                                        <a href="<?php echo esc_url( $settings['publisher_custom_url'] ); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html( $settings['publisher_custom_url'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set.', 'be-schema-engine' ); ?></em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Custom publisher logo URL', 'be-schema-engine' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $settings['publisher_custom_logo'] ) ) : ?>
                                        <a href="<?php echo esc_url( $settings['publisher_custom_logo'] ); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html( $settings['publisher_custom_logo'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <em><?php esc_html_e( 'Not set.', 'be-schema-engine' ); ?></em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- WEBSITE TAB -->
            <div id="be-schema-tab-website" class="be-schema-tab-panel" style="display:none;">
                <h2><?php esc_html_e( 'Website entities', 'be-schema-engine' ); ?></h2>

                <p class="description">
                    <?php esc_html_e(
                        'Configure the site-level schema entities: Person, Organisation, publisher, and shared logo images.',
                        'be-schema-engine'
                    ); ?>
                </p>

                <?php
                $site_url                 = trailingslashit( home_url() );
                $logo_id                  = $site_url . '#logo';
                $publisher_logo_id        = $site_url . '#publisher-logo';
                $has_logo                 = ! empty( $settings['org_logo'] );
                $has_publisher_logo       = ! empty( $settings['publisher_custom_logo'] );
                $publisher_custom_enabled = isset( $settings['publisher_custom_enabled'] ) && $settings['publisher_custom_enabled'] === '1';

                $has_featured_16x9 = ! empty( $settings['featured_image_16x9'] );
                $has_featured_4x3  = ! empty( $settings['featured_image_4x3'] );
                $has_featured_1x1  = ! empty( $settings['featured_image_1x1'] );
                $featured_16x9_id  = $site_url . '#website-featured-16x9';
                $featured_4x3_id   = $site_url . '#website-featured-4x3';
                $featured_1x1_id   = $site_url . '#website-featured-1x1';

                $has_person_image = ! empty( $settings['person_image_url'] );
                ?>

                <div class="be-schema-website-layout">
                    <div class="be-schema-website-nav">
                        <button type="button" class="button button-primary" data-panel="global">
                            <?php esc_html_e( 'Global', 'be-schema-engine' ); ?>
                        </button>
                        <button type="button" class="button" data-panel="person">
                            <?php esc_html_e( 'Person', 'be-schema-engine' ); ?>
                        </button>
                        <button type="button" class="button" data-panel="organization">
                            <?php esc_html_e( 'Organisation', 'be-schema-engine' ); ?>
                        </button>
                        <button type="button" class="button" data-panel="publisher">
                            <?php esc_html_e( 'Publisher', 'be-schema-engine' ); ?>
                        </button>
                    </div>

                    <div class="be-schema-website-panels">
                        <table class="form-table be-schema-website-table">

                            <!-- GLOBAL PANEL -->
                            <tbody id="be-schema-panel-global" class="be-schema-website-panel be-schema-website-panel-active">
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Overview', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <p class="description">
                                            <?php esc_html_e(
                                                'The site logo is shared across entities. When set, it is used as WebSite.logo, Organisation.logo, and Person.image (unless a Person profile picture is configured).',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                        <p class="description">
                                            <?php esc_html_e(
                                                'The optional custom publisher is a separate Organisation used as WebSite.publisher. Its logo is distinct from the site logo.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                        <p class="description">
                                            <?php esc_html_e(
                                                'You can also define three site-level featured images in different aspect ratios (16:9, 4:3, 1:1) which are attached to the WebSite node as an image array.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Site logo (#logo)', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input
                                            type="url"
                                            class="regular-text"
                                            id="be_schema_org_logo"
                                            name="be_schema_org_logo"
                                            value="<?php echo esc_attr( $settings['org_logo'] ); ?>"
                                        />
                                        <button
                                            type="button"
                                            class="button be-schema-media-select"
                                            data-target-input="#be_schema_org_logo"
                                            data-target-preview="#be-schema-org-logo-preview"
                                        >
                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="button be-schema-media-clear"
                                            data-target-input="#be_schema_org_logo"
                                            data-target-preview="#be-schema-org-logo-preview"
                                        >
                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                        </button>

                                        <p class="description">
                                            <?php esc_html_e(
                                                'This URL is used to build the shared #logo ImageObject. It is used by WebSite.logo, Organisation.logo, and Person.image (unless a Person profile picture is set).',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>

                                        <div id="be-schema-org-logo-preview" class="be-schema-logo-preview">
                                            <?php if ( $has_logo ) : ?>
                                                <img src="<?php echo esc_url( $settings['org_logo'] ); ?>" alt="" />
                                                <div class="be-schema-logo-meta">
                                                    <code><?php echo esc_html( $logo_id ); ?></code><br />
                                                    <a href="<?php echo esc_url( $settings['org_logo'] ); ?>" target="_blank" rel="noopener noreferrer">
                                                        <?php echo esc_html( $settings['org_logo'] ); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Site featured image 16:9', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input
                                            type="url"
                                            class="regular-text"
                                            id="be_schema_featured_image_16x9"
                                            name="be_schema_featured_image_16x9"
                                            value="<?php echo esc_attr( $settings['featured_image_16x9'] ); ?>"
                                        />
                                        <button
                                            type="button"
                                            class="button be-schema-media-select"
                                            data-target-input="#be_schema_featured_image_16x9"
                                            data-target-preview="#be-schema-featured-image-16x9-preview"
                                        >
                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="button be-schema-media-clear"
                                            data-target-input="#be_schema_featured_image_16x9"
                                            data-target-preview="#be-schema-featured-image-16x9-preview"
                                        >
                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                        </button>

                                        <p class="description">
                                            <?php esc_html_e(
                                                'Optional site-level featured image in 16:9 aspect ratio. Used only on the WebSite node as a featured image.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>

                                        <div id="be-schema-featured-image-16x9-preview" class="be-schema-logo-preview">
                                            <?php if ( $has_featured_16x9 ) : ?>
                                                <img src="<?php echo esc_url( $settings['featured_image_16x9'] ); ?>" alt="" />
                                                <div class="be-schema-logo-meta">
                                                    <code><?php echo esc_html( $featured_16x9_id ); ?></code><br />
                                                    <a href="<?php echo esc_url( $settings['featured_image_16x9'] ); ?>" target="_blank" rel="noopener noreferrer">
                                                        <?php echo esc_html( $settings['featured_image_16x9'] ); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Site featured image 4:3', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input
                                            type="url"
                                            class="regular-text"
                                            id="be_schema_featured_image_4x3"
                                            name="be_schema_featured_image_4x3"
                                            value="<?php echo esc_attr( $settings['featured_image_4x3'] ); ?>"
                                        />
                                        <button
                                            type="button"
                                            class="button be-schema-media-select"
                                            data-target-input="#be_schema_featured_image_4x3"
                                            data-target-preview="#be-schema-featured-image-4x3-preview"
                                        >
                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="button be-schema-media-clear"
                                            data-target-input="#be_schema_featured_image_4x3"
                                            data-target-preview="#be-schema-featured-image-4x3-preview"
                                        >
                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                        </button>

                                        <p class="description">
                                            <?php esc_html_e(
                                                'Optional site-level featured image in 4:3 aspect ratio. Used only on the WebSite node as a featured image.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>

                                        <div id="be-schema-featured-image-4x3-preview" class="be-schema-logo-preview">
                                            <?php if ( $has_featured_4x3 ) : ?>
                                                <img src="<?php echo esc_url( $settings['featured_image_4x3'] ); ?>" alt="" />
                                                <div class="be-schema-logo-meta">
                                                    <code><?php echo esc_html( $featured_4x3_id ); ?></code><br />
                                                    <a href="<?php echo esc_url( $settings['featured_image_4x3'] ); ?>" target="_blank" rel="noopener noreferrer">
                                                        <?php echo esc_html( $settings['featured_image_4x3'] ); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Site featured image 1:1', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input
                                            type="url"
                                            class="regular-text"
                                            id="be_schema_featured_image_1x1"
                                            name="be_schema_featured_image_1x1"
                                            value="<?php echo esc_attr( $settings['featured_image_1x1'] ); ?>"
                                        />
                                        <button
                                            type="button"
                                            class="button be-schema-media-select"
                                            data-target-input="#be_schema_featured_image_1x1"
                                            data-target-preview="#be-schema-featured-image-1x1-preview"
                                        >
                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="button be-schema-media-clear"
                                            data-target-input="#be_schema_featured_image_1x1"
                                            data-target-preview="#be-schema-featured-image-1x1-preview"
                                        >
                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                        </button>

                                        <p class="description">
                                            <?php esc_html_e(
                                                'Optional site-level featured image in 1:1 aspect ratio. Used only on the WebSite node as a featured image.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>

                                        <div id="be-schema-featured-image-1x1-preview" class="be-schema-logo-preview">
                                            <?php if ( $has_featured_1x1 ) : ?>
                                                <img src="<?php echo esc_url( $settings['featured_image_1x1'] ); ?>" alt="" />
                                                <div class="be-schema-logo-meta">
                                                    <code><?php echo esc_html( $featured_1x1_id ); ?></code><br />
                                                    <a href="<?php echo esc_url( $settings['featured_image_1x1'] ); ?>" target="_blank" rel="noopener noreferrer">
                                                        <?php echo esc_html( $settings['featured_image_1x1'] ); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>

                            <!-- PERSON PANEL -->
                            <tbody id="be-schema-panel-person" class="be-schema-website-panel">
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Person entity', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="be_schema_person_enabled" value="1" <?php checked( $settings['person_enabled'], '1' ); ?> />
                                            <?php esc_html_e( 'Emit a Person entity for the site.', 'be-schema-engine' ); ?>
                                        </label>
                                        <p class="description">
                                            <?php esc_html_e(
                                                'When enabled, a Person node with @id "#person" will be emitted using the site name by default. You can configure a profile picture, honorifics, and sameAs links below.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr data-be-schema-person-fields="1">
                                    <th scope="row"><?php esc_html_e( 'Profile picture URL', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input
                                            type="url"
                                            class="regular-text"
                                            id="be_schema_person_image_url"
                                            name="be_schema_person_image_url"
                                            value="<?php echo esc_attr( $settings['person_image_url'] ); ?>"
                                        />
                                        <button
                                            type="button"
                                            class="button be-schema-media-select"
                                            data-target-input="#be_schema_person_image_url"
                                            data-target-preview="#be-schema-person-image-preview"
                                        >
                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="button be-schema-media-clear"
                                            data-target-input="#be_schema_person_image_url"
                                            data-target-preview="#be-schema-person-image-preview"
                                        >
                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                        </button>

                                        <p class="description">
                                            <?php esc_html_e(
                                                'Optional. When set, this URL is used as the Person.image. If empty, the site logo (#logo) will be used as a fallback (when available).',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>

                                        <div id="be-schema-person-image-preview" class="be-schema-logo-preview">
                                            <?php if ( $has_person_image ) : ?>
                                                <img src="<?php echo esc_url( $settings['person_image_url'] ); ?>" alt="" />
                                                <div class="be-schema-logo-meta">
                                                    <a href="<?php echo esc_url( $settings['person_image_url'] ); ?>" target="_blank" rel="noopener noreferrer">
                                                        <?php echo esc_html( $settings['person_image_url'] ); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <tr data-be-schema-person-fields="1">
                                    <th scope="row"><?php esc_html_e( 'Honorific prefix', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input
                                            type="text"
                                            class="regular-text"
                                            name="be_schema_person_honorific_prefix"
                                            value="<?php echo esc_attr( $settings['person_honorific_prefix'] ); ?>"
                                        />
                                        <p class="description">
                                            <?php esc_html_e(
                                                'Optional. Examples: "Dr.", "Prof.", "Mr.", "Ms.". Used as Person.honorificPrefix.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr data-be-schema-person-fields="1">
                                    <th scope="row"><?php esc_html_e( 'Honorific suffix', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input
                                            type="text"
                                            class="regular-text"
                                            name="be_schema_person_honorific_suffix"
                                            value="<?php echo esc_attr( $settings['person_honorific_suffix'] ); ?>"
                                        />
                                        <p class="description">
                                            <?php esc_html_e(
                                                'Optional. Examples: "PhD", "MD", "Esq.". Used as Person.honorificSuffix.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr data-be-schema-person-fields="1">
                                    <th scope="row"><?php esc_html_e( 'Person sameAs URLs', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <textarea
                                            name="be_schema_person_sameas_raw"
                                            rows="5"
                                            class="large-text code"
                                        ><?php echo esc_textarea( $settings['person_sameas_raw'] ); ?></textarea>
                                        <p class="description">
                                            <?php esc_html_e(
                                                'Optional. One URL per line (e.g. social profiles, official pages). These are attached to the Person node as sameAs.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>

                            <!-- ORGANISATION PANEL -->
                            <tbody id="be-schema-panel-organization" class="be-schema-website-panel">
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Organisation entity', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="be_schema_organization_enabled" value="1" <?php checked( $settings['organization_enabled'], '1' ); ?> />
                                            <?php esc_html_e( 'Emit an Organisation entity for the site.', 'be-schema-engine' ); ?>
                                        </label>
                                        <p class="description">
                                            <?php esc_html_e(
                                                'When enabled, an Organisation node with @id "#organization" will be emitted. Its name, legal name, URL, and logo come from the fields below.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr data-be-schema-org-fields="1">
                                    <th scope="row"><?php esc_html_e( 'Organisation name', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input type="text" class="regular-text" name="be_schema_org_name" value="<?php echo esc_attr( $settings['org_name'] ); ?>" />
                                        <p class="description">
                                            <?php esc_html_e(
                                                'If empty, the site name will be used for the Organisation.name property.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr data-be-schema-org-fields="1">
                                    <th scope="row"><?php esc_html_e( 'Organisation legal name', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input type="text" class="regular-text" name="be_schema_org_legal_name" value="<?php echo esc_attr( $settings['org_legal_name'] ); ?>" />
                                        <p class="description">
                                            <?php esc_html_e(
                                                'Optional. Use this if the legal name differs from the brand name.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr data-be-schema-org-fields="1">
                                    <th scope="row"><?php esc_html_e( 'Organisation URL', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input type="url" class="regular-text" name="be_schema_org_url" value="<?php echo esc_attr( $settings['org_url'] ); ?>" />
                                        <p class="description">
                                            <?php esc_html_e(
                                                'If empty, the site home URL will be used for Organisation.url.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr data-be-schema-org-fields="1">
                                    <th scope="row"><?php esc_html_e( 'Site logo (#logo)', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <?php if ( $has_logo ) : ?>
                                            <div class="be-schema-logo-preview">
                                                <img src="<?php echo esc_url( $settings['org_logo'] ); ?>" alt="" />
                                                <div class="be-schema-logo-meta">
                                                    <code><?php echo esc_html( $logo_id ); ?></code><br />
                                                    <a href="<?php echo esc_url( $settings['org_logo'] ); ?>" target="_blank" rel="noopener noreferrer">
                                                        <?php echo esc_html( $settings['org_logo'] ); ?>
                                                    </a>
                                                </div>
                                            </div>
                                            <p class="description">
                                                <?php esc_html_e(
                                                    'This is the same shared #logo ImageObject configured in the Global panel.',
                                                    'be-schema-engine'
                                                ); ?>
                                            </p>
                                        <?php else : ?>
                                            <p class="description">
                                                <?php esc_html_e(
                                                    'No site logo is set yet. Configure it in the Global panel; when set, it will be used by WebSite, Organisation, and Person (unless a Person profile picture is provided).',
                                                    'be-schema-engine'
                                                ); ?>
                                            </p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>

                            <!-- PUBLISHER PANEL -->
                            <tbody id="be-schema-panel-publisher" class="be-schema-website-panel">
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Publisher', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="be_schema_publisher_enabled" value="1" <?php checked( $settings['publisher_enabled'], '1' ); ?> />
                                            <?php esc_html_e( 'Enable WebSite.publisher.', 'be-schema-engine' ); ?>
                                        </label>
                                        <p class="description">
                                            <?php esc_html_e(
                                                'When this is enabled, the plugin will output WebSite.publisher. You can either fall back to the site Organisation/Person, or define a separate custom publisher Organisation below.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr data-be-schema-publisher-fields="basic">
                                    <th scope="row"><?php esc_html_e( 'Use custom publisher organisation', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="be_schema_publisher_custom_enabled" value="1" <?php checked( $publisher_custom_enabled, true ); ?> />
                                            <?php esc_html_e( 'Define a separate Organisation as the publisher (instead of falling back to the site Organisation/Person).', 'be-schema-engine' ); ?>
                                        </label>
                                        <p class="description">
                                            <?php esc_html_e(
                                                'When this is checked, the fields below describe a dedicated publisher Organisation with @id "#publisher". If you leave this unchecked, WebSite.publisher falls back to the site Organisation (if enabled) or Person (if enabled).',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr data-be-schema-publisher-fields="basic">
                                    <th scope="row"><?php esc_html_e( 'Copyright year', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input type="text" class="small-text" name="be_schema_copyright_year" value="<?php echo esc_attr( $settings['copyright_year'] ); ?>" />
                                        <p class="description">
                                            <?php esc_html_e(
                                                'Optional, used in publishing-related metadata.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr data-be-schema-publisher-fields="basic">
                                    <th scope="row"><?php esc_html_e( 'License URL', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input type="url" class="regular-text" name="be_schema_license_url" value="<?php echo esc_attr( $settings['license_url'] ); ?>" />
                                    </td>
                                </tr>

                                <tr data-be-schema-publisher-fields="basic">
                                    <th scope="row"><?php esc_html_e( 'Publishing principles URL', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input type="url" class="regular-text" name="be_schema_publishing_principles" value="<?php echo esc_attr( $settings['publishing_principles'] ); ?>" />
                                    </td>
                                </tr>

                                <tr data-be-schema-publisher-fields="basic">
                                    <th scope="row"><?php esc_html_e( 'Corrections policy URL', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input type="url" class="regular-text" name="be_schema_corrections_policy" value="<?php echo esc_attr( $settings['corrections_policy'] ); ?>" />
                                    </td>
                                </tr>

                                <tr data-be-schema-publisher-fields="basic">
                                    <th scope="row"><?php esc_html_e( 'Ownership / funding URL', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input type="url" class="regular-text" name="be_schema_ownership_funding" value="<?php echo esc_attr( $settings['ownership_funding'] ); ?>" />
                                    </td>
                                </tr>

                                <!-- Custom publisher organisation fields -->
                                <tr data-be-schema-publisher-fields="custom">
                                    <th scope="row"><?php esc_html_e( 'Custom publisher name', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input type="text" class="regular-text" name="be_schema_publisher_custom_name" value="<?php echo esc_attr( $settings['publisher_custom_name'] ); ?>" />
                                        <p class="description">
                                            <?php esc_html_e(
                                                'Name of the custom publisher Organisation. Used when "Use custom publisher organisation" is enabled.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr data-be-schema-publisher-fields="custom">
                                    <th scope="row"><?php esc_html_e( 'Custom publisher URL', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input type="url" class="regular-text" name="be_schema_publisher_custom_url" value="<?php echo esc_attr( $settings['publisher_custom_url'] ); ?>" />
                                        <p class="description">
                                            <?php esc_html_e(
                                                'Homepage URL for the custom publisher Organisation.',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr data-be-schema-publisher-fields="custom">
                                    <th scope="row"><?php esc_html_e( 'Custom publisher logo', 'be-schema-engine' ); ?></th>
                                    <td>
                                        <input
                                            type="url"
                                            class="regular-text"
                                            id="be_schema_publisher_custom_logo"
                                            name="be_schema_publisher_custom_logo"
                                            value="<?php echo esc_attr( $settings['publisher_custom_logo'] ); ?>"
                                        />
                                        <button
                                            type="button"
                                            class="button be-schema-media-select"
                                            data-target-input="#be_schema_publisher_custom_logo"
                                            data-target-preview="#be-schema-publisher-logo-preview"
                                        >
                                            <?php esc_html_e( 'Select image', 'be-schema-engine' ); ?>
                                        </button>
                                        <button
                                            type="button"
                                            class="button be-schema-media-clear"
                                            data-target-input="#be_schema_publisher_custom_logo"
                                            data-target-preview="#be-schema-publisher-logo-preview"
                                        >
                                            <?php esc_html_e( 'Clear', 'be-schema-engine' ); ?>
                                        </button>

                                        <p class="description">
                                            <?php esc_html_e(
                                                'Optional distinct logo for the custom publisher (#publisher-logo).',
                                                'be-schema-engine'
                                            ); ?>
                                        </p>

                                        <div id="be-schema-publisher-logo-preview" class="be-schema-logo-preview">
                                            <?php if ( $has_publisher_logo ) : ?>
                                                <img src="<?php echo esc_url( $settings['publisher_custom_logo'] ); ?>" alt="" />
                                                <div class="be-schema-logo-meta">
                                                    <code><?php echo esc_html( $publisher_logo_id ); ?></code><br />
                                                    <a href="<?php echo esc_url( $settings['publisher_custom_logo'] ); ?>" target="_blank" rel="noopener noreferrer">
                                                        <?php echo esc_html( $settings['publisher_custom_logo'] ); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <p class="submit">
                <button type="submit" name="be_schema_engine_settings_submit" class="button-primary">
                    <?php esc_html_e( 'Save Changes', 'be-schema-engine' ); ?>
                </button>
            </p>
        </form>
    </div>

    <script>
    (function($){
        document.addEventListener('DOMContentLoaded', function() {
            // Top-level nav tabs (Settings / Website).
            var tabLinks = document.querySelectorAll('.nav-tab-wrapper .nav-tab');
            var tabPanels = document.querySelectorAll('.be-schema-tab-panel');

            tabLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    var target = this.getAttribute('href');

                    tabLinks.forEach(function(l) { l.classList.remove('nav-tab-active'); });
                    this.classList.add('nav-tab-active');

                    tabPanels.forEach(function(panel) {
                        panel.style.display = ( '#' + panel.id === target ) ? 'block' : 'none';
                    });
                });
            });

            // Website vertical nav.
            var verticalButtons = document.querySelectorAll('.be-schema-website-nav .button');
            var websitePanels   = document.querySelectorAll('.be-schema-website-panel');

            function activateWebsitePanel(panelName) {
                websitePanels.forEach(function(panel) {
                    if ( panel.id === 'be-schema-panel-' + panelName ) {
                        panel.classList.add('be-schema-website-panel-active');
                    } else {
                        panel.classList.remove('be-schema-website-panel-active');
                    }
                });

                verticalButtons.forEach(function(btn) {
                    if ( btn.getAttribute('data-panel') === panelName ) {
                        btn.classList.add('button-primary');
                    } else {
                        btn.classList.remove('button-primary');
                    }
                });
            }

            verticalButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var panel = this.getAttribute('data-panel');
                    activateWebsitePanel(panel);
                });
            });

            // Initial state (Global panel already active via markup).

            // Toggle Person fields when Person enabled/disabled.
            (function(){
                var personCheckbox = document.querySelector('input[name="be_schema_person_enabled"]');
                var personRows      = document.querySelectorAll('[data-be-schema-person-fields="1"]');

                function updatePersonVisibility() {
                    var enabled = personCheckbox && personCheckbox.checked;
                    personRows.forEach(function(row) {
                        row.style.display = enabled ? '' : 'none';
                    });
                }

                if ( personCheckbox ) {
                    personCheckbox.addEventListener('change', updatePersonVisibility);
                    updatePersonVisibility();
                }
            })();

            // Toggle Organisation fields when Organisation enabled/disabled.
            (function(){
                var orgCheckbox = document.querySelector('input[name="be_schema_organization_enabled"]');
                var orgRows     = document.querySelectorAll('[data-be-schema-org-fields="1"]');

                function updateOrgVisibility() {
                    var enabled = orgCheckbox && orgCheckbox.checked;
                    orgRows.forEach(function(row) {
                        row.style.display = enabled ? '' : 'none';
                    });
                }

                if ( orgCheckbox ) {
                    orgCheckbox.addEventListener('change', updateOrgVisibility);
                    updateOrgVisibility();
                }
            })();

            // Toggle Publisher fields when Publisher enabled/disabled and custom publisher enabled/disabled.
            (function(){
                var pubCheckbox       = document.querySelector('input[name="be_schema_publisher_enabled"]');
                var pubCustomCheckbox = document.querySelector('input[name="be_schema_publisher_custom_enabled"]');
                var pubBasicRows      = document.querySelectorAll('[data-be-schema-publisher-fields="basic"]');
                var pubCustomRows     = document.querySelectorAll('[data-be-schema-publisher-fields="custom"]');

                function updatePublisherVisibility() {
                    var enabled      = pubCheckbox && pubCheckbox.checked;
                    var customOn     = pubCustomCheckbox && pubCustomCheckbox.checked;

                    pubBasicRows.forEach(function(row) {
                        row.style.display = enabled ? '' : 'none';
                    });

                    pubCustomRows.forEach(function(row) {
                        row.style.display = (enabled && customOn) ? '' : 'none';
                    });
                }

                if ( pubCheckbox ) {
                    pubCheckbox.addEventListener('change', updatePublisherVisibility);
                }
                if ( pubCustomCheckbox ) {
                    pubCustomCheckbox.addEventListener('change', updatePublisherVisibility);
                }
                updatePublisherVisibility();
            })();

            // Media library selector (logo, person image, featured images, publisher logo).
            (function(){
                var frame;
                function openMedia(event) {
                    event.preventDefault();
                    var button         = event.currentTarget;
                    var targetInputSel = button.getAttribute('data-target-input');
                    var targetPrevSel  = button.getAttribute('data-target-preview');
                    var targetInput    = document.querySelector(targetInputSel);
                    var targetPreview  = document.querySelector(targetPrevSel);

                    if ( ! targetInput ) {
                        return;
                    }

                    if ( frame ) {
                        frame.open();
                        return;
                    }

                    frame = wp.media({
                        title: '<?php echo esc_js( __( 'Select image', 'be-schema-engine' ) ); ?>',
                        button: {
                            text: '<?php echo esc_js( __( 'Use this image', 'be-schema-engine' ) ); ?>'
                        },
                        multiple: false
                    });

                    frame.on( 'select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        if ( attachment && attachment.url ) {
                            targetInput.value = attachment.url;
                            if ( targetPreview ) {
                                targetPreview.innerHTML =
                                    '<img src="' + attachment.url + '" alt="" />' +
                                    '<div class="be-schema-logo-meta">' +
                                    '<a href="' + attachment.url + '" target="_blank" rel="noopener noreferrer">' +
                                    attachment.url +
                                    '</a></div>';
                            }
                        }
                    });

                    frame.open();
                }

                var mediaButtons = document.querySelectorAll('.be-schema-media-select');
                mediaButtons.forEach(function(button) {
                    button.addEventListener('click', openMedia);
                });

                var clearButtons = document.querySelectorAll('.be-schema-media-clear');
                clearButtons.forEach(function(button) {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        var targetInputSel = button.getAttribute('data-target-input');
                        var targetPrevSel  = button.getAttribute('data-target-preview');
                        var targetInput    = document.querySelector(targetInputSel);
                        var targetPreview  = document.querySelector(targetPrevSel);

                        if ( targetInput ) {
                            targetInput.value = '';
                        }
                        if ( targetPreview ) {
                            targetPreview.innerHTML = '';
                        }
                    });
                });
            })();

        });
    })(jQuery);
    </script>
    <?php
}