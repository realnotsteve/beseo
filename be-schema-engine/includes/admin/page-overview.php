<?php
/**
 * Overview Admin Page
 *
 * Submenu: BE SEO → Overview (default landing)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the "Overview" admin page.
 */
function be_schema_engine_render_overview_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $hero_image_url = plugins_url( 'assets/images/admin/be_seo-landing_image.webp', BE_SCHEMA_ENGINE_PLUGIN_FILE );

    $changelog_text = '';
    $changelog_path = trailingslashit( BE_SCHEMA_ENGINE_PLUGIN_DIR ) . '../CHANGELOG.md';
    $real_path      = realpath( $changelog_path );

    if ( $real_path && file_exists( $real_path ) && is_readable( $real_path ) ) {
        $raw = file_get_contents( $real_path );
        if ( false !== $raw ) {
            // Keep it short to avoid an overly tall hero area.
            $changelog_text = substr( $raw, 0, 1200 );
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
            color: #000;
            margin-bottom: 20px;
            min-height: 260px;
        }
        .be-schema-hero-row {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-start;
            justify-content: center;
            text-align: left;
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
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
            border-radius: 8px;
            padding: 20px 24px;
            display: inline-block;
        }
        .be-schema-overview-hero .be-schema-hero-content {
            min-width: 240px;
            text-align: center;
        }
        .be-schema-overview-hero .be-schema-hero-changelog {
            max-width: 480px;
            max-height: 220px;
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
            text-decoration: none;
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
                    <h1><?php esc_html_e( 'BE SEO', 'be-schema-engine' ); ?></h1>

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
                    <?php echo $changelog_html_safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
