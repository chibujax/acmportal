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

### Incremental deploys (`deploy/build-deploy-package.ps1`)

For routine changes that don't touch `composer.json` (most day-to-day work), you don't need to
re-upload `vendor/` or run composer on the server at all. This PowerShell script packages only
the changed application files into a zip for upload via cPanel File Manager, and writes a
plain-text manifest listing each file's local (Windows) path and its destination (cPanel/Linux)
path relative to the app root.

Dev-only files (`tests/`, `phpunit.xml`, `.gitignore`, `deploy/` itself, `README.md`) are never
packaged. Deleted files are listed separately (in the console output and the manifest) since
extracting a zip can't remove files — those need manual deletion in File Manager.

**Options**

| Parameter | Type | Purpose |
|---|---|---|
| `-Uncommitted` | switch | Package whatever `git status` shows right now (modified + untracked + deleted), ignoring commit history entirely. |
| `-Since <ref>` | string | Committed-mode only. Commit/tag/hash to diff from. Only needed the first time (or to override the saved marker). |
| `-ServerRoot <path>` | string | Optional. Prefixes the manifest's destination column with this absolute path (e.g. your real cPanel doc root) instead of leaving destinations relative to the app root. |

**Usage**

```powershell
# Package whatever's currently modified/untracked (uncommitted mode)
.\deploy\build-deploy-package.ps1 -Uncommitted

# Same, with the real server path baked into the manifest
.\deploy\build-deploy-package.ps1 -Uncommitted -ServerRoot '/home/YOUR_USER/public_html/YOUR_DOMAIN'

# Committed-history mode, first run ever — tell it what commit is currently live
.\deploy\build-deploy-package.ps1 -Since <commit-or-tag>

# Committed-history mode, every run after — uses the saved marker (deploy/.last-deployed) automatically
.\deploy\build-deploy-package.ps1
```

`-Since` is ignored (with a warning) if passed together with `-Uncommitted` — it's one mode or
the other per run. After uploading, in cPanel Terminal:

```bash
php artisan migrate --force   # if there are new migrations
php artisan optimize:clear && php artisan config:cache && php artisan view:cache
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


test


# Terminal 1
php artisan serve --env=dusk

# Terminal 2
php artisan dusk


Production (cPanel)
On cPanel you add one single cron job that runs every minute. Laravel's scheduler handles the rest from there.

Steps:

Log into cPanel → Cron Jobs
Set frequency to: * * * * * (every minute)
Set the command to:

/usr/local/bin/php /home/YOUR_CPANEL_USERNAME/public_html/artisan schedule:run >> /dev/null 2>&1
Finding the right paths — SSH into the server and run:


which php          # gets the PHP binary path
pwd                # run from your project root to get the artisan path
The comment already in routes/console.php documents this:


* * * * * /usr/bin/php /path/to/artisan schedule:run >> /dev/null 2>&1
What that one cron job controls
Once the every-minute cron is set, all your scheduled tasks are handled automatically:

Task	When it runs
queue:work --stop-when-empty	Every minute
acm:monthly-absentee-alert	00:00 on the last Saturday of the month
You never need to add more cron jobs for future scheduled commands — just add them to routes/console.php and the same minute-cron picks them up.

Testing options:

What you want	Command
Fire it right now (ignores schedule)	php artisan acm:monthly-absentee-alert
See when it will next run	php artisan schedule:list
Force the scheduler to check right now	php artisan schedule:run