<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the top-level BE Schema Engine dashboard page.
 *
 * This is the page you see when you click the top-level menu item.
 * It’s intentionally simple and just points users to the submenus.
 */
function be_schema_engine_render_dashboard_page() {
    ?>
    <div class="wrap be-schema-engine-dashboard-wrap">
        <h1><?php esc_html_e( 'BE Schema Engine', 'be-schema-engine' ); ?></h1>

        <p class="description">
            <?php esc_html_e(
                'BE Schema Engine centrally manages JSON-LD schema for your site with a conservative, safety-first approach.',
                'be-schema-engine'
            ); ?>
        </p>

        <h2><?php esc_html_e( 'Where to start', 'be-schema-engine' ); ?></h2>
        <p>
            <?php esc_html_e(
                'Use the submenus in the left-hand navigation to configure and inspect the engine:',
                'be-schema-engine'
            ); ?>
        </p>

        <ul class="ul-disc">
            <li>
                <strong><?php esc_html_e( 'Schema', 'be-schema-engine' ); ?></strong> –
                <?php esc_html_e( 'Enable/disable the engine, view diagnostics and configure site-level entities (Person, Organisation, Publisher, logos).', 'be-schema-engine' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Social Media', 'be-schema-engine' ); ?></strong> –
                <?php esc_html_e( 'Reserved for future sameAs / social profile settings.', 'be-schema-engine' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Tools', 'be-schema-engine' ); ?></strong> –
                <?php esc_html_e( 'Currently points you to the main diagnostics under Schema → Settings.', 'be-schema-engine' ); ?>
            </li>
        </ul>

        <p class="description">
            <?php esc_html_e(
                'Tip: Most configuration and debugging lives under the Schema submenu.',
                'be-schema-engine'
            ); ?>
        </p>
    </div>
    <?php
}