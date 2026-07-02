#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -ne 1 ]; then
    echo "Usage:"
    echo "  ./scripts/restore-db.sh backups/database/YOUR_BACKUP.sql.gz"
    exit 1
fi

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$APP_DIR/.env"
DB_CONTAINER="${DB_CONTAINER:-mariadb}"
BACKUP_FILE="$1"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Backup file not found: $BACKUP_FILE"
    exit 1
fi

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

echo "WARNING: This will restore into database: $DB_DATABASE"
echo "Container: $DB_CONTAINER"
echo "Backup file: $BACKUP_FILE"
echo
read -r -p "Type RESTORE to continue: " CONFIRM

if [ "$CONFIRM" != "RESTORE" ]; then
    echo "Restore cancelled."
    exit 1
fi

if [[ "$BACKUP_FILE" == *.gz ]]; then
    gunzip -c "$BACKUP_FILE" | docker exec -i \
        -e MYSQL_PWD="$DB_PASSWORD" \
        -e DB_DATABASE="$DB_DATABASE" \
        -e DB_USERNAME="$DB_USERNAME" \
        "$DB_CONTAINER" \
        sh -lc 'mariadb -u"$DB_USERNAME" "$DB_DATABASE"'
else
    cat "$BACKUP_FILE" | docker exec -i \
        -e MYSQL_PWD="$DB_PASSWORD" \
        -e DB_DATABASE="$DB_DATABASE" \
        -e DB_USERNAME="$DB_USERNAME" \
        "$DB_CONTAINER" \
        sh -lc 'mariadb -u"$DB_USERNAME" "$DB_DATABASE"'
fi

echo "Restore complete."
