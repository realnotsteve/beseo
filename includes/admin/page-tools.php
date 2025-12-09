<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the Tools submenu page.
 *
 * For now, all diagnostics and status information live under
 * Schema → Settings, so this page simply points users there.
 */
function be_schema_engine_render_tools_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'BE Schema Engine – Tools', 'beseo' ); ?></h1>
        <p class="description">
            <?php esc_html_e(
                'Diagnostic information and effective status summaries have been moved to the Schema → Settings tab.',
                'beseo'
            ); ?>
        </p>
        <p>
            <?php esc_html_e(
                'Go to: BE Schema Engine → Schema, then open the “Settings” tab to view global status, wp-config constants, and a settings snapshot.',
                'beseo'
            ); ?>
        </p>
    </div>
    <?php
}