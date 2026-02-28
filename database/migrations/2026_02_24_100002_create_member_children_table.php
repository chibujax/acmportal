<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_children', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('father_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('mother_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('added_by')->constrained('users');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_children');
    }
};
