<?php

namespace Database\Seeders;

use App\Models\DuesCycle;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use League\Csv\Reader;

class FixDuplicateUsersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // ── Fix 1: Rename Achilihu Sam Chimaobi → Chimaobi Sam Achilihu ─────
            $this->renameUser('Achilihu Sam Chimaobi', 'Chimaobi Sam Achilihu');

            // ── Fix 2: Separate Felicia Ogbonaya from Blessing Emeka ─────────────
            $this->separateUser('Blessing Emeka', 'Felicia Ogbonaya');

            // ── Fix 3: Separate Ngozi Onwubiko from Ngozi Amankulor ──────────────
            $this->separateUser('Ngozi Amankulor', 'Ngozi Onwubiko');

            // ── Fix 4: Onyinyechi — already one user, just report ────────────────
            $oyin = User::where('name', 'Onyinyechi Nwaogwugwu')->first();
            if ($oyin) {
                $count = $oyin->payments()->count();
                $this->command->info("Onyinyechi Nwaogwugwu: already on one account ({$count} payments). No action needed.");
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function renameUser(string $from, string $to): void
    {
        $user = User::where('name', $from)->first();

        if (! $user) {
            $this->command->warn("SKIP: User '$from' not found.");
            return;
        }

        $payments   = $user->payments()->count();
        $attendance = $user->attendanceRecords()->count();

        $user->update(['name' => $to]);

        $this->command->info("RENAMED: '$from' → '$to' (ID {$user->id}, {$payments} payments, {$attendance} attendance records)");
    }

    private function separateUser(string $primaryName, string $newName): void
    {
        $primaryUser = User::where('name', $primaryName)->first();

        if (! $primaryUser) {
            $this->command->warn("SKIP: Primary user '$primaryName' not found.");
            return;
        }

        // Create the separated user (no phone — admin can add it later)
        $newUser = User::firstOrCreate(
            ['name' => $newName],
            [
                'phone'    => null,
                'password' => Hash::make(Str::random(32)),
                'role'     => 'member',
                'status'   => 'inactive',
            ]
        );

        if (! $newUser->wasRecentlyCreated) {
            $this->command->warn("User '$newName' already exists (ID {$newUser->id}). Skipping separation.");
            return;
        }

        $this->command->info("Created user: '$newName' (ID {$newUser->id})");

        // Build dues CSV lookup maps
        $csv     = Reader::createFromPath(base_path('dues.csv'), 'r');
        $csv->setHeaderOffset(0);
        $records = iterator_to_array($csv->getRecords());

        // Key: "type-year-amount-date"  Value: count of rows with this key
        $primaryKeys = [];
        $newKeys     = [];

        foreach ($records as $row) {
            $key = $this->paymentKey($row);
            if (trim($row['name']) === $primaryName) {
                $primaryKeys[$key] = ($primaryKeys[$key] ?? 0) + 1;
            }
            if (trim($row['name']) === $newName) {
                $newKeys[$key] = ($newKeys[$key] ?? 0) + 1;
            }
        }

        // Build cycle lookup: "annual-2023" → DuesCycle
        $cycleMap = DuesCycle::all()->keyBy(function (DuesCycle $c) {
            $type = $c->type === 'yearly_dues' ? 'annual' : 'iriji';
            preg_match('/\d{4}/', $c->title, $m);
            return "$type-{$m[0]}";
        });

        $moved   = 0;
        $created = 0;

        foreach ($records as $row) {
            if (trim($row['name']) !== $newName) {
                continue;
            }

            $cycleKey   = "{$row['type']}-{$row['year']}";
            $paymentKey = $this->paymentKey($row);
            $cycle      = $cycleMap[$cycleKey] ?? null;

            if (! $cycle) {
                $this->command->warn("  No cycle found for '$cycleKey' — skipped.");
                continue;
            }

            if (isset($primaryKeys[$paymentKey])) {
                // Primary also paid this exact amount/date — the DB record belongs to primary.
                // Create a fresh payment on the new user.
                Payment::create([
                    'user_id'       => $newUser->id,
                    'dues_cycle_id' => $cycle->id,
                    'amount'        => (float) $row['amount'],
                    'currency'      => 'GBP',
                    'method'        => 'manual',
                    'status'        => 'completed',
                    'payment_date'  => $row['date_paid'] ?: null,
                    'notes'         => 'Separated from duplicate user import',
                ]);
                $created++;
            } else {
                // Primary never paid this — the DB record was misattributed. Move it.
                $updated = Payment::where('user_id', $primaryUser->id)
                    ->where('dues_cycle_id', $cycle->id)
                    ->where('amount', (float) $row['amount'])
                    ->where('payment_date', $row['date_paid'] ?: null)
                    ->limit(1)
                    ->update(['user_id' => $newUser->id, 'notes' => 'Separated from duplicate user import']);

                if ($updated) {
                    $moved++;
                } else {
                    $this->command->warn("  Could not find payment to move: $newName / $cycleKey / {$row['amount']} / {$row['date_paid']}");
                }
            }
        }

        $this->command->info(
            "SEPARATED '$newName' from '$primaryName': moved $moved payments, created $created new payments."
        );
    }

    private function paymentKey(array $row): string
    {
        return "{$row['type']}-{$row['year']}-" . (float) $row['amount'] . "-{$row['date_paid']}";
    }
}
