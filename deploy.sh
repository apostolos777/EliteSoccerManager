#!/usr/bin/env bash

# VIVO App Deployment Script
# Purpose: Sync working tree (this repo directory) to local XAMPP web root.
# Default: DRY RUN (no changes). Use --live to actually deploy.
# Optional: --backup creates a timestamped tar.gz of current destination before syncing.
# Usage: ./deploy.sh [--live] [--backup] [--quiet]
# Mac friendly; requires rsync. Falls back to basic copy if rsync missing.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SRC_ROOT="$SCRIPT_DIR"
DEST_DEFAULT="/Applications/XAMPP/xamppfiles/htdocs/vivo-app"
DEST="${VIVO_DEPLOY_DEST:-$DEST_DEFAULT}"

DO_LIVE=0
DO_BACKUP=0
QUIET=0

for arg in "$@"; do
	case "$arg" in
		--live) DO_LIVE=1 ; shift ;;
		--backup) DO_BACKUP=1 ; shift ;;
		--quiet|-q) QUIET=1 ; shift ;;
		--dest=*) DEST="${arg#*=}" ; shift ;;
		--help|-h)
			cat <<EOF
VIVO Deployment
Options:
	--live        Perform real sync (otherwise dry-run)
	--backup      Create timestamped tar.gz of destination before sync
	--dest=PATH   Override destination (default: $DEST_DEFAULT)
	--quiet       Less output
	--help        This help
Environment:
	VIVO_DEPLOY_DEST  Override destination path
Examples:
	./deploy.sh                # Dry run
	./deploy.sh --backup       # Dry run + prepare backup
	./deploy.sh --live --backup
EOF
			exit 0
			;;
		*) echo "Unknown arg: $arg" >&2; exit 1 ;;
	esac
done

if [ ! -d "$DEST" ]; then
	echo "Destination does not exist: $DEST" >&2
	exit 2
fi

timestamp() { date +%Y%m%d_%H%M%S; }

if [ $DO_BACKUP -eq 1 ]; then
	BK_DIR="$SCRIPT_DIR/deploy_backups"
	mkdir -p "$BK_DIR"
	BK_FILE="$BK_DIR/vivo-app_$(timestamp).tar.gz"
	echo "[backup] Creating $BK_FILE from $DEST" >&2
		# Use --exclude pattern before the directory argument; ensure path relative inside archive
		tar -czf "$BK_FILE" -C "$(dirname "$DEST")" --exclude="$(basename "$DEST")/uploads" "$(basename "$DEST")" || { echo "Backup failed" >&2; exit 3; }
fi

EXCLUDES=(
	"--exclude" ".git" \
	"--exclude" "deploy_backups" \
	"--exclude" "uploads" \
	"--exclude" "*.tar.gz" \
	"--exclude" "*.sql" \
	"--exclude" "vivo-final-clean" \
	"--exclude" "vivo-football-manager-clean" \
	"--exclude" "vivo-deployment" \
	"--exclude" "xampp-vivo-app-new/uploads" \
	"--exclude" "*/node_modules" \
)

RSYNC_FLAGS=(-av --delete)
if [ $DO_LIVE -eq 0 ]; then
	RSYNC_FLAGS+=(--dry-run)
fi
if [ $QUIET -eq 1 ]; then
	RSYNC_FLAGS+=(--quiet)
fi

if command -v rsync >/dev/null 2>&1; then
	echo "[deploy] Using rsync from $SRC_ROOT -> $DEST" >&2
	rsync "${RSYNC_FLAGS[@]}" "${EXCLUDES[@]}" "$SRC_ROOT/" "$DEST/"
	if [ $DO_LIVE -eq 0 ]; then
		echo "[deploy] Dry run complete. Re-run with --live to apply." >&2
	else
		echo "[deploy] Live sync complete." >&2
	fi
else
	echo "[deploy] rsync not found. Using fallback copy (NO delete sync)." >&2
	if [ $DO_LIVE -eq 0 ]; then
		echo "[deploy] Dry run requested but fallback copy does not support dry-run. Aborting." >&2
		exit 4
	fi
	# Basic recursive copy excluding patterns (simple subset)
	echo "[deploy] Copying..." >&2
	rs_ex_pat='(.git|deploy_backups|uploads|vivo-final-clean|vivo-football-manager-clean|vivo-deployment)'
	find "$SRC_ROOT" -type f | grep -Ev "$rs_ex_pat" | while read -r f; do
		 rel="${f#$SRC_ROOT/}"; mkdir -p "$DEST/$(dirname "$rel")"; cp -f "$f" "$DEST/$rel"; done
	echo "[deploy] Copy complete (fallback)." >&2
fi

exit 0
