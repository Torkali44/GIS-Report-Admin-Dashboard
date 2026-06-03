# نشر GIS على Namecheap VPS

## متطلبات السيرفر

- Ubuntu 22/24 أو Debian
- PHP **8.2+** مع الامتدادات: `sqlite3`, `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `gd`, `curl`, `zip`, `xml`
- Composer 2
- Node.js 20+ (لبناء CSS/JS مرة واحدة على السيرفر أو محلياً ثم رفع `public/build`)
- Nginx + PHP-FPM

## نشر سريع (أول مرة)

```bash
cd /var/www/gis-report
git clone <repo-url> .   # أو ارفع الملفات
cp .env.example .env
nano .env                  # عدّل APP_URL و ADMIN_PASSWORD
bash scripts/deploy-vps.sh --fresh
```

### إعداد `.env` للإنتاج

```env
APP_NAME="GIS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/gis-report/database/database.sqlite

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public

ADMIN_EMAIL=you@yourdomain.com
ADMIN_PASSWORD=strong-password-here
```

> **SQLite** مناسب لـ VPS صغير. للـ MySQL غيّر `DB_CONNECTION=mysql` وأضف بيانات الاتصال.

## قاعدة البيانات

```bash
touch database/database.sqlite
chmod 664 database/database.sqlite
chmod 775 database

# أول نشر أو إعادة بناء كاملة:
php artisan migrate:fresh --force --seed

# تحديثات لاحقة (بدون حذف البيانات):
php artisan migrate --force
```

`db:seed` ينشئ:
- حساب المشرف (`ADMIN_EMAIL` / `ADMIN_PASSWORD`)
- تصنيفات وملاحظات وتوصيات جاهزة (`ReadyNotesSeeder`)
- أقسام جاهزة (من migration)

## التخزين (ملفات PDF)

```bash
php artisan storage:link
mkdir -p storage/app/public/reports
chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache
```

- ملفات PDF المؤقتة في `storage/app/public/reports/`

### إعداد PHP

```ini
memory_limit = 256M
max_execution_time = 120
```

### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/gis-report/public;
    index index.php;

    client_max_body_size 32M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## بناء الواجهة

```bash
npm ci
npm run build
```

## بعد كل تحديث

```bash
git pull
bash scripts/deploy-vps.sh
# أو يدوياً:
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

## التحقق بعد النشر

1. افتح `/login` — تسجيل دخول المشرف
2. أنشئ منزل → أضف أقسام وملاحظات
3. حمّل PDF — تأكد من ظهور النسب والملاحظات
4. عدّل ملاحظات جاهزة — لا قفز لأول الصفحة

## ملاحظات

- PDF يُولَّد من أحدث البيانات عند كل تحميل
- التطبيق **تقارير نصية فقط** — بدون رفع صور
- غيّر كلمة مرور المشرف الافتراضية فوراً
