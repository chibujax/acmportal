<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('check_in_time');
            $table->enum('check_in_method', ['qr_scan', 'manual'])->default('qr_scan');
            $table->enum('status', ['present', 'late'])->default('present');  // late = after meeting_time + 15min
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['meeting_id', 'user_id']);  // one record per member per meeting
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
