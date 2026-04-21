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
            $table->index('referCode');
            $table->index('referredBy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sign_up', function (Blueprint $table) {
            $table->dropIndex(['referCode']);
            $table->dropIndex(['referredBy']);
        });
    }
};
