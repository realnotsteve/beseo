<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Controls_Manager;

/**
 * Elementor integration for BE Schema Engine.
 *
 * - Adds page-level controls (Elementor Page Settings → Advanced → "Schema").
 * - Adds widget-level controls (Advanced tab → "Schema") for:
 *     - Core Image widget
 *     - Video Playlist widget
 */
class BE_Elementor_Schema_Plugin {

    /**
     * Bootstrap the integration.
     */
    public static function init() {
        // Page/document-level controls.
        add_action( 'elementor/documents/register_controls', array( __CLASS__, 'register_document_controls' ) );

        // Widget-level controls (Image, Video Playlist).
        add_action( 'elementor/element/after_section_end', array( __CLASS__, 'register_widget_controls' ), 10, 3 );
        // Targeted hooks for reliability across Elementor versions/sections.
        add_action( 'elementor/element/image/section_advanced/after_section_end', array( __CLASS__, 'register_widget_controls' ), 10, 3 );
        add_action( 'elementor/element/image/section_advanced/before_section_end', array( __CLASS__, 'register_widget_controls' ), 10, 3 );
        add_action( 'elementor/element/image/section_style/after_section_end', array( __CLASS__, 'register_widget_controls' ), 10, 3 );
        add_action( 'elementor/element/image/section_content/after_section_end', array( __CLASS__, 'register_widget_controls' ), 10, 3 );
        add_action( 'elementor/element/video-playlist/section_advanced/after_section_end', array( __CLASS__, 'register_widget_controls' ), 10, 3 );
        add_action( 'elementor/element/video_playlist/section_advanced/after_section_end', array( __CLASS__, 'register_widget_controls' ), 10, 3 );
    }

    /**
     * Add page-level controls to Elementor documents (post/page).
     *
     * @param \Elementor\Core\Base\Document $document
     */
    public static function register_document_controls( $document ) {
        // We only care about post/page-like documents.
        if ( ! method_exists( $document, 'get_post_type' ) ) {
            return;
        }
        $type = $document->get_post_type();
        if ( ! in_array( $type, array( 'post', 'page' ), true ) ) {
            return;
        }

        $document->start_controls_section(
            'be_schema_section_page',
            array(
                'label' => __( 'Schema', 'beseo' ),
                'tab'   => Controls_Manager::TAB_SETTINGS,
            )
        );

        $document->add_control(
            'be_schema_enable_page',
            array(
                'label'        => __( 'Enable schema for this page', 'beseo' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'beseo' ),
                'label_off'    => __( 'No', 'beseo' ),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __( 'Required to allow page-specific schema (special pages, Elementor widgets).', 'beseo' ),
            )
        );

        $document->add_control(
            'be_schema_page_type',
            array(
                'label'     => __( 'Page type', 'beseo' ),
                'type'      => Controls_Manager::SELECT,
                'options'   => array(
                    'none'                  => __( 'None (regular page)', 'beseo' ),
                    'contact'               => __( 'Contact page', 'beseo' ),
                    'about'                 => __( 'About page', 'beseo' ),
                    'privacy-policy'        => __( 'Privacy policy', 'beseo' ),
                    'accessibility-statement' => __( 'Accessibility statement', 'beseo' ),
                ),
                'default'   => 'none',
                'condition' => array(
                    'be_schema_enable_page' => 'yes',
                ),
            )
        );

        $document->add_control(
            'be_schema_page_override_enable',
            array(
                'label'        => __( 'Override description & image', 'beseo' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'beseo' ),
                'label_off'    => __( 'No', 'beseo' ),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __( 'Override the default description and image used for special page schema.', 'beseo' ),
                'condition'    => array(
                    'be_schema_enable_page' => 'yes',
                    'be_schema_page_type!'  => 'none',
                ),
            )
        );

        $document->add_control(
            'be_schema_page_description',
            array(
                'label'       => __( 'Schema description override', 'beseo' ),
                'type'        => Controls_Manager::TEXTAREA,
                'rows'        => 3,
                'placeholder' => __( 'Short description for schema.org markup…', 'beseo' ),
                'condition'   => array(
                    'be_schema_enable_page'         => 'yes',
                    'be_schema_page_type!'          => 'none',
                    'be_schema_page_override_enable' => 'yes',
                ),
            )
        );

        $document->add_control(
            'be_schema_page_image',
            array(
                'label'     => __( 'Schema image override', 'beseo' ),
                'type'      => Controls_Manager::MEDIA,
                'dynamic'   => array( 'active' => false ),
                'condition' => array(
                    'be_schema_enable_page'         => 'yes',
                    'be_schema_page_type!'          => 'none',
                    'be_schema_page_override_enable' => 'yes',
                ),
            )
        );

        $document->end_controls_section();
    }

    /**
     * Add widget-level controls for the Image and Video Playlist widgets
     * under the Advanced tab ("Schema" section).
     *
     * @param \Elementor\Element_Base $element
     * @param string                  $section_id
     * @param array                   $args
     */
    public static function register_widget_controls( $element, $section_id, $args ) {
        // Allow all sections (we guard by widget and duplicate control below).

        $widget_name = $element->get_name();

        // Only support core Image + Video Playlist widgets.
        if ( ! in_array( $widget_name, array( 'image', 'video-playlist', 'video_playlist' ), true ) ) {
            return;
        }

        // Avoid double registration if multiple hooks fire.
        $controls = $element->get_controls();
        if ( isset( $controls['be_schema_enable_widget'] ) ) {
            return;
        }

        $element->start_controls_section(
            'be_schema_section_widget',
            array(
                'label' => __( 'Schema', 'beseo' ),
                'tab'   => Controls_Manager::TAB_ADVANCED,
            )
        );

        $element->add_control(
            'be_schema_enable_widget',
            array(
                'label'        => __( 'Enable schema for this widget', 'beseo' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'beseo' ),
                'label_off'    => __( 'No', 'beseo' ),
                'return_value' => 'yes',
                'default'      => '',
            )
        );

        // Optional per-widget description for Image widgets.
        if ( 'image' === $widget_name ) {
            $element->add_control(
                'be_schema_image_description',
                array(
                    'label'       => __( 'Schema description', 'beseo' ),
                    'type'        => Controls_Manager::TEXTAREA,
                    'rows'        => 2,
                    'placeholder' => __( 'Optional description for ImageObject schema…', 'beseo' ),
                    'condition'   => array(
                        'be_schema_enable_widget' => 'yes',
                    ),
                )
            );
        }

        $element->end_controls_section();
    }
}

/**
 * Output Elementor widget-level schema (ImageObject, VideoObject, ItemList).
 *
 * Skips if:
 * - Elementor schema is globally disabled (be_schema_elementor_disabled()).
 * - Not in a singular eligible context.
 * - Page is disabled (be_schema_is_disabled_for_current_page()).
 * - Admin, feed, AJAX, REST, or embed context.
 */
function be_schema_output_elementor_schema() {
    if ( be_schema_elementor_disabled() ) {
        return;
    }

    if ( ! is_singular() || ! be_schema_is_singular_eligible() ) {
        return;
    }

    if ( be_schema_is_disabled_for_current_page() ) {
        return;
    }

    if ( is_admin() || is_feed() || is_robots() || is_embed() || wp_doing_ajax() ) {
        return;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }

    if ( function_exists( 'be_schema_is_dry_run' ) && be_schema_is_dry_run() ) {
        if ( function_exists( 'be_schema_log_dry_run' ) ) {
            be_schema_log_dry_run(
                'elementor',
                array(
                    'post_id' => get_the_ID(),
                )
            );
        }
        return;
    }

    if ( ! class_exists( '\Elementor\Plugin' ) ) {
        return;
    }

    $post_id = get_the_ID();
    if ( ! $post_id ) {
        return;
    }

    $plugin   = \Elementor\Plugin::$instance;
    $document = $plugin->documents->get_doc_for_frontend( $post_id );

    if ( ! $document ) {
        return;
    }

    $elements_data = $document->get_elements_data();
    if ( empty( $elements_data ) || ! is_array( $elements_data ) ) {
        return;
    }

    $nodes    = array();
    $entities = be_schema_get_site_entities();

    // Include site-level logo and publisher logo at the top of the graph, if present.
    if ( isset( $entities['logo'] ) && is_array( $entities['logo'] ) && ! empty( $entities['logo'] ) ) {
        $nodes[] = $entities['logo'];
    }

    if ( isset( $entities['publisher_logo'] ) && is_array( $entities['publisher_logo'] ) && ! empty( $entities['publisher_logo'] ) ) {
        $nodes[] = $entities['publisher_logo'];
    }

    be_schema_elementor_collect_nodes_from_elements( $elements_data, $nodes );

    if ( empty( $nodes ) ) {
        return;
    }

    // Collect for debug logging.
    be_schema_debug_collect( $nodes );

    $output = array(
        '@context' => 'https://schema.org',
        '@graph'   => $nodes,
    );

    echo '<script type="application/ld+json">' . wp_json_encode( $output ) . '</script>' . "\n";
}

/**
 * Recursively walk Elementor element tree and collect schema nodes
 * for supported widgets.
 *
 * @param array $elements Element data array from Elementor.
 * @param array $nodes    Reference to list of nodes to append to.
 */
function be_schema_elementor_collect_nodes_from_elements( $elements, array &$nodes ) {
    foreach ( $elements as $element ) {
        if ( ! is_array( $element ) ) {
            continue;
        }

        $el_type = isset( $element['elType'] ) ? $element['elType'] : '';

        if ( 'widget' === $el_type ) {
            $widget_type = isset( $element['widgetType'] ) ? $element['widgetType'] : '';
            $settings    = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();

            if ( in_array( $widget_type, array( 'image', 'video-playlist', 'video_playlist' ), true ) ) {
                $enabled = isset( $settings['be_schema_enable_widget'] ) && $settings['be_schema_enable_widget'] === 'yes';

                if ( $enabled ) {
                    if ( 'image' === $widget_type ) {
                        $node = be_schema_elementor_build_imageobject_node( $settings );
                        if ( $node ) {
                            $nodes[] = $node;
                        }
                    } else {
                        // Video playlist widget.
                        be_schema_elementor_build_videoplaylist_nodes( $settings, $nodes );
                    }
                }
            }
        }

        // Recurse into children if present.
        if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
            be_schema_elementor_collect_nodes_from_elements( $element['elements'], $nodes );
        }
    }
}

/**
 * Build an ImageObject node from Elementor Image widget settings.
 *
 * @param array $settings
 * @return array|null
 */
function be_schema_elementor_build_imageobject_node( array $settings ) {
    $image      = isset( $settings['image'] ) && is_array( $settings['image'] ) ? $settings['image'] : array();
    $image_id   = isset( $image['id'] ) ? (int) $image['id'] : 0;
    $image_url  = '';
    $width      = null;
    $height     = null;

    if ( $image_id > 0 ) {
        $src = wp_get_attachment_image_src( $image_id, 'full' );
        if ( is_array( $src ) && ! empty( $src[0] ) ) {
            $image_url = $src[0];
            $width     = isset( $src[1] ) ? (int) $src[1] : null;
            $height    = isset( $src[2] ) ? (int) $src[2] : null;
        }
    } elseif ( ! empty( $image['url'] ) ) {
        $image_url = $image['url'];
    }

    if ( '' === $image_url ) {
        return null;
    }

    // Description: prefer explicit schema description, then attachment alt.
    $description = '';

    if ( ! empty( $settings['be_schema_image_description'] ) ) {
        if ( function_exists( 'be_schema_normalize_text' ) ) {
            $description = be_schema_normalize_text( $settings['be_schema_image_description'], 320 );
        } else {
            $description = trim( wp_strip_all_tags( (string) $settings['be_schema_image_description'] ) );
        }
    } elseif ( $image_id > 0 ) {
        $alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
        if ( $alt ) {
            if ( function_exists( 'be_schema_normalize_text' ) ) {
                $description = be_schema_normalize_text( $alt, 320 );
            } else {
                $description = trim( wp_strip_all_tags( (string) $alt ) );
            }
        }
    }

    $node = array(
        '@type'      => 'ImageObject',
        'url'        => $image_url,
        'contentUrl' => $image_url,
    );

    if ( $width && $width > 0 ) {
        $node['width'] = $width;
    }

    if ( $height && $height > 0 ) {
        $node['height'] = $height;
    }

    if ( '' !== $description ) {
        $node['description'] = $description;
    }

    return $node;
}

/**
 * Build VideoObject + ItemList nodes from Elementor Video Playlist widget settings.
 *
 * @param array $settings
 * @param array $nodes    Reference to list of nodes to append to.
 */
function be_schema_elementor_build_videoplaylist_nodes( array $settings, array &$nodes ) {
    $items = array();

    if ( ! empty( $settings['videos'] ) && is_array( $settings['videos'] ) ) {
        $items = $settings['videos'];
    } elseif ( ! empty( $settings['playlist_items'] ) && is_array( $settings['playlist_items'] ) ) {
        $items = $settings['playlist_items'];
    }

    if ( empty( $items ) ) {
        return;
    }

    // Resolve the page URL safely.
	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return $nodes;
	}
	
	$page_url = get_permalink( $post );
	if ( ! $page_url ) {
		return $nodes;
	}
    
    if ( ! $page_url ) {
        $page_url = trailingslashit( home_url() );
    }

    $list_elements = array();

    $index = 0;
    foreach ( $items as $item ) {
        $index++;

        $title       = isset( $item['title'] ) ? trim( wp_strip_all_tags( (string) $item['title'] ) ) : '';
        $description = isset( $item['description'] ) ? (string) $item['description'] : '';
        $image       = isset( $item['image'] ) && is_array( $item['image'] ) ? $item['image'] : array();
        $video_url   = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
        $thumb_url   = isset( $image['url'] ) ? esc_url_raw( $image['url'] ) : '';
        $duration    = isset( $item['duration'] ) ? (string) $item['duration'] : '';

        if ( '' === $video_url && '' === $title ) {
            continue;
        }

        if ( function_exists( 'be_schema_normalize_text' ) ) {
            $description = be_schema_normalize_text( $description, 320 );
        } else {
            $description = trim( wp_strip_all_tags( $description ) );
        }

        $iso_duration = be_schema_elementor_duration_to_iso8601( $duration );

        $video_node = array(
            '@type' => 'VideoObject',
            'name'  => $title ?: $video_url,
        );

        if ( '' !== $description ) {
            $video_node['description'] = $description;
        }

        if ( '' !== $thumb_url ) {
            $video_node['thumbnailUrl'] = $thumb_url;
        }

        if ( '' !== $video_url ) {
            $video_node['contentUrl'] = $video_url;
            $video_node['embedUrl']   = $video_url;
        }

        if ( '' !== $iso_duration ) {
            $video_node['duration'] = $iso_duration;
        }

        $nodes[] = $video_node;

        $list_elements[] = array(
            '@type'    => 'ListItem',
            'position' => $index,
            'name'     => $title ?: $video_url,
            'url'      => $video_url ?: $page_url,
        );
    }

    if ( ! empty( $list_elements ) ) {
        $itemlist = array(
            '@type'           => 'ItemList',
            'itemListElement' => $list_elements,
        );

        $nodes[] = $itemlist;
    }
}

/**
 * Convert duration like "MM:SS" or "H:MM:SS" into ISO 8601 (PT#H#M#S).
 *
 * @param string $duration_str
 * @return string Empty string if parse fails.
 */
function be_schema_elementor_duration_to_iso8601( $duration_str ) {
    $duration_str = trim( (string) $duration_str );
    if ( '' === $duration_str ) {
        return '';
    }

    $parts = explode( ':', $duration_str );

    $h = 0;
    $m = 0;
    $s = 0;

    if ( 3 === count( $parts ) ) {
        $h = (int) $parts[0];
        $m = (int) $parts[1];
        $s = (int) $parts[2];
    } elseif ( 2 === count( $parts ) ) {
        $m = (int) $parts[0];
        $s = (int) $parts[1];
    } else {
        // Single number treated as seconds.
        $s = (int) $parts[0];
    }

    if ( $h < 0 ) {
        $h = 0;
    }
    if ( $m < 0 ) {
        $m = 0;
    }
    if ( $s < 0 ) {
        $s = 0;
    }

    if ( 0 === $h && 0 === $m && 0 === $s ) {
        return '';
    }

    $iso = 'PT';
    if ( $h > 0 ) {
        $iso .= $h . 'H';
    }
    if ( $m > 0 ) {
        $iso .= $m . 'M';
    }
    if ( $s > 0 ) {
        $iso .= $s . 'S';
    }

    return $iso;
}
