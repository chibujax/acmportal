<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'excused' to the status enum
        DB::statement("ALTER TABLE attendance_records MODIFY COLUMN status ENUM('present','late','excused') NOT NULL DEFAULT 'present'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE attendance_records MODIFY COLUMN status ENUM('present','late') NOT NULL DEFAULT 'present'");
    }
};
