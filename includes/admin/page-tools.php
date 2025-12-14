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
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'BE Schema Engine – Tools', 'beseo' ); ?></h1>
        <style>
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
            <a href="#be-schema-tools-dashboard" class="nav-tab nav-tab-active" data-tools-tab="dashboard"><?php esc_html_e( 'Dashboard', 'beseo' ); ?></a>
            <a href="#be-schema-tools-images" class="nav-tab" data-tools-tab="images"><?php esc_html_e( 'Images', 'beseo' ); ?></a>
        </h2>

        <div id="be-schema-tools-dashboard" class="be-schema-tools-panel active">
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

        <div id="be-schema-tools-images" class="be-schema-tools-panel">
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
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tabs = document.querySelectorAll('.nav-tab-wrapper a[data-tools-tab]');
            var panels = document.querySelectorAll('.be-schema-tools-panel');

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
        });
    </script>
    <?php
}
