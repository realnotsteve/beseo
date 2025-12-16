<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_ajax_be_schema_analyser_run', 'be_schema_engine_handle_analyser_run' );
add_action( 'wp_ajax_be_schema_analyser_start', 'be_schema_engine_handle_analyser_start' );
add_action( 'wp_ajax_be_schema_analyser_step', 'be_schema_engine_handle_analyser_step' );
add_action( 'wp_ajax_be_schema_analyser_stop', 'be_schema_engine_handle_analyser_stop' );
add_action( 'wp_ajax_be_schema_analyser_history', 'be_schema_engine_handle_analyser_history' );

/**
 * Simple per-user analyser state storage.
 */
function be_schema_engine_analyser_state_key() {
    $user_id = get_current_user_id();
    return 'be_schema_analyser_state_' . ( $user_id ? $user_id : 'guest' );
}

function be_schema_engine_analyser_history_key() {
    $user_id = get_current_user_id();
    return 'be_schema_analyser_history_' . ( $user_id ? $user_id : 'guest' );
}

function be_schema_engine_analyser_state_get() {
    $key   = be_schema_engine_analyser_state_key();
    $state = get_transient( $key );
    if ( ! is_array( $state ) ) {
        return array();
    }
    return $state;
}

function be_schema_engine_analyser_state_set( $state ) {
    $key = be_schema_engine_analyser_state_key();
    set_transient( $key, $state, MINUTE_IN_SECONDS * 20 );
}

function be_schema_engine_analyser_state_clear() {
    delete_transient( be_schema_engine_analyser_state_key() );
}

function be_schema_engine_analyser_history_get() {
    $key     = be_schema_engine_analyser_history_key();
    $history = get_transient( $key );
    if ( ! is_array( $history ) ) {
        return array();
    }
    return $history;
}

function be_schema_engine_analyser_history_push( $entry ) {
    $key     = be_schema_engine_analyser_history_key();
    $history = be_schema_engine_analyser_history_get();
    array_unshift( $history, $entry );
    $history = array_slice( $history, 0, 10 );
    set_transient( $key, $history, DAY_IN_SECONDS * 14 );
}

/**
 * Lightweight analyser: fetch a single URL and emit issues.
 */
function be_schema_engine_handle_analyser_run() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Not allowed.', 'beseo' ) ), 403 );
    }
    check_ajax_referer( 'be_schema_analyser', 'nonce' );

    $url = isset( $_POST['url'] ) ? trim( (string) wp_unslash( $_POST['url'] ) ) : '';
    if ( ! $url || ! wp_http_validate_url( $url ) ) {
        wp_send_json_error( array( 'message' => __( 'Provide a valid URL (http/https).', 'beseo' ) ) );
    }

    $result = be_schema_engine_analyse_url( $url );
    wp_send_json_success( $result );
}

/**
 * Start a crawl: seed queue.
 */
function be_schema_engine_handle_analyser_start() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Not allowed.', 'beseo' ) ), 403 );
    }
    check_ajax_referer( 'be_schema_analyser', 'nonce' );
    $url      = isset( $_POST['url'] ) ? trim( (string) wp_unslash( $_POST['url'] ) ) : '';
    $max      = isset( $_POST['max'] ) ? (int) $_POST['max'] : 20;
    $max      = max( 1, min( 100, $max ) );
    if ( ! $url || ! wp_http_validate_url( $url ) ) {
        wp_send_json_error( array( 'message' => __( 'Provide a valid URL.', 'beseo' ) ) );
    }
    $parsed = wp_parse_url( $url );
    if ( ! $parsed || empty( $parsed['host'] ) ) {
        wp_send_json_error( array( 'message' => __( 'URL must include a host.', 'beseo' ) ) );
    }

    $state = array(
        'queue'     => array( $url ),
        'visited'   => array(),
        'results'   => array(),
        'start'     => time(),
        'max'       => $max,
        'processed' => 0,
        'host'      => $parsed['host'],
    );
    be_schema_engine_analyser_state_set( $state );

    wp_send_json_success(
        array(
            'message' => __( 'Crawl started.', 'beseo' ),
            'state'   => array(
                'processed' => 0,
                'queued'    => 1,
                'max'       => $max,
            ),
        )
    );
}

/**
 * Process one URL from the crawl queue.
 */
function be_schema_engine_handle_analyser_step() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Not allowed.', 'beseo' ) ), 403 );
    }
    check_ajax_referer( 'be_schema_analyser', 'nonce' );
    $state = be_schema_engine_analyser_state_get();
    if ( empty( $state['queue'] ) || ! isset( $state['processed'] ) ) {
        wp_send_json_success(
            array(
                'done' => true,
                'state'=> array(),
            )
        );
    }

    $url = array_shift( $state['queue'] );
    if ( isset( $state['visited'][ $url ] ) ) {
        be_schema_engine_analyser_state_set( $state );
        wp_send_json_success(
            array(
                'done' => empty( $state['queue'] ) || $state['processed'] >= $state['max'],
                'state'=> array(
                    'processed' => $state['processed'],
                    'queued'    => count( $state['queue'] ),
                ),
                'last' => null,
            )
        );
    }

    $state['visited'][ $url ] = true;
    $analysis = be_schema_engine_analyse_url( $url );
    $state['processed']      += 1;
    $state['results'][ $url ] = $analysis['issues'];

    // Build summary counts.
    $summary = array();
    foreach ( $state['results'] as $page_url => $page_issues ) {
        foreach ( (array) $page_issues as $issue ) {
            $key = ( isset( $issue['severity'] ) ? $issue['severity'] : 'info' ) . '|' . ( isset( $issue['type'] ) ? $issue['type'] : 'generic' );
            if ( ! isset( $summary[ $key ] ) ) {
                $summary[ $key ] = array(
                    'severity' => isset( $issue['severity'] ) ? $issue['severity'] : 'info',
                    'type'     => isset( $issue['type'] ) ? $issue['type'] : 'generic',
                    'count'    => 0,
                    'pages'    => array(),
                );
            }
            $summary[ $key ]['count']++;
            $summary[ $key ]['pages'][] = $page_url;
        }
    }

    if ( ! empty( $analysis['internal'] ) && isset( $state['host'] ) ) {
        foreach ( $analysis['internal'] as $link ) {
            if ( count( $state['visited'] ) + count( $state['queue'] ) >= $state['max'] ) {
                break;
            }
            $parsed = wp_parse_url( $link );
            if ( $parsed && isset( $parsed['host'] ) && $parsed['host'] === $state['host'] && ! isset( $state['visited'][ $link ] ) ) {
                $state['queue'][] = $link;
            }
        }
    }

    $done = ( $state['processed'] >= $state['max'] ) || empty( $state['queue'] );
    if ( $done ) {
        be_schema_engine_analyser_history_push(
            array(
                'timestamp' => time(),
                'summary'   => array_values( $summary ),
                'pages'     => $state['results'],
                'stats'     => array(
                    'processed' => $state['processed'],
                    'max'       => $state['max'],
                ),
            )
        );
        be_schema_engine_analyser_state_clear();
    } else {
        be_schema_engine_analyser_state_set( $state );
    }

    wp_send_json_success(
        array(
            'done'      => $done,
            'last'      => array(
                'url'     => $url,
                'issues'  => $analysis['issues'],
                'status'  => isset( $analysis['status'] ) ? $analysis['status'] : 0,
                'duration'=> isset( $analysis['duration'] ) ? $analysis['duration'] : 0,
            ),
            'state'     => array(
                'processed' => $state['processed'],
                'queued'    => count( $state['queue'] ),
                'max'       => $state['max'],
            ),
            'summary'   => array_values( $summary ),
            'pages'     => $state['results'],
        )
    );
}

/**
 * Stop/clear crawl state.
 */
function be_schema_engine_handle_analyser_stop() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Not allowed.', 'beseo' ) ), 403 );
    }
    check_ajax_referer( 'be_schema_analyser', 'nonce' );
    be_schema_engine_analyser_state_clear();
    wp_send_json_success( array( 'message' => __( 'Crawl stopped.', 'beseo' ) ) );
}

/**
 * Return analyser history.
 */
function be_schema_engine_handle_analyser_history() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Not allowed.', 'beseo' ) ), 403 );
    }
    check_ajax_referer( 'be_schema_analyser', 'nonce' );
    $history = be_schema_engine_analyser_history_get();
    wp_send_json_success( array( 'history' => $history ) );
}

/**
 * Render the Analyser submenu page.
 */
function be_schema_engine_render_analyser_page() {
    $default_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
    if ( ! in_array( $default_tab, array( 'overview', 'issues', 'pages', 'history', 'settings' ), true ) ) {
        $default_tab = 'overview';
    }
    $home_url = home_url( '/' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'BE Schema Engine – Analyser', 'beseo' ); ?></h1>
        <style>
            .be-schema-analyser-tabs {
                margin-top: 12px;
            }
            .be-schema-analyser-tab {
                display: inline-block;
                padding: 8px 14px;
                border: 1px solid #c3c4c7;
                border-bottom: none;
                background: #f6f7f7;
                color: #50575e;
                text-decoration: none;
                margin-right: 4px;
                border-radius: 3px 3px 0 0;
            }
            .be-schema-analyser-tab.active {
                background: #fff;
                color: #1d2327;
                border-bottom: 1px solid #fff;
            }
            .be-schema-analyser-panel {
                display: none;
                border: 1px solid #ccd0d4;
                padding: 16px;
                background: #fff;
            }
            .be-schema-analyser-panel.active {
                display: block;
            }
            .be-schema-issues-layout {
                display: grid;
                grid-template-columns: 220px 1fr;
                gap: 16px;
            }
            .be-schema-issues-nav {
                border: 1px solid #dfe2e6;
                border-radius: 6px;
                background: #f7f9fb;
                padding: 10px;
            }
            .be-schema-issues-nav button {
                display: block;
                width: 100%;
                text-align: left;
                margin-bottom: 6px;
            }
            .be-schema-issues-list {
                border: 1px solid #dfe2e6;
                border-radius: 6px;
                padding: 12px;
                background: #fff;
            }
            .be-schema-issues-list ul {
                margin: 0;
                padding-left: 16px;
            }
            .be-schema-issues-table {
                width: 100%;
                border-collapse: collapse;
            }
            .be-schema-issues-table th,
            .be-schema-issues-table td {
                border-bottom: 1px solid #e5e5e5;
                padding: 6px;
            }
            .be-schema-summary-card {
                border: 1px solid #dfe2e6;
                border-radius: 6px;
                padding: 12px;
                background: linear-gradient(135deg, #f9fbfd, #eef2f5);
                margin-bottom: 10px;
            }
            .be-schema-pill {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 999px;
                font-size: 11px;
            }
            .be-schema-pill.error { background: #fdecea; color: #8a1f11; }
            .be-schema-pill.warn { background: #fff4e5; color: #8a6d3b; }
            .be-schema-pill.info { background: #eef2f5; color: #2c3e50; }
            .be-schema-settings-layout {
                display: grid;
                grid-template-columns: 240px 1fr;
                gap: 16px;
                align-items: start;
            }
            .be-schema-settings-menu {
                border: 1px solid #dfe2e6;
                border-radius: 6px;
                background: #f7f9fb;
                padding: 10px;
            }
            .be-schema-settings-menu button {
                display: block;
                width: 100%;
                text-align: left;
                margin-bottom: 6px;
            }
            .be-schema-settings-panel {
                border: 1px solid #dfe2e6;
                border-radius: 6px;
                padding: 12px;
                background: #fff;
            }
            .be-schema-website-list {
                margin-top: 8px;
            }
            .be-schema-website-list li {
                margin-bottom: 6px;
            }
        </style>

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
            <div class="be-schema-analyser-controls">
                <label><input type="radio" name="be-schema-analyser-target-mode" value="site" checked /> <?php esc_html_e( 'Websites', 'beseo' ); ?></label>
                <label><input type="radio" name="be-schema-analyser-target-mode" value="manual" /> <?php esc_html_e( 'Manual URL', 'beseo' ); ?></label>
                <select id="be-schema-analyser-site" class="regular-text be-schema-analyser-url" style="display:inline-block;">
                    <option value="<?php echo esc_url( $home_url ); ?>"><?php echo esc_html( $home_url ); ?></option>
                </select>
                <input type="text" id="be-schema-analyser-url" class="regular-text be-schema-analyser-url" value="<?php echo esc_url( $home_url ); ?>" placeholder="https://example.com/" style="display:none;" />
                <input type="number" id="be-schema-analyser-limit" class="small-text" value="10" min="1" max="100" style="width:70px;" />
                <button class="button button-primary" id="be-schema-analyser-run"><?php esc_html_e( 'Run analysis', 'beseo' ); ?></button>
                <button class="button" id="be-schema-analyser-stop" disabled><?php esc_html_e( 'Stop', 'beseo' ); ?></button>
                <span id="be-schema-analyser-status"></span>
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
            <div class="be-schema-settings-layout">
                <div class="be-schema-settings-menu">
                    <button class="button button-secondary" data-settings-panel="websites"><?php esc_html_e( 'Websites', 'beseo' ); ?></button>
                </div>
                <div class="be-schema-settings-panel">
                    <div data-settings-panel-content="websites">
                        <h3><?php esc_html_e( 'Websites', 'beseo' ); ?></h3>
                        <p class="description"><?php esc_html_e( 'Manage a list of sites to analyse.', 'beseo' ); ?></p>
                        <div>
                            <input type="text" id="be-schema-sites-label" class="regular-text" placeholder="<?php esc_attr_e( 'Label (e.g., Main Site)', 'beseo' ); ?>" />
                            <input type="text" id="be-schema-sites-url" class="regular-text" placeholder="https://example.com/" />
                            <button class="button button-primary" id="be-schema-sites-add"><?php esc_html_e( 'Save Website', 'beseo' ); ?></button>
                        </div>
                        <ul class="be-schema-website-list" id="be-schema-sites-list"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                var tabs = document.querySelectorAll('.be-schema-analyser-tab');
                var panels = document.querySelectorAll('.be-schema-analyser-panel');
                var current = '<?php echo esc_js( $default_tab ); ?>';
                var runBtn = document.getElementById('be-schema-analyser-run');
                var stopBtn = document.getElementById('be-schema-analyser-stop');
                var urlInput = document.getElementById('be-schema-analyser-url');
                var siteSelect = document.getElementById('be-schema-analyser-site');
                var targetRadios = document.querySelectorAll('input[name="be-schema-analyser-target-mode"]');
                var limitInput = document.getElementById('be-schema-analyser-limit');
                var statusNode = document.getElementById('be-schema-analyser-status');
                var issuesList = document.getElementById('be-schema-issues-list');
                var nonce = '<?php echo wp_create_nonce( 'be_schema_analyser' ); ?>';
                var sitesList = document.getElementById('be-schema-sites-list');
                var sitesAdd = document.getElementById('be-schema-sites-add');
                var sitesLabel = document.getElementById('be-schema-sites-label');
                var sitesUrl = document.getElementById('be-schema-sites-url');
                var historyList = document.getElementById('be-schema-history-list');
                var historyDelta = document.getElementById('be-schema-history-delta');

                var sitesStoreKey = 'be-schema-analyser-sites';
                var sites = [];

                function activate(key) {
                    tabs.forEach(function(tab) {
                        tab.classList.toggle('active', tab.getAttribute('data-ana-tab') === key);
                    });
                    panels.forEach(function(panel) {
                        panel.classList.toggle('active', panel.id === 'be-schema-analyser-' + key);
                    });
                }

                tabs.forEach(function(tab) {
                    tab.addEventListener('click', function(e) {
                        e.preventDefault();
                        activate(tab.getAttribute('data-ana-tab'));
                    });
                    });

                    activate(current);

                function renderIssues(data) {
                    if (!issuesList) {
                        return;
                    }
                    issuesList.innerHTML = '';
                    if (!data || !data.summary || !data.summary.length) {
                        var p = document.createElement('p');
                        p.className = 'description';
                        p.textContent = '<?php echo esc_js( __( 'No issues detected for this crawl.', 'beseo' ) ); ?>';
                        issuesList.appendChild(p);
                        return;
                    }
                    var table = document.createElement('table');
                    table.className = 'be-schema-issues-table';
                    var thead = document.createElement('thead');
                    thead.innerHTML = '<tr><th><?php echo esc_js( __( 'Severity', 'beseo' ) ); ?></th><th><?php echo esc_js( __( 'Type', 'beseo' ) ); ?></th><th><?php echo esc_js( __( 'Count', 'beseo' ) ); ?></th><th><?php echo esc_js( __( 'Example Page', 'beseo' ) ); ?></th></tr>';
                    table.appendChild(thead);
                    var tbody = document.createElement('tbody');
                    data.summary.forEach(function(item) {
                        var tr = document.createElement('tr');
                        var sev = document.createElement('td');
                        var pill = document.createElement('span');
                        pill.className = 'be-schema-pill ' + (item.severity || 'info');
                        pill.textContent = item.severity || '';
                        sev.appendChild(pill);
                        var type = document.createElement('td');
                        type.textContent = item.type || '';
                        var count = document.createElement('td');
                        count.textContent = item.count || 0;
                        var page = document.createElement('td');
                        page.textContent = (item.pages && item.pages.length) ? item.pages[0] : '';
                        tr.appendChild(sev);
                        tr.appendChild(type);
                        tr.appendChild(count);
                        tr.appendChild(page);
                        tbody.appendChild(tr);
                    });
                    table.appendChild(tbody);
                    issuesList.appendChild(table);
                }

                function renderPages(pagesData) {
                    var pagesNode = document.getElementById('be-schema-pages-list');
                    if (!pagesNode) {
                        return;
                    }
                    pagesNode.innerHTML = '';
                    if (!pagesData || !Object.keys(pagesData).length) {
                        var p = document.createElement('p');
                        p.className = 'description';
                        p.textContent = '<?php echo esc_js( __( 'No pages processed yet.', 'beseo' ) ); ?>';
                        pagesNode.appendChild(p);
                        return;
                    }
                    Object.keys(pagesData).forEach(function(url) {
                        var issues = pagesData[url] || [];
                        var card = document.createElement('div');
                        card.className = 'be-schema-issues-list';
                        var title = document.createElement('strong');
                        title.textContent = url;
                        card.appendChild(title);
                        if (!issues.length) {
                            var none = document.createElement('p');
                            none.className = 'description';
                            none.textContent = '<?php echo esc_js( __( 'No issues.', 'beseo' ) ); ?>';
                            card.appendChild(none);
                        } else {
                            var list = document.createElement('ul');
                            issues.forEach(function(issue) {
                                var li = document.createElement('li');
                                li.textContent = '[' + (issue.severity || '').toUpperCase() + '] ' + (issue.type || '') + ': ' + (issue.message || '');
                                list.appendChild(li);
                            });
                            card.appendChild(list);
                        }
                        pagesNode.appendChild(card);
                    });
                }

                function setStatus(text) {
                    if (statusNode) {
                        statusNode.textContent = text || '';
                    }
                }

                var crawlTimer = null;

                function loadHistory() {
                    var form = new FormData();
                    form.append('action', 'be_schema_analyser_history');
                    form.append('nonce', nonce);
                    fetch(ajaxurl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: form
                    }).then(function(resp){ return resp.json(); }).then(function(payload) {
                        if (!payload || !payload.success) {
                            return;
                        }
                        renderHistory(payload.data.history || []);
                    }).catch(function() {});
                }

                function renderHistory(history) {
                    if (!historyList) {
                        return;
                    }
                    historyList.innerHTML = '';
                    if (!history || !history.length) {
                        var p = document.createElement('p');
                        p.className = 'description';
                        p.textContent = '<?php echo esc_js( __( 'No history yet.', 'beseo' ) ); ?>';
                        historyList.appendChild(p);
                        return;
                    }
                    history.forEach(function(entry, idx) {
                        var card = document.createElement('div');
                        card.className = 'be-schema-issues-list';
                        var heading = document.createElement('strong');
                        var date = new Date(entry.timestamp * 1000);
                        heading.textContent = date.toLocaleString();
                        card.appendChild(heading);
                        var info = document.createElement('p');
                        info.className = 'description';
                        info.textContent = '<?php echo esc_js( __( 'Processed', 'beseo' ) ); ?> ' + (entry.stats ? entry.stats.processed : 0);
                        card.appendChild(info);
                        if (entry.summary && entry.summary.length) {
                            var table = document.createElement('table');
                            table.className = 'be-schema-issues-table';
                            var thead = document.createElement('thead');
                            thead.innerHTML = '<tr><th><?php echo esc_js( __( 'Severity', 'beseo' ) ); ?></th><th><?php echo esc_js( __( 'Type', 'beseo' ) ); ?></th><th><?php echo esc_js( __( 'Count', 'beseo' ) ); ?></th></tr>';
                            table.appendChild(thead);
                            var tbody = document.createElement('tbody');
                            entry.summary.forEach(function(item) {
                                var tr = document.createElement('tr');
                        var sev = document.createElement('td');
                        var pill = document.createElement('span');
                        pill.className = 'be-schema-pill ' + (item.severity || 'info');
                        pill.textContent = item.severity || '';
                        sev.appendChild(pill);
                        var type = document.createElement('td');
                                type.textContent = item.type || '';
                                var count = document.createElement('td');
                                count.textContent = item.count || 0;
                                tr.appendChild(sev);
                                tr.appendChild(type);
                                tr.appendChild(count);
                                tbody.appendChild(tr);
                            });
                            table.appendChild(tbody);
                            card.appendChild(table);
                        }
                        historyList.appendChild(card);
                    });
                    if (historyDelta) {
                        if (history.length < 2) {
                            historyDelta.innerHTML = '<p class="description"><?php echo esc_js( __( 'Run two crawls to see deltas.', 'beseo' ) ); ?></p>';
                        } else {
                            var latest = history[0].summary || [];
                            var prev   = history[1].summary || [];
                            var diff   = {};
                            prev.forEach(function(item) {
                                var key = (item.severity || '') + '|' + (item.type || '');
                                diff[key] = -1 * (item.count || 0);
                            });
                            latest.forEach(function(item) {
                                var key = (item.severity || '') + '|' + (item.type || '');
                                diff[key] = (diff[key] || 0) + (item.count || 0);
                            });
                            var table = document.createElement('table');
                            table.className = 'be-schema-issues-table';
                            var thead = document.createElement('thead');
                            thead.innerHTML = '<tr><th><?php echo esc_js( __( 'Severity', 'beseo' ) ); ?></th><th><?php echo esc_js( __( 'Type', 'beseo' ) ); ?></th><th><?php echo esc_js( __( 'Delta', 'beseo' ) ); ?></th></tr>';
                            table.appendChild(thead);
                            var tbody = document.createElement('tbody');
                            Object.keys(diff).forEach(function(key) {
                                var parts = key.split('|');
                                var delta = diff[key];
                                var tr = document.createElement('tr');
                                var sev = document.createElement('td');
                                var pill = document.createElement('span');
                                pill.className = 'be-schema-pill ' + (parts[0] || 'info');
                                pill.textContent = parts[0] || '';
                                sev.appendChild(pill);
                                var type = document.createElement('td');
                                type.textContent = parts[1] || '';
                                var val = document.createElement('td');
                                val.textContent = delta;
                                tr.appendChild(sev);
                                tr.appendChild(type);
                                tr.appendChild(val);
                                tbody.appendChild(tr);
                            });
                            table.appendChild(tbody);
                            historyDelta.innerHTML = '';
                            historyDelta.appendChild(table);
                        }
                    }
                }

                function loadSites() {
                    try {
                        var raw = localStorage.getItem(sitesStoreKey);
                        sites = raw ? JSON.parse(raw) : [];
                        if (!Array.isArray(sites)) {
                            sites = [];
                        }
                    } catch (e) {
                        sites = [];
                    }
                }

                function saveSites() {
                    try {
                        localStorage.setItem(sitesStoreKey, JSON.stringify(sites));
                    } catch (e) {}
                }

                function renderSites() {
                    if (!sitesList || !siteSelect) {
                        return;
                    }
                    sitesList.innerHTML = '';
                    siteSelect.innerHTML = '';
                    if (!sites.length) {
                        var li = document.createElement('li');
                        li.textContent = '<?php echo esc_js( __( 'No saved websites yet.', 'beseo' ) ); ?>';
                        sitesList.appendChild(li);
                    }
                    sites.forEach(function(site, idx) {
                        var li = document.createElement('li');
                        li.textContent = site.label + ' — ' + site.url;
                        var btn = document.createElement('button');
                        btn.className = 'button-link delete';
                        btn.textContent = '<?php echo esc_js( __( 'Remove', 'beseo' ) ); ?>';
                        btn.addEventListener('click', function() {
                            sites.splice(idx, 1);
                            saveSites();
                            renderSites();
                        });
                        li.appendChild(btn);
                        sitesList.appendChild(li);

                        var opt = document.createElement('option');
                        opt.value = site.url;
                        opt.textContent = site.label + ' (' + site.url + ')';
                        siteSelect.appendChild(opt);
                    });
                    if (!sites.length) {
                        var optHome = document.createElement('option');
                        optHome.value = '<?php echo esc_js( $home_url ); ?>';
                        optHome.textContent = '<?php echo esc_js( $home_url ); ?>';
                        siteSelect.appendChild(optHome);
                    }
                }

                function currentTargetUrl() {
                    var mode = 'site';
                    targetRadios.forEach(function(r) { if (r.checked) { mode = r.value; } });
                    if (mode === 'manual') {
                        return urlInput ? urlInput.value.trim() : '';
                    }
                    return siteSelect ? siteSelect.value.trim() : '';
                }

                function pollStep() {
                    var form = new FormData();
                    form.append('action', 'be_schema_analyser_step');
                    form.append('nonce', nonce);
                    fetch(ajaxurl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: form
                    }).then(function(resp){ return resp.json(); }).then(function(payload) {
                        if (!payload || !payload.success) {
                            setStatus((payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js( __( 'Crawl failed.', 'beseo' ) ); ?>');
                            if (runBtn) { runBtn.disabled = false; }
                            if (stopBtn) { stopBtn.disabled = true; }
                            return;
                        }
                        if (payload.data) {
                            renderIssues(payload.data);
                            if (payload.data.pages) {
                                renderPages(payload.data.pages);
                            }
                        }
                        var state = payload.data.state || {};
                        setStatus('<?php echo esc_js( __( 'Processed', 'beseo' ) ); ?> ' + (state.processed || 0) + ' / ' + (state.max || '') + ' · ' + (state.queued || 0) + ' <?php echo esc_js( __( 'queued', 'beseo' ) ); ?>');
                        var done = payload.data.done;
                        if (done) {
                            if (runBtn) { runBtn.disabled = false; }
                            if (stopBtn) { stopBtn.disabled = true; }
                            crawlTimer = null;
                        } else {
                            crawlTimer = setTimeout(pollStep, 600);
                        }
                    }).catch(function() {
                        setStatus('<?php echo esc_js( __( 'Crawl failed.', 'beseo' ) ); ?>');
                        if (runBtn) { runBtn.disabled = false; }
                        if (stopBtn) { stopBtn.disabled = true; }
                        crawlTimer = null;
                    });
                }

                if (runBtn && urlInput) {
                    runBtn.addEventListener('click', function() {
                        var url = currentTargetUrl();
                        var limit = limitInput ? parseInt(limitInput.value, 10) : 10;
                        if (!url) {
                            setStatus('<?php echo esc_js( __( 'Enter a URL to analyse.', 'beseo' ) ); ?>');
                            return;
                        }
                        setStatus('<?php echo esc_js( __( 'Starting…', 'beseo' ) ); ?>');
                        runBtn.disabled = true;
                        if (stopBtn) { stopBtn.disabled = false; }
                        var form = new FormData();
                        form.append('action', 'be_schema_analyser_start');
                        form.append('nonce', nonce);
                        form.append('url', url);
                        form.append('max', isNaN(limit) ? 10 : limit);
                        fetch(ajaxurl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            body: form
                        }).then(function(resp){ return resp.json(); }).then(function(payload) {
                            runBtn.disabled = false;
                            if (!payload || !payload.success) {
                                setStatus((payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js( __( 'Analysis failed.', 'beseo' ) ); ?>');
                                if (stopBtn) { stopBtn.disabled = true; }
                                return;
                            }
                            setStatus('<?php echo esc_js( __( 'Crawl started…', 'beseo' ) ); ?>');
                            runBtn.disabled = true;
                            if (stopBtn) { stopBtn.disabled = false; }
                            pollStep();
                            loadHistory();
                        }).catch(function() {
                            runBtn.disabled = false;
                            setStatus('<?php echo esc_js( __( 'Analysis failed.', 'beseo' ) ); ?>');
                            if (stopBtn) { stopBtn.disabled = true; }
                        });
                    });
                }

                if (stopBtn) {
                    stopBtn.addEventListener('click', function() {
                        var form = new FormData();
                        form.append('action', 'be_schema_analyser_stop');
                        form.append('nonce', nonce);
                        fetch(ajaxurl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            body: form
                        }).finally(function() {
                            if (crawlTimer) {
                                clearTimeout(crawlTimer);
                                crawlTimer = null;
                            }
                            setStatus('<?php echo esc_js( __( 'Crawl stopped.', 'beseo' ) ); ?>');
                            if (runBtn) { runBtn.disabled = false; }
                            stopBtn.disabled = true;
                            loadHistory();
                        });
                    });
                }

                if (sitesAdd && sitesLabel && sitesUrl) {
                    sitesAdd.addEventListener('click', function() {
                        var label = sitesLabel.value.trim();
                        var url = sitesUrl.value.trim();
                        if (!label || !url) {
                            setStatus('<?php echo esc_js( __( 'Enter a label and URL.', 'beseo' ) ); ?>');
                            return;
                        }
                        if (!/^https?:\/\//i.test(url)) {
                            setStatus('<?php echo esc_js( __( 'Use http/https URLs only.', 'beseo' ) ); ?>');
                            return;
                        }
                        sites.push({ label: label, url: url });
                        saveSites();
                        renderSites();
                        sitesLabel.value = '';
                        sitesUrl.value = '';
                        setStatus('<?php echo esc_js( __( 'Website saved.', 'beseo' ) ); ?>');
                    });
                }

                targetRadios.forEach(function(radio) {
                    radio.addEventListener('change', function() {
                        var mode = radio.value;
                        var useManual = (mode === 'manual');
                        if (urlInput) {
                            urlInput.style.display = useManual ? 'inline-block' : 'none';
                            urlInput.disabled = !useManual;
                        }
                        if (siteSelect) {
                            siteSelect.style.display = useManual ? 'none' : 'inline-block';
                            siteSelect.disabled = useManual;
                        }
                    });
                });

                loadSites();
                renderSites();
                loadHistory();
            });
        })();
    </script>
    <?php
}
/**
 * Core analyser logic for a single URL.
 */
function be_schema_engine_analyse_url( $url ) {
    $issues      = array();
    $page_start  = microtime( true );
    $response    = wp_remote_get(
        $url,
        array(
            'timeout'     => 12,
            'redirection' => 5,
            'user-agent'  => sprintf( 'BESEO Analyser/1.0 (%s)', home_url() ),
        )
    );
    $page_end    = microtime( true );
    $duration_ms = (int) round( ( $page_end - $page_start ) * 1000 );

    if ( is_wp_error( $response ) ) {
        $issues[] = array(
            'severity' => 'error',
            'type'     => 'fetch',
            'message'  => sprintf( __( 'Fetch failed: %s', 'beseo' ), $response->get_error_message() ),
        );
        return array(
            'url'      => $url,
            'duration' => $duration_ms,
            'issues'   => $issues,
        );
    }

    $status = (int) wp_remote_retrieve_response_code( $response );
    $body   = wp_remote_retrieve_body( $response );
    if ( $status >= 400 ) {
        $issues[] = array(
            'severity' => 'error',
            'type'     => 'http',
            'message'  => sprintf( __( 'HTTP %d returned.', 'beseo' ), $status ),
        );
    }
    if ( empty( $body ) ) {
        $issues[] = array(
            'severity' => 'error',
            'type'     => 'content',
            'message'  => __( 'Empty response body.', 'beseo' ),
        );
    }

    libxml_use_internal_errors( true );
    $doc = new DOMDocument();
    $doc->loadHTML( $body );
    $xpath = new DOMXPath( $doc );
    libxml_clear_errors();

    $title_node = $xpath->query( '//head/title' );
    $title_text = ( $title_node && $title_node->length ) ? trim( wp_strip_all_tags( $title_node->item( 0 )->textContent ) ) : '';
    if ( '' === $title_text ) {
        $issues[] = array(
            'severity' => 'warn',
            'type'     => 'metadata',
            'message'  => __( 'Missing <title>.', 'beseo' ),
        );
    } elseif ( strlen( $title_text ) > 65 ) {
        $issues[] = array(
            'severity' => 'info',
            'type'     => 'metadata',
            'message'  => __( 'Title likely to truncate (over 65 characters).', 'beseo' ),
        );
    }

    $meta_desc = '';
    $desc_node = $xpath->query( '//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]' );
    if ( $desc_node && $desc_node->length ) {
        $meta_desc = trim( wp_strip_all_tags( $desc_node->item( 0 )->getAttribute( 'content' ) ) );
    }
    if ( '' === $meta_desc ) {
        $issues[] = array(
            'severity' => 'warn',
            'type'     => 'metadata',
            'message'  => __( 'Meta description missing.', 'beseo' ),
        );
    } elseif ( strlen( $meta_desc ) > 170 ) {
        $issues[] = array(
            'severity' => 'info',
            'type'     => 'metadata',
            'message'  => __( 'Meta description may truncate (over ~170 characters).', 'beseo' ),
        );
    }

    $h1_nodes = $xpath->query( '//h1' );
    $h1_count = $h1_nodes ? $h1_nodes->length : 0;
    if ( 0 === $h1_count ) {
        $issues[] = array(
            'severity' => 'warn',
            'type'     => 'content',
            'message'  => __( 'No H1 found on the page.', 'beseo' ),
        );
    } elseif ( $h1_count > 1 ) {
        $issues[] = array(
            'severity' => 'info',
            'type'     => 'content',
            'message'  => sprintf( __( 'Multiple H1 tags found (%d).', 'beseo' ), $h1_count ),
        );
    }

    $canonical_nodes = $xpath->query( '//link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="canonical"]' );
    if ( $canonical_nodes && $canonical_nodes->length ) {
        $canonical = trim( $canonical_nodes->item( 0 )->getAttribute( 'href' ) );
        if ( $canonical && ! wp_http_validate_url( $canonical ) ) {
            $issues[] = array(
                'severity' => 'warn',
                'type'     => 'metadata',
                'message'  => __( 'Canonical URL is invalid.', 'beseo' ),
            );
        }
    } else {
        $issues[] = array(
            'severity' => 'info',
            'type'     => 'metadata',
            'message'  => __( 'Canonical link missing.', 'beseo' ),
        );
    }

    $robots_node = $xpath->query( '//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="robots"]' );
    if ( $robots_node && $robots_node->length ) {
        $robots_content = strtolower( (string) $robots_node->item( 0 )->getAttribute( 'content' ) );
        if ( false !== strpos( $robots_content, 'noindex' ) ) {
            $issues[] = array(
                'severity' => 'warn',
                'type'     => 'index',
                'message'  => __( 'Robots meta contains noindex.', 'beseo' ),
            );
        }
    }

    $links = $xpath->query( '//a[@href]' );
    $link_count = $links ? $links->length : 0;
    if ( $link_count < 5 ) {
        $issues[] = array(
            'severity' => 'info',
            'type'     => 'links',
            'message'  => __( 'Very few links found on the page; check internal linking.', 'beseo' ),
        );
    }
    $internal_links = array();
    $host = wp_parse_url( $url, PHP_URL_HOST );
    if ( $links && $host ) {
        foreach ( $links as $node ) {
            $href = trim( $node->getAttribute( 'href' ) );
            if ( ! $href ) {
                continue;
            }
            if ( 0 === strpos( $href, '#' ) ) {
                continue;
            }
            if ( 0 === strpos( $href, 'mailto:' ) || 0 === strpos( $href, 'tel:' ) ) {
                continue;
            }
            if ( 0 === strpos( $href, '//' ) ) {
                $href = 'https:' . $href;
            }
            if ( 0 === strpos( $href, '/' ) ) {
                $href = trailingslashit( rtrim( home_url(), '/' ) ) . ltrim( $href, '/' );
            }
            $parsed = wp_parse_url( $href );
            if ( $parsed && isset( $parsed['host'] ) && $parsed['host'] === $host && wp_http_validate_url( $href ) ) {
                $internal_links[] = $href;
            }
        }
    }

    return array(
        'url'      => $url,
        'duration' => $duration_ms,
        'status'   => $status,
        'issues'   => $issues,
        'body'     => $body,
        'internal' => array_values( array_unique( $internal_links ) ),
    );
}
