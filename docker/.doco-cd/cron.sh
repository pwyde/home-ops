#!/usr/bin/env bash
# Self-update script for doco-cd on TrueNAS SCALE.

set -euo pipefail

WORK_DIR="/mnt/ssd-data/Docker/doco-cd"
BASE_URL="https://raw.githubusercontent.com/pwyde/home-ops/main/docker/.doco-cd"
APP_NAME="doco-cd"
LOG_DIR="$WORK_DIR/logs"
LOG_FILE="$LOG_DIR/$(date '+%Y-%m-%d').log"
CHANGED=0

mkdir -p "$LOG_DIR"

log() {
  local timestamp
  timestamp=$(date '+%Y-%m-%d %H:%M:%S')
  echo "[$timestamp] $*" | tee -a "$LOG_FILE"
}

require_binary() {
  local binary="$1"
  if ! command -v "$binary" >/dev/null 2>&1; then
    log "ERROR: Required binary '$binary' not found."
    exit 1
  fi
}

require_binary curl
require_binary sha256sum
require_binary midclt

cd "$WORK_DIR" || {
  log "ERROR: Unable to change directory to $WORK_DIR"
  exit 1
}

fetch_and_compare() {
  local filename="$1"
  local url="$BASE_URL/$filename"
  log "Checking $filename..."
  local tmp_file
  tmp_file=$(mktemp)
  if ! curl -sfL --max-time 30 "$url" -o "$tmp_file"; then
    log "ERROR: Failed to fetch $filename from GitHub."
    rm -f "$tmp_file"
    exit 1
  fi
  local new_hash=""
  local old_hash=""
  new_hash=$(sha256sum "$tmp_file" | cut -d' ' -f1)
  if [ -f "$filename" ]; then
    old_hash=$(sha256sum "$filename" | cut -d' ' -f1)
  fi
  if [ "$new_hash" != "$old_hash" ]; then
    log "CHANGED: $filename — updating"
    mv "$tmp_file" "$filename"
    CHANGED=1
  else
    log "UNCHANGED: $filename"
    rm -f "$tmp_file"
  fi
}

fetch_and_compare "docker-compose.app.yaml"

if [ "$CHANGED" -eq 1 ]; then
  log "Changes detected — restarting TrueNAS SCALE app '$APP_NAME'..."
  if midclt call app.redeploy "$APP_NAME"; then
    log "Successfully redeployed '$APP_NAME'."
  else
    log "ERROR: Failed to redeploy '$APP_NAME'."
    exit 1
  fi
  log "Update completed successfully."
else
  log "No changes detected — exiting."
fi
