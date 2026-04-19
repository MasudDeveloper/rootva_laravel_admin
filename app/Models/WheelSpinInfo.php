<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WheelSpinInfo extends Model
{
    protected $table = 'wheel_spin_info';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }
}
