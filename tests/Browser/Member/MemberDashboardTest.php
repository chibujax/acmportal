<?php

namespace Tests\Browser\Member;

use App\Models\DuesCycle;
use App\Models\MemberPledge;
use App\Models\Payment;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Tests the member-facing dashboard:
 * - Active cycles card display
 * - Correct obligation amounts (standard, couple-shared)
 * - Pledge-based cycle messaging (no pledge vs pledge recorded)
 * - "Fully Paid" badge after payment covers obligation
 * - Payment buttons visibility
 */
class MemberDashboardTest extends DuskTestCase
{
    public function test_member_sees_welcome_message_after_login(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsMember($browser, 'member.a@test.com')
                 ->visit('/member/dashboard')
                 ->assertSee('Welcome back, Ada Dusk');
        });
    }

    public function test_active_cycles_appear_on_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsMember($browser, 'member.a@test.com')
                 ->visit('/member/dashboard')
                 ->assertSee('Standard Levy 2026')
                 ->assertSee('Couple Dues 2026');
        });
    }

    public function test_standard_cycle_shows_obligation_amount_for_member(): void
    {
        $this->browse(function (Browser $browser) {
            // Chi is single → Standard Levy obligation = £60
            $this->loginAsMember($browser, 'member.c@test.com')
                 ->visit('/member/dashboard')
                 ->assertSee('Standard Levy 2026')
                 ->assertSee('£0.00')     // paid so far
                 ->assertSee('£60.00');   // obligation
        });
    }

    public function test_couple_shared_cycle_shows_couple_rate_for_married_member(): void
    {
        $this->browse(function (Browser $browser) {
            // Ada has spouse → Couple Dues obligation = £120 (couple rate)
            $this->loginAsMember($browser, 'member.a@test.com')
                 ->visit('/member/dashboard')
                 ->assertSee('Couple Dues 2026')
                 ->assertSee('£120.00');
        });
    }

    public function test_couple_shared_cycle_shows_single_rate_for_member_without_spouse(): void
    {
        $this->browse(function (Browser $browser) {
            // Chi has no spouse → Couple Dues obligation = £60 (half)
            $this->loginAsMember($browser, 'member.c@test.com')
                 ->visit('/member/dashboard')
                 ->assertSee('Couple Dues 2026')
                 ->assertSee('£60.00');
        });
    }

    public function test_pledge_based_cycle_shows_no_pledge_message_when_none_recorded(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsMember($browser, 'member.a@test.com')
                 ->visit('/member/dashboard')
                 ->assertSee('No pledge recorded yet');
        });
    }

    public function test_pledge_based_cycle_shows_pledge_amount_when_pledge_exists(): void
    {
        $cycle  = DuesCycle::where('title', 'Harvest Pledge 2026')->firstOrFail();
        $member = User::where('email', 'member.a@test.com')->firstOrFail();
        $admin  = User::where('email', 'admin@test.com')->firstOrFail();

        MemberPledge::create([
            'user_id'        => $member->id,
            'dues_cycle_id'  => $cycle->id,
            'pledged_amount' => 100.00,
            'currency'       => 'GBP',
            'recorded_by'    => $admin->id,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginAsMember($browser, 'member.a@test.com')
                 ->visit('/member/dashboard')
                 ->assertSee('Pledge:')
                 ->assertSee('£100.00');
        });
    }

    public function test_pay_by_card_button_appears_when_not_fully_paid(): void
    {
        $this->browse(function (Browser $browser) {
            // Standard cycle — Ada owes £60 and hasn't paid
            $this->loginAsMember($browser, 'member.a@test.com')
                 ->visit('/member/dashboard')
                 ->assertSee('Pay by Card');
        });
    }

    public function test_fully_paid_badge_appears_after_payment_covers_obligation(): void
    {
        $member      = User::where('email', 'member.c@test.com')->firstOrFail();
        $admin       = User::where('email', 'admin@test.com')->firstOrFail();
        $standardCycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();
        $coupleCycle   = DuesCycle::where('title', 'Couple Dues 2026')->firstOrFail();

        // Chi is single — both cycles have a £60 obligation. Pay both so no "Pay by Card" appears anywhere.
        foreach ([$standardCycle, $coupleCycle] as $cycle) {
            Payment::create([
                'user_id'        => $member->id,
                'dues_cycle_id'  => $cycle->id,
                'amount'         => 60.00,
                'currency'       => 'GBP',
                'method'         => 'manual',
                'status'         => 'completed',
                'recorded_by'    => $admin->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'payment_date'   => '2026-02-01',
            ]);
        }

        $this->browse(function (Browser $browser) {
            $this->loginAsMember($browser, 'member.c@test.com')
                 ->visit('/member/dashboard')
                 ->assertSee('Fully Paid')
                 ->assertDontSee('Pay by Card');
        });
    }

    public function test_stat_cards_show_correct_total_paid(): void
    {
        $cycle  = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();
        $member = User::where('email', 'member.c@test.com')->firstOrFail();
        $admin  = User::where('email', 'admin@test.com')->firstOrFail();

        Payment::create([
            'user_id'        => $member->id,
            'dues_cycle_id'  => $cycle->id,
            'amount'         => 60.00,
            'currency'       => 'GBP',
            'method'         => 'manual',
            'status'         => 'completed',
            'recorded_by'    => $admin->id,
            'receipt_number' => Payment::generateReceiptNumber(),
            'payment_date'   => '2026-02-01',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginAsMember($browser, 'member.c@test.com')
                 ->visit('/member/dashboard')
                 ->assertSee('Total Paid')
                 ->assertSee('£60.00');
        });
    }

    public function test_recent_payments_section_shows_payment_after_recording(): void
    {
        $cycle  = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();
        $member = User::where('email', 'member.a@test.com')->firstOrFail();
        $admin  = User::where('email', 'admin@test.com')->firstOrFail();

        Payment::create([
            'user_id'        => $member->id,
            'dues_cycle_id'  => $cycle->id,
            'amount'         => 60.00,
            'currency'       => 'GBP',
            'method'         => 'manual',
            'status'         => 'completed',
            'recorded_by'    => $admin->id,
            'receipt_number' => Payment::generateReceiptNumber(),
            'payment_date'   => '2026-02-28',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginAsMember($browser, 'member.a@test.com')
                 ->visit('/member/dashboard')
                 ->assertSee('Recent Payments')
                 ->assertSee('Standard Levy 2026');
        });
    }
}
