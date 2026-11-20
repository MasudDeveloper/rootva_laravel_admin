<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpsTransaction extends Model
{
    protected $table = 'eps_transactions';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'response_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }
}
