# Churches of Quezon Database

A Laravel + Filament church database system for managing people, households, family relationships, shepherding care, and church activity records for the Churches of Quezon Province.

## Main Features

- People database
- Household records
- Household head and members
- Family tree view
- Shepherding dashboard
- Church profile tracking
- Education / work profile
- Parent / guardian relationships
- Emergency contacts
- Custom Filament dashboard
- Database and project backup scripts

## Tech Stack

- Laravel
- Filament
- MariaDB
- Redis
- Docker
- Vite / Tailwind CSS
- Cloudflare Tunnel ready

## Main App URL

Local Docker access:

http://RASPBERRY_PI_IP:5000/quezonprovinceactivities

Filament panel route:

/quezonprovinceactivities

## Main Containers

- church-app
- church-redis
- mariadb
- adminer

## Important Commands

Build frontend assets:

    npm run build

Clear Laravel and Filament cache:

    docker exec -it church-app sh -lc "php artisan optimize:clear"

Run fresh migrations and seed demo data:

    docker exec -it church-app sh -lc "php artisan migrate:fresh --seed"

Backup database only:

    ./scripts/backup-db.sh

Backup project only:

    ./scripts/backup-project.sh

Backup database and project:

    ./scripts/backup-all.sh

Cleanup old backups:

    KEEP_DATABASE=10 KEEP_PROJECT=5 ./scripts/cleanup-backups.sh

Restore database:

    ./scripts/restore-db.sh backups/database/YOUR_BACKUP_FILE.sql.gz

## Git Workflow

Check status:

    git status

Commit changes:

    git add .
    git commit -m "Your commit message"

Push to GitHub:

    git push

## Security Notes

Do not commit:

- .env
- backups/
- vendor/
- node_modules/

The .env file contains private passwords.

Database backups may contain personal names, contact numbers, addresses, family relationships, and church information. Do not upload raw database backups to GitHub.
