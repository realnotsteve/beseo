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
    </div>
    <?php
}
