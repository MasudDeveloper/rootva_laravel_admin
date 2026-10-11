<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->index();
                $table->string('title')->nullable();
                $table->decimal('amount', 10, 2)->default(10.00);
                $table->integer('max_uses')->default(1); // Default: 1 (first person to claim)
                $table->integer('used_count')->default(0);
                $table->dateTime('expires_at')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });

            // Sample meeting coupons
            $sampleCoupons = [
                ['code' => 'MEET10', 'title' => 'মিটিং জয়েন বোনাস', 'amount' => 10.00, 'max_uses' => 1, 'used_count' => 0],
                ['code' => 'ROOTVA20', 'title' => 'স্পেশাল মিটিং গিফট', 'amount' => 20.00, 'max_uses' => 1, 'used_count' => 0],
                ['code' => 'VIP50', 'title' => 'মেগা লাকি বোনাস', 'amount' => 50.00, 'max_uses' => 1, 'used_count' => 0],
            ];

            foreach ($sampleCoupons as $coupon) {
                DB::table('coupons')->insert(array_merge($coupon, [
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        if (!Schema::hasTable('coupon_claims')) {
            Schema::create('coupon_claims', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('coupon_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('code')->index();
                $table->decimal('amount', 10, 2);
                $table->string('created_at')->nullable();
                $table->string('date')->nullable();

                $table->unique(['coupon_id', 'user_id']);
            });
        }

        if (Schema::hasTable('job_status') && !Schema::hasColumn('job_status', 'coupon_bonus')) {
            Schema::table('job_status', function (Blueprint $table) {
                $table->boolean('coupon_bonus')->default(true);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_claims');
        Schema::dropIfExists('coupons');

        if (Schema::hasTable('job_status') && Schema::hasColumn('job_status', 'coupon_bonus')) {
            Schema::table('job_status', function (Blueprint $table) {
                $table->dropColumn('coupon_bonus');
            });
        }
    }
};
