<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table = 'coupons';

    protected $fillable = [
        'code',
        'title',
        'amount',
        'max_uses',
        'used_count',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'amount' => 'double',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'status' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function claims()
    {
        return $this->hasMany(CouponClaim::class, 'coupon_id');
    }

    public function isAvailable(): bool
    {
        if (!$this->status) {
            return false;
        }

        if ($this->used_count >= $this->max_uses) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
