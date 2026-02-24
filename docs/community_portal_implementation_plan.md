# Community Portal Implementation Plan
## Complete Technical Specification & Rollout Strategy

**Project:** Community Management Portal  
**Tech Stack:** PHP, MySQL, React/TypeScript, MessageBird API  
**Members:** 120 total, ~60 active  
**Location:** United Kingdom  
**Last Updated:** February 2026

---

## Table of Contents
1. [Executive Summary](#executive-summary)
2. [Technical Architecture](#technical-architecture)
3. [QR Attendance System](#qr-attendance-system)
4. [Database Schema](#database-schema)
5. [Member Registration & Authentication](#member-registration--authentication)
6. [Payment Integration](#payment-integration)
7. [Notification System](#notification-system)
8. [Phase-by-Phase Implementation](#phase-by-phase-implementation)
9. [Cost Analysis](#cost-analysis)
10. [Security Considerations](#security-considerations)
11. [Code Snippets & Examples](#code-snippets--examples)

---

## Executive Summary

### Problems Being Solved
1. **Attendance Disputes** - Paper-based attendance led to election eligibility conflicts (70% threshold)
2. **Payment Tracking** - No centralized view of who's paid monthly/annual dues
3. **Communication Gaps** - Manual follow-up for absences, welfare rota, payment reminders
4. **Family Management** - No system to link spouses and track dependents

### Solution Overview
Web-based member portal with:
- QR code + GPS-verified attendance (90-min session window)
- Executive tablet fallback for unregistered members
- Stripe/GoCardless payment integration
- WhatsApp API notifications (SMS fallback)
- Family relationship linking
- Role-based dashboards (Admin, Executive, Financial Secretary, Member)

### Success Metrics
- 100% attendance tracking accuracy (no disputes)
- 95%+ automated notification delivery
- 80% reduction in manual payment tracking effort
- Zero eligibility questions for next election

---

## Technical Architecture

### Technology Stack

**Frontend**
```

```

**Backend (PHP)**
```
PHP 8.4+
- Framework: Plain PHP or Slim Framework (lightweight)
- Authentication: JWT tokens with PHP-JWT library
- API Structure: RESTful endpoints
- File Structure:
  /api
    /auth (login, register, password reset)
    /attendance (check-in, history, QR generation)
    /payments (record, history, webhooks)
    /members (profile, family links)
    /notifications (send, templates)
    /admin (reports, bulk actions)
```

**Database**
```
MySQL 8.0+
- Collation: utf8mb4_unicode_ci
- Storage Engine: InnoDB (transactions for payments)
- Backup: Daily automated backups via cPanel cron
```

**Third-Party Services**
```
- Payments: Stripe (one-off) + GoCardless (Direct Debit)
- Messaging: MessageBird WhatsApp API + SMS
- Email: cPanel native SMTP or SendGrid (optional)
- QR Generation: PHP QR Code library (endroid/qr-code)
```

### Hosting Configuration

**cPanel Setup**
```
1. Main domain: portal.yourcommunity.org.uk
2. SSL: Let's Encrypt (free via cPanel)
3. PHP Version: 8.4
4. MySQL Database: community_portal_db
5. Cron Jobs: 
   - Daily 7am: Check overdue payments, send reminders
   - Daily 9am: Welfare rota notifications (3 days before meeting)
   - Weekly Monday: Absence notifications (missed 2+ meetings)
```

**File Structure**
```
/public_html
  /api (PHP backend)
  /build (React production build - static files)
  /uploads (member photos, receipts)
  /qr-codes (temporary QR images, auto-delete after 90min)
  .htaccess (URL rewriting)
  config.php (DB credentials, API keys)
```

---

## QR Attendance System

### Architecture Flow

```
STEP 1: QR Generation (Meeting Starts - 5:00 PM)
→ Secretary clicks "Start Meeting" in admin panel
→ System generates master QR code for the meeting
→ QR displayed on projector/screen

STEP 2: Member Scans QR
→ QR contains: https://portal.com/attend/{unique_hash}
→ Opens mobile browser → attendance form

STEP 3: Identification
→ Member enters phone number (+447700900XXX)
→ System validates: Is this a registered member?

STEP 4: Location Verification (Optional but Logged)
→ Request GPS coordinates via browser API
→ If granted: Log lat/long, check if within 200m of venue
→ If denied: Log "GPS_DENIED", inform user to let admin sign them in
→ If outside radius: Log "REMOTE_CHECKIN", inform user to let admin sign them in

STEP 5: Confirmation
→ Log attendance with timestamp, GPS status, device info
→ Show success message: "Checked in at 5:12 PM ✓"
→ Attendance appears live on admin dashboard
→ Attendance appears live on user dashboard

STEP 6: Late Check-ins (After 5:20 PM)
→ System flags as "LATE" but still counts for attendance
→ Same process, different status field
```

### Unique Hash Generation

**Requirements:**
- Unique per meeting session
- Expires at them end of meeting
- Unpredictable (prevent guessing)
- URL-safe characters

### Executive Tablet Check-In

**Use Cases:**
1. New visitors (not yet registered)
2. Members with dead/forgotten phones
3. Guests/spouses attending but not full members
4. Technical failures

**Implementation:**
```
Executive Dashboard → "Manual Check-In" button
→ Opens modal with searchable member list
→ Tap name → Instant check-in logged
→ Option to add note: "Phone dead", "First-time visitor", etc.
```

---


## Member Registration & Authentication

### Phase 1: Member Import (Admin Task)

### Phase 2: Send Activation Links

**Bulk SMS Function:**

### Phase 3: Member Activation Page

**Activation Form (HTML/React):**

**PHP Activation Handler:**

### Authentication System

**Login Flow:**

### Family Linking UI

**Member Dashboard - Family Section:**


## Payment Integration

### Stripe Setup (One-Off Payments)

**PHP Stripe Handler:**

**Frontend Stripe Integration:**

### GoCardless Setup (Direct Debit)

**For recurring monthly payments:**

### Manual Payment Recording (Cash/Bank Transfer)

**Financial Secretary Interface:**


## Notification System

### WhatsApp API with SMS Fallback

**MessageBird Setup:**

### Automated Notification Triggers

**Cron Job Configuration:**
```
# /etc/crontab or cPanel Cron Jobs


# Daily 9am: Welfare rota notifications (21 days before meeting)
0 9 * * * php /path/to/scripts/welfare-notifications.php

# Weekly Monday 10am: Pledge remindersPledge reminders
0 10 * * 1 php /path/to/scripts/pledge-reminders.php

```

**Payment Reminder Script:**

**Absence Notification Script:**

**Welfare Rota Notification:**

### Message Templates

**Create reusable templates:**

---

## Phase-by-Phase Implementation

### **PHASE 1: Foundation (Weeks 1-3)**

**Goal:** Core authentication, attendance tracking, basic dashboard

#### Week 1: Setup & Infrastructure
- [ ] **Day 1-2:** Environment setup
  - Create MySQL database
  - Import database schema
  - Install Composer dependencies (PHP libraries)
  - Configure cPanel for PHP 8.1+
  - Setup SSL certificate (Let's Encrypt)
  - Create `.env` file for API keys

- [ ] **Day 3-5:** Member import & authentication
  - Build CSV import script for existing members
  - Import all 120 members
  - Create activation token generation
  - Build login/logout API endpoints
  - Implement JWT authentication
  - Test with 5-10 members

- [ ] **Day 6-7:** Frontend scaffold
  - Setup React + TypeScript project
  - Install Tailwind CSS
  - Create basic layout (header, sidebar, content)
  - Build login/register pages
  - Implement JWT storage (localStorage + httpOnly cookies)

#### Week 2: Attendance System
- [ ] **Day 1-2:** QR attendance backend
  - Build QR hash generation function
  - Create meeting creation endpoint
  - Build attendance submission endpoint
  - Implement GPS logging (store lat/long)
  - Create validation logic 

- [ ] **Day 3-4:** QR attendance frontend
  - Build QR code display page (admin view)
  - Create attendance submission form
  - Implement GPS coordinate capture (JavaScript)
  - Add device fingerprinting
  - Build success/error handling

- [ ] **Day 5-7:** Executive tablet check-in
  - Build searchable member list API
  - Create manual check-in endpoint
  - Design tablet-optimized UI
  - Add quick search functionality
  - Test with real meeting scenario

#### Week 3: Dashboards & Testing
- [ ] **Day 1-3:** Member dashboard
  - Display attendance history (table + graph)
  - Show payment status summary
  - Create profile editing form
  - Add notice board view
  - Display family links section

- [ ] **Day 4-5:** Admin dashboard
  - Meeting management (create, view, QR generation)
  - Live attendance view (who's checked in)
  - Attendance reports (export to CSV)
  - Member management (activate, deactivate)

- [ ] **Day 6-7:** Testing & bug fixes
  - End-to-end testing with 10 test accounts
  - Fix any authentication issues
  - Ensure QR expiry works correctly
  - Load testing (simulate 60 concurrent check-ins)
  - Security audit (SQL injection, XSS)

**Deliverables:**
- ✅ Working login system
- ✅ QR attendance check-in
- ✅ Executive manual check-in
- ✅ Member dashboard showing attendance
- ✅ Admin dashboard with live attendance

---

### **PHASE 2: Payments & Family Links (Weeks 4-6)**

**Goal:** Payment tracking, Stripe integration, family relationships

#### Week 4: Payment Foundation
- [ ] **Day 1-2:** Payment type setup
  - Create payment types (monthly, annual)
  - Build payment recording API
  - Create manual payment entry form (Financial Secretary)
  - Add payment validation

- [ ] **Day 3-4:** Stripe integration
  - Setup Stripe account
  - Install Stripe PHP SDK
  - Build payment intent creation endpoint
  - Implement webhook handler
  - Create frontend payment form

- [ ] **Day 5-7:** Payment dashboard
  - Member view: "You've paid £X of £Y"
  - Financial Secretary view: Full payment matrix
  - Add filters (date range, payment status)
  - Export to Excel functionality
  - Generate receipt PDFs

#### Week 5: Family Linking
- [ ] **Day 1-3:** Family relationship system
  - Build spouse linking API
  - Create dependent addition API
  - Implement relationship display
  - Add unlinking functionality

- [ ] **Day 4-5:** Frontend family management
  - Searchable spouse selector
  - Dependent form (name, DOB, relationship)
  - Display linked family members
  - Edit/remove relationships

- [ ] **Day 6-7:** Testing
  - Test payment webhooks
  - Verify receipt generation
  - Test family links (edge cases: divorce, remarriage)
  - Security check on payment endpoints

#### Week 6: GoCardless (Optional)
- [ ] **Day 1-3:** Direct Debit setup
  - Setup GoCardless account
  - Implement mandate creation
  - Build subscription scheduling
  - Create cancellation flow

- [ ] **Day 4-7:** Integration testing
  - Test end-to-end Direct Debit flow
  - Verify monthly auto-collection
  - Handle failed payments
  - Buffer week for fixes

**Deliverables:**
- ✅ Manual payment recording
- ✅ Stripe one-off payments
- ✅ Receipt generation
- ✅ Family linking system
- ✅ Payment dashboards for all roles
- ⚠️ GoCardless (optional, can defer to Phase 4)

---

### **PHASE 3: Automation & Notifications (Weeks 7-9)**

**Goal:** WhatsApp/SMS notifications, automated reminders, welfare rota

#### Week 7: MessageBird Setup
- [ ] **Day 1-2:** Account setup
  - Create MessageBird account
  - Setup WhatsApp Business Channel
  - Get API credentials
  - Test sandbox environment

- [ ] **Day 3-4:** Notification infrastructure
  - Build sendNotification() function
  - Implement WhatsApp → SMS fallback logic
  - Create notification logging system
  - Build message templates

- [ ] **Day 5-7:** Manual notifications
  - Admin panel: Send custom messages
  - Bulk messaging (select members)
  - Template selection dropdown
  - Test delivery rates

#### Week 8: Automated Triggers
- [ ] **Day 1-2:** Payment reminders
  - Build cron job for overdue payments
  - Implement reminder logic
  - Test notification delivery
  - Add opt-out mechanism

- [ ] **Day 3-4:** Absence notifications
  - Cron job: 2+ consecutive misses
  - Escalation: 3+ misses → welfare team
  - Test notification timing

- [ ] **Day 5-7:** Welfare rota
  - Build rota management interface
  - Assign members to meetings
  - Implement 3-day advance notification
  - Add confirmation system

#### Week 9: Pledge Management
- [ ] **Day 1-3:** Pledge tracking system
  - Create pledge recording interface
  - Build dashboard (member + admin)
  - Implement partial payment tracking
  - Add fulfillment status

- [ ] **Day 4-5:** Pledge reminders
  - Monthly reminder cron job
  - Gentle messaging templates
  - Dashboard notifications

- [ ] **Day 6-7:** Testing
  - End-to-end notification testing
  - Verify cron jobs run correctly
  - Check MessageBird costs
  - Security audit

**Deliverables:**
- ✅ WhatsApp/SMS notification system
- ✅ Automated payment reminders
- ✅ Absence follow-ups
- ✅ Welfare rota management
- ✅ Pledge tracking & reminders

---

### **PHASE 4: Polish & Advanced Features (Weeks 10-12)**

**Goal:** Notice board, reports, mobile PWA, final testing

#### Week 10: Notice Board & Reports
- [ ] **Day 1-3:** Notice board
  - Create/edit/delete notices (admin)
  - Priority flags (urgent, normal, low)
  - Pin important notices
  - Member view with filters

- [ ] **Day 4-5:** Advanced reports
  - Attendance analytics (graphs, trends)
  - Payment reports (arrears, projections)
  - Member growth over time
  - Export to PDF/Excel

- [ ] **Day 6-7:** Role management
  - Assign roles to members
  - Permission-based UI (hide features by role)
  - Test all role types (admin, exec, financial sec, member)

#### Week 11: Mobile Optimization
- [ ] **Day 1-3:** Progressive Web App (PWA)
  - Add manifest.json
  - Implement service worker (offline support)
  - Cache static assets
  - Test "Add to Home Screen"

- [ ] **Day 4-5:** Mobile UI polish
  - Responsive design testing
  - Touch-friendly buttons
  - Optimize QR scanning on mobile
  - Test on Android + iOS

- [ ] **Day 6-7:** Performance optimization
  - Image compression
  - Code minification
  - Database indexing
  - Load time < 3 seconds

#### Week 12: Launch Preparation
- [ ] **Day 1-2:** User acceptance testing
  - Train 5 executives on system
  - Conduct mock meeting with real QR check-in
  - Gather feedback
  - Fix critical bugs

- [ ] **Day 3-4:** Documentation
  - Write admin guide (how to create meetings, record payments)
  - Create member guide (how to check in, pay, link family)
  - Video tutorials (5-10 minutes each)

- [ ] **Day 5:** Data migration
  - Final member data import
  - Historical attendance import (if available)
  - Payment history backfill

- [ ] **Day 6-7:** Launch!
  - Send activation links to all 120 members
  - Monitor activation rate
  - Provide support for login issues
  - Prepare for first meeting with new system

**Deliverables:**
- ✅ Notice board
- ✅ Advanced reports
- ✅ Mobile PWA
- ✅ Full documentation
- ✅ System launched!

---

## Cost Analysis

### Setup Costs (One-Time)
| Item | Cost |
|------|------|
| Domain name (1 year) | £10-15 |
| SSL Certificate | FREE (Let's Encrypt) |
| Development time (120 hours @ £30/hr) | £3,600* |
| **Total Setup** | **£3,610-15** |

*If self-developed, cost is your time. If hiring: £2,000-4,000 for freelancer.

### Monthly Recurring Costs
| Service | Cost |
|---------|------|
| **Hosting** (cPanel) | £5-10/month |
| **MessageBird** (WhatsApp API) |  |
| - 60 members × 3 msgs/month = 180 msgs | £1.51-2.34/month |
| - SMS fallback (10% = 18 msgs) | £0.63/month |
| **Stripe** (transaction fees) |  |
| - 30 payments/month @ £20 avg = £600 | £9.00 + £6.00 = £15/month |
| **GoCardless** (optional Direct Debit) |  |
| - 30 subscriptions @ £20 = £600 | £6.00 + £6.00 = £12/month |
| **Backup storage** (optional) | £2-5/month |
| **Total Monthly** | **£24-32/month** |

### Annual Cost Summary
- **Year 1:** £3,610 + (£28 × 12) = **£3,946**
- **Year 2+:** £28 × 12 = **£336/year**

### Cost Savings vs Manual Process
| Manual Process | Portal |
|----------------|--------|
| Secretary time: 2 hrs/month @ £15/hr = £360/year | Automated |
| Payment tracking: 1 hr/month @ £15/hr = £180/year | Automated |
| SMS reminders: None (manual calls) | £18-28/month |
| **Total annual cost** | £540/year |
| **Portal annual cost** | £336/year |
| **Net savings** | **£204/year** |

Plus: Better record-keeping, zero disputes, professional image.

---

## Security Considerations

### Data Protection (GDPR Compliance)
1. **User consent:**
   - Members consent to data storage during activation
   - Clear privacy policy on website
   - Option to delete account

2. **Data minimization:**
   - Only collect necessary data
   - Don't store full card numbers (Stripe handles)
   - Automatic deletion of QR codes after 90 minutes

3. **Right to access:**
   - Members can download their data
   - Export attendance/payment history

4. **Data encryption:**
   - SSL/TLS for all communications
   - Hash passwords with bcrypt
   - Encrypt sensitive fields in database (optional)

### Authentication Security
1. **Password requirements:**
   - Minimum 8 characters
   - Force password change after 12 months
   - Rate limiting on login attempts

2. **Session management:**
   - JWT tokens expire after 24 hours
   - Refresh tokens for extended sessions
   - Logout = token blacklist

3. **Two-factor authentication (optional):**
   - SMS-based 2FA for executives
   - Required for financial transactions

### API Security
1. **Input validation:**
   - Sanitize all user inputs
   - Prepared statements (prevent SQL injection)
   - CSRF tokens on forms

2. **Rate limiting:**
   - Max 5 login attempts per 15 minutes
   - Max 10 API calls per minute per user

3. **Webhook verification:**
   - Validate Stripe webhook signatures
   - Verify MessageBird callbacks

### Audit Logging
- Log all sensitive actions:
  - Login/logout
  - Payment recording
  - Member data changes
  - Attendance modifications
- Retain logs for 2 years
- Regular security audits

---

## Code Snippets & Examples

### Complete API Endpoint Structure

```
/api
├── /auth
│   ├── login.php              # POST: Login with phone/email + password
│   ├── logout.php             # POST: Invalidate JWT token
│   ├── activate.php           # POST: Complete member activation
│   ├── forgot-password.php    # POST: Request password reset
│   └── reset-password.php     # POST: Complete password reset
│
├── /members
│   ├── profile.php            # GET: Fetch member details
│   ├── update-profile.php     # PUT: Update email, DOB, etc.
│   ├── link-spouse.php        # POST: Link existing member as spouse
│   ├── add-dependent.php      # POST: Add child/dependent
│   ├── remove-dependent.php   # DELETE: Remove dependent
│   └── search.php             # GET: Search members (for spouse linking)
│
├── /attendance
│   ├── generate-qr.php        # POST: Admin creates meeting QR
│   ├── validate-hash.php      # GET: Check if QR hash is valid
│   ├── check-in.php           # POST: Member checks in via QR
│   ├── manual-check-in.php    # POST: Executive manual check-in
│   ├── history.php            # GET: Member's attendance history
│   └── live-attendance.php    # GET: Who's checked in right now
│
├── /payments
│   ├── record-manual.php      # POST: Financial Secretary logs cash/transfer
│   ├── create-intent.php      # POST: Create Stripe payment intent
│   ├── stripe-webhook.php     # POST: Handle Stripe payment confirmations
│   ├── history.php            # GET: Member's payment history
│   ├── all-payments.php       # GET: All payments (Financial Secretary)
│   └── export.php             # GET: Export payments to Excel
│
├── /pledges
│   ├── create.php             # POST: Record member pledge
│   ├── pay.php                # POST: Record pledge payment
│   ├── my-pledges.php         # GET: Member's pledges
│   └── all-pledges.php        # GET: All pledges (Admin view)
│
├── /welfare
│   ├── create-rota.php        # POST: Assign members to meeting
│   ├── get-rota.php           # GET: Fetch rota for meeting
│   ├── notify-team.php        # POST: Send rota notifications
│   └── confirm-duty.php       # POST: Member confirms attendance
│
├── /notices
│   ├── create.php             # POST: Admin creates notice
│   ├── update.php             # PUT: Edit existing notice
│   ├── delete.php             # DELETE: Remove notice
│   └── list.php               # GET: Fetch all active notices
│
├── /admin
│   ├── import-members.php     # POST: Bulk import from CSV
│   ├── send-activation.php    # POST: Send activation links
│   ├── reports.php            # GET: Generate analytics reports
│   └── audit-log.php          # GET: View system audit log
│
└── /notifications
    ├── send-custom.php        # POST: Admin sends custom message
    └── test.php               # POST: Test WhatsApp/SMS delivery
```

### Frontend File Structure

```
/src
├── /components
│   ├── /auth
│   │   ├── LoginForm.tsx
│   │   ├── ActivationForm.tsx
│   │   └── PasswordResetForm.tsx
│   │
│   ├── /attendance
│   │   ├── QRDisplay.tsx          # Admin view: Show QR code
│   │   ├── CheckInForm.tsx        # Member view: Submit check-in
│   │   ├── ManualCheckIn.tsx      # Executive: Tap names to check in
│   │   └── AttendanceHistory.tsx
│   │
│   ├── /payments
│   │   ├── PaymentForm.tsx        # Stripe payment form
│   │   ├── PaymentHistory.tsx
│   │   ├── ManualPaymentForm.tsx  # Financial Secretary
│   │   └── PaymentDashboard.tsx
│   │
│   ├── /family
│   │   ├── FamilyLinks.tsx
│   │   ├── SpouseSelector.tsx
│   │   └── DependentForm.tsx
│   │
│   ├── /notices
│   │   ├── NoticeBoard.tsx
│   │   ├── NoticeForm.tsx
│   │   └── NoticeCard.tsx
│   │
│   └── /shared
│       ├── Header.tsx
│       ├── Sidebar.tsx
│       └── LoadingSpinner.tsx
│
├── /pages
│   ├── Dashboard.tsx
│   ├── Profile.tsx
│   ├── Attendance.tsx
│   ├── Payments.tsx
│   ├── Admin.tsx
│   └── Login.tsx
│
├── /services
│   ├── api.ts                 # Axios instance with JWT interceptor
│   ├── auth.ts                # Login, logout, token refresh
│   ├── attendance.ts          # Attendance API calls
│   └── payments.ts            # Payment API calls
│
├── /utils
│   ├── gps.ts                 # GPS coordinate functions
│   ├── validation.ts          # Form validation
│   └── formatting.ts          # Date, currency formatting
│
└── App.tsx
```

### Sample .env File

```bash
# Database
DB_HOST=localhost
DB_NAME=community_portal_db
DB_USER=portal_user
DB_PASS=secure_password_here

# Security
JWT_SECRET=random_256_bit_secret_here
ATTENDANCE_SECRET=another_random_secret_for_qr_hashing

# Stripe
STRIPE_SECRET_KEY=sk_live_xxxxx
STRIPE_PUBLISHABLE_KEY=pk_live_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx

# GoCardless (optional)
GOCARDLESS_ACCESS_TOKEN=sandbox_xxxxx

# MessageBird
MESSAGEBIRD_API_KEY=live_xxxxx
MESSAGEBIRD_WHATSAPP_CHANNEL_ID=channel_id_here

# Application
APP_URL=https://portal.yourcommunity.org.uk
APP_ENV=production

# Venue GPS (for attendance verification)
VENUE_LATITUDE=51.5074
VENUE_LONGITUDE=-0.1278
VENUE_RADIUS_METERS=200
```

---

## Next Steps After Reading This Document

### Immediate Actions (Week 1)
1. **Purchase domain:** portal.yourcommunity.org.uk
2. **Setup cPanel hosting:** Ensure PHP 8.4, MySQL 8.0+ available
3. **Create MessageBird account:** Test API in sandbox mode
4. **Create Stripe account:** Get test API keys
5. **Gather member data:** Compile CSV of all 120 members (name, phone, join date)

### Decision Points
Before starting development, decide:

1. **Stripe vs GoCardless:**
   - Stripe-only (simpler, one-off payments)
   - Stripe + GoCardless (recurring Direct Debit)
   - My recommendation: Start with Stripe, add GoCardless in Phase 4 if needed

2. **Launch timing:**
   - Soft launch (10-20 members beta test)
   - Full launch (all 120 members)
   - My recommendation: Soft launch in Phase 1, full launch after Phase 2

3. **Development approach:**
   - Self-develop (12 weeks, your time)
   - Hire freelancer (6-8 weeks, £2,000-4,000)
   - Hybrid (you build core, hire for polish)

### Support & Maintenance Plan
Post-launch:
- **Week 1-4:** Daily monitoring, quick bug fixes
- **Month 2-3:** Weekly check-ins, feature tweaks
- **Ongoing:** Monthly system health checks, quarterly feature updates

---

## Appendix

### Useful PHP Libraries (Composer)
```json
{
    "require": {
        "php": ">=8.1",
        "firebase/php-jwt": "^6.0",
        "stripe/stripe-php": "^10.0",
        "gocardless/gocardless-pro": "^4.0",
        "messagebird/php-rest-api": "^2.0",
        "endroid/qr-code": "^4.0",
        "phpmailer/phpmailer": "^6.0",
        "guzzlehttp/guzzle": "^7.0"
    }
}
```


### Database Backup Script
```bash
#!/bin/bash
# /scripts/backup-database.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/backups/portal"
DB_NAME="community_portal_db"
DB_USER="portal_user"
DB_PASS="secure_password"

mkdir -p $BACKUP_DIR

mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Keep last 30 days
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup completed: backup_$DATE.sql.gz"
```

### Testing Checklist
- [ ] Registration & activation flow
- [ ] Login with phone vs email
- [ ] QR check-in (test 90-min expiry)
- [ ] GPS denial handling
- [ ] Manual executive check-in
- [ ] Stripe payment (test mode)
- [ ] Payment webhook processing
- [ ] WhatsApp notification delivery
- [ ] SMS fallback
- [ ] Family linking
- [ ] Dashboard data accuracy
- [ ] Mobile responsiveness
- [ ] Password reset flow
- [ ] Role-based permissions
- [ ] Audit log recording

---

## Contact & Support

For questions during implementation:
1. **Technical issues:** Check error logs in `/var/log` or cPanel error log
2. **API errors:** Test endpoints with Postman
3. **Payment issues:** Check Stripe/GoCardless dashboard
4. **Messaging issues:** MessageBird dashboard for delivery logs

**Recommended resources:**
- Stripe docs: https://stripe.com/docs
- MessageBird docs: https://developers.messagebird.com
- PHP JWT: https://github.com/firebase/php-jwt
- React + TypeScript: https://react-typescript-cheatsheet.netlify.app

---

**END OF IMPLEMENTATION PLAN**

*Save this document and reference it at each phase. Update checkboxes as you progress. Good luck with the build!*
