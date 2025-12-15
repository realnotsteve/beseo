<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the Tools submenu page.
 *
 * For now, all diagnostics and status information live under
 * Schema → Settings, but this page provides quick entry points.
 */
function be_schema_engine_render_tools_page() {
    $tools_default_tab = 'dashboard';
    $current_page      = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    $requested_tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
    $is_settings_submenu = ( 'beseo-settings' === $current_page );
    $help_notice         = '';
    $help_overrides      = array();

    wp_enqueue_script(
        'be-schema-help-accent',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'includes/admin/js/be-help-accent.js',
        array(),
        BE_SCHEMA_ENGINE_VERSION,
        true
    );

    if ( $is_settings_submenu ) {
        if ( function_exists( 'be_schema_help_overrides_handle_request' ) ) {
            $help_notice = be_schema_help_overrides_handle_request();
        }
        if ( function_exists( 'be_schema_help_overrides_get' ) ) {
            $help_overrides = be_schema_help_overrides_get();
        }
    }
    if ( $is_settings_submenu ) {
        // Default to Help tab if explicitly requested or after a save postback.
        if ( ( $requested_tab && 'help' === $requested_tab ) || ! empty( $_POST['be_schema_help_overrides_nonce'] ) ) {
            $tools_default_tab = 'help';
        } else {
            $tools_default_tab = 'settings';
        }
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'BE Schema Engine – Tools', 'beseo' ); ?></h1>
        <style>
            .be-schema-help-accent {
                color: #00a0d2;
            }
            .be-schema-tools-panel {
                margin-top: 16px;
                display: none;
            }
            .be-schema-tools-panel.active {
                display: block;
            }
            .nav-tab-wrapper {
                margin-top: 12px;
            }
            /* Fallback styling in case admin nav-tab CSS is not present */
            .nav-tab {
                display: inline-block;
                padding: 8px 14px;
                border: 1px solid #c3c4c7;
                border-bottom: none;
                background: #f6f7f7;
                color: #50575e;
                text-decoration: none;
                margin-right: 4px;
                border-radius: 3px 3px 0 0;
            }
            .nav-tab-active {
                background: #fff;
                color: #1d2327;
                border-bottom: 1px solid #fff;
            }
        </style>

        <h2 class="nav-tab-wrapper">
            <?php if ( ! $is_settings_submenu ) : ?>
                <a href="#be-schema-tools-dashboard" class="nav-tab<?php echo ( 'dashboard' === $tools_default_tab ) ? ' nav-tab-active' : ''; ?>" data-tools-tab="dashboard"><?php esc_html_e( 'Dashboard', 'beseo' ); ?></a>
            <?php endif; ?>
            <a href="#be-schema-tools-settings" class="nav-tab<?php echo ( 'settings' === $tools_default_tab ) ? ' nav-tab-active' : ''; ?>" data-tools-tab="settings"><?php esc_html_e( 'Settings', 'beseo' ); ?></a>
            <?php if ( $is_settings_submenu ) : ?>
                <a href="#be-schema-tools-help" class="nav-tab<?php echo ( 'help' === $tools_default_tab ) ? ' nav-tab-active' : ''; ?>" data-tools-tab="help"><?php esc_html_e( 'Help Text', 'beseo' ); ?></a>
            <?php endif; ?>
            <?php if ( ! $is_settings_submenu ) : ?>
                <a href="#be-schema-tools-images" class="nav-tab<?php echo ( 'images' === $tools_default_tab ) ? ' nav-tab-active' : ''; ?>" data-tools-tab="images"><?php esc_html_e( 'Images', 'beseo' ); ?></a>
            <?php endif; ?>
        </h2>

        <?php if ( ! $is_settings_submenu ) : ?>
            <div id="be-schema-tools-dashboard" class="be-schema-tools-panel<?php echo ( 'dashboard' === $tools_default_tab ) ? ' active' : ''; ?>">
                <p class="description">
                    <?php esc_html_e(
                        'Diagnostic information and effective status summaries live under Schema → Settings. Use this dashboard for quick reminders and links.',
                        'beseo'
                    ); ?>
                </p>
                <ul class="ul-disc">
                    <li><?php esc_html_e( 'Check Schema → Settings for debug, dry run, and image validation toggles.', 'beseo' ); ?></li>
                    <li><?php esc_html_e( 'Visit Schema → Snapshots to review the last BE_SCHEMA_DEBUG snapshot when debug is enabled.', 'beseo' ); ?></li>
                    <li><?php esc_html_e( 'Open Graph/Twitter dry-run toggles live under Platforms → Facebook/Twitter → Tools.', 'beseo' ); ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <div id="be-schema-tools-settings" class="be-schema-tools-panel<?php echo ( 'settings' === $tools_default_tab ) ? ' active' : ''; ?>">
            <p class="description">
                <?php esc_html_e(
                    'Quick access to core toggles lives here. Use Schema → Settings and Social → Dashboard/Platforms for full control.',
                    'beseo'
                ); ?>
            </p>
        </div>

        <?php if ( $is_settings_submenu ) : ?>
            <div id="be-schema-tools-help" class="be-schema-tools-panel<?php echo ( 'help' === $tools_default_tab ) ? ' active' : ''; ?>">
                <?php
                if ( function_exists( 'be_schema_help_overrides_render_form' ) ) {
                    be_schema_help_overrides_render_form( $help_overrides, $help_notice, false );
                } else {
                    echo '<p class="description">' . esc_html__( 'Help overrides are unavailable.', 'beseo' ) . '</p>';
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if ( ! $is_settings_submenu ) : ?>
            <div id="be-schema-tools-images" class="be-schema-tools-panel<?php echo ( 'images' === $tools_default_tab ) ? ' active' : ''; ?>">
                <p class="description">
                    <?php esc_html_e(
                        'Image helpers: use Schema → Website → Global/Person/Publisher for recommended aspect ratios and validation pills. More tools coming soon.',
                    'beseo'
                ); ?>
            </p>
                <p>
                    <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=beseo-schema#website' ) ); ?>">
                        <?php esc_html_e( 'Go to Schema Images', 'beseo' ); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tabs = document.querySelectorAll('.nav-tab-wrapper a[data-tools-tab]');
            var panels = document.querySelectorAll('.be-schema-tools-panel');
            var defaultTab = '<?php echo esc_js( $tools_default_tab ); ?>';

            function activateTab(key) {
                tabs.forEach(function(tab) {
                    if (tab.getAttribute('data-tools-tab') === key) {
                        tab.classList.add('nav-tab-active');
                    } else {
                        tab.classList.remove('nav-tab-active');
                    }
                });
                panels.forEach(function(panel) {
                    if (panel.id === 'be-schema-tools-' + key) {
                        panel.classList.add('active');
                    } else {
                        panel.classList.remove('active');
                    }
                });
            }

            tabs.forEach(function(tab) {
                tab.addEventListener('click', function(event) {
                    event.preventDefault();
                    activateTab(tab.getAttribute('data-tools-tab'));
                });
            });

            activateTab(defaultTab || 'dashboard');
        });
    </script>
    <?php
}
