<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->enum('channel', ['sms', 'email'])->default('sms')->after('name');
            $table->string('subject')->nullable()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->dropColumn(['channel', 'subject']);
        });
    }
};
