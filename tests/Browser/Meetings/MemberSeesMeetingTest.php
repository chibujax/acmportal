<?php

namespace Tests\Browser\Meetings;

use App\Models\Meeting;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Members should see active/closed meetings on their attendance page.
 */
class MemberSeesMeetingTest extends DuskTestCase
{
    public function test_member_sees_active_meeting_in_attendance_list(): void
    {
        $meeting = Meeting::create([
            'title'              => 'Active Meeting Visible To Members',
            'meeting_date'       => '2026-03-06',
            'meeting_time'       => '18:00:00',
            'late_after_time'    => '18:15:00',
            'meeting_end_time'   => '20:00:00',
            'venue'              => 'Community Hall',
            'venue_postcode'     => 'SW1A 1AA',
            'venue_radius'       => 150,
            'gps_failure_action' => 'flag',
            'status'             => 'active',
            'created_by'         => User::where('email', 'admin@test.com')->value('id'),
        ]);

        $this->browse(function (Browser $browser) use ($meeting) {
            $this->loginAsMember($browser, 'member.a@test.com');

            $browser->visit('/member/attendance')
                    ->assertSee('My Attendance')
                    ->assertSee($meeting->title);
        });
    }
}
