#!/usr/bin/env bash
#
# Despliegue de DSLE en un host tradicional (VPS con PHP-FPM + Nginx, o Laragon).
# Para despliegue con contenedores usa `docker compose up -d --build`.
#
# Uso:  ./deploy/deploy.sh [rama]      (rama por defecto: main)
#
# Requisitos en el host: git, php 8.3+, composer, node 20+, acceso a la BD.
set -euo pipefail

BRANCH="${1:-main}"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "▶ DSLE deploy — rama ${BRANCH}"

if [ ! -f .env ]; then
    echo "✗ Falta .env. Copia .env.production.example y complétalo." >&2
    exit 1
fi

php artisan down --render="errors::503" --retry=15 || true
trap 'php artisan up || true' EXIT

git fetch --prune origin
git checkout "$BRANCH"
git reset --hard "origin/${BRANCH}"

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress

npm ci
npm run build

php artisan migrate --force --no-interaction

# Cachés de framework (config/route/view/event). `optimize` las agrupa.
php artisan optimize:clear
php artisan optimize

php artisan storage:link || true
php artisan queue:restart

php artisan up
trap - EXIT

echo "✔ Despliegue completado."
php artisan about --only=environment
