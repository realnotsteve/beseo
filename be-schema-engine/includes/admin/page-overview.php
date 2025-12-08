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
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .be-schema-overview-hero h1,
        .be-schema-overview-hero p {
            color: #000;
            text-shadow: none;
        }
        .be-schema-overview-hero .be-schema-hero-content {
            background-color: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid #e0e0e0;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
            border-radius: 8px;
            padding: 24px 28px;
            display: inline-block;
        }
        .be-schema-overview-hero .be-schema-hero-content a {
            color: #000;
            text-decoration: underline;
        }
    </style>

    <div class="wrap be-schema-engine-wrap be-schema-overview-wrap">
        <div class="be-schema-overview-hero">
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
                    <a href="https://github.com/realnotsteve/beseo" target="_blank" rel="noopener noreferrer">[ GitHub ]</a>
                </p>

                <p class="description" style="margin-top: 12pt;">
                    <a href="https://billevans.be" target="_blank" rel="noopener noreferrer">Bill's Site</a>
                </p>
            </div>
        </div>

        <h2><?php esc_html_e( 'Quick Links', 'be-schema-engine' ); ?></h2>
        <ul>
            <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=be-schema-engine-schema' ) ); ?>">
                <?php esc_html_e( 'Schema settings', 'be-schema-engine' ); ?>
            </a></li>
            <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=be-schema-engine-social-media' ) ); ?>">
                <?php esc_html_e( 'Social Media settings', 'be-schema-engine' ); ?>
            </a></li>
            <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=be-schema-engine-tools' ) ); ?>">
                <?php esc_html_e( 'Tools', 'be-schema-engine' ); ?>
            </a></li>
        </ul>
    </div>
    <?php
}
