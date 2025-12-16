<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_ajax_be_schema_validator_run', 'be_schema_engine_handle_validator_run' );

/**
 * AJAX handler for the BESEO validator.
 */
function be_schema_engine_handle_validator_run() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'You do not have permission to validate.', 'beseo' ),
            ),
            403
        );
    }

    check_ajax_referer( 'be_schema_validator', 'nonce' );

    $url            = isset( $_POST['url'] ) ? trim( (string) wp_unslash( $_POST['url'] ) ) : '';
    $enable_twitter = ! empty( $_POST['enableTwitter'] );
    $enable_og      = ! empty( $_POST['enableOg'] );

    if ( ! $enable_twitter && ! $enable_og ) {
        wp_send_json_error(
            array(
                'message' => __( 'Select at least one platform to validate.', 'beseo' ),
            )
        );
    }

    if ( ! $url || ! wp_http_validate_url( $url ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Please provide a valid http/https URL.', 'beseo' ),
            )
        );
    }

    $page_start = microtime( true );
    $request = wp_remote_get(
        $url,
        array(
            'timeout'     => 12,
            'redirection' => 5,
            /* translators: %s: site url */
            'user-agent'  => sprintf( 'BESEO Validator/2.0 (%s)', home_url() ),
        )
    );
    $page_end = microtime( true );
    $page_duration_ms = (int) round( ( $page_end - $page_start ) * 1000 );

    $result = array(
        'fetch'      => array(
            'status'    => 0,
            'message'   => '',
            'redirects' => 0,
            'final_url' => $url,
            'duration_ms' => $page_duration_ms,
        ),
        'platforms'  => array(
            'twitter' => $enable_twitter,
            'og'      => $enable_og,
        ),
        'resolved'   => array(),
        'image'      => array(),
        'warnings'   => array(),
        'legend'     => array(
            'green'  => __( 'Direct platform tag', 'beseo' ),
            'yellow' => __( 'Fallback used', 'beseo' ),
            'red'    => __( 'Missing/invalid', 'beseo' ),
        ),
        'metrics'    => array(
            'page_ms'  => $page_duration_ms,
            'image_ms' => 0,
        ),
    );

    if ( is_wp_error( $request ) ) {
        $result['fetch']['message'] = $request->get_error_message();
        $result['warnings'][]       = array(
            'status'    => 'error',
            'message'   => sprintf(
                /* translators: %s: error message */
                __( 'Page fetch failed: %s', 'beseo' ),
                $request->get_error_message()
            ),
            'platforms' => array( 'Open Graph', 'X' ),
        );
        if ( false !== stripos( $request->get_error_message(), 'timed out' ) ) {
            $result['warnings'][] = array(
                'status'    => 'warn',
                'message'   => __( 'The request timed out. Check network/robots.txt or retry.', 'beseo' ),
                'platforms' => array( 'Open Graph', 'X' ),
            );
        }
        wp_send_json_success( $result );
    }

    $status                 = (int) wp_remote_retrieve_response_code( $request );
        $result['fetch']['status'] = $status;
        $result['fetch']['message'] = (string) wp_remote_retrieve_response_message( $request );

        if ( isset( $request['http_response'] ) && is_object( $request['http_response'] ) && method_exists( $request['http_response'], 'get_response_object' ) ) {
            $response_obj = $request['http_response']->get_response_object();
            if ( isset( $response_obj->url ) && $response_obj->url ) {
                $result['fetch']['final_url'] = $response_obj->url;
            }
            if ( isset( $response_obj->history ) && is_array( $response_obj->history ) ) {
                $result['fetch']['redirects'] = count( $response_obj->history );
            }
        }

    $body = wp_remote_retrieve_body( $request );
    if ( 200 !== $status || empty( $body ) ) {
        $result['warnings'][] = array(
            'status'    => 'error',
            'message'   => sprintf(
                /* translators: %d: HTTP status */
                __( 'Page fetch failed (HTTP %d).', 'beseo' ),
                $status
            ),
            'platforms' => array( 'Open Graph', 'X' ),
        );
        wp_send_json_success( $result );
    }

    libxml_use_internal_errors( true );
    $doc = new DOMDocument();
    $doc->loadHTML( $body );
    $xpath = new DOMXPath( $doc );
    libxml_clear_errors();

    $meta_value = static function( $name, $attr ) use ( $xpath ) {
        $query = sprintf(
            '//meta[translate(@%1$s,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="%2$s"]',
            $attr,
            strtolower( $name )
        );
        $nodes = $xpath->query( $query );
        if ( $nodes && $nodes->length ) {
            return trim( wp_strip_all_tags( $nodes->item( 0 )->getAttribute( 'content' ) ) );
        }
        return '';
    };

    $tags = array(
        'twitter:card'       => $meta_value( 'twitter:card', 'name' ),
        'twitter:title'      => $meta_value( 'twitter:title', 'name' ),
        'twitter:description'=> $meta_value( 'twitter:description', 'name' ),
        'twitter:image'      => $meta_value( 'twitter:image', 'name' ),
        'twitter:image:alt'  => $meta_value( 'twitter:image:alt', 'name' ),
        'og:title'           => $meta_value( 'og:title', 'property' ),
        'og:description'     => $meta_value( 'og:description', 'property' ),
        'og:image'           => $meta_value( 'og:image', 'property' ),
        'og:image:width'     => $meta_value( 'og:image:width', 'property' ),
        'og:image:height'    => $meta_value( 'og:image:height', 'property' ),
        'meta:description'   => $meta_value( 'description', 'name' ),
    );

    $title_node          = $xpath->query( '//head/title' );
    $html_title          = ( $title_node && $title_node->length ) ? trim( wp_strip_all_tags( $title_node->item( 0 )->textContent ) ) : '';
    $domain              = wp_parse_url( $result['fetch']['final_url'], PHP_URL_HOST );
    $og_image_dimensions = array(
        'width'  => (int) $tags['og:image:width'],
        'height' => (int) $tags['og:image:height'],
    );
    $result['fetch']['host'] = $domain ? $domain : '';
    $url_parts = wp_parse_url( $url );
    if ( isset( $url_parts['scheme'] ) && 'https' !== strtolower( $url_parts['scheme'] ) ) {
        $result['warnings'][] = array(
            'status'    => 'warn',
            'message'   => __( 'URL is not HTTPS; some platforms may refuse previews.', 'beseo' ),
            'platforms' => array( 'Open Graph', 'X' ),
        );
    }

    $build_field = static function( $candidates, $platform_prefix ) {
        foreach ( $candidates as $source => $value ) {
            if ( '' !== $value && null !== $value ) {
                $confidence = ( 0 === strpos( $source, $platform_prefix ) ) ? 'green' : 'yellow';
                return array(
                    'value'      => $value,
                    'source'     => $source,
                    'confidence' => $confidence,
                );
            }
        }
        return array(
            'value'      => '',
            'source'     => '',
            'confidence' => 'red',
        );
    };

    $resolve_for_platform = static function( $platform, $tags, $html_title ) use ( $build_field, $domain ) {
        $is_twitter = ( 'twitter' === $platform );
        $title_candidates = $is_twitter
            ? array(
                'twitter:title' => $tags['twitter:title'],
                'og:title'      => $tags['og:title'],
                '<title>'       => $html_title,
            )
            : array(
                'og:title'  => $tags['og:title'],
                '<title>'   => $html_title,
            );

        $desc_candidates = $is_twitter
            ? array(
                'twitter:description' => $tags['twitter:description'],
                'og:description'      => $tags['og:description'],
                'meta[name=description]' => $tags['meta:description'],
            )
            : array(
                'og:description'      => $tags['og:description'],
                'meta[name=description]' => $tags['meta:description'],
            );

        $image_candidates = $is_twitter
            ? array(
                'twitter:image' => $tags['twitter:image'],
                'og:image'      => $tags['og:image'],
            )
            : array(
                'og:image' => $tags['og:image'],
            );

        $card_candidates = $is_twitter
            ? array(
                'twitter:card' => $tags['twitter:card'],
            )
            : array(
                'opengraph' => 'opengraph',
            );

        $title = $build_field( $title_candidates, $is_twitter ? 'twitter' : 'og' );
        $desc  = $build_field( $desc_candidates, $is_twitter ? 'twitter' : 'og' );
        $image = $build_field( $image_candidates, $is_twitter ? 'twitter' : 'og' );
        $card  = $build_field( $card_candidates, $is_twitter ? 'twitter' : 'og' );

        if ( 'red' === $card['confidence'] ) {
            $card['value']      = $is_twitter ? 'summary_large_image' : 'opengraph';
            $card['source']     = $is_twitter ? 'default summary_large_image' : 'opengraph';
            $card['confidence'] = 'yellow';
        }

        return array(
            'platform'    => $platform,
            'title'       => $title,
            'description' => $desc,
            'image'       => $image,
            'card'        => $card,
            'domain'      => array(
                'value'      => $domain ? $domain : '',
                'source'     => $domain ? 'url host' : '',
                'confidence' => $domain ? 'green' : 'red',
            ),
        );
    };

    $resolved_primary = $enable_twitter
        ? $resolve_for_platform( 'twitter', $tags, $html_title )
        : $resolve_for_platform( 'og', $tags, $html_title );

    $result['resolved']['primary'] = $resolved_primary;
    $result['resolved']['twitter'] = $enable_twitter ? $resolve_for_platform( 'twitter', $tags, $html_title ) : null;
    $result['resolved']['og']      = $enable_og ? $resolve_for_platform( 'og', $tags, $html_title ) : null;

    $image_url = '';
    if ( $enable_twitter && ! empty( $result['resolved']['twitter']['image']['value'] ) ) {
        $image_url = $result['resolved']['twitter']['image']['value'];
    } elseif ( $enable_og && ! empty( $result['resolved']['og']['image']['value'] ) ) {
        $image_url = $result['resolved']['og']['image']['value'];
    } else {
        $image_url = $resolved_primary['image']['value'];
    }

    $image_info = array(
        'status'       => 'unset',
        'httpStatus'   => 0,
        'contentType'  => '',
        'width'        => 0,
        'height'       => 0,
        'ratio'        => 0,
        'ratioLabel'   => '',
        'url'          => $image_url,
        'redirects'    => 0,
        'duration_ms'  => 0,
    );

    if ( '' === $image_url ) {
        $result['warnings'][] = array(
            'status'    => 'warn',
            'message'   => __( 'No image resolved for the selected platforms.', 'beseo' ),
            'platforms' => array( 'Open Graph', 'X' ),
        );
    } else {
        $image_start = microtime( true );
        $image_request = wp_remote_get(
            $image_url,
            array(
                'timeout'     => 10,
                'redirection' => 3,
                'stream'      => false,
                'user-agent'  => sprintf( 'BESEO Validator/2.0 (%s)', home_url() ),
            )
        );
        $image_end = microtime( true );
        $result['metrics']['image_ms'] = (int) round( ( $image_end - $image_start ) * 1000 );
        $image_info['duration_ms']      = $result['metrics']['image_ms'];

        if ( is_wp_error( $image_request ) ) {
            $result['warnings'][] = array(
                'status'    => 'warn',
                'message'   => sprintf(
                    /* translators: %s: error message */
                    __( 'Image fetch failed: %s', 'beseo' ),
                    $image_request->get_error_message()
                ),
                'platforms' => array( 'Open Graph', 'X' ),
            );
        } else {
            $image_status   = (int) wp_remote_retrieve_response_code( $image_request );
            $content_type   = (string) wp_remote_retrieve_header( $image_request, 'content-type' );
            $image_body     = wp_remote_retrieve_body( $image_request );
            $image_response = isset( $image_request['http_response'] ) && is_object( $image_request['http_response'] ) && method_exists( $image_request['http_response'], 'get_response_object' )
                ? $image_request['http_response']->get_response_object()
                : null;
            if ( $image_response && isset( $image_response->history ) && is_array( $image_response->history ) ) {
                $image_info['redirects'] = count( $image_response->history );
            }

            $image_info['status']      = $image_status === 200 ? 'ok' : 'error';
            $image_info['httpStatus']  = $image_status;
            $image_info['contentType'] = $content_type;

            if ( $image_body ) {
                $size = getimagesizefromstring( $image_body );
                if ( is_array( $size ) ) {
                    $image_info['width']  = isset( $size[0] ) ? (int) $size[0] : 0;
                    $image_info['height'] = isset( $size[1] ) ? (int) $size[1] : 0;
                    if ( $image_info['width'] > 0 && $image_info['height'] > 0 ) {
                        $image_info['ratio'] = round( $image_info['width'] / $image_info['height'], 3 );
                    }
                }
            }

            if ( 'ok' !== $image_info['status'] ) {
                $result['warnings'][] = array(
                    'status'    => 'error',
                    'message'   => sprintf(
                        /* translators: %d: HTTP status */
                        __( 'Image fetch failed (HTTP %d).', 'beseo' ),
                        $image_status
                    ),
                    'platforms' => array( 'Open Graph', 'X' ),
                );
            } else {
                if ( $image_info['redirects'] > 1 ) {
                    $result['warnings'][] = array(
                        'status'    => 'warn',
                        'message'   => __( 'Image was fetched with multiple redirects; some platforms may give up.', 'beseo' ),
                        'platforms' => array( 'Open Graph', 'X' ),
                    );
                }
                // Content type validation.
                if ( $content_type && false === strpos( $content_type, 'image/' ) ) {
                    $result['warnings'][] = array(
                        'status'    => 'warn',
                        'message'   => sprintf(
                            /* translators: %s: content type */
                            __( 'Unexpected image content type: %s', 'beseo' ),
                            $content_type
                        ),
                        'platforms' => array( 'Facebook', 'LinkedIn', 'X', 'Slack', 'WhatsApp' ),
                    );
                } elseif ( $content_type && false !== strpos( $content_type, 'image/webp' ) ) {
                    $result['warnings'][] = array(
                        'status'    => 'warn',
                        'message'   => __( 'WebP images may be skipped or downgraded by some platforms.', 'beseo' ),
                        'platforms' => array( 'Facebook', 'LinkedIn', 'X', 'Slack', 'WhatsApp', 'Discord' ),
                    );
                }

                // Use meta fallback if no dimensions from body.
                if ( 0 === $image_info['width'] && $og_image_dimensions['width'] && $og_image_dimensions['height'] ) {
                    $image_info['width']  = $og_image_dimensions['width'];
                    $image_info['height'] = $og_image_dimensions['height'];
                    $image_info['ratio']  = round( $og_image_dimensions['width'] / $og_image_dimensions['height'], 3 );
                    $image_info['ratioLabel'] = 'og:image dimensions';
                }

                if ( $image_info['width'] && $image_info['height'] ) {
                    $image_info['ratio'] = $image_info['ratio'] ?: round( $image_info['width'] / $image_info['height'], 3 );
                    $ratio = $image_info['ratio'];

                    $ratio_warning = '';
                    $platform_badges = array( 'Facebook', 'LinkedIn' );
                    // Helpers.
                    $close = static function( $ratio, $target, $tolerance ) {
                        return abs( $ratio - $target ) <= $tolerance;
                    };

                    if ( $close( $ratio, 1.91, 0.08 ) || $close( $ratio, 1.0, 0.05 ) ) {
                        // Safe, no warning.
                    } elseif ( $close( $ratio, 1.78, 0.06 ) || $close( $ratio, 1.33, 0.06 ) || $close( $ratio, 1.25, 0.05 ) ) {
                        $ratio_warning  = __( 'Supported but often cropped (16:9, 4:3, or 5:4).', 'beseo' );
                        $platform_badges = array( 'Facebook', 'LinkedIn', 'X' );
                    } elseif ( $close( $ratio, 0.8, 0.08 ) || $close( $ratio, 0.75, 0.08 ) || $close( $ratio, 0.56, 0.06 ) || $close( $ratio, 0.52, 0.05 ) ) {
                        $ratio_warning  = __( 'Usually cropped or masked on most platforms (4:5, 3:4, 9:16, ~1:1.9).', 'beseo' );
                        $platform_badges = array( 'Facebook', 'LinkedIn', 'X', 'Slack', 'WhatsApp', 'iMessage', 'Discord' );
                    } else {
                        $ratio_warning = __( 'Non-ideal aspect ratio; may crop on some platforms.', 'beseo' );
                    }

                    if ( $ratio_warning ) {
                        $image_info['ratioWarning'] = true;
                        $result['warnings'][] = array(
                            'status'    => 'warn',
                            'message'   => $ratio_warning,
                            'platforms' => $platform_badges,
                        );
                    }

                    // Twitter downgrade hints.
                    if ( $enable_twitter ) {
                        $card_type  = $result['resolved']['twitter']['card']['value'];
                        $width_ok   = $image_info['width'];
                        $height_ok  = $image_info['height'];
                        if ( 'summary_large_image' === $card_type && ( $width_ok && $height_ok ) && ( $width_ok < 300 || $height_ok < 157 ) ) {
                            $result['warnings'][] = array(
                                'status'    => 'warn',
                                'message'   => __( 'Image may trigger a Twitter card downgrade to summary (too small for large image). Recommended 1200×628; minimum 300×157.', 'beseo' ),
                                'platforms' => array( 'X' ),
                            );
                        }
                        if ( 'summary' === $card_type && ( $width_ok && $height_ok ) && ( $width_ok < 120 || $height_ok < 120 ) ) {
                            $result['warnings'][] = array(
                                'status'    => 'warn',
                                'message'   => __( 'Image may be rejected for summary cards (under 120×120). Recommended 1200×1200; minimum 120×120.', 'beseo' ),
                                'platforms' => array( 'X' ),
                            );
                        }
                        if ( empty( $tags['twitter:image:alt'] ) ) {
                            $result['warnings'][] = array(
                                'status'    => 'warn',
                                'message'   => __( 'twitter:image:alt is missing; add accessible alt text.', 'beseo' ),
                                'platforms' => array( 'X' ),
                            );
                        }
                    }
                }
            }
        }
    }

    $result['image'] = $image_info;

    if ( ! $result['warnings'] && 200 === $status ) {
        $result['warnings'][] = array(
            'status'    => 'ok',
            'message'   => __( 'Page fetched and parsed. No blocking issues detected.', 'beseo' ),
            'platforms' => array(),
        );
    }

    wp_send_json_success( $result );
}

/**
 * Render the Tools submenu page.
 *
 * For now, all diagnostics and status information live under
 * Schema → Settings, but this page provides quick entry points.
 */
function be_schema_engine_render_tools_page() {
    $tools_default_tab = 'validator';
    $current_page      = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    $requested_tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
    $is_settings_submenu = ( 'beseo-settings' === $current_page );
    $help_notice         = '';
    $help_overrides      = array();
    $validator_pages     = get_pages(
        array(
            'post_status' => 'publish',
        )
    );
    $validator_posts     = get_posts(
        array(
            'post_type'   => 'post',
            'numberposts' => -1,
            'post_status' => 'publish',
        )
    );
    $validator_page_data = array();
    foreach ( (array) $validator_pages as $page ) {
        $validator_page_data[] = array(
            'id'    => $page->ID,
            'title' => get_the_title( $page ),
            'url'   => get_permalink( $page ),
            'type'  => 'page',
        );
    }
    $validator_post_data = array();
    foreach ( (array) $validator_posts as $post_item ) {
        $validator_post_data[] = array(
            'id'    => $post_item->ID,
            'title' => get_the_title( $post_item ),
            'url'   => get_permalink( $post_item ),
            'type'  => 'post',
        );
    }

    wp_enqueue_script(
        'be-schema-help-accent',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'includes/admin/js/be-help-accent.js',
        array(),
        BE_SCHEMA_ENGINE_VERSION,
        true
    );

    if ( $is_settings_submenu ) {
        if ( function_exists( 'be_schema_help_overrides_handle_request' ) ) {
            $help_notice = be_schema_help_overrides_handle_request();
        }
        if ( function_exists( 'be_schema_help_overrides_get' ) ) {
            $help_overrides = be_schema_help_overrides_get();
        }
    }
    if ( $is_settings_submenu ) {
        // Default to Help tab if explicitly requested or after a save postback.
        if ( ( $requested_tab && 'help' === $requested_tab ) || ! empty( $_POST['be_schema_help_overrides_nonce'] ) ) {
            $tools_default_tab = 'help';
        } else {
            $tools_default_tab = 'validator';
        }
    } elseif ( $requested_tab && in_array( $requested_tab, array( 'dashboard', 'validator', 'images' ), true ) ) {
        $tools_default_tab = $requested_tab;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'BE Schema Engine – Tools', 'beseo' ); ?></h1>
        <style>
            .be-schema-help-accent {
                color: #00a0d2;
            }
            .be-schema-tools-panel {
                margin-top: 16px;
                display: none;
            }
            .be-schema-tools-panel.active {
                display: block;
            }
            .be-schema-validator-header {
                border: 1px solid #ccd0d4;
                background: #fff;
                border-radius: 6px;
                margin-top: 8px;
                overflow: hidden;
            }
            .be-schema-header-titles {
                display: grid;
                grid-template-columns: max-content max-content max-content 1fr;
                background: #e5e7ea;
                color: #444;
                font-weight: 600;
                font-size: 13px;
                text-transform: uppercase;
                padding: 8px 12px;
                gap: 0;
            }
            .be-schema-header-titles div {
                border-left: 1px solid #d1d4d8;
                padding-left: 10px;
            }
            .be-schema-header-titles div:first-child {
                border-left: none;
            }
            .be-schema-header-grid {
                display: grid;
                grid-template-columns: max-content max-content max-content 1fr;
                gap: 0;
                padding: 14px 12px 6px 12px;
            }
            .be-schema-header-section {
                padding: 4px 14px 10px;
                border-left: 1px solid #dfe2e6;
                min-height: 120px;
                display: flex;
                flex-direction: column;
                gap: 12px;
                justify-content: flex-start;
            }
            .be-schema-header-section:first-child {
                border-left: none;
                padding-left: 0;
            }
            .be-schema-header-section:last-child {
                padding-right: 0;
            }
            .be-schema-validator-context {
                display: grid;
                grid-template-columns: 1fr;
                gap: 8px;
                font-size: 12px;
                color: #444;
            }
            .be-schema-context-line {
                background: #eef2f5;
                border-radius: 999px;
                padding: 6px 10px;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                width: fit-content;
            }
            .be-schema-context-line .label {
                font-weight: 700;
                color: #2c3e50;
            }
            .be-schema-validator-select-wrap {
                display: flex;
                flex-direction: column;
                gap: 6px;
                min-width: 260px;
                width: 100%;
            }
            .be-schema-validator-select-wrap select,
            .be-schema-validator-select-wrap input[type="text"] {
                min-width: 260px;
                width: 100%;
            }
            .be-schema-validator-search input[type="text"] {
                width: 100%;
                min-width: 260px;
            }
            .be-schema-validator-actions {
                display: flex;
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            .be-schema-validator-actions button {
                min-width: 130px;
            }
            .be-schema-validator-service {
                min-width: 200px;
                width: 100%;
            }
            .be-schema-mini-badges {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-top: 6px;
            }
            .be-schema-mini-badge {
                background: #eef2f5;
                border-radius: 999px;
                padding: 3px 8px;
                font-size: 11px;
                color: #2c3e50;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            .be-schema-validator-platforms label {
                margin-right: 8px;
                margin-bottom: 2px;
            }
            .be-schema-engine-box {
                background: #eef2f5;
                border-radius: 8px;
                padding: 10px 12px;
                display: inline-flex;
                flex-wrap: wrap;
                gap: 10px 16px;
                align-items: center;
                width: auto;
            }
            .be-schema-engine-box.stacked {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .be-schema-engine-box .be-schema-validator-platforms {
                margin: 0;
            }
            .be-schema-engine-row {
                display: flex;
                gap: 16px;
                align-items: flex-start;
            }
            .be-schema-engine-col {
                flex: 0 1 auto;
                display: flex;
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            .be-schema-validator-rowline {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                align-items: center;
                white-space: nowrap;
            }
            .be-schema-validator-rowline label {
                margin-right: 8px;
                white-space: nowrap;
            }
            .be-schema-engine-box label {
                white-space: nowrap;
            }
            .be-schema-validator-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
                margin-top: 12px;
            }
            .be-schema-fetch-log {
                margin-top: 8px;
                font-size: 12px;
            }
            .be-schema-fetch-log summary {
                cursor: pointer;
                font-weight: 600;
            }
            .be-schema-fetch-log table {
                margin-top: 4px;
                border-collapse: collapse;
                width: 100%;
            }
            .be-schema-fetch-log td {
                padding: 2px 4px;
                border-bottom: 1px solid #e5e5e5;
            }
            .be-schema-validator-right {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .be-schema-warning-legend {
                font-size: 12px;
                color: #444;
                margin-bottom: 6px;
            }
            .be-schema-validator-card {
                border: 1px solid #ccd0d4;
                border-radius: 6px;
                padding: 12px;
                background: #fff;
            }
            .be-schema-validator-preview {
                border: 1px solid #dfe2e7;
                border-radius: 6px;
                padding: 12px;
                background: linear-gradient(135deg, #f7f9fb, #eef1f5);
            }
            .be-schema-preview-label {
                font-size: 12px;
                font-weight: 600;
                color: #3c434a;
                text-transform: uppercase;
                margin-bottom: 4px;
            }
            .be-schema-preview-img {
                position: relative;
                width: 100%;
                padding-top: 52.3%;
                border-radius: 4px;
                background: #e2e5ea;
                background-size: cover;
                background-position: center;
                margin-bottom: 8px;
                overflow: hidden;
            }
            .be-schema-preview-img .be-schema-crop-overlay {
                position: absolute;
                top: 12%;
                left: 8%;
                right: 8%;
                bottom: 12%;
                border: 2px dashed rgba(0,0,0,0.35);
                border-radius: 4px;
                display: none;
            }
            .be-schema-preview-img.crops-on .be-schema-crop-overlay {
                display: block;
            }
            .be-schema-preview-meta {
                font-size: 12px;
                color: #555;
                margin-bottom: 4px;
            }
            .be-schema-preview-domain-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 999px;
                background: #e6f4ff;
                color: #1d4b7a;
                font-size: 11px;
                margin-top: 4px;
            }
            .be-schema-preview-crop-flag {
                display: inline-block;
                margin-left: 8px;
                background: #fff3cd;
                color: #8a6d3b;
                padding: 2px 8px;
                border-radius: 999px;
                font-size: 11px;
            }
            .be-schema-preview-title {
                font-weight: 700;
                margin: 0 0 4px;
            }
            .be-schema-preview-desc {
                margin: 0;
                color: #444;
                font-size: 13px;
            }
            .be-schema-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 999px;
                font-size: 11px;
                margin-right: 6px;
                background: #f0f4f8;
                color: #22303a;
            }
            .be-schema-dot {
                display: inline-block;
                width: 10px;
                height: 10px;
                border-radius: 999px;
                margin-right: 6px;
            }
            .be-schema-dot.green { background: #2ecc71; }
            .be-schema-dot.yellow { background: #f1c40f; }
            .be-schema-dot.red { background: #e74c3c; }
            .be-schema-validator-table {
                width: 100%;
                border-collapse: collapse;
            }
            .be-schema-validator-table th,
            .be-schema-validator-table td {
                border-bottom: 1px solid #e5e5e5;
                padding: 6px;
                vertical-align: top;
                font-size: 13px;
            }
            .be-schema-validator-table th {
                width: 25%;
                font-weight: 600;
            }
            .be-schema-source-value {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }
            .be-schema-source-value .truncate {
                max-width: 320px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .be-schema-copy-btn {
                display: inline-block;
                padding: 2px 6px;
                font-size: 11px;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                background: #f6f7f7;
                cursor: pointer;
            }
            .be-schema-validator-legend {
                margin-top: 8px;
                font-size: 12px;
                color: #444;
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
            }
            .be-schema-warning-list {
                list-style: none;
                padding-left: 0;
                margin: 8px 0 0;
            }
            .be-schema-warning-list li {
                margin-bottom: 6px;
            }
            .be-schema-warning-list .status {
                font-weight: 700;
                margin-right: 6px;
            }
            .be-schema-warning-list .platform {
                display: inline-block;
                margin-left: 6px;
                font-size: 11px;
                background: #f0f4f8;
                border-radius: 999px;
                padding: 2px 8px;
            }
            .be-schema-warning-empty {
                color: #666;
            }
            .be-schema-validator-preview:not(:last-child) {
                margin-bottom: 12px;
            }
            .nav-tab-wrapper {
                margin-top: 12px;
            }
            /* Fallback styling in case admin nav-tab CSS is not present */
            .nav-tab {
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
            .nav-tab-active {
                background: #fff;
                color: #1d2327;
                border-bottom: 1px solid #fff;
            }
        </style>

        <h2 class="nav-tab-wrapper">
            <?php if ( ! $is_settings_submenu ) : ?>
                <a href="#be-schema-tools-dashboard" class="nav-tab<?php echo ( 'dashboard' === $tools_default_tab ) ? ' nav-tab-active' : ''; ?>" data-tools-tab="dashboard"><?php esc_html_e( 'Dashboard', 'beseo' ); ?></a>
            <?php endif; ?>
            <a href="#be-schema-tools-validator" class="nav-tab<?php echo ( 'validator' === $tools_default_tab ) ? ' nav-tab-active' : ''; ?>" data-tools-tab="validator"><?php esc_html_e( 'Validator', 'beseo' ); ?></a>
            <?php if ( $is_settings_submenu ) : ?>
                <a href="#be-schema-tools-help" class="nav-tab<?php echo ( 'help' === $tools_default_tab ) ? ' nav-tab-active' : ''; ?>" data-tools-tab="help"><?php esc_html_e( 'Help Text', 'beseo' ); ?></a>
            <?php endif; ?>
            <?php if ( ! $is_settings_submenu ) : ?>
                <a href="#be-schema-tools-images" class="nav-tab<?php echo ( 'images' === $tools_default_tab ) ? ' nav-tab-active' : ''; ?>" data-tools-tab="images"><?php esc_html_e( 'Images', 'beseo' ); ?></a>
            <?php endif; ?>
        </h2>

        <?php if ( ! $is_settings_submenu ) : ?>
            <div id="be-schema-tools-dashboard" class="be-schema-tools-panel<?php echo ( 'dashboard' === $tools_default_tab ) ? ' active' : ''; ?>">
                <p class="description">
                    <?php esc_html_e(
                        'Diagnostic information and effective status summaries live under Schema → Settings. Use this dashboard for quick reminders and links.',
                        'beseo'
                    ); ?>
                </p>
                <ul class="ul-disc">
                    <li><?php esc_html_e( 'Check Schema → Settings for debug, dry run, and image validation toggles.', 'beseo' ); ?></li>
                    <li><?php esc_html_e( 'Visit Schema → Snapshots to review the last BE_SCHEMA_DEBUG snapshot when debug is enabled.', 'beseo' ); ?></li>
                    <li><?php esc_html_e( 'Open Graph/Twitter dry-run toggles live under Platforms → Facebook/Twitter → Tools.', 'beseo' ); ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ( $is_settings_submenu ) : ?>
            <div id="be-schema-tools-help" class="be-schema-tools-panel<?php echo ( 'help' === $tools_default_tab ) ? ' active' : ''; ?>">
                <?php
                if ( function_exists( 'be_schema_help_overrides_render_form' ) ) {
                    be_schema_help_overrides_render_form( $help_overrides, $help_notice, false );
                } else {
                    echo '<p class="description">' . esc_html__( 'Help overrides are unavailable.', 'beseo' ) . '</p>';
                }
                ?>
            </div>
        <?php endif; ?>

        <div id="be-schema-tools-validator" class="be-schema-tools-panel<?php echo ( 'validator' === $tools_default_tab ) ? ' active' : ''; ?>">
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

        <?php if ( ! $is_settings_submenu ) : ?>
            <div id="be-schema-tools-images" class="be-schema-tools-panel<?php echo ( 'images' === $tools_default_tab ) ? ' active' : ''; ?>">
                <p class="description">
                    <?php esc_html_e(
                        'Image helpers: use Schema → Website → Global/Person/Publisher for recommended aspect ratios and validation pills. More tools coming soon.',
                    'beseo'
                ); ?>
            </p>
                <p>
                    <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=beseo-schema#website' ) ); ?>">
                        <?php esc_html_e( 'Go to Schema Images', 'beseo' ); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>
    <script>
        (function() {
            var validatorPages = <?php echo wp_json_encode( $validator_page_data ); ?>;
            var validatorPosts = <?php echo wp_json_encode( $validator_post_data ); ?>;
            var validatorAjax = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
            var validatorNonce = '<?php echo wp_create_nonce( 'be_schema_validator' ); ?>';

            var validatorStorageKey = 'be-schema-validator-state';
            var lastData = null;

            document.addEventListener('DOMContentLoaded', function () {
                var tabs = document.querySelectorAll('.nav-tab-wrapper a[data-tools-tab]');
                var panels = document.querySelectorAll('.be-schema-tools-panel');
                var defaultTab = '<?php echo esc_js( $tools_default_tab ); ?>';

                var validatorMode = document.querySelectorAll('input[name="be_schema_validator_mode"]');
                var validatorType = document.querySelectorAll('input[name="be_schema_validator_type"]');
                var validatorSelect = document.getElementById('be-schema-validator-select');
                var validatorManual = document.getElementById('be-schema-validator-manual');
                var searchWrap = document.querySelector('.be-schema-validator-search');
                var searchInput = document.getElementById('be-schema-validator-search');
                var includePosts = document.getElementById('be-schema-validator-include-posts');
                var ogCheckbox = document.getElementById('be-schema-validator-og');
                var twitterCheckbox = document.getElementById('be-schema-validator-twitter');
                var cropsCheckbox = document.getElementById('be-schema-validator-crops');
                var serviceSelect = document.getElementById('be-schema-validator-service');
                var copyCheckbox = document.getElementById('be-schema-validator-copy');
                var openNewCheckbox = document.getElementById('be-schema-validator-open-new');
                var validateBtn = document.getElementById('be-schema-validator-run');
                var reRunBtn = document.getElementById('be-schema-validator-rerun');
                var copySummaryBtn = document.getElementById('be-schema-validator-copy-summary');
                var toggleTwitter = document.getElementById('be-schema-toggle-twitter');
                var toggleOg = document.getElementById('be-schema-toggle-og');
                var validatorNote = document.getElementById('be-schema-validator-note');
                var warningList = document.getElementById('be-schema-warning-list');
                var sourceMap = document.getElementById('be-schema-source-map');
                var contextUrl = document.getElementById('be-schema-context-url');
                var contextPlatforms = document.getElementById('be-schema-context-platforms');
                var contextTime = document.getElementById('be-schema-context-time');
                var contextResult = document.getElementById('be-schema-context-result');
                var miniBadges = document.getElementById('be-schema-mini-badges');
                var fetchLog = {
                    container: document.getElementById('be-schema-fetch-log'),
                    pageStatus: document.getElementById('be-schema-log-page-status'),
                    pageTime: document.getElementById('be-schema-log-page-time'),
                    redirects: document.getElementById('be-schema-log-redirects'),
                    imageStatus: document.getElementById('be-schema-log-image-status'),
                    imageTime: document.getElementById('be-schema-log-image-time'),
                    imageType: document.getElementById('be-schema-log-image-type'),
                    imageSize: document.getElementById('be-schema-log-image-size')
                };

                var previewTwitter = {
                    wrap: document.getElementById('be-schema-preview-twitter'),
                    img: document.getElementById('be-schema-preview-twitter-img'),
                    title: document.getElementById('be-schema-preview-twitter-title'),
                        desc: document.getElementById('be-schema-preview-twitter-desc'),
                        domain: document.getElementById('be-schema-preview-twitter-domain'),
                        card: document.getElementById('be-schema-preview-twitter-card')
                    };
                var previewOg = {
                    wrap: document.getElementById('be-schema-preview-og'),
                    img: document.getElementById('be-schema-preview-og-img'),
                    title: document.getElementById('be-schema-preview-og-title'),
                    desc: document.getElementById('be-schema-preview-og-desc'),
                    domain: document.getElementById('be-schema-preview-og-domain')
                };

                function activateTab(key) {
                    tabs.forEach(function(tab) {
                        if (tab.getAttribute('data-tools-tab') === key) {
                            tab.classList.add('nav-tab-active');
                        } else {
                            tab.classList.remove('nav-tab-active');
                        }
                    });
                    panels.forEach(function(panel) {
                        if (panel.id === 'be-schema-tools-' + key) {
                            panel.classList.add('active');
                        } else {
                            panel.classList.remove('active');
                        }
                    });
                }

                tabs.forEach(function(tab) {
                    tab.addEventListener('click', function(event) {
                        event.preventDefault();
                        activateTab(tab.getAttribute('data-tools-tab'));
                    });
                });

                activateTab(defaultTab || 'dashboard');

                    function currentMode() {
                        var mode = 'dropdown';
                        validatorMode.forEach(function (radio) {
                            if (radio.checked) {
                                mode = radio.value;
                            }
                        });
                        return mode;
                    }

                function currentValidationType() {
                    var type = 'native';
                    validatorType.forEach(function (radio) {
                        if (radio.checked) {
                            type = radio.value;
                            }
                        });
                    return type;
                }

                function persistState() {
                    var state = {
                        mode: currentMode(),
                        type: currentValidationType(),
                        includePosts: includePosts ? includePosts.checked : false,
                        og: ogCheckbox ? ogCheckbox.checked : false,
                        twitter: twitterCheckbox ? twitterCheckbox.checked : false,
                        crops: cropsCheckbox ? cropsCheckbox.checked : false,
                        copy: copyCheckbox ? copyCheckbox.checked : false,
                        openNew: openNewCheckbox ? openNewCheckbox.checked : false,
                        url: currentUrl(),
                        selectValue: validatorSelect ? validatorSelect.value : '',
                        manualValue: validatorManual ? validatorManual.value : '',
                        search: searchInput ? searchInput.value : '',
                        showTwitter: toggleTwitter ? toggleTwitter.checked : true,
                        showOg: toggleOg ? toggleOg.checked : true,
                        service: serviceSelect ? serviceSelect.value : ''
                    };
                    try {
                        localStorage.setItem(validatorStorageKey, JSON.stringify(state));
                    } catch (e) {
                        // ignore
                    }
                }

                function restoreState() {
                    try {
                        var raw = localStorage.getItem(validatorStorageKey);
                        if (!raw) { return; }
                        var state = JSON.parse(raw);
                        if (state.mode && validatorMode) {
                            validatorMode.forEach(function(r){ r.checked = (r.value === state.mode); });
                        }
                        if (state.type && validatorType) {
                            validatorType.forEach(function(r){ r.checked = (r.value === state.type); });
                        }
                        if (includePosts && typeof state.includePosts !== 'undefined') {
                            includePosts.checked = state.includePosts;
                        }
                        if (ogCheckbox && typeof state.og !== 'undefined') { ogCheckbox.checked = state.og; }
                        if (twitterCheckbox && typeof state.twitter !== 'undefined') { twitterCheckbox.checked = state.twitter; }
                        if (cropsCheckbox && typeof state.crops !== 'undefined') { cropsCheckbox.checked = state.crops; }
                        if (copyCheckbox && typeof state.copy !== 'undefined') { copyCheckbox.checked = state.copy; }
                        if (openNewCheckbox && typeof state.openNew !== 'undefined') { openNewCheckbox.checked = state.openNew; }
                        if (toggleTwitter && typeof state.showTwitter !== 'undefined') { toggleTwitter.checked = state.showTwitter; }
                        if (toggleOg && typeof state.showOg !== 'undefined') { toggleOg.checked = state.showOg; }
                        if (serviceSelect && typeof state.service !== 'undefined') { serviceSelect.value = state.service; }
                        if (validatorSelect && state.selectValue) { validatorSelect.value = state.selectValue; }
                        if (validatorManual && typeof state.manualValue !== 'undefined') { validatorManual.value = state.manualValue; }
                        if (searchInput && typeof state.search !== 'undefined') { searchInput.value = state.search; }
                    } catch (e) {
                        // ignore
                    }
                }

                function isValidHttpUrl(value) {
                    if (!value) {
                        return false;
                    }
                    try {
                        var parsed = new URL(value);
                        return parsed.protocol === 'http:' || parsed.protocol === 'https:';
                    } catch (e) {
                        return false;
                    }
                }

                function currentUrl() {
                    var mode = currentMode();
                    var url = '';
                    if (mode === 'manual' && validatorManual) {
                        url = validatorManual.value.trim();
                    } else if (validatorSelect) {
                        url = (validatorSelect.value || '').trim();
                    }
                    return url;
                }

                function renderSelectOptions() {
                    if (!validatorSelect) {
                        return;
                    }
                    var previous = validatorSelect.value;
                    var options = validatorPages.slice();
                    if (includePosts && includePosts.checked) {
                        options = options.concat(validatorPosts);
                    }
                    var term = searchInput && searchInput.value ? searchInput.value.toLowerCase() : '';
                    if (term) {
                        options = options.filter(function (item) {
                            return item.title && item.title.toLowerCase().indexOf(term) !== -1;
                        });
                    }
                    options.sort(function(a, b) {
                        if (a.type !== b.type) {
                            return a.type.localeCompare(b.type);
                        }
                        return (a.title || '').localeCompare(b.title || '');
                    });
                    validatorSelect.innerHTML = '';
                    var placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = '<?php echo esc_js( __( 'Select a page', 'beseo' ) ); ?>';
                    validatorSelect.appendChild(placeholder);
                    options.forEach(function (item) {
                        var opt = document.createElement('option');
                        opt.value = item.url;
                        opt.textContent = item.title + (item.type === 'post' ? ' (post)' : '');
                        validatorSelect.appendChild(opt);
                    });
                    if (previous) {
                        validatorSelect.value = previous;
                    }
                }

                function syncValidatorMode() {
                    var mode = currentMode();
                    if (validatorSelect) {
                        validatorSelect.style.display = (mode === 'dropdown') ? 'inline-block' : 'none';
                    }
                    if (searchWrap) {
                        searchWrap.style.display = (mode === 'dropdown') ? 'flex' : 'none';
                    }
                    if (validatorManual) {
                        validatorManual.style.display = (mode === 'manual') ? 'inline-block' : 'none';
                    }
                    if (includePosts) {
                        includePosts.disabled = (mode !== 'dropdown');
                    }
                    persistState();
                    updateButtonState();
                }

                function syncValidationType() {
                    var type = currentValidationType();
                    var isNative = type === 'native';
                    if (ogCheckbox) { ogCheckbox.disabled = !isNative; }
                    if (twitterCheckbox) { twitterCheckbox.disabled = !isNative; }
                    if (cropsCheckbox) { cropsCheckbox.disabled = !isNative; }
                    if (serviceSelect) { serviceSelect.disabled = isNative; }
                    if (copyCheckbox) { copyCheckbox.disabled = isNative; }
                    if (openNewCheckbox) { openNewCheckbox.disabled = isNative; }
                    persistState();
                    updateButtonState();
                }

                function updateButtonState() {
                    var url = currentUrl();
                    var type = currentValidationType();
                    var platformsOn = (ogCheckbox && ogCheckbox.checked) || (twitterCheckbox && twitterCheckbox.checked);
                    var validUrl = isValidHttpUrl(url);
                    var externalReady = (serviceSelect && serviceSelect.value);
                    if (validateBtn) {
                        if (type === 'native') {
                            validateBtn.disabled = !(platformsOn && validUrl);
                        } else {
                            validateBtn.disabled = !(externalReady && validUrl);
                        }
                    }
                    if (reRunBtn) {
                        reRunBtn.disabled = validateBtn ? validateBtn.disabled : true;
                    }
                    if (contextResult) {
                        var guidance = '—';
                        if (type === 'native' && !platformsOn && validUrl) {
                            guidance = '<?php echo esc_js( __( 'Turn on at least one platform to validate.', 'beseo' ) ); ?>';
                        } else if (type === 'external' && !externalReady && validUrl) {
                            guidance = '<?php echo esc_js( __( 'Choose an external service to continue.', 'beseo' ) ); ?>';
                        } else if (validUrl) {
                            guidance = '<?php echo esc_js( __( 'Ready', 'beseo' ) ); ?>';
                        }
                        contextResult.textContent = guidance;
                        contextResult.dataset.state = 'guidance';
                    }
                    persistState();
                }

                function toggleCrops() {
                    var on = cropsCheckbox && cropsCheckbox.checked;
                    document.querySelectorAll('.be-schema-preview-img').forEach(function (el) {
                        el.classList.toggle('crops-on', on);
                    });
                }

                function applyPreviewToggles() {
                    if (previewTwitter.wrap && toggleTwitter) {
                        previewTwitter.wrap.style.display = toggleTwitter.checked ? 'block' : 'none';
                    }
                    if (previewOg.wrap && toggleOg) {
                        previewOg.wrap.style.display = toggleOg.checked ? 'block' : 'none';
                    }
                }

                function renderSourceRow(field, value, source, confidence) {
                    if (!sourceMap) {
                        return;
                    }
                    var row = sourceMap.querySelector('tr[data-field="' + field + '"]');
                    if (!row) {
                        return;
                    }
                    var valCell = row.querySelector('.value');
                    var sourceCell = row.querySelector('.source');
                    if (valCell) {
                        valCell.innerHTML = '';
                        var container = document.createElement('div');
                        container.className = 'be-schema-source-value';
                        var valSpan = document.createElement('span');
                        valSpan.className = 'truncate';
                        valSpan.textContent = value || '—';
                        container.appendChild(valSpan);
                        if (value && field === 'image') {
                            var copyBtn = document.createElement('button');
                            copyBtn.type = 'button';
                            copyBtn.className = 'be-schema-copy-btn';
                            copyBtn.textContent = '<?php echo esc_js( __( 'Copy URL', 'beseo' ) ); ?>';
                            copyBtn.addEventListener('click', function() {
                                navigator.clipboard.writeText(value);
                            });
                            container.appendChild(copyBtn);
                        }
                        if (!value) {
                            var missing = document.createElement('span');
                            missing.style.color = '#a00';
                            missing.textContent = '<?php echo esc_js( __( 'Missing', 'beseo' ) ); ?>';
                            container.appendChild(missing);
                        }
                        valCell.appendChild(container);
                    }
                    if (sourceCell) {
                        sourceCell.innerHTML = '';
                        var dot = document.createElement('span');
                        dot.className = 'be-schema-dot ' + (confidence || 'red');
                        sourceCell.appendChild(dot);
                        var text = document.createElement('span');
                        text.textContent = source || (value ? '<?php echo esc_js( __( 'fallback', 'beseo' ) ); ?>' : '<?php echo esc_js( __( 'missing', 'beseo' ) ); ?>');
                        sourceCell.appendChild(text);
                    }
                }

                function renderSourceMap(resolved) {
                    if (!resolved) {
                        return;
                    }
                    renderSourceRow('title', resolved.title && resolved.title.value, resolved.title && resolved.title.source, resolved.title && resolved.title.confidence);
                    renderSourceRow('description', resolved.description && resolved.description.value, resolved.description && resolved.description.source, resolved.description && resolved.description.confidence);
                    renderSourceRow('image', resolved.image && resolved.image.value, resolved.image && resolved.image.source, resolved.image && resolved.image.confidence);
                    renderSourceRow('card', resolved.card && resolved.card.value, resolved.card && resolved.card.source, resolved.card && resolved.card.confidence);
                    renderSourceRow('domain', resolved.domain && resolved.domain.value, resolved.domain && resolved.domain.source, resolved.domain && resolved.domain.confidence);
                }

                function applyPreview(preview, data, enabled) {
                    if (!preview.wrap) {
                        return;
                    }
                    if (!enabled || !data) {
                        preview.wrap.style.display = 'none';
                        return;
                    }
                    preview.wrap.style.display = 'block';
                    if (preview.title) {
                        preview.title.textContent = (data.title && data.title.value) || '—';
                    }
                    if (preview.desc) {
                        preview.desc.textContent = (data.description && data.description.value) || '—';
                    }
                    if (preview.domain) {
                        var domainText = (data.domain && data.domain.value) || '';
                        preview.domain.innerHTML = '';
                        if (domainText) {
                            var badge = document.createElement('span');
                            badge.className = 'be-schema-preview-domain-badge';
                            badge.textContent = domainText;
                            preview.domain.appendChild(badge);
                        }
                        if (data.image && data.image.ratioWarning) {
                            var cropFlag = document.createElement('span');
                            cropFlag.className = 'be-schema-preview-crop-flag';
                            cropFlag.textContent = '<?php echo esc_js( __( 'May crop', 'beseo' ) ); ?>';
                            preview.domain.appendChild(cropFlag);
                        }
                    }
                    if (preview.card && data.card) {
                        preview.card.textContent = data.card.value || '';
                    }
                    if (preview.img) {
                        var imgUrl = data.image && data.image.value ? data.image.value : '';
                        preview.img.style.backgroundImage = imgUrl ? 'url(' + imgUrl + ')' : '';
                        preview.img.classList.toggle('crops-on', cropsCheckbox && cropsCheckbox.checked);
                    }
                }

                function renderWarnings(warnings, data) {
                    if (!warningList) {
                        return;
                    }
                    warnings = warnings || [];
                    if (data && data.fetch && typeof data.fetch.redirects !== 'undefined' && data.fetch.redirects > 1) {
                        var redirWarn = {
                            status: 'warn',
                            message: '<?php echo esc_js( __( 'Page had multiple redirects; some platforms may stop after one.', 'beseo' ) ); ?>',
                            platforms: ['Open Graph', 'X']
                        };
                        warnings.push(redirWarn);
                    }
                    if (miniBadges) {
                        miniBadges.innerHTML = '';
                        warnings.forEach(function (item) {
                            if (item.status && item.status === 'error') {
                                var badge = document.createElement('span');
                                badge.className = 'be-schema-mini-badge';
                                badge.textContent = item.message || '';
                                miniBadges.appendChild(badge);
                            }
                        });
                    }
                    warningList.innerHTML = '';
                    if (!warnings || !warnings.length) {
                        var liEmpty = document.createElement('li');
                        liEmpty.className = 'be-schema-warning-empty';
                        liEmpty.textContent = '<?php echo esc_js( __( 'No issues detected.', 'beseo' ) ); ?>';
                        warningList.appendChild(liEmpty);
                        return;
                    }
                    var icons = {
                        ok: '✅',
                        warn: '⚠️',
                        error: '❌'
                    };
                    warnings.forEach(function (item) {
                        var li = document.createElement('li');
                        var status = document.createElement('span');
                        status.className = 'status';
                        status.textContent = icons[item.status] || '⚠️';
                        li.appendChild(status);
                        var msg = document.createElement('span');
                        msg.textContent = item.message || '';
                        li.appendChild(msg);
                        if (item.platforms && item.platforms.length) {
                            item.platforms.forEach(function (platform) {
                                var badge = document.createElement('span');
                                badge.className = 'platform';
                                badge.textContent = platform;
                                li.appendChild(badge);
                            });
                        }
                        warningList.appendChild(li);
                    });
                }

                function renderFetchLog(data) {
                    if (!fetchLog || !fetchLog.container) {
                        return;
                    }
                    if (!data || !data.fetch) {
                        fetchLog.container.style.display = 'none';
                        return;
                    }
                    fetchLog.container.style.display = 'block';
                    fetchLog.pageStatus.textContent = (data.fetch.status || '') + (data.fetch.message ? (' ' + data.fetch.message) : '');
                    fetchLog.pageTime.textContent = typeof data.fetch.duration_ms !== 'undefined' ? data.fetch.duration_ms + ' ms' : '—';
                    fetchLog.redirects.textContent = typeof data.fetch.redirects !== 'undefined' ? data.fetch.redirects : '—';
                    fetchLog.imageStatus.textContent = data.image ? (data.image.httpStatus || '') : '—';
                    fetchLog.imageTime.textContent = data.image && typeof data.image.duration_ms !== 'undefined' ? data.image.duration_ms + ' ms' : '—';
                    fetchLog.imageType.textContent = data.image ? (data.image.contentType || '') : '—';
                    fetchLog.imageSize.textContent = (data.image && data.image.width && data.image.height)
                        ? (data.image.width + '×' + data.image.height)
                        : '—';
                }

                function renderResponse(data) {
                    if (!data) {
                        return;
                    }
                    window.__beSchemaLastData = data;
                    lastData = data;
                    if (contextUrl) {
                        contextUrl.textContent = data.fetch && data.fetch.final_url ? data.fetch.final_url : currentUrl();
                    }
                    if (contextPlatforms) {
                        var labels = [];
                        if (data.platforms) {
                            if (data.platforms.og) { labels.push('<?php echo esc_js( __( 'OG', 'beseo' ) ); ?>'); }
                            if (data.platforms.twitter) { labels.push('<?php echo esc_js( __( 'Twitter', 'beseo' ) ); ?>'); }
                        }
                        contextPlatforms.textContent = labels.length ? labels.join(', ') : '—';
                    }
                    if (contextTime) {
                        contextTime.textContent = new Date().toLocaleTimeString();
                    }
                    if (contextResult) {
                        contextResult.textContent = data.fetch && data.fetch.status && data.fetch.status >= 200 && data.fetch.status < 300
                            ? '<?php echo esc_js( __( 'Validated', 'beseo' ) ); ?>'
                            : '<?php echo esc_js( __( 'Validation failed', 'beseo' ) ); ?>';
                        contextResult.dataset.state = 'result';
                    }
                    if (miniBadges) {
                        miniBadges.innerHTML = '';
                        var badgeItems = [];
                        if (data.fetch) {
                            badgeItems.push('HTTP ' + (data.fetch.status || '?'));
                            if (typeof data.fetch.duration_ms !== 'undefined') {
                                badgeItems.push((data.fetch.duration_ms || 0) + ' ms');
                            }
                            if (typeof data.fetch.redirects !== 'undefined') {
                                badgeItems.push('redir ' + data.fetch.redirects);
                            }
                        }
                        if (data.image) {
                            if (data.image.contentType) { badgeItems.push(data.image.contentType); }
                            if (data.image.width && data.image.height) { badgeItems.push(data.image.width + '×' + data.image.height); }
                            if (typeof data.image.duration_ms !== 'undefined') { badgeItems.push('img ' + data.image.duration_ms + ' ms'); }
                        }
                        badgeItems.forEach(function(label) {
                            var badge = document.createElement('span');
                            badge.className = 'be-schema-mini-badge';
                            badge.textContent = label;
                            miniBadges.appendChild(badge);
                        });
                    }
                    if (reRunBtn) {
                        reRunBtn.disabled = false;
                    }
                    if (copySummaryBtn) {
                        copySummaryBtn.disabled = false;
                    }
                    var resolved = data.resolved ? data.resolved.primary : null;
                    renderSourceMap(resolved);
                    applyPreview(previewTwitter, data.resolved ? data.resolved.twitter : null, (twitterCheckbox && twitterCheckbox.checked) && (toggleTwitter ? toggleTwitter.checked : true));
                    applyPreview(previewOg, data.resolved ? data.resolved.og : null, (ogCheckbox && ogCheckbox.checked) && (toggleOg ? toggleOg.checked : true));
                    renderWarnings(data.warnings, data);
                    renderFetchLog(data);
                    lastData = data;

                    if (validatorNote) {
                        var fetchNote = '';
                        if (data.fetch) {
                            fetchNote = 'HTTP ' + (data.fetch.status || '?');
                            if (typeof data.fetch.redirects !== 'undefined') {
                                fetchNote += ' · redirects: ' + data.fetch.redirects;
                            }
                            if (data.fetch.final_url) {
                                fetchNote += ' · ' + data.fetch.final_url;
                            }
                            if (data.fetch.host) {
                                fetchNote += ' · host: ' + data.fetch.host;
                            }
                        }
                        validatorNote.textContent = fetchNote;
                    }
                }

                function runValidation() {
                    var url = currentUrl();
                    var type = currentValidationType();
                    persistState();
                    if (!isValidHttpUrl(url) || !validateBtn) {
                        updateButtonState();
                        return;
                    }
                    if (type === 'external') {
                        var svc = serviceSelect ? serviceSelect.options[serviceSelect.selectedIndex] : null;
                        var svcUrl = svc && svc.getAttribute('data-url');
                        if (svc && svc.disabled) {
                            if (validatorNote) {
                                validatorNote.textContent = '<?php echo esc_js( __( 'Select an external service to continue.', 'beseo' ) ); ?>';
                            }
                            if (contextResult) {
                                contextResult.textContent = '<?php echo esc_js( __( 'Validation failed', 'beseo' ) ); ?>';
                            }
                            return;
                        }
                        if (copyCheckbox && copyCheckbox.checked && navigator.clipboard) {
                            navigator.clipboard.writeText(url);
                        }
                        if (svcUrl) {
                            var target = (openNewCheckbox && openNewCheckbox.checked) ? '_blank' : '_self';
                            window.open(svcUrl, target, 'noopener,noreferrer');
                            if (validatorNote) {
                                validatorNote.textContent = '<?php echo esc_js( __( 'Opening external validator…', 'beseo' ) ); ?>';
                            }
                            if (contextResult) {
                                contextResult.textContent = '<?php echo esc_js( __( 'External validator opened', 'beseo' ) ); ?>';
                                contextResult.dataset.state = 'result';
                            }
                        } else if (validatorNote) {
                            validatorNote.textContent = '<?php echo esc_js( __( 'Select an external service to continue.', 'beseo' ) ); ?>';
                        }
                        return;
                    }
                    validateBtn.disabled = true;
                    var originalLabel = validateBtn.textContent;
                    validateBtn.textContent = '<?php echo esc_js( __( 'Validating…', 'beseo' ) ); ?>';
                    if (reRunBtn) {
                        reRunBtn.disabled = true;
                    }
                    if (copySummaryBtn) {
                        copySummaryBtn.disabled = true;
                    }
                    if (contextResult) {
                        contextResult.textContent = '<?php echo esc_js( __( 'Validating…', 'beseo' ) ); ?>';
                        contextResult.dataset.state = 'result';
                    }
                    if (validatorNote) {
                        validatorNote.textContent = '<?php echo esc_js( __( 'Running validation…', 'beseo' ) ); ?>';
                    }

                    var form = new FormData();
                    form.append('action', 'be_schema_validator_run');
                    form.append('nonce', validatorNonce);
                    form.append('url', url);
                    form.append('enableOg', ogCheckbox && ogCheckbox.checked ? '1' : '');
                    form.append('enableTwitter', twitterCheckbox && twitterCheckbox.checked ? '1' : '');

                    var endpoint = validatorAjax || (window.ajaxurl || '');
                    fetch(endpoint, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: form
                    }).then(function (response) {
                        return response.json();
                    }).then(function (payload) {
                        validateBtn.disabled = false;
                        validateBtn.textContent = originalLabel;
                        if (!payload || !payload.success) {
                            if (validatorNote) {
                                var errorMsg = (payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js( __( 'Validation failed.', 'beseo' ) ); ?>';
                                validatorNote.textContent = errorMsg;
                            }
                            if (contextResult) {
                                contextResult.textContent = '<?php echo esc_js( __( 'Validation failed', 'beseo' ) ); ?>';
                                contextResult.dataset.state = 'result';
                            }
                            if (reRunBtn) {
                                reRunBtn.disabled = validateBtn.disabled;
                            }
                            return;
                        }
                        renderResponse(payload.data);
                    }).catch(function () {
                        validateBtn.disabled = false;
                        validateBtn.textContent = originalLabel;
                        if (reRunBtn) {
                            reRunBtn.disabled = validateBtn.disabled;
                        }
                        if (validatorNote) {
                            validatorNote.textContent = '<?php echo esc_js( __( 'Validation failed.', 'beseo' ) ); ?>';
                        }
                        if (contextResult) {
                            contextResult.textContent = '<?php echo esc_js( __( 'Validation failed', 'beseo' ) ); ?>';
                            contextResult.dataset.state = 'result';
                        }
                        if (copySummaryBtn) {
                            copySummaryBtn.disabled = false;
                        }
                    });
                }

                validatorMode.forEach(function (radio) {
                    radio.addEventListener('change', syncValidatorMode);
                });
                validatorType.forEach(function (radio) {
                    radio.addEventListener('change', syncValidationType);
                });
                if (validatorSelect) {
                    validatorSelect.addEventListener('change', function() {
                        persistState();
                        updateButtonState();
                    });
                }
                if (validatorManual) {
                    validatorManual.addEventListener('input', function() {
                        persistState();
                        updateButtonState();
                    });
                }
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        renderSelectOptions();
                        persistState();
                    });
                }
                if (includePosts) {
                    includePosts.addEventListener('change', renderSelectOptions);
                    includePosts.addEventListener('change', function() {
                        updateButtonState();
                        persistState();
                    });
                }
                if (ogCheckbox) {
                    ogCheckbox.addEventListener('change', function() {
                        updateButtonState();
                        persistState();
                    });
                }
                if (twitterCheckbox) {
                    twitterCheckbox.addEventListener('change', function() {
                        updateButtonState();
                        persistState();
                    });
                }
                if (cropsCheckbox) {
                    cropsCheckbox.addEventListener('change', function() {
                        toggleCrops();
                        persistState();
                    });
                }
                if (validateBtn) {
                    validateBtn.addEventListener('click', runValidation);
                }
                if (reRunBtn) {
                    reRunBtn.addEventListener('click', runValidation);
                }
                if (copyCheckbox) {
                    copyCheckbox.addEventListener('change', persistState);
                }
                if (openNewCheckbox) {
                    openNewCheckbox.addEventListener('change', persistState);
                }
                if (serviceSelect) {
                    serviceSelect.addEventListener('change', function() {
                        updateButtonState();
                        persistState();
                    });
                }
                if (toggleTwitter) {
                    toggleTwitter.addEventListener('change', function() {
                        applyPreviewToggles();
                        persistState();
                    });
                }
                if (toggleOg) {
                    toggleOg.addEventListener('change', function() {
                        applyPreviewToggles();
                        persistState();
                    });
                }
                if (copySummaryBtn) {
                    copySummaryBtn.addEventListener('click', function() {
                        var summary = [];
                        summary.push('Result: ' + (contextResult ? contextResult.textContent : ''));
                        summary.push('URL: ' + (contextUrl ? contextUrl.textContent : ''));
                        summary.push('Platforms: ' + (contextPlatforms ? contextPlatforms.textContent : ''));
                        summary.push('Last run: ' + (contextTime ? contextTime.textContent : ''));
                        var resolvedTwitter = (lastData && lastData.resolved) ? lastData.resolved.twitter : null;
                        var resolvedOg = (lastData && lastData.resolved) ? lastData.resolved.og : null;
                        if (resolvedTwitter) {
                            summary.push('Twitter title: ' + (resolvedTwitter.title && resolvedTwitter.title.value ? resolvedTwitter.title.value : ''));
                            summary.push('Twitter desc: ' + (resolvedTwitter.description && resolvedTwitter.description.value ? resolvedTwitter.description.value : ''));
                            summary.push('Twitter image: ' + (resolvedTwitter.image && resolvedTwitter.image.value ? resolvedTwitter.image.value : ''));
                        }
                        if (resolvedOg) {
                            summary.push('OG title: ' + (resolvedOg.title && resolvedOg.title.value ? resolvedOg.title.value : ''));
                            summary.push('OG desc: ' + (resolvedOg.description && resolvedOg.description.value ? resolvedOg.description.value : ''));
                            summary.push('OG image: ' + (resolvedOg.image && resolvedOg.image.value ? resolvedOg.image.value : ''));
                        }
                        if (lastData && lastData.warnings) {
                            summary.push('Warnings: ' + lastData.warnings.map(function(w){ return w.status.toUpperCase() + ': ' + w.message; }).join(' | '));
                        }
                        navigator.clipboard.writeText(summary.join('\n')).catch(function() {
                            if (validatorNote) {
                                validatorNote.textContent = '<?php echo esc_js( __( 'Copy failed. Please try again.', 'beseo' ) ); ?>';
                            }
                        });
                    });
                }

                restoreState();
                renderSelectOptions();
                syncValidatorMode();
                syncValidationType();
                updateButtonState();
                toggleCrops();
                applyPreviewToggles();
            });
        })();
    </script>
    <?php
}
