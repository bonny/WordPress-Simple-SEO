#!/usr/bin/env bash
#
# Symlinks Simple SEO's private docs from the Obsidian vault into this repo.
# Safe to re-run. Same approach as Simple History's scripts/setup-private-skills.sh.
#
# The private docs (todo list, drafts, strategy and site plans for Claude Code)
# live in the vault, not in git, and are git-ignored here:
#   todo.md, todos/, CLAUDE.private.md
# todo.md links to "Simple SEO todo.md" in the vault (a plain "todo" is too common a
# name among the notes there).
# CLAUDE.private.md is loaded through the git-ignored CLAUDE.local.md.
#
# Required env var:
#   SIMPLE_SEO_PRIVATE_DIR — absolute path to the "Simple SEO" folder in the vault.
#
# Example:
#   export SIMPLE_SEO_PRIVATE_DIR="$HOME/Documents/Notes/Simple SEO"
#   ./scripts/setup-private-docs.sh
#
# Add the export to your shell profile (.zshrc) to make it permanent.
# Contributors without the vault can ignore this script.

set -euo pipefail

if [ -z "${SIMPLE_SEO_PRIVATE_DIR:-}" ]; then
	echo "Error: SIMPLE_SEO_PRIVATE_DIR is not set." >&2
	echo "  export SIMPLE_SEO_PRIVATE_DIR=\"\$HOME/Documents/Notes/Simple SEO\"" >&2
	echo "If you're a contributor and not the maintainer, you can ignore this script." >&2
	exit 1
fi

if [ ! -d "$SIMPLE_SEO_PRIVATE_DIR" ]; then
	echo "Error: $SIMPLE_SEO_PRIVATE_DIR not found. Has Obsidian Sync finished on this machine?" >&2
	exit 1
fi

cd "$(dirname "$0")/.."

# Repo name:vault name.
for pair in "todo.md:Simple SEO todo.md" "todos:todos" "CLAUDE.private.md:CLAUDE.private.md"; do
	item="${pair%%:*}"
	target="${pair#*:}"
	if [ -e "$item" ] && [ ! -L "$item" ]; then
		echo "Skipped $item: a real file or folder is in the way. Move it into the vault first." >&2
		continue
	fi
	ln -sfn "$SIMPLE_SEO_PRIVATE_DIR/$target" "$item"
	echo "Linked $item"
done

# Load the private notes from the git-ignored CLAUDE.local.md.
if ! grep -qs '^@CLAUDE.private.md' CLAUDE.local.md; then
	printf '\n@CLAUDE.private.md\n' >>CLAUDE.local.md
	echo "Added @CLAUDE.private.md to CLAUDE.local.md"
fi
