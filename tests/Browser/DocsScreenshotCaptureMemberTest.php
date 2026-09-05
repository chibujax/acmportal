<?php

namespace Tests\Browser;

use App\Models\DuesCycle;
use App\Models\Meeting;
use App\Models\MeetingMinutes;
use App\Models\Payment;
use App\Models\User;
use App\Support\Docs\ScreenshotAnnotator;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Screenshot-generation script for the rest of the Member Guide, run via:
 *
 *   php artisan dusk --filter=DocsScreenshotCaptureMemberTest
 *
 * See DocsScreenshotCaptureTest for the pattern this follows (cookies
 * cleared per step, captureAnnotated/capturePlain helpers).
 */
class DocsScreenshotCaptureMemberTest extends DuskTestCase
{
    public function test_capture_dashboard_and_dues(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            $member = User::where('email', 'member.a@test.com')->firstOrFail();
            $standardLevy = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            // A partial payment so the dashboard shows realistic non-zero figures.
            Payment::create([
                'user_id'       => $member->id,
                'dues_cycle_id' => $standardLevy->id,
                'amount'        => 30.00,
                'currency'      => 'GBP',
                'method'        => 'manual',
                'status'        => 'completed',
                'payment_date'  => now(),
                'receipt_number' => Payment::generateReceiptNumber(),
            ]);

            $this->loginAsMember($browser, 'member.a@test.com');

            // dashboard-1: stat cards + active dues panel.
            $browser->visit('/member/dashboard')->pause(500);
            $this->capturePlain($browser, 'dashboard-1', 'member/your-dashboard/dashboard-1.png');

            // pledge-1: the inline pledge form for the Harvest Pledge cycle (no pledge yet).
            $this->captureAnnotated($browser, 'pledge-1', 'member/paying-your-dues/pledge-1.png', [
                'input[name="pledged_amount"]',
            ]);

            // history-1: payment history table.
            $browser->visit('/member/payments')->pause(300);
            $this->capturePlain($browser, 'history-1', 'member/paying-your-dues/history-1.png');

            // stripe-1: the card payment form (Stripe key is a fake test-shaped
            // value in .env.dusk.local — enough to render the form; no real
            // charge is ever attempted in this script).
            $browser->visit('/pay/stripe/' . $standardLevy->id)->pause(500);
            $this->capturePlain($browser, 'stripe-1', 'member/paying-your-dues/stripe-1.png');
        });
    }

    public function test_capture_profile_and_family(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            // profile-1 / profile-2: view mode, then edit mode.
            $this->loginAsMember($browser, 'member.a@test.com');
            $browser->visit('/member/profile')->pause(300);
            $this->capturePlain($browser, 'profile-1', 'member/profile-and-family/profile-1.png');

            $browser->click('#edit-btn')->pause(300);
            $this->capturePlain($browser, 'profile-2', 'member/profile-and-family/profile-2.png');

            // spouse-2: member.a is already linked to member.b in DuskSeeder.
            $browser->visit('/member/relationships')->pause(300);
            $this->capturePlain($browser, 'spouse-2', 'member/profile-and-family/spouse-2.png');

            // children-1: expand the Add Child form.
            $browser->click('button[data-bs-target="#addChildForm"]')->pause(500);
            $this->capturePlain($browser, 'children-1', 'member/profile-and-family/children-1.png');

            $browser->driver->manage()->deleteAllCookies();

            // spouse-1: member.c is single in DuskSeeder — shows the search form instead.
            $this->loginAsMember($browser, 'member.c@test.com');
            $browser->visit('/member/relationships')->pause(300);
            $this->captureAnnotated($browser, 'spouse-1', 'member/profile-and-family/spouse-1.png', [
                '#spouseSearch',
            ]);
        });
    }

    public function test_capture_meetings(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            $admin = User::where('email', 'admin@test.com')->firstOrFail();

            // No venue_lat/venue_lng set — hasLocation() is false, so the
            // server accepts a check-in with no GPS coordinates at all,
            // matching the "no geolocation" client branch used below.
            $meeting = Meeting::create([
                'title'        => 'March 2026 General Meeting',
                'meeting_date' => now()->toDateString(),
                'meeting_time' => '18:00:00',
                'venue'        => 'ACM Community Hall',
                'status'       => 'active',
                'qr_token'     => Str::random(32),
                'created_by'   => $admin->id,
            ]);

            MeetingMinutes::create([
                'title'        => 'March 2026 General Meeting — Minutes',
                'meeting_date' => now()->toDateString(),
                'published_at' => now(),
                'status'       => 'published',
                'file_path'    => 'minutes/march-2026.pdf',
                'file_name'    => 'March 2026 Minutes.pdf',
                'created_by'   => $admin->id,
            ]);

            $this->loginAsMember($browser, 'member.a@test.com');

            // checkin-error: an invalid QR token.
            $browser->visit('/attend/not-a-real-token')->pause(300);
            $this->capturePlain($browser, 'checkin-error', 'member/meetings/checkin-error.png');

            // checkin-1: the identity confirmation screen.
            $browser->visit('/attend/' . $meeting->qr_token)->pause(300);
            $this->capturePlain($browser, 'checkin-1', 'member/meetings/checkin-1.png');

            // Stub getCurrentPosition to succeed immediately with fixed coords —
            // deterministic, no OS-level permission prompt to fight with in a
            // headless browser. The meeting has no venue_lat/venue_lng, so the
            // server ignores these coordinates entirely (hasLocation() is false).
            $browser->script("navigator.geolocation.getCurrentPosition = function(success) { success({ coords: { latitude: 51.5074, longitude: -0.1278 } }); };");
            $browser->click('#btn-yes');
            $browser->waitUntil('!document.getElementById("step-success").classList.contains("d-none")', 10);
            $browser->pause(300);

            // checkin-2: already-checked-in screen (reload the same link).
            $browser->visit('/attend/' . $meeting->qr_token)->pause(300);
            $this->capturePlain($browser, 'checkin-2', 'member/meetings/checkin-2.png');

            // attendance-1: now has one real attendance record.
            $browser->visit('/member/attendance')->pause(500);
            $this->capturePlain($browser, 'attendance-1', 'member/meetings/attendance-1.png');

            // minutes-1 / minutes-2.
            $browser->visit('/member/minutes')->pause(300);
            $this->capturePlain($browser, 'minutes-1', 'member/meetings/minutes-1.png');

            $browser->click('a[href*="/member/minutes/"]')->pause(300);
            $this->capturePlain($browser, 'minutes-2', 'member/meetings/minutes-2.png');
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
