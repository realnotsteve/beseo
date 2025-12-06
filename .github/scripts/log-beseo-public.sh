#!/usr/bin/env bash
set -euo pipefail

LOGFILE="logs/beseo.jsonl"
BES_DIR="beseo"

SRC_BEFORE="${GITHUB_BEFORE:-}"
SRC_AFTER="${GITHUB_AFTER:-HEAD}"
ACTOR="${GITHUB_ACTOR:-unknown}"
REPO="${GITHUB_REPOSITORY:-unknown}"
REF="${GITHUB_REF:-refs/heads/unknown}"

# Ensure enough history to diff
git fetch --no-tags --prune --unshallow >/dev/null 2>&1 || true

# Determine name-status diff (handles A,M,D,R)
if [ -z "$SRC_BEFORE" ] || [[ "$SRC_BEFORE" =~ ^0+$ ]]; then
  name_status="$(git diff-tree --no-commit-id --name-status -r "$SRC_AFTER" || true)"
else
  name_status="$(git diff --name-status "$SRC_BEFORE" "$SRC_AFTER" || true)"
fi

# Build list of matched events
mapfile -t events < <(printf '%s\n' "$name_status" | while IFS=$'\t' read -r status p1 p2; do
  if [ -z "$status" ]; then
    continue
  fi
  status_char="${status:0:1}"
  case "$status_char" in
    A) action="added"; path="$p1" ;;
    M) action="modified"; path="$p1" ;;
    D) action="deleted"; path="$p1" ;;
    R) action="renamed"; path="$p2" ;;
    *) action="unknown"; path="$p1" ;;
  esac
  if [ "$path" = "$BES_DIR" ] || [[ "$path" == "$BES_DIR/"* ]]; then
    printf '%s\t%s\n' "$action" "$path"
  fi
done)

if [ "${#events[@]}" -eq 0 ]; then
  echo "No changes under '$BES_DIR' detected. Exiting."
  exit 0
fi

timestamp() { date -u +"%Y-%m-%dT%H:%M:%SZ"; }

mkdir -p "$(dirname "$LOGFILE")"
touch "$LOGFILE"

for ev in "${events[@]}"; do
  action="${ev%%$'\t'*}"
  path="${ev#*$'\t'}"
  printf '%s\n' "{\"ts\":\"$(timestamp)\",\"repo\":\"$REPO\",\"branch\":\"$REF\",\"sha\":\"$SRC_AFTER\",\"actor\":\"$ACTOR\",\"path\":\"$path\",\"action\":\"$action\"}" >> "$LOGFILE"
  echo "Appended: $action $path"
done

if git status --porcelain | grep -q "$(printf "%s" "$LOGFILE")"; then
  git add "$LOGFILE"
  git commit -m "chore: append beseo events (${#events[@]} event(s))"
  git push
  echo "Pushed $LOGFILE"
else
  echo "No changes to $LOGFILE to commit."
fi
