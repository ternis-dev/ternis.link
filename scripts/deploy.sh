#!/usr/bin/env bash
#
# Production deploy for ternis.link.
#
# Usage: bash scripts/deploy.sh
#
# Pulls code, installs dependencies, rebuilds frontend assets
# (public/build is gitignored — skipping this step 500s any page
# whose @vite() entry is missing from the stale manifest),
# migrates, and clears compiled views. Run on every deploy.
#
# NOTE: route caching is deliberately NOT here — routes/web.php uses
# closures, which cannot be cached.
set -euo pipefail

cd "$(dirname "$0")/.."

git pull origin master

composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

npm ci
npm run build

php artisan migrate --force
php artisan view:clear

echo 'Deploy complete.'
