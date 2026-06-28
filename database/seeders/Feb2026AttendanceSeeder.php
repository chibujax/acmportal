<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Meeting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class Feb2026AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('phone', '07000000001')->firstOrFail();

        // ── Meeting ───────────────────────────────────────────────────────────
        $meeting = Meeting::firstOrCreate(
            ['meeting_date' => '2026-02-25'],
            [
                'title'        => 'ACM General Meeting – February 2026',
                'meeting_time' => '18:00:00',
                'venue'        => 'Chorlton Irish Club, Manchester',
                'status'       => 'closed',
                'created_by'   => $admin->id,
            ]
        );

        $this->command->info($meeting->wasRecentlyCreated ? '  Meeting created.' : '  Meeting already exists.');

        // ── Present ───────────────────────────────────────────────────────────
        $presentNames = [
            'Andrew Iheanaetu',
            'Ugo Inyama',
            'Blessing Emeka',
            'Felicia Ogbonaya',
            'Anyacho Emeka Onuoha',
            'Nkem Kanis Igbokwe',
            'Ego Edith Ogbonna',
            'Livingstone Iroanya',
            'Lucy Iroanya',
            'Goodwin Ndubeze',
            'Justina Ogwuru',
            'Linda Oby Kanu',
            'Chinyere Ibezim',
            'Kate James',
            'Rose Inyama',
            'Ngozi Iheanaetu',
            'Chioma Ndubuka',
            'Kelechi Njoku',
            'Ezinne Ibe',
            'George Ibe',
            'Akudo Onuoha',
            'Godwin Emezue',
            'Chukwu Oko Chukwu',
            'Prince Eme Kalu',
            'Chinyere Kalu',
            'Uzunma Uba',
            'Oyediya Blessing Uke',
            'Grace Obison Stevens',
            'Isaiah Kalu',
            'Chris Ogwuru',
            'Blossom Ama-Kalu',
            'Rose Ujachukwu',
            'Chukwuemeka Kalu',
            'Uzoma Violet Oluwanbe',
            'Ikechukwu Amankulor',
            'Chibueze Uchendu',
            'Chisom Williams',
            'Amaka Williams',
            'Nwanneka Uwaezuoke',
            'Emenike Ndusoro',
        ];

        // ── Apologies ─────────────────────────────────────────────────────────
        $apologiesNames = [
            'Onyinyechi Nwaogwugwu Ohajuru',
        ];

        $present  = 0;
        $excused  = 0;
        $notFound = [];

        foreach ($presentNames as $name) {
            $user = User::where('name', $name)->first();

            if (! $user) {
                $notFound[] = "PRESENT – $name";
                continue;
            }

            AttendanceRecord::firstOrCreate(
                ['meeting_id' => $meeting->id, 'user_id' => $user->id],
                [
                    'check_in_time'   => Carbon::parse('2026-02-25 18:05:00'),
                    'check_in_method' => 'manual',
                    'status'          => 'present',
                    'notes'           => 'Imported from February 2026 attendance sheet',
                ]
            );
            $present++;
        }

        foreach ($apologiesNames as $name) {
            $user = User::where('name', $name)->first();

            if (! $user) {
                $notFound[] = "APOLOGY – $name";
                continue;
            }

            AttendanceRecord::firstOrCreate(
                ['meeting_id' => $meeting->id, 'user_id' => $user->id],
                [
                    'check_in_time'   => Carbon::parse('2026-02-25 18:05:00'),
                    'check_in_method' => 'excused',
                    'status'          => 'excused',
                    'notes'           => 'Apology submitted',
                ]
            );
            $excused++;
        }

        $this->command->info('');
        $this->command->info('── February 2026 attendance complete ───────────────────');
        $this->command->info("   Present:  $present");
        $this->command->info("   Excused:  $excused");

        if ($notFound) {
            $this->command->warn('');
            $this->command->warn('── Not found (' . count($notFound) . ') ──────────────────────────────────────');
            foreach ($notFound as $n) {
                $this->command->warn("   $n");
            }
        }
    }
}
