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
                if (!Schema::hasColumn('payment_number', 'eps_enabled')) {
                    $table->tinyInteger('eps_enabled')->default(0)->after('verify_amount');
                }
                if (!Schema::hasColumn('payment_number', 'manual_payment_enabled')) {
                    $table->tinyInteger('manual_payment_enabled')->default(1)->after('eps_enabled');
                }
                if (!Schema::hasColumn('payment_number', 'bangla_qr_enabled')) {
                    $table->tinyInteger('bangla_qr_enabled')->default(1)->after('manual_payment_enabled');
                }
                if (!Schema::hasColumn('payment_number', 'eps_auto_disable_others')) {
                    $table->tinyInteger('eps_auto_disable_others')->default(1)->after('bangla_qr_enabled');
                }
                if (!Schema::hasColumn('payment_number', 'eps_username')) {
                    $table->string('eps_username', 191)->nullable()->after('eps_auto_disable_others');
                }
                if (!Schema::hasColumn('payment_number', 'eps_password')) {
                    $table->string('eps_password', 191)->nullable()->after('eps_username');
                }
                if (!Schema::hasColumn('payment_number', 'eps_hash_key')) {
                    $table->text('eps_hash_key')->nullable()->after('eps_password');
                }
                if (!Schema::hasColumn('payment_number', 'eps_store_id')) {
                    $table->string('eps_store_id', 191)->nullable()->after('eps_hash_key');
                }
                if (!Schema::hasColumn('payment_number', 'eps_merchant_id')) {
                    $table->string('eps_merchant_id', 191)->nullable()->after('eps_store_id');
                }
                if (!Schema::hasColumn('payment_number', 'eps_sandbox')) {
                    $table->tinyInteger('eps_sandbox')->default(0)->after('eps_merchant_id');
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
                $cols = [
                    'eps_enabled', 'manual_payment_enabled', 'bangla_qr_enabled', 'eps_auto_disable_others',
                    'eps_username', 'eps_password', 'eps_hash_key', 'eps_store_id', 'eps_merchant_id', 'eps_sandbox'
                ];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('payment_number', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
