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
        if (Schema::hasTable('payment_number') && !Schema::hasColumn('payment_number', 'bangla_qr_support_link')) {
            Schema::table('payment_number', function (Blueprint $table) {
                $table->string('bangla_qr_support_link', 255)->nullable()->after('bangla_qr');
            });
        }

        if (Schema::hasTable('social_links') && !Schema::hasColumn('social_links', 'bangla_qr_support_link')) {
            Schema::table('social_links', function (Blueprint $table) {
                $table->string('bangla_qr_support_link', 255)->nullable()->after('support_verify');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payment_number') && Schema::hasColumn('payment_number', 'bangla_qr_support_link')) {
            Schema::table('payment_number', function (Blueprint $table) {
                $table->dropColumn('bangla_qr_support_link');
            });
        }

        if (Schema::hasTable('social_links') && Schema::hasColumn('social_links', 'bangla_qr_support_link')) {
            Schema::table('social_links', function (Blueprint $table) {
                $table->dropColumn('bangla_qr_support_link');
            });
        }
    }
};
