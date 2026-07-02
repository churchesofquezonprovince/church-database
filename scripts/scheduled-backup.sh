#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOG_DIR="$APP_DIR/storage/logs/backups"

mkdir -p "$LOG_DIR"

LOG_FILE="$LOG_DIR/backup_$(date +%Y-%m-%d).log"

{
    echo "========================================"
    echo "Scheduled backup started: $(date)"
    echo "Project: $APP_DIR"
    echo

    "$APP_DIR/scripts/backup-all.sh"

    echo
    KEEP_DATABASE=10 KEEP_PROJECT=5 "$APP_DIR/scripts/cleanup-backups.sh"

    echo
    echo "Scheduled backup completed: $(date)"
    echo "========================================"
    echo
} >> "$LOG_FILE" 2>&1
