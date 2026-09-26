<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support MODIFY COLUMN or typed ENUMs – skip on SQLite
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE attendance_records MODIFY COLUMN check_in_method ENUM('qr_scan', 'manual', 'excused') NOT NULL DEFAULT 'qr_scan'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE attendance_records MODIFY COLUMN check_in_method ENUM('qr_scan', 'manual') NOT NULL DEFAULT 'qr_scan'");
        }
    }
};
