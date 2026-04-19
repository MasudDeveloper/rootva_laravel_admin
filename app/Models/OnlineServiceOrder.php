<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineServiceOrder extends Model
{
    protected $table = 'online_service_orders';
    public $timestamps = false; // Based on old DB structure check
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
