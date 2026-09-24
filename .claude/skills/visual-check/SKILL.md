---
name: visual-check
description: Screenshot Simple SEO's Classic Editor fields and the front end on the local docker sites and check for PHP, JS and debug.log errors. Use when asked to take screenshots, check the UI, or verify a change in a real browser.
---

# Visual check in the local docker sites

Use the Playwright MCP tools (headless, separate from Pär's Chrome). `scripts/smoke-test.sh` covers the save round trip over HTTP; this skill is for how it looks.

## Sites

| Site | Admin URL | PHP | Login |
| --- | --- | --- | --- |
| `wordpress_php74` | http://wordpress-php74.test:8299/wp-admin | 7.4 | `admin` / `admin` via wp-login.php |
| `wordpress_mariadb` (stable) | http://wordpress-stable-docker-mariadb.test:8282/wp-admin | 8.3 | Pär's password is unknown: inject an auth cookie (below) |

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
4. In one `browser_run_code_unsafe` call: open `post.php?post=<id>&action=edit`, check `#titlediv #simple_seo_edit_wrapper` exists, compare the two inputs' `boundingBox()` (same `x` = aligned), screenshot, toggle a checkbox and screenshot again, then open the front end and read `page.title()`.
5. Errors: listen to `console` (error/warning) and `pageerror`, count `text=/(Warning|Deprecated|Notice|Fatal error):/` on the page, and `tail -n +<start+1>` each debug.log.
6. Also try an 800px wide viewport.

## Gotchas

- Playwright can only write inside the repo. Save screenshots to `.playwright-mcp/` (gitignored), never commit them.
- `styles.css` is enqueued with the plugin version as `ver`, so the browser caches it across CSS edits. Call `page.route('**/*', r => r.continue())` first; routing turns the cache off.
- On the stable site `?page_id=<id>` redirects to the pretty permalink; that's fine.
- Expected console noise: `JQMIGRATE: Migrate is installed` and WooCommerce's "Dependency detection enabled" info. Anything else is worth a look.
- Delete the test pages afterwards (`wp post delete <id> --force`).
