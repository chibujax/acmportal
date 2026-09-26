<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_minutes_views', function (Blueprint $table) {
            // view_count = times opened in the browser; download_count = times downloaded.
            $table->unsignedInteger('view_count')->default(0)->change();
            $table->unsignedInteger('download_count')->default(0)->after('view_count');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_minutes_views', function (Blueprint $table) {
            $table->dropColumn('download_count');
            $table->unsignedInteger('view_count')->default(1)->change();
        });
    }
};
