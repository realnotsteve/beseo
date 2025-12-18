<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize a list of URLs:
 * - Accepts array or scalar
 * - Trims
 * - esc_url_raw
 * - De-duplicates and drops empties
 *
 * @param mixed $urls
 * @return array
 */
if ( ! function_exists( 'be_schema_engine_normalize_url_list' ) ) {
    function be_schema_engine_normalize_url_list( $urls ) {
        if ( ! is_array( $urls ) ) {
            $urls = array( $urls );
        }

        $clean = array();

        foreach ( $urls as $url ) {
            $url = trim( (string) $url );
            if ( '' === $url ) {
                continue;
            }
            $url = esc_url_raw( $url );
            if ( '' === $url ) {
                continue;
            }
            $clean[ $url ] = true;
        }

        return array_keys( $clean );
    }
}

/**
 * Resolve a media attachment to an ImageObject-like array:
 * - Accepts attachment ID or URL.
 * - Returns:
 *   array(
 *     '@type'      => 'ImageObject',
 *     '@id'        => '{url}#image',
 *     'contentUrl' => '{url}',
 *     'url'        => '{url}',
 *     'width'      => int|null,
 *     'height'     => int|null,
 *     'description'=> string|null,
 *     'caption'    => string|null,
 *     'creator'    => Person|null,
 *     'license'    => string|null,
 *   )
 *
 * @param int|string $image Media attachment ID or URL.
 * @param string     $id_suffix Optional suffix for @id.
 * @return array|null
 */
if ( ! function_exists( 'be_schema_engine_build_image_object' ) ) {
    function be_schema_engine_build_image_object( $image, $id_suffix = '#image' ) {
        $url    = '';
        $width  = null;
        $height = null;

        // Attachment ID.
        if ( is_numeric( $image ) ) {
            $attachment_id = (int) $image;
            if ( $attachment_id > 0 ) {
                $src = wp_get_attachment_image_src( $attachment_id, 'full' );
                if ( is_array( $src ) && ! empty( $src[0] ) ) {
                    $url    = esc_url_raw( $src[0] );
                    $width  = isset( $src[1] ) ? (int) $src[1] : null;
                    $height = isset( $src[2] ) ? (int) $src[2] : null;
                }
            }
        } elseif ( is_string( $image ) ) {
            $url = esc_url_raw( $image );
        }

        if ( ! $url ) {
            return null;
        }

        $node = array(
            '@type'      => 'ImageObject',
            '@id'        => $url . $id_suffix,
            'contentUrl' => $url,
            'url'        => $url,
        );

        if ( $width ) {
            $node['width'] = $width;
        }

        if ( $height ) {
            $node['height'] = $height;
        }

        // Optional description/caption/creator/license from attachment meta.
        if ( is_numeric( $image ) ) {
            $attachment_id = (int) $image;
            if ( $attachment_id > 0 ) {
                $alt        = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                $caption    = wp_get_attachment_caption( $attachment_id );
                $candidate  = '';

                if ( ! empty( $alt ) ) {
                    $candidate = $alt;
                } elseif ( ! empty( $caption ) ) {
                    $candidate = $caption;
                }

                if ( $candidate ) {
                    $candidate = wp_strip_all_tags( (string) $candidate );
                    $candidate = preg_replace( '/\s+/', ' ', $candidate );
                    $candidate = trim( $candidate );
                    if ( '' !== $candidate ) {
                        $node['description'] = $candidate;
                    }
                }

                if ( $caption ) {
                    $node['caption'] = wp_strip_all_tags( (string) $caption );
                }

                $meta = wp_get_attachment_metadata( $attachment_id );
                if ( is_array( $meta ) && ! empty( $meta['image_meta'] ) && is_array( $meta['image_meta'] ) ) {
                    $image_meta = $meta['image_meta'];
                    if ( ! empty( $image_meta['copyright'] ) ) {
                        $node['license'] = $image_meta['copyright'];
                    }
                    if ( ! empty( $image_meta['credit'] ) ) {
                        $node['creator'] = array(
                            '@type' => 'Person',
                            'name'  => $image_meta['credit'],
                        );
                    }
                }

                // Fallback creator to attachment author.
                if ( empty( $node['creator'] ) ) {
                    $author_id   = get_post_field( 'post_author', $attachment_id );
                    $author_name = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';
                    if ( $author_name ) {
                        $node['creator'] = array(
                            '@type' => 'Person',
                            'name'  => $author_name,
                        );
                    }
                }
            }
        }

        return $node;
    }
}

/**
 * Get the site-level entities model:
 * - Person
 * - Person image
 * - Organisation
 * - WebSite
 * - Logo
 * - Publisher (and logo)
 *
 * Returns a structured array keyed by entity role.
 *
 * @return array
 */
function be_schema_get_site_entities() {
    static $cached;

    if ( isset( $cached ) ) {
        return $cached;
    }

    $settings = be_schema_engine_get_settings();

    // Base site info.
    $site_url        = trailingslashit( home_url() ); // e.g. https://example.com/
    $site_name       = get_bloginfo( 'name', 'display' );
    $site_tagline    = get_bloginfo( 'description', 'display' );
    $site_desc_clean = trim( wp_strip_all_tags( (string) $site_tagline ) );
    $site_desc_clean = ( $site_desc_clean !== '' ) ? $site_desc_clean : null;
    $site_language   = get_bloginfo( 'language' );

    /**
     * ---------------------------------------------------------------------
     * Shared brand logo (#logo)
     * ---------------------------------------------------------------------
     *
     * Sourced from settings['org_logo'].
     * Used as:
     * - WebSite.logo
     * - Organisation.logo (if enabled)
     * - Person.image (fallback, if enabled and no person-specific image)
     */
    $logo_node = null;

    if ( ! empty( $settings['org_logo'] ) ) {
        // Accept attachment ID or URL.
        $logo_node = be_schema_engine_build_image_object( $settings['org_logo'], '#logo' );
    }

    /**
     * ---------------------------------------------------------------------
     * Person (#person) and Person image (#person-image)
     * ---------------------------------------------------------------------
     */
    $person_node        = null;
    $person_image_node  = null;
    $person_enabled     = ! empty( $settings['person_enabled'] );

    if ( $person_enabled ) {
        $person_id = $site_url . '#person';

        $person_node = array(
            '@type' => 'Person',
            '@id'   => $person_id,
            'name'  => $site_name,
        );

        // Honorifics.
        if ( ! empty( $settings['person_honorific_prefix'] ) ) {
            $person_node['honorificPrefix'] = $settings['person_honorific_prefix'];
        }

        if ( ! empty( $settings['person_honorific_suffix'] ) ) {
            $person_node['honorificSuffix'] = $settings['person_honorific_suffix'];
        }

        // Person image.
        if ( ! empty( $settings['person_image'] ) ) {
            $person_image_node = be_schema_engine_build_image_object( $settings['person_image'], '#person-image' );
        }

        // Fallback person image from shared logo.
        if ( ! $person_image_node && $logo_node ) {
            // Use the same ImageObject but give it a distinct @id.
            $person_image_node = $logo_node;
            $person_image_node['@id'] = $logo_node['contentUrl'] . '#person-image';
        }

        if ( $person_image_node ) {
            $person_node['image'] = array(
                '@id' => $person_image_node['@id'],
            );
        }

        if ( ! empty( $settings['person_url'] ) ) {
            $person_node['url'] = $settings['person_url'];
        }

        if ( ! empty( $settings['person_alumni_of'] ) ) {
            $person_node['alumniOf'] = $settings['person_alumni_of'];
        }

        if ( ! empty( $settings['person_job_title'] ) ) {
            $person_node['jobTitle'] = $settings['person_job_title'];
        }

        if ( ! empty( $settings['person_affiliation'] ) ) {
            $person_node['affiliation'] = $settings['person_affiliation'];
        }

        // Person sameAs: from textarea, one URL per line.
        $person_sameas_source = null;
        if ( ! empty( $settings['person_sameas'] ) ) {
            $person_sameas_source = $settings['person_sameas'];
        } elseif ( ! empty( $settings['person_sameas_raw'] ) ) {
            $person_sameas_source = $settings['person_sameas_raw'];
        }

        if ( null !== $person_sameas_source ) {
            $lines = preg_split( '/\r\n|\r|\n/', (string) $person_sameas_source );
            $urls  = be_schema_engine_normalize_url_list( $lines );
            if ( ! empty( $urls ) ) {
                $person_node['sameAs'] = $urls;
            }
        }
    }

    /**
     * ---------------------------------------------------------------------
     * Organisation (#organisation)
     * ---------------------------------------------------------------------
     */
    $organization_node  = null;
    $organization_url   = ! empty( $settings['org_url'] ) ? esc_url_raw( $settings['org_url'] ) : $site_url;
    $organization_name  = ! empty( $settings['org_name'] ) ? $settings['org_name'] : $site_name;
    $organization_legal = ! empty( $settings['org_legal_name'] ) ? $settings['org_legal_name'] : '';

    $organization_enabled = ! empty( $settings['organization_enabled'] );

    if ( $organization_enabled ) {
        $organization_node = array(
            '@type' => 'Organization',
            '@id'   => $site_url . '#organisation',
            'name'  => $organization_name,
            'url'   => $organization_url,
        );

        if ( $organization_legal ) {
            $organization_node['legalName'] = $organization_legal;
        }

        if ( $logo_node ) {
            $organization_node['logo'] = array(
                '@id' => $logo_node['@id'],
            );
        }

        /**
         * Organisation sameAs URLs from settings and filters.
         *
         * This keeps the admin UI simpler and lets other plugins contribute.
         */
        $org_sameas = array();
        if ( ! empty( $settings['org_sameas_raw'] ) ) {
            $lines      = preg_split( '/\r\n|\r|\n/', (string) $settings['org_sameas_raw'] );
            $org_sameas = be_schema_engine_normalize_url_list( $lines );
        }

        $org_sameas_filtered = apply_filters( 'be_schema_sameas_urls', array() );
        $org_sameas_filtered = be_schema_engine_normalize_url_list( $org_sameas_filtered );

        $org_sameas_all = array_unique( array_filter( array_merge( $org_sameas, $org_sameas_filtered ) ) );
        if ( ! empty( $org_sameas_all ) ) {
            $organization_node['sameAs'] = $org_sameas_all;
        }
    }

    /**
     * ---------------------------------------------------------------------
     * Publisher (#publisher and #publisher-logo)
     * ---------------------------------------------------------------------
     *
     * The publisher can be:
     * - A custom organisation (with its own logo), or
     * - Fallback to Organisation/Person by @id.
     */
    $publisher_node      = null;
    $publisher_logo_node = null;
    $publisher_ref       = null;

    $publisher_enabled        = ! empty( $settings['publisher_enabled'] );
    $publisher_use_custom_org = $publisher_enabled && ! empty( $settings['publisher_dedicated_enabled'] );

    if ( $publisher_enabled && $publisher_use_custom_org ) {
        $publisher_name = ! empty( $settings['publisher_custom_name'] ) ? $settings['publisher_custom_name'] : $organization_name;
        $publisher_url  = ! empty( $settings['publisher_custom_url'] ) ? esc_url_raw( $settings['publisher_custom_url'] ) : $organization_url;

        $publisher_node = array(
            '@type' => 'Organization',
            '@id'   => $site_url . '#publisher',
            'name'  => $publisher_name,
            'url'   => $publisher_url,
        );

        // Custom publisher logo (dedicated block images take precedence, then custom logo, then shared site logo).
        $publisher_logo_candidates = array(
            $settings['publisher_image_1_1'] ?? '',
            $settings['publisher_image_16_9'] ?? '',
            $settings['publisher_image_4_3'] ?? '',
            $settings['publisher_image_3_4'] ?? '',
            $settings['publisher_image_9_16'] ?? '',
            $settings['publisher_custom_logo'] ?? '',
        );

        foreach ( $publisher_logo_candidates as $candidate_logo ) {
            if ( ! empty( $candidate_logo ) ) {
                $publisher_logo_node = be_schema_engine_build_image_object( $candidate_logo, '#publisher-logo' );
                if ( $publisher_logo_node ) {
                    break;
                }
            }
        }

        // Fallback to the shared site logo if nothing else was provided.
        if ( ! $publisher_logo_node && $logo_node ) {
            $publisher_logo_node = $logo_node;
            $publisher_logo_node['@id'] = $logo_node['contentUrl'] . '#publisher-logo';
        }

        if ( $publisher_logo_node ) {
            $publisher_node['logo'] = array(
                '@id' => $publisher_logo_node['@id'],
            );
        }

        $publisher_ref = array(
            '@id' => $publisher_node['@id'],
        );
    } elseif ( $publisher_enabled ) {
        // Fallback to Organization if available, otherwise Person.
        if ( $organization_node ) {
            $publisher_ref = array(
                '@id' => $organization_node['@id'],
            );
        } elseif ( $person_node ) {
            $publisher_ref = array(
                '@id' => $person_node['@id'],
            );
        }
    }

    /**
     * ---------------------------------------------------------------------
     * WebSite (#website)
     * ---------------------------------------------------------------------
     */
    $website_node = array(
        '@type' => 'WebSite',
        '@id'   => $site_url . '#website',
        'url'   => $site_url,
        'name'  => $site_name,
    );

    if ( $site_language ) {
        $website_node['inLanguage'] = $site_language;
    }

    if ( $site_desc_clean ) {
        $website_node['description'] = $site_desc_clean;
    }

    if ( $site_desc_clean ) {
        $website_node['alternateName'] = $site_desc_clean;
    }

    if ( $logo_node ) {
        $website_node['logo'] = array(
            '@id' => $logo_node['@id'],
        );
    }

    $priority = isset( $settings['site_identity_mode'] ) ? $settings['site_identity_mode'] : 'publisher';
    $priority_order = array(
        'person'       => array( 'person', 'organisation', 'publisher' ),
        'organisation' => array( 'organisation', 'publisher', 'person' ),
        'publisher'    => array( 'publisher', 'organisation', 'person' ),
    );
    if ( ! isset( $priority_order[ $priority ] ) ) {
        $priority = 'publisher';
    }

    $entity_refs = array(
        'person'       => $person_node ? array( '@id' => $person_node['@id'] ) : null,
        'organisation' => $organization_node ? array( '@id' => $organization_node['@id'] ) : null,
        'publisher'    => $publisher_node ? array( '@id' => $publisher_node['@id'] ) : $publisher_ref,
    );

    $pick_entity = function ( $priority_key ) use ( $priority_order, $entity_refs ) {
        foreach ( $priority_order[ $priority_key ] as $key ) {
            if ( ! empty( $entity_refs[ $key ] ) ) {
                return $entity_refs[ $key ];
            }
        }
        return null;
    };

    $primary_entity = $pick_entity( $priority );

    if ( $primary_entity ) {
        $website_node['about'] = $primary_entity;
        $website_node['publisher'] = $primary_entity;
    }

    if ( $organization_node ) {
        $website_node['isPartOf'] = array(
            '@id' => $organization_node['@id'],
        );
    }

    // Add SearchAction potentialAction for site search.
    $search_target = add_query_arg( 's', '{search_term_string}', home_url( '/' ) );
    $search_target = str_replace( '%7Bsearch_term_string%7D', '{search_term_string}', $search_target );
    $search_target = apply_filters( 'be_schema_search_action_target', $search_target, 'website' );
    $website_node['potentialAction'] = array(
        array(
            '@type'       => 'SearchAction',
            'target'      => $search_target,
            'query-input' => 'required name=search_term_string',
        ),
    );

    /**
     * Final assembled model.
     */
    $cached = array(
        'person'         => $person_node,
        'person_image'   => $person_image_node,
        'organization'   => $organization_node,
        'website'        => $website_node,
        'logo'           => $logo_node,
        'publisher'      => $publisher_node ? $publisher_node : $publisher_ref,
        'publisher_logo' => $publisher_logo_node,
    );

    /**
     * Filter the assembled site entities model.
     *
     * @param array $cached   Site entities keyed array.
     * @param array $settings Raw plugin settings used to build entities.
     */
    $cached = apply_filters( 'be_schema_site_entities', $cached, $settings );

    return $cached;
}

/**
 * Get the flat list of site entities suitable for @graph:
 * - Person
 * - Person image
 * - Organisation
 * - WebSite
 * - logo
 * - Publisher
 * - Publisher logo
 *
 * @return array
 */
function be_schema_get_site_entity_graph_nodes() {
    $model = be_schema_get_site_entities();
    $nodes = array();

    foreach ( array( 'person', 'person_image', 'organization', 'logo', 'publisher_logo', 'publisher', 'website' ) as $key ) {
        if ( isset( $model[ $key ] ) && is_array( $model[ $key ] ) && ! empty( $model[ $key ] ) ) {
            // 'publisher' might be just an @id reference in fallback mode; include it only
            // if it looks like a full node (has @type) to avoid duplicates.
            if ( $key === 'publisher' && ! isset( $model[ $key ]['@type'] ) ) {
                continue;
            }
            $nodes[] = $model[ $key ];
        }
    }

    return $nodes;
}
