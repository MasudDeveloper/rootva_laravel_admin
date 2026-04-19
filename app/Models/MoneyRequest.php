<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoneyRequest extends Model
{
    protected $table = 'money_requests';
    public $timestamps = false;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }
}
