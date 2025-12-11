<?php
/**
 * Home Admin Page
 *
 * Submenu: BE SEO → Home (default landing)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the "Home" admin page.
 */
function be_schema_engine_render_overview_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $hero_image_url = plugins_url( 'assets/images/admin/be_seo-landing_image.webp', BE_SCHEMA_ENGINE_PLUGIN_FILE );

    $changelog_text = '';

    // Prefer packaged changelog inside the plugin; fall back to BE_SCHEMA_ENGINE_PLUGIN_DIR just in case.
    $plugin_dir     = trailingslashit( dirname( dirname( __DIR__ ) ) ); // from includes/admin → plugin root.
    $changelog_path = $plugin_dir . 'CHANGELOG.md';

    if ( ! file_exists( $changelog_path ) || ! is_readable( $changelog_path ) ) {
        $changelog_path = trailingslashit( BE_SCHEMA_ENGINE_PLUGIN_DIR ) . 'CHANGELOG.md';
    }

    if ( file_exists( $changelog_path ) && is_readable( $changelog_path ) ) {
        $raw = file_get_contents( $changelog_path );
        if ( false !== $raw ) {
            $max_preview_lines = 120; // keep preview short without cutting mid-line.
            $lines             = preg_split( "/\r?\n/", $raw );
            if ( is_array( $lines ) ) {
                $changelog_text = implode( "\n", array_slice( $lines, 0, $max_preview_lines ) );
            } else {
                $changelog_text = $raw;
            }
        }
    }

    if ( '' === $changelog_text ) {
        $changelog_text = __( 'Changelog not available.', 'beseo' );
    }

    /**
     * Render a small subset of Markdown into safe HTML for the hero preview.
     *
     * Supports headings (#, ##, ###), bullet lists (- item), and paragraphs,
     * plus styling hooks for bump type, files list, and filenames.
     *
     * @param string $markdown Raw markdown snippet.
     * @return string HTML.
     */
    $render_changelog = function ( $markdown ) {
        $lines          = preg_split( "/\r?\n/", $markdown );
        $html           = '';
        $open_list_type = ''; // '', 'generic', or 'files'.

        $close_list = static function () use ( &$html, &$open_list_type ) {
            if ( '' !== $open_list_type ) {
                $html          .= '</ul>';
                $open_list_type = '';
            }
        };

        foreach ( $lines as $line ) {
            $trim = trim( $line );
            if ( '' === $trim ) {
                $close_list();
                continue;
            }

            if ( 0 === strpos( $trim, '### ' ) ) {
                $close_list();
                $html .= '<h4 class="be-changelog-heading be-changelog-subsection">' . esc_html( substr( $trim, 4 ) ) . '</h4>';
                continue;
            }

            if ( 0 === strpos( $trim, '## ' ) ) {
                $close_list();
                $html .= '<h3 class="be-changelog-heading be-changelog-version">' . esc_html( substr( $trim, 3 ) ) . '</h3>';
                continue;
            }

            if ( 0 === strpos( $trim, '# ' ) ) {
                $close_list();
                $html .= '<h2 class="be-changelog-heading be-changelog-title">' . esc_html( substr( $trim, 2 ) ) . '</h2>';
                continue;
            }

            if ( preg_match( '/^-\\s*Bump type:\\s*(.+)$/i', $trim, $m ) ) {
                $close_list();
                $html .= '<p class="be-changelog-bump">Bump type: ' . esc_html( $m[1] ) . '</p>';
                continue;
            }

            if ( preg_match( '/^-\\s*Files:?$/i', $trim ) ) {
                $close_list();
               /* $html          .= '<p class="be-changelog-files-label">Files</p>'; */
                $html          .= '<ul class="be-changelog-files">';
                $open_list_type = 'files';
                continue;
            }

            if ( 0 === strpos( $trim, '- ' ) ) {
                $content = substr( $trim, 2 );
                if ( '' === $open_list_type ) {
                    $html          .= '<ul>';
                    $open_list_type = 'generic';
                }

                if ( 'files' === $open_list_type ) {
                    $html .= '<li><span class="be-changelog-file">' . esc_html( $content ) . '</span></li>';
                } else {
                    $html .= '<li>' . esc_html( $content ) . '</li>';
                }
                continue;
            }

            $close_list();
            $html .= '<p>' . esc_html( $trim ) . '</p>';
        }

        $close_list();

        return $html;
    };

    // Current runtime flags (schema + social).
    $engine_settings = function_exists( 'be_schema_engine_get_settings' ) ? be_schema_engine_get_settings() : get_option( 'be_schema_engine_settings', array() );
    if ( ! is_array( $engine_settings ) ) {
        $engine_settings = array();
    }

    $enabled           = function_exists( 'be_schema_globally_disabled' ) ? ! be_schema_globally_disabled() : ( '1' === (string) ( $engine_settings['enabled'] ?? '0' ) );
    $elementor_enabled = function_exists( 'be_schema_elementor_disabled' ) ? ! be_schema_elementor_disabled() : ( '1' === (string) ( $engine_settings['elementor_enabled'] ?? '0' ) );
    $debug_enabled     = function_exists( 'be_schema_is_debug_enabled' ) ? be_schema_is_debug_enabled() : ( '1' === (string) ( $engine_settings['debug_enabled'] ?? ( $engine_settings['debug'] ?? '0' ) ) );

    $social_settings = function_exists( 'be_schema_social_get_settings' ) ? be_schema_social_get_settings() : get_option( 'be_schema_social_settings', array() );
    if ( ! is_array( $social_settings ) ) {
        $social_settings = array();
    }

    $og_enabled = false;
    if ( isset( $social_settings['social_enable_og'] ) ) {
        $og_enabled = '1' === (string) $social_settings['social_enable_og'];
    } elseif ( isset( $social_settings['og_enabled'] ) ) {
        $og_enabled = '1' === (string) $social_settings['og_enabled'];
    } elseif ( isset( $social_settings['enabled'] ) ) {
        $og_enabled = '1' === (string) $social_settings['enabled'];
    }

    $twitter_enabled = false;
    if ( isset( $social_settings['social_enable_twitter'] ) ) {
        $twitter_enabled = '1' === (string) $social_settings['social_enable_twitter'];
    } elseif ( isset( $social_settings['twitter_enabled'] ) ) {
        $twitter_enabled = '1' === (string) $social_settings['twitter_enabled'];
    } elseif ( isset( $social_settings['enabled'] ) ) {
        $twitter_enabled = '1' === (string) $social_settings['enabled'];
    }

    $changelog_html      = $render_changelog( $changelog_text );
    $changelog_html_safe = wp_kses(
        $changelog_html,
        array(
            'h2'   => array( 'class' => array() ),
            'h3'   => array( 'class' => array() ),
            'h4'   => array( 'class' => array() ),
            'ul'   => array( 'class' => array() ),
            'li'   => array( 'class' => array() ),
            'p'    => array( 'class' => array() ),
            'span' => array( 'class' => array() ),
        )
    );
    ?>
    <style>
        body.wp-admin.toplevel_page_beseo #wpcontent,
        body.wp-admin.toplevel_page_beseo #wpbody-content {
            background: #b5b7bf; /* pick your color */
        }
         h2 {font-size: 36px;}
        .beseo-overview-wrap {
            margin-top: 20px; /* preserve top spacing; use WP's default horizontal gutters */
        }
        .be-schema-overview-hero {
            background-image: url('<?php echo esc_url( $hero_image_url ); ?>');
            background-size: cover;
            background-position: center;
            border-radius: 8px;
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .be-schema-hero-row {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            gap: 16px;
            align-items: stretch;
            justify-content: space-between;
            text-align: left;
            flex: 1;
            height: 100%;
            min-height: 0;
        }
        .be-schema-overview-hero p {
            color: #000;
            text-shadow: none;
        }
        .be-schema-overview-hero .be-schema-hero-content,
        .be-schema-overview-hero .be-schema-hero-changelog {
            background-color: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid #e0e0e0;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.38);
            border-radius: 8px;
            padding: 24px;
            display: inline-block;
            border-style: solid;
            border-width: 1px;
            border-color: grey;
            flex: 1;
            height: 100%;
        }
        .be-schema-overview-hero .be-schema-hero-content {
            flex: 0 0 33%;
            max-width: 33%;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 0;
        }
        .be-schema-overview-hero .be-schema-hero-changelog {
            flex: 1 1 0;
            max-width: 67%;
            display: flex;
            flex-direction: column;
            max-height: none;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            white-space: normal;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #000000ff;
        }
        .be-schema-hero-changelog .be-changelog-heading {
            margin: 10px 0 6px;
        }
        .be-schema-hero-changelog .be-changelog-title {
            margin-top: 18px;
        }
        .be-schema-hero-changelog .be-changelog-version {
            margin: 16px 0px 0px 0px; /* controls space above/below the version/date heading */
            color: #333692ff;
        }
        .be-schema-hero-changelog .be-changelog-bump {
            font-weight: 600;
            margin: 4px 0 0px;
            color: #5a3554ff;
        }
        .be-schema-hero-changelog .be-changelog-files-label {
            font-weight: 600;
            margin: 1px 0px 0px;
            color: #484848ff;
        }
        .be-schema-hero-changelog ul.be-changelog-files {
            list-style: square outside !important;
            margin: 0px 0px 0px 18px;
            padding-left: 0;
            color: #484848ff;
        }
        .be-schema-hero-changelog ul.be-changelog-files li {
            margin: 0px 0px;
            padding-left: 2px;
        }
        .be-schema-hero-changelog .be-changelog-file {
            font-family: Menlo, Consolas, "Liberation Mono", monospace;
        }

        .be-schema-hero-devnotes {
            text-align: left;
            margin-top: 12px;
            border-top: 1px solid rgba(0,0,0,0.08);
            padding-top: 10px;
        }

        .be-schema-hero-devnotes h4 {
            margin: 0 0 6px;
            font-size: 14px;
        }

        .be-schema-hero-devnotes ul {
            margin: 0;
            padding-left: 18px;
        }

        .be-schema-hero-devnotes li {
            margin-bottom: 4px;
            font-size: 12px;
        }
        .be-schema-overview-hero .be-schema-hero-content a {
            color: #000;
            text-decoration: underline;
        }
        .be-schema-status-row {
            margin: 12px 0 0;
        }
        .be-schema-status-pill {
            display: inline-block;
            margin: 4px 6px 0 0;
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            letter-spacing: 0.01em;
            background-color: #31b355ff;
            border: 1px solid #2a2a2aff;
            color: #ffffffff;
        }
        .be-schema-status-pill.off {
            background-color: #b03e31ff;
            border: 1px solid #2a2a2aff;
            color: #ffffffff;
        }
        .description a {
            text-decoration: none !important;
        }
        .description a:hover {
            text-decoration: underline !important;;
        }
    </style>

    <div class="wrap beseo-wrap beseo-overview-wrap">
        <div class="be-schema-overview-hero">
            <div class="be-schema-hero-row">
                <?php
                $version = defined( 'BE_SCHEMA_ENGINE_VERSION' ) ? BE_SCHEMA_ENGINE_VERSION : '';
                ?>
                <div class="be-schema-hero-content">
                    <h2 style="margin-bottom: 8px"><?php esc_html_e( 'BE SEO', 'beseo' ); ?></h2>

                    <p class="description">
                        <?php
                        /* translators: %s is the plugin version. */
                        printf( esc_html__( 'Version : %s', 'beseo' ), esc_html( $version ) );
                        ?>
                    </p>

                    <p class="be-schema-status-row">
                        <span class="be-schema-status-pill <?php echo $enabled ? '' : 'off'; ?>">
                            <?php echo $enabled ? esc_html__( 'Schema Engine: ON', 'beseo' ) : esc_html__( 'Schema Engine: OFF', 'beseo' ); ?>
                        </span>
                        <span class="be-schema-status-pill <?php echo $elementor_enabled ? '' : 'off'; ?>">
                            <?php echo $elementor_enabled ? esc_html__( 'Elementor Schema: ON', 'beseo' ) : esc_html__( 'Elementor Schema: OFF', 'beseo' ); ?>
                        </span>
                        <span class="be-schema-status-pill <?php echo $debug_enabled ? '' : 'off'; ?>">
                            <?php echo $debug_enabled ? esc_html__( 'Plugin Debug: ON', 'beseo' ) : esc_html__( 'Plugin Debug: OFF', 'beseo' ); ?>
                        </span>
                    </p>
                    <p class="be-schema-status-row">
                        <span class="be-schema-status-pill <?php echo $og_enabled ? '' : 'off'; ?>">
                            <?php echo $og_enabled ? esc_html__( 'OpenGraph: ON', 'beseo' ) : esc_html__( 'OpenGraph: OFF', 'beseo' ); ?>
                        </span>
                        <span class="be-schema-status-pill <?php echo $twitter_enabled ? '' : 'off'; ?>">
                            <?php echo $twitter_enabled ? esc_html__( 'Twitter Cards: ON', 'beseo' ) : esc_html__( 'Twitter Cards: OFF', 'beseo' ); ?>
                        </span>
                    </p>
                    <p class="description" style="margin-top: 12pt;">
                        <a href="https://github.com/realnotsteve/beseo" style="margin-right: 1px;" target="_blank" rel="noopener noreferrer">GitHub</a><span style="vertical-align: -4px; margin-right: 6px;" class="dashicons dashicons-external"></span>
                        <a href="https://billevans.be" style="margin-left: 6px; margin-right: 1px;" target="_blank" rel="noopener noreferrer">Bill's Site</a><span style="vertical-align: -4px;" class="dashicons dashicons-external"></span>
                    </p>

                    <div class="be-schema-hero-devnotes">
                        <h4><?php esc_html_e( 'Dev Workflow', 'beseo' ); ?></h4>
                        <ul>
                            <li><?php esc_html_e( 'Pre-commit auto-bumps version/changelog; commit message is auto-built from staged files (override with BESEO_COMMIT_MSG).', 'beseo' ); ?></li>
                            <li><?php esc_html_e( 'Use Schema/Social dry-run toggles for safe testing; debug snapshots surface in Schema Snapshots and Social Dashboard when WP_DEBUG + Debug are on.', 'beseo' ); ?></li>
                            <li><?php esc_html_e( 'Manual test checklist: see tests/README.md.', 'beseo' ); ?></li>
                            <li><?php esc_html_e( 'Notes live in beseo-devnotes.json; keep it updated when making changes.', 'beseo' ); ?></li>
                        </ul>
                    </div>
                </div>

                <div class="be-schema-hero-changelog" aria-label="<?php esc_attr_e( 'Changelog preview', 'beseo' ); ?>">
                    <?php echo $changelog_html_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
