# Deployment Runbook

How to run DAS in production on a single Linux server (Ubuntu 24.04, Nginx, PHP-FPM 8.3, MySQL 8, Node 22). Three long-running processes must be supervised besides the web server: the **queue worker**, the **Inertia SSR server**, and the **scheduler** (cron).

## 1. Server prerequisites

- PHP 8.3 with `fpm`, `mysql`, `gd`, `intl`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`, `opcache`.
- `mysqldump` (package `mysql-client`) — used by nightly backups.
- Node 22 + npm (build step and the SSR server).
- Composer 2, Supervisor, Nginx, a TLS certificate (e.g. Certbot).
- In `php.ini` (FPM): `expose_php = Off`, `memory_limit = 256M` (image processing raises itself to 512M per request), `upload_max_filesize = 25M`, `post_max_size = 30M`, `opcache.enable = 1`, `opcache.validate_timestamps = 0` (then reload FPM on every deploy).

## 2. First deploy

```bash
git clone <repo> /var/www/das && cd /var/www/das
cp .env.production.example .env        # fill in every "CHANGE ME"
composer install --no-dev --optimize-autoloader
php artisan key:generate
npm ci
npm run build:ssr                      # client bundle + SSR bundle (bootstrap/ssr)
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=SettingsSeeder --force
php artisan storage:link               # product/category/banner images are served from here
php artisan das:bootstrap-mother-admin # once; then remove MOTHER_ADMIN_* from .env
php artisan optimize                   # config, route, view and event caches
```

Ownership: `chown -R www-data:www-data storage bootstrap/cache`.

## 3. Every later deploy

```bash
cd /var/www/das
php artisan down --render="errors::503"
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build:ssr
php artisan migrate --force
php artisan optimize
sudo systemctl reload php8.3-fpm
sudo supervisorctl restart das-queue das-ssr
php artisan up
```

Never copy `public/hot` to a server — it points asset URLs at a developer's Vite dev server. It is git-ignored; keep it that way.

## 4. Supervised processes

`/etc/supervisor/conf.d/das.conf`:

```ini
[program:das-queue]
command=php /var/www/das/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=www-data
autostart=true
autorestart=true
stopwaitsecs=3600
stdout_logfile=/var/www/das/storage/logs/queue.log
redirect_stderr=true

[program:das-ssr]
command=php /var/www/das/artisan inertia:start-ssr
user=www-data
autostart=true
autorestart=true
stdout_logfile=/var/www/das/storage/logs/ssr.log
redirect_stderr=true
```

Then `sudo supervisorctl reread && sudo supervisorctl update`.

- **Queue worker** sends queued mail (new-enquiry notifications). Without it, enquiries are stored but nobody is emailed.
- **SSR server** puts page titles, meta descriptions, canonical tags, structured data and the first page of catalogue products into the HTML. If it is down, Inertia silently falls back to client rendering and search engines see an almost empty page. The scheduler runs `inertia:check-ssr` every five minutes and logs a **critical** entry (sent to Slack when configured) if it fails.

## 5. Scheduler (cron)

`crontab -u www-data -e`:

```
* * * * * cd /var/www/das && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks (`routes/console.php`): `backup:clean` 01:00, `backup:run` 01:30, `activitylog:clean` 02:00 (deletes activity log entries older than 365 days), `backup:monitor` 03:00, `inertia:check-ssr` every 5 minutes.

## 6. Backups

`spatie/laravel-backup`, configured in `config/backup.php`:

- Contents: MySQL dump + `storage/app/public` (all uploaded images). Code is in Git and is not backed up.
- Destination: `BACKUP_DISKS` (default `local` → `storage/app/private/DAS`). **A backup on the same server is lost with the server**: add an off-server disk (`BACKUP_DISKS=local,s3` after `composer require league/flysystem-aws-s3-v3` and filling the `AWS_*` values) or sync that folder elsewhere.
- Retention: `backup.cleanup` defaults (all for 7 days, then daily/weekly/monthly thinning, capped at 5 GB).
- Alerts: failures and unhealthy backups are mailed to `BACKUP_NOTIFICATION_EMAIL`.
- Check status: `php artisan backup:list`.
- **Restore drill** (do this once before launch): unzip a backup, `mysql das < db-dumps/mysql-das.sql`, copy `storage/app/public` back, run `php artisan storage:link`.

## 7. Monitoring & logs

- Logs: `storage/logs/laravel-YYYY-MM-DD.log` (`LOG_STACK=daily,slack`, 14 days).
- Error alerts: set `LOG_SLACK_WEBHOOK_URL`; `error` and above are posted to Slack (`LOG_SLACK_LEVEL`).
- Uptime: point an external uptime checker at `https://<domain>/up` (Laravel's health route).

## 8. Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name www.example.com;
    root /var/www/das/public;
    index index.php;

    client_max_body_size 30m;
    server_tokens off;

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml application/xml;

    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location /storage/ {
        expires 30d;
        add_header Cache-Control "public";
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known) { deny all; }
}
```

The application itself sends `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS (production over HTTPS) and a Content Security Policy on admin pages (`App\Http\Middleware\AddSecurityHeaders`).

`robots.txt` and `sitemap.xml` are generated by Laravel routes (`SeoController`) — do not add static copies to `public/`, they would shadow the routes.

## 9. Pre-launch checklist

- [ ] `.env` filled; `APP_DEBUG=false`; `APP_URL` is the final https domain.
- [ ] Mail sends (trigger a password reset to yourself).
- [ ] `supervisorctl status` shows `das-queue` and `das-ssr` RUNNING; `php artisan inertia:check-ssr` passes.
- [ ] `view-source:` on `/catalogue` shows product names and a `<title>`.
- [ ] `/sitemap.xml` and `/robots.txt` use the production domain.
- [ ] `php artisan backup:run` succeeds and a restore drill has been done.
- [ ] Uptime check on `/up` and a Slack test alert received.
