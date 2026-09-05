<?php

namespace Tests\Browser;

use App\Models\ContactLog;
use App\Models\DuesCycle;
use App\Models\Meeting;
use App\Models\MemberChild;
use App\Models\MemberPledge;
use App\Models\Payment;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Support\Docs\ScreenshotAnnotator;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Screenshot-generation script for the Admin Guide (everything beyond the
 * Member Management pilot), run via:
 *
 *   php artisan dusk --filter=DocsScreenshotCaptureAdminTest
 *
 * Same pattern as the other capture scripts — cookies cleared per step,
 * captureAnnotated/capturePlain helpers, real (but side-effect-free) actions
 * preferred over faked state wherever practical.
 */
class DocsScreenshotCaptureAdminTest extends DuskTestCase
{
    public function test_capture_dues_and_payments(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            $admin  = User::where('email', 'admin@test.com')->firstOrFail();
            $member = User::where('email', 'member.a@test.com')->firstOrFail();
            $levy   = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();
            $harvest = DuesCycle::where('title', 'Harvest Pledge 2026')->firstOrFail();

            Payment::create([
                'user_id' => $member->id, 'dues_cycle_id' => $levy->id, 'amount' => 60,
                'currency' => 'GBP', 'method' => 'manual', 'status' => 'completed',
                'payment_date' => now(), 'receipt_number' => Payment::generateReceiptNumber(),
                'recorded_by' => $admin->id,
            ]);
            $stripePayment = Payment::create([
                'user_id' => $member->id, 'dues_cycle_id' => $levy->id, 'amount' => 60,
                'currency' => 'GBP', 'method' => 'stripe', 'status' => 'completed',
                'payment_date' => now(), 'receipt_number' => Payment::generateReceiptNumber(),
                'gateway_reference' => 'pi_' . Str::random(24),
            ]);
            MemberPledge::create([
                'user_id' => $member->id, 'dues_cycle_id' => $harvest->id,
                'pledged_amount' => 50, 'currency' => 'GBP', 'recorded_by' => $admin->id,
            ]);

            $this->loginAsAdmin($browser);

            // create-1: dues cycle list.
            $browser->visit('/admin/dues-cycles')->pause(300);
            $this->capturePlain($browser, 'dues-create-1', 'admin/dues-and-payments/create-1.png');

            // create-2: new dues cycle form.
            $browser->visit('/admin/dues-cycles/create')->pause(300);
            $this->captureAnnotated($browser, 'dues-create-2', 'admin/dues-and-payments/create-2.png', [
                'input[name="title"]',
            ]);

            // create-3: cycle detail page.
            $browser->visit('/admin/dues-cycles/' . $levy->id)->pause(300);
            $this->capturePlain($browser, 'dues-create-3', 'admin/dues-and-payments/create-3.png');

            // pledges-1 / pledges-2: contributions page for the pledge-based cycle.
            $browser->visit('/admin/dues-cycles/' . $harvest->id . '/pledges')->pause(300);
            $this->captureAnnotated($browser, 'pledges-1', 'admin/dues-and-payments/pledges-1.png', [
                '#memberSearch',
            ]);
            $this->capturePlain($browser, 'pledges-2', 'admin/dues-and-payments/pledges-2.png');

            // payments-1: payments list.
            $browser->visit('/admin/payments')->pause(300);
            $this->capturePlain($browser, 'payments-1', 'admin/dues-and-payments/payments-1.png');

            // payments-2: record payment form.
            $browser->visit('/admin/payments/create')->pause(300);
            $this->captureAnnotated($browser, 'payments-2', 'admin/dues-and-payments/payments-2.png', [
                '#memberSearch',
            ]);

            // reconciliation-1: Stripe reconciliation (the stripe payment above shows here).
            $browser->visit('/admin/stripe-reconciliation')->pause(300);
            $this->capturePlain($browser, 'reconciliation-1', 'admin/dues-and-payments/reconciliation-1.png');
        });
    }

    public function test_capture_meetings_and_attendance(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            $admin  = User::where('email', 'admin@test.com')->firstOrFail();
            $member = User::where('email', 'member.a@test.com')->firstOrFail();

            $meeting = Meeting::create([
                'title' => 'March 2026 General Meeting', 'meeting_date' => now()->toDateString(),
                'meeting_time' => '18:00:00', 'venue' => 'ACM Community Hall',
                'status' => 'active', 'qr_token' => Str::random(32), 'created_by' => $admin->id,
            ]);
            $meeting->attendanceRecords()->create([
                'user_id' => $member->id, 'check_in_time' => now(),
                'check_in_method' => 'manual', 'status' => 'present', 'recorded_by' => $admin->id,
            ]);

            $this->loginAsAdmin($browser);

            // create-1: new meeting form.
            $browser->visit('/admin/meetings/create')->pause(300);
            $this->captureAnnotated($browser, 'meetings-create-1', 'admin/meetings-and-attendance/create-1.png', [
                'input[name="title"]',
            ]);

            // create-2: an active meeting's detail page with a live QR code.
            $browser->visit('/admin/meetings/' . $meeting->id)->pause(500);
            $this->capturePlain($browser, 'meetings-create-2', 'admin/meetings-and-attendance/create-2.png');

            // report-1: attendance report.
            $browser->visit('/admin/meetings/report')->pause(500);
            $this->capturePlain($browser, 'attendance-report-1', 'admin/meetings-and-attendance/report-1.png');

            // absentees-1: consecutive absentees.
            $browser->visit('/admin/meetings/consecutive-absentees')->pause(300);
            $this->capturePlain($browser, 'absentees-1', 'admin/meetings-and-attendance/absentees-1.png');

            // minutes-1: upload minutes form.
            $browser->visit('/admin/minutes/create')->pause(300);
            $this->captureAnnotated($browser, 'minutes-admin-1', 'admin/meetings-and-attendance/minutes-1.png', [
                'input[name="title"]',
            ]);
        });
    }

    public function test_capture_reports(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();
            $this->loginAsAdmin($browser);

            $levy = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            // financial-1.
            $browser->visit('/admin/reports/financial')->pause(500);
            $this->capturePlain($browser, 'financial-1', 'admin/reports/financial-1.png');

            // arrears-1: with a cycle selected so the table has content.
            $browser->visit('/admin/reports/arrears?cycle_id=' . $levy->id)->pause(500);
            $this->capturePlain($browser, 'arrears-1', 'admin/reports/arrears-1.png');

            // engagement-1.
            $browser->visit('/admin/reports/engagement')->pause(500);
            $this->capturePlain($browser, 'engagement-1', 'admin/reports/engagement-1.png');

            // summary-1.
            $browser->visit('/admin/reports/members')->pause(500);
            $this->capturePlain($browser, 'summary-1', 'admin/reports/summary-1.png');
        });
    }

    public function test_capture_communications(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            SmsTemplate::create([
                'name' => 'Dues Reminder', 'channel' => 'sms',
                'body' => 'Hi {name}, you have an outstanding balance of £{amount} for {cycle}.',
            ]);
            ContactLog::create([
                'user_id' => User::where('email', 'member.a@test.com')->value('id'),
                'batch_id' => (string) Str::uuid(), 'channel' => 'sms',
                'message' => 'Hi Ada, you have an outstanding balance of £30.00 for Standard Levy 2026.',
                'context' => 'bulk', 'status' => 'sent',
                'sent_by' => User::where('email', 'admin@test.com')->value('id'),
            ]);

            $this->loginAsAdmin($browser);

            // templates-1: new template form.
            $browser->visit('/admin/sms-templates/create')->pause(300);
            $this->captureAnnotated($browser, 'templates-1', 'admin/communications/templates-1.png', [
                'input[name="name"]',
            ]);

            // bulk-1: bulk message page.
            $browser->visit('/admin/messages')->pause(500);
            $this->capturePlain($browser, 'bulk-1', 'admin/communications/bulk-1.png');

            // contact-log-1.
            $browser->visit('/admin/contact-log')->pause(500);
            $this->capturePlain($browser, 'contact-log-1', 'admin/communications/contact-log-1.png');

            // recipients-1: add a real recipient (safe — no external send, just a DB row).
            $browser->visit('/admin/notification-recipients')->pause(300);
            $browser->type('input[name="name"]', 'ACM Secretary')
                    ->type('input[name="email"]', 'secretary@abiacommunitymanchester.org.uk')
                    ->press('Add Recipient')
                    ->pause(500);
            $this->capturePlain($browser, 'recipients-1', 'admin/communications/recipients-1.png');
        });
    }

    public function test_capture_family_roles_audit(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            $memberA = User::where('email', 'member.a@test.com')->firstOrFail();
            $memberB = User::where('email', 'member.b@test.com')->firstOrFail();

            MemberChild::create([
                'first_name' => 'Sam', 'last_name' => 'Dusk', 'date_of_birth' => '2020-05-14',
                'gender' => 'male', 'father_id' => $memberB->id, 'mother_id' => $memberA->id,
                'added_by' => $memberA->id,
            ]);

            $this->loginAsAdmin($browser);

            // children-1: admin children list.
            $browser->visit('/admin/children')->pause(300);
            $this->capturePlain($browser, 'children-admin-1', 'admin/family-records/children-1.png');

            // roles-1: create role form.
            $browser->visit('/admin/roles/create')->pause(300);
            $this->captureAnnotated($browser, 'roles-1', 'admin/roles-and-permissions/roles-1.png', [
                'input[name="name"]',
            ]);

            // Actually create a role (real, safe — a DB row and an audit log entry,
            // exactly what the next two screenshots need to show real content).
            $browser->type('input[name="name"]', 'Treasurer')
                    ->type('input[name="description"]', 'Handles payments and reconciliation')
                    ->check('input[value="payments"]')
                    ->check('input[value="reconciliation"]')
                    ->press('Create Role')
                    ->pause(500);

            // Promote the Financial Secretary account to admin-with-role visibility
            // (it's already role=admin per DuskSeeder) then assign the new role.
            $browser->visit('/admin/roles/assign')->pause(300);
            $this->capturePlain($browser, 'roles-2', 'admin/roles-and-permissions/roles-2.png');

            // audit-1: by now several real admin actions have happened this run.
            $browser->visit('/admin/audit')->pause(500);
            $this->capturePlain($browser, 'audit-1', 'admin/audit-trail/audit-1.png');
        });
    }

    private function captureAnnotated(Browser $browser, string $rawName, string $destRelativePath, array $selectors): void
    {
        $points = [];

        foreach ($selectors as $i => $selector) {
            $element = $browser->element($selector);

            if (! $element) {
                continue;
            }

            $location = $element->getLocationOnScreenOnceScrolledIntoView();
            $size     = $element->getSize();

            $points[] = [
                'x'     => $location->getX() - 20,
                'y'     => $location->getY() + intdiv($size->getHeight(), 2),
                'label' => $i + 1,
            ];
        }

        $browser->screenshot($rawName);

        ScreenshotAnnotator::annotate(
            base_path("tests/Browser/screenshots/{$rawName}.png"),
            public_path("docs-assets/images/{$destRelativePath}"),
            $points
        );
    }

    private function capturePlain(Browser $browser, string $rawName, string $destRelativePath): void
    {
        $browser->screenshot($rawName);

        ScreenshotAnnotator::copyPlain(
            base_path("tests/Browser/screenshots/{$rawName}.png"),
            public_path("docs-assets/images/{$destRelativePath}")
        );
    }
}
