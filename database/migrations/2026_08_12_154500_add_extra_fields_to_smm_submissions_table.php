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
        Schema::table('smm_submissions', function (Blueprint $table) {
            $table->text('input_field_3')->nullable()->after('input_field_2');
            $table->text('input_field_4')->nullable()->after('input_field_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('smm_submissions', function (Blueprint $table) {
            $table->dropColumn(['input_field_3', 'input_field_4']);
        });
    }
};
