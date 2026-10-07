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

        if (preg_match('#/(uploads/.*)$#i', $value, $matches)) {
            return asset($matches[1]);
        }

        if (str_starts_with($value, '/')) {
            return asset(ltrim($value, '/'));
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $path = parse_url($value, PHP_URL_PATH);
            if ($path) {
                $path = preg_replace('#^/public/#', '/', $path);
                return asset(ltrim($path, '/'));
            }
            return $value;
        }

        return asset('uploads/bottom_banners/' . $value);
    }
}
