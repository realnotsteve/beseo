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
            $urls = (array) $urls;
        }

        $normalized = array();

        foreach ( $urls as $url ) {
            $url = trim( (string) $url );
            if ( '' === $url ) {
                continue;
            }
            $normalized[] = esc_url_raw( $url );
        }

        $normalized = array_unique( array_filter( $normalized ) );

        return array_values( $normalized );
    }
}

/**
 * Build the site-level entities model for BE Schema Engine.
 *
 * Returns an associative array of nodes:
 *
 * - person         => Person node (or null)
 * - person_image   => Person ImageObject (#person-image) or null
 * - organization   => Organization node (or null)
 * - website        => WebSite node (always present)
 * - logo           => shared ImageObject for site brand logo (#logo) or null
 * - publisher      => either:
 *                     - custom publisher Organization node, OR
 *                     - @id reference to Organisation/Person node, OR
 *                     - null if publisher disabled / not resolvable
 * - publisher_logo => ImageObject for custom publisher logo (#publisher-logo) or null
 *
 * This is purely a model builder; nothing is output here. Callers can decide
 * which nodes to include in @graph and in which contexts.
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
        $logo_id = $site_url . '#logo';

        $logo_node = array(
            '@type'      => 'ImageObject',
            '@id'        => $logo_id,
            'url'        => $settings['org_logo'],
            'contentUrl' => $settings['org_logo'],
        );
    }

    /**
     * ---------------------------------------------------------------------
     * Person entity (#person)
     * ---------------------------------------------------------------------
     *
     * Enabled when settings['person_enabled'] === '1'.
     * - @id  = {site_url}#person
     * - name = site name
     * - honorificPrefix / honorificSuffix from settings
     * - image:
     *   - #person-image ImageObject if person_image_url is set, else
     *   - #logo if available
     * - sameAs from person_sameas_raw (one URL per line)
     */
    $person_node       = null;
    $person_image_node = null;

    if ( isset( $settings['person_enabled'] ) && $settings['person_enabled'] === '1' ) {
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

        // Person-specific profile image (#person-image) if configured.
        if ( ! empty( $settings['person_image_url'] ) ) {
            $person_image_id = $site_url . '#person-image';

            $person_image_node = array(
                '@type'      => 'ImageObject',
                '@id'        => $person_image_id,
                'url'        => $settings['person_image_url'],
                'contentUrl' => $settings['person_image_url'],
            );

            $person_node['image'] = array(
                '@id' => $person_image_id,
            );
        } elseif ( $logo_node ) {
            // Fallback to shared site logo (#logo) if no person-specific image.
            $person_node['image'] = array(
                '@id' => $logo_node['@id'],
            );
        }

        // Person.sameAs from Person-specific settings (one URL per line).
        if ( ! empty( $settings['person_sameas_raw'] ) ) {
            $lines            = preg_split( '/[\r\n]+/', (string) $settings['person_sameas_raw'] );
            $person_sameas    = be_schema_engine_normalize_url_list( $lines );
            if ( ! empty( $person_sameas ) ) {
                $person_node['sameAs'] = $person_sameas;
            }
        }
    }

    /**
     * ---------------------------------------------------------------------
     * Organisation entity (#organization)
     * ---------------------------------------------------------------------
     *
     * Enabled when settings['organization_enabled'] === '1'.
     * - @id       = {site_url}#organization
     * - name      = org_name or site name
     * - legalName = org_legal_name (optional)
     * - url       = org_url or {site_url}
     * - logo      = #logo if available
     * - sameAs    = from be_schema_sameas_urls filter (Organisation-only)
     */
    $organization_node = null;

    if ( isset( $settings['organization_enabled'] ) && $settings['organization_enabled'] === '1' ) {
        $org_id   = $site_url . '#organization';
        $org_name = isset( $settings['org_name'] ) && $settings['org_name'] !== ''
            ? $settings['org_name']
            : $site_name;

        $organization_node = array(
            '@type' => 'Organization',
            '@id'   => $org_id,
            'name'  => $org_name,
        );

        if ( ! empty( $settings['org_legal_name'] ) ) {
            $organization_node['legalName'] = $settings['org_legal_name'];
        }

        $org_url = ! empty( $settings['org_url'] ) ? $settings['org_url'] : $site_url;
        $organization_node['url'] = $org_url;

        if ( $logo_node ) {
            $organization_node['logo'] = array(
                '@id' => $logo_node['@id'],
            );
        }

        // Organisation.sameAs from global filter (backward compatible).
        $org_sameas = apply_filters( 'be_schema_sameas_urls', array() );
        $org_sameas = be_schema_engine_normalize_url_list( $org_sameas );

        if ( ! empty( $org_sameas ) ) {
            $organization_node['sameAs'] = $org_sameas;
        }
    }

    /**
     * ---------------------------------------------------------------------
     * Publisher model
     * ---------------------------------------------------------------------
     *
     * Only used if settings['publisher_enabled'] === '1'.
     *
     * Paths:
     * 1) Custom publisher:
     *    - Requires publisher_custom_name (Organisation node #publisher).
     *    - Optional publisher_custom_url (falls back to site_url).
     *    - Optional publisher_custom_logo (ImageObject #publisher-logo).
     *    - WebSite.publisher uses @id of #publisher.
     *
     * 2) Fallback publisher (no custom name):
     *    - If Organisation exists: @id -> #organization
     *    - Else if Person exists: @id -> #person
     *
     * Returned keys:
     * - publisher_node  (custom Organisation) or null
     * - publisher_ref   (@id reference for WebSite.publisher) or null
     * - publisher_logo  (ImageObject #publisher-logo) or null
     */
    $publisher_enabled    = isset( $settings['publisher_enabled'] ) && $settings['publisher_enabled'] === '1';
    $publisher_node       = null;
    $publisher_ref        = null;
    $publisher_logo_node  = null;

    if ( $publisher_enabled ) {
        $custom_name = isset( $settings['publisher_custom_name'] )
            ? trim( (string) $settings['publisher_custom_name'] )
            : '';

        if ( $custom_name !== '' ) {
            // Custom publisher Organisation.
            $publisher_id  = $site_url . '#publisher';
            $publisher_url = ! empty( $settings['publisher_custom_url'] )
                ? $settings['publisher_custom_url']
                : $site_url;

            $publisher_node = array(
                '@type' => 'Organization',
                '@id'   => $publisher_id,
                'name'  => $custom_name,
                'url'   => $publisher_url,
            );

            // Custom publisher logo (#publisher-logo).
            if ( ! empty( $settings['publisher_custom_logo'] ) ) {
                $publisher_logo_id = $site_url . '#publisher-logo';

                $publisher_logo_node = array(
                    '@type'      => 'ImageObject',
                    '@id'        => $publisher_logo_id,
                    'url'        => $settings['publisher_custom_logo'],
                    'contentUrl' => $settings['publisher_custom_logo'],
                );

                $publisher_node['logo'] = array(
                    '@id' => $publisher_logo_id,
                );
            }

            $publisher_ref = array( '@id' => $publisher_id );

        } else {
            // Fallback publisher: Organisation > Person.
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
    }

    /**
     * ---------------------------------------------------------------------
     * WebSite entity (#website)
     * ---------------------------------------------------------------------
     *
     * Always emitted:
     * - @id         = {site_url}#website
     * - url         = {site_url}
     * - name        = site name
     * - description = site description (if any)
     * - logo        = #logo (if available)
     * - publisher   = publisher_ref (if any)
     * - about       = #organization if exists; else #person if exists
     */
    $website_id   = $site_url . '#website';
    $website_node = array(
        '@type' => 'WebSite',
        '@id'   => $website_id,
        'url'   => $site_url,
        'name'  => $site_name,
    );

    if ( $site_desc_clean ) {
        $website_node['description'] = $site_desc_clean;
    }

    if ( $logo_node ) {
        $website_node['logo'] = array(
            '@id' => $logo_node['@id'],
        );
    }

    if ( $publisher_ref ) {
        $website_node['publisher'] = $publisher_ref;
    }

    if ( $organization_node ) {
        $website_node['about'] = array(
            '@id' => $organization_node['@id'],
        );
    } elseif ( $person_node ) {
        $website_node['about'] = array(
            '@id' => $person_node['@id'],
        );
    }

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

    return $cached;
}

/**
 * Convenience helper: return a flat list of nodes suitable for @graph,
 * including only those entities that actually exist.
 *
 * This is handy when building the homepage @graph.
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