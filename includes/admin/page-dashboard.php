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
    <div class="wrap beseo-dashboard-wrap">
        <h1><?php esc_html_e( 'BE Schema Engine', 'beseo' ); ?></h1>

        <p class="description">
            <?php esc_html_e(
                'BE Schema Engine centrally manages JSON-LD schema for your site with a conservative, safety-first approach.',
                'beseo'
            ); ?>
        </p>

        <h2><?php esc_html_e( 'Where to start', 'beseo' ); ?></h2>
        <p>
            <?php esc_html_e(
                'Use the submenus in the left-hand navigation to configure and inspect the engine:',
                'beseo'
            ); ?>
        </p>

        <ul class="ul-disc">
            <li>
                <strong><?php esc_html_e( 'Schema', 'beseo' ); ?></strong> –
                <?php esc_html_e( 'Enable/disable the engine, view diagnostics and configure site-level entities (Person, Organisation, Publisher, logos).', 'beseo' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Social Media', 'beseo' ); ?></strong> –
                <?php esc_html_e( 'Reserved for future sameAs / social profile settings.', 'beseo' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Analyser', 'beseo' ); ?></strong> –
                <?php esc_html_e( 'Diagnostics and testers for Schema, Social, and Playfair.', 'beseo' ); ?>
            </li>
        </ul>

        <p class="description">
            <?php esc_html_e(
                'Tip: Most configuration and debugging lives under the Schema submenu.',
                'beseo'
            ); ?>
        </p>
    </div>
    <?php
}
