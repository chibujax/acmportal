<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\DuesCycle;
use App\Models\Meeting;
use App\Models\MemberChild;
use App\Models\MemberRelationship;
use App\Models\Payment;
use App\Models\PendingMember;
use App\Models\RegistrationToken;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ─────────────────────────────────────────────
        $admin = User::firstOrCreate(['phone' => '07000000001'], [
            'name'     => 'ACM Admin',
            'email'    => 'admin@abiacommunitymanchester.org',
            'phone'    => '07000000001',
            'password' => Hash::make('Admin@1234'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        // ── Financial Secretary ───────────────────────────────
        $finSec = User::firstOrCreate(['phone' => '07000000002'], [
            'name'     => 'Financial Secretary',
            'email'    => 'finsec@abiacommunitymanchester.org',
            'phone'    => '07000000002',
            'password' => Hash::make('FinSec@1234'),
            'role'     => 'financial_secretary',
            'status'   => 'active',
        ]);

        // ── Sample Members ────────────────────────────────────
        $memberData = [
            ['name' => 'Chukwuemeka Obi',   'phone' => '07111111001'],
            ['name' => 'Ngozi Obi',          'phone' => '07111111002'],  // wife of Chukwuemeka
            ['name' => 'Emeka Nwosu',        'phone' => '07111111003'],
            ['name' => 'Adaeze Okafor',      'phone' => '07111111004'],
            ['name' => 'Uche Ikechukwu',     'phone' => '07111111005'],
        ];

        $members = [];
        foreach ($memberData as $data) {
            $members[] = User::firstOrCreate(['phone' => $data['phone']], array_merge($data, [
                'password' => Hash::make('Member@1234'),
                'role'     => 'member',
                'status'   => 'active',
            ]));
        }

        // ── Pending Members (simulate CSV import) ─────────────
        $pending = [
            ['name' => 'Amaka Dike',    'phone' => '07222222001', 'email' => 'amaka@example.com'],
            ['name' => 'Obinna Oha',    'phone' => '07222222002', 'email' => null],
            ['name' => 'Chisom Nnadi',  'phone' => '07222222003', 'email' => 'chisom@example.com'],
        ];

        foreach ($pending as $data) {
            $pm = PendingMember::firstOrCreate(['phone' => $data['phone']], array_merge($data, [
                'status'       => 'invited',
                'import_batch' => 'BATCH-DEMO-001',
                'imported_by'  => $admin->id,
                'invited_at'   => now(),
            ]));

            if (! $pm->registrationToken()->exists()) {
                RegistrationToken::generate($pm);
            }
        }

        // ── Active Dues Cycles ────────────────────────────────
        DuesCycle::firstOrCreate(['title' => 'Annual Dues 2025'], [
            'title'           => 'Annual Dues 2025',
            'type'            => 'yearly_dues',
            'amount'          => 120.00,
            'currency'        => 'GBP',
            'start_date'      => '2025-01-01',
            'end_date'        => '2025-12-31',
            'payment_options' => 'monthly',
            'description'     => 'Annual membership dues for 2025. Couples pay £120 shared; single members pay £60.',
            'status'          => 'active',
            'send_reminders'  => true,
            'created_by'      => $admin->id,
        ]);

        $cycle2026 = DuesCycle::firstOrCreate(['title' => 'Annual Dues 2026'], [
            'title'           => 'Annual Dues 2026',
            'type'            => 'yearly_dues',
            'amount'          => 120.00,
            'currency'        => 'GBP',
            'start_date'      => '2026-01-01',
            'end_date'        => '2026-12-31',
            'payment_options' => 'monthly',
            'description'     => 'Annual membership dues for 2026. Couples pay £120 shared; single members pay £60.',
            'status'          => 'active',
            'send_reminders'  => true,
            'created_by'      => $admin->id,
        ]);

        // ── Sample Meetings ───────────────────────────────────
        $meetingDates = [
            ['title' => 'ACM General Meeting – October 2025',  'date' => '2025-10-19', 'status' => 'closed'],
            ['title' => 'ACM General Meeting – November 2025', 'date' => '2025-11-16', 'status' => 'closed'],
            ['title' => 'ACM General Meeting – December 2025', 'date' => '2025-12-14', 'status' => 'closed'],
            ['title' => 'ACM General Meeting – January 2026',  'date' => '2026-01-18', 'status' => 'closed'],
            ['title' => 'ACM General Meeting – February 2026', 'date' => '2026-02-15', 'status' => 'closed'],
            ['title' => 'ACM General Meeting – March 2026',    'date' => '2026-03-15', 'status' => 'scheduled'],
        ];

        foreach ($meetingDates as $md) {
            $meeting = Meeting::firstOrCreate(['title' => $md['title']], [
                'title'        => $md['title'],
                'meeting_date' => $md['date'],
                'meeting_time' => '18:00:00',
                'venue'        => 'Chorlton Irish Club, Manchester',
                'status'       => $md['status'],
                'created_by'   => $admin->id,
            ]);

            // Seed attendance for closed meetings (not all members attended each one)
            if ($md['status'] === 'closed' && $meeting->attendanceRecords()->count() === 0) {
                // Randomly assign 3-5 members as attending
                $attending = collect($members)->random(rand(3, 5));
                foreach ($attending as $m) {
                    AttendanceRecord::firstOrCreate(
                        ['meeting_id' => $meeting->id, 'user_id' => $m->id],
                        [
                            'check_in_time'   => \Carbon\Carbon::parse($md['date'] . ' 18:05:00'),
                            'check_in_method' => 'qr_scan',
                            'status'          => 'present',
                        ]
                    );
                }
            }
        }

        // ── Phase 2: Sample Spouse Relationship ───────────────
        // Chukwuemeka Obi (members[0]) married to Ngozi Obi (members[1])
        $member1 = $members[0]; // Chukwuemeka
        $member2 = $members[1]; // Ngozi

        if (! MemberRelationship::where('member_id_1', $member1->id)
            ->orWhere('member_id_2', $member1->id)->exists()) {

            MemberRelationship::create([
                'member_id_1'       => $member1->id,
                'member_id_2'       => $member2->id,
                'relationship_type' => 'spouse',
                'created_by'        => $admin->id,
                'created_at'        => now(),
            ]);
        }

        // ── Phase 2: Sample Children ──────────────────────────
        if (MemberChild::count() === 0) {
            MemberChild::create([
                'first_name'    => 'Chidi',
                'last_name'     => 'Obi',
                'date_of_birth' => '2015-06-12',
                'notes'         => null,
                'father_id'     => $member1->id,
                'mother_id'     => $member2->id,
                'added_by'      => $member1->id,
            ]);

            MemberChild::create([
                'first_name'    => 'Uchenna',
                'last_name'     => 'Obi',
                'date_of_birth' => '2018-03-22',
                'notes'         => null,
                'father_id'     => $member1->id,
                'mother_id'     => $member2->id,
                'added_by'      => $member1->id,
            ]);

            // Emeka Nwosu has a child (single parent)
            $member3 = $members[2]; // Emeka
            MemberChild::create([
                'first_name'    => 'Amara',
                'last_name'     => 'Nwosu',
                'date_of_birth' => '2019-11-05',
                'notes'         => null,
                'father_id'     => $member3->id,
                'mother_id'     => null,
                'added_by'      => $member3->id,
            ]);
        }

        // ── Phase 2: Sample Payments (family billing demo) ────
        // Record Chukwuemeka paying the full £120 couple obligation for 2026
        if (Payment::where('user_id', $member1->id)->where('dues_cycle_id', $cycle2026->id)->doesntExist()) {
            Payment::create([
                'user_id'         => $member1->id,
                'dues_cycle_id'   => $cycle2026->id,
                'amount'          => 120.00,
                'currency'        => 'GBP',
                'method'          => 'manual',
                'status'          => 'completed',
                'recorded_by'     => $finSec->id,
                'receipt_number'  => Payment::generateReceiptNumber(),
                'notes'           => 'Annual dues 2026 – couple payment (covers Chukwuemeka & Ngozi)',
                'payment_date'    => '2026-01-20',
            ]);
        }

        // Emeka pays £60 (single parent)
        $member3 = $members[2];
        if (Payment::where('user_id', $member3->id)->where('dues_cycle_id', $cycle2026->id)->doesntExist()) {
            Payment::create([
                'user_id'         => $member3->id,
                'dues_cycle_id'   => $cycle2026->id,
                'amount'          => 60.00,
                'currency'        => 'GBP',
                'method'          => 'manual',
                'status'          => 'completed',
                'recorded_by'     => $finSec->id,
                'receipt_number'  => Payment::generateReceiptNumber(),
                'notes'           => 'Annual dues 2026 – single member',
                'payment_date'    => '2026-01-25',
            ]);
        }

        $this->command->info('✅ Demo data seeded (Phase 1 + 2).');
        $this->command->info('   Admin:      07000000001 / Admin@1234');
        $this->command->info('   Fin. Sec.:  07000000002 / FinSec@1234');
        $this->command->info('   Members:    07111111001–005 / Member@1234');
        $this->command->info('   Couple:     Chukwuemeka (001) married to Ngozi (002) — £120 shared');
        $this->command->info('   Single:     Emeka (003) — £60');
    }
}
