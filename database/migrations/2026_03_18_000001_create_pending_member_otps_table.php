<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_member_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pending_member_id')->constrained()->cascadeOnDelete();
            $table->string('otp'); // hashed
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_member_otps');
    }
};
