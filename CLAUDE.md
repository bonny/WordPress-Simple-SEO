# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Simple SEO is a small WordPress plugin by Pär (eskapism), on WordPress.org at https://wordpress.org/plugins/simple-seo/ (slug `simple-seo`, about 300 active installs). It went unreleased from 0.3.4 (October 2012) until 0.3.5 (2026-09-24), the first release of the revival. The plan and its status live in [`todo.md`](todo.md).

Goals for the revival: a deliberately tiny SEO plugin (a few fields per post, no settings maze, no upsells), working on modern WordPress and PHP, and a friendly home for existing users.

Principles for everything we build (Pär, 2026-09-25):

- **Fast.** No extra SQL queries on the front end: read post meta only for the queried object (the main `WP_Query` already primes the meta cache), prime caches in bulk where lists are involved (as `get_pages` does), no autoloaded options, no settings page. Load admin CSS/JS only on the editor screens.
- **Not in the way.** No nags, notices, dashboards, or upsells. Nothing on screens where the fields aren't needed.
- **Nice to use.** Few fields, clear labels, sensible defaults.
- **Built for 2026 search.** GEO is mostly plain SEO; no llms.txt, meta keywords or AI scores. Evidence and scope in [`docs/seo-2026-research.md`](docs/seo-2026-research.md).
- **AI friendly, not AI first.** Fields are registered post meta exposed over REST, so WP-CLI, the REST API and AI tools can read and write them. No AI-specific features that cost anything for people who don't use them.

## History

This git repo was created on 2026-09-24 by replaying the WordPress.org SVN trunk history (r279243 to r607674) into git with the original authors, dates, and messages. Git tags `0.1` and `0.3.1` to `0.3.4` point at the matching trunk commits. SVN is still the release channel; GitHub (`bonny/simple-seo`, private for now) is the source of truth for development.

How old the SEO plugins are, which still live, and their installs and downloads: [`docs/seo-plugin-history.md`](docs/seo-plugin-history.md). The readme's "seven weeks before Yoast SEO" line rests on it.

## Code

Released: 0.3.5. `main` holds unreleased 1.0 work.

- `simple-seo.php` is a bootstrap that must stay parseable by PHP 5.6 (WordPress before 5.2 ignores "Requires PHP" and installs updates anyway). Below WordPress 6.6 or PHP 7.4 it shows an admin notice linking the 0.3.5 zip and loads nothing else. Otherwise it requires the files in `src/`.
- `src/` is namespaced (`SimpleSEO\`; no other plugin on WordPress.org declares it, checked 2026-09-25 with a case-insensitive regex search of all 76,734 plugins on veloria.dev, API in `PeterBooker/veloria` `docs/api.md`), PHP 7.4 syntax, one file per concern: `meta.php` (fields and getters), `frontend.php` (head output, menu label), `classic-editor.php` (the old fields below the title, saving). Keep it tight: functions, no classes or containers until something needs them. YAGNI for features.
- Post meta keys:
  - `_simple_seo_title`, `_simple_seo_description` (strings) and `_simple_seo_noindex` (boolean), registered with `register_post_meta()` and `show_in_rest`. REST writes need `edit_post`. Read them through `SimpleSEO\get_title()`, `get_description()` and `is_noindex()`, never with raw `get_post_meta()`.
  - Pre-1.0 title: `_simple_seo_use_custom_page_title` (0/1) and `_simple_seo_custom_page_title_value`. Existing sites depend on it, so `get_title()` falls back to it. Any write to `_simple_seo_title` (editor, REST, WP-CLI) deletes the old pair. No bulk migration, no option to track one.
  - Menu label: `_simple_seo_use_custom_menu_label` (0/1) and `_simple_seo_custom_menu_label_value`, unchanged. Kept for existing users, pages only, Classic meta box only (Pär, 2026-09-25).
- Front end, reading meta only for the queried post (already cached by the main query):
  - Title: the `single_post_title` filter replaces the post's part, and core adds " – Site name" (this also covers old themes calling `wp_title()`). A static front page never calls `single_post_title`, so `document_title_parts` makes its SEO title the whole title (tagline dropped), and a `wp_title` filter does the same for old themes.
  - Meta description in `wp_head`. A front page with the latest posts uses the tagline.
  - noindex via `wp_robots`.
  - The menu label only affects `get_pages()` / `wp_list_pages()`. That includes the Page List block (`wp-includes/blocks/page-list.php` calls `get_pages()`), which an empty Navigation block falls back to: WordPress creates a `wp_navigation` post containing `<!-- wp:page-list /-->` (verified in Twenty Twenty-Three on the php74 site). It does not reach classic menus or Navigation blocks with hand-picked Page Link blocks, which store their own labels.
- The Classic Editor fields are printed on `dbx_post_sidebar` and moved into `#titlediv` with jQuery. The block editor panel is still to come.

## Local development

The repo is bind-mounted into every WordPress site in the docker stack (`../_docker-compose-to-run-on-system-boot`, anchor `mnt-simple-seo`) as `wp-content/plugins/simple-seo`. Edits are live immediately.

Run WP-CLI through the paired service:

```bash
cd ../_docker-compose-to-run-on-system-boot
docker compose run --rm wpcli_mariadb plugin list --name=simple-seo
```

The main test site is `http://wp-playground-classiceditor.test:8314` (service `wordpress_playground_classiceditor`, WP-CLI `wpcli_classiceditor`, added 2026-09-25): current WordPress with Classic Editor always active and Simple SEO active, `blog_public` = 1 so sitemap and robots output work, pretty permalinks. Keep Classic Editor active there. Logins and a REST application password for the `claude` user are in the gitignored `CLAUDE.local.md`.

The `wordpress_php74` site has no paired wpcli service; `scripts/smoke-test.sh` shows how to run a one-off `wordpress:cli` container against it. It was installed on 2026-09-24 (admin / admin) and updated to current WordPress.

The local sites have "Discourage search engines" on (`blog_public` = 0), so core's `/wp-sitemap.xml` returns 404 and WordPress adds its own noindex. To test sitemap or robots output, run `option update blog_public 1` through WP-CLI and set it back to 0 afterwards.

Jetpack and Rank Math were deactivated on the stable site (2026-09-24) because they exhausted WP-CLI's 128M memory limit. If WP-CLI dies with "Allowed memory size exhausted" again, check for a newly activated heavy plugin, or add `--skip-plugins`.

## Checks

```bash
composer install                   # once
composer check                     # PHPCS (WPCS + PHPCompatibilityWP) and PHPStan level 5; both must be clean
PHP_CLI_VERSION=74 docker compose run --rm php-lint composer check   # same, on PHP 7.4
scripts/smoke-test.sh classic      # Classic Editor save + front end, on the Classic Editor site
scripts/smoke-test.sh stable       # same on the stable site, PHP 8.3
scripts/smoke-test.sh php74        # same on PHP 7.4
scripts/plugin-check.sh            # WordPress.org Plugin Check on the .distignore build
```

`scripts/old-wp/compose.yaml` is a throwaway WordPress 4.9 / PHP 5.6 stack for checking the bootstrap's too-old notice: activate the plugin there, and the dashboard must show the notice with no fatal error (checked 2026-09-25). Run `php -l simple-seo.php` with a `php:5.6-cli` container after touching the bootstrap.

The smoke test needs Simple SEO and Classic Editor active on the site; only the `classic` site keeps them on, so activate them on `stable`/`php74` first and switch back after. For screenshots and a browser check, use the `visual-check` skill (`.claude/skills/visual-check/`). Floors are PHP 7.4 and WP 6.6, set in the plugin header, `readme.txt`, `phpcs.xml.dist` and `phpstan.neon.dist` (wp-compat). There is no baseline; any new PHPCS or PHPStan error is a regression. CI (`.github/workflows/lint.yml`) runs `composer check` on PHP 7.4 for every push and PR.

## Releasing

Use the `cutting-a-release` skill; it's Pär's call, so never bump or tag unless asked. In short: `.github/workflows/deploy.yml` deploys trunk + tag to WordPress.org SVN with the 10up action when a semver tag is pushed, and syncs `.wordpress-org/` (screenshots, Live Preview blueprint) to SVN `assets/`. It needs the `SVN_USERNAME` and `SVN_PASSWORD` repo secrets (set, username `eskapism`). Readme or asset changes between releases go out with the manual `readme-assets.yml` workflow; see the skill. `.distignore` decides what ships.

The last line of `readme.txt` is a Wordfence verification string (`ysaetf7ruhjnm3e2x4tbtletpc35ckeb`, added 2026-09-25). Keep it there, also when the readme is rewritten.

Between releases the version stays at the last released one (`bumping-version` skill changes it) and changelog lines go under `= Unreleased =` in `readme.txt`, written for users.

## Conventions

- Take tooling and conventions from `../WordPress-CMS-Tree-Page-View` (the most recent plugin Pär revived) and, behind it, Simple History. Keep it proportionate: this plugin is small.
- Pär posts all public content himself. Draft readme copy, WordPress.org replies, and blog posts into `todo.md` or a `todos/` file and stop.
- Readme tone: short, personal, a bit funny. See the example copy in `todo.md` and the draft in `todos/readme-draft.md`.
- Images: every committed PNG goes through `pngquant` then `oxipng` (see the `visual-check` skill). The icon and banner come from `.wordpress-org/icon.svg` and the `generating-banner` skill; design drafts go in the gitignored `.design-drafts/`.
