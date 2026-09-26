<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Meeting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class April2026AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('phone', '07000000001')->firstOrFail();

        // ── 1. New members (first-timers not yet in the system) ───────────────
        $newMembers = [
            ['name' => 'Uchenna Nwabisi',   'gender' => null],
            ['name' => 'Paschaline Solomon', 'gender' => 'female'],
            ['name' => 'Bertha Adiele',      'gender' => 'female'],
            ['name' => 'Glory Adiele',       'gender' => 'female'],
        ];

        $phoneCounter = $this->nextPhoneCounter();

        foreach ($newMembers as $data) {
            $user = User::firstOrCreate(
                ['name' => $data['name']],
                [
                    'phone'    => sprintf('07001%06d', $phoneCounter++),
                    'gender'   => $data['gender'],
                    'password' => Hash::make(Str::random(32)),
                    'role'     => 'member',
                    'status'   => 'inactive',
                ]
            );
            $this->command->info('  ' . ($user->wasRecentlyCreated ? 'Created' : 'Found') . ": {$data['name']}");
        }

        // ── 2. Meeting ────────────────────────────────────────────────────────
        $meeting = Meeting::firstOrCreate(
            ['meeting_date' => '2026-04-26'],
            [
                'title'        => 'ACM General Meeting – April 2026',
                'meeting_time' => '18:00:00',
                'venue'        => 'Chorlton Irish Club, Manchester',
                'status'       => 'closed',
                'created_by'   => $admin->id,
            ]
        );

        $this->command->info($meeting->wasRecentlyCreated ? '  Meeting created.' : '  Meeting already exists.');

        // ── 3. Attendance — present ───────────────────────────────────────────
        // Names here are the canonical names from the users table (members.csv)
        $presentNames = [
            'Andrew Iheanaetu',
            'Ugo Inyama',
            'Chibueze Uchendu',
            'Nkem Kanis Igbokwe',
            'Charles Tucker',
            'Ann Tucker',
            'Maclighting Munnonye',
            'Vine Munnonye',
            'Ngozi Iheanaetu',
            'Jenny Orji',
            'Blessing Ozegbe',
            'Uzoma Violet Oluwanbe',
            'Isealife Ositaike',
            'Chucks Kalu',
            'Chioma Ndubuka',
            'Bede Nnadi',
            'Amuche Nnadi',
            'Chris Imoh',
            'Jonathan Ajere',
            'Rose Inyama',
            'Goodwin Ndubeze',
            'Sylvia Ndubeze',
            'Anyacho Emeka Onuoha',
            'Esther Onuoha',
            'Glory Adiele',
            'Paul Ogwuegbu',
            'Uchenna Nwabisi',
            'Solomon Kalu',
            'Paschaline Solomon',
            'Kate James',
            'Stella Achilihu',
            'Ego Edith Ogbonna',
            'Chinyere Ibezim',
            'Chijioke Ibezim',
            'Reggy Kanu',
            'Linda Oby Kanu',
            'Isaiah Kalu',
            'James Kalu',
            'Livingstone Iroanya',
            'Chisom Williams',
            'Amaka Williams',
            'George Ibe',
            'Ezinne Ibe',
            'Akudo Onuoha',
            'Chukwu Oko Chukwu',
            'Joy Oko Chukwu',
            'Blessing Emeka',
            'Mercy Arua',
            'Ngozi Oged',
            'Odinaka Steven Olumba',
            'Godwin Emezue',
            'Bertha Adiele',
            'Ngozi Amankulor Onwubiko',
        ];

        // ── 4. Attendance — apologies ─────────────────────────────────────────
        $apologiesNames = [
            'Onyinyechi Nwaogwugwu Ohajuru',
            'Chimka Ama-Kalu',
            'Goodness Kalu',
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
                    'check_in_time'   => Carbon::parse('2026-04-26 18:05:00'),
                    'check_in_method' => 'manual',
                    'status'          => 'present',
                    'notes'           => 'Imported from April 2026 attendance sheet',
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
                    'check_in_time'   => Carbon::parse('2026-04-26 18:05:00'),
                    'check_in_method' => 'excused',
                    'status'          => 'excused',
                    'notes'           => 'Apology submitted',
                ]
            );
            $excused++;
        }

        $this->command->info('');
        $this->command->info('── April 2026 attendance complete ──────────────────────');
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

    private function nextPhoneCounter(): int
    {
        $last = User::where('phone', 'like', '07001%')
            ->orderByDesc('phone')
            ->value('phone');

        return $last ? ((int) substr($last, 5)) + 1 : 1;
    }
}
