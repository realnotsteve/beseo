<?php
/**
 * Schema view partial: Settings tab.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function be_schema_engine_render_schema_tab_settings( $settings, $wp_debug, $debug_enabled, $dry_run, $enabled, $elementor_enabled, $image_validation_enabled ) {
    ?>
    <div id="be-schema-settings" class="be-schema-tab <?php echo ( isset( $_GET['tab'] ) && 'website' !== $_GET['tab'] ) ? 'active' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>">
        <h2><?php esc_html_e( 'Settings', 'beseo' ); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Enable BE Schema Engine', 'beseo' ); ?></th>
                <td>
                    <label><input type="checkbox" name="be_schema_enabled" value="1" <?php checked( $enabled ); ?> /> <?php esc_html_e( 'Enable schema output', 'beseo' ); ?></label>
                    <p class="description"><?php esc_html_e( 'Toggle global schema output on your site.', 'beseo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Elementor integration', 'beseo' ); ?></th>
                <td>
                    <label><input type="checkbox" name="be_schema_elementor_enabled" value="1" <?php checked( $elementor_enabled ); ?> /> <?php esc_html_e( 'Enable Elementor widgets', 'beseo' ); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Debug logging', 'beseo' ); ?></th>
                <td>
                    <label><input type="checkbox" name="be_schema_debug" value="1" <?php checked( $debug_enabled ); ?> /> <?php esc_html_e( 'Enable debug output (WP_DEBUG required)', 'beseo' ); ?></label>
                    <?php if ( ! $wp_debug ) : ?>
                        <p class="description" style="color:#b00;"><?php esc_html_e( 'WP_DEBUG is off; enable in wp-config.php to capture debug logs.', 'beseo' ); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Dry run', 'beseo' ); ?></th>
                <td>
                    <label><input type="checkbox" name="be_schema_dry_run" value="1" <?php checked( $dry_run ); ?> /> <?php esc_html_e( 'Generate but do not output schema', 'beseo' ); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Image validation', 'beseo' ); ?></th>
                <td>
                    <label><input type="checkbox" name="be_schema_image_validation_enabled" value="1" <?php checked( $image_validation_enabled ); ?> /> <?php esc_html_e( 'Warn on missing or invalid schema images', 'beseo' ); ?></label>
                </td>
            </tr>
        </table>

        <?php
        $playfair_vps_endpoint   = isset( $settings['playfair_vps_endpoint'] ) ? $settings['playfair_vps_endpoint'] : '';
        $playfair_vps_token      = isset( $settings['playfair_vps_token'] ) ? $settings['playfair_vps_token'] : '';
        $playfair_local_endpoint = isset( $settings['playfair_local_endpoint'] ) ? $settings['playfair_local_endpoint'] : '';
        $playfair_target_mode    = isset( $settings['playfair_target_mode'] ) ? $settings['playfair_target_mode'] : 'auto';
        $playfair_default_profile = isset( $settings['playfair_default_profile'] ) ? $settings['playfair_default_profile'] : 'desktop_chromium';
        $playfair_default_wait_ms = isset( $settings['playfair_default_wait_ms'] ) ? (int) $settings['playfair_default_wait_ms'] : 1500;
        ?>

        <h3><?php esc_html_e( 'Playfair Capture', 'beseo' ); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'VPS endpoint', 'beseo' ); ?></th>
                <td>
                    <input type="url" class="regular-text code" name="be_schema_playfair_vps_endpoint" value="<?php echo esc_attr( $playfair_vps_endpoint ); ?>" placeholder="https://playfair.belexes.com/capture" />
                    <p class="description"><?php esc_html_e( 'Remote Playfair capture endpoint (requires token).', 'beseo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'VPS token', 'beseo' ); ?></th>
                <td>
                    <input type="password" class="regular-text" name="be_schema_playfair_vps_token" value="<?php echo esc_attr( $playfair_vps_token ); ?>" autocomplete="off" />
                    <p class="description"><?php esc_html_e( 'Stored securely in settings; never logged.', 'beseo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Local endpoint', 'beseo' ); ?></th>
                <td>
                    <input type="url" class="regular-text code" name="be_schema_playfair_local_endpoint" value="<?php echo esc_attr( $playfair_local_endpoint ); ?>" placeholder="http://host.docker.internal:3719/capture" />
                    <p class="description"><?php esc_html_e( 'Use the Local container endpoint when capturing .local or private URLs.', 'beseo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Target mode', 'beseo' ); ?></th>
                <td>
                    <label style="margin-right:12px;">
                        <input type="radio" name="be_schema_playfair_target_mode" value="auto" <?php checked( 'auto', $playfair_target_mode ); ?> />
                        <?php esc_html_e( 'Auto', 'beseo' ); ?>
                    </label>
                    <label style="margin-right:12px;">
                        <input type="radio" name="be_schema_playfair_target_mode" value="local" <?php checked( 'local', $playfair_target_mode ); ?> />
                        <?php esc_html_e( 'Force Local', 'beseo' ); ?>
                    </label>
                    <label>
                        <input type="radio" name="be_schema_playfair_target_mode" value="vps" <?php checked( 'vps', $playfair_target_mode ); ?> />
                        <?php esc_html_e( 'Force VPS', 'beseo' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Default profile', 'beseo' ); ?></th>
                <td>
                    <select name="be_schema_playfair_default_profile">
                        <option value="desktop_chromium" <?php selected( 'desktop_chromium', $playfair_default_profile ); ?>><?php esc_html_e( 'Desktop (Chromium)', 'beseo' ); ?></option>
                        <option value="mobile_chromium" <?php selected( 'mobile_chromium', $playfair_default_profile ); ?>><?php esc_html_e( 'Mobile (Chromium)', 'beseo' ); ?></option>
                        <option value="webkit" <?php selected( 'webkit', $playfair_default_profile ); ?>><?php esc_html_e( 'WebKit', 'beseo' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Default wait (ms)', 'beseo' ); ?></th>
                <td>
                    <input type="number" class="small-text" name="be_schema_playfair_default_wait_ms" value="<?php echo esc_attr( $playfair_default_wait_ms ); ?>" min="0" max="60000" step="100" />
                </td>
            </tr>
        </table>
    </div>
    <?php
}
