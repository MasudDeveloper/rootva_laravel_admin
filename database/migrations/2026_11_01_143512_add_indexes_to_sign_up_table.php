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
        Schema::table('sign_up', function (Blueprint $table) {
            $table->index('created_at', 'idx_signup_created_at');
            $table->index('verified_raw_time', 'idx_signup_verified_raw');
            $table->index('is_verified', 'idx_signup_is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sign_up', function (Blueprint $table) {
            $table->dropIndex('idx_signup_created_at');
            $table->dropIndex('idx_signup_verified_raw');
            $table->dropIndex('idx_signup_is_verified');
        });
    }
};
