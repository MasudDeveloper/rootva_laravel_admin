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
        if (!Schema::hasTable('app_crash_reports')) {
            Schema::create('app_crash_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_phone', 50)->nullable();
                $table->string('app_version', 50)->nullable();
                $table->string('device_model', 150)->nullable();
                $table->string('android_version', 50)->nullable();
                $table->string('exception_class', 255)->nullable();
                $table->text('exception_message')->nullable();
                $table->string('crash_location', 255)->nullable(); // e.g. CourseActivity.java:285
                $table->longText('stack_trace')->nullable();
                $table->string('status', 50)->default('Unresolved'); // Unresolved, Investigating, Resolved
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_crash_reports');
    }
};
