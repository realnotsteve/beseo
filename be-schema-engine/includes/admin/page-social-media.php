<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function be_schema_engine_render_social_media_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'BE Schema Engine – Social Media', 'be-schema-engine' ); ?></h1>
        <p><?php esc_html_e( 'Future home for sameAs / social profile settings.', 'be-schema-engine' ); ?></p>
    </div>
    <?php
}