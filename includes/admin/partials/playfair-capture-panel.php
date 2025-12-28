<?php
/**
 * Playfair capture panel (shared).
 *
 * Expected variables (optional):
 * - $playfair_capture_context (schema|social)
 * - $playfair_capture_selector (schema|validator)
 * - $playfair_capture_id (string)
 * - $playfair_capture_defaults (array)
 * - $playfair_capture_show_schema (bool)
 * - $playfair_capture_show_og (bool)
 * - $playfair_capture_show_twitter (bool)
 * - $playfair_capture_show_html (bool)
 * - $playfair_capture_show_logs (bool)
 */

$playfair_capture_context  = isset( $playfair_capture_context ) ? $playfair_capture_context : 'schema';
$playfair_capture_selector = isset( $playfair_capture_selector ) ? $playfair_capture_selector : $playfair_capture_context;
$playfair_capture_id       = isset( $playfair_capture_id ) ? $playfair_capture_id : 'be-schema-playfair';

if ( isset( $playfair_capture_defaults ) && is_array( $playfair_capture_defaults ) ) {
    $playfair_defaults = $playfair_capture_defaults;
} elseif ( function_exists( 'be_schema_admin_get_playfair_defaults' ) ) {
    $playfair_defaults = be_schema_admin_get_playfair_defaults();
} else {
    $playfair_defaults = array(
        'mode'         => 'auto',
        'profile'      => 'desktop_chromium',
        'wait_ms'      => 1500,
        'include_html' => false,
        'include_logs' => true,
        'locale'       => '',
        'timezone'     => '',
    );
}

$playfair_capture_show_schema  = isset( $playfair_capture_show_schema ) ? (bool) $playfair_capture_show_schema : true;
$playfair_capture_show_og      = isset( $playfair_capture_show_og ) ? (bool) $playfair_capture_show_og : false;
$playfair_capture_show_twitter = isset( $playfair_capture_show_twitter ) ? (bool) $playfair_capture_show_twitter : false;
$playfair_capture_show_html    = isset( $playfair_capture_show_html ) ? (bool) $playfair_capture_show_html : false;
$playfair_capture_show_logs    = isset( $playfair_capture_show_logs ) ? (bool) $playfair_capture_show_logs : true;
?>
<div class="be-schema-playfair-capture"
     data-playfair-context="<?php echo esc_attr( $playfair_capture_context ); ?>"
     data-playfair-selector="<?php echo esc_attr( $playfair_capture_selector ); ?>"
     data-playfair-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
     data-playfair-nonce="<?php echo esc_attr( wp_create_nonce( 'be_schema_playfair_capture' ) ); ?>"
     data-playfair-home="<?php echo esc_url( home_url( '/' ) ); ?>">
    <div class="be-schema-playfair-form">
        <div class="be-schema-playfair-row">
            <label for="<?php echo esc_attr( $playfair_capture_id . '-profile' ); ?>"><?php esc_html_e( 'Profile', 'beseo' ); ?></label>
            <select id="<?php echo esc_attr( $playfair_capture_id . '-profile' ); ?>" data-playfair-role="profile">
                <option value="desktop_chromium" <?php selected( 'desktop_chromium', $playfair_defaults['profile'] ); ?>><?php esc_html_e( 'Desktop (Chromium)', 'beseo' ); ?></option>
                <option value="mobile_chromium" <?php selected( 'mobile_chromium', $playfair_defaults['profile'] ); ?>><?php esc_html_e( 'Mobile (Chromium)', 'beseo' ); ?></option>
                <option value="webkit" <?php selected( 'webkit', $playfair_defaults['profile'] ); ?>><?php esc_html_e( 'WebKit', 'beseo' ); ?></option>
            </select>
            <label for="<?php echo esc_attr( $playfair_capture_id . '-wait' ); ?>"><?php esc_html_e( 'Wait (ms)', 'beseo' ); ?></label>
            <input type="number"
                   id="<?php echo esc_attr( $playfair_capture_id . '-wait' ); ?>"
                   class="small-text"
                   data-playfair-role="wait"
                   value="<?php echo esc_attr( $playfair_defaults['wait_ms'] ); ?>"
                   min="0"
                   max="60000"
                   step="100" />
            <label for="<?php echo esc_attr( $playfair_capture_id . '-locale' ); ?>"><?php esc_html_e( 'Locale', 'beseo' ); ?></label>
            <input type="text"
                   id="<?php echo esc_attr( $playfair_capture_id . '-locale' ); ?>"
                   class="regular-text"
                   data-playfair-role="locale"
                   value="<?php echo esc_attr( $playfair_defaults['locale'] ); ?>"
                   placeholder="en-US" />
            <label for="<?php echo esc_attr( $playfair_capture_id . '-timezone' ); ?>"><?php esc_html_e( 'Timezone', 'beseo' ); ?></label>
            <input type="text"
                   id="<?php echo esc_attr( $playfair_capture_id . '-timezone' ); ?>"
                   class="regular-text"
                   data-playfair-role="timezone"
                   value="<?php echo esc_attr( $playfair_defaults['timezone'] ); ?>"
                   placeholder="America/New_York" />
        </div>
        <div class="be-schema-playfair-row">
            <?php if ( $playfair_capture_show_html ) : ?>
                <label>
                    <input type="checkbox"
                           id="<?php echo esc_attr( $playfair_capture_id . '-include-html' ); ?>"
                           data-playfair-role="include-html"
                           <?php checked( $playfair_defaults['include_html'] ); ?> />
                    <?php esc_html_e( 'Include HTML', 'beseo' ); ?>
                </label>
            <?php endif; ?>
            <?php if ( $playfair_capture_show_logs ) : ?>
                <label>
                    <input type="checkbox"
                           id="<?php echo esc_attr( $playfair_capture_id . '-include-logs' ); ?>"
                           data-playfair-role="include-logs"
                           <?php checked( $playfair_defaults['include_logs'] ); ?> />
                    <?php esc_html_e( 'Include logs', 'beseo' ); ?>
                </label>
            <?php endif; ?>
        </div>
        <div class="be-schema-playfair-actions">
            <button type="button" class="button button-primary" data-playfair-role="run">
                <?php esc_html_e( 'Run Capture', 'beseo' ); ?>
            </button>
        </div>
        <div class="be-schema-playfair-status" data-playfair-role="status" aria-live="polite"></div>
        <div class="be-schema-playfair-meta" data-playfair-role="meta"></div>
        <div class="be-schema-playfair-target" data-playfair-role="target"></div>
    </div>

    <div class="be-schema-playfair-results" data-playfair-role="results">
        <?php if ( $playfair_capture_show_schema ) : ?>
            <div class="be-schema-playfair-grid">
                <div class="be-schema-playfair-panel">
                    <h4><?php esc_html_e( 'Schema (DOM)', 'beseo' ); ?></h4>
                    <details>
                        <summary><?php esc_html_e( 'View JSON', 'beseo' ); ?></summary>
                        <pre class="be-schema-playfair-pre" data-playfair-role="schema-dom"></pre>
                    </details>
                </div>
                <div class="be-schema-playfair-panel">
                    <h4><?php esc_html_e( 'Schema (Server)', 'beseo' ); ?></h4>
                    <details>
                        <summary><?php esc_html_e( 'View JSON', 'beseo' ); ?></summary>
                        <pre class="be-schema-playfair-pre" data-playfair-role="schema-server"></pre>
                    </details>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( $playfair_capture_show_og || $playfair_capture_show_twitter ) : ?>
            <div class="be-schema-playfair-grid">
                <?php if ( $playfair_capture_show_og ) : ?>
                    <div class="be-schema-playfair-panel">
                        <h4><?php esc_html_e( 'Open Graph (DOM)', 'beseo' ); ?></h4>
                        <details>
                            <summary><?php esc_html_e( 'View JSON', 'beseo' ); ?></summary>
                            <pre class="be-schema-playfair-pre" data-playfair-role="og-dom"></pre>
                        </details>
                    </div>
                <?php endif; ?>
                <?php if ( $playfair_capture_show_twitter ) : ?>
                    <div class="be-schema-playfair-panel">
                        <h4><?php esc_html_e( 'Twitter (DOM)', 'beseo' ); ?></h4>
                        <details>
                            <summary><?php esc_html_e( 'View JSON', 'beseo' ); ?></summary>
                            <pre class="be-schema-playfair-pre" data-playfair-role="twitter-dom"></pre>
                        </details>
                    </div>
                <?php endif; ?>
                <?php if ( $playfair_capture_show_og ) : ?>
                    <div class="be-schema-playfair-panel">
                        <h4><?php esc_html_e( 'Open Graph (Server)', 'beseo' ); ?></h4>
                        <details>
                            <summary><?php esc_html_e( 'View JSON', 'beseo' ); ?></summary>
                            <pre class="be-schema-playfair-pre" data-playfair-role="og-server"></pre>
                        </details>
                    </div>
                <?php endif; ?>
                <?php if ( $playfair_capture_show_twitter ) : ?>
                    <div class="be-schema-playfair-panel">
                        <h4><?php esc_html_e( 'Twitter (Server)', 'beseo' ); ?></h4>
                        <details>
                            <summary><?php esc_html_e( 'View JSON', 'beseo' ); ?></summary>
                            <pre class="be-schema-playfair-pre" data-playfair-role="twitter-server"></pre>
                        </details>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ( $playfair_capture_show_html ) : ?>
            <div class="be-schema-playfair-grid" data-playfair-role="html">
                <div class="be-schema-playfair-panel">
                    <h4><?php esc_html_e( 'HTML (DOM)', 'beseo' ); ?></h4>
                    <details>
                        <summary><?php esc_html_e( 'View HTML', 'beseo' ); ?></summary>
                        <pre class="be-schema-playfair-pre" data-playfair-role="html-dom"></pre>
                    </details>
                </div>
                <div class="be-schema-playfair-panel">
                    <h4><?php esc_html_e( 'HTML (Server)', 'beseo' ); ?></h4>
                    <details>
                        <summary><?php esc_html_e( 'View HTML', 'beseo' ); ?></summary>
                        <pre class="be-schema-playfair-pre" data-playfair-role="html-server"></pre>
                    </details>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( $playfair_capture_show_logs ) : ?>
            <div class="be-schema-playfair-grid" data-playfair-role="logs">
                <div class="be-schema-playfair-panel">
                    <h4><?php esc_html_e( 'Console', 'beseo' ); ?></h4>
                    <details>
                        <summary><?php esc_html_e( 'View JSON', 'beseo' ); ?></summary>
                        <pre class="be-schema-playfair-pre" data-playfair-role="logs-console"></pre>
                    </details>
                </div>
                <div class="be-schema-playfair-panel">
                    <h4><?php esc_html_e( 'Page Errors', 'beseo' ); ?></h4>
                    <details>
                        <summary><?php esc_html_e( 'View JSON', 'beseo' ); ?></summary>
                        <pre class="be-schema-playfair-pre" data-playfair-role="logs-pageerrors"></pre>
                    </details>
                </div>
                <div class="be-schema-playfair-panel">
                    <h4><?php esc_html_e( 'Request Failed', 'beseo' ); ?></h4>
                    <details>
                        <summary><?php esc_html_e( 'View JSON', 'beseo' ); ?></summary>
                        <pre class="be-schema-playfair-pre" data-playfair-role="logs-requestfailed"></pre>
                    </details>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
