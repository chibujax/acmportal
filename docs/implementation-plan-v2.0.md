# ACM Portal – Implementation Plan
## Version 2.0 | February 2026
### Abia Community Manchester (ACM)

---

## Overview

A Laravel 12 member portal for Abia Community Manchester (ACM).
Hosted on **cPanel shared hosting** with **PHP 8.4+** and **MySQL 8**.
No Node.js in production — all assets use CDN (Bootstrap 5, Bootstrap Icons, Chart.js).

---

## Tech Stack

| Layer       | Technology                                      |
|-------------|--------------------------------------------------|
| Framework   | Laravel 12 (PHP 8.4+)                           |
| Database    | MySQL 8                                         |
| Frontend    | Bootstrap 5.3 (CDN), Bootstrap Icons (CDN)      |
| Charts      | Chart.js 4.4 (CDN)                              |
| Payments    | Stripe (stripe-php SDK) + Paystack (HTTP)        |
| CSV Import  | league/csv                                      |
| Queue       | Database driver                                 |
| Hosting     | cPanel Shared Hosting                           |
| Geocoding   | postcodes.io (free, no API key)                 |
| Email       | cPanel SMTP (SSL port 465)                      |
| SMS         | Vonage (formerly Nexmo) — cheapest with Laravel support (~£0.035/SMS UK) |

---

## Roles

| Role                  | Capabilities                                                          |
|-----------------------|-----------------------------------------------------------------------|
| `admin`               | Full access: members, meetings, reports, dues, relationships          |
| `financial_secretary` | Record payments, view financial reports, arrears, dues lists          |
| `member`              | Dashboard, attendance history, dues status, pay online, relationships |

---

## Phase 1 – Foundation & Attendance ✅ MOSTLY DONE

### 1.1 Member Import & Onboarding ✅
- Admin uploads CSV (`name`, `phone`, optional `email`)
- Imported into `pending_members` with batch ID (`BATCH-YYYYMMDDHHmmss`)
- Admin views batches, sends invite links per batch or individually
- Invite generates 64-char token (expires 7 days, configurable)
- If email present → invite emailed; admin can also copy link for WhatsApp/SMS
- Member opens link → confirms phone, sets password, optionally adds email
- If email added → verification link sent (expires 48h)

### 1.2 Authentication ✅
- Login: phone number or email + password
- "Remember Me" supported
- Suspended accounts blocked at login with message

### 1.3 QR Attendance Check-In ✅
**Agreed flow:**
1. Member scans QR code displayed at meeting
2. App validates: meeting active? QR not expired?
3. Identity confirmation — logged-in user sees "Is this you, [Name]?"
   - "Not me" → logs out + redirects back to QR URL to sign in as correct person
4. GPS check — browser requests location permission
5. Attendance marked → success / error screen

**Admin controls per meeting (all required):**
- Venue postcode (auto-geocodes to lat/lng via postcodes.io)
- GPS radius (default **100m**, required)
- Venue end time (required — QR expires at this time)
- Late threshold time (required — check-ins after this are flagged "late")
- GPS failure action: **Block + contact admin** OR **Allow but flag for review**

**Attendance statuses:** `present` | `late` | `excused` | `location_mismatch`

### 1.4 Admin Meeting Management ✅
- Create / edit / close meetings
- Activate meeting (opens check-in, generates QR)
- Manual check-in: **searchable member list** (not a dropdown — text search)
- Mark member as **excused / apologies** (member notified admin in advance)
- View live attendance (who is checked in for current meeting)

### 1.5 Meeting Open / QR Validity Check ✅ (already implemented)

`resolveMeeting()` in `CheckInController` already handles:
- `status = scheduled` → "Check-in is not open yet"
- `status = closed` → "Meeting has been closed"
- `isExpiredQr()` → "QR code has expired" (based on `qr_expires_at`)

**Gap:** `qr_expires_at` is currently set manually by admin when activating.
Once `end_time` is added (see 1.6 below), the QR expiry will be driven automatically by meeting end time.

---

### 1.6 Phase 1 – Outstanding Items ❌

| # | Item | Notes |
|---|------|-------|
| 1 | **Meeting form: end time (mandatory)** | QR auto-expires at end time; drives `qr_expires_at` automatically |
| 2 | **Meeting form: late threshold time (mandatory)** | Currently hardcoded at 15 min after `meeting_time`; must be admin-set |
| 3 | **Meeting form: GPS radius default 100m** | Currently defaults to 150m |
| 4 | **Admin: excused / apologies marking** | Admin marks a member as excused before or during the meeting; stored as `status = excused` on attendance record |
| 5 | **Admin: searchable member input** | Replace all member dropdowns with a live text search input (name or phone) |
| 6 | **Fix: flagged attendance not shown in admin view** | DB has `location_mismatch = 1` but admin meeting attendance table does not highlight or label it |
| 7 | **Password reset** | Not built — see spec below |
| 8 | **Email verification resend** | Needs verification — check if resend button exists on member dashboard |
| 9 | **Member attendance % / eligibility display** | Show each member's % attendance vs meetings held; flag below 70% threshold |
| 10 | **Attendance export (CSV, admin)** | Export full attendance sheet per meeting or per date range |

---

### 1.7 Password Reset – Spec

Since phone is the primary identifier and many members may not have an email address, password reset must support both paths:

**Path A — Member has email:**
- Login page has "Forgot password?" link
- Member enters their phone number or email
- System looks up account; if email found → sends reset link via email (standard Laravel reset flow)
- Link expires in 60 minutes
- Member sets new password

**Path B — Member has no email (phone only):**
- Member enters phone number
- System sends a **6-digit OTP via SMS**
- Member enters OTP on next screen (expires in 10 minutes)
- Member sets new password

**SMS Provider: Vonage (formerly Nexmo)**
- Laravel package: `laravel-notification-channels/vonage`
- Cost: ~£0.035 per SMS to UK numbers
- Alternatives if Vonage doesn't suit:
  - **Twilio** (~£0.04/SMS, best Laravel support, slightly more expensive)
  - **TextMagic** (~£0.035/SMS, UK-focused)
- A Vonage account requires sign-up at vonage.com (free sandbox for testing)

**`.env` keys needed:**
```
VONAGE_KEY=your_api_key
VONAGE_SECRET=your_api_secret
VONAGE_SMS_FROM="ACM Portal"
```

**DB change:** add `password_reset_otps` table:
```
- id
- phone        (the phone number)
- otp          (6-digit code, hashed)
- expires_at   (10 minutes from creation)
- used_at      (null until consumed)
```

---

## Phase 2 – Relationships & Family Dues

### 2.1 Member Relationships Page

Each member has a **Relationships** section in their profile/dashboard.

#### Spouse Linking
- A male member can add a female member as wife (and vice versa)
- **Both must already be registered members**
- System creates a bidirectional `spouse` link
- One spouse can unlink (removes both sides)
- A member can only have one spouse link at a time

#### Children Records
- Children are **not members** — they are records only
- A child can be linked to a **father** (male member) and/or **mother** (female member)
- Child fields: `first_name`, `last_name`, `date_of_birth`, `notes`
- Either parent can add a child; the other parent can be tagged if they are also a member
- Admin can view all children records

#### DB Tables Needed
```
member_relationships
  - id
  - member_id_1 (FK users)
  - member_id_2 (FK users)
  - relationship_type: enum ['spouse']
  - created_by (FK users)
  - created_at

member_children
  - id
  - first_name, last_name
  - date_of_birth (nullable)
  - notes (nullable)
  - father_id (FK users, nullable)
  - mother_id (FK users, nullable)
  - added_by (FK users)
  - created_at
```

### 2.2 Annual Dues & Family Billing

Annual dues are **£120**, but split based on family status:

| Situation | Amount |
|-----------|--------|
| Married couple (both members) | £120 shared (one payment covers both) |
| Single member | £60 |
| Single parent | £60 |

**Rules:**
- A couple's dues cycle creates a **shared payment obligation** — when one spouse pays £120, both are marked as settled
- Single members and single parents each owe £60
- Admin / financial secretary sets the dues cycle and amount; family billing is derived from relationship data
- If a couple later separates (unlink), each reverts to individual £60 obligation from the next cycle

### 2.3 Dues Cycles

- Admin creates dues cycles: title, type (`yearly_dues` | `donation` | `event_levy`), amount, start/end date
- Payment options: `once` | `monthly` | `installments`
- System calculates per-member obligation based on relationship status at cycle start

### 2.4 Manual Payment Recording (Financial Secretary)
- Select member, dues cycle, amount, date
- Upload proof of payment (JPG/PNG/PDF, max 4MB)
- Auto-generates receipt number (`ACM-XXXXXXXX`)
- Payment immediately marked `completed`

### 2.5 Online Payments
- **Stripe** (card payments)
- **Paystack** (card / bank transfer)
- Webhook handlers for both (already partially scaffolded)

### 2.6 Payment Dashboard
- Member view: "You've paid £X of £Y this cycle"
- Financial Secretary view: full payment matrix — all members, all cycles
- Arrears report: active members who have not fully paid
- Export to CSV

---

## Phase 3 – Notifications & Welfare Automation

### 3.1 Email/SMS Infrastructure
- **Email** (preferred): via cPanel SMTP — already configured
- **SMS**: vonage or similar (future, optional) — fall back to email only for now
- Admin creates **message templates** with placeholders (e.g. `{member_name}`, `{meeting_date}`, `{missed_count}`)

### 3.2 Notification Templates (Admin UI)
- Admin creates reusable templates per notification type:
  - Absence reminder
  - Welfare escalation
  - Dues reminder
  - General announcement
- Each template has: `name`, `subject`, `body` (with placeholder support), `type`

### 3.3 Automated Cron Jobs

#### Cron A — Weekly Absence Check (Every Monday)
- Finds members who **missed the most recent meeting** (excused excluded)
- Builds notification list
- **Admin reviews and clicks "Send"** — not auto-sent
- Sends email to members with an email address
- Members without email: flagged for manual follow-up (phone call)
- Uses selected template from 3.2

#### Cron B — Welfare Escalation (Every Monday)
- Finds members who have **missed 3 or more consecutive meetings** (excused excluded)
- Builds a separate list for the **welfare officer / admin**
- Admin reviews list and clicks "Send" to notify those members
- System also sends an **internal alert to admin** that the list is ready

#### Cron C — Financial Health Check (Every 6 Months: Jan 1 & Jul 1)
- Builds two lists:
  1. Members with **outstanding dues** (partially paid or unpaid)
  2. Members with **zero payments in the last 12 months**
- Notifies **Financial Secretary + Admin (Chairman)** that lists are ready for review
- No auto-send to members — finsec/chairman takes action

### 3.4 Manual Bulk Notifications (Admin)
- Admin can send a message to:
  - All active members
  - A filtered group (by attendance status, payment status, etc.)
  - Individual member
- Choose template or write custom message
- Delivery via email

---

## Phase 4 – Reports, Notice Board & Polish

### 4.1 Attendance Reports
- Per-meeting attendance sheet (who attended, late, excused, absent)
- Member attendance summary: % attended vs total meetings held
- **70% threshold highlight** — members below threshold flagged (election eligibility)
- Date range filter
- Export to CSV

### 4.2 Financial Reports (already partially built)
- Monthly collections bar chart
- Dues cycle breakdown: target vs collected vs payer count
- Arrears by member
- Export to CSV/PDF

### 4.3 Notice Board
- Admin creates notices with priority (urgent / normal / low) and optional expiry date
- Members see notices on dashboard sorted by priority
- Admin can pin, edit, delete notices

### 4.4 Member Profile Polish
- Profile photo upload
- Address field
- Full relationship view (spouse + children)
- Download own data (GDPR)

### 4.5 Security & Audit Log
- Log sensitive actions: login/logout, payment recorded, attendance modified, member data changed
- Admin can view audit log with filters
- Retain for 2 years

---

## Database — New Tables for Phase 2+

### `member_relationships`
| Column           | Type          | Notes                              |
|------------------|---------------|------------------------------------|
| id               | bigint PK     |                                    |
| member_id_1      | FK users      | The member who initiated the link  |
| member_id_2      | FK users      | The other member                   |
| relationship_type| enum          | spouse (only type for now)         |
| created_by       | FK users      | Who created this record            |
| created_at       | timestamp     |                                    |

### `member_children`
| Column      | Type       | Notes                              |
|-------------|------------|------------------------------------|
| id          | bigint PK  |                                    |
| first_name  | string     |                                    |
| last_name   | string     |                                    |
| date_of_birth| date?     |                                    |
| notes       | text?      |                                    |
| father_id   | FK users?  | Nullable — male member             |
| mother_id   | FK users?  | Nullable — female member           |
| added_by    | FK users   |                                    |
| created_at  | timestamp  |                                    |

### `notification_templates`
| Column      | Type       | Notes                               |
|-------------|------------|-------------------------------------|
| id          | bigint PK  |                                     |
| name        | string     | Admin-facing label                  |
| type        | enum       | absence / welfare / dues / general  |
| subject     | string     | Email subject line                  |
| body        | text       | Supports {member_name} placeholders |
| created_by  | FK users   |                                     |
| created_at  | timestamp  |                                     |

### `notification_logs`
| Column        | Type       | Notes                              |
|---------------|------------|------------------------------------|
| id            | bigint PK  |                                    |
| user_id       | FK users?  | Recipient (null for bulk)          |
| type          | string     | Which cron or manual               |
| channel       | enum       | email / sms                        |
| status        | enum       | sent / failed / pending            |
| subject       | string?    |                                    |
| body          | text?      |                                    |
| sent_at       | timestamp? |                                    |

### Updates to `meetings` table (Phase 1 outstanding)
| Column            | Change                                        |
|-------------------|-----------------------------------------------|
| `late_after_time` | Add — time after which check-in is flagged late (required) |
| `end_time`        | Add — meeting end time; QR auto-expires at this time (required) |
| `venue_radius`    | Change default from 150 to **100**            |

### Updates to `attendance_records` table
| Column   | Change                                            |
|----------|---------------------------------------------------|
| `status` | Add `excused` to enum (currently present/late)    |
| `notes`  | Add — admin notes (e.g. "Excused by member", "Location flagged") |

### `password_reset_otps` (Phase 1 outstanding — SMS reset)
| Column     | Type       | Notes                          |
|------------|------------|--------------------------------|
| id         | bigint PK  |                                |
| phone      | string     | The member's phone number      |
| otp        | string     | Hashed 6-digit code            |
| expires_at | timestamp  | 10 minutes from creation       |
| used_at    | timestamp? | Null until consumed            |
| created_at | timestamp  |                                |

---

## Deployment Checklist (cPanel)

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

**Cron job (cPanel → Cron Jobs):**
```
* * * * * /usr/local/bin/php /home/{user}/public_html/artisan schedule:run >> /dev/null 2>&1
```

**Required .env on production:**
```
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
MAIL_MAILER=smtp
MAIL_HOST=abiacommunitymanchester.org.uk
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
PAYSTACK_PUBLIC_KEY=pk_live_...
PAYSTACK_SECRET_KEY=sk_live_...
```

---

## Phase Summary

| Phase | Focus                          | Status        |
|-------|--------------------------------|---------------|
| 1     | Foundation, Auth, Attendance   | ~85% done     |
| 2     | Relationships, Family Dues, Payments | Not started |
| 3     | Notifications & Welfare Crons  | Not started   |
| 4     | Reports, Notice Board, Polish  | Not started   |

---

*v2.0 – ACM Community Portal – February 2026*
