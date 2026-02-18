<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dues_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g. "Annual Dues 2025"
            $table->enum('type', ['yearly_dues', 'donation', 'event_levy'])->default('yearly_dues');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('payment_options', ['once', 'monthly', 'installments'])->default('once');
            $table->integer('installment_count')->nullable(); // if installments
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'closed', 'draft'])->default('draft');
            $table->boolean('send_reminders')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dues_cycles');
    }
};
