BE SEO – Testing Notes
======================

Goal: quick, repeatable checks for the recent validation and debug-visibility changes. These are manual steps; no automated suite is wired up yet.

Prereqs
-------
- WordPress admin access with the BE SEO plugin active.
- For debug readiness checks, set `WP_DEBUG` to `true` in wp-config.php.

Schema Admin (Settings / Website)
---------------------------------
1) URL validation (Organisation/Publisher):
   - Enter an invalid URL (e.g., `foo` or `javascript:alert(1)`) in Organisation URL or any Publisher URL field, save.
   - Expect: error notice on the Schema page, field value cleared.
- Enter a valid `https://example.com` style URL and save; no notice.
2) Copyright year:
   - Enter `23` and save.
   - Expect: error notice and field cleared. Enter `2024`; no notice.
3) Schema dry run:
   - Enable “Schema Dry Run (No Output)” in Settings and ensure WP_DEBUG + Admin Debug are on.
   - Load a front-end page; expect no JSON-LD output in source, but see `BE_SCHEMA_DRY_RUN` entries in error log.
   - Disable dry run and confirm JSON-LD outputs again.
3) Debug readiness box:
   - With `WP_DEBUG` off: Debug row shows pill `WP_DEBUG: OFF` and message that debug output requires WP_DEBUG.
   - With `WP_DEBUG` on and admin Debug on: all pills should be ON (WP_DEBUG/Admin Debug/Constant if set).

Social Admin
------------
1) URL validation:
   - Set Global/Facebook/Twitter image fields to an invalid value (e.g., `abc`) and save.
   - Expect: error notice on the Social Media page, field cleared.
   - Set to a valid https URL; no notice.
2) Facebook Page URL:
   - Same pattern: invalid string -> error + cleared; valid URL -> accepted.
3) Social dry run:
   - Enable “social dry run” on the dashboard.
   - Load a front-end page; expect no OG/Twitter meta tags in page source, but `BE_SOCIAL_DRY_RUN` entries in error log.
   - Disable dry run and confirm tags return.

General
-------
- Confirm settings save normally when all inputs are valid.
- Verify status pills for Person/Organisation/Publisher reflect toggles after save.
