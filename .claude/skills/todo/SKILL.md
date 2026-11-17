---
name: todo
description: "This project's todos: one file per todo in `todos/` with YAML frontmatter; `todo.md` is generated. Use when asked about todos here — 'todos', 'what's next', 'show #N', 'new todo …', '#N is done', 'reject #N', 'the follow-up for #N is done', 'postpone #N', or when Pär asks to check the Obsidian inbox."
---

# /todo

The convention, file format, size rules and every command are in the shared skill
**`../.claude/skills/todos/SKILL.md`** (in `~/Projects`). Read it before changing a todo.

Short version:

- A todo is `todos/NN-slug.md`. `status`, `state`, `human`, `blocked_by` live in the
  frontmatter, follow-ups as lines under `## Follow-ups`. `todo.md` is generated; never edit it.
- Change todos with the script: `python3 ../.claude/skills/todos/todos.py <command> --project seo`
  (`new`, `set`, `done`, `reject`, `followup`, `tick`, `move`). After a hand edit run
  `index --project seo`, and `check` before you're done.
- **What's next:** `agent --project seo` and `status active`; suggest one and say why.
- **Show N:** `show SEO-N` and read the file.
- **Done:** `done seo N "result"` with a 2–5 line result; trim the log to the decisions.
- Never run prettier on the todo files. Commit only when Pär asks.

## Project-specific

- Prefix `SEO-01`. Todos are written in English. `todo.md` and `todos/` are symlinks into the
  Obsidian vault (`Notes/Simple SEO/`), not in git; set up with `scripts/setup-private-docs.sh`.
  Obsidian wiki links (`[[Simple SEO/todos/01-…]]`) break when a file is renamed or moved, so after
  `done`/`reject` grep the vault for the old name and fix them.
- Drafts and research (`readme-draft.md`, `seo-2026-research.md` …) are unnumbered attachments in
  `todos/`. The old phased checklist is in `todos/archive.md`.
- Inbox: `Notes/Inbox for Simple SEO.md`, only when Pär asks.
