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
 * - @graph of:
 *   - Person (if enabled)
 *   - Person image (if available)
 *   - Organisation (if enabled)
 *   - WebSite
 *   - logo
 *   - Publisher (if enabled)
 *   - Publisher logo (if enabled)
 *   - WebPage for the homepage.
 */
