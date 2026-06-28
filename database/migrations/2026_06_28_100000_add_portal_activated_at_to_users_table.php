<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('portal_activated_at')->nullable()->after('status');
        });

        // Members who are already 'active' have already set their password —
        // treat their account updated_at as the activation timestamp.
        DB::table('users')
            ->where('status', 'active')
            ->whereNull('portal_activated_at')
            ->update(['portal_activated_at' => DB::raw('updated_at')]);

        // Members who are 'inactive' are real community members who simply
        // haven't set their portal password yet. Make them active in the
        // community sense; portal_activated_at stays null until they join.
        DB::table('users')
            ->where('status', 'inactive')
            ->update(['status' => 'active']);
    }

    public function down(): void
    {
        // Reverse: anyone with portal_activated_at null was previously inactive
        DB::table('users')
            ->whereNull('portal_activated_at')
            ->update(['status' => 'inactive']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('portal_activated_at');
        });
    }
};
