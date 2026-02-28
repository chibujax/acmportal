<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_pledges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dues_cycle_id')->constrained()->cascadeOnDelete();
            $table->decimal('pledged_amount', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'dues_cycle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_pledges');
    }
};
