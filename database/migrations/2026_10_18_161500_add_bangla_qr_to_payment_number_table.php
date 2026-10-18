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
        if (Schema::hasTable('payment_number') && !Schema::hasColumn('payment_number', 'bangla_qr')) {
            Schema::table('payment_number', function (Blueprint $table) {
                $table->string('bangla_qr', 50)->nullable()->after('upay');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payment_number') && Schema::hasColumn('payment_number', 'bangla_qr')) {
            Schema::table('payment_number', function (Blueprint $table) {
                $table->dropColumn('bangla_qr');
            });
        }
    }
};
