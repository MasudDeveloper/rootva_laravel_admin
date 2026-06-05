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
        if (Schema::hasTable('transactions') && !Schema::hasColumn('transactions', 'support_admin_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('support_admin_id')->nullable()->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'support_admin_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('support_admin_id');
            });
        }
    }
};
