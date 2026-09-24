# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Simple SEO is a small WordPress plugin by Pär (eskapism), on WordPress.org at https://wordpress.org/plugins/simple-seo/ (slug `simple-seo`, about 300 active installs). It was last released as 0.3.4 in October 2012 and is now being brought back to life. The plan and its status live in [`todo.md`](todo.md).

Goals for the revival: a deliberately tiny SEO plugin (a few fields per post, no settings maze, no upsells), working on modern WordPress and PHP, and a friendly home for existing users.

## History

This git repo was created on 2026-09-24 by replaying the WordPress.org SVN trunk history (r279243 to r607674) into git with the original authors, dates, and messages. Git tags `0.1` and `0.3.1` to `0.3.4` point at the matching trunk commits. SVN is still the release channel; GitHub (`bonny/simple-seo`, private for now) is the source of truth for development.

## Current code (0.3.4)

- `simple-seo.php` holds everything: two optional fields per post, a custom page title and a custom menu label.
- Post meta keys, which existing sites depend on, so keep reading them:
  - `_simple_seo_use_custom_page_title` (0/1) and `_simple_seo_custom_page_title_value`
  - `_simple_seo_use_custom_menu_label` (0/1) and `_simple_seo_custom_menu_label_value`
- The fields are printed on `dbx_post_sidebar` and moved into `#titlediv` with jQuery, so they only appear in the Classic Editor. Gutenberg support is planned for 1.0.
- The title reaches the front end through the `single_post_title` filter, which `wp_get_document_title()` still uses. The menu label only affects `get_pages()` / `wp_list_pages()`, not nav menus or the Navigation block.

## Local development

The repo is bind-mounted into every WordPress site in the docker stack (`../_docker-compose-to-run-on-system-boot`, anchor `mnt-simple-seo`) as `wp-content/plugins/simple-seo`. Edits are live immediately.

Run WP-CLI through the paired service:

```bash
cd ../_docker-compose-to-run-on-system-boot
docker compose run --rm wpcli_mariadb plugin list --name=simple-seo
```

Jetpack and Rank Math were deactivated on the stable site (2026-09-24) because they exhausted WP-CLI's 128M memory limit. If WP-CLI dies with "Allowed memory size exhausted" again, check for a newly activated heavy plugin, or add `--skip-plugins`.

## Conventions

- Take tooling and conventions from `../WordPress-CMS-Tree-Page-View` (the most recent plugin Pär revived) and, behind it, Simple History. Keep it proportionate: this plugin is small.
- Pär posts all public content himself. Draft readme copy, WordPress.org replies, and blog posts into `todo.md` or a `todos/` file and stop.
- Readme tone: short, personal, a bit funny. See the example copy in `todo.md`.
