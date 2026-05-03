<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $guarded = [];
    public $timestamps = false;
    
    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }
}
