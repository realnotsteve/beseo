<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/schema-service.php';

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

function be_schema_engine_render_tester_selector_header() {
    ?>
    <div class="be-schema-tester-header">
        <?php
        if ( function_exists( 'be_schema_engine_render_schema_preview_selector' ) ) {
            be_schema_engine_render_schema_preview_selector();
        }
        ?>
    </div>
    <?php
}

function be_schema_engine_render_tester_panel_open( $panel_id, $is_active, $title, $description = '', $args = array() ) {
    $classes = array( 'be-schema-tools-panel' );
    if ( $is_active ) {
        $classes[] = 'active';
    }
    $description_class = 'description';
    if ( ! empty( $args['description_class'] ) ) {
        $description_class .= ' ' . $args['description_class'];
    }
    ?>
    <div id="<?php echo esc_attr( $panel_id ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
        <?php if ( $title ) : ?>
            <h2><?php echo esc_html( $title ); ?></h2>
        <?php endif; ?>
        <?php if ( '' !== $description ) : ?>
            <p class="<?php echo esc_attr( $description_class ); ?>">
                <?php echo esc_html( $description ); ?>
            </p>
        <?php endif; ?>
    <?php
}

function be_schema_engine_render_tester_panel_close() {
    ?>
    </div>
    <?php
}

/**
 * Render the Analyser/Settings submenu page.
 *
 * For now, all diagnostics and status information live under
 * Schema → Settings, but this page provides quick entry points.
 */
function be_schema_engine_render_tools_page() {
    $tools_default_tab = 'schema';
    $current_page      = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    $requested_tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
    $is_settings_submenu = ( 'beseo-settings' === $current_page );
    $help_notice         = '';
    $help_overrides      = array();
    if ( $is_settings_submenu && isset( $_POST['be_schema_playfair_settings_submitted'] ) ) {
        be_schema_engine_save_playfair_settings();
    }
    $settings = function_exists( 'be_schema_engine_get_settings' ) ? be_schema_engine_get_settings() : array();
    $analyser_default_tab = function_exists( 'be_schema_engine_get_analyser_default_tab' )
        ? be_schema_engine_get_analyser_default_tab()
        : 'overview';
    $analyser_home_url = home_url( '/' );

    wp_enqueue_script(
        'be-schema-help-accent',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'includes/admin/js/be-help-accent.js',
        array(),
        BE_SCHEMA_ENGINE_VERSION,
        true
    );

    if ( ! $is_settings_submenu ) {
        $image_validation_enabled = isset( $settings['image_validation_enabled'] ) ? ( '1' === (string) $settings['image_validation_enabled'] ) : true;

        wp_enqueue_style(
            'be-schema-admin',
            BE_SCHEMA_ENGINE_PLUGIN_URL . 'assets/css/schema.css',
            array(),
            BE_SCHEMA_ENGINE_VERSION
        );
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
            'be-schema-admin',
            BE_SCHEMA_ENGINE_PLUGIN_URL . 'assets/js/schema.js',
            array( 'media-editor', 'be-schema-selector' ),
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
        wp_localize_script(
            'be-schema-admin',
            'beSchemaSchemaData',
            array(
                'imageValidationEnabled' => $image_validation_enabled,
                'preview'                => array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'be_schema_preview_graph' ),
                    'homeUrl' => home_url( '/' ),
                    'listPagesNonce' => wp_create_nonce( 'be_schema_analyser' ),
                    'playfairDefaultProfile' => isset( $settings['playfair_default_profile'] ) ? $settings['playfair_default_profile'] : 'desktop_chromium',
                    'playfairNonce' => wp_create_nonce( 'be_schema_playfair_capture' ),
                    'playfairAction' => 'be_schema_playfair_capture',
                    'marker' => function_exists( 'be_schema_preview_marker_value' ) ? be_schema_preview_marker_value() : 'beseo-generated',
                    'playfairHealthNonce' => wp_create_nonce( 'be_schema_playfair_health' ),
                    'playfairTestUrl' => 'https://example.com',
                ),
                'labels'                 => array(
                    'undefined'          => __( 'Undefined', 'beseo' ),
                    'verified'           => __( 'Verified', 'beseo' ),
                    'resolution'         => __( 'Resolution', 'beseo' ),
                    'selectImage'        => __( 'Select Image', 'beseo' ),
                    'publisherNone'      => __( 'Publisher Type: None', 'beseo' ),
                    'publisherDedicated' => __( 'Publisher Type: Dedicated', 'beseo' ),
                    'publisherReference' => __( 'Publisher Type: Reference', 'beseo' ),
                ),
            )
        );
        wp_localize_script(
            'be-schema-analyser',
            'beSchemaAnalyserData',
            function_exists( 'be_schema_engine_get_analyser_localize_data' )
                ? be_schema_engine_get_analyser_localize_data( $analyser_default_tab )
                : array()
        );
    }

    if ( $is_settings_submenu ) {
        if ( function_exists( 'be_schema_help_overrides_handle_request' ) ) {
            $help_notice = be_schema_help_overrides_handle_request();
        }
        if ( function_exists( 'be_schema_help_overrides_get' ) ) {
            $help_overrides = be_schema_help_overrides_get();
        }
    }
    if ( $is_settings_submenu ) {
        if ( ! empty( $_POST['be_schema_playfair_settings_submitted'] ) ) {
            $tools_default_tab = 'wayfair';
        } elseif ( ! empty( $_POST['be_schema_help_overrides_nonce'] ) ) {
            $tools_default_tab = 'help';
        } elseif ( $requested_tab && in_array( $requested_tab, array( 'help', 'wayfair', 'lists' ), true ) ) {
            $tools_default_tab = $requested_tab;
        } else {
            $tools_default_tab = 'help';
        }
    } elseif ( $requested_tab && in_array( $requested_tab, array( 'schema', 'social', 'wayfair', 'analyser' ), true ) ) {
        $tools_default_tab = $requested_tab;
    }
    ?>
    <div class="wrap">
        <h1>
            <?php echo $is_settings_submenu ? esc_html__( 'BE SEO – Settings', 'beseo' ) : esc_html__( 'BE SEO – Analyser', 'beseo' ); ?>
        </h1>
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
            .be-schema-playfair-box {
                border: 1px solid #ccd0d4;
                background: #fff;
                border-radius: 6px;
                padding: 16px;
                margin-top: 12px;
            }
            .be-schema-tester-header {
                margin-top: 16px;
                margin-bottom: 16px;
            }
            .be-schema-sites-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
            }
            .be-schema-sites-row .regular-text {
                flex: 1 1 220px;
                min-width: 200px;
            }
            .be-schema-sites-row .button-primary {
                flex: 0 0 auto;
            }
            .be-schema-sites-check.is-success {
                background: #2c7a4b;
                border-color: #2c7a4b;
                color: #fff;
            }
            .be-schema-sites-check.is-error {
                background: #b42318;
                border-color: #b42318;
                color: #fff;
            }
            .be-schema-sites-check.is-pending {
                background: #e5e7eb;
                border-color: #cbd5e1;
                color: #1f2937;
            }
            .be-schema-sites-status {
                display: inline-flex;
                gap: 6px;
                align-items: center;
                margin-left: 8px;
                flex-wrap: wrap;
            }
            .be-schema-sites-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 2px 8px;
                border-radius: 999px;
                background: #f0f2f5;
                font-size: 11px;
                color: #475467;
            }
            .be-schema-sites-badge.is-success {
                background: #e7f6ec;
                color: #1b5e3a;
            }
            .be-schema-sites-badge.is-error {
                background: #fdecec;
                color: #8a1f11;
            }
            .be-schema-tools-panel input::placeholder,
            .be-schema-tools-panel textarea::placeholder {
                color: #9ca3af;
                opacity: 1;
            }
            .be-schema-website-list {
                margin-top: 8px;
                list-style: none;
                padding-left: 0;
            }
            .be-schema-website-list li {
                margin-bottom: 6px;
            }
            .be-schema-sites-status-divider {
                display: none;
                border: 0;
                border-top: 1px solid #e2e4e7;
                margin: 10px 0;
            }
        </style>

        <?php
        $tools_tabs = array();
        if ( ! $is_settings_submenu ) {
            $tools_tabs[] = array(
                'key'   => 'schema',
                'label' => __( 'Schema Tests', 'beseo' ),
                'href'  => '#be-schema-tools-schema',
                'data'  => array( 'tools-tab' => 'schema' ),
            );
            $tools_tabs[] = array(
                'key'   => 'social',
                'label' => __( 'Social Tests', 'beseo' ),
                'href'  => '#be-schema-tools-social',
                'data'  => array( 'tools-tab' => 'social' ),
            );
            $tools_tabs[] = array(
                'key'   => 'wayfair',
                'label' => __( 'Playfair', 'beseo' ),
                'href'  => '#be-schema-tools-wayfair',
                'data'  => array( 'tools-tab' => 'wayfair' ),
            );
            $tools_tabs[] = array(
                'key'   => 'analyser',
                'label' => __( 'Analyser', 'beseo' ),
                'href'  => '#be-schema-tools-analyser',
                'data'  => array( 'tools-tab' => 'analyser' ),
            );
        } else {
            $tools_tabs[] = array(
                'key'   => 'help',
                'label' => __( 'Help Text', 'beseo' ),
                'href'  => '#be-schema-tools-help',
                'data'  => array( 'tools-tab' => 'help' ),
            );
            $tools_tabs[] = array(
                'key'   => 'lists',
                'label' => __( 'Lists', 'beseo' ),
                'href'  => '#be-schema-tools-lists',
                'data'  => array( 'tools-tab' => 'lists' ),
            );
            $tools_tabs[] = array(
                'key'   => 'wayfair',
                'label' => __( 'Playfair', 'beseo' ),
                'href'  => '#be-schema-tools-wayfair',
                'data'  => array( 'tools-tab' => 'wayfair' ),
            );
        }
        be_schema_engine_admin_render_nav_tabs( $tools_tabs, $tools_default_tab );
        ?>

        <?php if ( ! $is_settings_submenu ) : ?>
            <?php
            $validator_styles = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/validator-styles.php';
            $validator_panel  = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/validator-panel.php';
            $validator_script = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/validator-script.php';
            $playfair_styles  = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/playfair-capture-styles.php';
            $playfair_panel   = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/playfair-capture-panel.php';
            $playfair_script  = BE_SCHEMA_ENGINE_PLUGIN_DIR . 'includes/admin/partials/playfair-capture-script.php';
            $validator_targets   = function_exists( 'be_schema_admin_get_validator_targets' )
                ? be_schema_admin_get_validator_targets()
                : array( 'pages' => array(), 'posts' => array() );
            $validator_page_data = $validator_targets['pages'] ?? array();
            $validator_post_data = $validator_targets['posts'] ?? array();

            if ( file_exists( $validator_styles ) ) {
                include $validator_styles;
            }
            if ( file_exists( $playfair_styles ) ) {
                include $playfair_styles;
            }
            ?>
            <?php be_schema_engine_render_tester_selector_header(); ?>

            <?php
            be_schema_engine_render_tester_panel_open(
                'be-schema-tools-schema',
                ( 'schema' === $tools_default_tab ),
                __( 'Schema Tests', 'beseo' ),
                __( 'Preview the JSON-LD graph that would be emitted for a specific page.', 'beseo' ),
                array( 'description_class' => 'be-schema-description' )
            );
            ?>
                <div id="be-schema-tab-preview">
                    <div class="be-schema-preview-upper">
                        <?php if ( function_exists( 'be_schema_engine_render_schema_preview_criteria' ) ) : ?>
                            <?php be_schema_engine_render_schema_preview_criteria(); ?>
                        <?php endif; ?>
                    </div>
                    <?php if ( function_exists( 'be_schema_engine_render_schema_preview_output' ) ) : ?>
                        <?php be_schema_engine_render_schema_preview_output(); ?>
                    <?php endif; ?>
                </div>
            <?php be_schema_engine_render_tester_panel_close(); ?>

            <?php
            be_schema_engine_render_tester_panel_open(
                'be-schema-tools-social',
                ( 'social' === $tools_default_tab ),
                __( 'Social Tests', 'beseo' ),
                __( 'Validate Open Graph and Twitter tags with previews and source mapping.', 'beseo' )
            );
            ?>
                <?php
                if ( file_exists( $validator_panel ) ) {
                    $validator_include_wrapper = false;
                    $validator_use_shared_selector = true;
                    include $validator_panel;
                } else {
                    echo '<p class="description">' . esc_html__( 'Tests unavailable.', 'beseo' ) . '</p>';
                }
                ?>
            <?php be_schema_engine_render_tester_panel_close(); ?>

            <?php
            be_schema_engine_render_tester_panel_open(
                'be-schema-tools-wayfair',
                ( 'wayfair' === $tools_default_tab ),
                ''
            );
            ?>
                <?php
                if ( file_exists( $playfair_panel ) ) {
                    be_schema_engine_admin_render_section_open(
                        __( 'Playfair Capture', 'beseo' ),
                        array(
                            'section_class' => 'be-schema-global-section',
                            'title_class'   => 'be-schema-section-title',
                            'title_tag'     => 'h4',
                        )
                    );
                    echo '<p class="description">' . esc_html__( 'Capture schema, Open Graph, and Twitter tags via Playfair using the shared selector above.', 'beseo' ) . '</p>';
                    $playfair_capture_context  = 'tester';
                    $playfair_capture_selector = 'schema';
                    $playfair_capture_id       = 'be-tester-playfair';
                    $playfair_capture_defaults = function_exists( 'be_schema_admin_get_playfair_defaults' )
                        ? be_schema_admin_get_playfair_defaults()
                        : array();
                    $playfair_capture_show_schema  = true;
                    $playfair_capture_show_og      = true;
                    $playfair_capture_show_twitter = true;
                    $playfair_capture_show_html    = true;
                    $playfair_capture_show_logs    = true;
                    include $playfair_panel;
                    be_schema_engine_admin_render_section_close();
                }
                ?>
            <?php be_schema_engine_render_tester_panel_close(); ?>

            <?php
            be_schema_engine_render_tester_panel_open(
                'be-schema-tools-analyser',
                ( 'analyser' === $tools_default_tab ),
                __( 'Analyser', 'beseo' ),
                __( 'Run quick crawls and review issue summaries, pages, and history.', 'beseo' )
            );
            ?>
                <?php
                if ( function_exists( 'be_schema_engine_render_analyser_content' ) ) {
                    be_schema_engine_render_analyser_content( $analyser_default_tab, $analyser_home_url );
                } else {
                    echo '<p class="description">' . esc_html__( 'Analyser unavailable.', 'beseo' ) . '</p>';
                }
                ?>
            <?php be_schema_engine_render_tester_panel_close(); ?>

            <?php
            if ( file_exists( $validator_script ) ) {
                include $validator_script;
            }
            if ( file_exists( $playfair_script ) ) {
                include $playfair_script;
            }
            ?>
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

        <?php if ( $is_settings_submenu ) : ?>
            <div id="be-schema-tools-lists" class="be-schema-tools-panel<?php echo ( 'lists' === $tools_default_tab ) ? ' active' : ''; ?>">
                <?php be_schema_engine_admin_render_section_open( __( 'Websites', 'beseo' ) ); ?>
                    <p class="description" id="be-schema-sites-empty"><?php esc_html_e( 'Website will appear here.', 'beseo' ); ?></p>
                    <ul class="be-schema-website-list" id="be-schema-sites-list"></ul>
                <?php be_schema_engine_admin_render_section_close(); ?>
                <?php be_schema_engine_admin_render_section_open( __( 'Library', 'beseo' ) ); ?>
                    <p class="description"><?php esc_html_e( 'Manage the list of websites used by the Analyser.', 'beseo' ); ?></p>
                    <div class="be-schema-sites-row">
                        <button type="button" class="button button-primary" id="be-schema-sites-add"><?php esc_html_e( 'Save Website', 'beseo' ); ?></button>
                        <input type="text" id="be-schema-sites-url" class="regular-text" placeholder="https://example.com/" />
                        <input type="text" id="be-schema-sites-label" class="regular-text" placeholder="<?php esc_attr_e( 'Label (e.g., Main Site)', 'beseo' ); ?>" />
                        <button type="button" id="be-schema-sites-local" class="be-schema-sites-check"><?php esc_html_e( 'Local', 'beseo' ); ?></button>
                        <button type="button" id="be-schema-sites-remote" class="be-schema-sites-check"><?php esc_html_e( 'Remote', 'beseo' ); ?></button>
                    </div>
                    <hr class="be-schema-sites-status-divider" id="be-schema-sites-status-divider" />
                    <p class="description" id="be-schema-sites-status"></p>
                <?php be_schema_engine_admin_render_section_close(); ?>
            </div>

            <div id="be-schema-tools-wayfair" class="be-schema-tools-panel<?php echo ( 'wayfair' === $tools_default_tab ) ? ' active' : ''; ?>">
                <?php settings_errors( 'be_schema_engine' ); ?>
                <?php
                $playfair_remote_base_url  = isset( $settings['playfair_remote_base_url'] ) ? $settings['playfair_remote_base_url'] : '';
                $playfair_local_base_url   = isset( $settings['playfair_local_base_url'] ) ? $settings['playfair_local_base_url'] : '';
                $playfair_mode             = isset( $settings['playfair_mode'] ) ? $settings['playfair_mode'] : 'auto';
                $playfair_timeout          = isset( $settings['playfair_timeout_seconds'] ) ? (int) $settings['playfair_timeout_seconds'] : 60;
                $playfair_default_profile  = isset( $settings['playfair_default_profile'] ) ? $settings['playfair_default_profile'] : 'desktop_chromium';
                $playfair_default_wait_ms  = isset( $settings['playfair_default_wait_ms'] ) ? (int) $settings['playfair_default_wait_ms'] : 1500;
                $playfair_default_locale   = isset( $settings['playfair_default_locale'] ) ? $settings['playfair_default_locale'] : '';
                $playfair_default_timezone = isset( $settings['playfair_default_timezone'] ) ? $settings['playfair_default_timezone'] : '';
                $playfair_allow_private    = ! empty( $settings['playfair_allow_private_targets'] );
                $playfair_token_set        = ! empty( $settings['playfair_remote_token'] );
                ?>
                <form method="post">
                    <?php wp_nonce_field( 'be_schema_playfair_save_settings', 'be_schema_playfair_settings_nonce' ); ?>
                    <input type="hidden" name="be_schema_playfair_settings_submitted" value="1" />

                    <div class="be-schema-playfair-box">
                        <h3><?php esc_html_e( 'Playfair Settings', 'beseo' ); ?></h3>
                        <p class="description"><?php esc_html_e( 'Configure Playfair capture endpoints and defaults.', 'beseo' ); ?></p>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Remote base URL', 'beseo' ); ?></th>
                                <td>
                                    <input type="url" class="regular-text code" name="be_schema_playfair_remote_base_url" value="<?php echo esc_attr( $playfair_remote_base_url ); ?>" placeholder="https://playfair.belexes.com" />
                                    <p class="description"><?php esc_html_e( 'Playfair remote base URL (uses /health and /capture).', 'beseo' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Remote token', 'beseo' ); ?></th>
                                <td>
                                    <?php if ( $playfair_token_set ) : ?>
                                        <p class="description"><?php esc_html_e( 'Token saved (hidden).', 'beseo' ); ?></p>
                                    <?php endif; ?>
                                    <input type="password" class="regular-text" name="be_schema_playfair_remote_token_new" value="" placeholder="<?php echo $playfair_token_set ? '********' : ''; ?>" autocomplete="off" />
                                    <label style="margin-left:8px;">
                                        <input type="checkbox" name="be_schema_playfair_remote_token_clear" value="1" />
                                        <?php esc_html_e( 'Clear token', 'beseo' ); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e( 'Stored securely; never displayed in full.', 'beseo' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Local base URL', 'beseo' ); ?></th>
                                <td>
                                    <input type="url" class="regular-text code" name="be_schema_playfair_local_base_url" value="<?php echo esc_attr( $playfair_local_base_url ); ?>" placeholder="http://127.0.0.1:3719" />
                                    <p class="description"><?php esc_html_e( 'Local Playfair base URL for workstation captures.', 'beseo' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Mode', 'beseo' ); ?></th>
                                <td>
                                    <label style="margin-right:12px;">
                                        <input type="radio" name="be_schema_playfair_mode" value="auto" <?php checked( 'auto', $playfair_mode ); ?> />
                                        <?php esc_html_e( 'Auto (try remote, then local)', 'beseo' ); ?>
                                    </label>
                                    <label style="margin-right:12px;">
                                        <input type="radio" name="be_schema_playfair_mode" value="remote" <?php checked( 'remote', $playfair_mode ); ?> />
                                        <?php esc_html_e( 'Remote (VPS)', 'beseo' ); ?>
                                    </label>
                                    <label>
                                        <input type="radio" name="be_schema_playfair_mode" value="local" <?php checked( 'local', $playfair_mode ); ?> />
                                        <?php esc_html_e( 'Local', 'beseo' ); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Timeout (seconds)', 'beseo' ); ?></th>
                                <td>
                                    <input type="number" class="small-text" name="be_schema_playfair_timeout_seconds" value="<?php echo esc_attr( $playfair_timeout ); ?>" min="5" max="300" step="1" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Default profile', 'beseo' ); ?></th>
                                <td>
                                    <select name="be_schema_playfair_default_profile">
                                        <option value="desktop_chromium" <?php selected( 'desktop_chromium', $playfair_default_profile ); ?>><?php esc_html_e( 'Desktop (Chromium)', 'beseo' ); ?></option>
                                        <option value="mobile_chromium" <?php selected( 'mobile_chromium', $playfair_default_profile ); ?>><?php esc_html_e( 'Mobile (Chromium)', 'beseo' ); ?></option>
                                        <option value="webkit" <?php selected( 'webkit', $playfair_default_profile ); ?>><?php esc_html_e( 'WebKit', 'beseo' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Default wait (ms)', 'beseo' ); ?></th>
                                <td>
                                    <input type="number" class="small-text" name="be_schema_playfair_default_wait_ms" value="<?php echo esc_attr( $playfair_default_wait_ms ); ?>" min="0" max="60000" step="100" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Default locale', 'beseo' ); ?></th>
                                <td>
                                    <input type="text" class="regular-text" name="be_schema_playfair_default_locale" value="<?php echo esc_attr( $playfair_default_locale ); ?>" placeholder="en-US" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Default timezone', 'beseo' ); ?></th>
                                <td>
                                    <input type="text" class="regular-text" name="be_schema_playfair_default_timezone" value="<?php echo esc_attr( $playfair_default_timezone ); ?>" placeholder="America/New_York" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Local File Access', 'beseo' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="be_schema_playfair_allow_private_targets" value="1" <?php checked( $playfair_allow_private ); ?> />
                                        <?php esc_html_e( 'Allow (SSRF risk)', 'beseo' ); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <?php submit_button( __( 'Save Playfair Settings', 'beseo' ) ); ?>
                </form>
            </div>
        <?php endif; ?>

    </div>
    <script>
        (function() {
            function initToolsTabs() {
                var tabs = document.querySelectorAll('.nav-tab-wrapper a[data-tools-tab]');
                var panels = document.querySelectorAll('.be-schema-tools-panel');
                var defaultTab = '<?php echo esc_js( $tools_default_tab ); ?>';

                if (!tabs.length || !panels.length) {
                    return;
                }

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

                activateTab(defaultTab || tabs[0].getAttribute('data-tools-tab'));
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initToolsTabs);
            } else {
                initToolsTabs();
            }
        })();
    </script>
    <script>
        (function() {
            function initSitesList() {
                var sitesStoreKey = 'be-schema-analyser-sites';
                var sites = [];
                var sitesCheckNonce = '<?php echo esc_js( wp_create_nonce( 'be_schema_sites_check' ) ); ?>';
                var sitesAjaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
                var playfairLocalBaseUrl = '<?php echo esc_js( $settings['playfair_local_base_url'] ?? '' ); ?>';
                var localSiteBaseUrl = '<?php echo esc_js( home_url() ); ?>';

                function setSitesStatus(message) {
                    var sitesStatus = document.getElementById('be-schema-sites-status');
                    var statusDivider = document.getElementById('be-schema-sites-status-divider');
                    if (!sitesStatus) {
                        return;
                    }
                    sitesStatus.textContent = message || '';
                    if (statusDivider) {
                        statusDivider.style.display = message ? 'block' : 'none';
                    }
                }

                function loadSites() {
                    try {
                        var raw = localStorage.getItem(sitesStoreKey);
                        sites = raw ? JSON.parse(raw) : [];
                        if (!Array.isArray(sites)) {
                            sites = [];
                        }
                        sites = sites.map(function(site) {
                            return {
                                label: site.label || '',
                                url: site.url || '',
                                localStatus: site.localStatus || 'unknown',
                                remoteStatus: site.remoteStatus || 'unknown',
                                autoCheck: !!site.autoCheck
                            };
                        });
                    } catch (e) {
                        sites = [];
                    }
                }

                function saveSites() {
                    try {
                        localStorage.setItem(sitesStoreKey, JSON.stringify(sites));
                    } catch (e) {}
                }

                function setCheckButtonState(button, state) {
                    if (!button) {
                        return;
                    }
                    button.classList.remove('is-success', 'is-error', 'is-pending');
                    if (state === 'ok') {
                        button.classList.add('is-success');
                    } else if (state === 'fail') {
                        button.classList.add('is-error');
                    } else if (state === 'pending') {
                        button.classList.add('is-pending');
                    }
                }

                function isPrivateIpv4(host) {
                    var parts = host.split('.');
                    if (parts.length !== 4) {
                        return false;
                    }
                    var nums = [];
                    for (var i = 0; i < parts.length; i++) {
                        var value = Number(parts[i]);
                        if (!Number.isFinite(value) || value < 0 || value > 255) {
                            return false;
                        }
                        nums.push(value);
                    }
                    if (nums[0] === 10) {
                        return true;
                    }
                    if (nums[0] === 127) {
                        return true;
                    }
                    if (nums[0] === 169 && nums[1] === 254) {
                        return true;
                    }
                    if (nums[0] === 192 && nums[1] === 168) {
                        return true;
                    }
                    if (nums[0] === 172 && nums[1] >= 16 && nums[1] <= 31) {
                        return true;
                    }
                    return false;
                }

                function isLocalHost(host) {
                    var normalized = (host || '').toLowerCase();
                    if (!normalized) {
                        return false;
                    }
                    if (normalized === 'localhost' || normalized === '127.0.0.1' || normalized === '::1') {
                        return true;
                    }
                    if (normalized.slice(-6) === '.local') {
                        return true;
                    }
                    if (/^\\d{1,3}(?:\\.\\d{1,3}){3}$/.test(normalized)) {
                        return isPrivateIpv4(normalized);
                    }
                    return false;
                }

                function resolveLocalBaseUrl() {
                    if (playfairLocalBaseUrl) {
                        try {
                            var parsedBase = new URL(playfairLocalBaseUrl);
                            var baseHost = parsedBase.hostname.toLowerCase();
                            var basePort = parsedBase.port;
                            var looksLikePlayfair = (basePort === '3719') || baseHost.indexOf('playfair') !== -1 || baseHost === 'host.docker.internal';
                            if (!looksLikePlayfair) {
                                return playfairLocalBaseUrl;
                            }
                        } catch (e) {
                            // ignore and fall through
                        }
                    }
                    return localSiteBaseUrl || playfairLocalBaseUrl || '';
                }

                function computeLocalUrl(targetUrl) {
                    var parsedTarget;
                    try {
                        parsedTarget = new URL(targetUrl);
                    } catch (e) {
                        return targetUrl;
                    }
                    if (isLocalHost(parsedTarget.hostname)) {
                        return targetUrl;
                    }
                    var localBase = resolveLocalBaseUrl();
                    if (!localBase) {
                        return targetUrl;
                    }
                    try {
                        var base = new URL(localBase);
                        return base.origin + parsedTarget.pathname + parsedTarget.search + parsedTarget.hash;
                    } catch (e) {
                        return targetUrl;
                    }
                }

                function isHttpUrl(value) {
                    return !!value && (value.indexOf('http://') === 0 || value.indexOf('https://') === 0);
                }

                function probeUrl(targetUrl, kind) {
                    var form = new FormData();
                    form.append('action', 'be_schema_sites_check');
                    form.append('nonce', sitesCheckNonce);
                    form.append('url', targetUrl);
                    form.append('kind', kind || '');
                    return fetch(sitesAjaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: form
                    }).then(function(response) {
                        return response.json().then(function(payload) {
                            if (payload && payload.success) {
                                return { ok: true, message: '' };
                            }
                            var message = '';
                            if (payload && payload.data && payload.data.message) {
                                message = payload.data.message;
                            } else if (payload && payload.message) {
                                message = payload.message;
                            }
                            if (!message && response && response.status) {
                                message = 'HTTP ' + response.status;
                            }
                            return { ok: false, message: message };
                        }).catch(function() {
                            return { ok: false, message: response && response.status ? ('HTTP ' + response.status) : 'Request failed.' };
                        });
                    }).catch(function(error) {
                        return { ok: false, message: error && error.message ? error.message : 'Request failed.' };
                    });
                }

                function updateSiteStatus(site, kind, status) {
                    if (!site) {
                        return;
                    }
                    if (kind === 'local') {
                        site.localStatus = status;
                    } else {
                        site.remoteStatus = status;
                    }
                    saveSites();
                    renderSites();
                }

                function runSiteCheck(site, kind) {
                    if (!site || !site.url) {
                        return Promise.resolve(false);
                    }
                    var targetUrl = (kind === 'local') ? computeLocalUrl(site.url) : site.url;
                    updateSiteStatus(site, kind, 'pending');
                    return probeUrl(targetUrl, kind).then(function(result) {
                        var ok = result && result.ok;
                        updateSiteStatus(site, kind, ok ? 'ok' : 'fail');
                        if (!ok && result && result.message) {
                            setSitesStatus(result.message);
                        } else if (ok) {
                            setSitesStatus('');
                        }
                        return ok;
                    });
                }

                function runInputCheck(kind, button) {
                    var sitesUrl = document.getElementById('be-schema-sites-url');
                    var url = sitesUrl ? sitesUrl.value.trim() : '';
                    if (!url) {
                        setSitesStatus('<?php echo esc_js( __( 'Enter a URL first.', 'beseo' ) ); ?>');
                        setCheckButtonState(button, 'fail');
                        return;
                    }
                    if (!isHttpUrl(url)) {
                        setSitesStatus('<?php echo esc_js( __( 'Use http/https URLs only.', 'beseo' ) ); ?>');
                        setCheckButtonState(button, 'fail');
                        return;
                    }
                    var targetUrl = (kind === 'local') ? computeLocalUrl(url) : url;
                    setCheckButtonState(button, 'pending');
                    probeUrl(targetUrl, kind).then(function(result) {
                        var ok = result && result.ok;
                        setCheckButtonState(button, ok ? 'ok' : 'fail');
                        if (!ok && result && result.message) {
                            setSitesStatus(result.message);
                        } else if (ok) {
                            setSitesStatus('');
                        }
                        var match = sites.find(function(site) { return site.url === url; });
                        if (match) {
                            updateSiteStatus(match, kind, ok ? 'ok' : 'fail');
                        }
                    });
                }

                function renderSites() {
                    var sitesList = document.getElementById('be-schema-sites-list');
                    var sitesEmpty = document.getElementById('be-schema-sites-empty');
                    if (!sitesList) {
                        return;
                    }
                    sitesList.innerHTML = '';
                    if (sitesEmpty) {
                        sitesEmpty.style.display = sites.length ? 'none' : 'block';
                    }
                    if (!sites.length) {
                        return;
                    }
                    sites.forEach(function(site, idx) {
                        var li = document.createElement('li');
                        var row = document.createElement('div');
                        row.className = 'be-schema-sites-row';

                        var saveBtn = document.createElement('button');
                        saveBtn.type = 'button';
                        saveBtn.className = 'button button-primary';
                        saveBtn.textContent = '<?php echo esc_js( __( 'Save Website', 'beseo' ) ); ?>';

                        var urlInput = document.createElement('input');
                        urlInput.type = 'text';
                        urlInput.className = 'regular-text';
                        urlInput.value = site.url;
                        urlInput.placeholder = 'https://example.com/';

                        var labelInput = document.createElement('input');
                        labelInput.type = 'text';
                        labelInput.className = 'regular-text';
                        labelInput.value = site.label;
                        labelInput.placeholder = '<?php echo esc_js( __( 'Label (e.g., Main Site)', 'beseo' ) ); ?>';

                        var localBtn = document.createElement('button');
                        localBtn.type = 'button';
                        localBtn.className = 'be-schema-sites-check';
                        localBtn.textContent = '<?php echo esc_js( __( 'Local', 'beseo' ) ); ?>';
                        setCheckButtonState(localBtn, site.localStatus);

                        var remoteBtn = document.createElement('button');
                        remoteBtn.type = 'button';
                        remoteBtn.className = 'be-schema-sites-check';
                        remoteBtn.textContent = '<?php echo esc_js( __( 'Remote', 'beseo' ) ); ?>';
                        setCheckButtonState(remoteBtn, site.remoteStatus);

                        var autoLabel = document.createElement('label');
                        var autoInput = document.createElement('input');
                        autoInput.type = 'checkbox';
                        autoInput.checked = !!site.autoCheck;
                        autoLabel.appendChild(autoInput);
                        autoLabel.appendChild(document.createTextNode(' <?php echo esc_js( __( 'Auto-check', 'beseo' ) ); ?>'));

                        var removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'button button-secondary';
                        removeBtn.textContent = '<?php echo esc_js( __( 'Remove', 'beseo' ) ); ?>';

                        saveBtn.addEventListener('click', function() {
                            var newUrl = urlInput.value.trim();
                            var newLabel = labelInput.value.trim();
                            if (!newUrl) {
                                setSitesStatus('<?php echo esc_js( __( 'Enter a URL.', 'beseo' ) ); ?>');
                                return;
                            }
                            if (!isHttpUrl(newUrl)) {
                                setSitesStatus('<?php echo esc_js( __( 'Use http/https URLs only.', 'beseo' ) ); ?>');
                                return;
                            }
                            if (!newLabel) {
                                try {
                                    var parsedLabel = new URL(newUrl);
                                    newLabel = parsedLabel.hostname.replace(/^www\\./i, '');
                                } catch (e) {
                                    newLabel = '<?php echo esc_js( __( 'Website', 'beseo' ) ); ?>';
                                }
                                labelInput.value = newLabel;
                            }
                            var urlChanged = newUrl !== site.url;
                            site.url = newUrl;
                            site.label = newLabel;
                            if (urlChanged) {
                                site.localStatus = 'unknown';
                                site.remoteStatus = 'unknown';
                            }
                            saveSites();
                            renderSites();
                            setSitesStatus('<?php echo esc_js( __( 'Website saved.', 'beseo' ) ); ?>');
                        });

                        localBtn.addEventListener('click', function() {
                            runSiteCheck(site, 'local');
                        });
                        remoteBtn.addEventListener('click', function() {
                            runSiteCheck(site, 'remote');
                        });
                        autoInput.addEventListener('change', function() {
                            site.autoCheck = autoInput.checked;
                            saveSites();
                            renderSites();
                            if (site.autoCheck) {
                                runSiteCheck(site, 'remote').then(function() {
                                    return runSiteCheck(site, 'local');
                                });
                            }
                        });
                        removeBtn.addEventListener('click', function() {
                            sites.splice(idx, 1);
                            saveSites();
                            renderSites();
                        });

                        row.appendChild(saveBtn);
                        row.appendChild(urlInput);
                        row.appendChild(labelInput);
                        row.appendChild(localBtn);
                        row.appendChild(remoteBtn);
                        row.appendChild(autoLabel);
                        row.appendChild(removeBtn);
                        li.appendChild(row);
                        sitesList.appendChild(li);
                    });
                }

                function runAutoCheck() {
                    if (!sites.length) {
                        return;
                    }
                    var chain = Promise.resolve();
                    sites.forEach(function(site) {
                        if (!site.autoCheck) {
                            return;
                        }
                        chain = chain.then(function() {
                            return runSiteCheck(site, 'remote');
                        }).then(function() {
                            return runSiteCheck(site, 'local');
                        });
                    });
                }

                document.addEventListener('click', function(event) {
                    var target = event && event.target ? event.target : null;
                    if (!target) {
                        return;
                    }
                    var addButton = target.closest('#be-schema-sites-add');
                    if (addButton) {
                        event.preventDefault();
                        var sitesLabel = document.getElementById('be-schema-sites-label');
                        var sitesUrl = document.getElementById('be-schema-sites-url');
                        if (!sitesLabel || !sitesUrl) {
                            return;
                        }
                        var label = sitesLabel.value.trim();
                        var url = sitesUrl.value.trim();
                        if (!url) {
                            setSitesStatus('<?php echo esc_js( __( 'Enter a URL.', 'beseo' ) ); ?>');
                            return;
                        }
                        if (!isHttpUrl(url)) {
                            setSitesStatus('<?php echo esc_js( __( 'Use http/https URLs only.', 'beseo' ) ); ?>');
                            return;
                        }
                        if (!label) {
                            try {
                                var parsed = new URL(url);
                                label = parsed.hostname.replace(/^www\\./i, '');
                            } catch (e) {
                                label = '<?php echo esc_js( __( 'Website', 'beseo' ) ); ?>';
                            }
                        }
                        sites.unshift({ label: label, url: url, localStatus: 'unknown', remoteStatus: 'unknown', autoCheck: false });
                        saveSites();
                        renderSites();
                        var sitesList = document.getElementById('be-schema-sites-list');
                        if (sitesList && typeof sitesList.scrollIntoView === 'function') {
                            sitesList.scrollIntoView({ block: 'start' });
                        }
                        sitesLabel.value = '';
                        sitesUrl.value = '';
                        setSitesStatus('<?php echo esc_js( __( 'Website saved.', 'beseo' ) ); ?>');
                        return;
                    }
                    var localButton = target.closest('#be-schema-sites-local');
                    if (localButton) {
                        runInputCheck('local', localButton);
                        return;
                    }
                    var remoteButton = target.closest('#be-schema-sites-remote');
                    if (remoteButton) {
                        runInputCheck('remote', remoteButton);
                    }
                });

                loadSites();
                renderSites();
                runAutoCheck();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initSitesList);
            } else {
                initSitesList();
            }
        })();
    </script>
    <?php
}
