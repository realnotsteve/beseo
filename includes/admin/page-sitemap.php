<?php
/**
 * Sitemap admin page with generator.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Build sitemap entries (home, posts/pages, archives) in a normalized array.
 *
 * @param array $args Parameters controlling inclusion and crawl hints.
 * @return array{entries: array<int,array>, latest_lastmod: string}
 */
if ( ! function_exists( 'be_schema_engine_build_sitemap_entries' ) ) {
    function be_schema_engine_build_sitemap_entries( $args ) {
        $defaults = array(
            'include_home'             => false,
            'selected_types'           => array(),
            'exclude_ids'              => array(),
            'include_lastmod'          => true,
            'changefreq_home'          => 'weekly',
            'changefreq_posts'         => 'weekly',
            'changefreq_pages'         => 'weekly',
            'changefreq_archive_now'   => 'weekly',
            'changefreq_archive_old'   => 'monthly',
            'priority_home'            => 8,
            'priority_posts'           => 5,
            'priority_posts_min'       => 3,
            'priority_pages'           => 5,
            'priority_archives'        => 4,
        );
        $args = wp_parse_args( $args, $defaults );

        $entries          = array();
        $latest_lastmod   = '';
        $track_lastmod_ts = static function( $lastmod_str, $current_latest ) {
            if ( ! $lastmod_str ) {
                return $current_latest;
            }
            $ts = strtotime( $lastmod_str );
            if ( false === $ts ) {
                return $current_latest;
            }
            $current_ts = $current_latest ? strtotime( $current_latest ) : 0;
            if ( ! $current_ts || $ts > $current_ts ) {
                return gmdate( 'c', $ts );
            }
            return $current_latest;
        };

        if ( $args['include_home'] ) {
            $home_lastmod = gmdate( 'c', time() );
            $home_loc     = home_url( '/' );
            $entries[] = array(
                'loc'        => $home_loc,
                'lastmod'    => $home_lastmod,
                'changefreq' => $args['changefreq_home'],
                'priority'   => number_format( $args['priority_home'] / 10, 1, '.', '' ),
            );
            $latest_lastmod = $track_lastmod_ts( $home_lastmod, $latest_lastmod );
        }

        // Collect posts/pages in pages to avoid memory spikes.
        if ( ! empty( $args['selected_types'] ) ) {
            $archives           = array();
            $current_year_month = gmdate( 'Y-m', time() );
            $posts_per_page     = 200;
            $paged              = 1;
            do {
                $query = new WP_Query(
                    array(
                        'post_type'              => $args['selected_types'],
                        'post_status'            => 'publish',
                        'posts_per_page'         => $posts_per_page,
                        'paged'                  => $paged,
                        'orderby'                => 'modified',
                        'order'                  => 'DESC',
                        'post__not_in'           => $args['exclude_ids'],
                        'fields'                 => 'ids',
                        'no_found_rows'          => true,
                        'update_post_term_cache' => false,
                        'update_post_meta_cache' => false,
                    )
                );

                if ( ! $query->have_posts() ) {
                    break;
                }

                foreach ( $query->posts as $post_id ) {
                    $post_item = get_post( $post_id );
                    if ( ! $post_item || 'publish' !== $post_item->post_status ) {
                        continue;
                    }
                    $lastmod = $post_item->post_modified_gmt ? $post_item->post_modified_gmt : $post_item->post_modified;
                    if ( ! $lastmod ) {
                        $lastmod = $post_item->post_date_gmt ? $post_item->post_date_gmt : $post_item->post_date;
                    }
                    $is_page            = ( 'page' === $post_item->post_type );
                    $entry_freq         = $is_page ? $args['changefreq_pages'] : $args['changefreq_posts'];
                    $entry_priority_raw = $is_page ? $args['priority_pages'] : max( $args['priority_posts'], $args['priority_posts_min'] );
                    $entry_lastmod      = $args['include_lastmod'] ? gmdate( 'c', strtotime( $lastmod ? $lastmod : 'now' ) ) : '';
                    $entry_loc          = get_permalink( $post_item );
                    $entries[]          = array(
                        'loc'        => $entry_loc,
                        'lastmod'    => $entry_lastmod,
                        'changefreq' => $entry_freq,
                        'priority'   => number_format( $entry_priority_raw / 10, 1, '.', '' ),
                    );

                    $latest_lastmod = $track_lastmod_ts( $entry_lastmod, $latest_lastmod );

                    $ym = gmdate( 'Y-m', strtotime( $lastmod ? $lastmod : 'now' ) );
                    if ( ! isset( $archives[ $ym ] ) ) {
                        $archives[ $ym ] = array(
                            'loc'     => get_month_link( (int) substr( $ym, 0, 4 ), (int) substr( $ym, 5, 2 ) ),
                            'lastmod' => gmdate( 'c', strtotime( $lastmod ? $lastmod : 'now' ) ),
                            'current' => ( $ym === $current_year_month ),
                        );
                    }
                }

                $paged++;
            } while ( true );
            wp_reset_postdata();

            foreach ( $archives as $archive_entry ) {
                $entry_lastmod    = $args['include_lastmod'] ? $archive_entry['lastmod'] : '';
                $archive_loc      = $archive_entry['loc'];
                $entries[]        = array(
                    'loc'        => $archive_loc,
                    'lastmod'    => $entry_lastmod,
                    'changefreq' => $archive_entry['current'] ? $args['changefreq_archive_now'] : $args['changefreq_archive_old'],
                    'priority'   => number_format( $args['priority_archives'] / 10, 1, '.', '' ),
                );
                $latest_lastmod = $track_lastmod_ts( $entry_lastmod, $latest_lastmod );
            }
        }

        return array(
            'entries'         => $entries,
            'latest_lastmod'  => $latest_lastmod,
        );
    }
}

/**
 * Render XML sitemaps (chunked) from entries.
 *
 * @param array $entries
 * @param int   $links_per_page
 * @param bool  $include_lastmod
 * @return array{files: array<int,array{name:string,xml:string,lastmod:string}>, first_xml: string}
 */
if ( ! function_exists( 'be_schema_engine_render_xml_sitemaps' ) ) {
    function be_schema_engine_render_xml_sitemaps( $entries, $links_per_page, $include_lastmod ) {
        $chunks        = array_chunk( $entries, $links_per_page );
        $sitemap_files = array();
        $xml_output    = '';

        foreach ( $chunks as $i => $chunk ) {
            $xml          = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            $xml         .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            $chunk_lastmod = '';
            foreach ( $chunk as $entry ) {
                $loc        = isset( $entry['loc'] ) ? $entry['loc'] : '';
                $lastmod    = isset( $entry['lastmod'] ) ? $entry['lastmod'] : '';
                $changefreq = isset( $entry['changefreq'] ) ? $entry['changefreq'] : '';
                $priority   = isset( $entry['priority'] ) ? $entry['priority'] : '';
                if ( $include_lastmod && $lastmod ) {
                    if ( empty( $chunk_lastmod ) || strtotime( $lastmod ) > strtotime( $chunk_lastmod ) ) {
                        $chunk_lastmod = $lastmod;
                    }
                }
                $xml       .= "  <url>\n";
                $xml       .= '    <loc>' . esc_url_raw( $loc ) . "</loc>\n";
                if ( $include_lastmod && $lastmod ) {
                    $xml .= '    <lastmod>' . esc_html( $lastmod ) . "</lastmod>\n";
                }
                if ( $changefreq ) {
                    $xml .= '    <changefreq>' . esc_html( $changefreq ) . "</changefreq>\n";
                }
                if ( $priority ) {
                    $xml .= '    <priority>' . esc_html( $priority ) . "</priority>\n";
                }
                $xml .= "  </url>\n";
            }
            $xml .= "</urlset>\n";

            $filename = ( 0 === $i ) ? 'sitemap.xml' : 'sitemap-' . ( $i + 1 ) . '.xml';
            $sitemap_files[] = array(
                'name'    => $filename,
                'xml'     => $xml,
                'lastmod' => $chunk_lastmod ? $chunk_lastmod : '',
            );
            if ( 0 === $i ) {
                $xml_output = $xml;
            }
        }

        return array(
            'files'     => $sitemap_files,
            'first_xml' => $xml_output,
        );
    }
}

/**
 * Render an HTML sitemap listing entries.
 *
 * @param array $entries
 * @param bool  $include_lastmod
 * @return string
 */
if ( ! function_exists( 'be_schema_engine_render_html_sitemap' ) ) {
    function be_schema_engine_render_html_sitemap( $entries, $include_lastmod ) {
        $html  = "<!doctype html>\n<html>\n<head>\n";
        $html .= "  <meta charset=\"utf-8\">\n";
        $html .= '  <title>' . esc_html( get_bloginfo( 'name' ) ) . " – Sitemap</title>\n";
        $html .= "</head>\n<body>\n";
        $html .= '  <h1>' . esc_html( get_bloginfo( 'name' ) ) . " – Sitemap</h1>\n";
        $html .= "  <ul>\n";
        foreach ( $entries as $entry ) {
            $loc        = isset( $entry['loc'] ) ? $entry['loc'] : '';
            $lastmod    = isset( $entry['lastmod'] ) ? $entry['lastmod'] : '';
            $changefreq = isset( $entry['changefreq'] ) ? $entry['changefreq'] : '';
            $priority   = isset( $entry['priority'] ) ? $entry['priority'] : '';
            $html   .= '    <li><a href="' . esc_url( $loc ) . '">' . esc_html( $loc ) . '</a>';
            if ( $include_lastmod && $lastmod ) {
                $html .= ' <small>(' . esc_html( $lastmod ) . ')</small>';
            }
            if ( $changefreq ) {
                $html .= ' <small>' . sprintf( esc_html__( 'Changefreq: %s', 'beseo' ), esc_html( $changefreq ) ) . '</small>';
            }
            if ( $priority ) {
                $html .= ' <small>' . sprintf( esc_html__( 'Priority: %s', 'beseo' ), esc_html( $priority ) ) . '</small>';
            }
            $html .= "</li>\n";
        }
        $html .= "  </ul>\n</body>\n</html>\n";
        return $html;
    }
}

/**
 * Render sitemap index XML from written sitemap metadata and optional HTML sitemap.
 *
 * @param array  $sitemap_written_meta Array of arrays with url/lastmod.
 * @param string $written_html_url
 * @param string $latest_lastmod Latest known lastmod from entries.
 * @return array{xml: string, entries: int}
 */
if ( ! function_exists( 'be_schema_engine_render_sitemap_index' ) ) {
    function be_schema_engine_render_sitemap_index( $sitemap_written_meta, $written_html_url, $latest_lastmod ) {
        $index_xml     = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $index_xml    .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $index_entries = 0;
        foreach ( $sitemap_written_meta as $meta_item ) {
            $index_entries++;
            $index_xml .= "  <sitemap>\n";
            $index_xml .= '    <loc>' . esc_url_raw( $meta_item['url'] ) . "</loc>\n";
            if ( ! empty( $meta_item['lastmod'] ) ) {
                $index_xml .= '    <lastmod>' . esc_html( $meta_item['lastmod'] ) . "</lastmod>\n";
            }
            $index_xml .= "  </sitemap>\n";
        }
        if ( $written_html_url ) {
            $index_entries++;
            $index_xml .= "  <sitemap>\n";
            $index_xml .= '    <loc>' . esc_url_raw( $written_html_url ) . "</loc>\n";
            if ( $latest_lastmod ) {
                $index_xml .= '    <lastmod>' . esc_html( $latest_lastmod ) . "</lastmod>\n";
            }
            $index_xml .= "  </sitemap>\n";
        }
        $index_xml .= "</sitemapindex>\n";

        return array(
            'xml'     => $index_xml,
            'entries' => $index_entries,
        );
    }
}

/**
 * Build image/video sitemap files.
 *
 * @param array $args Args for media collection.
 * @return array Array of sitemap file defs to merge with main sitemaps.
 */
if ( ! function_exists( 'be_schema_engine_build_media_sitemaps' ) ) {
    function be_schema_engine_build_media_sitemaps( $args ) {
        $defaults = array(
            'include_images'  => false,
            'include_videos'  => false,
            'selected_types'  => array(),
            'exclude_ids'     => array(),
            'include_lastmod' => true,
            'links_per_page'  => 100,
        );
        $args = wp_parse_args( $args, $defaults );
        $files = array();

        if ( ! $args['include_images'] && ! $args['include_videos'] ) {
            return $files;
        }

        $posts_per_page = 200;
        $paged          = 1;
        $items          = array();
        do {
            $query = new WP_Query(
                array(
                    'post_type'              => $args['selected_types'],
                    'post_status'            => 'publish',
                    'posts_per_page'         => $posts_per_page,
                    'paged'                  => $paged,
                    'orderby'                => 'modified',
                    'order'                  => 'DESC',
                    'post__not_in'           => $args['exclude_ids'],
                    'fields'                 => 'ids',
                    'no_found_rows'          => true,
                    'update_post_term_cache' => false,
                    'update_post_meta_cache' => false,
                )
            );
            if ( ! $query->have_posts() ) {
                break;
            }
            foreach ( $query->posts as $post_id ) {
                $post_item = get_post( $post_id );
                if ( ! $post_item || 'publish' !== $post_item->post_status ) {
                    continue;
                }
                $lastmod = $post_item->post_modified_gmt ? $post_item->post_modified_gmt : $post_item->post_modified;
                if ( ! $lastmod ) {
                    $lastmod = $post_item->post_date_gmt ? $post_item->post_date_gmt : $post_item->post_date;
                }
                $lastmod_c = $args['include_lastmod'] ? gmdate( 'c', strtotime( $lastmod ? $lastmod : 'now' ) ) : '';
                $thumb_id  = get_post_thumbnail_id( $post_id );
                $thumb_url = $thumb_id ? wp_get_attachment_url( $thumb_id ) : '';
                $videos    = array();
                if ( $args['include_videos'] ) {
                    $video_children = get_children(
                        array(
                            'post_type'      => 'attachment',
                            'post_parent'    => $post_id,
                            'post_mime_type' => 'video',
                            'numberposts'    => 3,
                            'fields'         => 'ids',
                        )
                    );
                    foreach ( $video_children as $vid_id ) {
                        $vid_url = wp_get_attachment_url( $vid_id );
                        if ( $vid_url ) {
                            $videos[] = $vid_url;
                        }
                    }
                }

                $items[] = array(
                    'loc'      => get_permalink( $post_item ),
                    'lastmod'  => $lastmod_c,
                    'thumb'    => $thumb_url,
                    'videos'   => $videos,
                    'title'    => get_the_title( $post_item ),
                    'summary'  => wp_strip_all_tags( $post_item->post_excerpt ? $post_item->post_excerpt : wp_trim_words( wp_strip_all_tags( $post_item->post_content ), 30, '…' ) ),
                );
            }
            $paged++;
        } while ( true );
        wp_reset_postdata();

        if ( $args['include_images'] ) {
            $chunks = array_chunk( $items, $args['links_per_page'] );
            foreach ( $chunks as $i => $chunk ) {
                $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
                $chunk_lastmod = '';
                foreach ( $chunk as $entry ) {
                    if ( empty( $entry['thumb'] ) ) {
                        continue;
                    }
                    $lastmod = isset( $entry['lastmod'] ) ? $entry['lastmod'] : '';
                    if ( $args['include_lastmod'] && $lastmod ) {
                        if ( empty( $chunk_lastmod ) || strtotime( $lastmod ) > strtotime( $chunk_lastmod ) ) {
                            $chunk_lastmod = $lastmod;
                        }
                    }
                    $xml .= "  <url>\n";
                    $xml .= '    <loc>' . esc_url_raw( $entry['loc'] ) . "</loc>\n";
                    if ( $args['include_lastmod'] && $lastmod ) {
                        $xml .= '    <lastmod>' . esc_html( $lastmod ) . "</lastmod>\n";
                    }
                    $xml .= "    <image:image>\n";
                    $xml .= '      <image:loc>' . esc_url_raw( $entry['thumb'] ) . "</image:loc>\n";
                    if ( ! empty( $entry['title'] ) ) {
                        $xml .= '      <image:title>' . esc_html( $entry['title'] ) . "</image:title>\n";
                    }
                    $xml .= "    </image:image>\n";
                    $xml .= "  </url>\n";
                }
                $xml .= "</urlset>\n";
                $files[] = array(
                    'name'    => ( 0 === $i ) ? 'image-sitemap.xml' : 'image-sitemap-' . ( $i + 1 ) . '.xml',
                    'xml'     => $xml,
                    'lastmod' => $chunk_lastmod,
                );
            }
        }

        if ( $args['include_videos'] ) {
            $chunks = array_chunk( $items, $args['links_per_page'] );
            foreach ( $chunks as $i => $chunk ) {
                $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";
                $chunk_lastmod = '';
                foreach ( $chunk as $entry ) {
                    if ( empty( $entry['videos'] ) ) {
                        continue;
                    }
                    $lastmod = isset( $entry['lastmod'] ) ? $entry['lastmod'] : '';
                    if ( $args['include_lastmod'] && $lastmod ) {
                        if ( empty( $chunk_lastmod ) || strtotime( $lastmod ) > strtotime( $chunk_lastmod ) ) {
                            $chunk_lastmod = $lastmod;
                        }
                    }
                    foreach ( $entry['videos'] as $video_url ) {
                        $xml .= "  <url>\n";
                        $xml .= '    <loc>' . esc_url_raw( $entry['loc'] ) . "</loc>\n";
                        if ( $args['include_lastmod'] && $lastmod ) {
                            $xml .= '    <lastmod>' . esc_html( $lastmod ) . "</lastmod>\n";
                        }
                        $xml .= "    <video:video>\n";
                        if ( ! empty( $entry['thumb'] ) ) {
                            $xml .= '      <video:thumbnail_loc>' . esc_url_raw( $entry['thumb'] ) . "</video:thumbnail_loc>\n";
                        }
                        $xml .= '      <video:content_loc>' . esc_url_raw( $video_url ) . "</video:content_loc>\n";
                        if ( ! empty( $entry['title'] ) ) {
                            $xml .= '      <video:title>' . esc_html( $entry['title'] ) . "</video:title>\n";
                        }
                        if ( ! empty( $entry['summary'] ) ) {
                            $xml .= '      <video:description>' . esc_html( $entry['summary'] ) . "</video:description>\n";
                        }
                        $xml .= "    </video:video>\n";
                        $xml .= "  </url>\n";
                    }
                }
                $xml .= "</urlset>\n";
                $files[] = array(
                    'name'    => ( 0 === $i ) ? 'video-sitemap.xml' : 'video-sitemap-' . ( $i + 1 ) . '.xml',
                    'xml'     => $xml,
                    'lastmod' => $chunk_lastmod,
                );
            }
        }

        return $files;
    }
}

/**
 * Ping IndexNow endpoints.
 *
 * @param string $sitemap_url URL to ping.
 * @param string $indexnow_key IndexNow key.
 * @return array Results array of endpoint/status/message.
 */
if ( ! function_exists( 'be_schema_engine_ping_indexnow' ) ) {
    function be_schema_engine_ping_indexnow( $sitemap_url, $indexnow_key ) {
        $results = array();
        $endpoints = array(
            'api.indexnow.org',
            'www.bing.com',
            'bing.com',
            'yandex.com',
            'searchadvisor.naver.com',
            'naver.com',
            'seznam.cz',
        );
        foreach ( $endpoints as $endpoint ) {
            $ping_url = 'https://' . $endpoint . '/indexnow';
            $query    = add_query_arg(
                array(
                    'url' => $sitemap_url,
                    'key' => $indexnow_key,
                ),
                $ping_url
            );
            $resp = wp_remote_get(
                $query,
                array(
                    'timeout'     => 6,
                    'redirection' => 3,
                )
            );
            if ( is_wp_error( $resp ) ) {
                $results[] = array(
                    'endpoint' => $endpoint,
                    'status'   => 'error',
                    'message'  => $resp->get_error_message(),
                );
            } else {
                $code = (int) wp_remote_retrieve_response_code( $resp );
                $results[] = array(
                    'endpoint' => $endpoint,
                    'status'   => ( $code >= 200 && $code < 300 ) ? 'ok' : 'warn',
                    'message'  => sprintf( __( 'HTTP %d', 'beseo' ), $code ),
                );
            }
        }
        return $results;
    }
}

/**
 * Ping Google with sitemap URLs.
 *
 * @param array $targets URLs to ping.
 * @return array Results array of target/status/message.
 */
if ( ! function_exists( 'be_schema_engine_ping_google' ) ) {
    function be_schema_engine_ping_google( $targets ) {
        $results = array();
        foreach ( $targets as $g_url ) {
            $ping_url = add_query_arg(
                array( 'sitemap' => rawurlencode( $g_url ) ),
                'https://www.google.com/ping'
            );
            $resp = wp_remote_get(
                $ping_url,
                array(
                    'timeout'     => 6,
                    'redirection' => 3,
                )
            );
            if ( is_wp_error( $resp ) ) {
                $results[] = array(
                    'target'  => $g_url,
                    'status'  => 'error',
                    'message' => $resp->get_error_message(),
                );
            } else {
                $code = (int) wp_remote_retrieve_response_code( $resp );
                $results[] = array(
                    'target'  => $g_url,
                    'status'  => ( $code >= 200 && $code < 300 ) ? 'ok' : 'warn',
                    'message' => sprintf( __( 'HTTP %d', 'beseo' ), $code ),
                );
            }
        }
        return $results;
    }
}

/**
 * Normalize and process a sitemap generation request.
 *
 * @param array $request Raw request (typically $_POST).
 * @return array
 */
if ( ! function_exists( 'be_schema_engine_process_sitemap_request' ) ) {
    function be_schema_engine_process_sitemap_request( $request ) {
        $result = array(
            'success'            => false,
            'notice'             => '',
            'notice_class'       => 'updated',
            'xml_output'         => '',
            'html_output'        => '',
            'written_paths'      => array(),
            'written_urls'       => array(),
            'written_path'       => '',
            'written_url'        => '',
            'written_index_path' => '',
            'written_index_url'  => '',
            'written_html_path'  => '',
            'written_html_url'   => '',
            'indexnow_results'   => array(),
            'google_results'     => array(),
            'preview_mode'       => '',
        );

        $include_posts  = isset( $request['be_schema_sitemap_include_posts'] );
        $include_pages  = isset( $request['be_schema_sitemap_include_pages'] );
        $selected_types = array();
        if ( $include_posts ) {
            $selected_types[] = 'post';
        }
        if ( $include_pages ) {
            $selected_types[] = 'page';
        }
        $include_html          = isset( $request['be_schema_sitemap_include_html'] );
        $include_indexnow      = isset( $request['be_schema_sitemap_include_indexnow'] );
        $indexnow_key          = isset( $request['be_schema_sitemap_indexnow_key'] ) ? sanitize_text_field( wp_unslash( $request['be_schema_sitemap_indexnow_key'] ) ) : '';
        $notify_google_main    = isset( $request['be_schema_sitemap_notify_google_main'] );
        $notify_google_all     = isset( $request['be_schema_sitemap_notify_google_all'] );
        $include_home          = isset( $request['be_schema_sitemap_include_home'] );
        $include_lastmod       = isset( $request['be_schema_sitemap_include_lastmod'] );
        $links_per_page        = isset( $request['be_schema_sitemap_links_per_page'] ) ? max( 1, (int) $request['be_schema_sitemap_links_per_page'] ) : 100;
        $include_images        = isset( $request['be_schema_sitemap_include_images'] );
        $include_videos        = isset( $request['be_schema_sitemap_include_videos'] );
        $preview_mode_request  = isset( $request['be_schema_sitemap_preview_mode'] ) ? sanitize_key( wp_unslash( $request['be_schema_sitemap_preview_mode'] ) ) : '';

        $changefreq_options     = array( 'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never' );
        $changefreq_home        = 'weekly';
        $changefreq_posts       = 'weekly';
        $changefreq_pages       = 'weekly';
        $changefreq_archive_now = 'weekly';
        $changefreq_archive_old = 'monthly';
        $priority_home          = 8;
        $priority_posts         = 5;
        $priority_posts_min     = 3;
        $priority_pages         = 5;
        $priority_archives      = 4;
        $exclude_ids_raw        = isset( $request['be_schema_sitemap_exclude_ids'] ) ? trim( (string) wp_unslash( $request['be_schema_sitemap_exclude_ids'] ) ) : '';
        $exclude_ids            = array();

        $posted_changefreq_home        = isset( $request['be_schema_sitemap_changefreq_home'] ) ? sanitize_key( wp_unslash( $request['be_schema_sitemap_changefreq_home'] ) ) : '';
        $posted_changefreq_posts       = isset( $request['be_schema_sitemap_changefreq_posts'] ) ? sanitize_key( wp_unslash( $request['be_schema_sitemap_changefreq_posts'] ) ) : '';
        $posted_changefreq_pages       = isset( $request['be_schema_sitemap_changefreq_pages'] ) ? sanitize_key( wp_unslash( $request['be_schema_sitemap_changefreq_pages'] ) ) : '';
        $posted_changefreq_archive_now = isset( $request['be_schema_sitemap_changefreq_archive_now'] ) ? sanitize_key( wp_unslash( $request['be_schema_sitemap_changefreq_archive_now'] ) ) : '';
        $posted_changefreq_archive_old = isset( $request['be_schema_sitemap_changefreq_archive_old'] ) ? sanitize_key( wp_unslash( $request['be_schema_sitemap_changefreq_archive_old'] ) ) : '';
        $posted_priority_home          = isset( $request['be_schema_sitemap_priority_home'] ) ? (int) $request['be_schema_sitemap_priority_home'] : null;
        $posted_priority_posts         = isset( $request['be_schema_sitemap_priority_posts'] ) ? (int) $request['be_schema_sitemap_priority_posts'] : null;
        $posted_priority_posts_min     = isset( $request['be_schema_sitemap_priority_posts_min'] ) ? (int) $request['be_schema_sitemap_priority_posts_min'] : null;
        $posted_priority_pages         = isset( $request['be_schema_sitemap_priority_pages'] ) ? (int) $request['be_schema_sitemap_priority_pages'] : null;
        $posted_priority_archives      = isset( $request['be_schema_sitemap_priority_archives'] ) ? (int) $request['be_schema_sitemap_priority_archives'] : null;

        if ( in_array( $posted_changefreq_home, $changefreq_options, true ) ) {
            $changefreq_home = $posted_changefreq_home;
        }
        if ( in_array( $posted_changefreq_posts, $changefreq_options, true ) ) {
            $changefreq_posts = $posted_changefreq_posts;
        }
        if ( in_array( $posted_changefreq_pages, $changefreq_options, true ) ) {
            $changefreq_pages = $posted_changefreq_pages;
        }
        if ( in_array( $posted_changefreq_archive_now, $changefreq_options, true ) ) {
            $changefreq_archive_now = $posted_changefreq_archive_now;
        }
        if ( in_array( $posted_changefreq_archive_old, $changefreq_options, true ) ) {
            $changefreq_archive_old = $posted_changefreq_archive_old;
        }

        $sanitize_priority = static function( $value, $default ) {
            $value = (int) $value;
            if ( $value < 1 || $value > 10 ) {
                return $default;
            }
            return $value;
        };
        if ( null !== $posted_priority_home ) {
            $priority_home = $sanitize_priority( $posted_priority_home, $priority_home );
        }
        if ( null !== $posted_priority_posts ) {
            $priority_posts = $sanitize_priority( $posted_priority_posts, $priority_posts );
        }
        if ( null !== $posted_priority_posts_min ) {
            $priority_posts_min = $sanitize_priority( $posted_priority_posts_min, $priority_posts_min );
        }
        if ( null !== $posted_priority_pages ) {
            $priority_pages = $sanitize_priority( $posted_priority_pages, $priority_pages );
        }
        if ( null !== $posted_priority_archives ) {
            $priority_archives = $sanitize_priority( $posted_priority_archives, $priority_archives );
        }

        if ( $exclude_ids_raw ) {
            $exclude_ids = array_filter(
                array_map(
                    'absint',
                    explode( ',', $exclude_ids_raw )
                )
            );
        }

        if ( empty( $selected_types ) && ! $include_home ) {
            $result['notice']       = __( 'Select at least one content type (home, posts, or pages).', 'beseo' );
            $result['notice_class'] = 'error';
            $result['preview_mode'] = $preview_mode_request;
            return $result;
        }

        $entries_data = be_schema_engine_build_sitemap_entries(
            array(
                'include_home'           => $include_home,
                'selected_types'         => $selected_types,
                'exclude_ids'            => $exclude_ids,
                'include_lastmod'        => $include_lastmod,
                'changefreq_home'        => $changefreq_home,
                'changefreq_posts'       => $changefreq_posts,
                'changefreq_pages'       => $changefreq_pages,
                'changefreq_archive_now' => $changefreq_archive_now,
                'changefreq_archive_old' => $changefreq_archive_old,
                'priority_home'          => $priority_home,
                'priority_posts'         => $priority_posts,
                'priority_posts_min'     => $priority_posts_min,
                'priority_pages'         => $priority_pages,
                'priority_archives'      => $priority_archives,
            )
        );

        $entries        = $entries_data['entries'];
        $latest_lastmod = $entries_data['latest_lastmod'];

        $rendered          = be_schema_engine_render_xml_sitemaps( $entries, $links_per_page, $include_lastmod );
        $sitemap_files     = $rendered['files'];
        $result['xml_output'] = $rendered['first_xml'];

        if ( $include_html ) {
            $result['html_output'] = be_schema_engine_render_html_sitemap( $entries, $include_lastmod );
        }

        $media_files = be_schema_engine_build_media_sitemaps(
            array(
                'include_images'  => $include_images,
                'include_videos'  => $include_videos,
                'selected_types'  => $selected_types,
                'exclude_ids'     => $exclude_ids,
                'include_lastmod' => $include_lastmod,
                'links_per_page'  => $links_per_page,
            )
        );
        if ( $media_files ) {
            $sitemap_files = array_merge( $sitemap_files, $media_files );
        }

        $write_result                   = be_schema_engine_write_sitemaps( $sitemap_files, $result['html_output'], $include_html, $latest_lastmod );
        $result['success']              = $write_result['success'];
        $result['notice']               = $write_result['notice'];
        $result['notice_class']         = $write_result['notice_class'];
        $result['written_paths']        = $write_result['written_paths'];
        $result['written_urls']         = $write_result['written_urls'];
        $result['written_path']         = $write_result['written_path'];
        $result['written_url']          = $write_result['written_url'];
        $result['written_index_path']   = $write_result['written_index_path'];
        $result['written_index_url']    = $write_result['written_index_url'];
        $result['written_html_path']    = $write_result['written_html_path'];
        $result['written_html_url']     = $write_result['written_html_url'];

        if ( $write_result['success'] ) {
            if ( $include_indexnow && $result['written_url'] ) {
                if ( empty( $indexnow_key ) ) {
                    $result['notice']       = __( 'Sitemap saved, but IndexNow key is required to notify search engines.', 'beseo' );
                    $result['notice_class'] = 'error';
                } else {
                    $result['indexnow_results'] = be_schema_engine_ping_indexnow( $result['written_url'], $indexnow_key );
                }
            }

            $google_targets = array();
            if ( $notify_google_main ) {
                if ( $result['written_index_url'] ) {
                    $google_targets[] = $result['written_index_url'];
                } elseif ( $result['written_url'] ) {
                    $google_targets[] = $result['written_url'];
                }
            }
            if ( $notify_google_all ) {
                if ( $result['written_index_url'] ) {
                    $google_targets[] = $result['written_index_url'];
                }
                if ( $result['written_urls'] ) {
                    $google_targets = array_merge( $google_targets, $result['written_urls'] );
                }
            }
            $google_targets = array_unique( array_filter( $google_targets ) );
            if ( $google_targets ) {
                $result['google_results'] = be_schema_engine_ping_google( $google_targets );
            }
        } elseif ( empty( $result['notice'] ) ) {
            // Ensure we report a clear error when writing failed unexpectedly.
            $result['notice']       = __( 'Could not create sitemap directory in uploads.', 'beseo' );
            $result['notice_class'] = 'error';
        }

        $result['preview_mode'] = $preview_mode_request;

        return $result;
    }
}

/**
 * Persist flash data and return the redirect URL.
 *
 * @param array $data Result from be_schema_engine_process_sitemap_request().
 * @return string Redirect URL with flash key appended.
 */
if ( ! function_exists( 'be_schema_engine_store_sitemap_flash' ) ) {
    function be_schema_engine_store_sitemap_flash( $data ) {
        $flash_key  = wp_generate_password( 12, false );
        $flash_data = array(
            'notice'             => isset( $data['notice'] ) ? $data['notice'] : '',
            'notice_class'       => isset( $data['notice_class'] ) ? $data['notice_class'] : 'updated',
            'xml_output'         => isset( $data['xml_output'] ) ? $data['xml_output'] : '',
            'html_output'        => isset( $data['html_output'] ) ? $data['html_output'] : '',
            'written_paths'      => isset( $data['written_paths'] ) ? $data['written_paths'] : array(),
            'written_urls'       => isset( $data['written_urls'] ) ? $data['written_urls'] : array(),
            'written_path'       => isset( $data['written_path'] ) ? $data['written_path'] : '',
            'written_url'        => isset( $data['written_url'] ) ? $data['written_url'] : '',
            'written_index_path' => isset( $data['written_index_path'] ) ? $data['written_index_path'] : '',
            'written_index_url'  => isset( $data['written_index_url'] ) ? $data['written_index_url'] : '',
            'written_html_path'  => isset( $data['written_html_path'] ) ? $data['written_html_path'] : '',
            'written_html_url'   => isset( $data['written_html_url'] ) ? $data['written_html_url'] : '',
            'indexnow_results'   => isset( $data['indexnow_results'] ) ? $data['indexnow_results'] : array(),
            'google_results'     => isset( $data['google_results'] ) ? $data['google_results'] : array(),
            'preview_mode'       => isset( $data['preview_mode'] ) ? $data['preview_mode'] : '',
        );
        set_transient( 'be_schema_sitemap_flash_' . $flash_key, $flash_data, 60 );
        return add_query_arg(
            array(
                'page'                => 'beseo-sitemap',
                'beseo_sitemap_flash' => $flash_key,
            ),
            admin_url( 'admin.php' )
        );
    }
}

/**
 * AJAX handler to generate sitemap without form resubmission warnings.
 */
function be_schema_engine_ajax_generate_sitemap() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Unauthorized', 'beseo' ) ), 403 );
    }
    check_ajax_referer( 'be_schema_generate_sitemap', 'be_schema_sitemap_nonce' );

    $result = be_schema_engine_process_sitemap_request( $_POST );

    if ( ! empty( $result['notice_class'] ) && 'error' === $result['notice_class'] && empty( $result['written_url'] ) ) {
        // Keep the immediate feedback in AJAX without redirect on basic validation errors.
        wp_send_json_error(
            array(
                'message' => ! empty( $result['notice'] ) ? $result['notice'] : __( 'Sitemap generation failed.', 'beseo' ),
            )
        );
    }

    $redirect_url = be_schema_engine_store_sitemap_flash( $result );
    wp_send_json_success(
        array(
            'redirect' => $redirect_url,
        )
    );
}
add_action( 'wp_ajax_be_schema_generate_sitemap', 'be_schema_engine_ajax_generate_sitemap' );

/**
 * Handle non-AJAX form submissions via admin-post to avoid resubmit prompts.
 */
function be_schema_engine_handle_sitemap_post() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized', 'beseo' ) );
    }
    check_admin_referer( 'be_schema_generate_sitemap', 'be_schema_sitemap_nonce' );

    $result       = be_schema_engine_process_sitemap_request( $_POST );
    $redirect_url = be_schema_engine_store_sitemap_flash( $result );

    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_be_schema_generate_sitemap', 'be_schema_engine_handle_sitemap_post' );

/**
 * Render preview UI (radios, select, code blocks) for sitemaps.
 *
 * @param string $xml_output       The first XML sitemap content.
 * @param array  $written_urls     List of generated sitemap URLs.
 * @param string $written_html_url HTML sitemap URL.
 * @param string $posted_mode      Requested preview mode (xml/html).
 * @param string $html_output      HTML sitemap content.
 */
if ( ! function_exists( 'be_schema_engine_render_sitemap_preview_block' ) ) {
    function be_schema_engine_render_sitemap_preview_block( $xml_output, $written_urls, $written_html_url, $posted_mode, $html_output ) {
        $has_xml   = ! empty( $xml_output ) || ! empty( $written_urls );
        $has_html  = ! empty( $written_html_url );
        $mode      = ( 'html' === $posted_mode && $has_html ) ? 'html' : 'xml';
        if ( ! $has_xml && $has_html ) {
            $mode = 'html';
        }
        $preview_source = '';
        $preview_lang   = 'xml';
        if ( 'html' === $mode && ! empty( $html_output ) ) {
            $preview_source = $html_output;
            $preview_lang   = 'html';
        } elseif ( ! empty( $xml_output ) ) {
            $preview_source = $xml_output;
        }
        $has_preview = ( '' !== $preview_source );
        $has_raw     = ! empty( $xml_output );
        $has_two     = $has_preview && $has_raw;

        ob_start();
        ?>
        <div class="beseo-sitemap-preview">
            <?php if ( $has_xml || $has_html ) : ?>
                <div style="margin-top:10px;">
                    <label style="margin-right:12px;">
                        <input type="radio" name="be_schema_sitemap_preview_mode" value="xml" <?php checked( 'xml', $mode ); ?> <?php disabled( ! $has_xml ); ?> />
                        <?php esc_html_e( 'XML sitemap', 'beseo' ); ?>
                    </label>
                    <label>
                        <input type="radio" name="be_schema_sitemap_preview_mode" value="html" <?php checked( 'html', $mode ); ?> <?php disabled( ! $has_html ); ?> />
                        <?php esc_html_e( 'HTML sitemap (preview)', 'beseo' ); ?>
                    </label>
                </div>

                <div class="beseo-sitemap-preview-grid<?php echo $has_two ? ' has-two' : ''; ?>" style="margin-top:10px;">
                    <div>
                        <?php if ( $written_urls ) : ?>
                            <div style="margin:10px 0 8px;">
                                <label for="be-schema-sitemap-iframe-select"><strong><?php esc_html_e( 'Choose sitemap file', 'beseo' ); ?></strong></label><br />
                                <select id="be-schema-sitemap-iframe-select" class="regular-text" style="max-width:420px;">
                                    <?php foreach ( $written_urls as $url_item ) : ?>
                                        <option value="<?php echo esc_url( $url_item ); ?>"><?php echo esc_html( $url_item ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ( $preview_source ) : ?>
                            <pre class="beseo-sitemap-code beseo-sitemap-code-left"><code id="be-schema-sitemap-preview-code" data-code-type="<?php echo esc_attr( $preview_lang ); ?>"><?php echo esc_html( $preview_source ); ?></code></pre>
                        <?php else : ?>
                            <p class="description" style="margin-top:8px;"><?php esc_html_e( 'Generate to preview your sitemap here.', 'beseo' ); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ( $xml_output ) : ?>
                        <div class="beseo-sitemap-raw" style="margin-top:10px;">
                            <pre class="beseo-sitemap-code beseo-sitemap-code-right"><code id="be-schema-sitemap-xml-code" data-code-type="xml"><?php echo esc_html( $xml_output ); ?></code></pre>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <p class="description" style="margin-top:8px;"><?php esc_html_e( 'Generate to preview your sitemap here.', 'beseo' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Write sitemap files (XML, optional HTML, index) and persist meta.
 *
 * @param array  $sitemap_files Array of files with name/xml/lastmod.
 * @param string $html_output   Optional HTML sitemap content.
 * @param bool   $include_html  Whether to write HTML sitemap.
 * @param string $latest_lastmod Latest lastmod across entries.
 * @return array {
 *   @type bool   $success
 *   @type string $notice
 *   @type string $notice_class
 *   @type array  $written_paths
 *   @type array  $written_urls
 *   @type string $written_path
 *   @type string $written_url
 *   @type string $written_index_path
 *   @type string $written_index_url
 *   @type string $written_html_path
 *   @type string $written_html_url
 * }
 */
if ( ! function_exists( 'be_schema_engine_write_sitemaps' ) ) {
    function be_schema_engine_write_sitemaps( $sitemap_files, $html_output, $include_html, $latest_lastmod ) {
        $result = array(
            'success'           => false,
            'notice'            => '',
            'notice_class'      => 'updated',
            'written_paths'     => array(),
            'written_urls'      => array(),
            'written_path'      => '',
            'written_url'       => '',
            'written_index_path'=> '',
            'written_index_url' => '',
            'written_html_path' => '',
            'written_html_url'  => '',
        );

        $upload_dir = wp_upload_dir();
        if ( empty( $upload_dir['basedir'] ) || empty( $upload_dir['baseurl'] ) ) {
            $result['notice']       = __( 'Uploads directory unavailable; cannot save sitemap.', 'beseo' );
            $result['notice_class'] = 'error';
            return $result;
        }

        $target_dir = trailingslashit( $upload_dir['basedir'] ) . 'beseo-sitemaps';
        if ( ! wp_mkdir_p( $target_dir ) ) {
            $result['notice']       = __( 'Could not create sitemap directory in uploads.', 'beseo' );
            $result['notice_class'] = 'error';
            return $result;
        }

        $written_urls         = array();
        $written_paths        = array();
        $written_index_path   = '';
        $written_index_url    = '';
        $written_html_path    = '';
        $written_html_url     = '';
        $sitemap_written_meta = array();

        foreach ( $sitemap_files as $file_def ) {
            $file_path = trailingslashit( $target_dir ) . $file_def['name'];
            $bytes     = file_put_contents( $file_path, $file_def['xml'] );
            if ( false === $bytes ) {
                $result['notice']       = __( 'Failed to write sitemap file. Check filesystem permissions.', 'beseo' );
                $result['notice_class'] = 'error';
                return $result;
            }
            $written_paths[] = $file_path;
            $written_urls[]  = trailingslashit( $upload_dir['baseurl'] ) . 'beseo-sitemaps/' . $file_def['name'];
            $sitemap_written_meta[] = array(
                'url'     => trailingslashit( $upload_dir['baseurl'] ) . 'beseo-sitemaps/' . $file_def['name'],
                'path'    => $file_path,
                'lastmod' => $file_def['lastmod'],
            );
        }

        $written_path = isset( $written_paths[0] ) ? $written_paths[0] : '';
        $written_url  = isset( $written_urls[0] ) ? $written_urls[0] : '';

        if ( $include_html && $html_output ) {
            $html_path  = trailingslashit( $target_dir ) . 'sitemap.html';
            $html_bytes = file_put_contents( $html_path, $html_output );
            if ( false === $html_bytes ) {
                $result['notice']       = __( 'XML sitemap saved, but failed to write HTML sitemap.', 'beseo' );
                $result['notice_class'] = 'error';
            } else {
                $written_html_path = $html_path;
                $written_html_url  = trailingslashit( $upload_dir['baseurl'] ) . 'beseo-sitemaps/sitemap.html';
            }
        }

        $index_render = be_schema_engine_render_sitemap_index( $sitemap_written_meta, $written_html_url, $latest_lastmod );
        if ( $index_render['entries'] > 0 ) {
            $written_index_path = trailingslashit( $target_dir ) . 'sitemap_index.xml';
            $index_bytes        = file_put_contents( $written_index_path, $index_render['xml'] );
            if ( false === $index_bytes ) {
                $result['notice']       = __( 'Sitemap files saved, but failed to write sitemap_index.xml.', 'beseo' );
                $result['notice_class'] = 'error';
            } else {
                $written_index_url = trailingslashit( $upload_dir['baseurl'] ) . 'beseo-sitemaps/sitemap_index.xml';
            }
        }

        $last_meta = array(
            'generated_at' => gmdate( 'c', time() ),
            'primary_url'  => $written_url,
            'primary_path' => $written_path,
            'sitemaps'     => $sitemap_written_meta,
            'html_url'     => $written_html_url,
            'html_path'    => $written_html_path,
            'index_url'    => $written_index_url,
            'index_path'   => $written_index_path,
        );
        update_option( 'be_schema_sitemap_last', $last_meta );

        $result['success']            = true;
        $result['notice']             = __( 'Sitemap generated successfully.', 'beseo' );
        $result['notice_class']       = 'updated';
        $result['written_paths']      = $written_paths;
        $result['written_urls']       = $written_urls;
        $result['written_path']       = $written_path;
        $result['written_url']        = $written_url;
        $result['written_index_path'] = $written_index_path;
        $result['written_index_url']  = $written_index_url;
        $result['written_html_path']  = $written_html_path;
        $result['written_html_url']   = $written_html_url;

        return $result;
    }
}

/**
 * Render the Sitemap submenu page.
 */
function be_schema_engine_render_sitemap_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $notice                 = '';
    $notice_class           = 'updated';
    $xml_output             = '';
    $html_output            = '';
    $written_path           = '';
    $written_url            = '';
    $written_paths          = array();
    $written_urls           = array();
    $written_index_path     = '';
    $written_index_url      = '';
    $written_html_path      = '';
    $written_html_url       = '';
    $selected_types         = array( 'post', 'page' );
    $include_html           = false;
    $include_indexnow       = false;
    $indexnow_key           = '';
    $indexnow_results       = array();
    $notify_google_main     = false;
    $notify_google_all      = false;
    $google_results         = array();
    $include_home           = true;
    $include_archive_now    = true;
    $include_archive_old    = true;
    $changefreq_options     = array( 'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never' );
    $changefreq_home        = 'weekly';
    $changefreq_posts       = 'weekly';
    $changefreq_pages       = 'weekly';
    $changefreq_archive_now = 'weekly';
    $changefreq_archive_old = 'monthly';
    $priority_home          = 8;
    $priority_posts         = 5;
    $priority_posts_min     = 3;
    $priority_pages         = 5;
    $priority_archives      = 4;
    $exclude_ids_raw        = '';
    $exclude_ids            = array();
    $include_lastmod        = true;
    $links_per_page         = 100;
    $include_posts         = true;
    $include_pages         = true;
    $include_images        = false;
    $include_videos        = false;
    $preview_mode_request  = '';

    // Flash message handler (PRG).
    if ( isset( $_GET['beseo_sitemap_flash'] ) ) {
        $flash_key = sanitize_key( wp_unslash( $_GET['beseo_sitemap_flash'] ) );
        $flash     = get_transient( 'be_schema_sitemap_flash_' . $flash_key );
        delete_transient( 'be_schema_sitemap_flash_' . $flash_key );
        if ( is_array( $flash ) ) {
            $notice            = isset( $flash['notice'] ) ? $flash['notice'] : '';
            $notice_class      = isset( $flash['notice_class'] ) ? $flash['notice_class'] : 'updated';
            $xml_output        = isset( $flash['xml_output'] ) ? $flash['xml_output'] : '';
            $html_output       = isset( $flash['html_output'] ) ? $flash['html_output'] : '';
            $written_paths     = isset( $flash['written_paths'] ) ? $flash['written_paths'] : array();
            $written_urls      = isset( $flash['written_urls'] ) ? $flash['written_urls'] : array();
            $written_path      = isset( $flash['written_path'] ) ? $flash['written_path'] : '';
            $written_url       = isset( $flash['written_url'] ) ? $flash['written_url'] : '';
            $written_index_path = isset( $flash['written_index_path'] ) ? $flash['written_index_path'] : '';
            $written_index_url  = isset( $flash['written_index_url'] ) ? $flash['written_index_url'] : '';
            $written_html_path  = isset( $flash['written_html_path'] ) ? $flash['written_html_path'] : '';
            $written_html_url   = isset( $flash['written_html_url'] ) ? $flash['written_html_url'] : '';
            $indexnow_results   = isset( $flash['indexnow_results'] ) ? $flash['indexnow_results'] : array();
            $google_results     = isset( $flash['google_results'] ) ? $flash['google_results'] : array();
            $preview_mode_request = isset( $flash['preview_mode'] ) ? $flash['preview_mode'] : '';
        }
    }

    $sitemap_meta         = get_option( 'be_schema_sitemap_last', array() );
    $sitemap_meta         = is_array( $sitemap_meta ) ? $sitemap_meta : array();
    $sitemap_generated_at = isset( $sitemap_meta['generated_at'] ) ? (string) $sitemap_meta['generated_at'] : '';
    $sitemap_generated_ts = $sitemap_generated_at ? strtotime( $sitemap_generated_at ) : 0;
    $sitemap_generated_on = $sitemap_generated_ts ? date_i18n( 'M j, Y g:i a', $sitemap_generated_ts ) : '';
    $sitemap_status_label = __( 'Not generated', 'beseo' );
    $sitemap_status_class = 'off';
    $sitemap_status_note  = '';
    $sitemap_file_path    = '';
    if ( $sitemap_generated_at ) {
        if ( ! empty( $sitemap_meta['index_path'] ) && file_exists( $sitemap_meta['index_path'] ) ) {
            $sitemap_file_path = $sitemap_meta['index_path'];
        } elseif ( ! empty( $sitemap_meta['sitemaps'][0]['path'] ) && file_exists( $sitemap_meta['sitemaps'][0]['path'] ) ) {
            $sitemap_file_path = $sitemap_meta['sitemaps'][0]['path'];
        } elseif ( ! empty( $sitemap_meta['primary_path'] ) && file_exists( $sitemap_meta['primary_path'] ) ) {
            $sitemap_file_path = $sitemap_meta['primary_path'];
        }

        if ( $sitemap_file_path ) {
            $sitemap_status_label = __( 'Generated', 'beseo' );
            $sitemap_status_class = '';
        } else {
            $sitemap_status_label = __( 'Missing file', 'beseo' );
            $sitemap_status_note  = __( 'The last generated sitemap file was not found on disk.', 'beseo' );
        }
    }
    $sitemap_latest_url = '';
    if ( ! empty( $sitemap_meta['index_url'] ) ) {
        $sitemap_latest_url = $sitemap_meta['index_url'];
    } elseif ( ! empty( $sitemap_meta['primary_url'] ) ) {
        $sitemap_latest_url = $sitemap_meta['primary_url'];
    }

    wp_enqueue_script(
        'be-schema-sitemap-admin',
        BE_SCHEMA_ENGINE_PLUGIN_URL . 'assets/js/sitemap-admin.js',
        array(),
        BE_SCHEMA_ENGINE_VERSION,
        true
    );
    wp_add_inline_script(
        'be-schema-sitemap-admin',
        'window.beSchemaSitemapData = ' . wp_json_encode(
            array(
                'htmlUrl'    => $written_html_url,
                'htmlSource' => $html_output,
                'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
                'hasFlash'   => isset( $_GET['beseo_sitemap_flash'] ),
            )
        ) . ';',
        'before'
    );

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'BE Schema Engine – Sitemap', 'beseo' ); ?></h1>

        <p class="description">
            <?php esc_html_e( 'Generate XML/HTML sitemaps for selected content, with optional pings to search engines.', 'beseo' ); ?>
        </p>

        <style>
            .beseo-sitemap-section {
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 14px 16px;
                background: #fff;
                box-shadow: 0 1px 1px rgba(0,0,0,0.04);
                margin-bottom: 16px;
            }
            .beseo-section-title {
                margin: 0 0 12px;
                font-size: 16px;
            }
            .beseo-sitemap-sections table.form-table th {
                width: 220px;
            }
            .beseo-sitemap-sections table.form-table td {
                padding-top: 8px;
            }
            .beseo-two-col {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                gap: 12px 24px;
            }
            .beseo-two-col .beseo-box {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 4px;
                padding: 10px 12px;
            }
            .be-schema-status-row {
                margin: 4px 0 10px;
            }
            .beseo-sitemap-dashboard-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                gap: 16px;
                align-items: start;
            }
            .beseo-sitemap-panel {
                display: none;
                margin-top: 16px;
            }
            .beseo-sitemap-panel.active {
                display: block;
            }
            .beseo-sitemap-tabs {
                margin-top: 16px;
            }
            .beseo-sitemap-results {
                border: 1px solid #e2e8f0;
                border-radius: 4px;
                background: #f8fafc;
                padding: 10px 12px;
            }
            .beseo-sitemap-results.is-success {
                border-color: #b6e1c5;
                background: #effaf4;
            }
            .beseo-sitemap-results.is-error {
                border-color: #f3b2b2;
                background: #fff4f4;
            }
            .beseo-sitemap-preview-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                gap: 12px;
            }
            .beseo-sitemap-preview-grid.has-two {
                align-items: stretch;
            }
            .beseo-sitemap-preview-grid.has-two > div {
                display: flex;
                flex-direction: column;
            }
            .beseo-sitemap-preview-grid.has-two .beseo-sitemap-code {
                flex: 1 1 auto;
                display: flex;
                flex-direction: column;
            }
            .beseo-sitemap-code {
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                background: #fff;
                padding: 10px;
                margin: 10px 0 0;
                font-family: Menlo, Consolas, Monaco, "Liberation Mono", monospace;
                font-size: 12px;
                line-height: 1.5;
                overflow: auto;
                white-space: pre;
                color: #111827;
                min-height: 220px;
            }
            .beseo-sitemap-preview-grid.has-two .beseo-sitemap-code {
                min-height: 320px;
            }
            .beseo-sitemap-code code {
                display: block;
                white-space: pre;
            }
            .beseo-code-tag {
                color: #1d4ed8;
                font-weight: 600;
            }
            .beseo-code-attr {
                color: #7c3aed;
            }
            .beseo-code-value {
                color: #0f766e;
            }
            .beseo-code-punct {
                color: #334155;
            }
            .beseo-code-comment {
                color: #6b7280;
                font-style: italic;
            }
            .beseo-sitemap-code-right .beseo-code-tag {
                color: #0f766e;
            }
            .beseo-sitemap-code-right .beseo-code-attr {
                color: #b45309;
            }
            .beseo-sitemap-code-right .beseo-code-value {
                color: #b91c1c;
            }
        </style>

        <form method="get" id="be-schema-sitemap-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return false;">
            <?php wp_nonce_field( 'be_schema_generate_sitemap', 'be_schema_sitemap_nonce' ); ?>
            <input type="hidden" name="action" value="be_schema_generate_sitemap" />
            <?php
            $sitemap_tabs = array(
                array(
                    'key'   => 'dashboard',
                    'label' => __( 'Dashboard', 'beseo' ),
                    'href'  => '#be-schema-sitemap-dashboard',
                    'data'  => array( 'sitemap-tab' => 'dashboard' ),
                ),
                array(
                    'key'   => 'inclusion',
                    'label' => __( 'Inclusion', 'beseo' ),
                    'href'  => '#be-schema-sitemap-inclusion',
                    'data'  => array( 'sitemap-tab' => 'inclusion' ),
                ),
                array(
                    'key'   => 'crawl',
                    'label' => __( 'Crawl Hints', 'beseo' ),
                    'href'  => '#be-schema-sitemap-crawl',
                    'data'  => array( 'sitemap-tab' => 'crawl' ),
                ),
                array(
                    'key'   => 'notifications',
                    'label' => __( 'Notifications', 'beseo' ),
                    'href'  => '#be-schema-sitemap-notifications',
                    'data'  => array( 'sitemap-tab' => 'notifications' ),
                ),
                array(
                    'key'   => 'links',
                    'label' => __( 'External Links', 'beseo' ),
                    'href'  => '#be-schema-sitemap-links',
                    'data'  => array( 'sitemap-tab' => 'links' ),
                ),
            );
            be_schema_engine_admin_render_nav_tabs( $sitemap_tabs, 'dashboard', array( 'wrapper_class' => 'beseo-sitemap-tabs' ) );
            ?>
            <div class="beseo-sitemap-sections">
                <div id="be-schema-sitemap-dashboard" class="beseo-sitemap-panel active" data-sitemap-panel="dashboard">
                    <?php
                    be_schema_engine_admin_render_section_open(
                        __( 'Dashboard', 'beseo' ),
                        array(
                            'section_class' => 'beseo-sitemap-section',
                            'title_class'   => 'beseo-section-title',
                            'title_tag'     => 'h3',
                        )
                    );
                    ?>
                    <div class="beseo-sitemap-dashboard-grid">
                        <div>
                            <h4 style="margin:4px 0 8px;"><?php esc_html_e( 'Preview & generate', 'beseo' ); ?></h4>
                            <p class="description" style="margin-top:-6px;">
                                <?php esc_html_e( 'Generation runs in the background and reloads this page with the results—no form resubmit prompt.', 'beseo' ); ?>
                            </p>
                            <div id="be-schema-sitemap-inline-error" class="notice notice-error" style="display:none; margin-top:10px;">
                                <p style="margin: 6px 0 8px;"></p>
                            </div>
                            <p>
                                <button type="button" class="button button-primary" id="be-schema-sitemap-generate-btn">
                                    <?php esc_html_e( 'Generate Sitemap', 'beseo' ); ?>
                                </button>
                            </p>
                        </div>
                        <div>
                            <p class="be-schema-status-row">
                                <?php
                                $sitemap_status_text = sprintf( __( 'Sitemap: %s', 'beseo' ), $sitemap_status_label );
                                echo be_schema_engine_admin_render_status_pill(
                                    $sitemap_status_text,
                                    '' === $sitemap_status_class,
                                    array( 'extra_class' => 'beseo-status-pill' )
                                );
                                ?>
                            </p>
                            <p class="description">
                                <?php if ( $sitemap_generated_on ) : ?>
                                    <?php
                                    printf(
                                        esc_html__( 'Last generated: %s', 'beseo' ),
                                        esc_html( $sitemap_generated_on )
                                    );
                                    ?>
                                <?php else : ?>
                                    <?php esc_html_e( 'No sitemap has been generated yet.', 'beseo' ); ?>
                                <?php endif; ?>
                                <?php if ( $sitemap_status_note ) : ?>
                                    <br /><?php echo esc_html( $sitemap_status_note ); ?>
                                <?php endif; ?>
                            </p>
                            <p class="description">
                                <a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php esc_html_e( 'Open /sitemap.xml', 'beseo' ); ?>
                                </a>
                                | <a href="<?php echo esc_url( home_url( '/sitemap_index.xml' ) ); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php esc_html_e( 'Open /sitemap_index.xml', 'beseo' ); ?>
                                </a>
                                <?php if ( $sitemap_latest_url ) : ?>
                                    | <a href="<?php echo esc_url( $sitemap_latest_url ); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php esc_html_e( 'Open latest file', 'beseo' ); ?>
                                    </a>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php be_schema_engine_admin_render_section_close(); ?>
                    </div>
                    <?php if ( $notice ) : ?>
                        <?php
                        be_schema_engine_admin_render_section_open(
                            __( 'Generation Results', 'beseo' ),
                            array(
                                'section_class' => 'beseo-sitemap-section',
                                'title_class'   => 'beseo-section-title',
                                'title_tag'     => 'h3',
                            )
                        );
                        ?>
                            <?php
                            $results_class = 'beseo-sitemap-results';
                            if ( 'error' === $notice_class ) {
                                $results_class .= ' is-error';
                            } else {
                                $results_class .= ' is-success';
                            }
                            ?>
                            <div class="<?php echo esc_attr( $results_class ); ?>">
                                <p><?php echo esc_html( $notice ); ?></p>
                                <?php if ( $written_urls ) : ?>
                                    <p>
                                        <strong><?php esc_html_e( 'Sitemap URLs:', 'beseo' ); ?></strong>
                                    </p>
                                    <ul>
                                        <?php foreach ( $written_urls as $url_item ) : ?>
                                            <li><a href="<?php echo esc_url( $url_item ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $url_item ); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php elseif ( $written_url ) : ?>
                                    <p>
                                        <strong><?php esc_html_e( 'Public URL:', 'beseo' ); ?></strong>
                                        <a href="<?php echo esc_url( $written_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $written_url ); ?></a>
                                    </p>
                                <?php endif; ?>
                                <?php if ( $written_paths ) : ?>
                                    <p><strong><?php esc_html_e( 'File paths:', 'beseo' ); ?></strong></p>
                                    <ul>
                                        <?php foreach ( $written_paths as $path_item ) : ?>
                                            <li><code><?php echo esc_html( $path_item ); ?></code></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php elseif ( $written_path ) : ?>
                                    <p><strong><?php esc_html_e( 'File path:', 'beseo' ); ?></strong> <code><?php echo esc_html( $written_path ); ?></code></p>
                                <?php endif; ?>
                                <?php if ( $written_index_url ) : ?>
                                    <p>
                                        <strong><?php esc_html_e( 'Sitemap index URL:', 'beseo' ); ?></strong>
                                        <a href="<?php echo esc_url( $written_index_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $written_index_url ); ?></a>
                                    </p>
                                <?php endif; ?>
                                <?php if ( $written_index_url ) : ?>
                                    <p>
                                        <strong><?php esc_html_e( 'Friendly index URL:', 'beseo' ); ?></strong>
                                        <a href="<?php echo esc_url( home_url( '/sitemap_index.xml' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( home_url( '/sitemap_index.xml' ) ); ?></a>
                                    </p>
                                <?php endif; ?>
                                <?php if ( $written_index_path ) : ?>
                                    <p><strong><?php esc_html_e( 'Sitemap index path:', 'beseo' ); ?></strong> <code><?php echo esc_html( $written_index_path ); ?></code></p>
                                <?php endif; ?>
                                <?php if ( $written_index_url ) : ?>
                                    <p>
                                        <strong><?php esc_html_e( 'Friendly URL:', 'beseo' ); ?></strong>
                                        <a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( home_url( '/sitemap.xml' ) ); ?></a>
                                    </p>
                                <?php endif; ?>
                                <?php if ( $written_html_url ) : ?>
                                    <p>
                                        <strong><?php esc_html_e( 'HTML URL:', 'beseo' ); ?></strong>
                                        <a href="<?php echo esc_url( $written_html_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $written_html_url ); ?></a>
                                    </p>
                                <?php endif; ?>
                                <?php if ( $written_html_path ) : ?>
                                    <p><strong><?php esc_html_e( 'HTML file path:', 'beseo' ); ?></strong> <code><?php echo esc_html( $written_html_path ); ?></code></p>
                                <?php endif; ?>
                                <?php if ( $indexnow_results ) : ?>
                                    <p><strong><?php esc_html_e( 'IndexNow notifications:', 'beseo' ); ?></strong></p>
                                    <ul>
                                        <?php foreach ( $indexnow_results as $result ) : ?>
                                            <li>
                                                <?php echo esc_html( $result['endpoint'] ); ?> –
                                                <?php echo esc_html( $result['status'] ); ?>:
                                                <?php echo esc_html( $result['message'] ); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <?php if ( $google_results ) : ?>
                                    <p><strong><?php esc_html_e( 'Google notifications:', 'beseo' ); ?></strong></p>
                                    <ul>
                                        <?php foreach ( $google_results as $result ) : ?>
                                            <li>
                                                <?php echo esc_html( $result['target'] ); ?> –
                                                <?php echo esc_html( $result['status'] ); ?>:
                                                <?php echo esc_html( $result['message'] ); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php be_schema_engine_admin_render_section_close(); ?>
                    <?php endif; ?>
                    <?php
                    be_schema_engine_admin_render_section_open(
                        __( 'Preview Results', 'beseo' ),
                        array(
                            'section_class' => 'beseo-sitemap-section',
                            'title_class'   => 'beseo-section-title',
                            'title_tag'     => 'h3',
                        )
                    );
                    ?>
                    <?php
                        echo be_schema_engine_render_sitemap_preview_block( $xml_output, $written_urls, $written_html_url, $preview_mode_request, $html_output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                    <?php be_schema_engine_admin_render_section_close(); ?>
                </div>
                <div id="be-schema-sitemap-inclusion" class="beseo-sitemap-panel" data-sitemap-panel="inclusion">
                    <?php
                    be_schema_engine_admin_render_section_open(
                        __( 'Inclusion', 'beseo' ),
                        array(
                            'section_class' => 'beseo-sitemap-section',
                            'title_class'   => 'beseo-section-title',
                            'title_tag'     => 'h3',
                        )
                    );
                    ?>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Content', 'beseo' ); ?></th>
                                <td>
                                    <div class="beseo-two-col">
                                        <div class="beseo-box">
                                            <label style="display:block; margin-bottom:8px;">
                                                <input type="checkbox"
                                                       name="be_schema_sitemap_include_home"
                                                       value="1"
                                                       <?php checked( $include_home ); ?> />
                                                <?php esc_html_e( 'Home page', 'beseo' ); ?>
                                            </label>
                                            <label style="display:block; margin-bottom:8px;">
                                                <input type="checkbox"
                                                       name="be_schema_sitemap_include_pages"
                                                       value="1"
                                                       <?php checked( $include_pages ); ?> />
                                                <?php esc_html_e( 'Static pages', 'beseo' ); ?>
                                            </label>
                                            <label style="display:block; margin-bottom:8px;">
                                                <input type="checkbox"
                                                       name="be_schema_sitemap_include_posts"
                                                       value="1"
                                                       <?php checked( $include_posts ); ?> />
                                                <?php esc_html_e( 'Posts', 'beseo' ); ?>
                                            </label>
                                            <label style="display:block; margin-bottom:10px;">
                                                <input type="checkbox"
                                                       name="be_schema_sitemap_include_html"
                                                       value="1"
                                                       <?php checked( $include_html ); ?> />
                                                <?php esc_html_e( 'Also generate HTML sitemap', 'beseo' ); ?>
                                            </label>
                        <!---->
                                            <label style="display:block; margin-bottom:8px;">
                                                <input type="checkbox"
                                                       name="be_schema_sitemap_include_images"
                                                       value="1"
                                                       <?php checked( $include_images ); ?> />
                                                <?php esc_html_e( 'Create Image sitemap', 'beseo' ); ?>
                                            </label>
                                            <label style="display:block; margin-bottom:8px;">
                                                <input type="checkbox"
                                                       name="be_schema_sitemap_include_videos"
                                                       value="1"
                                                       <?php checked( $include_videos ); ?> />
                                                <?php esc_html_e( 'Create Video sitemap', 'beseo' ); ?>
                                            </label>
                                            <p class="description"><?php esc_html_e( 'Choose the content to include. Custom post types are omitted for simplicity.', 'beseo' ); ?></p>
                                        </div>
                                        <div class="beseo-box">
                                            <label style="display:block; max-width: 320px; margin-bottom:10px;">
                                                <?php esc_html_e( 'Links per sitemap file', 'beseo' ); ?>
                                                <input type="number"
                                                       name="be_schema_sitemap_links_per_page"
                                                       min="1"
                                                       max="50000"
                                                       value="<?php echo esc_attr( $links_per_page ); ?>" />
                                            </label>
                                            <p class="description"><?php esc_html_e( 'Large sites split automatically once this limit is reached (default 100).', 'beseo' ); ?></p>
                                            <label style="display:block; margin-top:10px;">
                                                <textarea name="be_schema_sitemap_exclude_ids" rows="3" class="large-text code" placeholder="12,34,56"><?php echo esc_textarea( $exclude_ids_raw ); ?></textarea>
                                            </label>
                                            <p class="description">
                                                <?php esc_html_e( 'Exclude specific post/page IDs. Child posts are not excluded automatically.', 'beseo' ); ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php be_schema_engine_admin_render_section_close(); ?>
                </div>

                <div id="be-schema-sitemap-crawl" class="beseo-sitemap-panel" data-sitemap-panel="crawl">
                    <?php
                    be_schema_engine_admin_render_section_open(
                        __( 'Crawl hints', 'beseo' ),
                        array(
                            'section_class' => 'beseo-sitemap-section',
                            'title_class'   => 'beseo-section-title',
                            'title_tag'     => 'h3',
                        )
                    );
                    ?>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row" colspan="2" style="padding-bottom:0;"><?php esc_html_e( 'Change frequency & priorities', 'beseo' ); ?></th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <?php
                                    $freq_options = array(
                                        'always' => __( 'Always', 'beseo' ),
                                        'hourly' => __( 'Hourly', 'beseo' ),
                                        'daily'  => __( 'Daily', 'beseo' ),
                                        'weekly' => __( 'Weekly', 'beseo' ),
                                        'monthly'=> __( 'Monthly', 'beseo' ),
                                        'yearly' => __( 'Yearly', 'beseo' ),
                                        'never'  => __( 'Never', 'beseo' ),
                                    );
                                    $render_select = function( $name, $current ) use ( $freq_options ) {
                                        $html = '<select name="' . esc_attr( $name ) . '">';
                                        foreach ( $freq_options as $value => $label ) {
                                            $html .= '<option value="' . esc_attr( $value ) . '"' . selected( $current, $value, false ) . '>' . esc_html( $label ) . '</option>';
                                        }
                                        $html .= '</select>';
                                        return $html;
                                    };
                                    ?>
                                    <div class="beseo-two-col">
                                        <div class="beseo-box">
                                            <p><strong><?php esc_html_e( 'Homepage', 'beseo' ); ?></strong><br /><?php echo $render_select( 'be_schema_sitemap_changefreq_home', $changefreq_home ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                                            <p><strong><?php esc_html_e( 'Posts', 'beseo' ); ?></strong><br /><?php echo $render_select( 'be_schema_sitemap_changefreq_posts', $changefreq_posts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                                            <p><strong><?php esc_html_e( 'Pages (static)', 'beseo' ); ?></strong><br /><?php echo $render_select( 'be_schema_sitemap_changefreq_pages', $changefreq_pages ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                                            <p><strong><?php esc_html_e( 'Current month archive', 'beseo' ); ?></strong><br /><?php echo $render_select( 'be_schema_sitemap_changefreq_archive_now', $changefreq_archive_now ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                                            <p><strong><?php esc_html_e( 'Older archives', 'beseo' ); ?></strong><br /><?php echo $render_select( 'be_schema_sitemap_changefreq_archive_old', $changefreq_archive_old ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                                            <p class="description"><?php esc_html_e( 'Archives split lets you hint crawlers differently for current vs older months.', 'beseo' ); ?></p>
                                        </div>
                                        <div class="beseo-box">
                                            <p><strong><?php esc_html_e( 'Homepage priority', 'beseo' ); ?></strong><br />
                                                <input type="number" name="be_schema_sitemap_priority_home" min="1" max="10" value="<?php echo esc_attr( $priority_home ); ?>" /></p>
                                            <p><strong><?php esc_html_e( 'Posts priority', 'beseo' ); ?></strong><br />
                                                <input type="number" name="be_schema_sitemap_priority_posts" min="1" max="10" value="<?php echo esc_attr( $priority_posts ); ?>" />
                                                <span class="description"><?php esc_html_e( 'If auto calculation is disabled.', 'beseo' ); ?></span></p>
                                            <p><strong><?php esc_html_e( 'Minimum post priority', 'beseo' ); ?></strong><br />
                                                <input type="number" name="be_schema_sitemap_priority_posts_min" min="1" max="10" value="<?php echo esc_attr( $priority_posts_min ); ?>" />
                                                <span class="description"><?php esc_html_e( 'Even when auto calc is enabled, floor priority.', 'beseo' ); ?></span></p>
                                            <p><strong><?php esc_html_e( 'Static pages priority', 'beseo' ); ?></strong><br />
                                                <input type="number" name="be_schema_sitemap_priority_pages" min="1" max="10" value="<?php echo esc_attr( $priority_pages ); ?>" /></p>
                                            <p><strong><?php esc_html_e( 'Archives priority', 'beseo' ); ?></strong><br />
                                                <input type="number" name="be_schema_sitemap_priority_archives" min="1" max="10" value="<?php echo esc_attr( $priority_archives ); ?>" /></p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Timestamps', 'beseo' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="be_schema_sitemap_include_lastmod"
                                               value="1"
                                               <?php checked( $include_lastmod ); ?> />
                                        <?php esc_html_e( 'Include the last modification time for all entries', 'beseo' ); ?>
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php be_schema_engine_admin_render_section_close(); ?>
                </div>

                <div id="be-schema-sitemap-notifications" class="beseo-sitemap-panel" data-sitemap-panel="notifications">
                    <?php
                    be_schema_engine_admin_render_section_open(
                        __( 'Notifications', 'beseo' ),
                        array(
                            'section_class' => 'beseo-sitemap-section',
                            'title_class'   => 'beseo-section-title',
                            'title_tag'     => 'h3',
                        )
                    );
                    ?>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row" colspan="2" style="padding-bottom:0;"><?php esc_html_e( 'Choose what to notify after generation', 'beseo' ); ?></th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div class="beseo-two-col">
                                        <div class="beseo-box">
                                            <p><strong><?php esc_html_e( 'IndexNow', 'beseo' ); ?></strong></p>
                                            <label style="display:block; margin-bottom:8px;">
                                                <input type="checkbox"
                                                       name="be_schema_sitemap_include_indexnow"
                                                       value="1"
                                                       <?php checked( $include_indexnow ); ?> />
                                                <?php esc_html_e( 'Notify Bing, Seznam, Naver, and Yandex via IndexNow after generating.', 'beseo' ); ?>
                                            </label>
                                            <label style="display:block; max-width: 420px;">
                                                <?php esc_html_e( 'IndexNow key', 'beseo' ); ?>
                                                <input type="text"
                                                       name="be_schema_sitemap_indexnow_key"
                                                       value="<?php echo esc_attr( $indexnow_key ); ?>"
                                                       class="regular-text"
                                                       placeholder="e.g. 1234567890abcdef1234567890abcdef" />
                                                <span class="description"><?php esc_html_e( 'Place the key file at your site root as required by IndexNow.', 'beseo' ); ?></span>
                                            </label>
                                        </div>
                                        <div class="beseo-box">
                                            <p><strong><?php esc_html_e( 'Google notifications', 'beseo' ); ?></strong></p>
                                            <label style="display:block; margin-bottom:8px;">
                                                <input type="checkbox"
                                                       name="be_schema_sitemap_notify_google_main"
                                                       value="1"
                                                       <?php checked( $notify_google_main ); ?> />
                                                <?php esc_html_e( 'Notify Google about the main sitemap', 'beseo' ); ?>
                                            </label>
                                            <label style="display:block; margin-bottom:8px;">
                                                <input type="checkbox"
                                                       name="be_schema_sitemap_notify_google_all"
                                                       value="1"
                                                       <?php checked( $notify_google_all ); ?> />
                                                <?php esc_html_e( 'Notify Google about all generated sitemaps (if split)', 'beseo' ); ?>
                                            </label>
                                            <p class="description">
                                                <?php esc_html_e( 'Uses Google ping endpoint with each sitemap URL.', 'beseo' ); ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php be_schema_engine_admin_render_section_close(); ?>
                </div>

                <div id="be-schema-sitemap-links" class="beseo-sitemap-panel" data-sitemap-panel="links">
                    <?php
                    be_schema_engine_admin_render_section_open(
                        __( 'External Links', 'beseo' ),
                        array(
                            'section_class' => 'beseo-sitemap-section',
                            'title_class'   => 'beseo-section-title',
                            'title_tag'     => 'h3',
                        )
                    );
                    ?>
                        <p class="description">
                            <?php esc_html_e( 'Helpful links:', 'beseo' ); ?>
                        </p>
                        <ul style="margin-left:18px; list-style: disc;">
                            <li><a href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google Search Console', 'beseo' ); ?></a></li>
                            <li><a href="https://developers.google.com/search/blog" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google Search Blog', 'beseo' ); ?></a></li>
                            <li><a href="https://www.bing.com/webmasters" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Bing Webmaster Tools', 'beseo' ); ?></a></li>
                            <li><a href="https://blogs.bing.com/webmaster" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Microsoft Bing Blog', 'beseo' ); ?></a></li>
                            <li><a href="https://www.sitemaps.org/protocol.html" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Sitemaps Protocol', 'beseo' ); ?></a></li>
                            <li><a href="https://www.indexnow.org/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'IndexNow Protocol', 'beseo' ); ?></a></li>
                        </ul>
                    <?php be_schema_engine_admin_render_section_close(); ?>
                </div>

            </div>
        </form>

    </div>
    <?php
}
