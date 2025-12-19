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
 *     'name'       => string|null,
 *     'isLogo'     => bool|null,
 *     'about'      => array|null,
 *   )
 *
 * @param int|string $image Media attachment ID or URL.
 * @param string     $id_suffix Optional suffix for @id.
 * @param array      $flags Optional flags: force_logo, force_entity.
 * @return array|null
 */
if ( ! function_exists( 'be_schema_engine_build_image_object' ) ) {
    function be_schema_engine_build_image_object( $image, $id_suffix = '#image', $flags = array() ) {
        $url    = '';
        $width  = null;
        $height = null;
        $has_alt = false;
        $force_logo   = ! empty( $flags['force_logo'] );
        $force_entity = ! empty( $flags['force_entity'] );
        $is_logo      = false;
        $is_entity    = false;
        $creator_type      = 'Person';

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

                $logo_flag   = get_post_meta( $attachment_id, '_be_schema_media_logo', true );
                $entity_flag = get_post_meta( $attachment_id, '_be_schema_media_entity', true );
                $is_logo     = $force_logo || ( '' !== $logo_flag && false !== $logo_flag && '0' !== $logo_flag );
                $is_entity   = $force_entity || ( '' !== $entity_flag && false !== $entity_flag && '0' !== $entity_flag );
            }
        } elseif ( is_string( $image ) ) {
            $url = esc_url_raw( $image );
            $is_logo   = $force_logo;
            $is_entity = $force_entity;
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
                $title      = get_the_title( $attachment_id );
                $desc_post  = get_post( $attachment_id );
                $desc_field = ( $desc_post && ! empty( $desc_post->post_content ) ) ? $desc_post->post_content : '';
                $candidate  = '';
                $has_alt    = ( '' !== trim( (string) $alt ) );
                $creator_raw         = get_post_meta( $attachment_id, '_be_schema_creator_name', true );
                $creator_type_raw    = get_post_meta( $attachment_id, '_be_schema_creator_type', true );
                $creator_url_raw     = get_post_meta( $attachment_id, '_be_schema_creator_url', true );
                $creator_enabled_raw = get_post_meta( $attachment_id, '_be_schema_creator_enabled', true );
                $creator_url_enabled_raw = get_post_meta( $attachment_id, '_be_schema_creator_url_enabled', true );
                $creator_override    = is_string( $creator_raw ) ? trim( $creator_raw ) : '';
                $creator_url         = is_string( $creator_url_raw ) ? trim( $creator_url_raw ) : '';
                $creator_enabled     = ( '' === $creator_enabled_raw ) ? ( '' !== $creator_override ) : ( '1' === $creator_enabled_raw );
                $creator_url_enabled = ( '' === $creator_url_enabled_raw ) ? ( '' !== $creator_url ) : ( '1' === $creator_url_enabled_raw );
                if ( $creator_type_raw && is_string( $creator_type_raw ) ) {
                    $creator_type = in_array( $creator_type_raw, array( 'Person', 'Organisation', 'Organization' ), true ) ? $creator_type_raw : 'Person';
                }

                // Description precedence: caption, description field, alt.
                $desc_sources = array( $caption, $desc_field, $alt );
                foreach ( $desc_sources as $desc_source ) {
                    if ( empty( $desc_source ) ) {
                        continue;
                    }
                    $candidate = wp_strip_all_tags( (string) $desc_source );
                    $candidate = preg_replace( '/\s+/', ' ', $candidate );
                    $candidate = trim( $candidate );
                    if ( '' !== $candidate ) {
                        $node['description'] = $candidate;
                        break;
                    }
                }

                if ( $caption ) {
                    $node['caption'] = wp_strip_all_tags( (string) $caption );
                }

                if ( $title ) {
                    $node['name'] = wp_strip_all_tags( (string) $title );
                }

                $meta = wp_get_attachment_metadata( $attachment_id );
                if ( is_array( $meta ) && ! empty( $meta['image_meta'] ) && is_array( $meta['image_meta'] ) ) {
                    $image_meta = $meta['image_meta'];
                    if ( ! empty( $image_meta['copyright'] ) ) {
                        $node['license'] = $image_meta['copyright'];
                    }
                    if ( $creator_enabled && empty( $node['creator'] ) && ! empty( $image_meta['credit'] ) ) {
                        $node['creator'] = array(
                            '@type' => 'Person',
                            'name'  => $image_meta['credit'],
                        );
                    }
                }

                // Local override from media bin (only if provided).
                if ( $creator_enabled && empty( $node['creator'] ) && ! empty( $creator_override ) ) {
                    $creator_type_schema = ( 'Organisation' === $creator_type || 'Organization' === $creator_type ) ? 'Organization' : 'Person';
                    $node['creator'] = array(
                        '@type' => $creator_type_schema,
                        'name'  => wp_strip_all_tags( (string) $creator_override ),
                    );
                    if ( $creator_url_enabled && $creator_url ) {
                        $node['creator']['url'] = $creator_url;
                    }
                }
            }
        }

        // Require an alt/description-like value; otherwise skip emitting.
        if ( empty( $node['description'] ) && empty( $node['caption'] ) && empty( $has_alt ) ) {
            return null;
        }

        if ( $is_logo ) {
            $node['@id']    = $url . '#logo';
        }

        if ( $is_entity ) {
            $org_id    = be_schema_id( 'organisation' );
            $person_id = be_schema_id( 'person' );
            $about_ref = $org_id ? $org_id : $person_id;
            if ( $about_ref ) {
                $node['about'] = array(
                    array( '@id' => $about_ref ),
                );
            }
        }

        // Apply global author (website entity or override) when no per-image creator is set.
        if ( empty( $node['creator'] ) ) {
            $settings    = be_schema_engine_get_settings();
            $author_mode = isset( $settings['global_author_mode'] ) ? $settings['global_author_mode'] : 'website';

            if ( 'override' === $author_mode && ! empty( $settings['global_author_name'] ) ) {
                $override_type = isset( $settings['global_author_type'] ) ? $settings['global_author_type'] : 'Person';
                $override_type = in_array( $override_type, array( 'Person', 'Organisation' ), true ) ? $override_type : 'Person';
                $override_type = ( 'Organisation' === $override_type ) ? 'Organization' : 'Person';
                $node['creator'] = array(
                    '@type' => $override_type,
                    'name'  => wp_strip_all_tags( (string) $settings['global_author_name'] ),
                );
                if ( ! empty( $settings['global_author_url'] ) ) {
                    $node['creator']['url'] = $settings['global_author_url'];
                }
            } else {
                $entities = be_schema_get_site_entities();
                $identity = isset( $settings['site_identity_mode'] ) ? $settings['site_identity_mode'] : 'publisher';
                $identity = in_array( $identity, array( 'person', 'organisation', 'publisher' ), true ) ? $identity : 'publisher';
                $key      = ( 'organisation' === $identity ) ? 'organization' : $identity;
                $entity   = isset( $entities[ $key ] ) ? $entities[ $key ] : null;

                if ( $entity && is_array( $entity ) && empty( $entity['name'] ) ) {
                    $entity = isset( $entities['organization'] ) ? $entities['organization'] : ( $entities['person'] ?? $entity );
                }

                if ( $entity && is_array( $entity ) ) {
                    $creator = array();
                    if ( ! empty( $entity['@type'] ) ) {
                        $creator['@type'] = $entity['@type'];
                    }
                    if ( ! empty( $entity['name'] ) ) {
                        $creator['name'] = $entity['name'];
                    }
                    if ( ! empty( $entity['url'] ) ) {
                        $creator['url'] = $entity['url'];
                    }
                    if ( ! empty( $entity['@id'] ) ) {
                        $creator['@id'] = $entity['@id'];
                    }
                    if ( $creator ) {
                        $node['creator'] = $creator;
                    }
                }
            }
        }

        // Fallback creator to attachment author.
        if ( empty( $node['creator'] ) ) {
            $author_id   = is_numeric( $image ) ? get_post_field( 'post_author', (int) $image ) : 0;
            $author_name = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';
            if ( $author_name ) {
                $node['creator'] = array(
                    '@type' => 'Person',
                    'name'  => $author_name,
                );
            }
        }

        return $node;
    }
}

// Media library: add a per-image creator override field.
if ( is_admin() ) {
    add_filter(
        'attachment_fields_to_edit',
        static function( $form_fields, $post ) {
            if ( empty( $post ) || ! isset( $post->ID ) ) {
                return $form_fields;
            }

            $creator_name        = get_post_meta( $post->ID, '_be_schema_creator_name', true );
            $creator_type        = get_post_meta( $post->ID, '_be_schema_creator_type', true );
            $creator_url         = get_post_meta( $post->ID, '_be_schema_creator_url', true );
            $creator_enabled_raw = get_post_meta( $post->ID, '_be_schema_creator_enabled', true );
            $creator_url_enabled_raw = get_post_meta( $post->ID, '_be_schema_creator_url_enabled', true );
            $creator_enabled     = ( '' === $creator_enabled_raw ) ? ( '' !== trim( (string) $creator_name ) ) : ( '1' === $creator_enabled_raw );
            $creator_url_enabled = ( '' === $creator_url_enabled_raw ) ? ( '' !== trim( (string) $creator_url ) ) : ( '1' === $creator_url_enabled_raw );
            $creator_type        = in_array( $creator_type, array( 'Person', 'Organisation' ), true ) ? $creator_type : 'Person';

            $settings = function_exists( 'be_schema_engine_get_settings' ) ? be_schema_engine_get_settings() : array();
            $global_author_mode = isset( $settings['global_author_mode'] ) ? $settings['global_author_mode'] : 'website';
            $global_author_mode = ( 'override' === $global_author_mode ) ? 'override' : 'website';
            $global_author_name = isset( $settings['global_author_name'] ) ? $settings['global_author_name'] : '';
            $global_author_url  = isset( $settings['global_author_url'] ) ? $settings['global_author_url'] : '';
            $global_author_type = isset( $settings['global_author_type'] ) ? $settings['global_author_type'] : 'Person';
            $global_author_type = in_array( $global_author_type, array( 'Person', 'Organisation' ), true ) ? $global_author_type : 'Person';

            $website_author_name = '';
            $website_author_url  = '';
            $website_author_type = '';

            if ( function_exists( 'be_schema_get_site_entities' ) ) {
                $site_entities = be_schema_get_site_entities();
                $identity      = isset( $settings['site_identity_mode'] ) ? $settings['site_identity_mode'] : 'publisher';
                $identity      = in_array( $identity, array( 'person', 'organisation', 'publisher' ), true ) ? $identity : 'publisher';
                $key           = ( 'organisation' === $identity ) ? 'organization' : $identity;
                $entity        = isset( $site_entities[ $key ] ) ? $site_entities[ $key ] : null;

                if ( $entity && is_array( $entity ) && empty( $entity['name'] ) ) {
                    $entity = isset( $site_entities['organization'] ) ? $site_entities['organization'] : ( $site_entities['person'] ?? $entity );
                }

                if ( $entity && is_array( $entity ) ) {
                    $entity_type = isset( $entity['@type'] ) ? $entity['@type'] : '';
                    if ( 'Organization' === $entity_type || 'Organisation' === $entity_type ) {
                        $website_author_type = 'Organisation';
                    } elseif ( 'Person' === $entity_type ) {
                        $website_author_type = 'Person';
                    }
                    if ( ! empty( $entity['name'] ) ) {
                        $website_author_name = $entity['name'];
                    }
                    if ( ! empty( $entity['url'] ) ) {
                        $website_author_url = $entity['url'];
                    }
                }
            }

            $global_display_name = ( 'override' === $global_author_mode ) ? $global_author_name : $website_author_name;
            $global_display_url  = ( 'override' === $global_author_mode ) ? $global_author_url : $website_author_url;
            $global_display_type = ( 'override' === $global_author_mode ) ? $global_author_type : ( $website_author_type ? $website_author_type : 'Person' );

            $creator_display_name = $creator_enabled ? $creator_name : $global_display_name;
            $creator_display_url  = $creator_enabled ? $creator_url : $global_display_url;
            $creator_display_type = $creator_enabled ? $creator_type : $global_display_type;
            $creator_display_url_enabled = $creator_enabled ? $creator_url_enabled : ( '' !== trim( (string) $global_display_url ) );

            $creator_name_missing = '' === trim( (string) $creator_name );
            $global_name_missing  = '' === trim( (string) $global_display_name );
            $warning_message      = '';
            if ( $creator_enabled ) {
                if ( $creator_name_missing ) {
                    $warning_message = __( 'Creator Override is enabled but Creator Name is empty. Image schema will not be produced until the fields have values.', 'beseo' );
                }
            } else {
                if ( $global_name_missing && $creator_name_missing ) {
                    $warning_message = __( 'No Global Author value is available and Creator Override is off. Image schema will not be produced until the fields have values.', 'beseo' );
                }
            }

            $box_html  = '<tr class="be-schema-creator-box-row"><td colspan="2" style="padding-right:0; display:flex;">';
            $box_html .= sprintf(
                '<div class="be-schema-creator-box" style="border:1px solid #ccd0d4; padding:10px; margin:6px 0; background:#fff; width:100%%; box-sizing:border-box;" data-global-name="%1$s" data-global-type="%2$s" data-global-url="%3$s" data-override-name="%4$s" data-override-url="%5$s" data-override-type="%6$s">',
                esc_attr( $global_display_name ),
                esc_attr( $global_display_type ),
                esc_attr( $global_display_url ),
                esc_attr( $creator_name ),
                esc_attr( $creator_url ),
                esc_attr( $creator_type )
            );
            $box_html .= '<div style="font-weight:600; margin-bottom:8px;">' . esc_html__( 'BE SEO', 'beseo' ) . '</div>';
            $box_html .= '<div style="margin-bottom:8px;">';
            $box_html .= sprintf(
                '<label style="display:inline-flex; align-items:center; gap:6px;"><input type="checkbox" class="be-schema-creator-enabled" name="attachments[%1$d][be_schema_creator_enabled]" value="1" %2$s /> %3$s</label>',
                (int) $post->ID,
                $creator_enabled ? 'checked="checked"' : '',
                esc_html__( 'Creator Override', 'beseo' )
            );
            $box_html .= sprintf(
                ' <button type="button" class="button be-schema-creator-copy-global" style="margin-left:10px;">%s</button>',
                esc_html__( 'Copy Global', 'beseo' )
            );
            $box_html .= '</div><hr style="margin:8px 0;" />';
            if ( '' !== $warning_message ) {
                $box_html .= sprintf(
                    '<div class="notice notice-error inline be-schema-creator-warning" style="margin:8px 0;"><p>%s</p></div>',
                    esc_html( $warning_message )
                );
            } else {
                $box_html .= '<div class="notice notice-error inline be-schema-creator-warning" style="margin:8px 0; display:none;"></div>';
            }
            $box_html .= '<div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:8px;">';
            $box_html .= '<label style="min-width:120px;">' . esc_html__( 'Creator Name', 'beseo' ) . '</label>';
            $box_html .= sprintf(
                '<input type="text" class="text be-schema-creator-name" name="attachments[%1$d][be_schema_creator_name]" value="%2$s" data-override-value="%3$s" data-global-value="%4$s" %5$s style="flex:1 1 320px; min-width:220px;" />',
                (int) $post->ID,
                esc_attr( $creator_display_name ),
                esc_attr( $creator_name ),
                esc_attr( $global_display_name ),
                $creator_enabled ? '' : 'disabled="disabled"'
            );
            $box_html .= '</div>';
            $box_html .= '<div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:8px;">';
            $box_html .= '<span style="min-width:120px;">' . esc_html__( 'Creator Type', 'beseo' ) . '</span>';
            $box_html .= sprintf(
                '<label style="margin-right:10px;"><input type="radio" class="be-schema-creator-type" name="attachments[%1$d][be_schema_creator_type]" value="Person" %2$s %4$s /> %5$s</label>' .
                '<label><input type="radio" class="be-schema-creator-type" name="attachments[%1$d][be_schema_creator_type]" value="Organisation" %3$s %4$s /> %6$s</label>',
                (int) $post->ID,
                ( 'Person' === $creator_display_type ) ? 'checked="checked"' : '',
                ( 'Organisation' === $creator_display_type ) ? 'checked="checked"' : '',
                $creator_enabled ? '' : 'disabled="disabled"',
                esc_html__( 'Person', 'beseo' ),
                esc_html__( 'Organisation', 'beseo' )
            );
            $box_html .= '</div>';
            $box_html .= '<div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">';
            $box_html .= '<label style="min-width:120px;">' . esc_html__( 'Creator URL', 'beseo' ) . '</label>';
            $box_html .= sprintf(
                '<input type="checkbox" class="be-schema-creator-url-enabled" name="attachments[%1$d][be_schema_creator_url_enabled]" value="1" %2$s %3$s data-override-enabled="%4$s" data-global-enabled="%5$s" aria-label="%6$s" />',
                (int) $post->ID,
                $creator_display_url_enabled ? 'checked="checked"' : '',
                $creator_enabled ? '' : 'disabled="disabled"',
                $creator_url_enabled ? '1' : '0',
                ( '' !== trim( (string) $global_display_url ) ) ? '1' : '0',
                esc_attr__( 'Include creator URL', 'beseo' )
            );
            $box_html .= sprintf(
                '<input type="url" class="text be-schema-creator-url" name="attachments[%1$d][be_schema_creator_url]" value="%2$s" data-override-value="%3$s" data-global-value="%4$s" %5$s style="flex:1 1 320px; min-width:220px;" />',
                (int) $post->ID,
                esc_attr( $creator_display_url ),
                esc_attr( $creator_url ),
                esc_attr( $global_display_url ),
                ( $creator_enabled && $creator_url_enabled ) ? '' : 'disabled="disabled"'
            );
            $box_html .= '</div>';
            $box_html .= '</div></td></tr>';

            $form_fields['be_schema_creator_box'] = array(
                'tr' => $box_html,
            );

            return $form_fields;
        },
        10,
        2
    );

    add_filter(
        'attachment_fields_to_save',
        static function( $post, $attachment ) {
            $creator_enabled = isset( $attachment['be_schema_creator_enabled'] ) ? '1' : '0';
            update_post_meta( $post['ID'], '_be_schema_creator_enabled', $creator_enabled );

            if ( isset( $attachment['be_schema_creator_name'] ) ) {
                $value = sanitize_text_field( $attachment['be_schema_creator_name'] );
                if ( '' !== $value ) {
                    update_post_meta( $post['ID'], '_be_schema_creator_name', $value );
                } else {
                    delete_post_meta( $post['ID'], '_be_schema_creator_name' );
                }
            }

            if ( isset( $attachment['be_schema_creator_type'] ) ) {
                $type = sanitize_text_field( $attachment['be_schema_creator_type'] );
                $type = in_array( $type, array( 'Person', 'Organisation' ), true ) ? $type : 'Person';
                update_post_meta( $post['ID'], '_be_schema_creator_type', $type );
            }

            $creator_url_enabled = isset( $attachment['be_schema_creator_url_enabled'] ) ? '1' : '0';
            update_post_meta( $post['ID'], '_be_schema_creator_url_enabled', $creator_url_enabled );

            if ( isset( $attachment['be_schema_creator_url'] ) ) {
                $url = esc_url_raw( $attachment['be_schema_creator_url'] );
                if ( '' !== $url ) {
                    update_post_meta( $post['ID'], '_be_schema_creator_url', $url );
                } else {
                    delete_post_meta( $post['ID'], '_be_schema_creator_url' );
                }
            }
            return $post;
        },
        10,
        2
    );

    add_action(
        'admin_print_footer_scripts',
        static function() {
            ?>
            <script>
            jQuery(function($){
                var warningOverrideMsg = '<?php echo esc_js( __( 'Creator Override is enabled but Creator Name is empty. Image schema will not be produced until the fields have values.', 'beseo' ) ); ?>';
                var warningGlobalMsg = '<?php echo esc_js( __( 'No Global Author value is available and Creator Override is off. Image schema will not be produced until the fields have values.', 'beseo' ) ); ?>';
                var copyGlobalMissingMsg = '<?php echo esc_js( __( 'Global author is empty.', 'beseo' ) ); ?>';

                function isOverrideEnabled($table){
                    return $table.find('.be-schema-creator-enabled').is(':checked');
                }

                function applyCreatorValues($table, useOverride){
                    var $box = $table.find('.be-schema-creator-box');
                    var globalName = ($box.data('globalName') || '').toString();
                    var globalType = ($box.data('globalType') || 'Person').toString();
                    var globalUrl = ($box.data('globalUrl') || '').toString();
                    var overrideType = ($box.data('overrideType') || 'Person').toString();
                    var nextType = useOverride ? overrideType : globalType;

                    $table.find('.be-schema-creator-name').each(function(){
                        var $field = $(this);
                        var val = useOverride ? $field.data('overrideValue') : $field.data('globalValue');
                        if (typeof val !== 'undefined') {
                            $field.val(val);
                        }
                    });

                    $table.find('.be-schema-creator-type').prop('checked', false)
                        .filter('[value="' + nextType + '"]').prop('checked', true);

                    $table.find('.be-schema-creator-url').each(function(){
                        var $field = $(this);
                        var val = useOverride ? $field.data('overrideValue') : $field.data('globalValue');
                        if (typeof val !== 'undefined') {
                            $field.val(val);
                        }
                    });

                    var $urlToggle = $table.find('.be-schema-creator-url-enabled');
                    var urlEnabled = useOverride ? $urlToggle.data('overrideEnabled') : $urlToggle.data('globalEnabled');
                    urlEnabled = urlEnabled ? true : false;
                    $urlToggle.prop('checked', urlEnabled);
                }

                function updateCreatorWarning($table){
                    var $box = $table.find('.be-schema-creator-box');
                    var $warning = $box.find('.be-schema-creator-warning');
                    if(!$warning.length){ return; }
                    var overrideEnabled = isOverrideEnabled($table);
                    var globalName = ($box.data('globalName') || '').toString().trim();
                    var overrideName = ($table.find('.be-schema-creator-name').data('overrideValue') || '').toString().trim();
                    var message = '';

                    if (overrideEnabled) {
                        if (!overrideName) {
                            message = warningOverrideMsg;
                        }
                    } else {
                        if (!globalName && !overrideName) {
                            message = warningGlobalMsg;
                        }
                    }

                    if (message) {
                        $warning.html('<p>' + message + '</p>').show();
                    } else {
                        $warning.hide().empty();
                    }
                }

                function syncCreatorFields($table){
                    if(!$table || !$table.length){ return; }
                    var $enabled = $table.find('.be-schema-creator-enabled');
                    if(!$enabled.length){ return; }
                    var enabled = $enabled.is(':checked');
                    applyCreatorValues($table, enabled);
                    $table.find('.be-schema-creator-name, .be-schema-creator-type, .be-schema-creator-url-enabled').prop('disabled', !enabled);
                    var urlEnabled = enabled && $table.find('.be-schema-creator-url-enabled').is(':checked');
                    $table.find('.be-schema-creator-url').prop('disabled', !urlEnabled);
                    updateCreatorWarning($table);
                }

                function syncAll(){
                    $('.be-schema-creator-enabled').each(function(){
                        syncCreatorFields($(this).closest('table'));
                    });
                }

                $(document).on('change', '.be-schema-creator-enabled', function(){
                    syncCreatorFields($(this).closest('table'));
                });

                $(document).on('change', '.be-schema-creator-url-enabled', function(){
                    var $table = $(this).closest('table');
                    if (isOverrideEnabled($table)) {
                        $(this).data('overrideEnabled', $(this).is(':checked') ? 1 : 0);
                    }
                    syncCreatorFields($table);
                });

                $(document).on('input', '.be-schema-creator-name', function(){
                    var $table = $(this).closest('table');
                    if (isOverrideEnabled($table)) {
                        $(this).data('overrideValue', $(this).val());
                    }
                    updateCreatorWarning($table);
                });

                $(document).on('input', '.be-schema-creator-url', function(){
                    var $table = $(this).closest('table');
                    if (isOverrideEnabled($table)) {
                        $(this).data('overrideValue', $(this).val());
                    }
                });

                $(document).on('change', '.be-schema-creator-type', function(){
                    var $table = $(this).closest('table');
                    if (isOverrideEnabled($table)) {
                        $table.find('.be-schema-creator-box').data('overrideType', $(this).val());
                    }
                });

                $(document).on('click', '.be-schema-creator-copy-global', function(e){
                    e.preventDefault();
                    var $table = $(this).closest('table');
                    var $box = $table.find('.be-schema-creator-box');
                    var globalName = ($box.data('globalName') || '').toString();
                    var globalType = ($box.data('globalType') || 'Person').toString();
                    var globalUrl = ($box.data('globalUrl') || '').toString();
                    if (!globalName.trim()) {
                        alert(copyGlobalMissingMsg);
                        return;
                    }
                    $table.find('.be-schema-creator-enabled').prop('checked', true);
                    $box.data('overrideType', globalType);
                    $table.find('.be-schema-creator-name').data('overrideValue', globalName).val(globalName);
                    $table.find('.be-schema-creator-url').data('overrideValue', globalUrl).val(globalUrl);
                    var urlEnabled = globalUrl.trim() !== '' ? 1 : 0;
                    $table.find('.be-schema-creator-url-enabled').data('overrideEnabled', urlEnabled).prop('checked', !!urlEnabled);
                    $table.find('.be-schema-creator-type').prop('checked', false)
                        .filter('[value="' + globalType + '"]').prop('checked', true);
                    syncCreatorFields($table);
                });

                syncAll();
                $(document).ajaxComplete(function(){ syncAll(); });
            });
            </script>
            <?php
        }
    );
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
