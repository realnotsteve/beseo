BE SEO – Testing Notes
======================

Goal: quick, repeatable checks for the recent validation and debug-visibility changes. These are manual steps; no automated suite is wired up yet.

Prereqs
-------
- WordPress admin access with the BE SEO plugin active.
- For debug/dry-run log checks, set `WP_DEBUG` to `true` in wp-config.php (and ensure logging is enabled in your environment).

Schema Admin (Website / Debug)
------------------------------
1) URL validation (Organisation URL only):
   - Enter an invalid URL (e.g., `foo` or `javascript:alert(1)`) in Organisation URL, save.
   - Expect: error notice on the Schema page, field value cleared.
   - Enter a valid `https://example.com` style URL and save; no notice.
2) Schema dry run:
   - Go to Schema > Debug and enable “Generate but do not output schema”.
   - Load a front-end page; expect no JSON-LD output in source.
   - With WP_DEBUG + “Enable debug output” on, confirm `BE_SCHEMA_DRY_RUN` entries in the error log.
   - Disable dry run and confirm JSON-LD outputs again.
3) Debug logging:
   - With `WP_DEBUG` off: the Debug logging row shows a warning message about WP_DEBUG being required.
   - With `WP_DEBUG` on and “Enable debug output” checked: load a front-end page and confirm `BE_SCHEMA_DEBUG_GRAPH` entries in the error log.

Social Admin
------------
1) URL validation:
   - Set Global/Facebook/Twitter image fields to an invalid value (e.g., `abc`) and save.
   - Expect: error notice on the Social Media page, field cleared.
   - Set to a valid https URL; no notice.
2) Facebook Page URL:
   - Same pattern: invalid string -> error + cleared; valid URL -> accepted.
3) Social dry run:
   - Social Media > Platforms > Facebook > Tools: enable “OpenGraph dry run”.
   - Social Media > Platforms > Twitter > Tools: enable “Twitter dry run”.
   - Load a front-end page; expect OG/Twitter meta tags to be suppressed for the enabled toggles, and `BE_SOCIAL_DRY_RUN` entries in the error log.
   - Disable dry run toggles and confirm tags return.

General
-------
- Confirm settings save normally when all inputs are valid.
- Verify status pills for Person/Organisation/Publisher reflect toggles after save.
