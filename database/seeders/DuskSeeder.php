<?php

namespace Database\Seeders;

use App\Models\DuesCycle;
use App\Models\MemberPledge;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Deterministic seeder for Laravel Dusk E2E tests.
 *
 * Credentials (all predictable):
 *   Admin:      admin@test.com       / Admin@1234
 *   Fin. Sec.:  finsec@test.com      / FinSec@1234
 *   Member A:   member.a@test.com    / Member@1234  (married to B)
 *   Member B:   member.b@test.com    / Member@1234  (spouse of A)
 *   Member C:   member.c@test.com    / Member@1234  (single)
 *
 * Cycles (all active, 2026-01-01 → 2026-12-31):
 *   Standard Levy 2026  – £60, couple_shared: false
 *   Couple Dues 2026    – £120, couple_shared: true
 *   Harvest Pledge 2026 – pledge-based donation
 */
class DuskSeeder extends Seeder
{
    public function run(): void
    {
        // ── Users ─────────────────────────────────────────────────────────────

        $admin = User::create([
            'name'     => 'Dusk Admin',
            'email'    => 'admin@test.com',
            'phone'    => '07900000001',
            'password' => Hash::make('Admin@1234'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        $finSec = User::create([
            'name'     => 'Dusk FinSec',
            'email'    => 'finsec@test.com',
            'phone'    => '07900000002',
            'password' => Hash::make('FinSec@1234'),
            'role'     => 'financial_secretary',
            'status'   => 'active',
        ]);

        $memberA = User::create([
            'name'     => 'Ada Dusk',
            'email'    => 'member.a@test.com',
            'phone'    => '07900000003',
            'password' => Hash::make('Member@1234'),
            'role'     => 'member',
            'status'   => 'active',
        ]);

        $memberB = User::create([
            'name'     => 'Ben Dusk',
            'email'    => 'member.b@test.com',
            'phone'    => '07900000004',
            'password' => Hash::make('Member@1234'),
            'role'     => 'member',
            'status'   => 'active',
        ]);

        $memberC = User::create([
            'name'     => 'Chi Dusk',
            'email'    => 'member.c@test.com',
            'phone'    => '07900000005',
            'password' => Hash::make('Member@1234'),
            'role'     => 'member',
            'status'   => 'active',
        ]);

        // ── Spouse relationship: Ada ↔ Ben ────────────────────────────────────

        MemberRelationship::create([
            'member_id_1'       => $memberA->id,
            'member_id_2'       => $memberB->id,
            'relationship_type' => 'spouse',
            'created_by'        => $admin->id,
            'created_at'        => now(),
        ]);

        // ── Dues Cycles ───────────────────────────────────────────────────────

        // Cycle 1: Standard flat levy — everyone pays £60, no couple sharing
        DuesCycle::create([
            'title'           => 'Standard Levy 2026',
            'type'            => 'yearly_dues',
            'amount'          => 60.00,
            'currency'        => 'GBP',
            'start_date'      => '2026-01-01',
            'end_date'        => '2026-12-31',
            'payment_options' => 'once',
            'status'          => 'active',
            'couple_shared'   => false,
            'is_pledge_based' => false,
            'accepts_items'   => false,
            'send_reminders'  => true,
            'created_by'      => $admin->id,
        ]);

        // Cycle 2: Couple-shared dues — married pair pays £120, singles pay £60
        DuesCycle::create([
            'title'           => 'Couple Dues 2026',
            'type'            => 'yearly_dues',
            'amount'          => 120.00,
            'currency'        => 'GBP',
            'start_date'      => '2026-01-01',
            'end_date'        => '2026-12-31',
            'payment_options' => 'once',
            'status'          => 'active',
            'couple_shared'   => true,
            'is_pledge_based' => false,
            'accepts_items'   => false,
            'send_reminders'  => true,
            'created_by'      => $admin->id,
        ]);

        // Cycle 3: Pledge-based donation
        DuesCycle::create([
            'title'           => 'Harvest Pledge 2026',
            'type'            => 'donation',
            'amount'          => 0,
            'currency'        => 'GBP',
            'start_date'      => '2026-01-01',
            'end_date'        => '2026-12-31',
            'payment_options' => 'once',
            'status'          => 'active',
            'couple_shared'   => false,
            'is_pledge_based' => true,
            'accepts_items'   => true,
            'send_reminders'  => false,
            'created_by'      => $admin->id,
        ]);
    }
}
