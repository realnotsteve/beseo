<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * -------------------------------------------------------------------------
 * Homepage schema
 * -------------------------------------------------------------------------
 *
 * Conditions:
 * - Global schema enabled (be_schema_globally_disabled() === false)
 * - is_front_page()
 * - Not disabled for current page (be_schema_is_disabled_for_current_page() === false)
 *
 * Output:
 * - @graph with:
 *   - Person (if enabled)
 *   - Organization (if enabled)
 *   - publisher node (if custom publisher)
 *   - logo (brand ImageObject, if any)
 *   - publisher_logo (if any)
 *   - WebSite
 *   - WebPage for home:
 *       @id   = {site_url}#webpage
 *       url   = {site_url}
 *       name  = site name
 *       isPartOf -> #website
 *       about     -> matches WebSite.about (Person/Org)
 *       primaryImageOfPage -> #logo if present.
 */
function be_schema_output_homepage_schema() {
    if ( be_schema_globally_disabled() ) {
        return;
    }

    if ( ! is_front_page() ) {
        return;
    }

    // If the front page is a static page, apply per-page safety.
    if ( be_schema_is_disabled_for_current_page() ) {
        return;
    }

    $entities    = be_schema_get_site_entities();
    $graph_nodes = be_schema_get_site_entity_graph_nodes();

    $site_url  = trailingslashit( home_url() );
    $site_name = get_bloginfo( 'name', 'display' );

    // Home WebPage node.
    $webpage_id = $site_url . '#webpage';
    $website    = isset( $entities['website'] ) && is_array( $entities['website'] ) ? $entities['website'] : null;

    $webpage = array(
        '@type'    => 'WebPage',
        '@id'      => $webpage_id,
        'url'      => $site_url,
        'name'     => $site_name,
        'isPartOf' => $website && isset( $website['@id'] )
            ? array( '@id' => $website['@id'] )
            : array( '@id' => $site_url . '#website' ),
    );

    // Mirror WebSite.about onto WebPage.about.
    if ( $website && isset( $website['about'] ) && is_array( $website['about'] ) ) {
        $webpage['about'] = $website['about'];
    }

    // primaryImageOfPage -> #logo, if any.
    if ( isset( $entities['logo'] ) && is_array( $entities['logo'] ) && isset( $entities['logo']['@id'] ) ) {
        $webpage['primaryImageOfPage'] = array(
            '@id' => $entities['logo']['@id'],
        );
    }

    $graph_nodes[] = $webpage;

    /**
     * Filter the homepage @graph nodes before output.
     *
     * @param array $graph_nodes List of nodes to be emitted.
     * @param array $entities    Site entities model from be_schema_get_site_entities().
     */
    $graph_nodes = apply_filters( 'be_schema_homepage_graph_nodes', $graph_nodes, $entities );

    // Debug collector: store nodes for @graph logging.
    be_schema_debug_collect( $graph_nodes );

    $output = array(
        '@context' => 'https://schema.org',
        '@graph'   => $graph_nodes,
    );

    echo '<script type="application/ld+json">' . wp_json_encode( $output ) . '</script>' . "\n";
}

/**
 * -------------------------------------------------------------------------
 * Special page schema (Contact/About/Privacy/Accessibility)
 * -------------------------------------------------------------------------
 *
 * Conditions:
 * - Global schema enabled.
 * - is_singular().
 * - Not disabled for current page (page-level safety).
 * - Elementor page settings exist and:
 *   - be_schema_page_type is one of:
 *       contact              → ContactPage
 *       about                → AboutPage
 *       privacy-policy       → WebPage
 *       accessibility-statement → WebPage
 *   - be_schema_enable_page must be 'yes'.
 *
 * Overrides (Elementor page settings):
 * - be_schema_page_override_enable (switcher):
 *   - If 'yes' and be_schema_page_description set → use normalized description.
 *   - If 'yes' and be_schema_page_image set      → use that image URL.
 *
 * Fallback description:
 * - Post excerpt
 * - OR site description (WebSite.description) if no override and no excerpt.
 *
 * Fallback image:
 * - Site brand logo URL (org_logo) if no override image.
 *
 * Output:
 * - Single page node, not a full @graph:
 *   {
 *     "@context": "https://schema.org",
 *     "@type": ...,
 *     "@id": "{page_url}#webpage",
 *     "url": page_url,
 *     "name": post_title,
 *     "isPartOf": { "@id": "{site_url}#website" },
 *     "description": ...,
 *     "image": "https://...",
 *     "about": WebSite.about (Person/Org)
 *   }
 */
function be_schema_output_special_page_schema() {
    if ( be_schema_globally_disabled() ) {
        return;
    }

    if ( ! is_singular() ) {
        return;
    }

    if ( be_schema_is_disabled_for_current_page() ) {
        return;
    }

    $post = get_post();
    if ( ! $post ) {
        return;
    }

    $page_settings = get_post_meta( $post->ID, '_elementor_page_settings', true );
    if ( empty( $page_settings ) || ! is_array( $page_settings ) ) {
        return;
    }

    // Page must be explicitly enabled at the Elementor level.
    $enable_page = isset( $page_settings['be_schema_enable_page'] ) ? $page_settings['be_schema_enable_page'] : '';
    if ( $enable_page !== 'yes' ) {
        return;
    }

    $page_type = isset( $page_settings['be_schema_page_type'] ) ? $page_settings['be_schema_page_type'] : 'none';

    // Map internal type to Schema.org @type.
    $type_map = array(
        'contact'               => 'ContactPage',
        'about'                 => 'AboutPage',
        'privacy-policy'        => 'WebPage',
        'accessibility-statement' => 'WebPage',
    );

    if ( ! isset( $type_map[ $page_type ] ) ) {
        return; // No special schema type selected.
    }

    $schema_type = $type_map[ $page_type ];

    $site_url        = trailingslashit( home_url() );
    $page_url        = get_permalink( $post );
    $page_url        = $page_url ? $page_url : $site_url;
    $page_id         = $page_url . '#webpage';
    $page_name       = get_the_title( $post ) ?: get_bloginfo( 'name', 'display' );
    $entities        = be_schema_get_site_entities();
    $website         = isset( $entities['website'] ) && is_array( $entities['website'] ) ? $entities['website'] : null;
    $settings        = be_schema_engine_get_settings();
    $site_logo_url   = ! empty( $settings['org_logo'] ) ? $settings['org_logo'] : '';
    $site_tagline    = get_bloginfo( 'description', 'display' );
    $site_desc_clean = trim( wp_strip_all_tags( (string) $site_tagline ) );
    $site_desc_clean = $site_desc_clean !== '' ? $site_desc_clean : null;

    // Determine description.
    $override_enabled   = isset( $page_settings['be_schema_page_override_enable'] ) && $page_settings['be_schema_page_override_enable'] === 'yes';
    $override_desc_raw  = isset( $page_settings['be_schema_page_description'] ) ? (string) $page_settings['be_schema_page_description'] : '';
    $override_desc      = $override_enabled ? be_schema_normalize_text( $override_desc_raw ) : '';
    $post_excerpt       = has_excerpt( $post ) ? get_the_excerpt( $post ) : '';
    $post_excerpt_clean = be_schema_normalize_text( $post_excerpt );

    if ( $override_enabled && $override_desc !== '' ) {
        $description = $override_desc;
    } elseif ( $post_excerpt_clean !== '' ) {
        $description = $post_excerpt_clean;
    } elseif ( $site_desc_clean ) {
        $description = $site_desc_clean;
    } else {
        $description = '';
    }

    // Determine image URL.
    $override_image_url = '';

    if ( $override_enabled && ! empty( $page_settings['be_schema_page_image'] ) ) {
        $image_setting = $page_settings['be_schema_page_image'];

        // New format: Elementor MEDIA control stores an array: [ 'id' => int, 'url' => string ].
        if ( is_array( $image_setting ) ) {
            // Prefer explicit URL if present.
            if ( ! empty( $image_setting['url'] ) ) {
                $override_image_url = esc_url_raw( $image_setting['url'] );
            } elseif ( ! empty( $image_setting['id'] ) ) {
                // Fall back to resolving attachment ID to full-size image URL.
                $src = wp_get_attachment_image_src( (int) $image_setting['id'], 'full' );
                if ( is_array( $src ) && ! empty( $src[0] ) ) {
                    $override_image_url = esc_url_raw( $src[0] );
                }
            }
        }
        // Backward compatibility: if it somehow ended up as a bare string URL.
        elseif ( is_string( $image_setting ) ) {
            $override_image_url = esc_url_raw( $image_setting );
        }
    }

    if ( $override_image_url ) {
        $image_url = $override_image_url;
    } elseif ( $site_logo_url ) {
        $image_url = $site_logo_url;
    } else {
        $image_url = '';
    }

    // Build the page node.
    $node = array(
        '@context' => 'https://schema.org',
        '@type'    => $schema_type,
        '@id'      => $page_id,
        'url'      => $page_url,
        'name'     => $page_name,
        'isPartOf' => array(
            '@id' => $site_url . '#website',
        ),
    );

    if ( $description !== '' ) {
        $node['description'] = $description;
    }

    if ( $image_url !== '' ) {
        $node['image'] = $image_url;
    }

    // about -> mirror WebSite.about if present (Person/Org).
    if ( $website && isset( $website['about'] ) && is_array( $website['about'] ) ) {
        $node['about'] = $website['about'];
    }

    // Collect into debug graph (just the node).
    be_schema_debug_collect( $node );

    echo '<script type="application/ld+json">' . wp_json_encode( $node ) . '</script>' . "\n";
}
