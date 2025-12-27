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
            <div class="be-schema-preview-inputs">
                <label class="be-schema-preview-selector-label"><?php esc_html_e( 'Selector', 'beseo' ); ?></label>
                <div class="be-schema-preview-selector-box">
                    <div class="be-schema-preview-selector-grid">
                        <div class="be-schema-preview-local-column">
                            <div class="be-schema-preview-local-box">
                                <label class="be-schema-preview-selector-inline">
                                    <input type="radio" name="be-schema-preview-env" id="be-schema-preview-env-local" value="local" checked />
                                    <span><?php esc_html_e( 'Local', 'beseo' ); ?></span>
                                </label>
                                <label class="be-schema-preview-selector-inline">
                                    <input type="radio" name="be-schema-preview-env" id="be-schema-preview-env-remote" value="remote" />
                                    <span><?php esc_html_e( 'Remote', 'beseo' ); ?></span>
                                </label>
                            </div>
                        </div>
                        <span class="be-schema-preview-selector-divider be-schema-preview-selector-divider-full" aria-hidden="true"></span>
                        <div class="be-schema-preview-selector-rows">
                            <div class="be-schema-preview-selector-row">
                                <label><input type="radio" name="be-schema-preview-target-mode" value="site" checked /> <?php esc_html_e( 'Websites', 'beseo' ); ?></label>
                                <label><input type="radio" name="be-schema-preview-target-mode" value="manual" /> <?php esc_html_e( 'Manual URL', 'beseo' ); ?></label>
                                <select id="be-schema-preview-site" class="regular-text be-schema-preview-url" style="display:inline-block;">
                                    <option value="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( home_url( '/' ) ); ?></option>
                                </select>
                                <input type="text" id="be-schema-preview-target" class="regular-text be-schema-preview-url" placeholder="<?php esc_attr_e( 'https://example.com/ or 123', 'beseo' ); ?>" style="display:none;" />
                                <label class="be-schema-preview-selector-inline">
                                    <input type="checkbox" id="be-schema-preview-include-posts" />
                                    <span><?php esc_html_e( 'Include Posts', 'beseo' ); ?></span>
                                </label>
                                <label class="be-schema-preview-selector-inline">
                                    <span><?php esc_html_e( 'Max Posts', 'beseo' ); ?></span>
                                    <input type="number" id="be-schema-preview-max-posts" class="small-text" value="25" min="1" max="500" style="width:80px;" disabled />
                                </label>
                            </div>
                            <div class="be-schema-preview-selector-row">
                                <button type="button" class="button button-primary" id="be-schema-preview-list-pages"><?php esc_html_e( 'List Pages', 'beseo' ); ?></button>
                                <label class="be-schema-preview-selector-inline">
                                    <span><?php esc_html_e( 'Subpage(s)', 'beseo' ); ?></span>
                                    <select id="be-schema-preview-subpages" class="regular-text" disabled>
                                        <option value=""><?php esc_html_e( 'None', 'beseo' ); ?></option>
                                    </select>
                                </label>
                                <label class="be-schema-preview-selector-inline">
                                    <span><?php esc_html_e( 'Max Site Pages', 'beseo' ); ?></span>
                                    <input type="number" id="be-schema-preview-site-limit" class="small-text" value="25" min="1" max="500" style="width:80px;" />
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="be-schema-preview-buttons">
                    <button type="button" class="button" id="be-schema-preview-health">
                        <?php esc_html_e( 'Health Check', 'beseo' ); ?>
                    </button>
                    <button type="button" class="button" id="be-schema-preview-test">
                        <?php esc_html_e( 'Run Test', 'beseo' ); ?>
                    </button>
                </div>
            </div>
            <p class="be-schema-preview-help" id="be-schema-preview-target-help"></p>
            <div class="be-schema-preview-status" id="be-schema-preview-target-status" aria-live="polite"></div>
        </div>

        <div class="be-schema-global-section be-schema-preview-criteria">
            <h4 class="be-schema-section-title"><?php esc_html_e( 'Render Criteria', 'beseo' ); ?></h4>
            <div class="be-schema-preview-criteria-row">
                <div class="be-schema-preview-criteria-grid">
                    <div class="be-schema-preview-field" data-control="capture">
                        <span class="be-schema-preview-label"><?php esc_html_e( 'Capture Mode', 'beseo' ); ?></span>
                        <div class="be-schema-preview-options">
                            <label><input type="radio" name="be_schema_preview_capture" value="server" checked /> <?php esc_html_e( 'Server', 'beseo' ); ?></label>
                            <label><input type="radio" name="be_schema_preview_capture" value="dom" /> <?php esc_html_e( 'DOM', 'beseo' ); ?></label>
                        </div>
                        <p class="be-schema-preview-help" id="be-schema-preview-capture-help"></p>
                    </div>
                    <div class="be-schema-preview-field" data-control="view">
                        <span class="be-schema-preview-label"><?php esc_html_e( 'View', 'beseo' ); ?></span>
                        <div class="be-schema-preview-options">
                            <label><input type="radio" name="be_schema_preview_view" value="graph" checked /> <?php esc_html_e( 'Graph', 'beseo' ); ?></label>
                            <label><input type="radio" name="be_schema_preview_view" value="json" /> <?php esc_html_e( 'JSON-LD', 'beseo' ); ?></label>
                        </div>
                        <p class="be-schema-preview-help" id="be-schema-preview-view-help"></p>
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
                <div class="be-schema-preview-criteria-actions">
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
            <p class="be-schema-preview-note"><?php esc_html_e( 'Schema extraction currently supports JSON-LD only (Microdata/RDFa not supported yet).', 'beseo' ); ?></p>
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
