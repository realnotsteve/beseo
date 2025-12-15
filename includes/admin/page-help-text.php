<?php
/**
 * Help Text Overrides admin page + gettext override wiring.
 *
 * @package BESEO
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle POSTed help override updates.
 *
 * @return string Notice text.
 */
function be_schema_help_overrides_handle_request() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return '';
    }

    if ( empty( $_POST['be_schema_help_overrides_nonce'] ) ) {
        return '';
    }

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['be_schema_help_overrides_nonce'] ) ), 'be_schema_help_overrides_save' ) ) {
        return '';
    }

    $existing_originals = isset( $_POST['be_schema_help_original'] ) ? (array) wp_unslash( $_POST['be_schema_help_original'] ) : array();
    $existing_overrides = isset( $_POST['be_schema_help_override'] ) ? (array) wp_unslash( $_POST['be_schema_help_override'] ) : array();

    $new_map = array();

    foreach ( $existing_originals as $idx => $original ) {
        $original_raw = trim( (string) $original );
        $override_raw = isset( $existing_overrides[ $idx ] ) ? trim( (string) $existing_overrides[ $idx ] ) : '';
        if ( '' === $original_raw || '' === $override_raw ) {
            continue;
        }
        $new_map[ $original_raw ] = wp_kses_post( $override_raw );
    }

    $new_original = isset( $_POST['be_schema_help_new_original'] ) ? trim( wp_unslash( $_POST['be_schema_help_new_original'] ) ) : '';
    $new_override = isset( $_POST['be_schema_help_new_override'] ) ? trim( wp_unslash( $_POST['be_schema_help_new_override'] ) ) : '';

    if ( '' !== $new_original && '' !== $new_override ) {
        $new_map[ $new_original ] = wp_kses_post( $new_override );
    }

    be_schema_help_overrides_save( $new_map );

    return __( 'Help text overrides updated.', 'beseo' );
}

/**
 * Load help text overrides once per request.
 *
 * @return array
 */
function be_schema_help_overrides_get() {
    global $be_schema_help_overrides_cache;

    if ( null !== $be_schema_help_overrides_cache && is_array( $be_schema_help_overrides_cache ) ) {
        return $be_schema_help_overrides_cache;
    }

    $overrides = get_option( 'be_schema_help_overrides', array() );
    if ( ! is_array( $overrides ) ) {
        $overrides = array();
    }

    // Trim keys/values defensively.
    $clean = array();
    foreach ( $overrides as $original => $override ) {
        $original = trim( (string) $original );
        $override = trim( (string) $override );
        if ( '' === $original || '' === $override ) {
            continue;
        }
        $clean[ $original ] = $override;
    }

    $be_schema_help_overrides_cache = $clean;

    return $clean;
}

/**
 * Render the help overrides form/table.
 *
 * @param array  $overrides Map of original => override.
 * @param string $notice    Optional notice text.
 * @param bool   $wrap      Whether to wrap in .wrap and heading.
 */
function be_schema_help_overrides_render_form( $overrides, $notice = '', $wrap = true ) {
    if ( $wrap ) :
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Help Text Overrides', 'beseo' ); ?></h1>
        <?php
    endif;

    if ( $notice ) :
        ?>
        <div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
        <?php
    endif;
    ?>
    <p class="description">
        <?php esc_html_e( 'Override help/description text without changing code. Leave an override blank to remove it and fall back to the built-in wording.', 'beseo' ); ?>
    </p>

    <form method="post">
        <?php wp_nonce_field( 'be_schema_help_overrides_save', 'be_schema_help_overrides_nonce' ); ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th style="width:45%;"><?php esc_html_e( 'Original Help Text', 'beseo' ); ?></th>
                    <th><?php esc_html_e( 'Override (leave empty to remove)', 'beseo' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $overrides ) ) : ?>
                    <tr>
                        <td colspan="2"><em><?php esc_html_e( 'No overrides yet.', 'beseo' ); ?></em></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $overrides as $original => $override ) : ?>
                        <tr>
                            <td>
                                <textarea name="be_schema_help_original[]" class="large-text code" rows="3" readonly><?php echo esc_textarea( $original ); ?></textarea>
                            </td>
                            <td>
                                <textarea name="be_schema_help_override[]" class="large-text code" rows="3"><?php echo esc_textarea( $override ); ?></textarea>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <tr>
                    <td>
                        <label for="be_schema_help_new_original" class="screen-reader-text"><?php esc_html_e( 'Original help text', 'beseo' ); ?></label>
                        <textarea name="be_schema_help_new_original" id="be_schema_help_new_original" class="large-text code" rows="3" placeholder="<?php esc_attr_e( 'New: original help text to override', 'beseo' ); ?>"></textarea>
                    </td>
                    <td>
                        <label for="be_schema_help_new_override" class="screen-reader-text"><?php esc_html_e( 'Override text', 'beseo' ); ?></label>
                        <textarea name="be_schema_help_new_override" id="be_schema_help_new_override" class="large-text code" rows="3" placeholder="<?php esc_attr_e( 'New: override text', 'beseo' ); ?>"></textarea>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Overrides', 'beseo' ); ?></button>
        </p>
    </form>

    <p class="description">
        <?php esc_html_e( 'Tip: copy the exact help text you want to change into the Original box, add your wording in Override, and save.', 'beseo' ); ?>
    </p>
    <?php

    if ( $wrap ) :
        ?>
        </div>
        <?php
    endif;
}

/**
 * Persist overrides and reset cache.
 *
 * @param array $map Keyed by original string => override string.
 * @return void
 */
function be_schema_help_overrides_save( $map ) {
    update_option( 'be_schema_help_overrides', $map );
    // Reset cache for this request.
    $GLOBALS['be_schema_help_overrides_cache'] = null;
}

/**
 * gettext filter to swap help/descriptive strings when an override exists.
 *
 * Overrides apply only to the 'beseo' text domain.
 *
 * @param string $translated Translated text.
 * @param string $text       Original text.
 * @param string $domain     Text domain.
 *
 * @return string
 */
function be_schema_help_override_gettext( $translated, $text, $domain ) {
    if ( 'beseo' !== $domain ) {
        return $translated;
    }

    $overrides = be_schema_help_overrides_get();
    if ( empty( $overrides ) ) {
        return $translated;
    }

    if ( isset( $overrides[ $text ] ) ) {
        return $overrides[ $text ];
    }

    return $translated;
}
add_filter( 'gettext', 'be_schema_help_override_gettext', 20, 3 );

/**
 * Render the Help Text Overrides admin page.
 */
function be_schema_engine_render_help_text_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $notice    = be_schema_help_overrides_handle_request();
    $overrides = be_schema_help_overrides_get();

    be_schema_help_overrides_render_form( $overrides, $notice, true );
}
