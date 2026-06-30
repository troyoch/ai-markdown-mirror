# AI Markdown Mirror Project Handoff

Version: 0.1.0 MVP

AI Markdown Mirror is a WordPress plugin that exposes AI-readable Markdown mirrors and LLM discovery files for public WordPress content.

## Included in v0.1.0

- Dynamic `.md` mirrors for public singular content
- HTML head alternate links for Markdown mirrors
- Optional HTTP Link header
- Dynamic `/llms.txt`
- Dynamic `/llms-full.txt`
- Settings page
- Public post type include controls
- Per-post and per-page include/exclude controls
- Default noindex headers for machine-readable outputs

## Source Map

- `ai-markdown-mirror.php`: plugin bootstrap
- `includes/class-plugin.php`: plugin loader and head/header links
- `includes/class-settings.php`: settings page and option handling
- `includes/class-markdown.php`: Markdown and LLM text generation
- `includes/class-routes.php`: dynamic routes
- `includes/class-metaboxes.php`: per-content controls

## Next Steps

1. Install ZIP on WordPress staging.
2. Activate plugin.
3. Save settings.
4. Refresh routes.
5. Test `/llms.txt`.
6. Test `/llms-full.txt`.
7. Test a page URL with `.md` appended.
8. Confirm the Markdown alternate link appears in page source.

## Known v0.1.0 Limits

- No drag-and-drop ordering yet.
- No sitemap integration yet.
- No Gravity Forms extraction yet.
- No advanced selector cleanup UI yet.
- Markdown conversion is intentionally simple for the MVP.
