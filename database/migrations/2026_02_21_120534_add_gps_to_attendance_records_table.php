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
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->decimal('gps_lat', 10, 7)->nullable()->after('notes');
            $table->decimal('gps_lng', 10, 7)->nullable()->after('gps_lat');
            $table->unsignedSmallInteger('gps_distance')->nullable()->after('gps_lng');
            $table->boolean('location_mismatch')->default(false)->after('gps_distance');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['gps_lat', 'gps_lng', 'gps_distance', 'location_mismatch']);
        });
    }
};
