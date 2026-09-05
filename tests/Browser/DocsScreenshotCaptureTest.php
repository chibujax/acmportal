<?php

namespace Tests\Browser;

use App\Models\PendingMember;
use App\Models\User;
use App\Support\Docs\ScreenshotAnnotator;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Not a correctness test — a screenshot-generation script for the docs site,
 * run manually via:
 *
 *   php artisan dusk --filter=DocsScreenshotCaptureTest
 *
 * Each method drives one screen, takes a raw screenshot via Dusk, then hands
 * it to ScreenshotAnnotator to burn in numbered callouts before saving into
 * public/docs-assets/images/... at the exact path the matching docs article
 * expects.
 *
 * Every method starts by clearing cookies — a guest-only page (activation,
 * the OTP screens) silently redirects to the dashboard if the browser is
 * still authenticated from an earlier step, which is exactly what bit the
 * first version of this script.
 *
 * Steps that would normally send a real SMS/email are still safe to trigger
 * here: SmsService no-ops with a log warning when Vonage isn't configured,
 * and .env.dusk.local sets MAIL_MAILER=log, so nothing actually goes out —
 * but the reset-password form itself doesn't need a real token at all
 * (PasswordResetController::showResetForm only checks the token/email query
 * params are present, not that they're valid), so that one's constructed
 * directly instead.
 */
class DocsScreenshotCaptureTest extends DuskTestCase
{
    public function test_capture_member_getting_started(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            // signing-in-1: the empty sign-in form, phone/email + password fields called out.
            $browser->visit('/login');
            $this->captureAnnotated($browser, 'signing-in-1', 'member/getting-started/signing-in-1.png', [
                'input[name="login"]',
                'input[name="password"]',
            ]);

            // signing-in-2: dashboard after a real sign-in.
            $this->loginAsMember($browser, 'member.a@test.com');
            $browser->visit('/member/dashboard')->pause(500);
            $this->capturePlain($browser, 'signing-in-2', 'member/getting-started/signing-in-2.png');

            $browser->driver->manage()->deleteAllCookies();

            // joining-1: the Join lookup form.
            $browser->visit('/join');
            $this->captureAnnotated($browser, 'joining-1', 'member/getting-started/joining-1.png', [
                'input[name="identifier"]',
            ]);

            // joining-2: a real lookup submission landing on the actual OTP screen.
            // Giving the pending member an email routes the OTP through Mail::raw
            // (safe — MAIL_MAILER=log here) instead of live SMS; SelfRegisterController
            // hard-fails the lookup back to the form if sending returns false, so the
            // phone-only path isn't usable for a screenshot without real SMS credentials.
            PendingMember::create([
                'name'   => 'Pending Dusk Member',
                'email'  => 'pending.otp.dusk@test.com',
                'phone'  => '07900008888',
                'status' => 'pending',
            ]);
            $browser->type('input[name="identifier"]', 'pending.otp.dusk@test.com')
                    ->press('Send Verification Code')
                    ->waitForLocation('/join/verify');
            $this->captureAnnotated($browser, 'joining-2', 'member/getting-started/joining-2.png', [
                'input[name="otp"]',
            ]);
        });
    }

    public function test_capture_activation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            $pending = User::create([
                'name'                        => 'Pending Dusk Member',
                'email'                       => 'pending.dusk@test.com',
                'phone'                       => '07900009999',
                'password'                    => bcrypt(Str::random(20)),
                'role'                        => 'member',
                'status'                      => 'active',
                'activation_token'            => Str::random(40),
                'activation_token_expires_at' => now()->addDays(7),
            ]);

            // activate-1: valid activation form, pre-filled with the member's name.
            $browser->visit('/activate/' . $pending->activation_token);
            $this->capturePlain($browser, 'activate-1', 'member/getting-started/activate-1.png');

            $browser->driver->manage()->deleteAllCookies();

            // activate-expired: an invalid/garbage token.
            $browser->visit('/activate/not-a-real-token');
            $this->capturePlain($browser, 'activate-expired', 'member/getting-started/activate-expired.png');
        });
    }

    public function test_capture_forgot_password(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            // forgot-1: the identifier form.
            $browser->visit('/forgot-password');
            $this->captureAnnotated($browser, 'forgot-1', 'member/getting-started/forgot-1.png', [
                'input[name="identifier"]',
            ]);

            // forgot-2: the Set New Password screen — reachable with any token/email
            // pair in the query string, since the controller only validates the
            // token for real on submission, not on display.
            $browser->driver->manage()->deleteAllCookies();
            $browser->visit('/reset-password?token=preview-token&email=member.a%40test.com');
            $this->captureAnnotated($browser, 'forgot-2', 'member/getting-started/forgot-2.png', [
                'input[name="password"]',
                'input[name="password_confirmation"]',
            ]);
        });
    }

    public function test_capture_verifying_email(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            // verify-email-banner: dashboard for a signed-in member whose email
            // isn't verified yet (member.a is seeded without email_verified_at).
            $this->loginAsMember($browser, 'member.a@test.com');
            $browser->visit('/member/dashboard')->pause(500);
            $this->capturePlain($browser, 'verify-email-banner', 'member/getting-started/verify-email-banner.png');

            $browser->driver->manage()->deleteAllCookies();

            // verify-email-failed: an invalid/expired verification link.
            $browser->visit('/email/verify/not-a-real-token');
            $this->capturePlain($browser, 'verify-email-failed', 'member/getting-started/verify-email-failed.png');
        });
    }

    public function test_capture_admin_member_management(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();
            $this->loginAsAdmin($browser);

            // directory-1: member list with filters.
            $browser->visit('/admin/members')->pause(500);
            $this->captureAnnotated($browser, 'directory-1', 'admin/member-management/directory-1.png', [
                'input[name="search"]',
            ]);

            // directory-2: an individual member's detail page.
            $member = User::where('email', 'member.a@test.com')->firstOrFail();
            $browser->visit('/admin/members/' . $member->id)->pause(500);
            $this->capturePlain($browser, 'directory-2', 'admin/member-management/directory-2.png');

            // import-1: the CSV upload form.
            $browser->visit('/admin/import')->pause(500);
            $this->captureAnnotated($browser, 'import-1', 'admin/member-management/import-1.png', [
                'input[name="csv_file"]',
            ]);

            // import-2: the import batches table — needs pending_members sharing an import_batch.
            $batch = 'BATCH-' . now()->format('YmdHis');
            \App\Models\PendingMember::create(['name' => 'Uche Import', 'phone' => '07900001111', 'email' => 'uche.import@test.com', 'status' => 'pending', 'import_batch' => $batch]);
            \App\Models\PendingMember::create(['name' => 'Nkem Import', 'phone' => '07900001112', 'status' => 'invited', 'import_batch' => $batch, 'invited_at' => now()]);
            $browser->visit('/admin/import')->pause(300);
            $this->capturePlain($browser, 'import-2', 'admin/member-management/import-2.png');

            // import-3: pending invites list — now has real rows from the batch above.
            $browser->visit('/admin/pending')->pause(500);
            $this->capturePlain($browser, 'import-3', 'admin/member-management/import-3.png');
        });
    }

    /**
     * Screenshot the current browser state, draw numbered callout badges over
     * the given CSS selectors (in order), and save into public/docs-assets/images/.
     */
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

    /** Screenshot the current browser state with no callouts. */
    private function capturePlain(Browser $browser, string $rawName, string $destRelativePath): void
    {
        $browser->screenshot($rawName);

        ScreenshotAnnotator::copyPlain(
            base_path("tests/Browser/screenshots/{$rawName}.png"),
            public_path("docs-assets/images/{$destRelativePath}")
        );
    }
}
