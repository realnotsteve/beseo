<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$be_schema_social_facebook_content_partial = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/social-facebook-content.php';
$be_schema_social_twitter_cards_partial = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/social-twitter-cards.php';
?>
<div id="be-schema-social-tab-platforms" class="be-schema-social-tab-panel">
    <h2><?php esc_html_e( 'Platforms', 'beseo' ); ?></h2>
    <div class="be-schema-social-layout">
        <div class="be-schema-social-nav">
            <ul>
                <li>
                    <a href="#be-schema-platforms-facebook"
                       class="be-schema-social-subtab be-schema-platforms-subtab be-schema-social-subtab-active"
                       data-platform-tab="facebook">
                        <?php esc_html_e( 'Facebook', 'beseo' ); ?>
                    </a>
                </li>
                <li>
                    <a href="#be-schema-platforms-instagram"
                       class="be-schema-social-subtab be-schema-platforms-subtab"
                       data-platform-tab="instagram">
                        <?php esc_html_e( 'Instagram', 'beseo' ); ?>
                    </a>
                </li>
                <li>
                    <a href="#be-schema-platforms-linkedin"
                       class="be-schema-social-subtab be-schema-platforms-subtab"
                       data-platform-tab="linkedin">
                        <?php esc_html_e( 'LinkedIn', 'beseo' ); ?>
                    </a>
                </li>
                <li>
                    <a href="#be-schema-platforms-tictok"
                       class="be-schema-social-subtab be-schema-platforms-subtab"
                       data-platform-tab="tictok">
                        <?php esc_html_e( 'TikTok', 'beseo' ); ?>
                    </a>
                </li>
                <li>
                    <a href="#be-schema-platforms-twitter"
                       class="be-schema-social-subtab be-schema-platforms-subtab"
                       data-platform-tab="twitter">
                        <?php esc_html_e( 'Twitter', 'beseo' ); ?>
                    </a>
                </li>
            </ul>
        </div>
        <div class="be-schema-social-panels">
            <div id="be-schema-platforms-facebook" class="be-schema-social-panel be-schema-social-panel-active be-schema-platforms-parent-panel">
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

                <?php
                if ( file_exists( $be_schema_social_facebook_content_partial ) ) {
                    include $be_schema_social_facebook_content_partial;
                }
                ?>

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

            <div id="be-schema-platforms-instagram" class="be-schema-social-panel be-schema-platforms-parent-panel">
                <div class="be-schema-social-section">
                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Instagram', 'beseo' ); ?></h4>
                    <p class="description be-schema-social-description">
                        <?php esc_html_e( 'Instagram settings placeholder. Add handles and defaults when available.', 'beseo' ); ?>
                    </p>
                </div>
            </div>
            <div id="be-schema-platforms-linkedin" class="be-schema-social-panel be-schema-platforms-parent-panel">
                <div class="be-schema-social-section">
                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'LinkedIn', 'beseo' ); ?></h4>
                    <p class="description be-schema-social-description">
                        <?php esc_html_e( 'LinkedIn settings placeholder for upcoming options.', 'beseo' ); ?>
                    </p>
                </div>
            </div>
            <div id="be-schema-platforms-tictok" class="be-schema-social-panel be-schema-platforms-parent-panel">
                <div class="be-schema-social-section">
                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'TikTok', 'beseo' ); ?></h4>
                    <p class="description be-schema-social-description">
                        <?php esc_html_e( 'TikTok settings placeholder for future defaults.', 'beseo' ); ?>
                    </p>
                </div>
            </div>
            <div id="be-schema-platforms-twitter" class="be-schema-social-panel be-schema-platforms-parent-panel">
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

                <?php
                $be_schema_social_twitter_cards_section_id = 'be-schema-platforms-twitter-content';
                $be_schema_social_twitter_cards_split_sections = false;
                $be_schema_social_twitter_summary_description = __(
                    'Used for twitter:image when the Summary Card type is selected. If empty, Twitter falls back to the Global default image (if set).',
                    'beseo'
                );
                if ( file_exists( $be_schema_social_twitter_cards_partial ) ) {
                    include $be_schema_social_twitter_cards_partial;
                }
                ?>

                <div class="be-schema-social-section">
                    <h4 class="be-schema-social-section-title"><?php esc_html_e( 'Tools', 'beseo' ); ?></h4>
                    <p class="description be-schema-social-description">
                        <?php esc_html_e(
                            'Use Twitter Card Validator to rescrape after changing images or titles.',
                            'beseo'
                        ); ?>
                        <br />
                        <a href="https://cards-dev.twitter.com/validator"
                           target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e( 'Open Twitter Card Validator', 'beseo' ); ?>
                        </a>
                    </p>
                    <p class="description be-schema-social-description" style="margin-top:0;">
                        <?php esc_html_e( 'Use dry run to compute values but skip outputting Twitter Card meta tags on the front end.', 'beseo' ); ?>
                    </p>
                    <label>
                        <input type="checkbox"
                               name="be_schema_twitter_dry_run"
                               value="1"
                               <?php checked( $twitter_dry_run ); ?> />
                        <?php esc_html_e( 'Enable Twitter dry run (do not output twitter:* tags)', 'beseo' ); ?>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
