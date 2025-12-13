<?php
/**
 * BE Schema Engine - Core helpers.
 *
 * Shared helper functions for:
 * - Plugin settings (with conservative defaults).
 * - Global / Elementor / per-page disable checks.
 * - Singular eligibility.
 * - Text normalization.
 * - Stable @id generation for schema nodes.
 *
 * @package BE_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get BE Schema Engine settings with sane defaults.
 *
 * This function:
 * - Provides a central place for all plugin-level defaults.
 * - Merges saved options over those defaults.
 * - Caches the result for the duration of the request.
 *
 * @return array
 */
function be_schema_engine_get_settings() {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached;
	}

	// Conservative defaults: everything off unless explicitly enabled.
	$defaults = array(
		// Master enable.
		'enabled'           => '0',

		// Elementor integration.
		'elementor_enabled' => '0',

		// Debug flag (in addition to WP_DEBUG / BE_SCHEMA_DEBUG).
		'debug'             => '0',
		'dry_run'           => '0',

		// Entity toggles.
		'person_enabled'        => '0',
		'organization_enabled'  => '0',
		'publisher_enabled'     => '0',

		// Person fields.
		'person_url'            => '',
		'person_alumni_of'      => array(),
		'person_job_title'      => array(),
		'person_affiliation'    => array(),
		'person_honorific_prefix' => array(),
		'person_honorific_suffix' => array(),

		// Site identity mode (how WebSite & publisher prioritise Person vs Organisation vs Publisher).
		// Allowed values: 'person', 'organisation', 'publisher'.
		'site_identity_mode'    => 'publisher',
		'site_identity_person_enabled'       => '1',
		'site_identity_organisation_enabled' => '1',
		'site_identity_publisher_enabled'    => '1',

		// Organisation fields.
		'org_name'         => '',
		'org_legal_name'   => '',
		'org_url'          => '',
		'org_logo'         => '', // Could be attachment ID or URL depending on your UI.
		'org_logo_enabled' => '1',

		// Publisher custom organisation.
		'publisher_custom_name' => '',
		'publisher_custom_url'  => '',
		'publisher_custom_logo' => '',

		// WebSite featured images.
		'website_image_16_9'        => '',
		'website_image_4_3'         => '',
		'website_image_1_1'         => '',
		'website_image_3_4'         => '',
		'website_image_9_16'        => '',
		'website_image_16_9_enabled' => '0',
		'website_image_4_3_enabled'  => '0',
		'website_image_1_1_enabled'  => '0',
		'website_image_3_4_enabled'  => '0',
		'website_image_9_16_enabled' => '0',

		// Additional policy / legal metadata.
		'copyright_year'        => '',
		'license_url'           => '',
		'publishing_principles' => '',
		'corrections_policy'    => '',
		'ownership_funding'     => '',
	);

	$settings = get_option( 'be_schema_engine_settings', array() );
	$cached   = wp_parse_args( $settings, $defaults );

	// Normalize multi-valued person fields to arrays.
	$multi_fields = array(
		'person_alumni_of',
		'person_job_title',
		'person_affiliation',
		'person_honorific_prefix',
		'person_honorific_suffix',
	);

	foreach ( $multi_fields as $field ) {
		if ( isset( $cached[ $field ] ) && ! is_array( $cached[ $field ] ) ) {
			$cached[ $field ] = '' !== $cached[ $field ] ? array( $cached[ $field ] ) : array();
		}
	}

	return $cached;
}

/**
 * Hard/global disable for all schema.
 *
 * True if:
 * - BE_SCHEMA_DISABLE_ALL is defined and truthy; OR
 * - plugin setting 'enabled' !== '1'.
 *
 * @return bool
 */
function be_schema_globally_disabled() {
	if ( defined( 'BE_SCHEMA_DISABLE_ALL' ) && BE_SCHEMA_DISABLE_ALL ) {
		return true;
	}

	$settings = be_schema_engine_get_settings();

	return ( $settings['enabled'] !== '1' );
}

/**
 * Dry-run mode for schema output.
 *
 * When enabled, schema renderers log debug events but do not emit JSON-LD.
 * Never allow output on REST requests when dry run is on.
 *
 * @return bool
 */
function be_schema_is_dry_run() {
	$settings = be_schema_engine_get_settings();
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return true;
	}
	return ( isset( $settings['dry_run'] ) && '1' === (string) $settings['dry_run'] );
}

/**
 * Helper to log a dry-run skip event.
 *
 * @param string $context Context label (homepage, post, breadcrumbs, etc.).
 * @param array  $data    Optional metadata for the log.
 *
 * @return void
 */
function be_schema_log_dry_run( $context, $data = array() ) {
	if ( function_exists( 'be_schema_debug_event' ) ) {
		be_schema_debug_event(
			'schema_dry_run',
			array(
				'context' => $context,
				'data'    => $data,
			)
		);
		return;
	}

	// Fallback log.
	error_log(
		'BE_SCHEMA_DRY_RUN ' . wp_json_encode(
			array(
				'context' => $context,
				'data'    => $data,
			)
		)
	);
}

/**
 * Hard/global disable for Elementor-driven schema.
 *
 * True if:
 * - global schema is disabled; OR
 * - BE_SCHEMA_DISABLE_ELEMENTOR is defined and truthy; OR
 * - plugin setting 'elementor_enabled' !== '1'.
 *
 * @return bool
 */
function be_schema_elementor_disabled() {
	if ( be_schema_globally_disabled() ) {
		return true;
	}

	if ( defined( 'BE_SCHEMA_DISABLE_ELEMENTOR' ) && BE_SCHEMA_DISABLE_ELEMENTOR ) {
		return true;
	}

	$settings = be_schema_engine_get_settings();

	return ( $settings['elementor_enabled'] !== '1' );
}

/**
 * Returns true if schema should be considered disabled for the current
 * singular page, based on:
 *
 * - _be_schema_disable meta
 * - Elementor page settings in _elementor_page_settings
 *
 * Safe-by-default:
 * - If no Elementor page settings exist, schema is disabled for that page
 *   unless you’re emitting non-page-specific things (e.g. breadcrumbs).
 *
 * @return bool
 */
function be_schema_is_disabled_for_current_page() {
	if ( ! is_singular() ) {
		// Page-level safety doesn’t apply to archives, home, search, etc.
		return false;
	}

	$post = get_post();
	if ( ! $post ) {
		// No post context; safest is "consider disabled".
		return true;
	}

	// Hard per-page disable meta.
	$disable_meta = get_post_meta( $post->ID, '_be_schema_disable', true );
	if ( (string) $disable_meta === '1' ) {
		return true;
	}

	// Elementor page settings.
	$page_settings = get_post_meta( $post->ID, '_elementor_page_settings', true );

	// Safe-by-default: if there are no Elementor settings at all, we treat
	// that as "schema not explicitly enabled for this page".
	if ( empty( $page_settings ) || ! is_array( $page_settings ) ) {
		return true;
	}

	// be_schema_enable_page must be explicitly "yes".
	$enable_page = isset( $page_settings['be_schema_enable_page'] ) ? $page_settings['be_schema_enable_page'] : '';

	if ( $enable_page !== 'yes' ) {
		return true;
	}

	return false;
}

/**
 * Determine whether the current singular post is eligible for
 * singular-schema emission (BlogPosting, Elementor page schema, etc.).
 *
 * - Requires is_singular() === true.
 * - Uses a filter so consumers can gate per post_type.
 *
 * @return bool
 */
function be_schema_is_singular_eligible() {
	if ( ! is_singular() ) {
		return false;
	}

	$post = get_post();
	if ( ! $post ) {
		return false;
	}

	$post_type = get_post_type( $post );

	// Default: allow all post types. Developers can tighten this up.
	$allowed = true;

	/**
	 * Filter whether BE Schema Engine is allowed to emit schema
	 * for the given singular post type.
	 *
	 * @param bool   $allowed   Default true.
	 * @param string $post_type The post type slug.
	 */
	$allowed = apply_filters( 'be_schema_allow_post_type', $allowed, $post_type );

	return (bool) $allowed;
}

/**
 * Build a stable @id for schema nodes.
 *
 * Example:
 *   be_schema_id( 'person' )      => https://example.com/#person
 *   be_schema_id( 'organisation' ) => https://example.com/#organisation
 *
 * @param string $suffix Logical suffix (e.g. 'person', 'organisation', 'website').
 *
 * @return string
 */
function be_schema_id( $suffix ) {
	$suffix = sanitize_key( $suffix );
	$home   = trailingslashit( home_url( '/' ) );

	return $home . '#' . $suffix;
}

/**
 * Normalize free-text descriptions:
 * - Cast to string
 * - Strip tags
 * - Collapse whitespace
 * - Trim
 * - Optionally cap to a max length, trimming to the last full word
 *
 * @param string $text       Raw text.
 * @param int    $max_length Max length (characters).
 *
 * @return string
 */
function be_schema_normalize_text( $text, $max_length = 320 ) {
	$text = (string) $text;

	// Strip HTML tags.
	$text = wp_strip_all_tags( $text, true );

	// Collapse whitespace.
	$text = preg_replace( '/\s+/', ' ', $text );
	$text = trim( $text );

	if ( $max_length > 0 && strlen( $text ) > $max_length ) {
		$text = substr( $text, 0, $max_length );

		// Trim to last full word to avoid ugly mid-word cuts.
		$last_space = strrpos( $text, ' ' );
		if ( false !== $last_space && $last_space > 0 ) {
			$text = substr( $text, 0, $last_space );
		}

		$text = rtrim( $text );
	}

	return $text;
}
