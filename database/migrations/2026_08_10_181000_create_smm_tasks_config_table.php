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
        Schema::create('smm_tasks_config', function (Blueprint $table) {
            $table->string('task_type')->primary(); // gmail, facebook, instagram, whatsapp, telegram
            $table->string('name');
            $table->double('rate', 8, 2)->default(0.00);
            $table->string('status')->default('active'); // active, inactive
            $table->text('notice')->nullable();
            $table->text('video_url')->nullable();
            $table->string('daily_password')->nullable();
            $table->json('required_fields')->nullable(); // e.g. ["gmail_address", "password"]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smm_tasks_config');
    }
};
