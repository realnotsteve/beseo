<?php
/**
 * Analyser admin page: controller and view.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/analyser-service.php';

/**
 * Determine the default analyser tab.
 */
function be_schema_engine_get_analyser_default_tab() {
    $default_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $allowed     = array( 'overview', 'issues', 'pages', 'history', 'settings' );
    if ( ! in_array( $default_tab, $allowed, true ) ) {
        $default_tab = 'overview';
    }
    return $default_tab;
}

/**
 * Enqueue analyser assets for the analyser screen only.
 */
function be_schema_engine_enqueue_analyser_assets() {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    $is_analyser_screen = false;

    if ( $screen && 'beseo_page_beseo-analyser' === $screen->id ) {
        $is_analyser_screen = true;
    } elseif ( isset( $_GET['page'] ) && 'beseo-analyser' === sanitize_key( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $is_analyser_screen = true;
    }

    if ( ! $is_analyser_screen ) {
        return;
    }

    wp_enqueue_style(
        'be-schema-analyser',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'assets/css/analyser.css',
        array(),
        BE_SCHEMA_ENGINE_VERSION
    );

    wp_enqueue_script(
        'be-schema-selector',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'includes/admin/js/be-selector.js',
        array(),
        BE_SCHEMA_ENGINE_VERSION,
        true
    );

    wp_enqueue_script(
        'be-schema-analyser',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'assets/js/analyser.js',
        array( 'be-schema-selector' ),
        BE_SCHEMA_ENGINE_VERSION,
        true
    );

    $default_tab = be_schema_engine_get_analyser_default_tab();
    $home_url    = home_url( '/' );

    wp_localize_script(
        'be-schema-analyser',
        'beSchemaAnalyserData',
        array(
            'nonce'      => wp_create_nonce( 'be_schema_analyser' ),
            'defaultTab' => $default_tab,
            'homeUrl'    => $home_url,
            'strings'    => array(
                'noIssues'       => __( 'No issues detected for this crawl.', 'beseo' ),
                'severity'       => __( 'Severity', 'beseo' ),
                'type'           => __( 'Type', 'beseo' ),
                'count'          => __( 'Count', 'beseo' ),
                'examplePage'    => __( 'Example Page', 'beseo' ),
                'noPagesYet'     => __( 'No pages processed yet.', 'beseo' ),
                'noIssuesPage'   => __( 'No issues.', 'beseo' ),
                'processed'      => __( 'Processed', 'beseo' ),
                'queued'         => __( 'queued', 'beseo' ),
                'crawlFailed'    => __( 'Crawl failed.', 'beseo' ),
                'enterUrl'       => __( 'Enter a URL to analyse.', 'beseo' ),
                'starting'       => __( 'Starting…', 'beseo' ),
                'analysisFailed' => __( 'Analysis failed.', 'beseo' ),
                'crawlStarted'   => __( 'Crawl started…', 'beseo' ),
                'crawlStopped'   => __( 'Crawl stopped.', 'beseo' ),
                'crawlPaused'    => __( 'Crawl paused.', 'beseo' ),
                'resuming'       => __( 'Resuming…', 'beseo' ),
                'enterLabelUrl'  => __( 'Enter a label and URL.', 'beseo' ),
                'useHttp'        => __( 'Use http/https URLs only.', 'beseo' ),
                'websiteSaved'   => __( 'Website saved.', 'beseo' ),
                'noSavedSites'   => __( 'No saved websites yet.', 'beseo' ),
                'listingPages'   => __( 'Listing sitemap pages…', 'beseo' ),
                'listFailed'     => __( 'Failed to list pages.', 'beseo' ),
                'listNoPages'    => __( 'No sitemap pages found.', 'beseo' ),
                'listReady'      => __( 'Pages loaded.', 'beseo' ),
                'subpagesNone'   => __( 'None', 'beseo' ),
                'subpagesHome'   => __( 'Home page', 'beseo' ),
                'subpagesDivider'=> __( '────────', 'beseo' ),
                'noHistory'      => __( 'No history yet.', 'beseo' ),
                'runTwo'         => __( 'Run two crawls to see deltas.', 'beseo' ),
                'currentLabel'   => __( 'Current:', 'beseo' ),
                'elapsedLabel'   => __( 'Elapsed:', 'beseo' ),
                'errorsLabel'    => __( 'Errors:', 'beseo' ),
                'errors'         => __( 'Errors', 'beseo' ),
                'remove'         => __( 'Remove', 'beseo' ),
            ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'be_schema_engine_enqueue_analyser_assets' );

/**
 * Render the Analyser submenu page.
 */
function be_schema_engine_render_analyser_page() {
    $default_tab = be_schema_engine_get_analyser_default_tab();
    $home_url    = home_url( '/' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'BE Schema Engine – Analyser', 'beseo' ); ?></h1>

        <div class="be-schema-analyser-tabs">
            <a href="#be-schema-analyser-overview" class="be-schema-analyser-tab<?php echo ( 'overview' === $default_tab ) ? ' active' : ''; ?>" data-ana-tab="overview"><?php esc_html_e( 'Overview', 'beseo' ); ?></a>
            <a href="#be-schema-analyser-issues" class="be-schema-analyser-tab<?php echo ( 'issues' === $default_tab ) ? ' active' : ''; ?>" data-ana-tab="issues"><?php esc_html_e( 'Issues', 'beseo' ); ?></a>
            <a href="#be-schema-analyser-pages" class="be-schema-analyser-tab<?php echo ( 'pages' === $default_tab ) ? ' active' : ''; ?>" data-ana-tab="pages"><?php esc_html_e( 'Pages', 'beseo' ); ?></a>
            <a href="#be-schema-analyser-history" class="be-schema-analyser-tab<?php echo ( 'history' === $default_tab ) ? ' active' : ''; ?>" data-ana-tab="history"><?php esc_html_e( 'History', 'beseo' ); ?></a>
            <a href="#be-schema-analyser-settings" class="be-schema-analyser-tab<?php echo ( 'settings' === $default_tab ) ? ' active' : ''; ?>" data-ana-tab="settings"><?php esc_html_e( 'Settings', 'beseo' ); ?></a>
        </div>

        <div id="be-schema-analyser-overview" class="be-schema-analyser-panel<?php echo ( 'overview' === $default_tab ) ? ' active' : ''; ?>">
            <div class="be-schema-summary-card">
                <strong><?php esc_html_e( 'Scan status', 'beseo' ); ?></strong><br />
                <?php esc_html_e( 'Run a quick analysis to populate issues and trends.', 'beseo' ); ?>
            </div>
            <p class="description"><?php esc_html_e( 'The analyser performs a lightweight fetch and inspects metadata, headings, canonicals, robots, and link counts. It will expand to multi-page crawls in the future.', 'beseo' ); ?></p>
            <label class="be-schema-analyser-section-label"><?php esc_html_e( 'Selector', 'beseo' ); ?></label>
            <div class="be-schema-issues-list be-schema-selector-box">
                <div class="be-schema-selector-grid">
                    <div class="be-schema-analyser-local-column">
                        <div class="be-schema-analyser-local-box">
                            <label class="be-schema-analyser-inline-field">
                                <input type="radio" name="be-schema-analyser-env" id="be-schema-analyser-env-local" value="local" checked />
                                <span><?php esc_html_e( 'Local', 'beseo' ); ?></span>
                            </label>
                            <label class="be-schema-analyser-inline-field">
                                <input type="radio" name="be-schema-analyser-env" id="be-schema-analyser-env-remote" value="remote" />
                                <span><?php esc_html_e( 'Remote', 'beseo' ); ?></span>
                            </label>
                        </div>
                    </div>
                    <span class="be-schema-analyser-vertical-divider be-schema-selector-divider" aria-hidden="true"></span>
                    <div class="be-schema-selector-rows">
                        <div class="be-schema-analyser-controls be-schema-selector-row">
                            <label><input type="radio" name="be-schema-analyser-target-mode" value="site" checked /> <?php esc_html_e( 'Websites', 'beseo' ); ?></label>
                            <label><input type="radio" name="be-schema-analyser-target-mode" value="manual" /> <?php esc_html_e( 'Manual URL', 'beseo' ); ?></label>
                            <select id="be-schema-analyser-site" class="regular-text be-schema-analyser-url" style="display:inline-block;">
                                <option value="<?php echo esc_url( $home_url ); ?>"><?php echo esc_html( $home_url ); ?></option>
                            </select>
                            <input type="text" id="be-schema-analyser-url" class="regular-text be-schema-analyser-url" value="<?php echo esc_url( $home_url ); ?>" placeholder="<?php esc_attr_e( 'https://example.com/', 'beseo' ); ?>" style="display:none;" />
                            <label class="be-schema-analyser-inline-field">
                                <input type="checkbox" id="be-schema-analyser-include-posts" />
                                <span><?php esc_html_e( 'Include Posts', 'beseo' ); ?></span>
                            </label>
                            <label class="be-schema-analyser-inline-field">
                                <span><?php esc_html_e( 'Max Posts', 'beseo' ); ?></span>
                                <input type="number" id="be-schema-analyser-max-posts" class="small-text" value="25" min="1" max="500" style="width:80px;" disabled />
                            </label>
                        </div>
                        <div class="be-schema-analyser-controls be-schema-selector-row">
                            <button type="button" class="button button-primary" id="be-schema-analyser-list-pages"><?php esc_html_e( 'List Pages', 'beseo' ); ?></button>
                            <label class="be-schema-analyser-inline-field">
                                <span><?php esc_html_e( 'Subpage(s)', 'beseo' ); ?></span>
                                <select id="be-schema-analyser-subpages" class="regular-text" disabled>
                                    <option value=""><?php esc_html_e( 'None', 'beseo' ); ?></option>
                                </select>
                            </label>
                            <label class="be-schema-analyser-inline-field">
                                <span><?php esc_html_e( 'Max Site Pages', 'beseo' ); ?></span>
                                <input type="number" id="be-schema-analyser-site-limit" class="small-text" value="25" min="1" max="500" style="width:80px;" />
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="be-schema-analyser-actions">
                <button class="button button-primary" id="be-schema-analyser-run"><?php esc_html_e( 'Run analysis', 'beseo' ); ?></button>
                <label class="be-schema-analyser-inline-field">
                    <span><?php esc_html_e( 'Max Follow Links', 'beseo' ); ?></span>
                    <input type="number" id="be-schema-analyser-limit" class="small-text" value="10" min="1" max="100" style="width:70px;" />
                </label>
                <button class="button" id="be-schema-analyser-stop" disabled><?php esc_html_e( 'Stop', 'beseo' ); ?></button>
                <button class="button" id="be-schema-analyser-pause" disabled><?php esc_html_e( 'Pause', 'beseo' ); ?></button>
                <button class="button" id="be-schema-analyser-resume" disabled><?php esc_html_e( 'Resume', 'beseo' ); ?></button>
                <button class="button" id="be-schema-analyser-export-csv" disabled><?php esc_html_e( 'Export CSV', 'beseo' ); ?></button>
                <button class="button" id="be-schema-analyser-export-json" disabled><?php esc_html_e( 'Export JSON', 'beseo' ); ?></button>
                <span id="be-schema-analyser-status"></span>
                <div class="be-schema-analyser-progress">
                    <span id="be-schema-analyser-current"></span>
                    <span id="be-schema-analyser-elapsed"></span>
                    <span id="be-schema-analyser-errors"></span>
                </div>
            </div>
        </div>

        <div id="be-schema-analyser-issues" class="be-schema-analyser-panel<?php echo ( 'issues' === $default_tab ) ? ' active' : ''; ?>">
            <div class="be-schema-issues-layout">
                <div class="be-schema-issues-nav">
                    <strong><?php esc_html_e( 'Issue groups', 'beseo' ); ?></strong>
                    <button class="button button-secondary" data-issue-group="all"><?php esc_html_e( 'All issues', 'beseo' ); ?></button>
                    <button class="button button-secondary" data-issue-group="index"><?php esc_html_e( 'Indexability', 'beseo' ); ?></button>
                    <button class="button button-secondary" data-issue-group="links"><?php esc_html_e( 'Links', 'beseo' ); ?></button>
                    <button class="button button-secondary" data-issue-group="content"><?php esc_html_e( 'Content', 'beseo' ); ?></button>
                    <button class="button button-secondary" data-issue-group="schema"><?php esc_html_e( 'Schema', 'beseo' ); ?></button>
                    <button class="button button-secondary" data-issue-group="performance"><?php esc_html_e( 'Performance', 'beseo' ); ?></button>
                    <button class="button button-secondary" data-issue-group="social"><?php esc_html_e( 'Social/OG', 'beseo' ); ?></button>
                </div>
                <div class="be-schema-issues-list" id="be-schema-issues-list">
                    <p class="description"><?php esc_html_e( 'Issues will appear here after the first analysis.', 'beseo' ); ?></p>
                </div>
            </div>
        </div>

        <div id="be-schema-analyser-pages" class="be-schema-analyser-panel<?php echo ( 'pages' === $default_tab ) ? ' active' : ''; ?>">
            <p class="description"><?php esc_html_e( 'Per-page findings from the last crawl.', 'beseo' ); ?></p>
            <div id="be-schema-pages-list" class="be-schema-issues-list">
                <p class="description"><?php esc_html_e( 'No crawl has run yet.', 'beseo' ); ?></p>
            </div>
        </div>

        <div id="be-schema-analyser-history" class="be-schema-analyser-panel<?php echo ( 'history' === $default_tab ) ? ' active' : ''; ?>">
            <p class="description"><?php esc_html_e( 'Recent crawl snapshots with issue counts.', 'beseo' ); ?></p>
            <div id="be-schema-history-list" class="be-schema-issues-list">
                <p class="description"><?php esc_html_e( 'No history yet.', 'beseo' ); ?></p>
            </div>
            <div id="be-schema-history-delta" class="be-schema-issues-list" style="margin-top:12px;">
                <p class="description"><?php esc_html_e( 'Run two crawls to see deltas.', 'beseo' ); ?></p>
            </div>
        </div>

        <div id="be-schema-analyser-settings" class="be-schema-analyser-panel<?php echo ( 'settings' === $default_tab ) ? ' active' : ''; ?>">
            <p class="description"><?php esc_html_e( 'Website lists are managed under Settings → Lists.', 'beseo' ); ?></p>
        </div>
    </div>
    <?php
}
