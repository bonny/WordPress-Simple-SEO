---
name: generating-banner
description: Use when the Simple SEO WordPress.org banner (banner-772x250.png / banner-1544x500.png) or icon (icon-128x128.png / icon-256x256.png) needs (re)generating, after a branding, headline or color change.
---

# Generating the WordPress.org banner and icon

Both are rendered from committed sources, like CMS Tree Page View's `generating-banner` skill, so there's no hand-painted PNG to lose. Output goes to `.wordpress-org/`, which the deploy and readme-assets workflows sync to SVN `assets/`.

## Sources

- `.wordpress-org/icon.svg`: the icon, source of truth. A search result card (blue title, green link, grey text) on a grape (`#8a3ffc`) to pink (`#ff6fa8`) rounded square. Picked by Pär on 2026-09-24 from three drafts.
- `banner.html` (this folder): layout, copy and palette. Headline "Really simple SEO. *That's it.*", a search result showing the custom title from the screenshots' Acme Coffee demo, and a handwritten "your custom title" arrow.
- `fonts/`: Quicksand (headline), Nunito (brand, sub-copy), Caveat (script accent). Copied from CMS Tree Page View so the plugins look like a family; bundled so renders don't depend on system fonts.

## Render

Playwright MCP `browser_run_code_unsafe` with `filename: .claude/skills/generating-banner/render.js`. It writes `banner-772x250.png`, `banner-1544x500.png` (2x), `icon-128x128.png` and `icon-256x256.png` (transparent corners). No WordPress site needed.

Then compress, as for every committed PNG:

```bash
pngquant --quality=80-95 --strip --skip-if-larger --force --ext .png .wordpress-org/banner-*.png .wordpress-org/icon-*.png
oxipng -o max --strip safe .wordpress-org/banner-*.png .wordpress-org/icon-*.png
```

2026-09-24: banner 338/116 KB -> 92/36 KB, icons 17/6 KB -> 3.4/1.9 KB. Check the gradients for banding; if they band, raise to `--quality=90-100`.

## Publish

Commit, then run the manual readme/assets workflow (Pär's call, see `cutting-a-release`), or let the next release carry it. WordPress.org caches assets, so the plugin page can take a while to update.

## Gotchas

- Chromium won't load a `file://` image into `about:blank`, so `render.js` opens `banner.html` before `setContent()` for the icon. Without that the icon PNGs come out empty (about 1 KB).
- Look at the icon at 64 px (the admin plugin list) and 32 px before changing it.
- Throwaway design exploration goes in the gitignored `.design-drafts/`.
