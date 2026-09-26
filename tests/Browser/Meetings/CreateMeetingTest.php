<?php

namespace Tests\Browser\Meetings;

use App\Models\Meeting;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Meeting creation should be stable independent of the live Google Maps picker.
 */
class CreateMeetingTest extends DuskTestCase
{
    public function test_admin_can_create_meeting_from_ui(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $title = 'Dusk Meeting - March 2026';

            $browser->visit('/admin/meetings/create')
                    ->type('input[name="title"]', $title)
                    ->type('input[name="meeting_date"]', '2026-03-05')
                    ->type('input[name="meeting_time"]', '18:00')
                    ->type('input[name="late_after_time"]', '18:15')
                    ->type('input[name="meeting_end_time"]', '20:00')
                    ->type('input[name="venue"]', 'Community Hall, SW1A 1AA')
                    ->type('input[name="venue_radius"]', '150')
                    ->select('select[name="gps_failure_action"]', 'flag')
                    ->type('textarea[name="description"]', 'Created by Dusk test')
                    // Confirming a pin requires the live Google Maps/Places widget,
                    // which isn't available in the test environment — simulate a
                    // confirmed location directly instead of driving the real map.
                    ->script([
                        "document.getElementById('venueLat').value = '51.5010';",
                        "document.getElementById('venueLng').value = '-0.1416';",
                        "document.getElementById('submitBtn').disabled = false;",
                    ])
                    ->press('Create Meeting')
                    ->waitForLocation('/admin/meetings')
                    ->assertSee('Meeting created successfully.')
                    ->assertSee($title);

            $meeting = Meeting::where('title', $title)->first();
            $this->assertNotNull($meeting);
        });
    }
}
