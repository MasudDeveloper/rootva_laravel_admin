<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopupBanner extends Model
{
    protected $table = 'popup_banner';
    public $timestamps = false;
    protected $guarded = [];

    public function getImageUrlAttribute($value)
    {
        if (empty($value)) return null;

        if (str_starts_with($value, 'https://') && !str_contains($value, '127.0.0.1') && !str_contains($value, 'localhost')) {
            return $value;
        }

        if (str_starts_with($value, 'http://') && !str_contains($value, '127.0.0.1') && !str_contains($value, 'localhost')) {
            return str_replace('http://', 'https://', $value);
        }

        if (preg_match('#/(uploads/.*)$#i', $value, $matches)) {
            return asset($matches[1]);
        }

        if (str_starts_with($value, '/')) {
            return asset(ltrim($value, '/'));
        }

        return asset('uploads/popups/' . ltrim($value, '/'));
    }
}
