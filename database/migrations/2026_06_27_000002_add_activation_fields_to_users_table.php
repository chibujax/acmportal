<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('activation_token', 64)->nullable()->after('status');
            $table->timestamp('activation_token_expires_at')->nullable()->after('activation_token');
            $table->timestamp('activation_invited_at')->nullable()->after('activation_token_expires_at');

            $table->index('activation_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['activation_token']);
            $table->dropColumn(['activation_token', 'activation_token_expires_at', 'activation_invited_at']);
        });
    }
};
