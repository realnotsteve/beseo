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

        if ( 'image' === $widget_name ) {
            $element->add_control(
                'be_schema_image_logo',
                array(
                    'label'        => __( 'Mark as logo', 'beseo' ),
                    'type'         => Controls_Manager::SWITCHER,
                    'label_on'     => __( 'Yes', 'beseo' ),
                    'label_off'    => __( 'No', 'beseo' ),
                    'return_value' => 'yes',
                    'default'      => '',
                    'condition'    => array(
                        'be_schema_enable_widget' => 'yes',
                    ),
                    'description'  => __( 'Treat this image as a logo in schema.', 'beseo' ),
                )
            );

            $element->add_control(
                'be_schema_image_entity',
                array(
                    'label'        => __( 'Mark as entity image', 'beseo' ),
                    'type'         => Controls_Manager::SWITCHER,
                    'label_on'     => __( 'Yes', 'beseo' ),
                    'label_off'    => __( 'No', 'beseo' ),
                    'return_value' => 'yes',
                    'default'      => '',
                    'condition'    => array(
                        'be_schema_enable_widget' => 'yes',
                    ),
                    'description'  => __( 'Link this image to your Organisation/Person in schema.', 'beseo' ),
                )
            );
        }

        $element->add_control(
            'be_schema_widget_preview',
            array(
                'label' => '',
                'type'  => Controls_Manager::RAW_HTML,
                'raw'   => '<div class="be-schema-widget-preview">' .
                           '<button type="button" class="elementor-button elementor-button-default be-schema-preview-btn" disabled>' . esc_html__( 'Preview JSON', 'beseo' ) . '</button>' .
                           '<textarea class="large-text code be-schema-preview-json" rows="6" readonly style="margin-top:8px;"></textarea>' .
                           '<div class="be-schema-image-status" style="margin-top:6px; color:#2271b1; display:none;"></div>' .
                           '</div>',
                'content_classes' => 'be-schema-preview-control',
                'show_label'      => false,
                'condition'       => array(
                    'be_schema_enable_widget' => 'yes',
                ),
            )
        );

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
    $image        = isset( $settings['image'] ) && is_array( $settings['image'] ) ? $settings['image'] : array();
    $image_id     = isset( $image['id'] ) ? (int) $image['id'] : 0;
    $force_logo   = ( isset( $settings['be_schema_image_logo'] ) && 'yes' === $settings['be_schema_image_logo'] );
    $force_entity = ( isset( $settings['be_schema_image_entity'] ) && 'yes' === $settings['be_schema_image_entity'] );

    $flags = array(
        'force_logo'   => $force_logo,
        'force_entity' => $force_entity,
    );

    if ( $image_id > 0 ) {
        return be_schema_engine_build_image_object( $image_id, '#image', $flags );
    }

    if ( ! empty( $image['url'] ) ) {
        return be_schema_engine_build_image_object( $image['url'], '#image', $flags );
    }

    return null;
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

	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$page_url = get_permalink( $post );
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
    $parts = array_map( 'intval', $parts );

    if ( count( $parts ) === 3 ) {
        list( $hours, $minutes, $seconds ) = $parts;
    } elseif ( count( $parts ) === 2 ) {
        $hours   = 0;
        $minutes = $parts[0];
        $seconds = $parts[1];
    } else {
        return '';
    }

    return 'PT' . ( $hours ? $hours . 'H' : '' ) . ( $minutes ? $minutes . 'M' : '' ) . ( $seconds ? $seconds . 'S' : '' );
}

// Bootstrap Elementor integration.
add_action(
    'init',
    static function() {
        if ( did_action( 'elementor/loaded' ) ) {
            BE_Elementor_Schema_Plugin::init();
            return;
        }
        add_action(
            'elementor/loaded',
            function() {
                BE_Elementor_Schema_Plugin::init();
            }
        );
    },
    30
);

/**
 * Enqueue editor-only assets for Elementor panel preview.
 */
function be_schema_elementor_enqueue_editor_assets() {
    wp_register_script(
        'be-schema-elementor-preview',
        false,
        array( 'jquery', 'elementor-editor' ),
        BE_SCHEMA_ENGINE_VERSION,
        true
    );
    wp_enqueue_script( 'be-schema-elementor-preview' );

    wp_add_inline_script(
        'be-schema-elementor-preview',
        '(function($){' .
        'var data = {' .
            'ajaxurl: ' . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ',' .
            'nonce: ' . wp_json_encode( wp_create_nonce( 'be_schema_preview_elementor' ) ) .
        '};' .
        'function bindPreview(panel, model, type){' .
            'var $panel = panel.$el;' .
            'var $btn = $panel.find(".be-schema-preview-btn");' .
            'var $textarea = $panel.find(".be-schema-preview-json");' .
            'var $status = $panel.find(".be-schema-image-status");' .
            'if(!$btn.length || !$textarea.length){return;}' .
            'var settingsModel = model.get("settings");' .
            'function getImage(){ if(!settingsModel){ return null; } if(settingsModel.get){ return settingsModel.get("image"); } if(settingsModel.attributes){ return settingsModel.attributes.image; } return null; }' .
            'function hasImage(){ var img = getImage() || {}; var idOk = img.id && parseInt(img.id,10) > 0; var urlOk = img.url && img.url !== "" && !/placeholder|dummy|transparent/i.test(img.url); return idOk || urlOk; }' .
            'function sync(){ var enabled = hasImage(); if($status.length){ $status.text(enabled ? "Image detected" : "No image selected").css("display","block"); } if(!enabled){$textarea.val("");} $btn.prop("disabled", !enabled); }' .
            'setTimeout(sync, 50);' .
            '$panel.on("input change", "[data-setting=\'be_schema_enable_widget\'], [data-setting=\'image\'], input, select, textarea", sync);' .
            'if(settingsModel && settingsModel.on){ settingsModel.on("change:image", sync); settingsModel.on("change", sync); }' .
            'model.on("change:image", sync);' .
            'model.on("change", sync);' .
            '$panel.find(".elementor-panel-navigation-tab[data-tab=\'advanced\']").on("click", function(){ setTimeout(sync, 50); });' .
            '$btn.off("click.beSchemaPreview").on("click.beSchemaPreview", function(e){ e.preventDefault(); if($btn.is(":disabled")) return; if(!hasImage()){ $textarea.val("Select an image to preview."); return; } if(!settingsModel){ $textarea.val("No settings."); return; } var attrs = settingsModel.attributes || {}; $.post(data.ajaxurl, {action:"be_schema_preview_elementor", nonce:data.nonce, widget_type:type, settings:attrs}, function(resp){ if(resp && resp.success && resp.data && resp.data.json){ $textarea.val(resp.data.json); } else { var msg = resp && resp.data && resp.data.message ? resp.data.message : "Preview unavailable."; $textarea.val(msg); } }).fail(function(){ $textarea.val("Preview failed."); }); });' .
        '}' .
        'elementor.hooks.addAction("panel/open_editor/widget/image", function(panel, model){ if(window.console){ console.log("Editor Opened"); } bindPreview(panel, model, "image"); var settings = model.get("settings"); if(settings && settings.on){ settings.on("change:image", function(changedModel){ var value = changedModel.get("image"); if(window.console){ console.log("New image selected:", value); } }); } });' .
        'elementor.hooks.addAction("panel/open_editor/widget/video-playlist", function(panel, model){ bindPreview(panel, model, "video-playlist"); });' .
        'elementor.hooks.addAction("panel/open_editor/widget/video_playlist", function(panel, model){ bindPreview(panel, model, "video_playlist"); });' .
        '})(jQuery);'
    );
}
add_action( 'elementor/editor/after_enqueue_scripts', 'be_schema_elementor_enqueue_editor_assets' );

/**
 * AJAX handler to preview schema JSON for Elementor widgets.
 */
function be_schema_preview_elementor() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'beseo' ) ), 403 );
    }
    check_ajax_referer( 'be_schema_preview_elementor', 'nonce' );

    $widget_type = isset( $_POST['widget_type'] ) ? sanitize_key( wp_unslash( $_POST['widget_type'] ) ) : '';
    $settings    = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? $_POST['settings'] : array();

    $node = null;
    if ( in_array( $widget_type, array( 'image', 'video-playlist', 'video_playlist' ), true ) ) {
        if ( 'image' === $widget_type ) {
            $node = be_schema_elementor_build_imageobject_node( $settings );
        } else {
            $nodes = array();
            be_schema_elementor_build_videoplaylist_nodes( $settings, $nodes );
            if ( $nodes ) {
                $node = count( $nodes ) === 1 ? $nodes[0] : $nodes;
            }
        }
    }

    if ( ! $node ) {
        wp_send_json_error( array( 'message' => __( 'No schema available for this widget.', 'beseo' ) ) );
    }

    wp_send_json_success(
        array(
            'json' => wp_json_encode( $node, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
        )
    );
}
add_action( 'wp_ajax_be_schema_preview_elementor', 'be_schema_preview_elementor' );
