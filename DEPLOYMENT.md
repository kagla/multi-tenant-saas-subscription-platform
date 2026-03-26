# Production Deployment Checklist

## Server Requirements

- PHP 8.3+ with extensions: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- Composer 2.x
- Node.js 18+ (build only)
- Nginx or Apache
- Supervisor (queue workers)
- SSL certificate (wildcard for `*.yourdomain.com`)

## Pre-Deployment

```bash
# 1. Environment
cp .env.example .env
# Edit .env with production values:
#   APP_ENV=production
#   APP_DEBUG=false
#   APP_URL=https://yourdomain.com
#   APP_BASE_DOMAIN=yourdomain.com
#   DB_CONNECTION=mysql (with credentials)
#   QUEUE_CONNECTION=redis
#   CACHE_STORE=redis
#   SESSION_DRIVER=redis
#   SESSION_DOMAIN=.yourdomain.com
#   MAIL_MAILER=smtp (with credentials)
#   STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET
#   SENTRY_LARAVEL_DSN
#   TELESCOPE_ENABLED=false

# 2. Install dependencies
composer install --optimize-autoloader --no-dev

# 3. Generate key
php artisan key:generate

# 4. Build assets
npm ci && npm run build

# 5. Database
php artisan migrate --force

# 6. Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Storage
php artisan storage:link

# 8. Seed (first deploy only)
php artisan db:seed
```

## Nginx Configuration

```nginx
# Wildcard subdomain + main domain
server {
    listen 443 ssl http2;
    server_name yourdomain.com *.yourdomain.com;

    ssl_certificate /etc/ssl/wildcard.yourdomain.com.pem;
    ssl_certificate_key /etc/ssl/wildcard.yourdomain.com.key;

    root /var/www/saas-platform/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Supervisor (Queue Workers)

```bash
sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/saas-platform.conf
# Edit paths in the conf file
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start saas-queue-worker:*
sudo supervisorctl start saas-scheduler
```

## Stripe Webhook

```bash
# Set up webhook endpoint in Stripe Dashboard:
# URL: https://yourdomain.com/webhook/stripe
# Events:
#   - checkout.session.completed
#   - customer.subscription.updated
#   - customer.subscription.deleted
#   - invoice.payment_succeeded
#   - invoice.payment_failed
```

## DNS for Tenants

Each tenant uses `{subdomain}.yourdomain.com`. This works automatically with a wildcard DNS record:

```
*.yourdomain.com  A  <your-server-ip>
```

For custom domains, tenants add a CNAME:
```
app.customerdomain.com  CNAME  {subdomain}.yourdomain.com
```

## Post-Deployment

```bash
# Verify
php artisan about
php artisan route:list --compact
php artisan queue:monitor database

# Health check
curl https://yourdomain.com/up
```

## Maintenance

```bash
# Deploy updates
php artisan down
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
npm ci && npm run build
php artisan up

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Queue maintenance
php artisan queue:retry all
php artisan queue:flush  # flush failed jobs
```

## Monitoring

- **Telescope** (dev): `https://yourdomain.com/telescope`
- **Sentry** (prod): Configure `SENTRY_LARAVEL_DSN` in `.env`
- **Logs**: `storage/logs/laravel.log`
- **Queue**: `php artisan queue:monitor database`
