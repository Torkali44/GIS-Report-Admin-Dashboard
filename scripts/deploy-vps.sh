#!/usr/bin/env bash
# نشر GIS على VPS (Namecheap / Ubuntu)
# الاستخدام: bash scripts/deploy-vps.sh [--fresh]
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

FRESH=false
if [[ "${1:-}" == "--fresh" ]]; then
    FRESH=true
fi

echo "==> GIS deploy in $ROOT"

if [[ ! -f .env ]]; then
    cp .env.example .env
    php artisan key:generate --force
    echo "Created .env — عدّل APP_URL و ADMIN_PASSWORD قبل الإنتاج."
fi

composer install --no-dev --optimize-autoloader --no-interaction

if [[ ! -f database/database.sqlite ]]; then
    touch database/database.sqlite
fi

chmod 664 database/database.sqlite 2>/dev/null || true
chmod 775 database 2>/dev/null || true

if $FRESH; then
    echo "==> Fresh database (migrate:fresh --seed)"
    php artisan migrate:fresh --force --seed
else
    php artisan migrate --force
    php artisan db:seed --force
fi

php artisan storage:link --force 2>/dev/null || php artisan storage:link

mkdir -p storage/app/public/reports
mkdir -p storage/framework/{sessions,cache/data,views} bootstrap/cache

if command -v npm >/dev/null 2>&1 && [[ -f package.json ]]; then
    if [[ -f package-lock.json ]]; then
        npm ci
    else
        npm install
    fi
    npm run build
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

# صلاحيات (عدّل www-data حسب نظامك)
if id www-data >/dev/null 2>&1; then
    chown -R www-data:www-data storage bootstrap/cache database/database.sqlite 2>/dev/null || true
fi
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "==> Done."
echo "    APP_URL: $(grep '^APP_URL=' .env | cut -d= -f2- || echo 'not set')"
echo "    Admin:   $(grep '^ADMIN_EMAIL=' .env | cut -d= -f2- || echo 'admin@example.com')"
echo ""
echo "تأكد من:"
echo "  - php artisan storage:link (لملفات PDF)"
echo "  - APP_DEBUG=false في الإنتاج"
