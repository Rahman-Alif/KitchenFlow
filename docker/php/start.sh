#!/bin/sh
set -e

composer install --no-interaction --quiet

php artisan key:generate --no-interaction --force
php artisan migrate --no-interaction --force
php artisan storage:link --no-interaction --force

chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php-fpm