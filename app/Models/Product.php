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

        if (preg_match('#/(uploads/.*)$#i', $value, $matches)) {
            return '/' . $matches[1];
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $path = parse_url($value, PHP_URL_PATH);
            return $path ? preg_replace('#^/public/#', '/', $path) : $value;
        }

        return '/uploads/products/' . $value;
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
