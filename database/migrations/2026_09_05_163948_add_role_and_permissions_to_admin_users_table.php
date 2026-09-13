<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_users', 'name')) {
                $table->string('name')->nullable()->after('username');
            }
            if (!Schema::hasColumn('admin_users', 'role')) {
                $table->string('role')->default('sub_admin')->after('password'); // super_admin, sub_admin
            }
            if (!Schema::hasColumn('admin_users', 'permissions')) {
                $table->json('permissions')->nullable()->after('role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn(['name', 'role', 'permissions']);
        });
    }
};
