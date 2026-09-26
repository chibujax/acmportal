<?php

namespace Tests\Browser\DuesCycles;

use App\Models\DuesCycle;
use App\Models\MemberPledge;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Pledge management E2E tests for pledge-based cycles.
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

        return $browser->visit("/admin/dues-cycles/{$cycle->id}/pledges")
                       ->assertSee('Pledges');
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
            $cycle = $this->getPledgeCycle();
            $member = User::where('email', 'member.a@test.com')->firstOrFail();

            $this->visitPledgesPage($browser)
                 ->type('#memberSearch', 'Ada')
                 ->waitFor('#memberDropdown button[data-id]')
                 ->click('#memberDropdown button[data-id]')
                 ->type('input[name="pledged_amount"]', '75.00')
                 ->type('textarea[name="pledge_notes"]', 'Pledged at March meeting')
                 ->press('Save Contribution')
                 ->assertSee('Pledge recorded');

            $pledge = MemberPledge::where('dues_cycle_id', $cycle->id)
                ->where('user_id', $member->id)
                ->first();

            $this->assertNotNull($pledge);
            $this->assertEquals(75.0, (float) $pledge->pledged_amount);
        });
    }

    public function test_submitting_pledge_again_updates_existing_pledge_amount(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = $this->getPledgeCycle();
            $member = User::where('email', 'member.a@test.com')->firstOrFail();

            $this->visitPledgesPage($browser)
                 ->type('#memberSearch', 'Ada')
                 ->waitFor('#memberDropdown button[data-id]')
                 ->click('#memberDropdown button[data-id]')
                 ->type('input[name="pledged_amount"]', '40.00')
                 ->press('Save Contribution')
                 ->assertSee('40.00');

            // Update pledge with new amount
            $browser->type('#memberSearch', 'Ada')
                    ->waitFor('#memberDropdown button[data-id]')
                    ->click('#memberDropdown button[data-id]')
                    ->type('input[name="pledged_amount"]', '90.00')
                    ->press('Save Contribution')
                    ->assertSee('90.00');

            $pledge = MemberPledge::where('dues_cycle_id', $cycle->id)
                ->where('user_id', $member->id)
                ->firstOrFail();

            $this->assertEquals(90.0, (float) $pledge->pledged_amount);
        });
    }

    public function test_fs_can_delete_a_pledge(): void
    {
        $this->browse(function (Browser $browser) {
            $cycle = $this->getPledgeCycle();
            $chi = User::where('email', 'member.c@test.com')->firstOrFail();

            // Create pledge first
            $this->visitPledgesPage($browser)
                 ->type('#memberSearch', 'Chi')
                 ->waitFor('#memberDropdown button[data-id]')
                 ->click('#memberDropdown button[data-id]')
                 ->type('input[name="pledged_amount"]', '30.00')
                 ->press('Save Contribution')
                 ->assertSee('Chi Dusk');

            // Delete it (confirm dialog)
            $browser->acceptDialog();

            // The delete button is rendered per-row; click the first danger-outline in the table.
            $browser->click('table tbody tr:first-child button.btn-outline-danger')
                    ->pause(150) // allow DOM repaint
                    ->assertDontSee('Chi Dusk');

            $this->assertDatabaseMissing('member_pledges', [
                'dues_cycle_id' => $cycle->id,
                'user_id'       => $chi->id,
            ]);
        });
    }
}
