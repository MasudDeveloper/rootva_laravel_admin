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
        Schema::table('sim_offers', function (Blueprint $table) {
            $table->string('offer_type', 50)->default('drive')->after('offer_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sim_offers', function (Blueprint $table) {
            $table->dropColumn('offer_type');
        });
    }
};
