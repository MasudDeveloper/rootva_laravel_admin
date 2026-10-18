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
        if (Schema::hasTable('social_links')) {
            Schema::table('social_links', function (Blueprint $table) {
                if (!Schema::hasColumn('social_links', 'telegram_customer_group')) {
                    $table->string('telegram_customer_group', 255)->nullable()->after('telegram_group');
                }
                if (!Schema::hasColumn('social_links', 'telegram_business_group')) {
                    $table->string('telegram_business_group', 255)->nullable()->after('telegram_customer_group');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('social_links')) {
            Schema::table('social_links', function (Blueprint $table) {
                if (Schema::hasColumn('social_links', 'telegram_customer_group')) {
                    $table->dropColumn('telegram_customer_group');
                }
                if (Schema::hasColumn('social_links', 'telegram_business_group')) {
                    $table->dropColumn('telegram_business_group');
                }
            });
        }
    }
};
