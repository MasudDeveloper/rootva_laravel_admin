<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BottomBanner extends Model
{
    protected $table = 'bottom_banners';
    public $timestamps = false;
    protected $guarded = [];

    public function getImageUrlAttribute($value)
    {
        if (empty($value)) return null;

        $url = null;
        if (str_starts_with($value, 'https://') && !str_contains($value, '127.0.0.1') && !str_contains($value, 'localhost')) {
            $url = $value;
        } elseif (str_starts_with($value, 'http://') && !str_contains($value, '127.0.0.1') && !str_contains($value, 'localhost')) {
            $url = str_replace('http://', 'https://', $value);
        } elseif (preg_match('#/(uploads/.*)$#i', $value, $matches)) {
            $url = asset($matches[1]);
        } elseif (str_starts_with($value, '/')) {
            $url = asset(ltrim($value, '/'));
        } else {
            $url = asset('uploads/bottom_banners/' . ltrim($value, '/'));
        }

        return str_replace(' ', '%20', $url);
    }
}
