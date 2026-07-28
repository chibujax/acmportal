<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_pledges', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('donor_name')->nullable()->after('user_id');
            $table->decimal('received_amount', 10, 2)->default(0)->after('shared_with_spouse');
        });
    }

    public function down(): void
    {
        Schema::table('member_pledges', function (Blueprint $table) {
            $table->dropColumn(['donor_name', 'received_amount']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
