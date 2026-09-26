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
            // Time after which check-ins are flagged as late (e.g. 18:20)
            $table->time('late_after_time')->nullable()->after('meeting_time');
            // Meeting end time — QR auto-expires at this time
            $table->time('meeting_end_time')->nullable()->after('late_after_time');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['late_after_time', 'meeting_end_time']);
        });
    }
};
