---
name: generating-banner
description: Use when the Simple SEO WordPress.org banner (banner-772x250.png / banner-1544x500.png) or icon (icon-128x128.png / icon-256x256.png) needs (re)generating, after a branding, headline or color change.
---

# Generating the WordPress.org banner and icon

Both are rendered from committed sources, like CMS Tree Page View's `generating-banner` skill, so there's no hand-painted PNG to lose. Output goes to `.wordpress-org/`, which the deploy and readme-assets workflows sync to SVN `assets/`.

## Sources

- `.wordpress-org/icon.svg`: the icon, source of truth. A search result card (green link line, blue title, grey text, in Google's order) on a flat sunny yellow (`#FFD23F`) rounded square. Pär picked the card from three drafts on 2026-09-24. It first had a grape to pink gradient; an art director review found that read as a polished SaaS app and blended in with the purple SEO competitors (Yoast, Rank Math), so it went flat yellow: calm but distinct, and between the family's mint Simple History and coral CMS Tree Page View. Keep it flat, no gradients.
- `banner.html` (this folder): layout, copy and palette. Warm cream background (`#FFF8E1`) with one soft yellow glow, headline "Really simple SEO. *That's it.*", a search result showing the custom title from the screenshots' Acme Coffee demo, and handwriting in link blue (`#2f6fed`) pointing at it.
- `fonts/`: Quicksand (headline), Nunito (brand, sub-copy), Caveat (script accent). Copied from CMS Tree Page View so the plugins look like a family; bundled so renders don't depend on system fonts.

## Render

Playwright MCP `browser_run_code_unsafe` with `filename: .claude/skills/generating-banner/render.js`. It writes `icon-128x128.png` and `icon-256x256.png` (transparent corners) to `.wordpress-org/`, and `banner-772x250.png` / `banner-1544x500.png` (2x) to `bannerDir`. **The banner is not approved yet:** Pär published the icon alone on 2026-09-24 and rejected the current banner, so `bannerDir` is the gitignored `.design-drafts/`. Everything in `.wordpress-org/` is mirrored to SVN `assets/` (rsync `--delete`), so only put a banner there once it's approved. No WordPress site needed.

Then compress, as for every committed PNG:

```bash
pngquant --quality=80-95 --strip --skip-if-larger --force --ext .png .wordpress-org/banner-*.png .wordpress-org/icon-*.png
oxipng -o max --strip safe .wordpress-org/banner-*.png .wordpress-org/icon-*.png
```

2026-09-24 (yellow version): banner 203/80 KB -> 61/26 KB, icons 3.7/1.8 KB -> 1.2/0.6 KB. Check the gradients for banding; if they band, raise to `--quality=90-100`.

## Publish

Commit, then run the manual readme/assets workflow (Pär's call, see `cutting-a-release`), or let the next release carry it. WordPress.org caches assets, so the plugin page can take a while to update.

## Gotchas

- Chromium won't load a `file://` image into `about:blank`, so `render.js` opens `banner.html` before `setContent()` for the icon. Without that the icon PNGs come out empty (about 1 KB).
- Look at the icon at 64 px (the admin plugin list) and 32 px before changing it.
- Throwaway design exploration goes in the gitignored `.design-drafts/`.
