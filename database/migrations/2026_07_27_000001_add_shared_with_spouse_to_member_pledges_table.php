<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_pledges', function (Blueprint $table) {
            $table->boolean('shared_with_spouse')->default(false)->after('pledged_amount');
        });
    }

    public function down(): void
    {
        Schema::table('member_pledges', function (Blueprint $table) {
            $table->dropColumn('shared_with_spouse');
        });
    }
};
