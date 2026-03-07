<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change role column from enum to string (supports super_admin, admin, member)
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->change();
        });

        // Migrate existing roles:
        // admin -> super_admin
        // financial_secretary -> admin
        DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin']);
        DB::table('users')->where('role', 'financial_secretary')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        // Reverse migration data
        DB::table('users')->where('role', 'admin')->update(['role' => 'financial_secretary']);
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'financial_secretary', 'member'])->default('member')->change();
        });
    }
};
