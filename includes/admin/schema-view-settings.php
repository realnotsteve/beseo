<?php
/**
 * Schema view partial: Dashboard + Options tabs.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function be_schema_engine_render_schema_tab_settings( $settings, $wp_debug, $debug_enabled, $dry_run, $enabled, $elementor_enabled, $image_validation_enabled ) {
    ?>
    <div id="be-schema-settings" class="be-schema-tab <?php echo ( isset( $_GET['tab'] ) && 'website' !== $_GET['tab'] ) ? 'active' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>">
        <h2><?php esc_html_e( 'Dashboard', 'beseo' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Operational toggles now live under Status → Operation, and advanced switches are under Options.', 'beseo' ); ?></p>
    </div>
    <?php
}

function be_schema_engine_render_schema_tab_options( $wp_debug, $debug_enabled, $dry_run, $image_validation_enabled ) {
    ?>
    <h2><?php esc_html_e( 'Options', 'beseo' ); ?></h2>
    <div class="be-schema-global-section">
        <h4 class="be-schema-section-title"><?php esc_html_e( 'Safety', 'beseo' ); ?></h4>
        <table class="form-table">
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
