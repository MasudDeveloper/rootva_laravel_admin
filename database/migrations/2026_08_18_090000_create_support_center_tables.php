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
        Schema::create('support_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('designation');
            $table->text('description')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('fb_link')->nullable();
            $table->string('wa_link')->nullable();
            $table->string('tg_link')->nullable();
            $table->string('phone_link')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('support_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('link')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_members');
        Schema::dropIfExists('support_services');
    }
};
