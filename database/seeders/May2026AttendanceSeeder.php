<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Meeting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class May2026AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('phone', '07000000001')->firstOrFail();

        // ── 1. New members ────────────────────────────────────────────────────
        $newMembers = [
            ['name' => 'Rachel David Eluwa',   'gender' => 'female'],
            ['name' => 'Chibuzor David Eluwa',  'gender' => 'male'],
            ['name' => 'Ogechi Kalibe',          'gender' => 'female'],
            ['name' => 'Chuks Joseph',           'gender' => 'male'],
            ['name' => 'Jeffery Emeka',          'gender' => 'male'],
            ['name' => 'Chidinma Eluka',         'gender' => 'female'],
            ['name' => 'Chukwuemeka Ogbuso',     'gender' => 'male'],
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
            ['meeting_date' => '2026-05-31'],
            [
                'title'        => 'ACM General Meeting – May 2026',
                'meeting_time' => '18:00:00',
                'venue'        => 'Chorlton Irish Club, Manchester',
                'status'       => 'closed',
                'created_by'   => $admin->id,
            ]
        );

        $this->command->info($meeting->wasRecentlyCreated ? '  Meeting created.' : '  Meeting already exists.');

        // ── 3. Present ────────────────────────────────────────────────────────
        $presentNames = [
            'Andrew Iheanaetu',
            'Ugo Inyama',
            'Chibueze Uchendu',
            'Anyacho Emeka Onuoha',
            'Chukwuemeka Kalu',
            'Ezinne Ibe',
            'Rose Inyama',
            'Rachel David Eluwa',
            'Chibuzor David Eluwa',
            'Ego Edith Ogbonna',
            'Ngozi Oged',
            'Justina Ogwuru',
            'Anne Ajere',
            'Kate James',
            'Ogechi Kalibe',
            'Chukwuemeka Ogbuso',
            'Akudo Onuoha',
            'Chuks Joseph',
            'Jenny Orji',
            'Joel Orji',
            'Chris Ogwuru',
            'Nwanneka Uwaezuoke',
            'Goodness Kalu',
            'Blessing Ozegbe',
            'Sunday Ekeh',
            'Isaiah Kalu',
            'Chibuzo Kalu',
            'Chucks Kalu',
            'James Kalu',
            'Chukwu Oko Chukwu',
            'Joy Oko Chukwu',
            'Kelechi Njoku',
            'Armstrong Otasowie',
            'Ikechukwu Amankulor',
            'Ngozi Amankulor Onwubiko',
            'Bede Nnadi',
            'Amuche Nnadi',
            'Bertha Adiele',
            'Oyediya Blessing Uke',
            'Reggy Kanu',
            'Linda Oby Kanu',
            'Gloria Ekeanyanwu',
            'Onyemachi Ndubuka',
            'Chioma Ndubuka',
            'Uzoma Violet Oluwanbe',
            'Uzunma Uba',
            'Ijeoma Ernest',
            'Amaka Williams',
            'Lucy Iroanya',
            'Jonathan Ajere',
            'Stella Ezekwe',
            'Chisom Williams',
            'Blessing Emeka',
            // New timers present but listed separately in the sheet
            'Jeffery Emeka',
            'Chidinma Eluka',
        ];

        // ── 4. Apologies ──────────────────────────────────────────────────────
        $apologiesNames = [
            'Prince Eme Kalu',
            'Charles Tucker',
            'Ann Tucker',
            'Chimka Ama-Kalu',
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
                    'check_in_time'   => Carbon::parse('2026-05-31 18:05:00'),
                    'check_in_method' => 'manual',
                    'status'          => 'present',
                    'notes'           => 'Imported from May 2026 attendance sheet',
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
                    'check_in_time'   => Carbon::parse('2026-05-31 18:05:00'),
                    'check_in_method' => 'excused',
                    'status'          => 'excused',
                    'notes'           => 'Apology submitted',
                ]
            );
            $excused++;
        }

        $this->command->info('');
        $this->command->info('── May 2026 attendance complete ────────────────────────');
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
