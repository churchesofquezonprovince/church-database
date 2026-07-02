#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="$APP_DIR/backups/project"

mkdir -p "$BACKUP_DIR"

TIMESTAMP="$(date +%Y-%m-%d_%H%M%S)"
BACKUP_FILE="$BACKUP_DIR/church_app_${TIMESTAMP}.tar.gz"

echo "Backing up Laravel project..."
echo "Project: $APP_DIR"
echo "Output: $BACKUP_FILE"

tar -czf "$BACKUP_FILE" \
    -C "$APP_DIR" \
    --exclude="./vendor" \
    --exclude="./node_modules" \
    --exclude="./.git" \
    --exclude="./backups" \
    --exclude="./storage/logs/*.log" \
    .

echo "Project backup complete:"
ls -lh "$BACKUP_FILE"
