#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

DATABASE_BACKUP_DIR="$APP_DIR/backups/database"
PROJECT_BACKUP_DIR="$APP_DIR/backups/project"

KEEP_DATABASE="${KEEP_DATABASE:-20}"
KEEP_PROJECT="${KEEP_PROJECT:-10}"

cleanup_dir() {
    local dir="$1"
    local pattern="$2"
    local keep="$3"
    local label="$4"

    if [ ! -d "$dir" ]; then
        echo "$label backup directory does not exist yet: $dir"
        return
    fi

    echo "Cleaning $label backups..."
    echo "Directory: $dir"
    echo "Keeping newest: $keep"

    mapfile -t old_files < <(
        find "$dir" -maxdepth 1 -type f -name "$pattern" -printf '%T@ %p\n' \
            | sort -rn \
            | awk -v keep="$keep" 'NR > keep {print $2}'
    )

    if [ "${#old_files[@]}" -eq 0 ]; then
        echo "No old $label backups to remove."
        return
    fi

    printf '%s\n' "${old_files[@]}" | while read -r file; do
        echo "Removing: $file"
        rm -f "$file"
    done
}

cleanup_dir "$DATABASE_BACKUP_DIR" "*.sql.gz" "$KEEP_DATABASE" "database"
echo
cleanup_dir "$PROJECT_BACKUP_DIR" "*.tar.gz" "$KEEP_PROJECT" "project"

echo
echo "Backup cleanup complete."
