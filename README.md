# ACM Community Portal

**Abia Community Manchester** – Member Management & Financial Portal  
Laravel 10 · PHP 8+ · MySQL · Bootstrap 5 (CDN) · Stripe + Paystack

---

## Quick Start (Local)

```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Edit .env: set DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 4. Run migrations and seed demo data
php artisan migrate --seed

# 5. Start local server
php artisan serve

cd storage/
mkdir -p framework/{sessions,views,cache}
chmod -R 775 framework
```

**Open:** http://localhost:8000

| Role               | Phone       | Password     |
|--------------------|-------------|--------------|
| Admin              | 07000000001 | Admin@1234   |
| Financial Secretary| 07000000002 | FinSec@1234  |
| Member             | 07111111001 | Member@1234  |

---

## Payment Gateway Setup

### Stripe
1. Create account at https://dashboard.stripe.com
2. Copy **Publishable key** → `STRIPE_KEY`
3. Copy **Secret key** → `STRIPE_SECRET`
4. Add webhook endpoint `{APP_URL}/webhooks/stripe` in Stripe Dashboard  
   Subscribe to: `payment_intent.succeeded`, `payment_intent.payment_failed`
5. Copy **Webhook signing secret** → `STRIPE_WEBHOOK_SECRET`

### Paystack
1. Create account at https://dashboard.paystack.com
2. Copy **Public key** → `PAYSTACK_PUBLIC_KEY`
3. Copy **Secret key** → `PAYSTACK_SECRET_KEY`
4. Add webhook URL `{APP_URL}/webhooks/paystack` in Paystack Dashboard

---

## cPanel Deployment

1. Upload all files except `node_modules/` (there are none) to hosting.
2. Point domain **document root** to the `public/` folder.
3. Create MySQL database and user in cPanel.
4. Via SSH / cPanel Terminal:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   ```
5. Add cron job (Cron Jobs in cPanel):
   ```
   * * * * * /usr/local/bin/php /home/YOUR_USER/public_html/artisan schedule:run >> /dev/null 2>&1
   ```

---

## VSCode Debug

Install extension: **PHP Debug** (`xdebug.php-debug`)

Three launch configs in `.vscode/launch.json`:
- **Listen for Xdebug** – for Apache/Nginx + Xdebug
- **PHP Built-in Server (Debug)** – all-in-one local debug
- **Laravel Artisan** – debug artisan commands

---

See `docs/implementation-plan-v1.3.md` for full specification.
