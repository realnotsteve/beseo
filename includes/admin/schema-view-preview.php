<?php
/**
 * Schema view partial: Preview tab.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function be_schema_engine_render_schema_tab_preview() {
    ?>
    <h2><?php esc_html_e( 'Preview', 'beseo' ); ?></h2>
    <p class="description be-schema-description">
        <?php esc_html_e( 'Preview the JSON-LD graph that would be emitted for a specific page.', 'beseo' ); ?>
    </p>

    <div class="be-schema-global-section be-schema-preview-target">
        <h4 class="be-schema-section-title"><?php esc_html_e( 'Target', 'beseo' ); ?></h4>
        <p class="description be-schema-description">
            <?php esc_html_e( 'Enter a URL or a post ID, then generate the schema graph preview.', 'beseo' ); ?>
        </p>
        <div class="be-schema-preview-inputs">
            <input type="text" id="be-schema-preview-target" class="regular-text" placeholder="<?php esc_attr_e( 'https://example.com/page or 123', 'beseo' ); ?>" />
            <div class="be-schema-preview-buttons">
                <button type="button" class="button button-secondary" id="be-schema-preview-home">
                    <?php esc_html_e( 'Use Homepage', 'beseo' ); ?>
                </button>
                <button type="button" class="button button-primary" id="be-schema-preview-run">
                    <?php esc_html_e( 'Generate Preview', 'beseo' ); ?>
                </button>
            </div>
        </div>
        <div id="be-schema-preview-status" class="be-schema-preview-status" aria-live="polite"></div>
        <div class="be-schema-preview-meta">
            <div><strong><?php esc_html_e( 'Nodes', 'beseo' ); ?></strong>: <span id="be-schema-preview-node-count">0</span></div>
            <div><strong><?php esc_html_e( 'Edges', 'beseo' ); ?></strong>: <span id="be-schema-preview-edge-count">0</span></div>
        </div>
    </div>

    <div class="be-schema-preview-columns">
        <div class="be-schema-preview-column" id="be-schema-preview-column-internal">
            <div class="be-schema-preview-column-header">
                <span class="be-schema-preview-column-title"><?php esc_html_e( 'Internal', 'beseo' ); ?></span>
                <button type="button" class="button-link be-schema-preview-collapse" aria-expanded="true" aria-controls="be-schema-preview-internal-body" aria-label="<?php esc_attr_e( 'Collapse', 'beseo' ); ?>" data-collapse-label="<?php esc_attr_e( 'Collapse', 'beseo' ); ?>" data-expand-label="<?php esc_attr_e( 'Expand', 'beseo' ); ?>">
                    <span class="screen-reader-text"><?php esc_html_e( 'Collapse', 'beseo' ); ?></span>
                </button>
            </div>
            <div class="be-schema-preview-column-body" id="be-schema-preview-internal-body">
                <div class="be-schema-preview-section">
                    <div class="be-schema-preview-section-header">
                        <span class="be-schema-preview-section-title"><?php esc_html_e( 'Graph', 'beseo' ); ?></span>
                        <button type="button" class="button-link be-schema-preview-section-toggle" aria-expanded="true" aria-controls="be-schema-preview-graph-wrap" aria-label="<?php esc_attr_e( 'Collapse', 'beseo' ); ?>" data-collapse-label="<?php esc_attr_e( 'Collapse', 'beseo' ); ?>" data-expand-label="<?php esc_attr_e( 'Expand', 'beseo' ); ?>">
                            <span class="screen-reader-text"><?php esc_html_e( 'Collapse', 'beseo' ); ?></span>
                        </button>
                    </div>
                    <div class="be-schema-preview-section-body" id="be-schema-preview-graph-wrap">
                        <div id="be-schema-preview-graph" class="be-schema-preview-graph"></div>
                    </div>
                </div>
                <div class="be-schema-preview-section">
                    <div class="be-schema-preview-section-header">
                        <span class="be-schema-preview-section-title" id="be-schema-preview-json-label"><?php esc_html_e( 'Raw JSON-LD', 'beseo' ); ?></span>
                        <button type="button" class="button-link be-schema-preview-section-toggle" aria-expanded="true" aria-controls="be-schema-preview-json-wrap" aria-label="<?php esc_attr_e( 'Collapse', 'beseo' ); ?>" data-collapse-label="<?php esc_attr_e( 'Collapse', 'beseo' ); ?>" data-expand-label="<?php esc_attr_e( 'Expand', 'beseo' ); ?>">
                            <span class="screen-reader-text"><?php esc_html_e( 'Collapse', 'beseo' ); ?></span>
                        </button>
                    </div>
                    <div class="be-schema-preview-section-body" id="be-schema-preview-json-wrap">
                        <pre class="be-schema-preview-json" role="textbox" aria-readonly="true" aria-labelledby="be-schema-preview-json-label"><code id="be-schema-preview-json" class="be-schema-json-code"></code></pre>
                    </div>
                </div>
            </div>
        </div>
        <div class="be-schema-preview-column" id="be-schema-preview-column-third-party">
            <div class="be-schema-preview-column-header">
                <span class="be-schema-preview-column-title"><?php esc_html_e( 'Third-Party Output', 'beseo' ); ?></span>
                <button type="button" class="button-link be-schema-preview-collapse" aria-expanded="true" aria-controls="be-schema-preview-third-party-body" aria-label="<?php esc_attr_e( 'Collapse', 'beseo' ); ?>" data-collapse-label="<?php esc_attr_e( 'Collapse', 'beseo' ); ?>" data-expand-label="<?php esc_attr_e( 'Expand', 'beseo' ); ?>">
                    <span class="screen-reader-text"><?php esc_html_e( 'Collapse', 'beseo' ); ?></span>
                </button>
            </div>
            <div class="be-schema-preview-column-body" id="be-schema-preview-third-party-body">
                <p class="be-schema-preview-empty"><?php esc_html_e( 'No third-party output loaded.', 'beseo' ); ?></p>
            </div>
        </div>
        <div class="be-schema-preview-column" id="be-schema-preview-column-full">
            <div class="be-schema-preview-column-header">
                <span class="be-schema-preview-column-title"><?php esc_html_e( 'Full Output', 'beseo' ); ?></span>
                <button type="button" class="button-link be-schema-preview-collapse" aria-expanded="true" aria-controls="be-schema-preview-full-body" aria-label="<?php esc_attr_e( 'Collapse', 'beseo' ); ?>" data-collapse-label="<?php esc_attr_e( 'Collapse', 'beseo' ); ?>" data-expand-label="<?php esc_attr_e( 'Expand', 'beseo' ); ?>">
                    <span class="screen-reader-text"><?php esc_html_e( 'Collapse', 'beseo' ); ?></span>
                </button>
            </div>
            <div class="be-schema-preview-column-body" id="be-schema-preview-full-body">
                <p class="be-schema-preview-empty"><?php esc_html_e( 'No full output loaded.', 'beseo' ); ?></p>
            </div>
        </div>
    </div>
    <?php
}
