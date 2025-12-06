#!/usr/bin/env bash
set -euo pipefail

# Configuration via env:
# LOGS_REPO (owner/repo) - required
# LOGS_BRANCH (branch name) - default "main"
# LOGS_PAT (personal access token with repo push access to LOGS_REPO) - required
# GITHUB_BEFORE, GITHUB_AFTER, GITHUB_ACTOR, GITHUB_REF, GITHUB_REPOSITORY - provided by workflow

LOGS_REPO="${LOGS_REPO:?LOGS_REPO is required (owner/repo)}"
LOGS_BRANCH="${LOGS_BRANCH:-main}"
LOGS_PAT="${LOGS_PAT:?LOGS_PAT secret is required}"

SRC_BEFORE="${GITHUB_BEFORE:-}"
SRC_AFTER="${GITHUB_AFTER:-HEAD}"
ACTOR="${GITHUB_ACTOR:-unknown}"
REPO="${GITHUB_REPOSITORY:-unknown}"
REF="${GITHUB_REF:-refs/heads/unknown}"

# Ensure we have history to diff
git fetch --no-tags --prune --unshallow >/dev/null 2>&1 || true

# Determine changed files
if [ -z "$SRC_BEFORE" ] || [[ "$SRC_BEFORE" =~ ^0+$ ]]; then
  changed_files=$(git diff-tree --no-commit-id --name-only -r "$SRC_AFTER" || true)
else
  changed_files=$(git diff --name-only "$SRC_BEFORE" "$SRC_AFTER" || true)
fi

# Filter for "beseo" path
matched=()
while IFS= read -r f; do
  [ -z "$f" ] && continue
  if [[ "$f" == "beseo" || "$f" == beseo/* ]]; then
    matched+=("$f")
  fi
done <<< "$changed_files"

if [ "${#matched[@]}" -eq 0 ]; then
  echo "No changes under 'beseo' detected. Exiting."
  exit 0
fi

timestamp() { date -u +"%Y-%m-%dT%H:%M:%SZ"; }

# Prepare temporary workdir
tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

# Clone the central logs repo using PAT
echo "Cloning logs repo $LOGS_REPO (branch $LOGS_BRANCH)"
git -c http.extraHeader="Authorization: bearer $LOGS_PAT" clone --single-branch --branch "$LOGS_BRANCH" "https://github.com/$LOGS_REPO.git" "$tmpdir/logs-repo"

LOGFILE="$tmpdir/logs-repo/logs/beseo.jsonl"
mkdir -p "$(dirname "$LOGFILE")"
touch "$LOGFILE"

# Append entries (JSONL). Do not include file contents.
for path in "${matched[@]}"; do
  cat >> "$LOGFILE" <<EOF
{"ts":"$(timestamp)","repo":"$REPO","branch":"$REF","sha":"$SRC_AFTER","actor":"$ACTOR","path":"$path","action":"modified"}
EOF
  echo "Appended log for $path"
done

cd "$tmpdir/logs-repo"
git add "$LOGFILE"
git commit -m "chore: append beseo events from $REPO @ $SRC_AFTER" || {
  echo "No changes to commit in logs repo."
  exit 0
}

# Push using the same http.extraHeader auth
git -c http.extraHeader="Authorization: bearer $LOGS_PAT" push origin "$LOGS_BRANCH"

echo "Pushed logs to $LOGS_REPO:$LOGS_BRANCH"
