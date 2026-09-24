---
name: cutting-a-release
description: Use when releasing Simple SEO to WordPress.org, or to recall how a release is published. The runbook from pre-flight checks through version bump, changelog, tag and the automated SVN deploy.
---

# Cutting a release (WordPress.org)

Pushing a semver git tag (`0.3.5`, `1.0.0`) runs `.github/workflows/deploy.yml`, which uses `10up/action-wordpress-plugin-deploy` to push trunk + the tag to WordPress.org SVN (slug `simple-seo`) and sync `.wordpress-org/` to SVN `assets/`. `.distignore` decides what goes in the zip. Modeled on CMS Tree Page View's `cutting-a-release` skill.

## This is Pär's call

Do **not** bump, tag or push a release unless Pär asks for it in this conversation. Ongoing work only adds lines under `= Unreleased =` in `readme.txt`. If a release seems due, say so and stop.

## Pre-flight

- [ ] On `main`, working tree clean, pushed.
- [ ] `composer check` clean (PHPCS + PHPStan, no baseline). CI runs the same on PHP 7.4.
- [ ] `scripts/plugin-check.sh` says "No errors found". It checks the `.distignore` build, like WordPress.org sees it.
- [ ] `scripts/smoke-test.sh php74` and `scripts/smoke-test.sh stable` pass (needs Simple SEO + Classic Editor active; see CLAUDE.md).
- [ ] `= Unreleased =` in `readme.txt` lists everything, in plain user-facing language.
- [ ] `Tested up to:` is the current WordPress major. `Requires at least:` / `Requires PHP:` still match `phpcs.xml.dist` and `phpstan.neon.dist`.
- [ ] Screenshots current if the UI changed (`visual-check` skill).
- [ ] Repo secrets `SVN_USERNAME` and `SVN_PASSWORD` exist (`gh secret list -R bonny/simple-seo`). Without them the deploy fails at the SVN step.

## Steps

1. Bump: `node .claude/skills/bumping-version/bump.mjs patch` (or `minor`, `major`, `X.Y.Z`).
2. In `readme.txt`, rename `= Unreleased =` to `= X.Y.Z =`.
3. Commit both together: `Release X.Y.Z`.
4. Push `main`, then the tag:
   ```bash
   git push origin main
   git tag X.Y.Z && git push origin X.Y.Z
   ```
5. Watch the run: `gh run watch -R bonny/simple-seo` (or the Actions tab). Then check https://wordpress.org/plugins/simple-seo/.

## After the deploy

- WordPress.org holds plugin updates for about 24 hours before sites get them. The plugin page shows the new version right away; users get it a day later. For an urgent security fix, email plugins@wordpress.org to ask for an expedited release (Pär does this).
- The Live Preview button uses `.wordpress-org/blueprints/blueprint.json`, which installs the released version from WordPress.org. It may need turning on in the plugin's Advanced view on WordPress.org.
- The WordPress.org changelog is cut off at 5,000 characters. When Plugin Check warns, move the oldest entries to a `changelog.txt`. Not needed yet.

## Gotchas

- The tag must match `[0-9]+.[0-9]+.[0-9]+*`. The old SVN-era tags (`0.1`, `0.3.1` … `0.3.4`) are fine: their commits have no workflow file, so pushing them runs nothing.
- Keep the classic `== Section ==` / `= 1.0 =` readme syntax the file already uses.
- Replies to the WordPress.org plugin review team are drafts only; write them into `todos/` for Pär.
