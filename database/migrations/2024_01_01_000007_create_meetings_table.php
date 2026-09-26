<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');                                  // "February 2026 General Meeting"
            $table->date('meeting_date');
            $table->time('meeting_time')->default('18:00:00');
            $table->string('venue')->nullable();
            $table->text('description')->nullable();
            $table->string('qr_token', 64)->unique()->nullable();     // active check-in hash
            $table->timestamp('qr_expires_at')->nullable();           // when QR stops working
            $table->enum('status', ['scheduled', 'active', 'closed'])->default('scheduled');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
