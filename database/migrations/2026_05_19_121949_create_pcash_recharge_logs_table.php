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
        Schema::create('pcash_recharge_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('api_id')->unique(); // The uniqid sent to API
            $table->string('number');
            $table->string('operator');
            $table->decimal('amount', 8, 2);
            $table->integer('type')->default(1);
            $table->string('api_status')->default('pending'); // pending, success, failed
            $table->string('api_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcash_recharge_logs');
    }
};
