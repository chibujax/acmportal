# Community Operations Portal – Implementation Plan
## Version 1.3 | Updated: February 2026
### Abia Community Manchester (ACM)

---

## Overview

A Laravel 10 web portal for the Abia Community Manchester (ACM).  
Hosted on **shared cPanel hosting** with **PHP 8.0+** and **MySQL**.  
No Node.js / npm required in production — all assets use CDN.  
Mobile-responsive using Bootstrap 5 (CDN).

---

## Technology Stack

| Layer       | Technology                                   |
|-------------|----------------------------------------------|
| Framework   | Laravel 10 (PHP 8.0+)                        |
| Database    | MySQL 5.7+ / 8.0                             |
| Frontend    | Bootstrap 5.3 (CDN), Bootstrap Icons (CDN)   |
| Charts      | Chart.js 4.4 (CDN)                           |
| Payments    | Stripe (stripe-php SDK) + Paystack (HTTP)    |
| CSV Import  | league/csv                                   |
| Queue       | Database driver (no Redis needed)            |
| Hosting     | cPanel Shared Hosting, PHP 8, MySQL          |
| Dev Tools   | VSCode + PHP Intelephense + Xdebug           |

---

## User Roles

| Role                 | Capabilities                                             |
|----------------------|----------------------------------------------------------|
| `admin`              | Full access: members, CSV import, reports, dues cycles   |
| `financial_secretary`| Record manual payments, view financial reports & arrears |
| `member`             | View dashboard, dues status, payment history, pay online |

---

## Phase 1 – Registration & Member Onboarding ✅ IMPLEMENTED

### 1.1 CSV Import Flow

1. Admin uploads a CSV file (`name`, `phone`, optional `email`).
2. System validates and imports into `pending_members` table.
3. Each import is tagged with a batch ID (`BATCH-YYYYMMDDHHmmss`).
4. Admin can view all batches and send registration invite links.

**CSV Template columns:** `name`, `phone`, `email` (optional)

### 1.2 Registration Link Flow

1. Admin clicks "Send Invites" for a batch or individual pending member.
2. System generates a secure 64-character token stored in `registration_tokens`.
3. Token expires after **7 days** (configurable via `REGISTRATION_TOKEN_EXPIRY_DAYS`).
4. If the pending member has an email → invite is emailed.
5. Admin can also **copy the registration link manually** from the Pending Members list and share via WhatsApp/SMS/other means.

### 1.3 Member Registration

- Member opens the unique link: `/register/{token}`
- Confirms phone number, sets a password.
- Optionally enters an email address.
- If email entered → email verification link is sent (expires 48h).
- Dashboard shows email verification status and resend option.

### 1.4 Authentication

- Login accepts **phone number or email** + password.
- "Remember Me" option.
- Suspended accounts are blocked at login with a message.

---

## Phase 2 – Dues Cycles & Online Payments ✅ SCAFFOLDED

### 2.1 Dues Cycle Management

Admins define cycles with:
- Title, type (`yearly_dues` | `donation` | `event_levy`)
- Start date / end date
- Total amount and currency (GBP)
- Payment options: `once` | `monthly` | `installments`
- If installments: number of instalments

### 2.2 Payment Methods

#### Online – Stripe (Card)
- Member clicks "Pay by Card" on dashboard.
- Stripe.js `CardElement` renders a secure iframe.
- Frontend calls `/pay/stripe/intent` to create a `PaymentIntent`.
- On success, member is redirected to `/pay/stripe/success`.
- **Webhook** at `/webhooks/stripe` handles `payment_intent.succeeded` and `payment_intent.payment_failed`.
- Webhook verified with **HMAC signature** (`Stripe-Signature` header).

#### Online – Paystack (Card / Bank Transfer)
- Member clicks "Pay via Paystack" on dashboard.
- Backend calls Paystack `/transaction/initialize` API.
- Member is redirected to Paystack's hosted payment page.
- On return, backend verifies via `/transaction/verify/{reference}`.
- **Webhook** at `/webhooks/paystack` handles `charge.success` and `refund.processed`.
- Webhook verified with **HMAC-SHA512** (`x-paystack-signature` header).

#### Manual Payment (Financial Secretary)
- Financial Secretary navigates to **Finance → Record Payment**.
- Selects member, dues cycle, amount, date.
- Optionally uploads proof of payment (JPG/PNG/PDF, max 4MB).
- System auto-generates a receipt number (`ACM-XXXXXXXX`).
- Payment is immediately marked as `completed`.

### 2.3 Installment Tracking

- `Payment` records carry `installment_number` and `total_installments`.
- Member dashboard shows running balance and % progress per cycle.

---

## Phase 3 – Reports & Analytics ✅ IMPLEMENTED

### 3.1 Financial Report
- Monthly collections bar chart (Chart.js).
- Year filter (dropdown).
- Dues Cycle breakdown table: target vs. collected vs. payer count.

### 3.2 Arrears Report
- Filter by dues cycle.
- Lists all active members who have **not** fully paid.
- Shows: amount owed vs. paid vs. outstanding.

### 3.3 Member Summary Report
- Paginated table of all members with total paid and payment count.

---

## Phase 4 – Reminders (Planned)

- Automated email/SMS reminders for upcoming and overdue dues.
- Uses Laravel's scheduled tasks (`schedule:run` cron).
- Cron job setup on cPanel:
  ```
  * * * * * /usr/local/bin/php /home/{user}/public_html/artisan schedule:run >> /dev/null 2>&1
  ```

---

## Database Schema

### `users`
| Column          | Type       | Notes                                      |
|-----------------|------------|--------------------------------------------|
| id              | bigint PK  |                                            |
| name            | string     |                                            |
| phone           | string     | Unique. Primary login identifier           |
| email           | string?    | Optional. Unique if set                    |
| email_verified_at| timestamp?|                                            |
| password        | string     | Bcrypt hashed                              |
| role            | enum       | admin / financial_secretary / member       |
| status          | enum       | active / inactive / suspended              |
| profile_photo   | string?    |                                            |
| address         | string?    |                                            |

### `pending_members`
| Column          | Type       | Notes                                      |
|-----------------|------------|--------------------------------------------|
| id              | bigint PK  |                                            |
| name            | string     |                                            |
| phone           | string     | Unique                                     |
| email           | string?    |                                            |
| status          | enum       | pending / invited / registered / failed    |
| import_batch    | string?    | Groups CSV upload batches                  |
| invited_at      | timestamp? |                                            |
| registered_at   | timestamp? |                                            |

### `registration_tokens`
| Column            | Type       | Notes                                    |
|-------------------|------------|------------------------------------------|
| id                | bigint PK  |                                          |
| pending_member_id | bigint FK  |                                          |
| token             | string(64) | Unique secure random token               |
| expires_at        | timestamp  | Default: 7 days                          |
| used_at           | timestamp? | Null until consumed                      |

### `dues_cycles`
| Column           | Type    | Notes                                     |
|------------------|---------|-------------------------------------------|
| id               | bigint  |                                           |
| title            | string  |                                           |
| type             | enum    | yearly_dues / donation / event_levy       |
| amount           | decimal |                                           |
| start_date       | date    |                                           |
| end_date         | date    |                                           |
| payment_options  | enum    | once / monthly / installments             |
| installment_count| int?    |                                           |
| status           | enum    | draft / active / closed                   |

### `payments`
| Column             | Type    | Notes                                   |
|--------------------|---------|-----------------------------------------------|
| id                 | bigint  |                                               |
| user_id            | FK      |                                               |
| dues_cycle_id      | FK?     |                                               |
| amount             | decimal |                                               |
| method             | enum    | stripe / paystack / manual / bank_transfer    |
| status             | enum    | pending / completed / failed / refunded       |
| gateway_reference  | string? | Stripe PI ID or Paystack reference            |
| gateway_payload    | json?   | Full webhook payload stored                   |
| recorded_by        | FK?     | For manual payments (financial secretary)     |
| receipt_number     | string? | Auto-generated `ACM-XXXXXXXX`                 |
| proof_of_payment   | string? | File path                                     |
| installment_number | int?    |                                               |

---

## Deployment – cPanel Shared Hosting

### Steps

1. **Upload files** to `public_html` (or a subdomain folder).
2. **Point document root** to `public_html/public` (via cPanel → Domains → Document Root).
3. **Create MySQL database** in cPanel and note credentials.
4. **SSH or Terminal in cPanel** and run:
   ```bash
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   php artisan key:generate
   # Edit .env with DB credentials and mail settings
   php artisan migrate --seed
   php artisan storage:link
   ```
5. **Set up cron job** in cPanel (Cron Jobs):
   ```
   * * * * * /usr/local/bin/php /home/{cpanel_user}/public_html/artisan schedule:run >> /dev/null 2>&1
   ```
6. **Ensure `storage/` and `bootstrap/cache/` are writable** (755).

### Stripe Webhooks (Production)
- Register endpoint `https://yourdomain.com/webhooks/stripe` in Stripe Dashboard.
- Copy the webhook signing secret to `.env` as `STRIPE_WEBHOOK_SECRET`.
- Events to subscribe: `payment_intent.succeeded`, `payment_intent.payment_failed`

### Paystack Webhooks (Production)
- Register endpoint `https://yourdomain.com/webhooks/paystack` in Paystack Dashboard.
- The secret key (`PAYSTACK_SECRET_KEY`) is used for HMAC-SHA512 verification – no separate webhook secret needed.
- Events: `charge.success`, `refund.processed`

---

## Local Development (VSCode + Xdebug)

### Requirements
- PHP 8.0+ with Xdebug 3 extension
- Composer
- MySQL
- VSCode extensions: PHP Intelephense, PHP Debug

### Xdebug php.ini settings
```ini
[xdebug]
zend_extension=xdebug
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.idekey=vscode
```

### Running the project
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve   # visit http://localhost:8000
```

**Default demo credentials (after seeding):**
| Role               | Phone       | Password     |
|--------------------|-------------|--------------|
| Admin              | 07000000001 | Admin@1234   |
| Financial Secretary| 07000000002 | FinSec@1234  |
| Member             | 07111111001 | Member@1234  |

The `.vscode/launch.json` includes three debug configurations:
1. **Listen for Xdebug** – attach to PHP running in a web server
2. **PHP Built-in Server (Debug)** – launch `php -S localhost:8000` with Xdebug
3. **Laravel Artisan** – debug any artisan command

---

## Out of Scope

- Mobile API / native app (mobile responsive web only)
- Node.js / npm in production

---

*End of v1.3 – ACM Community Portal*
