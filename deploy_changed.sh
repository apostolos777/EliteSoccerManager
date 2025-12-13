#!/usr/bin/env bash
# Deploy changed files to FTP host only
# Usage: deploy_changed.sh [--range origin/main...HEAD] [--dry-run]
set -euo pipefail

DRY_RUN=0
RANGE=""
while [[ "$#" -gt 0 ]]; do
  case "$1" in
    --dry-run) DRY_RUN=1; shift;;
    --range) RANGE="$2"; shift 2;;
    *) echo "Unknown option: $1"; exit 1;;
  esac
done

# Require FTP env vars
: "${FTP_HOST:?FTP_HOST must be set}"
: "${FTP_USER:?FTP_USER must be set}"
: "${FTP_PASS:?FTP_PASS must be set}"
: "${FTP_PATH:?FTP_PATH must be set}"

# Determine changed files (committed) or staged changes
if [ -n "$RANGE" ]; then
  FILES=$(git diff --name-only "$RANGE")
else
  # prefer comparing to origin/main if available
  if git rev-parse --verify origin/main >/dev/null 2>&1; then
    FILES=$(git diff --name-only origin/main...HEAD)
  else
    FILES=$(git status --porcelain | awk '{print $2}')
  fi
fi

# Filter only existing files (skip deleted)
FILES=$(echo "$FILES" | xargs -I{} bash -lc 'if [ -f "{}" ]; then echo "{}"; fi' || true)

if [ -z "$FILES" ]; then
  echo "No changed files detected"
  exit 0
fi

if [ "$DRY_RUN" -eq 1 ]; then
  echo "Changed files (dry run):"
  echo "$FILES"
  exit 0
fi

# Upload files via curl using FTP with --ftp-create-dirs
for f in $FILES; do
  echo "Uploading $f"
  remote_path="$FTP_PATH/$f"
  # ensure directory structure is created on server and upload file
  curl -s --ftp-create-dirs -T "$f" "ftp://$FTP_USER:$FTP_PASS@$FTP_HOST/$remote_path"
  rc=$?
  if [ $rc -ne 0 ]; then
    echo "Upload failed for $f (curl rc=$rc)" >&2
    exit $rc
  fi
done

echo "Upload complete"
