<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_minutes_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_minutes_id')->constrained('meeting_minutes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('view_count')->default(1);
            $table->timestamp('first_viewed_at')->useCurrent();
            $table->timestamp('last_viewed_at')->useCurrent();

            // One row per member per minutes — view_count tracks repeat visits.
            $table->unique(['meeting_minutes_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_minutes_views');
    }
};
