<div id="be-schema-tools-validator" class="be-schema-tools-panel active">
            <p class="description">
                <?php esc_html_e( 'Validate Open Graph and Twitter Cards with live previews and source mapping.', 'beseo' ); ?>
            </p>
            <div class="be-schema-validator-header">
                <div class="be-schema-header-titles">
                    <div><?php esc_html_e( 'Source', 'beseo' ); ?></div>
                    <div><?php esc_html_e( 'Engine', 'beseo' ); ?></div>
                    <div><?php esc_html_e( 'Action', 'beseo' ); ?></div>
                    <div><?php esc_html_e( 'Messages', 'beseo' ); ?></div>
                </div>
                <div class="be-schema-header-grid">
                    <div class="be-schema-header-section">
                       <div class="be-schema-validator-rowline">
                           <label><input type="radio" name="be_schema_validator_mode" value="manual" /> <?php esc_html_e( 'Manual URL', 'beseo' ); ?></label>
                           <label><input type="radio" name="be_schema_validator_mode" value="dropdown" checked /> <?php esc_html_e( 'Site Page', 'beseo' ); ?></label>
                            <label><input type="checkbox" id="be-schema-validator-include-posts" /> <?php esc_html_e( 'Include Posts', 'beseo' ); ?></label>
                        </div>
                        <div class="be-schema-validator-select-wrap">
                            <select id="be-schema-validator-select" class="regular-text"></select>
                            <input type="text" id="be-schema-validator-manual" class="regular-text" placeholder="https://" style="display:none;" />
                        </div>
                        <div class="be-schema-validator-search">
                            <label class="screen-reader-text" for="be-schema-validator-search"><?php esc_html_e( 'Search pages/posts', 'beseo' ); ?></label>
                            <input type="text" id="be-schema-validator-search" class="regular-text" placeholder="<?php esc_attr_e( 'Search pages/posts', 'beseo' ); ?>" />
                        </div>
                    </div>
                    <div class="be-schema-header-section">
                        <div class="be-schema-engine-row">
                            <div class="be-schema-engine-col">
                                <div class="be-schema-validator-rowline">
                                    <label><input type="radio" name="be_schema_validator_type" value="native" checked /> <?php esc_html_e( 'Native Validation', 'beseo' ); ?></label>
                                </div>
                                <div class="be-schema-engine-box">
                                    <div class="be-schema-validator-platforms">
                                        <label><input type="checkbox" id="be-schema-validator-og" checked /> <?php esc_html_e( 'Open Graph', 'beseo' ); ?></label>
                                        <label><input type="checkbox" id="be-schema-validator-twitter" checked /> <?php esc_html_e( 'Twitter Cards', 'beseo' ); ?></label>
                                    </div>
                                </div>
                                <label><input type="checkbox" id="be-schema-validator-crops" /> <?php esc_html_e( 'Possible Crops', 'beseo' ); ?></label>
                            </div>
                            <div class="be-schema-engine-col">
                                <div class="be-schema-validator-rowline">
                                    <label><input type="radio" name="be_schema_validator_type" value="external" /> <?php esc_html_e( 'External Service', 'beseo' ); ?></label>
                                </div>
                                <div class="be-schema-engine-box stacked">
                                    <label><input type="checkbox" id="be-schema-validator-copy" disabled /> <?php esc_html_e( 'Copy Source URL to Clipboard', 'beseo' ); ?></label>
                                    <label><input type="checkbox" id="be-schema-validator-open-new" disabled checked /> <?php esc_html_e( 'Open and Switch to New Tab', 'beseo' ); ?></label>
                                    <select id="be-schema-validator-service" class="be-schema-validator-service" disabled>
                                        <option value=""><?php esc_html_e( 'Choose a service', 'beseo' ); ?></option>
                                        <option value="twitter" data-url="https://cards-dev.twitter.com/validator"><?php esc_html_e( 'Twitter Card Validator', 'beseo' ); ?></option>
                                        <option value="facebook" data-url="https://developers.facebook.com/tools/debug/"><?php esc_html_e( 'Facebook Sharing Debugger', 'beseo' ); ?></option>
                                        <option value="linkedin" data-url="https://www.linkedin.com/post-inspector/inspect/"><?php esc_html_e( 'LinkedIn Post Inspector', 'beseo' ); ?></option>
                                        <option disabled>──────────</option>
                                        <option value="metatags" data-url="https://metatags.io"><?php esc_html_e( 'Metatags', 'beseo' ); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="be-schema-header-section">
                        <div class="be-schema-validator-actions">
                            <button type="button" class="button button-primary" id="be-schema-validator-run" disabled><?php esc_html_e( 'Validate', 'beseo' ); ?></button>
                            <button type="button" class="button" id="be-schema-validator-rerun" disabled><?php esc_html_e( 'Re-run', 'beseo' ); ?></button>
                            <button type="button" class="button" id="be-schema-validator-copy-summary" disabled><?php esc_html_e( 'Copy summary', 'beseo' ); ?></button>
                            <div class="be-schema-validator-rowline" style="flex-wrap: nowrap;">
                                <label><input type="checkbox" id="be-schema-toggle-twitter" checked /> <?php esc_html_e( 'Show Twitter preview', 'beseo' ); ?></label>
                                <label><input type="checkbox" id="be-schema-toggle-og" checked /> <?php esc_html_e( 'Show OG preview', 'beseo' ); ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="be-schema-header-section">
                        <div class="be-schema-validator-context" id="be-schema-validator-context">
                    <span class="be-schema-context-line"><span class="label"><?php esc_html_e( 'Result', 'beseo' ); ?>:</span> <span id="be-schema-context-result" aria-live="polite">—</span></span>
                    <span class="be-schema-context-line"><span class="label"><?php esc_html_e( 'URL', 'beseo' ); ?>:</span> <span id="be-schema-context-url">—</span></span>
                    <span class="be-schema-context-line"><span class="label"><?php esc_html_e( 'Last run', 'beseo' ); ?>:</span> <span id="be-schema-context-time">—</span></span>
                    <span class="be-schema-context-line"><span class="label"><?php esc_html_e( 'Platforms', 'beseo' ); ?>:</span> <span id="be-schema-context-platforms">—</span></span>
                    <div class="be-schema-mini-badges" id="be-schema-mini-badges"></div>
                </div>
                        <p class="description" id="be-schema-validator-note" style="margin-top:6px;" aria-live="polite"></p>
                        <details class="be-schema-fetch-log" id="be-schema-fetch-log" style="display:none;">
                            <summary><?php esc_html_e( 'Fetch log', 'beseo' ); ?></summary>
                            <table>
                                <tbody>
                                    <tr><td><?php esc_html_e( 'Page status', 'beseo' ); ?></td><td id="be-schema-log-page-status">—</td></tr>
                                    <tr><td><?php esc_html_e( 'Page time (ms)', 'beseo' ); ?></td><td id="be-schema-log-page-time">—</td></tr>
                                    <tr><td><?php esc_html_e( 'Redirects', 'beseo' ); ?></td><td id="be-schema-log-redirects">—</td></tr>
                                    <tr><td><?php esc_html_e( 'Image status', 'beseo' ); ?></td><td id="be-schema-log-image-status">—</td></tr>
                                    <tr><td><?php esc_html_e( 'Image time (ms)', 'beseo' ); ?></td><td id="be-schema-log-image-time">—</td></tr>
                                    <tr><td><?php esc_html_e( 'Image type', 'beseo' ); ?></td><td id="be-schema-log-image-type">—</td></tr>
                                    <tr><td><?php esc_html_e( 'Image size', 'beseo' ); ?></td><td id="be-schema-log-image-size">—</td></tr>
                                </tbody>
                            </table>
                        </details>
                    </div>
                </div>
            </div>
            <div class="be-schema-validator-grid">
                <div class="be-schema-validator-card">
                    <h3><?php esc_html_e( 'Preview', 'beseo' ); ?></h3>
                    <div id="be-schema-validator-preview-wrapper">
                        <div class="be-schema-validator-preview" id="be-schema-preview-twitter" data-platform="twitter">
                            <div class="be-schema-preview-label"><?php esc_html_e( 'Twitter Card', 'beseo' ); ?> · <span id="be-schema-preview-twitter-card">summary_large_image</span></div>
                            <div class="be-schema-preview-img" id="be-schema-preview-twitter-img">
                                <span class="be-schema-crop-overlay"></span>
                            </div>
                            <div class="be-schema-preview-title" id="be-schema-preview-twitter-title"><?php esc_html_e( 'Title will appear here', 'beseo' ); ?></div>
                            <p class="be-schema-preview-desc" id="be-schema-preview-twitter-desc"><?php esc_html_e( 'Description preview will appear here.', 'beseo' ); ?></p>
                            <div class="be-schema-preview-meta" id="be-schema-preview-twitter-domain"></div>
                        </div>
                        <div class="be-schema-validator-preview" id="be-schema-preview-og" data-platform="og">
                            <div class="be-schema-preview-label"><?php esc_html_e( 'Open Graph', 'beseo' ); ?></div>
                            <div class="be-schema-preview-img" id="be-schema-preview-og-img">
                                <span class="be-schema-crop-overlay"></span>
                            </div>
                            <div class="be-schema-preview-title" id="be-schema-preview-og-title"><?php esc_html_e( 'Title will appear here', 'beseo' ); ?></div>
                            <p class="be-schema-preview-desc" id="be-schema-preview-og-desc"><?php esc_html_e( 'Description preview will appear here.', 'beseo' ); ?></p>
                            <div class="be-schema-preview-meta" id="be-schema-preview-og-domain"></div>
                        </div>
                    </div>
                </div>
                <div class="be-schema-validator-right">
                    <div class="be-schema-validator-card">
                        <h3><?php esc_html_e( 'Source Map', 'beseo' ); ?></h3>
                        <table class="be-schema-validator-table" id="be-schema-source-map">
                            <tbody>
                                <tr data-field="title"><th><?php esc_html_e( 'Title', 'beseo' ); ?></th><td class="value">—</td><td class="source">—</td></tr>
                                <tr data-field="description"><th><?php esc_html_e( 'Description', 'beseo' ); ?></th><td class="value">—</td><td class="source">—</td></tr>
                                <tr data-field="image"><th><?php esc_html_e( 'Image', 'beseo' ); ?></th><td class="value">—</td><td class="source">—</td></tr>
                                <tr data-field="card"><th><?php esc_html_e( 'Card Type', 'beseo' ); ?></th><td class="value">—</td><td class="source">—</td></tr>
                                <tr data-field="domain"><th><?php esc_html_e( 'Domain', 'beseo' ); ?></th><td class="value">—</td><td class="source">—</td></tr>
                            </tbody>
                        </table>
                        <div class="be-schema-validator-legend">
                            <span><span class="be-schema-dot green"></span> <?php esc_html_e( 'Direct platform tag', 'beseo' ); ?></span>
                            <span><span class="be-schema-dot yellow"></span> <?php esc_html_e( 'Fallback used', 'beseo' ); ?></span>
                            <span><span class="be-schema-dot red"></span> <?php esc_html_e( 'Missing/invalid', 'beseo' ); ?></span>
                        </div>
                    </div>
                    <div class="be-schema-validator-card">
                        <h3><?php esc_html_e( 'Validation & Warnings', 'beseo' ); ?></h3>
                        <div class="be-schema-warning-legend"><?php esc_html_e( 'Legend: ✅ OK · ⚠️ Warning · ❌ Error', 'beseo' ); ?></div>
                        <ul class="be-schema-warning-list" id="be-schema-warning-list">
                            <li class="be-schema-warning-empty"><?php esc_html_e( 'Run a validation to see results.', 'beseo' ); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        
