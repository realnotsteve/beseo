<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_ajax_be_schema_analyser_run', 'be_schema_engine_handle_analyser_run' );
add_action( 'wp_ajax_be_schema_analyser_start', 'be_schema_engine_handle_analyser_start' );
add_action( 'wp_ajax_be_schema_analyser_step', 'be_schema_engine_handle_analyser_step' );
add_action( 'wp_ajax_be_schema_analyser_stop', 'be_schema_engine_handle_analyser_stop' );

/**
 * Simple per-user analyser state storage.
 */
function be_schema_engine_analyser_state_key() {
    $user_id = get_current_user_id();
    return 'be_schema_analyser_state_' . ( $user_id ? $user_id : 'guest' );
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
    be_schema_engine_analyser_state_set( $state );

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
            .be-schema-summary-card {
                border: 1px solid #dfe2e6;
                border-radius: 6px;
                padding: 12px;
                background: linear-gradient(135deg, #f9fbfd, #eef2f5);
                margin-bottom: 10px;
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
                <input type="text" id="be-schema-analyser-url" class="regular-text be-schema-analyser-url" value="<?php echo esc_url( $home_url ); ?>" placeholder="https://example.com/" />
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
            <p class="description"><?php esc_html_e( 'Per-page details and filters will appear here. Use this tab to inspect specific URLs.', 'beseo' ); ?></p>
        </div>

        <div id="be-schema-analyser-history" class="be-schema-analyser-panel<?php echo ( 'history' === $default_tab ) ? ' active' : ''; ?>">
            <p class="description"><?php esc_html_e( 'History of scans and issue trends will appear here.', 'beseo' ); ?></p>
        </div>

        <div id="be-schema-analyser-settings" class="be-schema-analyser-panel<?php echo ( 'settings' === $default_tab ) ? ' active' : ''; ?>">
            <p class="description"><?php esc_html_e( 'Configure analyser scope, rate limits, and modules. (Coming soon.)', 'beseo' ); ?></p>
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
                var limitInput = document.getElementById('be-schema-analyser-limit');
                var statusNode = document.getElementById('be-schema-analyser-status');
                var issuesList = document.getElementById('be-schema-issues-list');
                var nonce = '<?php echo wp_create_nonce( 'be_schema_analyser' ); ?>';

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
                    if (!data || !data.issues || !data.issues.length) {
                        var p = document.createElement('p');
                        p.className = 'description';
                        p.textContent = '<?php echo esc_js( __( 'No issues detected for this page.', 'beseo' ) ); ?>';
                        issuesList.appendChild(p);
                        return;
                    }
                    var table = document.createElement('table');
                    table.className = 'be-schema-issues-table';
                    var thead = document.createElement('thead');
                    thead.innerHTML = '<tr><th><?php echo esc_js( __( 'Severity', 'beseo' ) ); ?></th><th><?php echo esc_js( __( 'Type', 'beseo' ) ); ?></th><th><?php echo esc_js( __( 'Message', 'beseo' ) ); ?></th></tr>';
                    table.appendChild(thead);
                    var tbody = document.createElement('tbody');
                    data.issues.forEach(function(issue) {
                        var tr = document.createElement('tr');
                        var sev = document.createElement('td');
                        var pill = document.createElement('span');
                        pill.className = 'be-schema-pill ' + (issue.severity || 'info');
                        pill.textContent = issue.severity || '';
                        sev.appendChild(pill);
                        var type = document.createElement('td');
                        type.textContent = issue.type || '';
                        var msg = document.createElement('td');
                        msg.textContent = issue.message || '';
                        tr.appendChild(sev);
                        tr.appendChild(type);
                        tr.appendChild(msg);
                        tbody.appendChild(tr);
                    });
                    table.appendChild(tbody);
                    issuesList.appendChild(table);
                }

                function setStatus(text) {
                    if (statusNode) {
                        statusNode.textContent = text || '';
                    }
                }

                var crawlTimer = null;

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
                        if (payload.data && payload.data.last && payload.data.last.issues) {
                            renderIssues({ issues: payload.data.last.issues });
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
                        var url = urlInput.value.trim();
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
                        });
                    });
                }
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
