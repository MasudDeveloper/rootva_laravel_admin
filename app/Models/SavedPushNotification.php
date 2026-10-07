<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedPushNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'image',
        'link',
    ];

    public function getImageAttribute($value)
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

        return asset('uploads/notifications/' . ltrim($value, '/'));
    }
}
