---
name: visual-check
description: Screenshot Simple SEO's editor fields (Classic box, block editor panel), settings and the front end on the local sites and check for PHP, JS and debug.log errors, and regenerate the WordPress.org screenshots in .wordpress-org/. Use when asked to take screenshots, check the UI, verify a change in a real browser, or when the readme screenshots are stale.
---

# Visual check in the local docker sites

Use the Playwright MCP tools (headless, separate from the maintainer's Chrome). `scripts/smoke-test.sh` covers the save round trip over HTTP; this skill is for how it looks.

## Sites

| Site | Admin URL | PHP | Login |
| --- | --- | --- | --- |
| `wordpress_php74` | http://wordpress-php74.test:8299/wp-admin | 7.4 | `admin` / `admin` via wp-login.php |
| `wordpress_mariadb` (stable) | http://wordpress-stable-docker-mariadb.test:8282/wp-admin | 8.3 | the admin password is unknown: inject an auth cookie (below) |

Both need Simple SEO and Classic Editor active. The stable site is shared with Simple History work, so deactivate both plugins there when done:

```bash
cd ../_docker-compose-to-run-on-system-boot
docker compose run --rm wpcli_mariadb plugin activate simple-seo classic-editor
docker compose run --rm wpcli_mariadb plugin deactivate simple-seo classic-editor   # when done
```

Don't send WP-CLI stderr to `/dev/null` while setting up; an activation once failed silently that way.

## Steps

1. Note the debug.log length on each site: `docker compose exec -T wordpress_mariadb sh -c 'wc -l < wp-content/debug.log'`.
2. Create a test page with the meta set (`wp eval` with `wp_insert_post()` + `update_post_meta()` for the four `_simple_seo_*` keys).
3. Stable site login: generate cookies and add them with `page.context().addCookies()` (domain `wordpress-stable-docker-mariadb.test`, path `/`):
   ```bash
   docker compose run --rm -T wpcli_mariadb eval '$u = get_users( array( "role" => "administrator", "number" => 1 ) )[0]; $e = time() + 7200; echo wp_json_encode( array( array( "name" => AUTH_COOKIE, "value" => wp_generate_auth_cookie( $u->ID, $e, "auth" ) ), array( "name" => LOGGED_IN_COOKIE, "value" => wp_generate_auth_cookie( $u->ID, $e, "logged_in" ) ) ) );'
   ```
4. In one `browser_run_code_unsafe` call: open `post.php?post=<id>&action=edit`, check the `#simple-seo` box (Classic) or the \"SEO\" panel (block editor) exists, screenshot, toggle a checkbox and save, then open the front end and read `page.title()`.
5. Errors: listen to `console` (error/warning) and `pageerror`, count `text=/(Warning|Deprecated|Notice|Fatal error):/` on the page, and `tail -n +<start+1>` each debug.log.
6. Also try an 800px wide viewport.

## WordPress.org screenshots

Six shots, captions in `readme.txt` under `== Screenshots ==` (keep them in sync). All at 2x and 1120 CSS px wide, so they show at the same zoom (the Simple History one is cropped to the entry):

1. `screenshot-1.png`: the "Simple SEO" panel in the block editor, in a 1120 px window (wp-env dev site, http://localhost:8315, admin / password)
2. `screenshot-2.png`: the Pages list with the SEO column and Quick Edit open on "Our coffees" (wp-env)
3. `screenshot-3.png`: the Posts list with the SEO column (wp-env; Tags and Comments are hidden in Screen Options by the seed, since the SEO column gets cramped next to them at this width)
4. `screenshot-4.png`: the Simple SEO box in the Classic Editor (http://wp-playground-classiceditor.test:8314, admin / admin)
5. `screenshot-5.png`: the Simple SEO section in Settings → General with the demo share image (wp-env)
6. `screenshot-6.png`: the Simple History entry for the title and description change made in shot 1 (wp-env, where Simple History is active)

The demo company is Tallvik Coffee Roasters, a made-up Stockholm roastery, set up to look like a real company's site (2026-09-26): named authors (Anna Lindberg, Erik Sjöberg), pages published over several years, a child page, a draft, a static front page, the Stockholm timezone. Its domain `tallvikcoffee.se` (unregistered when checked on 2026-09-26) is only shown: as Anna's email and, rewritten in the DOM, as the Classic shot's permalink. The demo share image `demo-share-image.png` (1200 × 630, rendered with Playwright from `demo-share-image.html`, which says how) lives next to the scripts; don't use the plugin banner, it reads as if the plugin brands your previews.

Steps:

1. `npm run env:start`, then activate Simple SEO there once: `npx wp-env run cli wp plugin activate simple-seo`.
2. Seed both sites (nine pages with varied fields: one with a switched-off title, one with search engines discouraged, a draft; the demo share image; trashes Sample Page). `full` (wp-env only) also adds eight blog posts, sets the front page, posts page, timezone and Anna's email, and publishes the privacy policy, which would change how the Classic Editor test site behaves. Each prints the "About us" and "Our coffees" IDs:
   ```bash
   npx wp-env run cli wp eval-file wp-content/plugins/simple-seo/.claude/skills/visual-check/seed.php full
   (cd ../_docker-compose-to-run-on-system-boot && docker compose run --rm -T wpcli_classiceditor eval-file wp-content/plugins/simple-seo/.claude/skills/visual-check/seed.php)
   ```
   Reseed wp-env before every capture: shot 1 edits the SEO title and description and saves, which is the change shot 6 shows, and unchanged fields leave the Save button disabled.
3. Put the IDs into `capture-screenshots.js` (`WP_ENV_PAGE`, `WP_ENV_QUICK_EDIT`, `CLASSIC_PAGE`) and run it: Playwright MCP `browser_run_code_unsafe` with `filename: .claude/skills/visual-check/capture-screenshots.js`. It uses its own 2x context.
4. Compress, same pipeline as Simple History's `code.md` "Images" (never commit a PNG straight out of Playwright):
   ```bash
   pngquant --quality=80-95 --strip --skip-if-larger --force --ext .png .wordpress-org/screenshot-*.png
   oxipng -o max --strip safe .wordpress-org/screenshot-*.png
   ```
   2026-09-26: the six came out at 114/111/81/123/27/26 KB.
5. Look at all six before committing: the whole panel down to "Learn more about these fields" visible in shot 1 (the window is 1200 high for that), Quick Edit and column values both visible in shot 2 (it ends at the "Thanks for your order" row), nothing cut off in shot 4, no `about-us-2` slug (delete older "About us" pages on the Classic site), no Simple History sidebar in shot 6.

## Gotchas

- Playwright can only write inside the repo. Throwaway screenshots go to `.playwright-mcp/` (gitignored); only the compressed `.wordpress-org/` shots get committed.
- On the stable site `?page_id=<id>` redirects to the pretty permalink; that's fine.
- Expected console noise: `JQMIGRATE: Migrate is installed` and WooCommerce's "Dependency detection enabled" info. Anything else is worth a look.
- Delete the test pages afterwards (`wp post delete <id> --force`).
