#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$APP_DIR/.env"
BACKUP_DIR="$APP_DIR/backups/database"
DB_CONTAINER="${DB_CONTAINER:-mariadb}"

env_value() {
    local key="$1"

    grep -E "^${key}=" "$ENV_FILE" \
        | tail -n 1 \
        | cut -d '=' -f2- \
        | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

DB_DATABASE="$(env_value DB_DATABASE)"
DB_USERNAME="$(env_value DB_USERNAME)"
DB_PASSWORD="$(env_value DB_PASSWORD)"

mkdir -p "$BACKUP_DIR"

TIMESTAMP="$(date +%Y-%m-%d_%H%M%S)"
BACKUP_FILE="$BACKUP_DIR/${DB_DATABASE}_${TIMESTAMP}.sql.gz"

echo "Backing up database: $DB_DATABASE"
echo "Container: $DB_CONTAINER"
echo "Output: $BACKUP_FILE"

docker exec \
    -e MYSQL_PWD="$DB_PASSWORD" \
    -e DB_DATABASE="$DB_DATABASE" \
    -e DB_USERNAME="$DB_USERNAME" \
    "$DB_CONTAINER" \
    sh -lc '
        DUMP_BIN="$(command -v mariadb-dump || command -v mysqldump)"

        "$DUMP_BIN" \
            --single-transaction \
            --quick \
            --routines \
            --triggers \
            -u"$DB_USERNAME" \
            "$DB_DATABASE"
    ' | gzip > "$BACKUP_FILE"

echo "Backup complete:"
ls -lh "$BACKUP_FILE"
