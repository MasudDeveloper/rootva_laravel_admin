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
        if (Schema::hasTable('payment_number')) {
            Schema::table('payment_number', function (Blueprint $table) {
                if (!Schema::hasColumn('payment_number', 'bkash_enabled')) {
                    $table->tinyInteger('bkash_enabled')->default(1)->after('bkash');
                }
                if (!Schema::hasColumn('payment_number', 'nagad_enabled')) {
                    $table->tinyInteger('nagad_enabled')->default(1)->after('nagad');
                }
                if (!Schema::hasColumn('payment_number', 'rocket_enabled')) {
                    $table->tinyInteger('rocket_enabled')->default(1)->after('rocket');
                }
                if (!Schema::hasColumn('payment_number', 'upay_enabled')) {
                    $table->tinyInteger('upay_enabled')->default(1)->after('upay');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payment_number')) {
            Schema::table('payment_number', function (Blueprint $table) {
                $cols = ['bkash_enabled', 'nagad_enabled', 'rocket_enabled', 'upay_enabled'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('payment_number', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
