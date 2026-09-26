<?php

namespace Tests\Browser\DuesCycles;

use App\Models\DuesCycle;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Tests creating dues cycles via the admin UI.
 * Keep these tests outcome-focused + DB-backed to avoid flaky UI assertions.
 */
class CreateDuesCycleTest extends DuskTestCase
{
    private function visitCreateForm(Browser $browser): Browser
    {
        $this->loginAsAdmin($browser);

        return $browser->visit('/admin/dues-cycles/create')
                       ->assertSee('Create Dues Cycle');
    }

    public function test_admin_can_create_standard_dues_cycle(): void
    {
        $this->browse(function (Browser $browser) {
            $title = 'Test Event Levy 2026';

            $this->visitCreateForm($browser)
                 ->type('input[name="title"]', $title)
                 ->select('select[name="type"]', 'event_levy')
                 ->type('#amountInput', '30')
                 ->type('input[name="start_date"]', '01-03-2026')
                 ->type('input[name="end_date"]', '31-03-2026')
                 ->select('select[name="status"]', 'active')
                 ->press('Create Cycle');

            // Controller redirects to index
            $browser->waitForLocation('/admin/dues-cycles')
                    ->assertSee('Dues cycle created')
                    ->assertSee($title);

            $cycle = DuesCycle::where('title', $title)->first();
            $this->assertNotNull($cycle);
            $this->assertSame('event_levy', $cycle->type);
            $this->assertSame('active', $cycle->status);
            $this->assertEquals(30.0, (float) $cycle->amount);
        });
    }

    public function test_couple_shared_cycle_can_be_created(): void
    {
        $this->browse(function (Browser $browser) {
            $title = 'Couple Test Dues';

            $this->visitCreateForm($browser)
                 ->type('input[name="title"]', $title)
                 ->type('#amountInput', '80')
                 ->type('input[name="start_date"]', '01-05-2026')
                 ->type('input[name="end_date"]', '31-05-2026')
                 ->select('select[name="status"]', 'active')
                 ->check('#coupleShared')
                 ->press('Create Cycle');

            $browser->waitForLocation('/admin/dues-cycles')
                    ->assertSee('Dues cycle created')
                    ->assertSee($title);

            $cycle = DuesCycle::where('title', $title)->firstOrFail();
            $this->assertTrue((bool) $cycle->couple_shared);

            // Visit show page to confirm badge exists
            $browser->visit("/admin/dues-cycles/{$cycle->id}")
                    ->assertSee('Couple shared');
        });
    }

    public function test_pledge_based_toggle_hides_amount_field_and_saves_amount_as_zero(): void
    {
        $this->browse(function (Browser $browser) {
            $title = 'Christmas Pledge 2026';

            $this->visitCreateForm($browser)
                 ->type('input[name="title"]', $title)
                 ->select('select[name="type"]', 'donation')
                 ->check('#isPledgeBased');

            // The amount field stays in the DOM but is hidden via inline style.
            $browser->assertPresent('#amountField')
                    ->assertScript("return document.getElementById('amountField').style.display === 'none';");

            $browser->type('input[name="start_date"]', '01-11-2026')
                    ->type('input[name="end_date"]', '31-12-2026')
                    ->select('select[name="status"]', 'active')
                    ->press('Create Cycle')
                    ->waitForLocation('/admin/dues-cycles')
                    ->assertSee('Dues cycle created')
                    ->assertSee($title);

            $cycle = DuesCycle::where('title', $title)->firstOrFail();
            $this->assertTrue((bool) $cycle->is_pledge_based);
            // Controller normalizes pledge-based cycles to 0
            $this->assertEquals(0.0, (float) $cycle->amount);

            $browser->visit("/admin/dues-cycles/{$cycle->id}")
                    ->assertSee('Manage Pledges');
        });
    }

    public function test_accepts_items_cycle_can_be_created(): void
    {
        $this->browse(function (Browser $browser) {
            $title = 'Harvest Donations Test';

            $this->visitCreateForm($browser)
                 ->type('input[name="title"]', $title)
                 ->select('select[name="type"]', 'donation')
                 ->type('#amountInput', '0.01')
                 ->type('input[name="start_date"]', '01-10-2026')
                 ->type('input[name="end_date"]', '31-10-2026')
                 ->select('select[name="status"]', 'active')
                 ->check('#acceptsItems')
                 ->press('Create Cycle');

            $browser->waitForLocation('/admin/dues-cycles')
                    ->assertSee('Dues cycle created')
                    ->assertSee($title);

            $cycle = DuesCycle::where('title', $title)->firstOrFail();
            $this->assertTrue((bool) $cycle->accepts_items);

            $browser->visit("/admin/dues-cycles/{$cycle->id}")
                    ->assertSee('item donations');
        });
    }

    public function test_cycle_create_does_not_submit_when_required_fields_missing(): void
    {
        $this->browse(function (Browser $browser) {
            $this->visitCreateForm($browser)
                 ->press('Create Cycle');

            // HTML5 required fields prevent submission, so we remain on the create form.
            $browser->assertPathIs('/admin/dues-cycles/create')
                    ->assertDontSee('Dues cycle created');
        });
    }
}
