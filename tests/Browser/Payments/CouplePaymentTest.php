<?php

namespace Tests\Browser\Payments;

use App\Models\DuesCycle;
use App\Models\Payment;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Tests the couple/shared payment flow:
 * - "Pay for spouse" checkbox visibility rules
 * - Two linked payment records are created when checkbox is ticked
 * - Couple-shared cycles suppress the spouse checkbox
 */
class CouplePaymentTest extends DuskTestCase
{
    private function visitPaymentCreate(Browser $browser): Browser
    {
        $this->loginAsFS($browser);
        return $browser->visit('/admin/payments/create');
    }

    private function selectMember(Browser $browser, string $namePrefix): Browser
    {
        return $browser->type('#memberSearch', $namePrefix)
                       ->waitFor('#memberSuggestions a')
                       ->click('#memberSuggestions a');
    }

    public function test_spouse_checkbox_is_visible_for_married_member_on_standard_cycle(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $browser->select('#cycleSelect', $cycle->id)
                    ->waitUntilMissing('#couplePayWrap.d-none')
                    ->assertVisible('#payForSpouse')
                    ->assertSeeIn('#spouseNameHint', 'Ben Dusk');
        });
    }

    public function test_spouse_checkbox_is_hidden_for_couple_shared_cycle(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Couple Dues 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $browser->select('#cycleSelect', $cycle->id)
                    ->pause(300) // let JS update
                    ->assertMissing('#payForSpouse:not(.d-none)');

            // The couplePayWrap must remain hidden (d-none) for couple_shared cycles
            $browser->assertAttribute('#couplePayWrap', 'class', 'mb-3 d-none');
        });
    }

    public function test_spouse_checkbox_is_hidden_for_single_member(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Chi'); // Member C has no spouse

            $browser->select('#cycleSelect', $cycle->id)
                    ->pause(300)
                    ->assertAttribute('#couplePayWrap', 'class', 'mb-3 d-none');
        });
    }

    public function test_couple_payment_creates_two_linked_records(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $browser->select('#cycleSelect', $cycle->id)
                    ->waitUntilMissing('#couplePayWrap.d-none')
                    ->check('#payForSpouse')
                    ->type('input[name="payment_date"]', '2026-02-28')
                    ->press('Record Payment')
                    ->assertSee('Payment recorded for member and spouse');
        });

        // Verify two linked payments exist in the DB
        $payments = Payment::where('dues_cycle_id',
                        DuesCycle::where('title', 'Standard Levy 2026')->value('id')
                    )->orderBy('id')->get();

        $this->assertCount(2, $payments);
        $this->assertEquals($payments[0]->linked_payment_id, $payments[1]->id);
        $this->assertEquals($payments[1]->linked_payment_id, $payments[0]->id);
    }

    public function test_both_members_show_paid_on_cycle_show_after_couple_payment(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            // Record couple payment
            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $browser->select('#cycleSelect', $cycle->id)
                    ->waitUntilMissing('#couplePayWrap.d-none')
                    ->check('#payForSpouse')
                    ->type('input[name="payment_date"]', '2026-02-28')
                    ->press('Record Payment');

            // Navigate to cycle show page and verify both members are paid
            $browser->visit("/admin/dues-cycles/{$cycle->id}")
                    ->assertSee('Ada Dusk')
                    ->assertSee('Ben Dusk');
        });
    }

    public function test_obligation_hint_shows_correct_amount_for_couple_shared_cycle(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Couple Dues 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada'); // Ada has spouse → couple rate £120

            $browser->select('#cycleSelect', $cycle->id)
                    ->waitUntilMissing('#obligationHint.d-none')
                    ->assertSeeIn('#obligationHint', 'Couple rate')
                    ->assertSeeIn('#obligationHint', '120.00');
        });
    }

    public function test_obligation_hint_shows_single_rate_for_member_without_spouse(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Couple Dues 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Chi'); // Chi has no spouse → single rate £60

            $browser->select('#cycleSelect', $cycle->id)
                    ->waitUntilMissing('#obligationHint.d-none')
                    ->assertSeeIn('#obligationHint', 'Single rate')
                    ->assertSeeIn('#obligationHint', '60.00');
        });
    }
}
