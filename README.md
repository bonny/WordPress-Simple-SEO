![Simple SEO](.wordpress-org/banner-1544x500.png)

# Simple SEO

**The SEO basics for WordPress. Nothing more.**

[![Version on WordPress.org](https://img.shields.io/wordpress/plugin/v/simple-seo?label=wordpress.org&color=FFD23F&labelColor=1e1b3a)](https://wordpress.org/plugins/simple-seo/)
[![Active installs](https://img.shields.io/wordpress/plugin/installs/simple-seo?color=FFD23F&labelColor=1e1b3a)](https://wordpress.org/plugins/simple-seo/advanced/)
[![Tests](https://github.com/bonny/WordPress-Simple-SEO/actions/workflows/tests.yml/badge.svg)](https://github.com/bonny/WordPress-Simple-SEO/actions/workflows/tests.yml)
[![License: GPL v2](https://img.shields.io/badge/license-GPLv2-1e1b3a)](https://www.gnu.org/licenses/gpl-2.0.html)

Three fields on every post and page: an **SEO title**, a **meta description** and **Discourage search engines**. Leave one empty and WordPress does what it always did. No settings page, no scores, no upsells.

A few things just happen:

- 🔗 **Link previews** with the right title, description and image.
- 🗺️ **A cleaner sitemap:** pages you keep out of search are left out.
- ✍️ **Both editors,** plus an SEO column and the fields in **Quick Edit**.
- 🤝 **Plays nice:** with Yoast SEO or another big SEO plugin active, Simple SEO steps aside.
- 📜 **Every change logged** with [Simple History](https://simple-history.com/).

<p align="center">
	<img src=".wordpress-org/screenshot-1.png" width="720" alt="The Simple SEO panel in the block editor sidebar, with an SEO title, a meta description and the discourage search engines checkbox.">
</p>

<details>
<summary><strong>More screenshots</strong></summary>

<br>

**The SEO column and Quick Edit in the Pages list**
<img src=".wordpress-org/screenshot-2.png" alt="The Pages list with an SEO column, and Quick Edit open with the SEO fields.">

**The same column for posts**
<img src=".wordpress-org/screenshot-3.png" alt="The Posts list with the SEO column.">

**The Classic Editor box**
<img src=".wordpress-org/screenshot-4.png" alt="The Simple SEO box below the Classic Editor.">

**The one setting: a default share image**
<img src=".wordpress-org/screenshot-5.png" alt="The Simple SEO section in Settings, General, with a default share image.">

**Every change logged in Simple History**
<img src=".wordpress-org/screenshot-6.png" alt="A Simple History entry showing the old and new SEO title and meta description.">

</details>

**Not included, on purpose:** readability scores, green lights, `llms.txt`, admin notices, "Go Pro" banners, an "optimized by" comment in your HTML, and extra database queries. No plugin makes a page rank; good content does.

## Install

Search for **Simple SEO** in Plugins → Add New Plugin, or get it from [wordpress.org/plugins/simple-seo](https://wordpress.org/plugins/simple-seo/). Needs WordPress 6.6 and PHP 7.4.

## For developers

The fields are post meta (`_simple_seo_title`, `_simple_seo_description`, `_simple_seo_noindex`), readable over REST with `?context=edit`:

```bash
wp post meta update 123 _simple_seo_title "About us: small batch coffee from Stockholm"
```

Everything Simple SEO outputs goes through a filter first: [`docs/hooks.md`](docs/hooks.md).

```bash
npm install && npm run build          # the block editor panel (Node 22.22+ or 24.15+)
composer install && composer check    # PHPCS and PHPStan
npm run env:start && npm run test:php # PHPUnit in wp-env
```

Issues and pull requests are welcome, though the answer to a new feature is often "that's a bit much for a plugin called *Simple*". More in [`CLAUDE.md`](CLAUDE.md).

## About

Simple SEO arrived on WordPress.org in August 2010, seven weeks before Yoast SEO ([history](docs/seo-plugin-history.md)), slept from 2012 to 2026, and is awake again.

Made by [Pär Thernström](https://eskapism.se/): one developer, no company or investors. Also [Simple History](https://simple-history.com/) and [CMS Tree Page View](https://wordpress.org/plugins/cms-tree-page-view/). [A donation](https://eskapism.se/sida/donate/) keeps it going. GPLv2.
