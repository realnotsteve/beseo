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
            $changelog_text = substr( $raw, 0, 1200 ); // keep preview short.
        }
    }

    if ( '' === $changelog_text ) {
        $changelog_text = __( 'Changelog not available.', 'be-schema-engine' );
    }

    /**
     * Render a small subset of Markdown into safe HTML for the hero preview.
     *
     * Supports headings (#, ##, ###), bullet lists (- item), and paragraphs.
     *
     * @param string $markdown Raw markdown snippet.
     * @return string HTML.
     */
    $render_changelog = function ( $markdown ) {
        $lines    = preg_split( "/\r?\n/", $markdown );
        $html     = '';
        $in_list  = false;
        foreach ( $lines as $line ) {
            $trim = trim( $line );
            if ( '' === $trim ) {
                if ( $in_list ) {
                    $html   .= '</ul>';
                    $in_list = false;
                }
                continue;
            }

            if ( 0 === strpos( $trim, '### ' ) ) {
                if ( $in_list ) {
                    $html   .= '</ul>';
                    $in_list = false;
                }
                $html .= '<h3>' . esc_html( substr( $trim, 4 ) ) . '</h3>';
                continue;
            }

            if ( 0 === strpos( $trim, '## ' ) ) {
                if ( $in_list ) {
                    $html   .= '</ul>';
                    $in_list = false;
                }
                $html .= '<h2>' . esc_html( substr( $trim, 3 ) ) . '</h2>';
                continue;
            }

            if ( 0 === strpos( $trim, '# ' ) ) {
                if ( $in_list ) {
                    $html   .= '</ul>';
                    $in_list = false;
                }
                $html .= '<h2>' . esc_html( substr( $trim, 2 ) ) . '</h2>';
                continue;
            }

            if ( 0 === strpos( $trim, '- ' ) ) {
                if ( ! $in_list ) {
                    $html   .= '<ul>';
                    $in_list = true;
                }
                $html .= '<li>' . esc_html( substr( $trim, 2 ) ) . '</li>';
                continue;
            }

            if ( $in_list ) {
                $html   .= '</ul>';
                $in_list = false;
            }

            $html .= '<p>' . esc_html( $trim ) . '</p>';
        }

        if ( $in_list ) {
            $html .= '</ul>';
        }

        return $html;
    };

    $changelog_html      = $render_changelog( $changelog_text );
    $changelog_html_safe = wp_kses(
        $changelog_html,
        array(
            'h2' => array(),
            'h3' => array(),
            'ul' => array(),
            'li' => array(),
            'p'  => array(),
        )
    );
    ?>
    <style>
        .be-schema-overview-hero {
            background-image: url('<?php echo esc_url( $hero_image_url ); ?>');
            background-size: cover;
            background-position: center;
            border-radius: 8px;
            padding: 24px;
            min-height: 100vh;
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
        }
        .be-schema-overview-hero h1,
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
        }
        .be-schema-overview-hero .be-schema-hero-changelog {
            flex: 1 1 0;
            max-width: 67%;
            display: flex;
            flex-direction: column;
            max-height: none;
            overflow: auto;
            white-space: normal;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #111;
        }
        .be-schema-overview-hero .be-schema-hero-content a {
            color: #000;
            text-decoration: underline;
        }
        .description a {
            text-decoration: none !important;
        }
        .description a:hover {
            text-decoration: underline;
        }
    </style>

    <div class="wrap be-schema-engine-wrap be-schema-overview-wrap">
        <div class="be-schema-overview-hero">
            <div class="be-schema-hero-row">
                <?php
                $version = defined( 'BE_SCHEMA_ENGINE_VERSION' ) ? BE_SCHEMA_ENGINE_VERSION : '';
                ?>
                <div class="be-schema-hero-content">
                    <h2><?php esc_html_e( 'BE SEO', 'be-schema-engine' ); ?></h2>

                    <p class="description">
                        <?php
                        /* translators: %s is the plugin version. */
                        printf( esc_html__( 'Version : %s', 'be-schema-engine' ), esc_html( $version ) );
                        ?>
                    </p>

                    <p class="description" style="margin-top: 12pt;">
                        <a href="https://github.com/realnotsteve/beseo" target="_blank" rel="noopener noreferrer">GitHub</a><span class="dashicons dashicons-external"></span>
                    </p>

                    <p class="description" style="margin-top: 12pt;">
                        <a href="https://billevans.be" target="_blank" rel="noopener noreferrer">Bill's Site</a> <span class="dashicons dashicons-external"></span>
                    </p>
                </div>

                <div class="be-schema-hero-changelog" aria-label="<?php esc_attr_e( 'Changelog preview', 'be-schema-engine' ); ?>">
                    <h2><?php esc_html_e( 'Changelog', 'be-schema-engine' ); ?></h2>
                    <?php echo $changelog_html_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
