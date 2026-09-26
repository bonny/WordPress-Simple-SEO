# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Simple SEO is a small WordPress plugin by Pär (eskapism), on WordPress.org at https://wordpress.org/plugins/simple-seo/ (slug `simple-seo`, about 300 active installs). It went unreleased from 0.3.4 (October 2012) until 0.3.5 (2026-09-24), the first release of the revival. Private notes (the todo list, drafts, plans) live in Pär's Obsidian vault and are linked into the repo by `scripts/setup-private-docs.sh` as `todo.md`, `todos/` and `CLAUDE.private.md` (all git-ignored). Contributors can ignore them. Never put strategy, site plans or business details in tracked files; they go in those private files.

Goals for the revival: a deliberately tiny SEO plugin (a few fields per post, no settings maze, no upsells), working on modern WordPress and PHP, and a friendly home for existing users.

Principles for everything we build (Pär, 2026-09-25):

- **Fast.** No extra SQL queries on the front end: read post meta only for the queried object (the main `WP_Query` already primes the meta cache), prime caches in bulk where lists are involved (as `get_pages` does), no settings page of our own. One exception to "no autoloaded options" (Pär, 2026-09-25): `simple_seo_share_image`, a few bytes, autoloaded so the front end reads it with 0 queries (a non-autoloaded option would cost a query on every page). Load admin CSS/JS only on the editor screens.
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
- `src/` is namespaced (`SimpleSEO\`; no other plugin on WordPress.org declares it, checked 2026-09-25 with a case-insensitive regex search of all 76,734 plugins on veloria.dev, API in `PeterBooker/veloria` `docs/api.md`), PHP 7.4 syntax, one file per concern: `meta.php` (fields and getters), `frontend.php` (head output, menu label), `classic-editor.php` (the meta box, saving), `block-editor.php` (loads the panel), `list-table.php` (column and Quick Edit). Keep it tight: functions, no classes or containers until something needs them. YAGNI for features. Style rules (braces not `if (): endif;`, how to output HTML) are in the `php-code-style` skill.
- Post meta keys:
  - `_simple_seo_title`, `_simple_seo_description` (strings), their `_simple_seo_title_disabled` / `_simple_seo_description_disabled` flags (boolean, missing = on) and `_simple_seo_noindex` (boolean), registered with `register_post_meta()` and shown in REST in the `edit` context only (Pär, 2026-09-25: "leak as little as possible"). Anonymous requests see none of them; use `?context=edit` with an application password to read them. REST writes need `edit_post`. Read them through `SimpleSEO\get_title()`, `get_description()` and `is_noindex()`, never with raw `get_post_meta()`.
  - Pre-1.0 title: `_simple_seo_use_custom_page_title` (0/1) and `_simple_seo_custom_page_title_value`. Existing sites depend on it, so `get_title()` falls back to it. Any write to `_simple_seo_title` (editor, REST, WP-CLI) deletes the old pair. No bulk migration, no option to track one.
  - Menu label: `_simple_seo_use_custom_menu_label` (0/1) and `_simple_seo_custom_menu_label_value`, unchanged. Kept for existing users, pages only, Classic meta box only (Pär, 2026-09-25).
- Link previews (`src/link-previews.php`): Open Graph + `twitter:card` on posts, pages, the blog page and the front page (not archives or search), from the SEO title, description and featured image. On by default, no setting (Pär, 2026-09-25). Turns Jetpack's Open Graph off while ours are on.
- Settings (`src/settings.php`): a "Simple SEO" section on Settings → General via the Settings API (Pär: looks like core, but users must see it's from our plugin, hence the section heading and "From the Simple SEO plugin."). One field, "Default share image", with the media library like core's Site Icon (`build/share-image-field.js`). Stored as `{ id, url, width, height, alt }` so link previews need no queries; refreshed when that attachment's metadata or alt text changes, removed when it's deleted. Used in link previews when a post has no featured image.
- Simple History (`src/simple-history.php`, `src/class-simple-history-logger.php`): when it's active, changes to the fields are logged as one "Updated the SEO for …" event per post and request (captured before our keys are written, logged on `shutdown`), with old and new values, each checkbox and its text logged separately (Pär, 2026-09-26: ticking a box on kept text shows "Use a custom SEO title: No → Yes", not the text as new). Action links under the event (`get_action_links()`, Simple History 5.24+): Edit/View <type> and All <types>, following Simple History's `action-links` skill (plain title in the message, no revisions link). Changes of the default share image are logged too (set, changed, removed; not the refresh after an alt-text edit), with Simple History 5.32+'s image diff (size `small`, the default overflows) and a "General settings" link. The logger class is only loaded from `register_simple_history_logger()`, after checking for Simple History 4.0+'s `Logger` class (as CMS Tree Page View does). PHPStan knows the Simple History classes from `tests/phpstan/simple-history-stubs.php`. Without Simple History: one tip line in the editor fields, for users who can install plugins only.
- Hooks (Pär: "be a nice plugin", let developers modify what we output): filters prefixed `simple_seo_` on every output value, applied right before output, each documented in core's hook docblock style. List and examples in [`docs/hooks.md`](docs/hooks.md); keep it up to date when adding one. Filters on the value, not settings; no actions of our own unless something needs one.
- When another SEO plugin is active (`active_seo_plugin()`: Yoast SEO, Rank Math, All in One SEO, SEOPress, The SEO Framework, detected by constants), none of the head output below is added, and the editor fields say which plugin is in charge. The menu label keeps working. Anything new that outputs SEO tags or touches the sitemap must check it too.
- Front end, reading meta only for the queried post (already cached by the main query):
  - Title: the `single_post_title` filter replaces the post's part, and core adds " – Site name" (this also covers old themes calling `wp_title()`). A static front page never calls `single_post_title`, so `document_title_parts` makes its SEO title the whole title (tagline dropped), and a `wp_title` filter does the same for old themes.
  - Meta description in `wp_head`. A front page with the latest posts uses the tagline.
  - noindex via `wp_robots`.
  - Sitemap: noindexed posts are left out of core's `wp-sitemap.xml` (`wp_sitemaps_posts_query_args`). The noindex meta's sanitize callback stores it as `'1'` or `''`, which the query relies on.
  - The menu label only affects `get_pages()` / `wp_list_pages()`. That includes the Page List block (`wp-includes/blocks/page-list.php` calls `get_pages()`), which an empty Navigation block falls back to: WordPress creates a `wp_navigation` post containing `<!-- wp:page-list /-->` (verified in Twenty Twenty-Three on the php74 site). It does not reach classic menus or Navigation blocks with hand-picked Page Link blocks, which store their own labels.
- Block editor: `src/block-editor.php` loads `build/editor-panel.js` (source `js/editor-panel.js`) on the post edit screen only. A "Simple SEO" `PluginDocumentSettingPanel` made of core components, reading and writing the meta with `useEntityProp`, so it saves with the post. Same checkbox + text pattern as the Classic box. It renders nothing for post types without `custom-fields` support. Keep it looking like core: stable `@wordpress/components` only (`__experimental*` fails lint), core wording and spacing, one short help line per field, the other-plugin message as a plain help paragraph (core panels don't use Notices). Pass only changed keys to `setMeta()` (meta edits are merged). Checkbox state follows the meta, local state only for "ticked but empty", so undo works. Keep `__nextHasNoMarginBottom` / `__next40pxDefaultSize` until the floor is WordPress 7.0 (default there, warnings without them on 6.7–6.9); never pass `__next40pxDefaultSize` to `TextareaControl`. Reviewed against WP 7.1 and Gutenberg trunk on 2026-09-25: no deprecated APIs, and DataForm isn't usable by plugins yet.
- Naming (Pär, 2026-09-26, after a UX and an art director review): the block editor panel and the Classic box are "Simple SEO", so users see where they come from; the list column and the Quick Edit group are "SEO", where space is tight. Help text is one line per field (`src/admin.php`: the title help says "whole title" on the static front page, the noindex checkbox and help follow core's Settings → Reading wording, since noindex is a request search engines may ignore: "Discourage search engines from indexing this page" / "It's up to search engines to honor this request. Anyone with the link can still open the page."). The Simple History tip only shows on posts that use the fields.
- Posts lists (`src/list-table.php`): an "SEO" column right after the title (Pär, 2026-09-26: it's related to the title) (what's used: "Search engines discouraged" first, then "Title:" and "Description:" lines with a muted label (the title text semibold, 600), so each row reads on its own; switched-off text greyed out as "Title (off):" so saved values never seem to vanish; — when nothing is set. Pär picked this on 2026-09-26 from a mockup comparison) and the same checkbox + text fields in Quick Edit, filled by `build/quick-edit.js` from JSON the column keeps in a hidden element. Quick Edit posts the Classic box's field names, so `save_post()` in `classic-editor.php` saves both; Bulk Edit doesn't include them, so it can't touch them. The list query has already loaded the meta: no extra queries. Which post types: `has_seo_fields()` in `meta.php`, shared with the box and the panel.
- Old 0.3.5 titles reach the REST API and the panel through `legacy_title_default()` (`default_post_metadata`). `migrate_legacy_title()` moves them to the new keys on any write of the title or its flag. Moving matters: the REST API skips values equal to the (legacy) default, so ticking the box writes only the flag.
- Which post types get the fields (Classic box and block editor panel): those where `is_post_type_viewable()` is true, except attachments. That's deliberate (Pär, 2026-09-25): "viewable" means the post type has pages visitors and search engines can reach (`publicly_queryable`, or `public` for built-ins), which is what matters for SEO; checking `public` alone would be wrong for CPTs that set the two differently. Attachments are left out because attachment pages are off by default since WordPress 6.4. The block editor panel also needs `custom-fields` support (see todo.md).
- Classic Editor: a plain meta box (`src/classic-editor.php`) on every viewable post type, hidden in the block editor with `__back_compat_meta_box`. Each text field is a checkbox + text, both always visible (Pär: as simple as possible, no dimming or auto-ticking); ticked = used, unticking keeps the text; a ticked box with empty text counts as off everywhere (it behaves the same, and the block editor shows it that way after a reload). Stored as the text plus a `_disabled` flag (`save_field()`); `title_field()` / `description_field()` return `[ on, text ]`. The block editor panel is still to come.
- Posts saved before 1.0 have an unchecked-but-kept title as the old flag + value; `title_field()` reads that until the post is saved.

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
npm install && npm run build      # the block editor panel; commit build/. Needs Node ^22.22.2, ^24.15.0 or >=26 (@wordpress/scripts 36); .nvmrc says 24. On the Mac mini nvm's Node 20 is too old, use Homebrew's: PATH=/opt/homebrew/bin:$PATH
npm run lint:js                    # ESLint + Prettier, WordPress config
composer install                   # once
composer check                     # PHPCS (WPCS + PHPCompatibilityWP) and PHPStan level 5; both must be clean
PHP_CLI_VERSION=74 docker compose run --rm php-lint composer check   # same, on PHP 7.4
npm run env:start && npm run test:php   # PHPUnit integration tests in wp-env (ports 8315/8316), tests/php/. CI runs them too (.github/workflows/tests.yml)
scripts/smoke-test.sh classic      # Classic Editor save + front end, on the Classic Editor site
scripts/smoke-test.sh stable       # same on the stable site, PHP 8.3
scripts/smoke-test.sh php74        # same on PHP 7.4
scripts/plugin-check.sh            # WordPress.org Plugin Check on the .distignore build
```

`scripts/old-wp/compose.yaml` is a throwaway WordPress 4.9 / PHP 5.6 stack for checking the bootstrap's too-old notice: activate the plugin there, and the dashboard must show the notice with no fatal error (checked 2026-09-25). Run `php -l simple-seo.php` with a `php:5.6-cli` container after touching the bootstrap.

PHPUnit tests (`tests/php/`, same setup as CMS Tree Page View: wp-env + PHPUnit 9.6 + Yoast polyfills) cover storage and the pre-1.0 title, REST access, front-end output, the sitemap, link previews and the share image, other-plugin detection and every filter. Extend `SimpleSEO_TestCase` (in `tests/php/bootstrap.php`): the test framework unregisters all meta keys after each test, so it registers ours again. Add a test for every bug fixed.

The smoke test needs Simple SEO and Classic Editor active on the site; only the `classic` site keeps them on, so activate them on `stable`/`php74` first and switch back after. For screenshots and a browser check, use the `visual-check` skill (`.claude/skills/visual-check/`). Floors are PHP 7.4 and WP 6.6, set in the plugin header, `readme.txt`, `phpcs.xml.dist` and `phpstan.neon.dist` (wp-compat). There is no baseline; any new PHPCS or PHPStan error is a regression. CI (`.github/workflows/lint.yml`) runs `composer check` on PHP 7.4 for every push and PR.

## Releasing

Use the `cutting-a-release` skill; it's Pär's call, so never bump or tag unless asked. In short: `.github/workflows/deploy.yml` deploys trunk + tag to WordPress.org SVN with the 10up action when a semver tag is pushed, and syncs `.wordpress-org/` (screenshots, Live Preview blueprint) to SVN `assets/`. It needs the `SVN_USERNAME` and `SVN_PASSWORD` repo secrets (set, username `eskapism`). Readme or asset changes between releases go out with the manual `readme-assets.yml` workflow; see the skill. `.distignore` decides what ships.

Until 1.0 is released, don't run `readme-assets.yml`: `.wordpress-org/` already holds the 1.0 screenshots (2026-09-25), and it would publish them next to the live 0.3.5.

The last line of `readme.txt` is a Wordfence verification string (`ysaetf7ruhjnm3e2x4tbtletpc35ckeb`, added 2026-09-25). Keep it there, also when the readme is rewritten.

Between releases the version stays at the last released one (`bumping-version` skill changes it) and changelog lines go under `= Unreleased =` in `readme.txt`, written for users.

## Conventions

- Commit messages: no `Claude-Session:` links (Pär, 2026-09-26: the repo is going public). `Co-Authored-By` is fine.
- Take tooling and conventions from `../WordPress-CMS-Tree-Page-View` (the most recent plugin Pär revived) and, behind it, Simple History. Keep it proportionate: this plugin is small.
- Pär posts all public content himself. Draft readme copy, WordPress.org replies, and blog posts into `todo.md` or a `todos/` file and stop.
- Readme tone: short, personal, a bit funny. See the example copy in `todo.md` and the draft in `todos/readme-draft.md`.
- Images: every committed PNG goes through `pngquant` then `oxipng` (see the `visual-check` skill). The icon and banner come from `.wordpress-org/icon.svg` and the `generating-banner` skill; design drafts go in the gitignored `.design-drafts/`.
