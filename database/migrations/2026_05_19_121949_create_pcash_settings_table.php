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
        Schema::create('pcash_settings', function (Blueprint $table) {
            $table->id();
            $table->string('api_user')->nullable();
            $table->string('api_key')->nullable();
            $table->string('default_service_code')->default('64');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcash_settings');
    }
};
