<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawRequest extends Model
{
    protected $table = 'withdraw_requests';
    public $timestamps = false;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }
}
