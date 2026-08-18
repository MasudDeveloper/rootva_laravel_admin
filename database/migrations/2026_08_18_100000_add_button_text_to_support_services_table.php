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
        Schema::table('support_services', function (Blueprint $table) {
            $table->string('button_text')->default('WhatsApp সাপোর্ট')->after('link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_services', function (Blueprint $table) {
            $table->dropColumn('button_text');
        });
    }
};
