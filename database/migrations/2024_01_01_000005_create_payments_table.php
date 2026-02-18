<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dues_cycle_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->enum('method', ['stripe', 'paystack', 'manual', 'bank_transfer'])->default('manual');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');

            // Gateway references
            $table->string('gateway_reference')->nullable();   // Stripe/Paystack transaction ref
            $table->string('gateway_response')->nullable();    // raw status from gateway
            $table->json('gateway_payload')->nullable();       // full webhook payload

            // Manual payment fields (Financial Secretary)
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receipt_number')->nullable();
            $table->text('notes')->nullable();
            $table->date('payment_date')->nullable();          // actual cash/bank date
            $table->string('proof_of_payment')->nullable();    // file path

            // Installment tracking
            $table->integer('installment_number')->nullable();
            $table->integer('total_installments')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
