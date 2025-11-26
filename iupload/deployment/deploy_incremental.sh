#!/usr/bin/env bash
# Incremental FTP deployment using curl based on git changes.
# Uses settings from sftp.json (FTP credentials & remote path) with ignore patterns.
# Optionally specify a base git ref (default: origin/master) and flags:
#   --all       Deploy all tracked files (still applies ignore rules)
#   --dry-run   Show what would be uploaded without transferring
#   --since <ref>  Compare against specific git ref/commit/tag
#
# Usage examples:
#   ./deploy_incremental.sh                # upload changed files vs origin/master
#   ./deploy_incremental.sh --since main   # upload changed vs main
#   ./deploy_incremental.sh --all          # upload everything (excluding ignores)
#   ./deploy_incremental.sh --dry-run      # just list actions

set -euo pipefail

HOST="ftp.bizdynamix.co.za"
USER="ftpadmin@vivounited.org"
PASS='HR6DsVS#eaPp'
REMOTE_BASE="/vivoapp"

DRY_RUN=0
ALL_MODE=0
BASE_REF="origin/master"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dry-run) DRY_RUN=1; shift ;;
    --all) ALL_MODE=1; shift ;;
    --since) BASE_REF="$2"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 1 ;;
  esac
done

if ! command -v git >/dev/null 2>&1; then
  echo "git not found in PATH" >&2
  exit 1
fi

# Gather candidate files
 CANDIDATES=()
 if [[ $ALL_MODE -eq 1 ]]; then
   while IFS= read -r f; do
     [[ -n "$f" ]] && CANDIDATES+=("$f")
   done < <(git ls-files)
 else
   if git rev-parse --verify "$BASE_REF" >/dev/null 2>&1; then
     while IFS= read -r f; do
       [[ -n "$f" ]] && CANDIDATES+=("$f")
     done < <(git diff --name-only --diff-filter=ACMRTUXB "$BASE_REF" HEAD)
   else
     echo "Base ref $BASE_REF not found. Falling back to git status." >&2
     while IFS= read -r f; do
       # git status porcelain lines: XY <path>
       f="${f#?? }"
       [[ -n "$f" ]] && CANDIDATES+=("$f")
     done < <(git status --porcelain)
   fi
 fi

 if [[ ${#CANDIDATES[@]} -eq 0 ]]; then
   echo "No files to deploy."; exit 0
 fi

# Ignore rules from sftp.json translated to shell patterns
IGNORES=(
  '.vscode/' '.git/' 'node_modules/' '*.log' '*.tmp' '.DS_Store' 'Thumbs.db'
  'club_settings_backup.php' 'vivo-deployment-clean*' 'VIVONEW/' 'archive-workspace/'
  'vivo-wordpress-style/' '*.sql' 'backup_*.sql' '*.zip' '*.md'
)

should_ignore() {
  local f="$1"
  for pat in "${IGNORES[@]}"; do
    case "$pat" in
      */) # directory prefix ignore
        [[ "$f" == $pat* ]] && return 0 ;;
      *\*/**) # pattern with /** already approximated by dir prefix above
        local base=${pat%%/**}
        [[ "$f" == $base* ]] && return 0 ;;
      *)
        [[ "$f" == $pat ]] && return 0
        [[ "$f" == $pat ]] && return 0
        # wildcard match
        case "$f" in $pat) return 0 ;; esac ;;
    esac
  done
  return 1
}

UPLOAD_LIST=()
for f in "${CANDIDATES[@]}"; do
  [[ -z "$f" ]] && continue
  if should_ignore "$f"; then
    continue
  fi
  if [[ ! -f "$f" ]]; then
    # skip directories / deleted / binary irregular
    continue
  fi
  UPLOAD_LIST+=("$f")
done

if [[ ${#UPLOAD_LIST[@]} -eq 0 ]]; then
  echo "No files to upload after applying ignore rules."; exit 0
fi

echo "Will upload ${#UPLOAD_LIST[@]} files to $HOST$REMOTE_BASE";

FAILED=0
for f in "${UPLOAD_LIST[@]}"; do
  REMOTE_PATH="$REMOTE_BASE/$f"
  if [[ $DRY_RUN -eq 1 ]]; then
    echo "[DRY] curl --ftp-create-dirs -T '$f' ftp://$HOST$REMOTE_PATH"
    continue
  fi
  echo "Uploading: $f -> $REMOTE_PATH"
  # Use --silent but show progress bar with --progress-bar; hide password from process listing by --user option
  if ! curl --fail --ftp-create-dirs --progress-bar -T "$f" --user "$USER:$PASS" "ftp://$HOST$REMOTE_PATH"; then
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

echo "Deployment complete: ${#UPLOAD_LIST[@]} files uploaded successfully."
