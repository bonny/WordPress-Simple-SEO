---
name: bumping-version
description: Use when the Simple SEO version number needs changing for a release. Updates every place the version lives (plugin header, SIMPLE_SEO_VERSION, readme Stable tag) in one go.
---

# Bumping the version

The version lives in three places that must match:

- `simple-seo.php`: the `Version:` header (the source of truth for the current version)
- `simple-seo.php`: `define( 'SIMPLE_SEO_VERSION', … )`, used as the CSS cache buster
- `readme.txt`: `Stable tag:`

```bash
node .claude/skills/bumping-version/bump.mjs patch   # 0.3.4 -> 0.3.5
node .claude/skills/bumping-version/bump.mjs minor   # 0.3.4 -> 0.4.0
node .claude/skills/bumping-version/bump.mjs major   # 0.3.4 -> 1.0.0
node .claude/skills/bumping-version/bump.mjs 1.0.0   # explicit
```

It exits with an error if any pattern is missing, so a half-bumped release can't ship. Never hand-edit the three spots.

Only bump as part of a release; see [`cutting-a-release`](../cutting-a-release/SKILL.md). Between releases the version stays at the last released one and new changelog lines go under `= Unreleased =`.

Added a new place the version lives? Add it to `edits` in `bump.mjs`, with exactly two regex groups (before and after the version).
