<?php

namespace App\Console\Commands;

use App\Models\MemberLegacyBalance;
use App\Models\User;
use Illuminate\Console\Command;

class ImportLegacyBalances extends Command
{
    protected $signature = 'import:legacy-balances
                            {file : Path to the CSV file}
                            {--fresh : Wipe existing legacy balances before importing}';

    protected $description = 'Import per-member legacy outstanding balances from a CSV file.
                              CSV must have columns: phone, label, year, amount';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! file_exists($path)) {
            $this->error("File not found: $path");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            MemberLegacyBalance::truncate();
            $this->warn('Existing legacy balances cleared.');
        }

        $handle = fopen($path, 'r');
        $headers = array_map('trim', fgetcsv($handle));

        $required = ['phone', 'label', 'year', 'amount'];
        $missing  = array_diff($required, $headers);

        if ($missing) {
            $this->error('CSV is missing columns: ' . implode(', ', $missing));
            fclose($handle);
            return self::FAILURE;
        }

        $inserted = 0;
        $skipped  = [];
        $row      = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            $record = array_combine($headers, array_map('trim', $data));

            $phone  = preg_replace('/\D/', '', $record['phone'] ?? '');
            $amount = (float) ($record['amount'] ?? 0);
            $year   = (int)   ($record['year']   ?? 0);
            $label  = $record['label'] ?? '';

            if (! $phone || ! $label || ! $year || $amount == 0) {
                $skipped[] = "Row $row: empty or invalid data — skipped";
                continue;
            }

            // Match user by phone (strip non-digits for comparison)
            $user = User::all()->first(function ($u) use ($phone) {
                return preg_replace('/\D/', '', $u->phone ?? '') === $phone;
            });

            if (! $user) {
                $skipped[] = "Row $row: no member found for phone $phone — skipped";
                continue;
            }

            MemberLegacyBalance::create([
                'user_id' => $user->id,
                'label'   => $label,
                'year'    => $year,
                'amount'  => $amount,
            ]);

            $inserted++;
        }

        fclose($handle);

        $this->info("Imported $inserted rows.");

        if ($skipped) {
            $this->warn(count($skipped) . ' rows skipped:');
            foreach ($skipped as $msg) {
                $this->line("  $msg");
            }
        }

        return self::SUCCESS;
    }
}
