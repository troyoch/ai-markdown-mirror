# AI Markdown Mirror

AI Markdown Mirror is a WordPress plugin that creates AI-readable Markdown mirrors, `/llms.txt`, `/llms-full.txt`, and hidden discovery links in the HTML head.

## MVP Features

- Dynamic `.md` versions of public posts and pages
- `<link rel="alternate" type="text/markdown">` in singular HTML page heads
- Optional HTTP `Link` header to the Markdown mirror
- Dynamic `/llms.txt`
- Dynamic `/llms-full.txt`
- Noindex headers for Markdown/LLM outputs by default
- Settings page under Settings → AI Markdown Mirror
- Public post type selection
- Per-post/per-page include/exclude controls
- Custom intro, guidance, and footer/disclaimer text for `llms.txt`

## Example URLs

Normal page:

```text
https://example.com/my-page/
```

Markdown mirror:

```text
https://example.com/my-page.md
```

LLM discovery files:

```text
https://example.com/llms.txt
https://example.com/llms-full.txt
```

## Safety Notes

This first version is intentionally local-only. It does not call external APIs, track visitors, or add any SaaS connection.
MD and LLM outputs send `X-Robots-Tag: noindex, nofollow` by default to reduce duplicate-content risk.
