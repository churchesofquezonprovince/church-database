# Database Seeding Guide

This project separates production-safe seeders from demo seeders.

## Production-safe seeder

Use this for the real church database:

php artisan db:seed

or after migration:

php artisan migrate --seed

This only runs:

AdminUserSeeder

It does not create demo people, households, or family trees.

## Demo seeder

Use this only for development or testing databases:

php artisan db:seed --class=DemoDatabaseSeeder

This runs:

AdminUserSeeder
DemoChurchSeeder

## Fresh demo database

For testing only:

php artisan migrate:fresh --seed --seeder=DemoDatabaseSeeder

WARNING:
Do not run migrate:fresh on the real church database because it deletes all tables first.

## Safety rule

Before running any command with migrate:fresh, run:

./scripts/backup-all.sh

## Real database rule

For the real church database, use forms, CSV import, and normal editing.

Do not use demo seeders for real church records.

## Admin User Environment Variables

The admin account is controlled by .env:

ADMIN_USER_EMAIL
ADMIN_USER_NAME
ADMIN_USER_PASSWORD

Never commit the real admin password.

The .env.example file must only contain placeholder values.
