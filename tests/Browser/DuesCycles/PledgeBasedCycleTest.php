<?php

namespace Tests\Browser\DuesCycles;

use App\Models\DuesCycle;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Tests the pledge management flow:
 * recording, updating (upsert), and deleting pledges via the admin UI.
 */
class PledgeBasedCycleTest extends DuskTestCase
{
    private function getPledgeCycle(): DuesCycle
    {
        return DuesCycle::where('title', 'Harvest Pledge 2026')->firstOrFail();
    }

    private function visitPledgesPage(Browser $browser): Browser
    {
        $cycle = $this->getPledgeCycle();
        $this->loginAsFS($browser);

        return $browser->visit("/admin/dues-cycles/{$cycle->id}/pledges");
    }

    public function test_manage_pledges_button_appears_on_pledge_cycle_show(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = $this->getPledgeCycle();
            $this->loginAsFS($browser);

            $browser->visit("/admin/dues-cycles/{$cycle->id}")
                    ->assertSee('Manage Pledges');
        });
    }

    public function test_fs_can_record_pledge_for_member(): void
    {
        $this->browse(function (Browser $browser) {
            $this->visitPledgesPage($browser)
                 // Search and select "Ada Dusk"
                 ->type('#memberSearch', 'Ada')
                 ->waitFor('#memberDropdown div[data-member-id]')
                 ->click('#memberDropdown div[data-member-id]')
                 ->type('input[name="pledged_amount"]', '75.00')
                 ->type('textarea[name="pledge_notes"]', 'Pledged at March meeting')
                 ->press('Save Contribution')
                 ->assertSee('Pledge recorded');
        });
    }

    public function test_recorded_pledge_appears_in_pledges_table(): void
    {
        $this->browse(function (Browser $browser) {
            $this->visitPledgesPage($browser)
                 ->type('#memberSearch', 'Ada')
                 ->waitFor('#memberDropdown div[data-member-id]')
                 ->click('#memberDropdown div[data-member-id]')
                 ->type('input[name="pledged_amount"]', '50.00')
                 ->press('Save Contribution')
                 ->assertSee('Ada Dusk')
                 ->assertSee('50.00');
        });
    }

    public function test_submitting_pledge_again_updates_existing_pledge(): void
    {
        $this->browse(function (Browser $browser) {
            // First pledge
            $this->visitPledgesPage($browser)
                 ->type('#memberSearch', 'Ada')
                 ->waitFor('#memberDropdown div[data-member-id]')
                 ->click('#memberDropdown div[data-member-id]')
                 ->type('input[name="pledged_amount"]', '40.00')
                 ->press('Save Contribution');

            // Update pledge with new amount
            $browser->type('#memberSearch', 'Ada')
                    ->waitFor('#memberDropdown div[data-member-id]')
                    ->click('#memberDropdown div[data-member-id]')
                    ->type('input[name="pledged_amount"]', '90.00')
                    ->press('Save Contribution')
                    ->assertSee('90.00')
                    ->assertDontSee('40.00');
        });
    }

    public function test_fs_can_delete_a_pledge(): void
    {
        $this->browse(function (Browser $browser) {
            // Create pledge first
            $this->visitPledgesPage($browser)
                 ->type('#memberSearch', 'Chi')
                 ->waitFor('#memberDropdown div[data-member-id]')
                 ->click('#memberDropdown div[data-member-id]')
                 ->type('input[name="pledged_amount"]', '30.00')
                 ->press('Save Contribution')
                 ->assertSee('Chi Dusk');

            // Delete it
            $browser->acceptDialog()  // Pre-accept the confirm() dialog
                    ->click('button.btn-outline-danger')
                    ->assertDontSee('Chi Dusk');
        });
    }
}
