# Project: BE SEO Engine (WordPress + Elementor plugin) [beseo]
Slug: beseo

## Identity check (always do this first)
- Confirm the working directory is this plugin: beseo
- Do not modify other plugins (header-styler, crossfade-scroller) unless explicitly asked.
- Never edit files outside this plugin directory unless explicitly requested.

## What this plugin does
- Outputs SEO-related metadata and JSON-LD schema for WordPress.
- Integrates with Elementor pages/widgets where applicable.

## Core priorities
- Standards compliance and correctness (valid JSON-LD, appropriate Schema.org types).
- Backwards compatibility; prefer feature flags/toggles over breaking changes.
- Keep debug output behind explicit toggles; avoid noisy output in production.

## WordPress + Elementor conventions
- Sanitize inputs, escape outputs, capability checks, nonces.
- Treat Elementor editor separately from frontend rendering.
- Keep schema generation deterministic and cache-friendly.

## Output style (how to respond)
- Do NOT print entire files unless explicitly requested.
- When making changes: specify file path + hook/function + describe resulting schema impact.

## Testing checklist
- Activate plugin: no fatals, no notices.
- Verify schema output for:
  - homepage
  - single post
  - Elementor page
- Basic validation: valid JSON, correct @context/@type, no conflicting duplicates.

## Attribution
- If author credit is needed in plugin code examples, use: Bill Evans