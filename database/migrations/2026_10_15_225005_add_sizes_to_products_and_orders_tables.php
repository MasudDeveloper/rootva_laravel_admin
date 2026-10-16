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
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'sizes')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('sizes', 255)->nullable()->after('description');
            });
        }

        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'selected_size')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('selected_size', 100)->nullable()->after('product_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'sizes')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('sizes');
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'selected_size')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('selected_size');
            });
        }
    }
};
