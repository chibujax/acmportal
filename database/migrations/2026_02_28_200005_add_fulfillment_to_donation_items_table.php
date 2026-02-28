<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donation_items', function (Blueprint $table) {
            $table->boolean('is_fulfilled')->default(false)->after('notes');
            $table->timestamp('fulfilled_at')->nullable()->after('is_fulfilled');
            $table->foreignId('fulfilled_by')->nullable()->constrained('users')->nullOnDelete()->after('fulfilled_at');
        });
    }

    public function down(): void
    {
        Schema::table('donation_items', function (Blueprint $table) {
            $table->dropForeign(['fulfilled_by']);
            $table->dropColumn(['is_fulfilled', 'fulfilled_at', 'fulfilled_by']);
        });
    }
};
