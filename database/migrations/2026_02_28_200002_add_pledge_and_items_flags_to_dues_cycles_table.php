<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dues_cycles', function (Blueprint $table) {
            $table->boolean('is_pledge_based')->default(false)->after('couple_shared');
            $table->boolean('accepts_items')->default(false)->after('is_pledge_based');
        });
    }

    public function down(): void
    {
        Schema::table('dues_cycles', function (Blueprint $table) {
            $table->dropColumn(['is_pledge_based', 'accepts_items']);
        });
    }
};
