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

    <div class="be-schema-preview-upper">
        <div class="be-schema-global-section be-schema-preview-target">
            <h4 class="be-schema-section-title"><?php esc_html_e( 'Target', 'beseo' ); ?></h4>
            <p class="description be-schema-description">
                <?php esc_html_e( 'Enter a URL or a post ID, then generate schema output with Playfair.', 'beseo' ); ?>
            </p>
            <div class="be-schema-preview-inputs">
                <input type="text" id="be-schema-preview-target" class="regular-text" placeholder="<?php esc_attr_e( 'https://example.com/page or 123', 'beseo' ); ?>" />
                <div class="be-schema-preview-buttons">
                    <button type="button" class="button button-secondary" id="be-schema-preview-home">
                        <?php esc_html_e( 'Use Homepage', 'beseo' ); ?>
                    </button>
                </div>
            </div>
            <p class="be-schema-preview-help" id="be-schema-preview-target-help"></p>
        </div>

        <div class="be-schema-global-section be-schema-preview-criteria">
            <h4 class="be-schema-section-title"><?php esc_html_e( 'Render Criteria', 'beseo' ); ?></h4>
            <div class="be-schema-preview-criteria-grid">
                <div class="be-schema-preview-field" data-control="location">
                    <span class="be-schema-preview-label"><?php esc_html_e( 'Render Location', 'beseo' ); ?></span>
                    <div class="be-schema-preview-options">
                        <label><input type="radio" name="be_schema_preview_location" value="internal" checked /> <?php esc_html_e( 'Internal', 'beseo' ); ?></label>
                        <label><input type="radio" name="be_schema_preview_location" value="external" /> <?php esc_html_e( 'External', 'beseo' ); ?></label>
                    </div>
                    <p class="be-schema-preview-help" id="be-schema-preview-location-help"></p>
                </div>
                <div class="be-schema-preview-field" data-control="capture">
                    <span class="be-schema-preview-label"><?php esc_html_e( 'Capture Mode', 'beseo' ); ?></span>
                    <div class="be-schema-preview-options">
                        <label><input type="radio" name="be_schema_preview_capture" value="server" checked /> <?php esc_html_e( 'Server', 'beseo' ); ?></label>
                        <label><input type="radio" name="be_schema_preview_capture" value="dom" /> <?php esc_html_e( 'DOM', 'beseo' ); ?></label>
                    </div>
                </div>
                <div class="be-schema-preview-field" data-control="view">
                    <span class="be-schema-preview-label"><?php esc_html_e( 'View', 'beseo' ); ?></span>
                    <div class="be-schema-preview-options">
                        <label><input type="radio" name="be_schema_preview_view" value="graph" checked /> <?php esc_html_e( 'Graph', 'beseo' ); ?></label>
                        <label><input type="radio" name="be_schema_preview_view" value="json" /> <?php esc_html_e( 'JSON-LD', 'beseo' ); ?></label>
                    </div>
                </div>
                <div class="be-schema-preview-field" data-control="colour">
                    <label class="be-schema-preview-label" for="be-schema-preview-colour"><?php esc_html_e( 'Colour', 'beseo' ); ?></label>
                    <select id="be-schema-preview-colour">
                        <option value="keyword"><?php esc_html_e( 'Keyword', 'beseo' ); ?></option>
                        <option value="source"><?php esc_html_e( 'Source', 'beseo' ); ?></option>
                    </select>
                    <p class="be-schema-preview-help" id="be-schema-preview-colour-help"></p>
                </div>
                <div class="be-schema-preview-field" data-control="beseo">
                    <label class="be-schema-preview-label">
                        <input type="checkbox" id="be-schema-preview-add-beseo" checked />
                        <?php esc_html_e( 'Add BE SEO', 'beseo' ); ?>
                    </label>
                    <p class="be-schema-preview-help" id="be-schema-preview-beseo-help"></p>
                </div>
            </div>
            <p class="be-schema-preview-note"><?php esc_html_e( 'Schema extraction currently supports JSON-LD only (Microdata/RDFa not supported yet).', 'beseo' ); ?></p>
            <div class="be-schema-preview-actions">
                <button type="button" class="button button-primary" id="be-schema-preview-create">
                    <?php esc_html_e( 'Create', 'beseo' ); ?>
                </button>
                <button type="button" class="button" id="be-schema-preview-refresh-all">
                    <?php esc_html_e( 'Refresh All', 'beseo' ); ?>
                </button>
                <button type="button" class="button" id="be-schema-preview-clear-cache">
                    <?php esc_html_e( 'Clear Cache', 'beseo' ); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="be-schema-preview-columns" id="be-schema-preview-columns"></div>

    <div class="be-schema-global-section be-schema-preview-diff" id="be-schema-preview-diff">
        <h4 class="be-schema-section-title"><?php esc_html_e( 'Diff', 'beseo' ); ?></h4>
        <p class="description"><?php esc_html_e( 'Select two columns to compare their JSON-LD output.', 'beseo' ); ?></p>
        <div class="be-schema-preview-diff-body" id="be-schema-preview-diff-body"></div>
    </div>
    <?php
}
