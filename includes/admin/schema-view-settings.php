<?php
/**
 * Schema view partial: Settings tab.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function be_schema_engine_render_schema_tab_settings( $settings, $wp_debug, $debug_enabled, $dry_run, $enabled, $elementor_enabled, $image_validation_enabled ) {
    $global_creator_value = isset( $settings['global_creator_name'] ) ? $settings['global_creator_name'] : '';
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
            <tr>
                <th scope="row"><?php esc_html_e( 'Global creator', 'beseo' ); ?></th>
                <td>
                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                        <input type="text" name="be_schema_global_creator_name" class="regular-text" value="<?php echo esc_attr( $global_creator_value ); ?>" />
                        <select name="be_schema_global_creator_type">
                            <?php
                            $type = isset( $settings['global_creator_type'] ) ? $settings['global_creator_type'] : 'Person';
                            $type = in_array( $type, array( 'Person', 'Organisation' ), true ) ? $type : 'Person';
                            ?>
                            <option value="Person" <?php selected( 'Person', $type ); ?>><?php esc_html_e( 'Person', 'beseo' ); ?></option>
                            <option value="Organisation" <?php selected( 'Organisation', $type ); ?>><?php esc_html_e( 'Organisation', 'beseo' ); ?></option>
                        </select>
                        <button type="button" class="button" id="be-schema-populate-creator-empty"><?php esc_html_e( 'Populate Empty', 'beseo' ); ?></button>
                        <span class="description" id="be-schema-populate-creator-status"></span>
                    </div>
                    <p class="description"><?php esc_html_e( 'Name to use when bulk-filling blank image creator fields. Click “Populate Empty” to write it to images without a creator.', 'beseo' ); ?></p>
                </td>
            </tr>
        </table>
    </div>
    <?php
}
