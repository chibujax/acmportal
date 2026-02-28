<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dues_cycle_id')->constrained()->cascadeOnDelete();
            $table->enum('item_type', ['money', 'other'])->default('other');
            $table->string('description');
            $table->string('quantity', 100)->nullable();
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->string('currency', 3)->default('GBP');
            $table->date('donation_date');
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_items');
    }
};
