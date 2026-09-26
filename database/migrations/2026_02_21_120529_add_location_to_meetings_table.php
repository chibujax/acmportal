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
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('venue_postcode')->nullable()->after('venue');
            $table->decimal('venue_lat', 10, 7)->nullable()->after('venue_postcode');
            $table->decimal('venue_lng', 10, 7)->nullable()->after('venue_lat');
            $table->unsignedSmallInteger('venue_radius')->default(150)->after('venue_lng');
            $table->enum('gps_failure_action', ['reject', 'flag'])->default('reject')->after('venue_radius');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['venue_postcode', 'venue_lat', 'venue_lng', 'venue_radius', 'gps_failure_action']);
        });
    }
};
