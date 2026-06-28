<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Meeting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class March2026AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('phone', '07000000001')->firstOrFail();

        // ── Meeting ───────────────────────────────────────────────────────────
        $meeting = Meeting::firstOrCreate(
            ['meeting_date' => '2026-03-29'],
            [
                'title'        => 'ACM General Meeting – March 2026',
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
            'Nkem Kanis Igbokwe',
            'Isaiah Kalu',
            'Chisom Williams',
            'Amaka Williams',
            'Prince Eme Kalu',
            'Ngozi Iheanaetu',
            'Solomon Kalu',
            'Rose Inyama',
            'Blossom Ama-Kalu',
            'Uzunma Uba',
            'Chinyere Kalu',
            'Joy Francis',
            'Anne Ajere',
            'Linda Oby Kanu',
            'Uzoma Violet Oluwanbe',
            'Vine Munnonye',
            'Maclighting Munnonye',
            'Lucy Iroanya',
            'Justina Ogwuru',
            'Chukwuemeka Kalu',
            'Chukwu Oko Chukwu',
            'Joy Oko Chukwu',
            'Godwin Emezue',
            'James Kalu',
            'Anne Ariwaodo',
            'Ogbonna Ajiwe',
            'George Ibe',
            'Ezinne Ibe',
            'Amaka Ani',
            'Onyemachi Ndubuka',
            'Chioma Ndubuka',
            'Chinyere Ibezim',
            'Oyediya Blessing Uke',
            'Anthony Mgbarine',
            'Blessing Emeka',
            'Goodwin Ndubeze',
            'Sylvia Ndubeze',
            'Chucks Kalu',
            'Akudo Onuoha',
            'Grace Obison Stevens',
            'Chris Ogwuru',
            'Bede Nnadi',
            'Amuche Nnadi',
            'Ikechukwu Amankulor',
            'Odinaka Steven Olumba',
            'Onyinyechi Nwaogwugwu Ohajuru',
            'Stella Achilihu',
            'Chimaobi Sam Achilihu',
            'Stella Ezekwe',
            'Kelechi Njoku',
        ];

        // ── Apologies ─────────────────────────────────────────────────────────
        $apologiesNames = [
            'Chibueze Uchendu',
            'Ngozi Oged',
            'Rose Ujachukwu',
            'Chijioke Ibezim',
            'Nkechi Emenike',
            'Sunday Onuoha',
            'Livingstone Iroanya',
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
                    'check_in_time'   => Carbon::parse('2026-03-29 18:05:00'),
                    'check_in_method' => 'manual',
                    'status'          => 'present',
                    'notes'           => 'Imported from March 2026 attendance sheet',
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
                    'check_in_time'   => Carbon::parse('2026-03-29 18:05:00'),
                    'check_in_method' => 'excused',
                    'status'          => 'excused',
                    'notes'           => 'Apology submitted',
                ]
            );
            $excused++;
        }

        $this->command->info('');
        $this->command->info('── March 2026 attendance complete ──────────────────────');
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
