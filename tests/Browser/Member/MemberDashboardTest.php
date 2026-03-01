<?php

namespace Tests\Browser\Member;

use App\Models\DuesCycle;
use App\Models\Payment;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Member-facing tests that catch regressions in what members see:
 * - Owed/paid cards
 * - Fully paid badge
 * - Payment history page
 */
class MemberDashboardTest extends DuskTestCase
{
    public function test_member_sees_welcome_message_after_login(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsMember($browser, 'member.a@test.com');

            $browser->visit('/member/dashboard')
                    ->assertSee('Welcome back, Ada Dusk');
        });
    }

    public function test_member_sees_amounts_owed_and_paid_after_a_payment(): void
    {
        $this->browse(function (Browser $browser) {
            $member = User::where('email', 'member.a@test.com')->firstOrFail();
            $cycle  = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            // Create a completed payment directly (faster + stable),
            // then verify the member UI reflects it.
            Payment::create([
                'user_id'       => $member->id,
                'dues_cycle_id' => $cycle->id,
                'amount'        => 60.00,
                'currency'      => 'GBP',
                'method'        => 'manual',
                'status'        => 'completed',
                'payment_date'  => '2026-03-01',
                'recorded_by'   => User::where('email', 'finsec@test.com')->value('id'),
            ]);

            $this->loginAsMember($browser, 'member.a@test.com');
            $browser->visit('/member/dashboard')
                    ->assertSee('Standard Levy 2026')
                    ->assertSee('Fully Paid');
        });
    }

    public function test_member_payment_history_shows_recorded_payments(): void
    {
        $this->browse(function (Browser $browser) {
            $member = User::where('email', 'member.c@test.com')->firstOrFail();
            $cycle  = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            Payment::create([
                'user_id'       => $member->id,
                'dues_cycle_id' => $cycle->id,
                'amount'        => 15.50,
                'currency'      => 'GBP',
                'method'        => 'manual',
                'status'        => 'completed',
                'payment_date'  => '2026-03-01',
                'recorded_by'   => User::where('email', 'finsec@test.com')->value('id'),
            ]);

            $this->loginAsMember($browser, 'member.c@test.com');
            $browser->visit('/member/payments')
                    ->assertSee('My Payment History')
                    ->assertSee('Standard Levy 2026')
                    ->assertSee('15.50');
        });
    }
}
