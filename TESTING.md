# AI Markdown Mirror Testing Checklist

## Activation

- Upload plugin ZIP.
- Activate plugin without fatal error.
- Confirm Settings → AI Markdown Mirror appears.
- Save settings.
- Click Refresh Markdown and LLM Routes.

## Route Tests

Replace `example.com` with the staging or live domain.

- `https://example.com/llms.txt` loads as plain text.
- `https://example.com/llms-full.txt` loads as plain text.
- `https://example.com/some-page.md` loads as Markdown.
- Disabled/excluded posts return 404 for `.md` if disabled.

## Header Tests

On a normal HTML page, view source and confirm a Markdown alternate link appears in the head.

Expected shape:

```html
<link rel="alternate" type="text/markdown" title="Markdown version" href="https://example.com/some-page.md">
```

## Content Tests

- Headings convert to Markdown headings.
- Links convert to Markdown links.
- Images convert to Markdown image syntax.
- Lists stay readable.
- Arabic text remains readable.
- Custom LLM summary appears in `/llms.txt`.

## SEO Safety Tests

Check response headers for `.md`, `/llms.txt`, and `/llms-full.txt`:

- `X-Robots-Tag: noindex, nofollow` appears when enabled.
- `.md` pages include a canonical Link header pointing back to the HTML page.
