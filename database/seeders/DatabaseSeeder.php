<?php

namespace Database\Seeders;

use App\Models\DuesCycle;
use App\Models\PendingMember;
use App\Models\RegistrationToken;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
        $members = [
            ['name' => 'Chukwuemeka Obi',   'phone' => '07111111001'],
            ['name' => 'Ngozi Eze',          'phone' => '07111111002'],
            ['name' => 'Emeka Nwosu',        'phone' => '07111111003'],
            ['name' => 'Adaeze Okafor',      'phone' => '07111111004'],
            ['name' => 'Uche Ikechukwu',     'phone' => '07111111005'],
        ];

        foreach ($members as $data) {
            User::firstOrCreate(['phone' => $data['phone']], array_merge($data, [
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

        // ── Active Dues Cycle ─────────────────────────────────
        DuesCycle::firstOrCreate(['title' => 'Annual Dues 2025'], [
            'title'           => 'Annual Dues 2025',
            'type'            => 'yearly_dues',
            'amount'          => 120.00,
            'currency'        => 'GBP',
            'start_date'      => '2025-01-01',
            'end_date'        => '2025-12-31',
            'payment_options' => 'monthly',
            'description'     => 'Annual membership dues for 2025. £10 per month.',
            'status'          => 'active',
            'send_reminders'  => true,
            'created_by'      => $admin->id,
        ]);

        DuesCycle::firstOrCreate(['title' => 'Annual Dues 2026'], [
            'title'           => 'Annual Dues 2026',
            'type'            => 'yearly_dues',
            'amount'          => 120.00,
            'currency'        => 'GBP',
            'start_date'      => '2026-01-01',
            'end_date'        => '2026-12-31',
            'payment_options' => 'monthly',
            'description'     => 'Annual membership dues for 2026. £10 per month.',
            'status'          => 'active',
            'send_reminders'  => true,
            'created_by'      => $admin->id,
        ]);

        $this->command->info('✅ Demo data seeded.');
        $this->command->info('   Admin:      07000000001 / Admin@1234');
        $this->command->info('   Fin. Sec.:  07000000002 / FinSec@1234');
        $this->command->info('   Members:    07111111001–005 / Member@1234');
    }
}
