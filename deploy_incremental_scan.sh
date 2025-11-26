#!/usr/bin/env bash
# deploy_incremental_scan.sh
# Incremental FTP deployment using curl by scanning the working tree (no git required).
# Maintains a manifest of file hashes to detect changes.
# Ignores patterns from sftp.json equivalent list.
#
# Options:
#   --dry-run   Show what would be uploaded without transferring
#   --force     Upload all (ignoring manifest) but still respects ignore rules
#
# Manifest file: .deploy_manifest (format: <hash><space><path>)
# Hash algorithm: MD5 (portable: md5 or md5sum fallback)

set -eo pipefail
IFS=$'\n\t'

HOST="ftp.bizdynamix.co.za"
USER="ftpadmin@vivounited.org"
PASS='HR6DsVS#eaPp'
REMOTE_BASE="/vivoapp"
MANIFEST_FILE=".deploy_manifest"
DRY_RUN=0
FORCE_ALL=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dry-run) DRY_RUN=1; shift ;;
    --force) FORCE_ALL=1; shift ;;
    *) echo "Unknown option: $1" >&2; exit 1 ;;
  esac
done

# Ignore patterns (directory patterns end with /)
IGNORES=(
  '.vscode/' '.git/' 'node_modules/' '*.log' '*.tmp' '.DS_Store' 'Thumbs.db'
  'club_settings_backup.php' 'vivo-deployment-clean' 'VIVONEW/' 'archive-workspace/'
  'vivo-wordpress-style/' '*.sql' 'backup_' '*.zip' '*.md' '*.tar.gz' 'deployment/'
)

should_ignore() {
  local f="$1"
  for pat in "${IGNORES[@]}"; do
    case "$pat" in
      */) [[ "$f" == $pat* ]] && return 0 ;;
      *.md) [[ "$f" == *.md ]] && return 0 ;;
      *.log) [[ "$f" == *.log ]] && return 0 ;;
      *.tmp) [[ "$f" == *.tmp ]] && return 0 ;;
      *.zip) [[ "$f" == *.zip ]] && return 0 ;;
      *.sql) [[ "$f" == *.sql ]] && return 0 ;;
      *.tar.gz) [[ "$f" == *.tar.gz ]] && return 0 ;;
      backup_) [[ "$f" == backup_* ]] && return 0 ;;
      deployment/) [[ "$f" == deployment/* ]] && return 0 ;;
      *)
        if [[ "$f" == "$pat" ]]; then return 0; fi
        # directory prefix without trailing slash (e.g., vivo-deployment-clean)
        if [[ -d "$pat" && "$f" == $pat/* ]]; then return 0; fi
        ;;
    esac
  done
  return 1
}

# Load existing manifest into parallel arrays
MANIFEST_PATHS=()
MANIFEST_HASHES=()
  : # ensure arrays are defined even if empty
if [[ -f "$MANIFEST_FILE" ]]; then
  while IFS= read -r line; do
    mh="${line%% *}"
    mp="${line#* }"
    [[ -n "$mp" ]] && MANIFEST_PATHS+=("$mp") && MANIFEST_HASHES+=("$mh")
  done < "$MANIFEST_FILE"
fi

lookup_old_hash() {
  local target="$1"
  local i=0
  for p in ${MANIFEST_PATHS[@]-}; do
    if [[ "$p" == "$target" ]]; then
      echo "${MANIFEST_HASHES[$i]}"
      return 0
    fi
    i=$((i+1))
  done
  return 1
}

hash_file() {
  local f="$1"
  if command -v md5 >/dev/null 2>&1; then
    md5 -q "$f" 2>/dev/null || return 1
  elif command -v md5sum >/dev/null 2>&1; then
    md5sum "$f" | awk '{print $1}'
  else
    # fallback to shasum
    shasum -a 256 "$f" | awk '{print $1}'
  fi
}

# Scan files
CHANGED=()
TOTAL_SCANNED=0
while IFS= read -r -d '' f; do
  rel="${f#./}"
  if should_ignore "$rel"; then
    continue
  fi
  if [[ ! -f "$rel" ]]; then
    continue
  fi
  TOTAL_SCANNED=$((TOTAL_SCANNED+1))
  if [[ $FORCE_ALL -eq 1 ]]; then
    CHANGED+=("$rel")
    continue
  fi
  new_hash=$(hash_file "$rel" || echo 'NOHASH')
  old_hash=$(lookup_old_hash "$rel" || true)
  if [[ -z "$old_hash" || "$new_hash" != "$old_hash" ]]; then
    CHANGED+=("$rel")
  fi

done < <(find . -type f -print0)

echo "Scanned $TOTAL_SCANNED files; ${#CHANGED[@]} changed (force=$FORCE_ALL)."

if [[ ${#CHANGED[@]} -eq 0 ]]; then
  echo "No changes to upload."; exit 0
fi

echo "Preparing to upload ${#CHANGED[@]} file(s) to $HOST$REMOTE_BASE"
FAILED=0
for f in "${CHANGED[@]}"; do
  remote_path="$REMOTE_BASE/$f"
  if [[ $DRY_RUN -eq 1 ]]; then
    echo "[DRY] $f -> $remote_path"
    continue
  fi
  echo "Uploading: $f"
  if ! curl --fail --ftp-create-dirs --silent --show-error --progress-bar -T "$f" --user "$USER:$PASS" "ftp://$HOST$remote_path"; then
    echo "Failed: $f" >&2
    FAILED=$((FAILED+1))
  fi
done

if [[ $DRY_RUN -eq 1 ]]; then
  echo "Dry run complete."; exit 0
fi

if [[ $FAILED -gt 0 ]]; then
  echo "$FAILED file(s) failed to upload." >&2
  exit 2
fi

# Rebuild manifest with current hashes (only for non-ignored files)
{
  while IFS= read -r -d '' f; do
    rel="${f#./}"
    if should_ignore "$rel"; then continue; fi
    if [[ -f "$rel" ]]; then
      h=$(hash_file "$rel" || echo 'NOHASH')
      printf '%s %s\n' "$h" "$rel"
    fi
  done < <(find . -type f -print0)
} > "$MANIFEST_FILE"

echo "Deployment complete. Manifest updated ($MANIFEST_FILE)."
