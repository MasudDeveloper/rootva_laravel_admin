<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponClaim extends Model
{
    protected $table = 'coupon_claims';
    public $timestamps = false;

    protected $fillable = [
        'coupon_id',
        'user_id',
        'code',
        'amount',
        'created_at',
        'date',
    ];

    protected $casts = [
        'amount' => 'double',
        'coupon_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }
}
