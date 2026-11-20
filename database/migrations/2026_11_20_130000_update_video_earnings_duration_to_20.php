<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('video_earnings')) {
            DB::table('video_earnings')
                ->where('duration', '>=', 60)
                ->orWhereNull('duration')
                ->update(['duration' => 20]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('video_earnings')) {
            DB::table('video_earnings')
                ->where('duration', 20)
                ->update(['duration' => 60]);
        }
    }
};
