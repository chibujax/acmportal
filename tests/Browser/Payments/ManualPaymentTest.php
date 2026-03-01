<?php

namespace Tests\Browser\Payments;

use App\Models\DuesCycle;
use App\Models\Payment;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Manual payment UI tests.
 * These are intentionally outcome-focused (DB + member UI) to catch real regressions.
 */
class ManualPaymentTest extends DuskTestCase
{
    private function visitPaymentCreate(Browser $browser): Browser
    {
        $this->loginAsFS($browser);

        return $browser->visit('/admin/payments/create')
                       ->assertSee('Record Manual Payment');
    }

    private function selectMember(Browser $browser, string $namePrefix): Browser
    {
        return $browser->type('#memberSearch', $namePrefix)
                       ->waitFor('#memberSuggestions a')
                       ->click('#memberSuggestions a')
                       ->assertVisible('#memberSelected');
    }

    public function test_member_search_autocomplete_shows_suggestions(): void
    {
        $this->browse(function (Browser $browser) {
            $this->visitPaymentCreate($browser)
                 ->type('#memberSearch', 'Ada')
                 ->waitFor('#memberSuggestions a')
                 ->assertSeeIn('#memberSuggestions', 'Ada Dusk');
        });
    }

    public function test_selecting_member_from_suggestions_populates_hidden_id_field(): void
    {
        $this->browse(function (Browser $browser) {
            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $memberId = User::where('email', 'member.a@test.com')->value('id');
            $browser->assertValue('#memberIdInput', (string) $memberId);
        });
    }

    public function test_finsec_can_record_manual_payment_and_member_dashboard_updates(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();
            $member = User::where('email', 'member.a@test.com')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $browser->select('#cycleSelect', (string) $cycle->id)
                    ->type('#amountInput', '60.00')
                    ->type('input[name="payment_date"]', '2026-03-01')
                    ->press('Record Payment')
                    ->waitForLocation('/admin/payments/manual')
                    ->assertSee('Payment recorded')
                    ->assertSee('Ada Dusk');

            // DB proof
            $this->assertDatabaseHas('payments', [
                'user_id'       => $member->id,
                'dues_cycle_id' => $cycle->id,
                'method'        => 'manual',
                'status'        => 'completed',
            ]);

            // Member UI reflects fully paid
            $this->loginAsMember($browser, 'member.a@test.com');
            $browser->visit('/member/dashboard')
                    ->assertSee('Standard Levy 2026')
                    ->assertSee('Fully Paid');
        });
    }

    public function test_overpayment_is_recorded_and_still_marks_cycle_as_fully_paid(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();
            $member = User::where('email', 'member.c@test.com')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Chi');

            // Obligation is £60 (single member), but we record £100
            $browser->select('#cycleSelect', (string) $cycle->id)
                    ->type('#amountInput', '100.00')
                    ->type('input[name="payment_date"]', '2026-03-01')
                    ->press('Record Payment')
                    ->waitForLocation('/admin/payments/manual')
                    ->assertSee('Payment recorded')
                    ->assertSee('Chi Dusk');

            $payment = Payment::where('user_id', $member->id)
                ->where('dues_cycle_id', $cycle->id)
                ->latest()
                ->first();

            $this->assertNotNull($payment);
            $this->assertEquals(100.0, (float) $payment->amount);

            // Member sees Fully Paid regardless of overpayment
            $this->loginAsMember($browser, 'member.c@test.com');
            $browser->visit('/member/dashboard')
                    ->assertSee('Standard Levy 2026')
                    ->assertSee('Fully Paid');

            // Payment history includes the recorded amount
            $browser->visit('/member/payments')
                    ->assertSee('My Payment History')
                    ->assertSee('Standard Levy 2026')
                    ->assertSee('100.00');
        });
    }
}
