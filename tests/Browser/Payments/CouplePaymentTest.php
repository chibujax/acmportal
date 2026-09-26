<?php

namespace Tests\Browser\Payments;

use App\Models\DuesCycle;
use App\Models\Payment;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Tests the couple/spouse payment flows on the admin manual-payment form:
 * - "Split with spouse" control visibility rules
 * - Splitting a payment creates two linked payment records with independent amounts
 * - Couple-shared cycles suppress the split control (they merge into one obligation instead)
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

    public function test_split_control_is_visible_for_married_member_on_standard_cycle(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $browser->select('#cycleSelect', $cycle->id)
                    ->waitUntilMissing('#couplePayWrap.d-none')
                    ->assertVisible('#splitWithSpouse')
                    ->assertSeeIn('#spouseNameHint', 'Ben Dusk');
        });
    }

    public function test_split_control_is_hidden_for_couple_shared_cycle(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Couple Dues 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $browser->select('#cycleSelect', $cycle->id)
                    ->pause(300) // let JS update
                    ->assertAttribute('#couplePayWrap', 'class', 'mb-3 d-none');
        });
    }

    public function test_split_control_is_hidden_for_single_member(): void
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

    public function test_split_payment_creates_two_linked_records_with_independent_amounts(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $browser->select('#cycleSelect', $cycle->id)
                    ->waitUntilMissing('#couplePayWrap.d-none')
                    ->check('#splitWithSpouse')
                    ->waitUntilMissing('#splitFields.d-none')
                    ->clear('#totalAmountInput')
                    ->type('#totalAmountInput', '100')
                    ->clear('#selfShareInput')
                    ->type('#selfShareInput', '40')
                    ->clear('#spouseAmountInput')
                    ->type('#spouseAmountInput', '60')
                    ->type('input[name="payment_date"]', '2026-02-28')
                    ->press('Record Payment')
                    ->assertSee('Payment recorded for member and spouse');
        });

        $payments = Payment::where('dues_cycle_id',
                        DuesCycle::where('title', 'Standard Levy 2026')->value('id')
                    )->orderBy('id')->get();

        $this->assertCount(2, $payments);
        $this->assertEqualsCanonicalizing([40.0, 60.0], $payments->pluck('amount')->map(fn ($a) => (float) $a)->all());
        $this->assertEquals($payments[0]->linked_payment_id, $payments[1]->id);
        $this->assertEquals($payments[1]->linked_payment_id, $payments[0]->id);
    }

    public function test_both_members_show_paid_on_cycle_show_after_split_payment(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $browser->select('#cycleSelect', $cycle->id)
                    ->waitUntilMissing('#couplePayWrap.d-none')
                    ->check('#splitWithSpouse')
                    ->waitUntilMissing('#splitFields.d-none')
                    ->type('input[name="payment_date"]', '2026-02-28')
                    ->press('Record Payment');

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
