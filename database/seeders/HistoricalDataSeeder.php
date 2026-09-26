<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\DuesCycle;
use App\Models\Meeting;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use League\Csv\Reader;

class HistoricalDataSeeder extends Seeder
{
    private array $nameToUser = [];
    private array $warnings   = [];
    private int   $usersCreated = 0;
    private int   $paymentsCreated = 0;
    private int   $attendanceCreated = 0;
    private int   $phoneCounter = 1;

    public function run(): void
    {
        $admin = User::where('phone', '07000000001')->first();

        if (! $admin) {
            $this->command->error('Admin user not found. Run `php artisan db:seed` (DatabaseSeeder) first.');
            return;
        }

        $this->command->info('── Importing members...');
        $this->importMembers($admin);

        $this->command->info('── Creating dues cycles...');
        $cycles = $this->createDuesCycles($admin);

        $this->command->info('── Creating meetings...');
        $meetings = $this->createMeetings($admin);

        $this->command->info('── Importing payment records...');
        $this->importPayments($cycles);

        $this->command->info('── Importing attendance records...');
        $this->importAttendance($meetings);

        $this->printSummary();
    }

    // ── 1. Members ────────────────────────────────────────────────────────────

    private function importMembers(User $admin): void
    {
        $csv        = $this->readCsv(base_path('members.csv'));
        $seenPhones = [];

        foreach ($csv as $row) {
            $name   = trim($row['name'] ?? '');
            $phone  = $this->normalisePhone($row['phone'] ?? '');
            $email  = trim($row['email'] ?? '') ?: null;
            $gender = trim($row['gender'] ?? '') ?: null;

            if (! $name) {
                continue;
            }

            $key = strtolower($name);

            // Duplicate real phone — alias this name to the existing user
            if ($phone && isset($seenPhones[$phone])) {
                $existing = $seenPhones[$phone];
                $this->nameToUser[$key] = $existing;
                $this->warnings[] = "DUPLICATE PHONE $phone: '$name' aliased to '{$existing->name}'";
                continue;
            }

            if ($phone) {
                // Member has a real phone — deduplicate by phone
                $user = User::firstOrCreate(
                    ['phone' => $phone],
                    [
                        'name'     => $name,
                        'phone'    => $phone,
                        'email'    => $email,
                        'gender'   => $gender,
                        'password' => Hash::make(\Illuminate\Support\Str::random(32)),
                        'role'     => 'member',
                        'status'   => 'inactive',
                    ]
                );
                $seenPhones[$phone] = $user;
            } else {
                // No phone — deduplicate by name, assign a placeholder phone
                $user = User::firstOrCreate(
                    ['name' => $name],
                    [
                        'name'     => $name,
                        'phone'    => $this->generatePlaceholderPhone(),
                        'email'    => $email,
                        'gender'   => $gender,
                        'password' => Hash::make(\Illuminate\Support\Str::random(32)),
                        'role'     => 'member',
                        'status'   => 'inactive',
                    ]
                );
            }

            if ($user->wasRecentlyCreated) {
                $this->usersCreated++;
            }

            $this->nameToUser[$key] = $user;
        }
    }

    // ── 2. Dues Cycles ────────────────────────────────────────────────────────

    private function createDuesCycles(User $admin): array
    {
        $csv = $this->readCsv(base_path('dues.csv'));

        // Collect unique (type, year) pairs from the CSV
        $pairs = [];
        foreach ($csv as $row) {
            $type = trim($row['type'] ?? '');
            $year = (int) trim($row['year'] ?? 0);
            if ($type && $year) {
                $pairs["$type-$year"] = ['type' => $type, 'year' => $year];
            }
        }

        ksort($pairs);

        $cycles = [];
        $currentYear = (int) now()->format('Y');

        foreach ($pairs as $key => ['type' => $type, 'year' => $year]) {
            $dbType = $type === 'annual' ? 'yearly_dues' : 'event_levy';
            $label  = $type === 'annual' ? 'Annual Dues' : 'Iriji Dues';
            $title  = "$label $year";

            $isActive = $year >= $currentYear;

            if ($type === 'annual') {
                $startDate = "$year-01-01";
                $endDate   = "$year-12-31";
                $amount    = 120.00;
                $options   = 'installments';
            } else {
                $startDate = "$year-07-01";
                $endDate   = "$year-09-30";
                $amount    = 100.00;
                $options   = 'once';
            }

            $cycle = DuesCycle::firstOrCreate(
                ['title' => $title],
                [
                    'type'            => $dbType,
                    'amount'          => $amount,
                    'currency'        => 'GBP',
                    'start_date'      => $startDate,
                    'end_date'        => $endDate,
                    'payment_options' => $options,
                    'status'          => $isActive ? 'active' : 'closed',
                    'send_reminders'  => true,
                    'created_by'      => $admin->id,
                ]
            );

            $cycles[$key] = $cycle;
        }

        $this->command->info("   Created/found " . count($cycles) . " dues cycles.");
        return $cycles;
    }

    // ── 3. Meetings ───────────────────────────────────────────────────────────

    private function createMeetings(User $admin): array
    {
        $csv = $this->readCsv(base_path('attendance.csv'));

        $dates = [];
        foreach ($csv as $row) {
            $date = trim($row['meeting_date'] ?? '');
            if ($date) {
                $dates[$date] = true;
            }
        }

        ksort($dates);

        $meetings = [];
        foreach (array_keys($dates) as $date) {
            $carbon = Carbon::parse($date);
            $title  = 'ACM General Meeting – ' . $carbon->format('F Y');

            $meeting = Meeting::firstOrCreate(
                ['meeting_date' => $date],
                [
                    'title'        => $title,
                    'meeting_time' => '18:00:00',
                    'venue'        => 'Chorlton Irish Club, Manchester',
                    'status'       => 'closed',
                    'created_by'   => $admin->id,
                ]
            );

            $meetings[$date] = $meeting;
        }

        $this->command->info("   Created/found " . count($meetings) . " meetings.");
        return $meetings;
    }

    // ── 4. Payments ───────────────────────────────────────────────────────────

    private function importPayments(array $cycles): void
    {
        $csv = $this->readCsv(base_path('dues.csv'));

        foreach ($csv as $index => $row) {
            $name      = trim($row['name'] ?? '');
            $type      = trim($row['type'] ?? '');
            $year      = (int) trim($row['year'] ?? 0);
            $amount    = (float) trim($row['amount'] ?? 0);
            $datePaid  = trim($row['date_paid'] ?? '');

            if (! $name || ! $type || ! $year || ! $amount) {
                continue;
            }

            $user = $this->findUser($name);
            if (! $user) {
                $this->warnings[] = "PAYMENT row " . ($index + 2) . ": no user found for '$name' — skipped.";
                continue;
            }

            $cycleKey = "$type-$year";
            if (! isset($cycles[$cycleKey])) {
                $this->warnings[] = "PAYMENT row " . ($index + 2) . ": no dues cycle '$cycleKey' — skipped.";
                continue;
            }

            Payment::firstOrCreate(
                [
                    'user_id'       => $user->id,
                    'dues_cycle_id' => $cycles[$cycleKey]->id,
                    'amount'        => $amount,
                    'payment_date'  => $datePaid ?: null,
                ],
                [
                    'currency'     => 'GBP',
                    'method'       => 'manual',
                    'status'       => 'completed',
                    'notes'        => 'Imported from historical records',
                ]
            );

            $this->paymentsCreated++;
        }
    }

    // ── 5. Attendance ─────────────────────────────────────────────────────────

    private function importAttendance(array $meetings): void
    {
        $csv = $this->readCsv(base_path('attendance.csv'));

        foreach ($csv as $index => $row) {
            $name   = trim($row['name'] ?? '');
            $date   = trim($row['meeting_date'] ?? '');
            $status = strtolower(trim($row['status'] ?? ''));

            if (! $name || ! $date || $status === 'absent') {
                continue;
            }

            $user = $this->findUser($name);
            if (! $user) {
                $this->warnings[] = "ATTENDANCE row " . ($index + 2) . ": no user found for '$name' — skipped.";
                continue;
            }

            if (! isset($meetings[$date])) {
                $this->warnings[] = "ATTENDANCE row " . ($index + 2) . ": no meeting for date '$date' — skipped.";
                continue;
            }

            $meeting       = $meetings[$date];
            $checkInMethod = $status === 'excused' ? 'excused' : 'manual';
            $checkInTime   = Carbon::parse($date . ' 18:05:00');

            AttendanceRecord::firstOrCreate(
                ['meeting_id' => $meeting->id, 'user_id' => $user->id],
                [
                    'check_in_time'   => $checkInTime,
                    'check_in_method' => $checkInMethod,
                    'status'          => $status,
                    'notes'           => 'Imported from historical records',
                ]
            );

            $this->attendanceCreated++;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function findUser(string $name): ?User
    {
        $key = strtolower(trim($name));

        if (isset($this->nameToUser[$key])) {
            return $this->nameToUser[$key];
        }

        // Not in members.csv — create a minimal inactive user so data isn't lost
        $user = User::firstOrCreate(
            ['name' => $name],
            [
                'name'     => $name,
                'phone'    => $this->generatePlaceholderPhone(),
                'password' => Hash::make(\Illuminate\Support\Str::random(32)),
                'role'     => 'member',
                'status'   => 'inactive',
            ]
        );

        if ($user->wasRecentlyCreated) {
            $this->usersCreated++;
            $this->warnings[] = "NEW USER created from dues/attendance (not in members.csv): '$name'";
        }

        $this->nameToUser[$key] = $user;
        return $user;
    }

    private function generatePlaceholderPhone(): string
    {
        return sprintf('07001%06d', $this->phoneCounter++);
    }

    private function readCsv(string $path): \Iterator
    {
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);
        return $csv->getRecords();
    }

    private function normalisePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', $phone);
    }

    private function printSummary(): void
    {
        $placeholders = $this->phoneCounter - 1;

        $this->command->info('');
        $this->command->info('── Historical import complete ──────────────────────────────');
        $this->command->info("   Users created:        $this->usersCreated");
        $this->command->info("   Placeholder phones:   $placeholders (07001000001 … " . sprintf('07001%06d', $this->phoneCounter - 1) . ')');
        $this->command->info("   Payments imported:    $this->paymentsCreated");
        $this->command->info("   Attendance records:   $this->attendanceCreated");

        if ($this->warnings) {
            $this->command->warn('');
            $this->command->warn('── Warnings (' . count($this->warnings) . ') ─────────────────────────────────────');
            foreach ($this->warnings as $w) {
                $this->command->warn("   $w");
            }
        }
    }
}
