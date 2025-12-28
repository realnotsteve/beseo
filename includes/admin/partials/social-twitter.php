<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$be_schema_social_twitter_cards_partial = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/social-twitter-cards.php';
?>
<!-- TWITTER TAB -->
<div id="be-schema-social-tab-twitter" class="be-schema-social-tab-panel">
    <h2><?php esc_html_e( 'Twitter Settings', 'beseo' ); ?></h2>

    <div class="be-schema-social-layout">
        <div class="be-schema-social-nav">
            <ul>
                <li>
                    <a href="#be-schema-twitter-overview"
                       class="be-schema-social-subtab be-schema-social-subtab-active"
                       data-twitter-tab="overview">
                        <?php esc_html_e( 'Overview', 'beseo' ); ?>
                    </a>
                </li>
                <li>
                    <a href="#be-schema-twitter-content"
                       class="be-schema-social-subtab"
                       data-twitter-tab="content">
                        <?php esc_html_e( 'Cards', 'beseo' ); ?>
                    </a>
                </li>
                <li>
                    <a href="#be-schema-twitter-tools"
                       class="be-schema-social-subtab"
                       data-twitter-tab="tools">
                        <?php esc_html_e( 'Tools', 'beseo' ); ?>
                    </a>
                </li>
            </ul>
        </div>

        <div class="be-schema-social-panels">
            <div id="be-schema-twitter-overview" class="be-schema-social-panel be-schema-social-panel-active">
                <div class="be-schema-social-section">
                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Overview', 'beseo' ); ?></h4>
                    <label>
                        <input type="checkbox"
                               name="be_schema_twitter_enabled"
                               value="1"
                               <?php checked( $twitter_enabled ); ?> />
                        <?php esc_html_e(
                            'Enable Twitter Cards (twitter:* tags) for supported pages.',
                            'beseo'
                        ); ?>
                    </label>
                    <p class="description be-schema-social-description">
                        <?php esc_html_e(
                            'When enabled, the plugin will output Twitter Card tags for pages and posts using the rules described below.',
                            'beseo'
                        ); ?>
                    </p>
                </div>
                <div class="be-schema-social-section">
                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Status', 'beseo' ); ?></h4>
                    <p>
                        <span class="be-schema-social-status-pill <?php echo $twitter_enabled ? '' : 'off'; ?>">
                            <?php echo $twitter_enabled ? esc_html__( 'Twitter Cards: ON', 'beseo' ) : esc_html__( 'Twitter Cards: OFF', 'beseo' ); ?>
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

            <div id="be-schema-twitter-content" class="be-schema-social-panel">
                <?php
                $be_schema_social_twitter_cards_section_id = '';
                $be_schema_social_twitter_cards_split_sections = true;
                $be_schema_social_twitter_summary_description = __(
                    'Optional secondary fallback for twitter:image. If empty, Twitter follows the usual order: featured image → Large Summary Card image → Global default.',
                    'beseo'
                );
                if ( file_exists( $be_schema_social_twitter_cards_partial ) ) {
                    include $be_schema_social_twitter_cards_partial;
                }
                ?>
            </div>

            <div id="be-schema-twitter-tools" class="be-schema-social-panel">
                <div class="be-schema-social-section">
                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Tools', 'beseo' ); ?></h4>
                    <p class="description be-schema-social-description">
                        <?php esc_html_e(
                            'Use Analyser → Social Tests for Twitter Card validation and previews.',
                            'beseo'
                        ); ?>
                    </p>
                </div>
                <div class="be-schema-social-section">
                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Dry Run', 'beseo' ); ?></h4>
                    <p class="description be-schema-social-description" style="margin-top:0;">
                        <?php esc_html_e( 'Use dry run to compute values but skip outputting Twitter meta tags on the front end.', 'beseo' ); ?>
                    </p>
                    <label>
                        <input type="checkbox"
                               name="be_schema_twitter_dry_run"
                               value="1"
                               <?php checked( $twitter_dry_run ); ?> />
                        <?php esc_html_e( 'Enable Twitter native content dry run. (Do not output meta tags.)', 'beseo' ); ?>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
