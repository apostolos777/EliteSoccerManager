#!/usr/bin/env bash
# Safe SFTP deploy helper for uploading app.js to the remote /vivoapp path
# Usage:
#  * Set SFTP_HOST, SFTP_USER (and optionally SFTP_PORT) in your environment or export them before calling
#    (e.g. export SFTP_HOST=ftp.bizdynamix.co.za; export SFTP_USER=ftpadmin@vivounited.org)
#  * Run: ./scripts/deploy_appjs_sftp.sh
#
# What the script does (safe-by-default):
# 1) Validates local `app.js` existence and prints its SHA256
# 2) Creates a remote backup name (app.js.bak-<timestamp>) and tries to rename the remote file via a short SFTP batch
# 3) Uploads `app.js` as app.js.new, sets permissions, and atomically renames app.js.new -> app.js
# 4) Attempts to fetch the uploaded file (via HTTP) to verify checksum, when reachable
#
# NOTE: This script does NOT embed or print passwords or credentials. Provide credentials interactively
# (sftp will prompt) or set up SSH keys / an authenticated agent when required.

set -euo pipefail

PROJ_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
LOCAL_FILE="$PROJ_ROOT/app.js"
REMOTE_DIR="/public_html/vivounited/vivoapp"

if [ ! -f "$LOCAL_FILE" ]; then
  echo "ERROR: local file $LOCAL_FILE not found. Run from the repository root or place app.js at the project root."
  exit 1
fi

SFTP_HOST="${SFTP_HOST:-}"
SFTP_USER="${SFTP_USER:-}"
SFTP_PORT="${SFTP_PORT:-22}"

if [ -z "$SFTP_HOST" ] || [ -z "$SFTP_USER" ]; then
  echo "Missing SFTP_HOST or SFTP_USER environment variables."
  echo "Set them and re-run, for example:"
  echo "  export SFTP_HOST=ftp.bizdynamix.co.za"
  echo "  export SFTP_USER=ftpadmin@vivounited.org"
  exit 1
fi

sha_local() {
  # print sha256 in a friendly way
  if command -v shasum >/dev/null 2>&1; then
    shasum -a 256 "$1" | awk '{print $1}'
  else
    if command -v sha256sum >/dev/null 2>&1; then
      sha256sum "$1" | awk '{print $1}'
    else
      echo "(no sha tool available)"
    fi
  fi
}

TS=$(date +%Y%m%d-%H%M%S)
BACKUP_NAME="app.js.bak-$TS"
TMP_BATCH="/tmp/deploy_appjs_sftp_batch_$TS.txt"

echo "Preparing to deploy: $LOCAL_FILE -> sftp://$SFTP_USER@$SFTP_HOST:$SFTP_PORT$REMOTE_DIR/app.js"
echo "Local SHA256: $(sha_local "$LOCAL_FILE")"

cat > "$TMP_BATCH" <<EOF
cd $REMOTE_DIR
# rename existing file to a timestamped backup (fails silently if not present)
rename app.js $BACKUP_NAME
put $LOCAL_FILE app.js.new
chmod 644 app.js.new
rename app.js.new app.js
bye
EOF

echo "Running SFTP batch (you may be prompted for password/SSH agent)"
# -oBatchMode=no allows interactive auth if needed
sftp -oBatchMode=no -oPort=$SFTP_PORT -b "$TMP_BATCH" "$SFTP_USER@$SFTP_HOST"

echo "SFTP session complete — attempting HTTP verification if the site is public"
if command -v curl >/dev/null 2>&1; then
  REMOTE_HTTP_URL="https://www.vivounited.org/vivoapp/app.js"
  echo "Downloading $REMOTE_HTTP_URL to /tmp/remote_app.js"
  curl -sS "$REMOTE_HTTP_URL" -o /tmp/remote_app.js || echo "Note: HTTP download failed (server may block remote fetch or require auth)"
  if [ -f /tmp/remote_app.js ]; then
    echo "Remote SHA256: $(sha_local /tmp/remote_app.js)"
    echo "Local SHA256:  $(sha_local $LOCAL_FILE)"
    if [ "$(sha_local /tmp/remote_app.js)" = "$(sha_local $LOCAL_FILE)" ]; then
      echo "✅ Upload verified — checksums match."
      exit 0
    else
      echo "⚠️ Upload may not match the HTTP-served version. If your server uses caching/CDN, purge cache or wait a few seconds and re-run the verification before rolling back."
      exit 2
    fi
  else
    echo "Could not download /tmp/remote_app.js for verification. Please check manually (curl) or use a remote shell to calculate sha256."
  fi
else
  echo "curl not available — run remote verification manually."
fi

echo "Done. If something looks wrong, you can restore the remote backup with:"
echo "  (in sftp) rename $REMOTE_DIR/$BACKUP_NAME $REMOTE_DIR/app.js"

rm -f "$TMP_BATCH"
