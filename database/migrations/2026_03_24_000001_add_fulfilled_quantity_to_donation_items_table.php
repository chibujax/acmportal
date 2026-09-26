<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donation_items', function (Blueprint $table) {
            $table->string('fulfilled_quantity')->nullable()->after('fulfilled_by');
        });
    }

    public function down(): void
    {
        Schema::table('donation_items', function (Blueprint $table) {
            $table->dropColumn('fulfilled_quantity');
        });
    }
};
