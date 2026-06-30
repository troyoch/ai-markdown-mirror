=== AI Markdown Mirror ===
Contributors: troyochowicz
Tags: markdown, llms.txt, ai, seo, content
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create AI-readable Markdown mirrors, /llms.txt, /llms-full.txt, and hidden discovery links in the HTML head.

== Description ==

AI Markdown Mirror creates clean, AI-readable versions of WordPress content without changing the human-facing page layout.

Features in this first MVP:

* Dynamic .md versions of posts and pages.
* Hidden `<link rel="alternate" type="text/markdown">` tags in the HTML head.
* Optional HTTP Link headers pointing to Markdown mirrors.
* Dynamic `/llms.txt` and `/llms-full.txt`.
* Post type selection.
* Per-post and per-page include/exclude controls.
* Custom title, description, guidance, and footer text for llms.txt.
* Noindex headers for Markdown and LLM outputs by default.

This plugin does not call external APIs, track users, or require an AI provider key.

== Installation ==

1. Upload the plugin ZIP through Plugins → Add New → Upload Plugin.
2. Activate AI Markdown Mirror.
3. Go to Settings → AI Markdown Mirror.
4. Save settings and refresh routes if needed.
5. Test `/llms.txt`, `/llms-full.txt`, and a page URL with `.md` appended.

== Frequently Asked Questions ==

= Does this replace my normal website pages? =

No. It creates alternate machine-readable Markdown versions while keeping your normal HTML pages unchanged.

= Will humans see the Markdown links? =

Not by default. The plugin adds discovery links in the HTML head, not visible buttons in the page body.

= Are Markdown pages indexed by Google? =

By default, the plugin sends `X-Robots-Tag: noindex, nofollow` on Markdown and LLM outputs to reduce duplicate content risk.

== Changelog ==

= 0.1.0 =
* Initial MVP.
