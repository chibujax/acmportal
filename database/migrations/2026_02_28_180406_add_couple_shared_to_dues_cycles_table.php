<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dues_cycles', function (Blueprint $table) {
            $table->boolean('couple_shared')->default(false)->after('send_reminders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dues_cycles', function (Blueprint $table) {
            $table->dropColumn('couple_shared');
        });
    }
};
