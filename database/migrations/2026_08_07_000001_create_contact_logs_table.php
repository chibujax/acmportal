<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // recipient
            $table->string('channel'); // sms, email
            $table->string('subject')->nullable(); // email only
            $table->text('message'); // fully rendered body actually sent, placeholders already substituted
            $table->foreignId('template_id')->nullable()->constrained('sms_templates')->nullOnDelete();
            $table->string('context'); // consecutive_absentee, meeting_absentee, bulk
            $table->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status'); // sent, failed
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_logs');
    }
};
