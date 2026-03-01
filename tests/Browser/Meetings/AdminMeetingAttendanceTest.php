<?php

namespace Tests\Browser\Meetings;

use App\Models\AttendanceRecord;
use App\Models\Meeting;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Admin meeting show page should allow manual check-in and show attendance records table.
 */
class AdminMeetingAttendanceTest extends DuskTestCase
{
    public function test_admin_can_manual_checkin_and_see_attendance_row(): void
    {
        $adminId = User::where('email', 'admin@test.com')->value('id');
        $member  = User::where('email', 'member.a@test.com')->firstOrFail();

        $meeting = Meeting::create([
            'title'              => 'Meeting For Manual Check-In',
            'meeting_date'       => '2026-03-07',
            'meeting_time'       => '18:00:00',
            'late_after_time'    => '18:15:00',
            'meeting_end_time'   => '20:00:00',
            'venue'              => 'Community Hall',
            'venue_postcode'     => 'SW1A 1AA',
            'venue_radius'       => 150,
            'gps_failure_action' => 'flag',
            'status'             => 'active',
            'created_by'         => $adminId,
        ]);

        $this->browse(function (Browser $browser) use ($meeting, $member) {
            $this->loginAsAdmin($browser);

            $browser->visit("/admin/meetings/{$meeting->id}")
                    ->assertSee($meeting->title)
                    ->assertPresent('#checkinSearch')
                    ->type('#checkinSearch', 'Ada')
                    ->waitFor('#checkinDropdown button[data-id]')
                    ->click('#checkinDropdown button[data-id]')
                    ->type('input[name="notes"]', 'manual check-in by test')
                    ->press('Check In')
                    ->assertSee('Member checked in manually.')
                    ->assertSee('Ada Dusk');
        });

        $this->assertDatabaseHas('attendance_records', [
            'meeting_id'      => $meeting->id,
            'user_id'         => $member->id,
            'check_in_method' => 'manual',
            'status'          => 'present',
        ]);
    }
}
