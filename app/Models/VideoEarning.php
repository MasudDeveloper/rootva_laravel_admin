<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoEarning extends Model
{
    protected $table = 'video_earnings';

    protected $fillable = [
        'title',
        'youtube_url',
        'video_id',
        'duration',
        'serial',
        'status',
    ];

    protected $casts = [
        'duration' => 'integer',
        'serial' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Helper to extract YouTube video ID from URL
     */
    public static function extractVideoId($url)
    {
        if (empty($url)) {
            return null;
        }

        // Standard watch?v=...
        if (preg_match('/[?&]v=([^&#]+)/', $url, $matches)) {
            return $matches[1];
        }

        // youtu.be/...
        if (preg_match('/youtu\.be\/([^?&#]+)/', $url, $matches)) {
            return $matches[1];
        }

        // embed/...
        if (preg_match('/embed\/([^?&#]+)/', $url, $matches)) {
            return $matches[1];
        }

        // If user directly passed an 11-char ID
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', trim($url))) {
            return trim($url);
        }

        return null;
    }
}
