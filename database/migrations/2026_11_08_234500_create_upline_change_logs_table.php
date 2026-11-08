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
        Schema::create('upline_change_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('admin_name')->nullable();
            $table->string('admin_username')->nullable();
            
            $table->unsignedBigInteger('user_id')->index();
            $table->string('user_name')->nullable();
            $table->string('user_number')->nullable();
            $table->string('user_refer_code')->nullable();

            $table->string('old_upline_code')->nullable();
            $table->string('old_upline_name')->nullable();
            $table->string('old_upline_number')->nullable();

            $table->string('new_upline_code')->nullable();
            $table->string('new_upline_name')->nullable();
            $table->string('new_upline_number')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upline_change_logs');
    }
};

