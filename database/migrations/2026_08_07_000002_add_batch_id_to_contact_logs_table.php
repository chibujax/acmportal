<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_logs', function (Blueprint $table) {
            // Groups every recipient of one send action together — timestamps
            // alone aren't reliable since each SMS/email is a real API call
            // and a batch of many recipients can span well beyond a second.
            $table->string('batch_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('contact_logs', function (Blueprint $table) {
            $table->dropColumn('batch_id');
        });
    }
};
