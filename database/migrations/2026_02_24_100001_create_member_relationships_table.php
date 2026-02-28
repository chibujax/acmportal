<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id_1')->constrained('users')->cascadeOnDelete();
            $table->foreignId('member_id_2')->constrained('users')->cascadeOnDelete();
            $table->enum('relationship_type', ['spouse'])->default('spouse');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->nullable();

            // A member can only have one spouse link
            $table->unique(['member_id_1', 'relationship_type']);
            $table->unique(['member_id_2', 'relationship_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_relationships');
    }
};
