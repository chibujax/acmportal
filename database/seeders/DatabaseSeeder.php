<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Super Admin ───────────────────────────────────────
        User::firstOrCreate(['phone' => '07000000001'], [
            'name'     => 'ACM Admin',
            'email'    => 'admin@abiacommunitymanchester.org',
            'phone'    => '07000000001',
            'password' => Hash::make('Admin@1234'),
            'role'     => 'super_admin',
            'status'   => 'active',
        ]);

        // ── Financial Secretary ───────────────────────────────
        User::firstOrCreate(['phone' => '07000000002'], [
            'name'     => 'Financial Secretary',
            'email'    => 'finsec@abiacommunitymanchester.org',
            'phone'    => '07000000002',
            'password' => Hash::make('FinSec@1234'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        $this->command->info('✅ Admin accounts created.');
        $this->command->info('   Super Admin:  07000000001 / Admin@1234');
        $this->command->info('   Fin Sec:      07000000002 / FinSec@1234');

        // ── Historical data from CSV files ────────────────────
        $this->call(HistoricalDataSeeder::class);
    }
}
