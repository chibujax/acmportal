<?php

namespace Tests\Browser\DuesCycles;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Tests creating dues cycles via the admin UI.
 * Covers standard, couple-shared, pledge-based, and items-accepting cycles.
 */
class CreateDuesCycleTest extends DuskTestCase
{
    private function visitCreateForm(Browser $browser): Browser
    {
        $this->loginAsAdmin($browser);
        return $browser->visit('/admin/dues-cycles/create');
    }

    public function test_admin_can_create_standard_dues_cycle(): void
    {
        $this->browse(function (Browser $browser) {
            $this->visitCreateForm($browser)
                 ->type('input[name="title"]', 'Test Event Levy 2026')
                 ->select('select[name="type"]', 'event_levy')
                 ->type('#amountInput', '30')
                 ->type('input[name="start_date"]', '2026-03-01')
                 ->type('input[name="end_date"]', '2026-03-31')
                 ->select('select[name="status"]', 'active')
                 ->press('Save')
                 ->assertSee('Dues cycle created');
        });
    }

    public function test_new_cycle_appears_in_index_list(): void
    {
        $this->browse(function (Browser $browser) {
            $this->visitCreateForm($browser)
                 ->type('input[name="title"]', 'Index Check Cycle')
                 ->type('#amountInput', '50')
                 ->type('input[name="start_date"]', '2026-04-01')
                 ->type('input[name="end_date"]', '2026-04-30')
                 ->select('select[name="status"]', 'active')
                 ->press('Save')
                 ->assertPathContains('/admin/dues-cycles')
                 ->assertSee('Index Check Cycle');
        });
    }

    public function test_couple_shared_cycle_can_be_created(): void
    {
        $this->browse(function (Browser $browser) {
            $this->visitCreateForm($browser)
                 ->type('input[name="title"]', 'Couple Test Dues')
                 ->type('#amountInput', '80')
                 ->type('input[name="start_date"]', '2026-05-01')
                 ->type('input[name="end_date"]', '2026-05-31')
                 ->select('select[name="status"]', 'active')
                 ->check('#coupleShared')
                 ->press('Save')
                 ->assertSee('Dues cycle created');

            // Verify the "Couple shared" badge appears on the show page
            $browser->assertSee('Couple shared');
        });
    }

    public function test_pledge_based_checkbox_hides_amount_field(): void
    {
        $this->browse(function (Browser $browser) {
            $this->visitCreateForm($browser)
                 ->check('#isPledgeBased')
                 ->assertMissing('#amountField');
        });
    }

    public function test_pledge_based_cycle_can_be_created(): void
    {
        $this->browse(function (Browser $browser) {
            $this->visitCreateForm($browser)
                 ->type('input[name="title"]', 'Christmas Pledge 2026')
                 ->select('select[name="type"]', 'donation')
                 ->check('#isPledgeBased')
                 ->type('input[name="start_date"]', '2026-11-01')
                 ->type('input[name="end_date"]', '2026-12-31')
                 ->select('select[name="status"]', 'active')
                 ->press('Save')
                 ->assertSee('Dues cycle created');

            // Show page should have "Manage Pledges" button
            $browser->assertSee('Manage Pledges');
        });
    }

    public function test_accepts_items_cycle_can_be_created(): void
    {
        $this->browse(function (Browser $browser) {
            $this->visitCreateForm($browser)
                 ->type('input[name="title"]', 'Harvest Donations Test')
                 ->select('select[name="type"]', 'donation')
                 ->type('#amountInput', '0.01')
                 ->type('input[name="start_date"]', '2026-10-01')
                 ->type('input[name="end_date"]', '2026-10-31')
                 ->select('select[name="status"]', 'active')
                 ->check('#acceptsItems')
                 ->press('Save')
                 ->assertSee('Dues cycle created');

            // Show page should have "Accepts item donations" badge
            $browser->assertSee('item donations');
        });
    }

    public function test_cycle_create_fails_without_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $this->visitCreateForm($browser)
                 ->press('Save')
                 ->assertPathContains('/admin/dues-cycles');

            // HTML5 validation or server validation prevents submission
            // Either way the user stays on the create/form page
            $browser->assertDontSee('Dues cycle created');
        });
    }
}
