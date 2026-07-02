#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "=== Database Backup ==="
"$APP_DIR/scripts/backup-db.sh"

echo
echo "=== Project Backup ==="
"$APP_DIR/scripts/backup-project.sh"

echo
echo "All backups complete."
