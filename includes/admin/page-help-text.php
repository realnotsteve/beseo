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
 * Collect candidate help strings from BESEO translation calls.
 *
 * @return array
 */
function be_schema_help_overrides_get_candidates() {
    static $cached = null;
    if ( null !== $cached ) {
        return $cached;
    }

    $version = defined( 'BE_SCHEMA_ENGINE_VERSION' ) ? BE_SCHEMA_ENGINE_VERSION : '1';
    $version .= '-description';
    $stored_version = get_option( 'be_schema_help_override_candidates_version', '' );
    $stored = get_option( 'be_schema_help_override_candidates', array() );
    if ( $stored_version === $version && is_array( $stored ) && ! empty( $stored ) ) {
        $cached = array_values( array_unique( array_map( 'trim', $stored ) ) );
        return $cached;
    }

    $strings = array();
    $base_dir = defined( 'BE_SCHEMA_ENGINE_PLUGIN_DIR' ) ? BE_SCHEMA_ENGINE_PLUGIN_DIR : '';
    if ( $base_dir && is_dir( $base_dir ) ) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $base_dir, FilesystemIterator::SKIP_DOTS )
        );
        $pattern = '/\b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\(\s*([\'"])(.*?)\1\s*,\s*[\'"]beseo[\'"]\s*\)/s';

        foreach ( $iterator as $file ) {
            if ( ! $file->isFile() ) {
                continue;
            }
            if ( 'php' !== strtolower( $file->getExtension() ) ) {
                continue;
            }
            $contents = file_get_contents( $file->getPathname() );
            if ( false === $contents || '' === $contents ) {
                continue;
            }
            if ( preg_match_all( $pattern, $contents, $matches, PREG_OFFSET_CAPTURE ) ) {
                foreach ( $matches[2] as $idx => $match ) {
                    $value = trim( stripcslashes( $match[0] ) );
                    if ( '' === $value ) {
                        continue;
                    }

                    $offset = isset( $matches[0][ $idx ][1] ) ? (int) $matches[0][ $idx ][1] : 0;
                    $context_start = max( 0, $offset - 300 );
                    $context = substr( $contents, $context_start, 300 );
                    if ( ! preg_match( '/class=["\'][^"\']*description[^"\']*["\']/', $context ) ) {
                        continue;
                    }

                    $strings[] = $value;
                }
            }
        }
    }

    $strings = array_values( array_unique( $strings ) );
    sort( $strings );

    update_option( 'be_schema_help_override_candidates', $strings, false );
    update_option( 'be_schema_help_override_candidates_version', $version, false );

    $cached = $strings;

    return $cached;
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

    <style>
        .be-schema-help-layout {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .be-schema-global-section {
            border: 1px solid #ccd0d4;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 16px;
            background: #f9fafb;
            color: #111;
        }
        .be-schema-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: -15px -15px 12px;
            padding: 12px 15px;
            background: #e1e4e8;
            color: #111;
        }
        .be-schema-help-body {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .be-schema-help-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .be-schema-help-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .be-schema-help-row textarea {
            flex: 1 1 0;
        }
        .be-schema-help-button {
            width: 30px;
            height: 30px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .be-schema-help-remove {
            text-decoration: none;
        }
        .be-schema-help-toggle {
            text-decoration: none;
            border: none;
            background: transparent;
            padding: 0;
            margin: 0;
            cursor: pointer;
        }
        .be-schema-help-toggle::before {
            content: "\25BE";
            font-size: 18px;
            line-height: 1;
        }
        .be-schema-help-section.is-collapsed .be-schema-help-body {
            display: none;
        }
        .be-schema-help-section.is-collapsed .be-schema-help-toggle::before {
            content: "\25B8";
        }
        .be-schema-help-headers {
            display: flex;
            gap: 12px;
            font-size: 11px;
            color: #9ca3af;
        }
        .be-schema-help-headers span {
            flex: 1 1 0;
        }
        #be-schema-help-new-original::placeholder,
        #be-schema-help-new-override::placeholder {
            color: #9ca3af;
        }
        .be-schema-help-footer {
            margin-top: 10px;
            font-size: 12px;
            color: #4b5563;
        }
        .be-schema-help-divider {
            border: 0;
            border-top: 1px solid #dcdcde;
            margin: 8px 0;
        }
    </style>

    <form method="post">
        <?php wp_nonce_field( 'be_schema_help_overrides_save', 'be_schema_help_overrides_nonce' ); ?>
        <div class="be-schema-help-layout">
            <div class="be-schema-global-section be-schema-help-section is-collapsed" id="be-schema-help-existing">
                <div class="be-schema-section-title">
                    <button type="button" class="be-schema-help-toggle" aria-expanded="false">
                        <span class="screen-reader-text"><?php esc_html_e( 'Expand', 'beseo' ); ?></span>
                    </button>
                    <strong><?php esc_html_e( 'Existing', 'beseo' ); ?></strong>
                </div>
                <div class="be-schema-help-body">
                    <div class="be-schema-help-headers">
                        <span><?php esc_html_e( 'Text to find', 'beseo' ); ?></span>
                        <span><?php esc_html_e( 'Replacement text', 'beseo' ); ?></span>
                    </div>
                    <div class="be-schema-help-list" id="be-schema-help-list">
                        <?php if ( empty( $overrides ) ) : ?>
                            <p class="description" id="be-schema-help-empty"><?php esc_html_e( 'No overrides yet.', 'beseo' ); ?></p>
                        <?php else : ?>
                            <?php foreach ( $overrides as $original => $override ) : ?>
                                <div class="be-schema-help-row">
                                    <button type="button" class="button be-schema-help-button be-schema-help-remove" aria-label="<?php esc_attr_e( 'Remove override', 'beseo' ); ?>">−</button>
                                    <textarea name="be_schema_help_original[]" class="large-text code" rows="2" readonly><?php echo esc_textarea( $original ); ?></textarea>
                                    <textarea name="be_schema_help_override[]" class="large-text code" rows="2"><?php echo esc_textarea( $override ); ?></textarea>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <hr class="be-schema-help-divider" />
                </div>
                <div class="be-schema-help-footer">
                    <?php esc_html_e( 'Number of Overrides:', 'beseo' ); ?>
                    <span id="be-schema-help-count"><?php echo isset( $overrides ) && is_array( $overrides ) ? esc_html( (string) count( $overrides ) ) : '0'; ?></span>
                </div>
            </div>

            <div class="be-schema-global-section">
                <div class="be-schema-section-title">
                    <strong><?php esc_html_e( 'Add New Override', 'beseo' ); ?></strong>
                </div>
                <div class="be-schema-help-body">
                    <div class="be-schema-help-row">
                        <button type="button" class="button be-schema-help-button" id="be-schema-help-add" aria-label="<?php esc_attr_e( 'Add override', 'beseo' ); ?>">+</button>
                        <textarea id="be-schema-help-new-original" class="large-text code" rows="2" placeholder="<?php esc_attr_e( 'Text to find', 'beseo' ); ?>"></textarea>
                        <textarea id="be-schema-help-new-override" class="large-text code" rows="2" placeholder="<?php esc_attr_e( 'Replacement text', 'beseo' ); ?>"></textarea>
                    </div>
                    <p class="description" id="be-schema-help-status"></p>
                </div>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Overrides', 'beseo' ); ?></button>
        </p>
    </form>

    <p class="description">
        <?php esc_html_e( 'Tip: copy the exact help text you want to change into the Original box, add your wording in Override, and save.', 'beseo' ); ?>
    </p>
    <script>
        (function() {
            var candidates = <?php echo wp_json_encode( be_schema_help_overrides_get_candidates() ); ?>;
            var candidateSet = {};
            if (Array.isArray(candidates)) {
                candidates.forEach(function(value) {
                    candidateSet[String(value).trim()] = true;
                });
            }

            function setStatus(message) {
                var status = document.getElementById('be-schema-help-status');
                if (!status) {
                    return;
                }
                status.textContent = message || '';
            }

            function ensureEmptyNotice() {
                var list = document.getElementById('be-schema-help-list');
                if (!list) {
                    return;
                }
                var rows = list.querySelectorAll('.be-schema-help-row');
                var empty = document.getElementById('be-schema-help-empty');
                if (!rows.length) {
                    if (!empty) {
                        empty = document.createElement('p');
                        empty.className = 'description';
                        empty.id = 'be-schema-help-empty';
                        empty.textContent = '<?php echo esc_js( __( 'No overrides yet.', 'beseo' ) ); ?>';
                        list.appendChild(empty);
                    }
                } else if (empty) {
                    empty.remove();
                }
            }

            function updateOverrideCount() {
                var list = document.getElementById('be-schema-help-list');
                var count = document.getElementById('be-schema-help-count');
                if (!list || !count) {
                    return;
                }
                var rows = list.querySelectorAll('.be-schema-help-row');
                count.textContent = rows.length;
            }

            function addOverrideRow(findText, replaceText) {
                var list = document.getElementById('be-schema-help-list');
                if (!list) {
                    return;
                }

                var rows = list.querySelectorAll('.be-schema-help-row textarea[readonly]');
                for (var i = 0; i < rows.length; i++) {
                    if (rows[i].value.trim() === findText) {
                        setStatus('<?php echo esc_js( __( 'That help text already has an override.', 'beseo' ) ); ?>');
                        return;
                    }
                }

                var row = document.createElement('div');
                row.className = 'be-schema-help-row';

                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'button be-schema-help-button be-schema-help-remove';
                removeBtn.setAttribute('aria-label', '<?php echo esc_js( __( 'Remove override', 'beseo' ) ); ?>');
                removeBtn.textContent = '−';
                removeBtn.addEventListener('click', function() {
                    row.remove();
                    ensureEmptyNotice();
                });

                var find = document.createElement('textarea');
                find.name = 'be_schema_help_original[]';
                find.className = 'large-text code';
                find.rows = 2;
                find.readOnly = true;
                find.value = findText;

                var replace = document.createElement('textarea');
                replace.name = 'be_schema_help_override[]';
                replace.className = 'large-text code';
                replace.rows = 2;
                replace.value = replaceText;

                row.appendChild(removeBtn);
                row.appendChild(find);
                row.appendChild(replace);
                list.appendChild(row);
                ensureEmptyNotice();
                updateOverrideCount();
            }

            document.addEventListener('DOMContentLoaded', function() {
                var addBtn = document.getElementById('be-schema-help-add');
                var findInput = document.getElementById('be-schema-help-new-original');
                var replaceInput = document.getElementById('be-schema-help-new-override');
                var existingSection = document.getElementById('be-schema-help-existing');
                var toggle = existingSection ? existingSection.querySelector('.be-schema-help-toggle') : null;

                if (toggle && existingSection) {
                    toggle.addEventListener('click', function() {
                        var collapsed = existingSection.classList.toggle('is-collapsed');
                        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                        var sr = toggle.querySelector('.screen-reader-text');
                        if (sr) {
                            sr.textContent = collapsed ? '<?php echo esc_js( __( 'Expand', 'beseo' ) ); ?>' : '<?php echo esc_js( __( 'Collapse', 'beseo' ) ); ?>';
                        }
                    });
                }

                var removeButtons = document.querySelectorAll('.be-schema-help-remove');
                removeButtons.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var row = btn.closest('.be-schema-help-row');
                        if (row) {
                            row.remove();
                            ensureEmptyNotice();
                            updateOverrideCount();
                        }
                    });
                });

                if (!addBtn || !findInput || !replaceInput) {
                    return;
                }

                addBtn.addEventListener('click', function() {
                    var findText = findInput.value.trim();
                    var replaceText = replaceInput.value.trim();

                    if (!findText || !replaceText) {
                        setStatus('<?php echo esc_js( __( 'Enter both Text to find and Replacement text.', 'beseo' ) ); ?>');
                        return;
                    }

                    if (!candidateSet[findText]) {
                        setStatus('<?php echo esc_js( __( 'Text to find must match an existing help text in the plugin.', 'beseo' ) ); ?>');
                        return;
                    }

                    addOverrideRow(findText, replaceText);
                    findInput.value = '';
                    replaceInput.value = '';
                    setStatus('<?php echo esc_js( __( 'Override added.', 'beseo' ) ); ?>');
                });

                ensureEmptyNotice();
                updateOverrideCount();
            });
        })();
    </script>
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
