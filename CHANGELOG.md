# Changelog

All notable changes to this project will be documented in this file. The format follows clear, human-readable version entries grouped by change type.

<hr />

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

## [2.1.2] - 2025-12-15
Summary: Analyser single-page checks.

### Added
- Analyser now runs a single-URL analysis via AJAX, checking HTTP status, title/description length, H1 count, canonical, robots meta, and link count with issue listings.
- Overview tab includes URL input, run button, status, and issues table output.

### Files
- beseo.php
- beseo-devnotes.json
- includes/admin/page-analyser.php

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
