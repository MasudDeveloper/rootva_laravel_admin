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
        Schema::table('job_status', function (Blueprint $table) {
            $table->boolean('microjob')->default(true);
            $table->boolean('job_post')->default(true);
            $table->boolean('spin_bonus')->default(true);
            $table->boolean('math_game')->default(true);
            $table->boolean('leadership')->default(true);
            $table->boolean('daily_bonus')->default(true);
            $table->boolean('weekly_salary')->default(true);
            $table->boolean('monthly_salary')->default(true);
            $table->boolean('leaderboard')->default(true);
            $table->boolean('reselling_shop')->default(true);
            $table->boolean('course')->default(true);
            $table->boolean('freelancing_course')->default(true);
            $table->boolean('online_service')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_status', function (Blueprint $table) {
            $table->dropColumn([
                'microjob', 'job_post', 'spin_bonus', 'math_game', 'leadership', 
                'daily_bonus', 'weekly_salary', 'monthly_salary', 'leaderboard',
                'reselling_shop', 'course', 'freelancing_course', 'online_service'
            ]);
        });
    }
};
