<?php
/**
 * Core Social Meta Engine
 *
 * Responsible for emitting OpenGraph and Twitter Card meta tags based on
 * settings from the Social Media admin page and the current page context.
 *
 * This file does NOT emit JSON-LD schema or canonical tags.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Determine if Social Media debug is enabled.
 *
 * Follows the same spirit as the main schema debug:
 * - Requires WP_DEBUG to be true.
 * - Respects BE_SCHEMA_DEBUG constant if defined and true.
 * - Falls back to the plugin's "debug" setting if available.
 *
 * @return bool
 */
function be_schema_social_debug_enabled() {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
        return false;
    }

    // Hard override via constant.
    if ( defined( 'BE_SCHEMA_DEBUG' ) && BE_SCHEMA_DEBUG ) {
        return true;
    }

    // Fallback to main plugin settings, if available.
    if ( function_exists( 'be_schema_engine_get_settings' ) ) {
        $settings = be_schema_engine_get_settings();
        if ( ! empty( $settings['debug'] ) && '1' === (string) $settings['debug'] ) {
            return true;
        }
    }

    return false;
}

/**
 * Get Social Media settings for OG/Twitter.
 *
 * NOTE: Keep defaults in sync with:
 * - includes/admin/page-social-media.php -> be_schema_engine_get_social_settings().
 *
 * Option name: be_schema_social_settings
 *
 * @return array
 */
function be_schema_social_get_settings() {
    $defaults = array(
        // Global settings.
        'social_enable_og'       => '0',
        'social_enable_twitter'  => '0',
        'social_default_image'   => '',

        // Facebook.
        'facebook_page_url'      => '',
        'facebook_default_image' => '',
        'facebook_app_id'        => '',
        'facebook_notes'         => '',

        // Twitter.
        'twitter_handle'         => '',
        'twitter_card_type'      => 'summary_large_image',
        'twitter_default_image'  => '',
        'twitter_notes'          => '',
    );

    $saved = get_option( 'be_schema_social_settings', array() );

    if ( ! is_array( $saved ) ) {
        $saved = array();
    }

    return wp_parse_args( $saved, $defaults );
}

/**
 * Clean a URL for OG/Twitter usage.
 *
 * - Strips tracking params (utm_*, gclid, fbclid).
 * - Drops fragment (#...).
 *
 * @param string $url Raw URL.
 * @return string Clean URL.
 */
function be_schema_social_clean_url( $url ) {
    if ( ! $url ) {
        return '';
    }

    $parts = wp_parse_url( $url );
    if ( false === $parts ) {
        return $url;
    }

    $scheme = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '';
    $host   = isset( $parts['host'] ) ? $parts['host'] : '';
    $port   = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
    $path   = isset( $parts['path'] ) ? $parts['path'] : '';

    $query_string = '';
    if ( ! empty( $parts['query'] ) ) {
        parse_str( $parts['query'], $params );

        if ( is_array( $params ) ) {
            foreach ( $params as $key => $value ) {
                if (
                    0 === strpos( $key, 'utm_' ) ||
                    'gclid' === $key ||
                    'fbclid' === $key
                ) {
                    unset( $params[ $key ] );
                }
            }

            if ( ! empty( $params ) ) {
                $query_string = '?' . http_build_query( $params );
            }
        }
    }

    // Drop fragment for OG/Twitter.
    return $scheme . $host . $port . $path . $query_string;
}

/**
 * Output OpenGraph & Twitter Card meta tags.
 *
 * Driven entirely by be_schema_social_settings (Social Media admin page).
 * Does NOT output canonical tags or JSON-LD.
 */
function be_schema_output_social_meta() {
    // Do not run in non-frontend contexts.
    if ( is_admin() || is_feed() ) {
        return;
    }

    if ( function_exists( 'is_robots' ) && is_robots() ) {
        return;
    }

    if ( is_embed() ) {
        return;
    }

    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
        return;
    }

    if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
        return;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }

    $settings = be_schema_social_get_settings();

    $enable_og      = ( isset( $settings['social_enable_og'] ) && '1' === $settings['social_enable_og'] );
    $enable_twitter = ( isset( $settings['social_enable_twitter'] ) && '1' === $settings['social_enable_twitter'] );

    // If both are disabled, do nothing.
    if ( ! $enable_og && ! $enable_twitter ) {
        return;
    }

    global $post;

    // Resolve page URL using canonical logic first.
    $url = '';
    if ( function_exists( 'wp_get_canonical_url' ) ) {
        $url = wp_get_canonical_url();
    }

    if ( ! $url && is_singular() ) {
        $current_post = $post instanceof WP_Post ? $post : get_post();
        if ( $current_post ) {
            $url = get_permalink( $current_post );
        }
    }

    if ( ! $url ) {
        $url = home_url( '/' );
    }

    $url = be_schema_social_clean_url( $url );

    // Site name.
    $site_name = get_bloginfo( 'name' );

    // Title: use WP's document title.
    $title = wp_get_document_title();

    // Description.
    $raw_description = '';
    $current_post    = null;

    if ( is_singular() ) {
        $current_post = $post instanceof WP_Post ? $post : get_post();

        if ( $current_post instanceof WP_Post ) {
            if ( has_excerpt( $current_post ) ) {
                $raw_description = get_the_excerpt( $current_post );
            } else {
                $raw_description = get_post_field( 'post_content', $current_post );
            }
        }
    }

    if ( ! $raw_description ) {
        $raw_description = get_bloginfo( 'description', 'display' );
    }

    if ( function_exists( 'be_schema_normalize_text' ) ) {
        $description = be_schema_normalize_text( $raw_description, 300 );
    } else {
        $description = wp_strip_all_tags( $raw_description, true );
        $description = trim( preg_replace( '/\s+/', ' ', $description ) );
        if ( function_exists( 'mb_substr' ) && strlen( $description ) > 300 ) {
            $description = mb_substr( $description, 0, 300 );
        } elseif ( strlen( $description ) > 300 ) {
            $description = substr( $description, 0, 300 );
        }
    }

    // Images.
    $featured_image         = '';
    $global_default_image   = ! empty( $settings['social_default_image'] ) ? $settings['social_default_image'] : '';
    $facebook_default_image = ! empty( $settings['facebook_default_image'] ) ? $settings['facebook_default_image'] : '';
    $twitter_default_image  = ! empty( $settings['twitter_default_image'] ) ? $settings['twitter_default_image'] : '';

    if ( $current_post instanceof WP_Post && has_post_thumbnail( $current_post ) ) {
        $featured_image = get_the_post_thumbnail_url( $current_post, 'full' );
    }

    // OG image selection: featured → Facebook default → global default.
    $og_image = $featured_image;
    if ( ! $og_image && $facebook_default_image ) {
        $og_image = $facebook_default_image;
    }
    if ( ! $og_image && $global_default_image ) {
        $og_image = $global_default_image;
    }

    // Twitter image selection: featured → Twitter default → global default.
    $twitter_image = $featured_image;
    if ( ! $twitter_image && $twitter_default_image ) {
        $twitter_image = $twitter_default_image;
    }
    if ( ! $twitter_image && $global_default_image ) {
        $twitter_image = $global_default_image;
    }

    // OG type and article metadata.
    $og_type                = 'website';
    $article_published_time = '';
    $article_modified_time  = '';
    $article_author         = '';

    if ( is_singular( 'post' ) && $current_post instanceof WP_Post ) {
        $og_type                = 'article';
        $article_published_time = get_post_time( 'c', true, $current_post );
        $article_modified_time  = get_post_modified_time( 'c', true, $current_post );
        $article_author         = get_the_author_meta( 'display_name', $current_post->post_author );
    }

    // Twitter handle and card type.
    $twitter_handle = '';
    if ( ! empty( $settings['twitter_handle'] ) ) {
        $twitter_handle = ltrim( trim( $settings['twitter_handle'] ), '@' );
    }

    $allowed_card_types = array( 'summary', 'summary_large_image' );
    $twitter_card_type  = 'summary_large_image';
    if ( ! empty( $settings['twitter_card_type'] ) && in_array( $settings['twitter_card_type'], $allowed_card_types, true ) ) {
        $twitter_card_type = $settings['twitter_card_type'];
    }

    $facebook_app_id = ! empty( $settings['facebook_app_id'] ) ? $settings['facebook_app_id'] : '';

    // Debug snapshot.
    if ( be_schema_social_debug_enabled() && function_exists( 'wp_json_encode' ) ) {
        $debug_payload = array(
            'context'     => 'social_meta',
            'url'         => $url,
            'title'       => $title,
            'description' => $description,
            'site_name'   => $site_name,
            'og'          => array(
                'enabled'           => $enable_og,
                'type'              => $og_type,
                'image'             => $og_image,
                'article_published' => $article_published_time,
                'article_modified'  => $article_modified_time,
                'article_author'    => $article_author,
                'facebook_app_id'   => $facebook_app_id,
            ),
            'twitter'     => array(
                'enabled'    => $enable_twitter,
                'card_type'  => $twitter_card_type,
                'image'      => $twitter_image,
                'handle'     => $twitter_handle,
            ),
            'images'      => array(
                'featured'         => $featured_image,
                'global_default'   => $global_default_image,
                'facebook_default' => $facebook_default_image,
                'twitter_default'  => $twitter_default_image,
            ),
        );

        error_log( 'BE_SOCIAL_DEBUG: ' . wp_json_encode( $debug_payload ) );
    }

    // Output meta tags.
    // We echo them directly to keep the function simple and fast.
    if ( $enable_og ) :
        ?>
        <meta property="og:title" content="<?php echo esc_attr( $title ); ?>" />
        <meta property="og:description" content="<?php echo esc_attr( $description ); ?>" />
        <meta property="og:type" content="<?php echo esc_attr( $og_type ); ?>" />
        <meta property="og:url" content="<?php echo esc_url( $url ); ?>" />
        <meta property="og:site_name" content="<?php echo esc_attr( $site_name ); ?>" />
        <?php if ( $og_image ) : ?>
            <meta property="og:image" content="<?php echo esc_url( $og_image ); ?>" />
        <?php endif; ?>

        <?php if ( 'article' === $og_type ) : ?>
            <?php if ( $article_published_time ) : ?>
                <meta property="article:published_time" content="<?php echo esc_attr( $article_published_time ); ?>" />
            <?php endif; ?>
            <?php if ( $article_modified_time ) : ?>
                <meta property="article:modified_time" content="<?php echo esc_attr( $article_modified_time ); ?>" />
            <?php endif; ?>
            <?php if ( $article_author ) : ?>
                <meta property="article:author" content="<?php echo esc_attr( $article_author ); ?>" />
            <?php endif; ?>
        <?php endif; ?>

        <?php if ( $facebook_app_id ) : ?>
            <meta property="fb:app_id" content="<?php echo esc_attr( $facebook_app_id ); ?>" />
        <?php endif; ?>
        <?php
    endif;

    if ( $enable_twitter ) :
        ?>
        <meta name="twitter:card" content="<?php echo esc_attr( $twitter_card_type ); ?>" />
        <meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>" />
        <meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>" />
        <?php if ( $twitter_image ) : ?>
            <meta name="twitter:image" content="<?php echo esc_url( $twitter_image ); ?>" />
        <?php endif; ?>
        <?php if ( $twitter_handle ) : ?>
            <meta name="twitter:site" content="@<?php echo esc_attr( $twitter_handle ); ?>" />
            <meta name="twitter:creator" content="@<?php echo esc_attr( $twitter_handle ); ?>" />
        <?php endif; ?>
        <?php
    endif;
}

/**
 * Hook output into wp_head.
 *
 * Priority 5 to run early but after most core setup.
 */
add_action( 'wp_head', 'be_schema_output_social_meta', 5 );