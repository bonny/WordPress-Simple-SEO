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
- [ ] `scripts/smoke-test.sh classic`, `php74` and `stable` pass (the last two need Simple SEO + Classic Editor activated first; see CLAUDE.md).
- [ ] Still works on WordPress 4.9 / PHP 5.6 (`scripts/old-wp/compose.yaml`, see CLAUDE.md), because the changelog promises old sites keep working.
- [ ] `= Unreleased =` in `readme.txt` lists everything, in plain user-facing language.
- [ ] The Wordfence verification string is still the last line of `readme.txt` (see CLAUDE.md).
- [ ] `Tested up to:` is the current WordPress major. `Requires at least:` / `Requires PHP:` still match `phpcs.xml.dist` and `phpstan.neon.dist`.
- [ ] Screenshots current if the UI changed (`visual-check` skill).
- [ ] Repo secrets `SVN_USERNAME` and `SVN_PASSWORD` exist (`gh secret list -R bonny/simple-seo`). Without them the deploy fails at the SVN step.

## Steps

1. Bump: `node .claude/skills/bumping-version/bump.mjs patch` (or `minor`, `major`, `X.Y.Z`).
2. In `readme.txt`, rename `= Unreleased =` to `= X.Y.Z (Month YYYY) =`, e.g. `= 0.3.5 (September 2026) =`, like Pär's other plugins.
3. Commit both together: `Release X.Y.Z`.
4. Push `main`, then the tag:
   ```bash
   git push origin main
   git tag X.Y.Z && git push origin X.Y.Z
   ```
5. Watch the run: `gh run watch -R bonny/simple-seo` (or the Actions tab). Then check https://wordpress.org/plugins/simple-seo/.

## After the deploy

- WordPress.org holds plugin updates for about 24 hours before sites get them. The plugin page shows the new version right away; users get it a day later. For an urgent security fix, email plugins@wordpress.org to ask for an expedited release (Pär does this).
- The Live Preview button uses `.wordpress-org/blueprints/blueprint.json`, which installs the released version from WordPress.org. It showed up by itself for 0.3.5 (no Advanced view toggle needed); check that the button still opens the demo after a release.
- The WordPress.org changelog is cut off at 5,000 characters. When Plugin Check warns, move the oldest entries to a `changelog.txt`. Not needed yet.

## Readme or assets only, between releases

`.github/workflows/readme-assets.yml` publishes `readme.txt` and `.wordpress-org/` from `main` without a new version (trunk, the stable tag's readme, and SVN `assets/`). Manual only, and it's Pär's call like a release:

```bash
gh workflow run readme-assets.yml -R bonny/simple-seo
gh run watch -R bonny/simple-seo $(gh run list -R bonny/simple-seo --workflow readme-assets.yml --limit 1 --json databaseId -q '.[0].databaseId')
```

It publishes the readme exactly as it is on `main`. If `= Unreleased =` has lines for work that isn't released yet, those go public too, so check the readme first.

## Gotchas

- The tag must match `[0-9]+.[0-9]+.[0-9]+*`. The old SVN-era tags (`0.1`, `0.3.1` … `0.3.4`) are fine: their commits have no workflow file, so pushing them runs nothing.
- Keep the classic `== Section ==` / `= 1.0 (Month YYYY) =` readme syntax the file already uses.
- Replies to the WordPress.org plugin review team are drafts only; write them into `todos/` for Pär.
