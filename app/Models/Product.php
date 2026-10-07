<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'images' => 'array',
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
            return '/' . $matches[1];
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        return '/uploads/products/' . ltrim($value, '/');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', 1);
    }
}
