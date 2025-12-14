<?php
/**
 * Shared Social settings helpers (defaults, normalization).
 *
 * @package BESEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default social settings.
 *
 * These must stay in sync with admin save + core-social consumer.
 *
 * @return array
 */
function be_schema_social_get_default_settings() {
	return array(
		// Master enable for social meta (legacy; derived in admin save).
		'enabled' => '0',

		// Per-network enables.
		'social_enable_og'      => '1',
		'social_enable_twitter' => '1',

		// Safety toggles.
		'dry_run'         => '0',
		'twitter_dry_run' => '0',

		// Default images (attachment IDs or URLs depending on UI).
		'default_facebook_image_id' => '',
		'default_twitter_image_id'  => '',
		'default_global_image_id'   => '',
		'global_default_image_alt'  => '',
		'global_images_optional'    => '',
		'global_image_16_9'         => '',
		'global_image_5_4'          => '',
		'global_image_1_1'          => '',
		'global_image_4_5'          => '',
		'global_image_1_1_91'       => '',
		'global_image_9_16'         => '',

		// Facebook additional aspect images.
		'facebook_images_optional' => '',
		'facebook_image_16_9'      => '',
		'facebook_image_5_4'       => '',
		'facebook_image_1_1'       => '',
		'facebook_image_4_5'       => '',
		'facebook_image_1_1_91'    => '',
		'facebook_image_9_16'      => '',

		// Twitter additional aspect images.
		'twitter_images_optional' => '',
		'twitter_image_16_9'      => '',
		'twitter_image_5_4'       => '',
		'twitter_image_1_1'       => '',
		'twitter_image_4_5'       => '',
		'twitter_image_1_1_91'    => '',
		'twitter_image_9_16'      => '',

		// Twitter handles (with or without @, we will normalize).
		'twitter_site'    => '',
		'twitter_creator' => '',

		// Card type: summary or summary_large_image.
		'twitter_card_type' => 'summary_large_image',

		// Optional Facebook app ID.
		'facebook_app_id' => '',
	);
}

/**
 * Merge given settings with defaults.
 *
 * @param array $settings Raw settings.
 *
 * @return array
 */
function be_schema_social_merge_defaults( $settings ) {
	return wp_parse_args( (array) $settings, be_schema_social_get_default_settings() );
}
