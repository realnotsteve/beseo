# Changelog

All notable changes to this project will be documented in this file. The format follows clear, human-readable version entries grouped by change type.

<hr />

## [2.1.19] - 2025-12-18
Summary: Engine.

### Changed
- Touched areas: engine.

### Files
- includes/engine/core-elementor.php
- includes/engine/core-site-entities.php


## [2.1.18] - 2025-12-17
Summary: Engine.

### Changed
- Touched areas: engine.

### Files
- includes/engine/core-elementor.php


## [2.1.17] - 2025-12-17
Summary: Engine.

### Changed
- Touched areas: engine.

### Files
- includes/engine/core-breadcrumbs.php
- includes/engine/core-posts.php
- includes/engine/core-site-entities.php
- includes/engine/core-special-pages.php


## [2.1.16] - 2025-12-17
Summary: Engine.

### Changed
- Touched areas: engine.

### Files
- beseo.php
- includes/admin/admin-menu.php
- includes/admin/page-sitemap.php
- includes/admin/schema-view-website.php
- includes/admin/schema-view.php
- includes/engine/core-elementor.php
- includes/engine/core-posts.php
- includes/engine/core-site-entities.php
- includes/engine/core-sitemap.php
- includes/engine/core-special-pages.php


## [2.1.15] - 2025-12-16
Summary: Social admin.

### Changed
- Touched areas: social admin.

### Files
- includes/admin/page-social-media.php
- includes/admin/page-tools.php
- includes/admin/partials/validator-panel.php
- includes/admin/partials/validator-script.php
- includes/admin/partials/validator-styles.php


## [2.1.14] - 2025-12-16
Summary: Social admin.

### Changed
- Touched areas: social admin.

### Files
- includes/admin/page-social-media.php


## [2.1.13] - 2025-12-16
Summary: Social admin.

### Changed
- Touched areas: social admin.

### Files
- includes/admin/page-social-media.php


## [2.1.12] - 2025-12-16
Summary: Social admin.

### Changed
- Touched areas: social admin.

### Files
- includes/admin/page-social-media.php


## [2.1.11] - 2025-12-16
Summary: Social admin.

### Changed
- Touched areas: social admin.

### Files
- includes/admin/admin-menu.php
- includes/admin/page-social-media.php


## [2.1.10] - 2025-12-16
Summary: Social admin.

### Changed
- Touched areas: social admin.

### Files
- includes/admin/admin-menu.php
- includes/admin/page-social-media.php


## [2.1.9] - 2025-12-15
Summary: Devnotes + schema admin.

### Changed
- Touched areas: devnotes, schema admin.

### Files
- CHANGELOG.md
- assets/css/schema.css
- assets/js/schema.js
- beseo-devnotes.json
- beseo.php
- bin/smoke-schema.sh
- includes/admin/page-schema.php
- includes/admin/schema-view-settings.php
- includes/admin/schema-view-website.php
- includes/admin/schema-view.php


## [2.1.8.11] - 2025-12-16
Summary: Fix schema Website tab load order and fatal.

### Fixed
- Load the Website tab partial only inside the schema render flow so translations/variables are initialised correctly, removing the early textdomain notice and the in_array fatal on schema admin.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/schema-view.php
- includes/admin/schema-view-website.php

## [2.1.8.9] - 2025-12-15
Summary: Schema assets externalised + smoke script.

### Changed
- Moved schema admin CSS/JS out of the view into `assets/css/schema.css` and `assets/js/schema.js`, with localized strings and screen-scoped enqueue.
- Added a light smoke script `bin/smoke-schema.sh` to lint key schema files (and optional WP bootstrap check when wp-cli is available).
- Ran `bin/smoke-schema.sh` to verify schema files lint clean.

## [2.1.8.10] - 2025-12-15
Summary: Ensure schema assets load consistently.

### Changed
- Schema admin CSS/JS now enqueues without screen gating to avoid missing styles/scripts on the Schema page after the extraction.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-schema.php

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-schema.php
- includes/admin/schema-view.php
- assets/css/schema.css
- assets/js/schema.js
- bin/smoke-schema.sh

## [2.1.8.8] - 2025-12-15
Summary: Website tab extracted to partial.

### Changed
- Moved the full Website tab markup out of `schema-view.php` into `schema-view-website.php`, replacing it with a single include to shrink the main view.
- Loader now pulls the website partial alongside the settings partial.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/schema-view.php
- includes/admin/schema-view-website.php

## [2.1.8.7] - 2025-12-15
Summary: Schema settings tab extracted.

### Changed
- Wired the schema Dashboard tab to a dedicated partial (`schema-view-settings.php`) via the loader, continuing the break-up of the 5k-line schema view.
- Bumped version metadata to match the refactor step.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-schema.php
- includes/admin/schema-view.php
- includes/admin/schema-view-settings.php

## [2.1.8] - 2025-12-15
Summary: Devnotes + schema admin.

### Changed
- Touched areas: devnotes, schema admin.

### Files
- CHANGELOG.md
- assets/css/analyser.css
- assets/js/analyser.js
- beseo-devnotes.json
- beseo.php
- includes/admin/analyser-service.php
- includes/admin/page-analyser.php
- includes/admin/page-schema.php
- includes/admin/schema-service.php


## [2.1.7.5] - 2025-12-15
Summary: Schema refactor groundwork.

### Changed
- Added schema-service include for shared helpers/saving logic and guarded definitions to allow further splitting without redeclare issues.
- Ensured schema page pulls helpers from the new service file to prep for tab-level extraction.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/schema-service.php
- includes/admin/page-schema.php
- includes/admin/admin-menu.php

## [2.1.7.4] - 2025-12-15
Summary: Analyser refactor and asset extraction.

### Changed
- Split analyser into service/controller, external JS/CSS assets, and screen-scoped enqueues for cleaner maintenance.
- Localized UI strings and preserved crawl controls (pause/resume, exports, progress) with the new asset pipeline.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/analyser-service.php
- includes/admin/page-analyser.php
- assets/js/analyser.js
- assets/css/analyser.css

## [2.1.7.3] - 2025-12-15
Summary: Crawl UI polish and richer checks.

### Added
- Pause/resume controls, elapsed/current/error progress display, and CSV/JSON export for analyser crawls.
- Extra crawl validations: broken internal links (sampled), duplicate titles across pages, missing OG/Twitter tags, and off-domain canonicals.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-analyser.php

## [2.1.7] - 2025-12-15
Summary: Update.

### Changed
- Updated staged files.

### Files
- includes/admin/page-analyser.php


## [2.1.6] - 2025-12-15
Summary: Devnotes.

### Changed
- Touched areas: devnotes.

### Files
- CHANGELOG.md
- beseo-devnotes.json
- beseo.php
- includes/admin/page-analyser.php


## [2.1.5] - 2025-12-15
Summary: Devnotes.

### Changed
- Touched areas: devnotes.

### Files
- CHANGELOG.md
- beseo-devnotes.json

## [2.1.5.2] - 2025-12-15
Summary: Analyser multi-page aggregate + targets.

### Added
- Saved Websites list in Analyser settings and toggle between saved sites and manual URL for crawls.
- Aggregated issue summary across crawl (severity/type counts) and per-page issue listing.
- Crawl controls now show progress and issue table updates during multi-page runs.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-analyser.php
- beseo.php


## [2.1.4] - 2025-12-15
Summary: Update.

### Changed
- Updated staged files.

### Files
- includes/admin/page-analyser.php


## [2.1.3] - 2025-12-15
Summary: Devnotes.

### Changed
- Touched areas: devnotes.

### Files
- CHANGELOG.md
- beseo-devnotes.json
- beseo.php
- includes/admin/admin-menu.php
- includes/admin/page-analyser.php
- includes/admin/page-tools.php


## [2.1.1] - 2025-12-15
Summary: Devnotes.

### Changed
- Touched areas: devnotes.

### Files
- CHANGELOG.md
- beseo-devnotes.json
- beseo.php
- includes/admin/page-tools.php


## [2.0.24] - 2025-12-15
Summary: Devnotes.

### Changed
- Touched areas: devnotes.

### Files
- CHANGELOG.md
- beseo-devnotes.json
- beseo.php
- includes/admin/page-tools.php


## [2.0.22] - 2025-12-15
Summary: Remove Tools Settings tab.

### Changed
- Removed the Settings tab from Tools; Tools now focuses on Dashboard, Validator, Images, and Help (when applicable).

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-tools.php

## [2.1.0] - 2025-12-15
Summary: Minor bump for Validator and Tools updates.

### Changed
- Incremented version to 2.1.0.
- Added Tools Analysis tab placeholder and ongoing Validator UX refinements.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-tools.php

## [2.1.1] - 2025-12-15
Summary: Analyser submenu scaffold.

### Added
- New Analyser submenu with Overview/Issues/Pages/History/Settings tabs and issue group sidebar.

### Changed
- Removed the Analysis tab from Tools; Tools remains Dashboard/Validator/Images/Help.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/admin-menu.php
- includes/admin/page-analyser.php
- includes/admin/page-tools.php

## [2.1.4.1] - 2025-12-15
Summary: Introduce build version segment.

### Changed
- Incremented version to 2.1.4.1 (adds build segment to versioning).

### Files
- beseo.php
- beseo-devnotes.json

## [2.0.23] - 2025-12-15
Summary: Add Tools Analysis tab (placeholder).

### Added
- Added an Analysis tab under Tools (placeholder content).

### Changed
- Incremented version to 2.0.23.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-tools.php

## [2.0.21] - 2025-12-15
Summary: Validator persistence, metrics, and UI controls.

### Added
- Fetch timing/redirect badges, fetch log drawer, and image/type/dimension metrics in Validator results.
- Copy summary action, per-platform preview visibility toggles, and session persistence for validator inputs.
- External validators now include LinkedIn Post Inspector and Metatags, plus a new-tab toggle.

### Changed
- Refined header spacing/layout; external open defaults to new tab.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-tools.php

## [2.0.20] - 2025-12-15
Summary: Update.

### Changed
- Updated staged files.

### Files
- includes/admin/page-tools.php


## [2.0.19] - 2025-12-15
Summary: Update.

### Changed
- Updated staged files.

### Files
- includes/admin/page-tools.php


## [2.0.18] - 2025-12-15
Summary: Devnotes + social admin.

### Changed
- Touched areas: devnotes, social admin.

### Files
- CHANGELOG.md
- beseo-devnotes.json
- beseo.php
- includes/admin/page-social-media.php
- includes/admin/page-tools.php


## [2.0.17] - 2025-12-15
Summary: Tools Validator UI + server-side validation.

### Added
- New Validator tab layout with previews, source map, warnings, optional posts, search, manual URL mode, and crop overlays.
- Server-side validator that fetches pages/images, resolves platform sources, and flags Twitter downgrade, type, and aspect-ratio issues.

### Changed
- Tools now defaults to the Validator tab; Twitter Tools points users to the Validator.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-social-media.php
- includes/admin/page-tools.php

## [2.0.16] - 2025-12-15
Summary: Tools Validator tab + cleanup.

### Changed
- Added a Validator tab under Tools with dropdown/manual URL selection and buttons for BESEO validator and Twitter’s validator (new tab).
- Removed the Twitter Tools embed/controls; Twitter Tools now points users to Tools → Validator.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-social-media.php
- includes/admin/page-tools.php


## [2.0.13] - 2025-12-15
Summary: Twitter validator button fallback.

### Changed
- Added load/fallback logic and clearer note for the Twitter Card Validator embed; auto-opens a new tab if embedding is blocked.
- Versions bumped to 2.0.13.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-social-media.php

## [2.0.14] - 2025-12-15
Summary: Twitter Tools validator UI revamp.

### Changed
- Removed the iframe attempt; added BESEO Validator button with dropdown/manual URL selection and a backup button for Twitter’s validator.
- Added mode toggle (site page vs manual URL) with inline feedback.

### Files
- includes/admin/page-social-media.php


## [2.0.12] - 2025-12-15
Summary: Twitter validator buttons + embed load.

### Changed
- Twitter > Tools now offers buttons to load the Card Validator in-panel or in a new tab; the iframe starts blank until loaded.
- Versions bumped to 2.0.12.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-social-media.php


## [2.0.11] - 2025-12-15
Summary: Twitter Tools default to validator.

### Changed
- Twitter > Tools now opens with the Tools subtab active so the Card Validator iframe is immediately visible.
- Versions bumped to 2.0.11.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-social-media.php


## [2.0.10] - 2025-12-15
Summary: Social admin.

### Changed
- Touched areas: social admin.

### Files
- includes/admin/js/be-twitter-handles.js
- includes/admin/page-social-media.php


## [2.0.9] - 2025-12-15
Summary: Devnotes + schema admin + social admin.

### Changed
- Touched areas: devnotes, schema admin, social admin.

### Files
- CHANGELOG.md
- beseo-devnotes.json
- beseo.php
- includes/admin/admin-menu.php
- includes/admin/js/be-help-accent.js
- includes/admin/js/be-optional-fields.js
- includes/admin/page-help-text.php
- includes/admin/page-schema.php
- includes/admin/page-social-media.php
- includes/admin/page-tools.php


## [2.0.7] - 2025-12-14
Summary: Remove Twitter additional aspect ratios.

### Changed
- Removed the Additional Aspect Ratios dropdown and fields from Twitter > Content.
- Bumped versions to 2.0.7.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-social-media.php

## [2.0.8] - 2025-12-14
Summary: Twitter image labels + card type toggle.

### Changed
- Renamed Twitter images to Large Summary Card / Summary Card and added a radio toggle for card type.
- Kept Summary Card image as the secondary fallback, Large Summary as primary.
- Bumped versions to 2.0.8.

### Files
- includes/admin/page-social-media.php
- beseo.php
- beseo-devnotes.json


## [2.0.6] - 2025-12-14
Summary: Cyan help accents via {braces}.

### Changed
- Added a shared helper script to highlight help text wrapped in {curly braces} using WordPress cyan (#00a0d2).
- Enqueued the helper and styling across Schema, Social, and Settings/Help tabs.
- Bumped versions to 2.0.6.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/js/be-help-accent.js
- includes/admin/page-schema.php
- includes/admin/page-social-media.php
- includes/admin/page-tools.php


## [2.0.5] - 2025-12-14
Summary: Keep Help tab active after saving overrides.

### Changed
- Settings submenu now returns to the Help Text tab after saving overrides (or when `tab=help` is requested).
- Bumped versions to 2.0.5.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-tools.php


## [2.0.4] - 2025-12-14
Summary: Help text overrides now live under Settings tab.

### Changed
- Moved Help Text overrides into a tab under the Settings submenu; removed the separate Help Text submenu.
- Reused override form within Settings tabs; defaults still apply unless overridden.
- Bumped versions to 2.0.4.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/admin-menu.php
- includes/admin/page-help-text.php
- includes/admin/page-tools.php


## [2.0.3] - 2025-12-14
Summary: Help text overrides + settings submenu.

### Changed
- Added Help Text submenu with override UI and gettext-based overrides.
- Bumped plugin/devnotes version to 2.0.3 for the new feature.
- Tools page now adapts tabs for the Help Text entry point.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/admin-menu.php
- includes/admin/page-help-text.php
- includes/admin/page-tools.php


## [2.0.2] - 2025-12-14
Summary: Tooling + devnotes.

### Changed
- Touched areas: tooling, devnotes.

### Files
- .githooks/pre-commit
- .githooks/prepare-commit-msg
- BE_SEO-Dev_Notes.json
- CHANGELOG.md
- beseo-devnotes.json


## [2.0.1] - 2025-12-14
Summary: Devnotes + engine.

### Changed
- Touched areas: devnotes, engine.

### Files
- CHANGELOG.md
- beseo-devnotes.json
- beseo.php
- includes/engine/core-site-entities.php
- includes/engine/core-social-settings.php
- includes/engine/core-social.php


## [1.3.76] - 2025-12-14
Summary: Devnotes.

### Changed
- Touched areas: devnotes.

### Files
- beseo-devnotes.json


## [1.3.75] - 2025-12-14
Summary: Devnotes.

### Changed
- Touched areas: devnotes.

### Files
- beseo-devnotes.json
- includes/admin/admin-menu.php
- includes/admin/page-tools.php


## [1.3.74] - 2025-12-14
Summary: Devnotes + schema admin + social admin + engine.

### Changed
- Touched areas: devnotes, schema admin, social admin, engine.

### Files
- beseo-devnotes.json
- beseo.php
- includes/admin/js/be-image-pills.js
- includes/admin/js/be-optional-fields.js
- includes/admin/page-overview.php
- includes/admin/page-schema.php
- includes/admin/page-social-media.php
- includes/engine/core-helpers.php
- includes/engine/core-social-settings.php
- includes/engine/core-social.php


## [1.3.73] - 2025-12-14
Summary: Devnotes + engine.

### Changed
- Touched areas: devnotes, engine.

### Files
- beseo-devnotes.json
- includes/engine/core-social.php


## [1.3.72] - 2025-12-13
Summary: Devnotes + social admin + engine.

### Changed
- Touched areas: devnotes, social admin, engine.

### Files
- beseo-devnotes.json
- includes/admin/page-social-media.php
- includes/engine/core-social.php


## [1.3.71] - 2025-12-13
Summary: Social admin.

### Changed
- Touched areas: social admin.

### Files
- includes/admin/page-social-media.php


## [1.3.70] - 2025-12-13
Summary: Devnotes + social admin + engine.

### Changed
- Touched areas: devnotes, social admin, engine.

### Files
- beseo-devnotes.json
- includes/admin/admin-menu.php
- includes/admin/page-social-media.php
- includes/engine/core-social.php


## [1.3.69] - 2025-12-13
Summary: Devnotes + social admin + engine.

### Changed
- Touched areas: devnotes, social admin, engine.

### Files
- beseo-devnotes.json
- includes/admin/page-social-media.php
- includes/engine/core-social.php


## [1.3.68] - 2025-12-13
Summary: Devnotes + schema admin + engine.

### Changed
- Touched areas: devnotes, schema admin, engine.

### Files
- beseo-devnotes.json
- includes/admin/page-schema.php
- includes/engine/core-helpers.php


## [1.3.67] - 2025-12-13
Summary: Schema admin.

### Changed
- Touched areas: schema admin.

### Files
- includes/admin/page-schema.php


## [1.3.66] - 2025-12-13
Summary: Devnotes + social admin.

### Changed
- Touched areas: devnotes, social admin.

### Files
- beseo-devnotes.json
- includes/admin/page-social-media.php


## [1.3.65] - 2025-12-13
Summary: Devnotes + schema admin + engine.

### Changed
- Touched areas: devnotes, schema admin, engine.

### Files
- beseo-devnotes.json
- includes/admin/page-schema.php
- includes/engine/core-helpers.php


## [1.3.64] - 2025-12-13
Summary: Tooling + devnotes.

### Changed
- Touched areas: tooling, devnotes.

### Files
- .githooks/prepare-commit-msg
- CHANGELOG.md
- beseo-devnotes.json
- beseo.php


## [1.3.63] - 2025-12-13
Summary: Tooling + devnotes.

### Changed
- Touched areas: tooling, devnotes.

### Files
- .githooks/prepare-commit-msg
- beseo-devnotes.json


## [1.3.62] - 2025-12-13
Summary: Devnotes + schema admin.

### Changed
- Touched areas: devnotes, schema admin.

### Files
- beseo-devnotes.json
- includes/admin/page-schema.php


## [1.3.61] - 2025-12-13
Summary: Schema admin.

### Changed
- Touched areas: schema admin.

### Files
- includes/admin/page-schema.php


## [1.3.60] - 2025-12-12
Summary: Devnotes + schema admin.

### Changed
- Touched areas: devnotes, schema admin.

### Files
- beseo-devnotes.json
- includes/admin/page-schema.php


## [1.3.59] - 2025-12-12
Summary: Devnotes + schema admin + engine.

### Changed
- Touched areas: devnotes, schema admin, engine.

### Files
- beseo-devnotes.json
- includes/admin/page-schema.php
- includes/engine/core-helpers.php


## [1.3.58] - 2025-12-12
Summary: Devnotes + schema admin + social admin + engine.

### Changed
- Touched areas: devnotes, schema admin, social admin, engine.

### Files
- beseo-devnotes.json
- includes/admin/page-schema.php
- includes/admin/page-social-media.php
- includes/engine/core-helpers.php
- includes/engine/core-site-entities.php


## [1.3.57] - 2025-12-12
Summary: Devnotes + schema admin + social admin.

### Changed
- Touched areas: devnotes, schema admin, social admin.

### Files
- beseo-devnotes.json
- includes/admin/page-schema.php
- includes/admin/page-social-media.php


## [1.3.56] - 2025-12-12
Summary: Schema admin.

### Changed
- Touched areas: schema admin.

### Files
- includes/admin/page-schema.php


## [1.3.55] - 2025-12-12
Summary: Schema admin.

### Changed
- Touched areas: schema admin.

### Files
- includes/admin/page-schema.php


## [1.3.54] - 2025-12-12
Summary: Schema admin.

### Changed
- Touched areas: schema admin.

### Files
- includes/admin/page-schema.php
