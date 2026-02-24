<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE attendance_records MODIFY COLUMN check_in_method ENUM('qr_scan', 'manual', 'excused') NOT NULL DEFAULT 'qr_scan'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE attendance_records MODIFY COLUMN check_in_method ENUM('qr_scan', 'manual') NOT NULL DEFAULT 'qr_scan'");
    }
};
