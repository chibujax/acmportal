<?php

namespace Tests\Browser\Payments;

use App\Models\DuesCycle;
use App\Models\MemberPledge;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Tests the manual payment recording flow:
 * - Member search autocomplete
 * - Obligation hints for standard and pledge-based cycles
 * - Single payment submission and confirmation
 */
class ManualPaymentTest extends DuskTestCase
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

            $browser->assertVisible('#memberSelected')
                    ->assertSeeIn('#memberSelected', 'Ada Dusk');

            $memberId = User::where('email', 'member.a@test.com')->value('id');
            $browser->assertValue('#memberIdInput', (string) $memberId);
        });
    }

    public function test_obligation_hint_shows_for_standard_cycle(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $browser->select('#cycleSelect', $cycle->id)
                    ->waitUntilMissing('#obligationHint.d-none')
                    ->assertSeeIn('#obligationHint', 'Obligation')
                    ->assertSeeIn('#obligationHint', '60.00');
        });
    }

    public function test_pledge_hint_shows_for_pledge_based_cycle_with_no_pledge(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Harvest Pledge 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $browser->select('#cycleSelect', $cycle->id)
                    ->waitUntilMissing('#pledgeHint.d-none')
                    ->assertSeeIn('#pledgeHint', 'No pledge recorded');
        });
    }

    public function test_pledge_hint_shows_pledge_amount_when_pledge_exists(): void
    {
        // Seed a pledge for Member A on the Harvest cycle
        $cycle  = DuesCycle::where('title', 'Harvest Pledge 2026')->firstOrFail();
        $member = User::where('email', 'member.a@test.com')->firstOrFail();
        $admin  = User::where('email', 'admin@test.com')->firstOrFail();

        MemberPledge::create([
            'user_id'        => $member->id,
            'dues_cycle_id'  => $cycle->id,
            'pledged_amount' => 55.00,
            'currency'       => 'GBP',
            'recorded_by'    => $admin->id,
        ]);

        $this->browse(function (Browser $browser) use ($cycle) {
            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $browser->select('#cycleSelect', $cycle->id)
                    ->waitUntilMissing('#pledgeHint.d-none')
                    ->assertSeeIn('#pledgeHint', 'Pledge on record')
                    ->assertSeeIn('#pledgeHint', '55.00');
        });
    }

    public function test_amount_field_is_prefilled_from_obligation(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Chi'); // Single member — full £60

            $browser->select('#cycleSelect', $cycle->id)
                    ->pause(300)
                    ->assertValue('input[name="amount"]', '60.00');
        });
    }

    public function test_fs_can_record_single_payment_successfully(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Chi');

            $browser->select('#cycleSelect', $cycle->id)
                    ->pause(300)
                    ->type('input[name="payment_date"]', '2026-02-28')
                    ->type('textarea[name="notes"]', 'Cash received at meeting')
                    ->press('Record Payment')
                    ->assertSee('Payment recorded successfully');
        });
    }

    public function test_payment_appears_in_payment_index_after_recording(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = DuesCycle::where('title', 'Standard Levy 2026')->firstOrFail();

            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Chi');

            $browser->select('#cycleSelect', $cycle->id)
                    ->pause(300)
                    ->type('input[name="payment_date"]', '2026-02-28')
                    ->press('Record Payment');

            $browser->visit('/admin/payments')
                    ->assertSee('Chi Dusk');
        });
    }

    public function test_clear_button_resets_member_selection(): void
    {
        $this->browse(function (Browser $browser) {
            $this->visitPaymentCreate($browser);
            $this->selectMember($browser, 'Ada');

            $browser->assertVisible('#memberSelected')
                    ->click('#memberClear')
                    ->assertVisible('#memberSearch')
                    ->assertValue('#memberIdInput', '');
        });
    }
}
