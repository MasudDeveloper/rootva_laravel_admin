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
        if (!Schema::hasTable('video_earnings')) {
            Schema::create('video_earnings', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('youtube_url');
                $table->string('video_id')->nullable();
                $table->integer('duration')->default(60); // Minimum 60 seconds
                $table->integer('serial')->default(1);
                $table->boolean('status')->default(true);
                $table->timestamps();
            });

            // Seed initial 10 videos for Video Earning
            $initialVideos = [
                ['title' => 'অনলাইন থেকে আয় করার সহজ উপায়', 'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'video_id' => 'dQw4w9WgXcQ', 'duration' => 60, 'serial' => 1],
                ['title' => 'রুটবা অ্যাপে কাজ করার নিয়মাবলি', 'youtube_url' => 'https://www.youtube.com/watch?v=kJQP7kiw5Fk', 'video_id' => 'kJQP7kiw5Fk', 'duration' => 60, 'serial' => 2],
                ['title' => 'ফ্রিল্যান্সিং ক্যারিয়ার শুরুর গাইডলাইন', 'youtube_url' => 'https://www.youtube.com/watch?v=fJ9rUzIMcZQ', 'video_id' => 'fJ9rUzIMcZQ', 'duration' => 60, 'serial' => 3],
                ['title' => 'ডিজিটাল মার্কেটিং বেসিক টিউটোরিয়াল', 'youtube_url' => 'https://www.youtube.com/watch?v=3JZ_D3ELwOQ', 'video_id' => '3JZ_D3ELwOQ', 'duration' => 60, 'serial' => 4],
                ['title' => 'সোশ্যাল মিডিয়া থেকে ইনকাম ট্রিক্স', 'youtube_url' => 'https://www.youtube.com/watch?v=RgKAFK5djSk', 'video_id' => 'RgKAFK5djSk', 'duration' => 60, 'serial' => 5],
                ['title' => 'সহজ স্কিল শিখে পার্ট টাইম ইনকাম', 'youtube_url' => 'https://www.youtube.com/watch?v=2Vv-BfVoq4g', 'video_id' => '2Vv-BfVoq4g', 'duration' => 60, 'serial' => 6],
                ['title' => 'স্মার্টফোন দিয়ে ঘরে বসে আয় করুন', 'youtube_url' => 'https://www.youtube.com/watch?v=L_LUpnjgPso', 'video_id' => 'L_LUpnjgPso', 'duration' => 60, 'serial' => 7],
                ['title' => 'সফল উদ্যোক্তা হওয়ার সহজ পরামর্শ', 'youtube_url' => 'https://www.youtube.com/watch?v=uelHwf8o7_U', 'video_id' => 'uelHwf8o7_U', 'duration' => 60, 'serial' => 8],
                ['title' => 'মাইক্রোজব করে দ্রুত ইনকাম করার টিপস', 'youtube_url' => 'https://www.youtube.com/watch?v=kXYiU_JCYtU', 'video_id' => 'kXYiU_JCYtU', 'duration' => 60, 'serial' => 9],
                ['title' => 'ইনকাম বাড়ানোর স্পেশাল টেকনিক', 'youtube_url' => 'https://www.youtube.com/watch?v=9bZkp7q19f0', 'video_id' => '9bZkp7q19f0', 'duration' => 60, 'serial' => 10],
            ];

            foreach ($initialVideos as $video) {
                DB::table('video_earnings')->insert(array_merge($video, [
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        if (Schema::hasTable('sign_up') && !Schema::hasColumn('sign_up', 'video_earning')) {
            Schema::table('sign_up', function (Blueprint $table) {
                $table->integer('video_earning')->default(2);
            });
        }

        if (Schema::hasTable('job_status') && !Schema::hasColumn('job_status', 'video_earning')) {
            Schema::table('job_status', function (Blueprint $table) {
                $table->boolean('video_earning')->default(true);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_earnings');

        if (Schema::hasTable('sign_up') && Schema::hasColumn('sign_up', 'video_earning')) {
            Schema::table('sign_up', function (Blueprint $table) {
                $table->dropColumn('video_earning');
            });
        }

        if (Schema::hasTable('job_status') && Schema::hasColumn('job_status', 'video_earning')) {
            Schema::table('job_status', function (Blueprint $table) {
                $table->dropColumn('video_earning');
            });
        }
    }
};
