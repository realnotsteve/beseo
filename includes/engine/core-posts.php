<?php
/**
 * BE Schema Engine - Blog posts (BlogPosting).
 *
 * Responsibilities:
 * - Emit JSON-LD BlogPosting schema for eligible single posts.
 * - Emit a paired WebPage node whose @id is the post permalink.
 * - Respect:
 *     - Global disable via be_schema_globally_disabled().
 *     - Per-page / Elementor disable via be_schema_is_disabled_for_current_page().
 *     - Post-type eligibility via be_schema_is_singular_eligible().
 * - Use site-level entities (Person, Publisher/Organisation) via @id references.
 *
 * @package BE_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Per-post author override meta box.
 */
add_action(
	'add_meta_boxes',
	static function() {
		$screens = array( 'post', 'page' );
		foreach ( $screens as $screen ) {
			add_meta_box(
				'be-schema-author-meta',
				__( 'Schema Author', 'beseo' ),
				static function( $post ) {
					$mode = get_post_meta( $post->ID, '_be_schema_author_mode', true );
					$mode = $mode ? $mode : 'global';
					$type = get_post_meta( $post->ID, '_be_schema_author_type', true );
					$type = in_array( $type, array( 'Person', 'Organisation' ), true ) ? $type : 'Person';
					$name = get_post_meta( $post->ID, '_be_schema_author_name', true );
					$url  = get_post_meta( $post->ID, '_be_schema_author_url', true );
					wp_nonce_field( 'be_schema_author_meta', 'be_schema_author_meta_nonce' );
					?>
					<p>
						<label><input type="radio" name="be_schema_author_mode" value="global" <?php checked( 'global', $mode ); ?> /> <?php esc_html_e( 'Use Global author', 'beseo' ); ?></label><br />
						<label><input type="radio" name="be_schema_author_mode" value="override" <?php checked( 'override', $mode ); ?> /> <?php esc_html_e( 'Local Override', 'beseo' ); ?></label><br />
						<label><input type="radio" name="be_schema_author_mode" value="active_user" <?php checked( 'active_user', $mode ); ?> /> <?php esc_html_e( 'Use Active User', 'beseo' ); ?></label>
					</p>
					<p>
						<label for="be_schema_author_type"><?php esc_html_e( 'Author type', 'beseo' ); ?></label><br />
						<select name="be_schema_author_type" id="be_schema_author_type">
							<option value="Person" <?php selected( 'Person', $type ); ?>><?php esc_html_e( 'Person', 'beseo' ); ?></option>
							<option value="Organisation" <?php selected( 'Organisation', $type ); ?>><?php esc_html_e( 'Organisation', 'beseo' ); ?></option>
						</select>
					</p>
					<p>
						<label for="be_schema_author_name"><?php esc_html_e( 'Author name', 'beseo' ); ?></label><br />
						<input type="text" id="be_schema_author_name" name="be_schema_author_name" class="widefat" value="<?php echo esc_attr( $name ); ?>" />
					</p>
					<p>
						<label for="be_schema_author_url"><?php esc_html_e( 'Author URL', 'beseo' ); ?></label><br />
						<input type="url" id="be_schema_author_url" name="be_schema_author_url" class="widefat" value="<?php echo esc_attr( $url ); ?>" />
					</p>
					<p class="description"><?php esc_html_e( 'Fields above apply when Local Override is selected.', 'beseo' ); ?></p>
					<?php
				},
				$screen,
				'side',
				'low'
			);
		}
	}
);

add_action(
	'save_post',
	static function( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['be_schema_author_meta_nonce'] ) || ! wp_verify_nonce( $_POST['be_schema_author_meta_nonce'], 'be_schema_author_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$mode = isset( $_POST['be_schema_author_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_author_mode'] ) ) : 'global';
		if ( ! in_array( $mode, array( 'global', 'override', 'active_user' ), true ) ) {
			$mode = 'global';
		}
		update_post_meta( $post_id, '_be_schema_author_mode', $mode );

		$type = isset( $_POST['be_schema_author_type'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_author_type'] ) ) : 'Person';
		if ( ! in_array( $type, array( 'Person', 'Organisation' ), true ) ) {
			$type = 'Person';
		}
		update_post_meta( $post_id, '_be_schema_author_type', $type );

		$name = isset( $_POST['be_schema_author_name'] ) ? sanitize_text_field( wp_unslash( $_POST['be_schema_author_name'] ) ) : '';
		$url  = isset( $_POST['be_schema_author_url'] ) ? esc_url_raw( wp_unslash( $_POST['be_schema_author_url'] ) ) : '';
		update_post_meta( $post_id, '_be_schema_author_name', $name );
		update_post_meta( $post_id, '_be_schema_author_url', $url );
	},
	10,
	1
);

/**
 * Resolve the primary image URL for a BlogPosting:
 *
 * Fallback chain:
 *   1. Featured image of the post.
 *   2. be_schema_get_default_image_url() helper if present.
 *
 * @param int $post_id Post ID.
 *
 * @return string Empty string if none available.
 */
function be_schema_get_post_image_url( $post_id ) {
	$post_id = (int) $post_id;

	if ( $post_id <= 0 ) {
		return '';
	}

	if ( has_post_thumbnail( $post_id ) ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
		if ( $src && ! empty( $src[0] ) ) {
			return (string) $src[0];
		}
	}

	// Optional: global default image helper, if defined elsewhere.
	if ( function_exists( 'be_schema_get_default_image_url' ) ) {
		$fallback = be_schema_get_default_image_url();
		if ( $fallback ) {
			return (string) $fallback;
		}
	}

	return '';
}

/**
 * Build the WebPage node that represents the post’s page.
 *
 * @param WP_Post $post The post object.
 *
 * @return array
 */
function be_schema_build_post_webpage_node( $post ) {
	$post_id = (int) $post->ID;
	$url     = get_permalink( $post_id );
	$language = get_bloginfo( 'language' );

	$name = get_the_title( $post );
	if ( '' === $name ) {
		$name = get_bloginfo( 'name', 'display' );
	}

	$description = '';
	$max_length  = 320;

	if ( has_excerpt( $post ) ) {
		$description = be_schema_normalize_text( $post->post_excerpt, $max_length );
	} elseif ( ! empty( $post->post_content ) ) {
		$description = be_schema_normalize_text( $post->post_content, $max_length );
	}

	$webpage_id = $url; // For posts, we use the permalink as the @id for WebPage.

	$node = array(
		'@type' => 'WebPage',
		'@id'   => $webpage_id,
		'url'   => $url,
		'name'  => $name,
	);

	if ( $language ) {
		$node['inLanguage'] = $language;
	}

	if ( $description ) {
		$node['description'] = $description;
	}

	// Link to site-level WebSite node by @id.
	$node['isPartOf'] = array(
		'@id' => be_schema_id( 'website' ),
	);

	// about → Person / Organisation by @id, if present.
	$node['about'] = array(
		array( '@id' => be_schema_id( 'organisation' ) ),
		array( '@id' => be_schema_id( 'person' ) ),
	);

	$image_url = be_schema_get_post_image_url( $post_id );
	if ( $image_url ) {
		$node['primaryImageOfPage'] = array(
			'@type' => 'ImageObject',
			'url'   => $image_url,
		);
	}

	return $node;
}

/**
 * Resolve publisher @id for a BlogPosting.
 *
 * Preference order:
 *   1. Explicit Publisher entity (#publisher).
 *   2. Organisation entity (#organisation).
 *   3. Person entity (#person).
 *
 * We return an array with @id, suitable for embedding directly in the
 * BlogPosting node.
 *
 * @return array|null
 */
function be_schema_get_publisher_reference() {
	// In a more advanced implementation, you might inspect settings to see
	// whether a custom publisher org is configured or enabled. For now, we
	// rely on stable @ids.
	$publisher_id    = be_schema_id( 'publisher' );
	$organisation_id = be_schema_id( 'organisation' );
	$person_id       = be_schema_id( 'person' );

	// Very conservative: always return something, but in an order that
	// respects your design (publisher → organisation → person).
	// If no matching node is ultimately emitted elsewhere, it’s still safe
	// to reference by @id; the graph will simply lack that node.
	if ( $publisher_id ) {
		return array( '@id' => $publisher_id );
	}

	if ( $organisation_id ) {
		return array( '@id' => $organisation_id );
	}

	if ( $person_id ) {
		return array( '@id' => $person_id );
	}

	return null;
}

/**
 * Build FAQPage schema from Elementor widgets (Accordion/Toggle/FAQ).
 *
 * @param WP_Post $post Post object.
 * @return array Array of FAQPage nodes (0 or 1).
 */
function be_schema_build_faq_schema( $post ) {
	$data_raw = get_post_meta( $post->ID, '_elementor_data', true );
	if ( empty( $data_raw ) ) {
		return array();
	}
	$data = json_decode( $data_raw, true );
	if ( ! is_array( $data ) ) {
		return array();
	}

	$faq_items = array();
	$walker    = function( $nodes ) use ( &$walker, &$faq_items ) {
		if ( empty( $nodes ) || ! is_array( $nodes ) ) {
			return;
		}
		foreach ( $nodes as $node ) {
			if ( isset( $node['elements'] ) && is_array( $node['elements'] ) ) {
				$walker( $node['elements'] );
			}
			if ( ! isset( $node['elType'] ) || 'widget' !== $node['elType'] ) {
				continue;
			}
			$type = isset( $node['widgetType'] ) ? strtolower( (string) $node['widgetType'] ) : '';
			$faq_widgets = array(
				'accordion',
				'toggle',
				'faq',
				'faq-widget',
				'faq_schema',
				'bdt-accordion',
				'eael-accordion',
				'eael-advanced-accordion',
				'elementor-faq',
			);
			if ( ! in_array( $type, $faq_widgets, true ) ) {
				continue;
			}
			$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
			$list     = array();
			foreach ( array( 'accordion', 'tabs', 'items', 'faq_list', 'faq_items' ) as $key ) {
				if ( ! empty( $settings[ $key ] ) && is_array( $settings[ $key ] ) ) {
					$list = $settings[ $key ];
					break;
				}
			}
			if ( empty( $list ) ) {
				continue;
			}
			foreach ( $list as $entry ) {
				$q = '';
				foreach ( array( 'question', 'tab_title', 'title' ) as $qk ) {
					if ( ! empty( $entry[ $qk ] ) ) {
						$q = $entry[ $qk ];
						break;
					}
				}
				$a = '';
				foreach ( array( 'answer', 'tab_content', 'content', 'text' ) as $ak ) {
					if ( ! empty( $entry[ $ak ] ) ) {
						$a = $entry[ $ak ];
						break;
					}
				}
				$q = wp_strip_all_tags( (string) $q );
				$a = wp_strip_all_tags( (string) $a );
				if ( $q && $a ) {
					$faq_items[] = array(
						'@type'          => 'Question',
						'name'           => $q,
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => $a,
						),
					);
				}
			}
		}
	};
	$walker( $data );

	if ( empty( $faq_items ) ) {
		return array();
	}

	return array(
		array(
			'@type'      => 'FAQPage',
			'@id'        => be_schema_id( 'faq-' . $post->ID ),
			'mainEntity' => $faq_items,
		),
	);
}

/**
 * Build HowTo schema from Elementor widgets (Steps/Process-like).
 *
 * @param WP_Post $post Post object.
 * @return array Array of HowTo nodes (0 or 1).
 */
function be_schema_build_howto_schema( $post ) {
	$data_raw = get_post_meta( $post->ID, '_elementor_data', true );
	if ( empty( $data_raw ) ) {
		return array();
	}
	$data = json_decode( $data_raw, true );
	if ( ! is_array( $data ) ) {
		return array();
	}

	$howto_steps = array();
	$walker      = function( $nodes ) use ( &$walker, &$howto_steps ) {
		if ( empty( $nodes ) || ! is_array( $nodes ) ) {
			return;
		}
		foreach ( $nodes as $node ) {
			if ( isset( $node['elements'] ) && is_array( $node['elements'] ) ) {
				$walker( $node['elements'] );
			}
			if ( ! isset( $node['elType'] ) || 'widget' !== $node['elType'] ) {
				continue;
			}
			$type = isset( $node['widgetType'] ) ? strtolower( (string) $node['widgetType'] ) : '';
			$howto_widgets = array(
				'steps',
				'process',
				'howto',
				'how_to',
				'timeline',
			);
			if ( ! in_array( $type, $howto_widgets, true ) ) {
				continue;
			}
			$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
			$list     = array();
			foreach ( array( 'steps', 'items', 'process', 'tabs' ) as $key ) {
				if ( ! empty( $settings[ $key ] ) && is_array( $settings[ $key ] ) ) {
					$list = $settings[ $key ];
					break;
				}
			}
			if ( empty( $list ) ) {
				continue;
			}
			foreach ( $list as $idx => $entry ) {
				$name = '';
				foreach ( array( 'title', 'tab_title', 'heading' ) as $nk ) {
					if ( ! empty( $entry[ $nk ] ) ) {
						$name = $entry[ $nk ];
						break;
					}
				}
				$text = '';
				foreach ( array( 'text', 'description', 'content', 'tab_content' ) as $tk ) {
					if ( ! empty( $entry[ $tk ] ) ) {
						$text = $entry[ $tk ];
						break;
					}
				}
				$step_item = array(
					'@type'    => 'HowToStep',
					'position' => $idx + 1,
					'name'     => wp_strip_all_tags( $name ? $name : sprintf( __( 'Step %d', 'beseo' ), $idx + 1 ) ),
					'text'     => wp_strip_all_tags( $text ),
				);

				// Attempt to resolve an image for the step.
				$image_source = null;
				if ( isset( $entry['image'] ) ) {
					$image_source = $entry['image'];
				} elseif ( isset( $entry['media'] ) ) {
					$image_source = $entry['media'];
				}
				if ( $image_source ) {
					if ( is_array( $image_source ) && isset( $image_source['id'] ) ) {
						$img_obj = be_schema_engine_build_image_object( $image_source['id'] );
						if ( $img_obj ) {
							$step_item['image'] = $img_obj;
						}
					} else {
						$img_obj = be_schema_engine_build_image_object( $image_source );
						if ( $img_obj ) {
							$step_item['image'] = $img_obj;
						}
					}
				}

				$howto_steps[] = $step_item;
			}
		}
	};
	$walker( $data );

	if ( empty( $howto_steps ) ) {
		return array();
	}

	return array(
		array(
			'@type' => 'HowTo',
			'@id'   => be_schema_id( 'howto-' . $post->ID ),
			'name'  => get_the_title( $post ),
			'step'  => $howto_steps,
		),
	);
}

/**
 * Build a BlogPosting node for the current post.
 *
 * @param WP_Post $post The post object.
 *
 * @return array
 */
function be_schema_build_blogposting_node( $post ) {
	$post_id = (int) $post->ID;
	$url     = get_permalink( $post_id );
	$canonical = function_exists( 'wp_get_canonical_url' ) ? wp_get_canonical_url( $post_id ) : '';
	if ( $canonical ) {
		$url = $canonical;
	}
	$language = get_bloginfo( 'language' );

	$headline = get_the_title( $post );
	if ( '' === $headline ) {
		$headline = get_bloginfo( 'name', 'display' );
	}
	// Keep headline within common validator limits (~110 chars).
	if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
		if ( mb_strlen( $headline ) > 110 ) {
			$headline = mb_substr( $headline, 0, 107 ) . '…';
		}
	} elseif ( strlen( $headline ) > 110 ) {
		$headline = substr( $headline, 0, 107 ) . '…';
	}

	$max_length  = 320;
	$description = '';

	if ( has_excerpt( $post ) ) {
		$description = be_schema_normalize_text( $post->post_excerpt, $max_length );
	} elseif ( ! empty( $post->post_content ) ) {
		$description = be_schema_normalize_text( $post->post_content, $max_length );
	}

	$date_published = get_the_date( DATE_W3C, $post );
	$date_modified  = get_the_modified_date( DATE_W3C, $post );

	$image_objects = array();
	$image_url     = '';
	if ( has_post_thumbnail( $post_id ) ) {
		$thumb_id   = get_post_thumbnail_id( $post_id );
		$image_obj  = be_schema_engine_build_image_object( $thumb_id );
		if ( $image_obj ) {
			$image_objects[] = $image_obj;
			$image_url       = isset( $image_obj['contentUrl'] ) ? $image_obj['contentUrl'] : '';
		}
	}

	if ( ! $image_url ) {
		$image_url = be_schema_get_post_image_url( $post_id );
		if ( $image_url ) {
			$image_objects[] = array(
				'@type'      => 'ImageObject',
				'url'        => $image_url,
				'contentUrl' => $image_url,
			);
		}
	}

	// Stable @id for the article node.
	$article_id = be_schema_id( 'article-' . $post_id );

	// Author resolution: per-post meta -> global author -> per-post author -> site title.
	$settings     = be_schema_engine_get_settings();
	$author_node  = null;

	$meta_mode = get_post_meta( $post_id, '_be_schema_author_mode', true );
	$meta_mode = $meta_mode ? $meta_mode : 'global';

	if ( 'override' === $meta_mode ) {
		$meta_type = get_post_meta( $post_id, '_be_schema_author_type', true );
		$meta_type = in_array( $meta_type, array( 'Person', 'Organisation' ), true ) ? $meta_type : 'Person';
		$meta_name = get_post_meta( $post_id, '_be_schema_author_name', true );
		$meta_url  = get_post_meta( $post_id, '_be_schema_author_url', true );
		if ( $meta_name ) {
			$author_node = array(
				'@type' => ( 'Organisation' === $meta_type ) ? 'Organization' : 'Person',
				'name'  => $meta_name,
			);
			if ( $meta_url ) {
				$author_node['@id'] = trailingslashit( $meta_url ) . '#author';
				$author_node['url'] = $meta_url;
			}
		}
	} elseif ( 'active_user' === $meta_mode ) {
		$current_user = wp_get_current_user();
		if ( $current_user && $current_user->exists() ) {
			$author_node = array(
				'@type' => 'Person',
				'name'  => $current_user->display_name,
				'@id'   => be_schema_id( 'person' ),
			);
			if ( ! empty( $current_user->user_url ) ) {
				$author_node['url'] = $current_user->user_url;
			}
		}
	}

	if ( ! $author_node ) {
		$author_mode_global = isset( $settings['global_author_mode'] ) ? $settings['global_author_mode'] : 'website';
		if ( 'override' === $author_mode_global && ! empty( $settings['global_author_name'] ) ) {
			$override_type = isset( $settings['global_author_type'] ) ? $settings['global_author_type'] : 'Person';
			$override_type = in_array( $override_type, array( 'Person', 'Organisation' ), true ) ? $override_type : 'Person';
			$override_type = ( 'Organisation' === $override_type ) ? 'Organization' : 'Person';

			$author_node = array(
				'@type' => $override_type,
				'name'  => $settings['global_author_name'],
			);

			if ( ! empty( $settings['global_author_url'] ) ) {
				$author_node['@id'] = trailingslashit( $settings['global_author_url'] ) . '#author';
				$author_node['url'] = $settings['global_author_url'];
			}
		}
	}

	if ( ! $author_node ) {
		$site_entities = be_schema_get_site_entities();
		$mode          = isset( $settings['site_identity_mode'] ) ? $settings['site_identity_mode'] : 'publisher';
		$mode          = in_array( $mode, array( 'person', 'organisation', 'publisher' ), true ) ? $mode : 'publisher';
		$key           = ( 'organisation' === $mode ) ? 'organization' : $mode;
		$entity        = isset( $site_entities[ $key ] ) ? $site_entities[ $key ] : null;

		if ( $entity && is_array( $entity ) && ( empty( $entity['@type'] ) || empty( $entity['name'] ) ) ) {
			$entity = isset( $site_entities['organization'] ) ? $site_entities['organization'] : ( $site_entities['person'] ?? $entity );
		}

		if ( $entity && is_array( $entity ) ) {
			$author_node = $entity;
		}
	}

	if ( ! $author_node ) {
		// Author (per-post) with URL/image when available.
		$author_id         = (int) $post->post_author;
		$author_name       = get_the_author_meta( 'display_name', $author_id );
		$author_url        = get_author_posts_url( $author_id );
		$author_img        = get_avatar_url( $author_id, array( 'size' => 256 ) );
		$author_job        = get_the_author_meta( 'job_title', $author_id );
		$author_knows      = get_the_author_meta( 'knows_about', $author_id );
		$author_sameas_raw = get_the_author_meta( 'be_schema_sameas', $author_id );

		if ( $author_name ) {
			$author_node = array(
				'@type' => 'Person',
				'name'  => $author_name,
			);
			if ( $author_url ) {
				$author_node['@id'] = $author_url . '#author';
				$author_node['url'] = $author_url;
			} else {
				$author_node['@id'] = be_schema_id( 'person' );
			}
			if ( $author_img ) {
				$author_node['image'] = array(
					'@type' => 'ImageObject',
					'url'   => $author_img,
				);
			}
			if ( $author_job ) {
				$author_node['jobTitle'] = $author_job;
			}
			if ( $author_knows ) {
				$author_node['knowsAbout'] = array_map( 'trim', explode( ',', $author_knows ) );
			}
			if ( $author_sameas_raw ) {
				$urls = be_schema_engine_normalize_url_list( preg_split( '/\r\n|\r|\n|,/', (string) $author_sameas_raw ) );
				if ( $urls ) {
					$author_node['sameAs'] = $urls;
				}
			}
		}
	}

	// Final fallback to site title.
	if ( ! $author_node ) {
		$author_node = array(
			'@type' => 'Person',
			'name'  => get_bloginfo( 'name', 'display' ),
			'@id'   => be_schema_id( 'person' ),
		);
	}

	// Editor (last editor) if available.
	$editor_id = (int) get_post_meta( $post_id, '_edit_last', true );
	$editor_node = null;
	if ( $editor_id ) {
		$editor_name = get_the_author_meta( 'display_name', $editor_id );
		$editor_url  = get_author_posts_url( $editor_id );
		$editor_img  = get_avatar_url( $editor_id, array( 'size' => 256 ) );
		$editor_node = array(
			'@type' => 'Person',
			'name'  => $editor_name ? $editor_name : '',
		);
		if ( $editor_url ) {
			$editor_node['@id'] = $editor_url . '#editor';
			$editor_node['url'] = $editor_url;
		}
		if ( $editor_img ) {
			$editor_node['image'] = array(
				'@type' => 'ImageObject',
				'url'   => $editor_img,
			);
		}
	}

	// Categories → articleSection.
	$article_section = '';
	$categories      = get_the_category( $post_id );
	if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
		$article_section = $categories[0]->name;
	}

	// Tags → keywords.
	$tag_terms = get_the_tags( $post_id );
	$keywords  = array();
	if ( ! empty( $tag_terms ) && ! is_wp_error( $tag_terms ) ) {
		foreach ( $tag_terms as $tag_term ) {
			$keywords[] = $tag_term->name;
		}
	}

	// VideoObject attachments.
	$video_objects = array();
	$video_children = get_children(
		array(
			'post_type'      => 'attachment',
			'post_parent'    => $post_id,
			'post_mime_type' => 'video',
			'numberposts'    => 5,
			'fields'         => 'ids',
		)
	);
	if ( $video_children ) {
		$thumb_fallback = $image_url ? $image_url : be_schema_get_post_image_url( $post_id );
		$org_id         = be_schema_id( 'organisation' );
		foreach ( $video_children as $vid_id ) {
			$vid_url = wp_get_attachment_url( $vid_id );
			if ( ! $vid_url ) {
				continue;
			}
			$vid_post   = get_post( $vid_id );
			$vid_meta   = wp_get_attachment_metadata( $vid_id );
			$uploadDate = $vid_post ? $vid_post->post_date_gmt : '';
			if ( ! $uploadDate && $vid_post ) {
				$uploadDate = $vid_post->post_date;
			}
			$uploadDate = $uploadDate ? gmdate( DATE_W3C, strtotime( $uploadDate ) ) : '';

			$duration_iso = '';
			if ( is_array( $vid_meta ) ) {
				if ( isset( $vid_meta['length'] ) && is_numeric( $vid_meta['length'] ) ) {
					$seconds      = (int) $vid_meta['length'];
					$duration_iso = 'PT' . $seconds . 'S';
				} elseif ( isset( $vid_meta['length_formatted'] ) ) {
					// Convert mm:ss or hh:mm:ss to PT#M#S form.
					$parts = array_map( 'intval', explode( ':', $vid_meta['length_formatted'] ) );
					if ( count( $parts ) === 3 ) {
						list( $hours, $minutes, $seconds_part ) = $parts;
						$duration_iso = 'PT' . $hours . 'H' . $minutes . 'M' . $seconds_part . 'S';
					} elseif ( count( $parts ) === 2 ) {
						list( $minutes, $seconds_part ) = $parts;
						$duration_iso = 'PT' . $minutes . 'M' . $seconds_part . 'S';
					}
				}
			}

			$views_meta = (int) get_post_meta( $vid_id, 'post_views_count', true );

			$video_obj = array(
				'@type'         => 'VideoObject',
				'name'          => $vid_post ? get_the_title( $vid_post ) : $headline,
				'description'   => $vid_post ? be_schema_normalize_text( $vid_post->post_content, 320 ) : $description,
				'contentUrl'    => $vid_url,
				'embedUrl'      => $vid_url,
				'uploadDate'    => $uploadDate,
				'isFamilyFriendly' => true,
			);
			if ( $thumb_fallback ) {
				$video_obj['thumbnailUrl'] = $thumb_fallback;
			}
			if ( $duration_iso ) {
				$video_obj['duration'] = $duration_iso;
			}
			if ( $views_meta > 0 ) {
				$video_obj['interactionStatistic'] = array(
					'@type'                => 'InteractionCounter',
					'interactionType'      => array( '@type' => 'WatchAction' ),
					'userInteractionCount' => $views_meta,
				);
			}
			if ( $org_id ) {
				$video_obj['publisher'] = array(
					'@type' => 'Organization',
					'@id'   => $org_id,
				);
			}
			$video_objects[] = $video_obj;
		}
	}

	$node = array(
		'@type'            => 'BlogPosting',
		'@id'              => $article_id,
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => $url,
		),
		'headline'         => $headline,
		'url'              => $url,
		'mainEntity'       => array(
			'@id' => $url,
		),
	);

	if ( $language ) {
		$node['inLanguage'] = $language;
	}

	if ( $description ) {
		$node['description'] = $description;
	}

	if ( $date_published ) {
		$node['datePublished'] = $date_published;
	}
	if ( $date_modified ) {
		$node['dateModified'] = $date_modified;
	}

	$node['author'] = $author_node;

	if ( $editor_node ) {
		$node['editor'] = $editor_node;
	}

	// Publisher: resolved via helper.
	$publisher_ref = be_schema_get_publisher_reference();
	if ( $publisher_ref ) {
		$node['publisher'] = $publisher_ref;
	}

	if ( $image_objects ) {
		$node['image'] = $image_objects;
	}

	if ( $video_objects ) {
		$node['video'] = $video_objects;
	}

	if ( $article_section ) {
		$node['articleSection'] = $article_section;
	}

	if ( $keywords ) {
		$node['keywords'] = $keywords;
	}

	$node['speakable'] = array(
		'@type'       => 'SpeakableSpecification',
		'cssSelector' => array(
			'article .entry-title',
			'.entry-title',
			'header h1',
			'article h1',
			'title',
			'main .lead',
		),
	);

	// Article body (sanitized, truncated).
	$article_body = be_schema_normalize_text( wp_strip_all_tags( $post->post_content ), 5000 );
	if ( $article_body ) {
		$node['articleBody'] = $article_body;
	}

	// Comment count if comments are open/available.
	$comment_count = get_comments_number( $post_id );
	if ( $comment_count > 0 ) {
		$node['commentCount'] = (int) $comment_count;
	}

	return $node;
}

/**
 * Emit BlogPosting + WebPage schema for eligible singular posts.
 *
 * Respects:
 * - Global disable.
 * - Singular eligibility via be_schema_is_singular_eligible().
 * - Per-page / Elementor gating via be_schema_is_disabled_for_current_page().
 *
 * @return void
 */
function be_schema_output_post_schema() {
	// Never emit in admin, AJAX, feeds, or JSON endpoints.
	if ( is_admin() || wp_doing_ajax() || is_feed() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
		return;
	}

	if ( function_exists( 'be_schema_globally_disabled' ) && be_schema_globally_disabled() ) {
		if ( function_exists( 'be_schema_debug_event' ) ) {
			be_schema_debug_event(
				'post_schema_skipped',
				array(
					'reason' => 'global_disabled',
					'url'    => home_url( add_query_arg( array(), '' ) ),
				)
			);
		}
		return;
	}

	if ( ! is_singular() ) {
		return;
	}

	$post = get_post();
	if ( ! $post ) {
		return;
	}

	$post_id = (int) $post->ID;

	if ( function_exists( 'be_schema_is_singular_eligible' ) && ! be_schema_is_singular_eligible() ) {
		if ( function_exists( 'be_schema_debug_event' ) ) {
			be_schema_debug_event(
				'post_schema_skipped',
				array(
					'reason'  => 'post_type_not_eligible',
					'post_id' => $post_id,
					'url'     => get_permalink( $post_id ),
					'post_type' => get_post_type( $post ),
				)
			);
		}
		return;
	}

	if ( function_exists( 'be_schema_is_disabled_for_current_page' ) && be_schema_is_disabled_for_current_page() ) {
		if ( function_exists( 'be_schema_debug_event' ) ) {
			be_schema_debug_event(
				'post_schema_skipped',
				array(
					'reason'  => 'per_page_or_elementor_disabled',
					'post_id' => $post_id,
					'url'     => get_permalink( $post_id ),
				)
			);
		}
		return;
	}

	if ( function_exists( 'be_schema_debug_event' ) ) {
		be_schema_debug_event(
			'schema_graph_attempt',
			array(
				'context' => 'post',
				'post_id' => $post_id,
				'url'     => get_permalink( $post_id ),
			)
		);
	}

	if ( function_exists( 'be_schema_is_dry_run' ) && be_schema_is_dry_run() ) {
		if ( function_exists( 'be_schema_log_dry_run' ) ) {
			be_schema_log_dry_run(
				'post',
				array(
					'post_id' => $post_id,
					'url'     => get_permalink( $post_id ),
				)
			);
		}
		return;
	}

	$webpage    = be_schema_build_post_webpage_node( $post );
	$blogposting = be_schema_build_blogposting_node( $post );
	$faq_nodes   = be_schema_build_faq_schema( $post );
	$howto_nodes = be_schema_build_howto_schema( $post );

	$graph = array(
		$webpage,
		$blogposting,
	);
	if ( $faq_nodes ) {
		$graph = array_merge( $graph, $faq_nodes );
	}
	if ( $howto_nodes ) {
		$graph = array_merge( $graph, $howto_nodes );
	}

	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $payload ) . '</script>' . "\n";

	if ( function_exists( 'be_schema_debug_event' ) ) {
		be_schema_debug_event(
			'schema_graph_emitted',
			array(
				'context' => 'post',
				'post_id' => $post_id,
				'url'     => get_permalink( $post_id ),
			)
		);
	}
}
