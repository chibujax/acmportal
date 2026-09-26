<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable and unpopulated by default - null means "existing/legacy member,
            // full obligation" (see User::obligationFor()). Backfilling for existing
            // members is a manual one-off query, not part of this migration.
            $table->date('join_date')->nullable()->after('date_of_birth');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('join_date');
        });
    }
};
