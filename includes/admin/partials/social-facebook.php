<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$be_schema_social_facebook_content_partial = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/social-facebook-content.php';
?>
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
                <?php
                if ( file_exists( $be_schema_social_facebook_content_partial ) ) {
                    include $be_schema_social_facebook_content_partial;
                }
                ?>
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
                <div class="be-schema-social-section">
                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Safety', 'beseo' ); ?></h4>
                    <p class="description be-schema-social-description" style="margin-top:0;">
                        <?php esc_html_e( 'Use dry run to compute values but skip outputting OpenGraph meta tags on the front end.', 'beseo' ); ?>
                    </p>
                    <label>
                        <input type="checkbox"
                               name="be_schema_social_dry_run"
                               value="1"
                               <?php checked( $social_dry_run ); ?> />
                        <?php esc_html_e( 'Enable OpenGraph dry run (do not output og:* tags)', 'beseo' ); ?>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
