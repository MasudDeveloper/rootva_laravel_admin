<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadershipRewardRequest extends Model
{
    protected $table = 'leadership_reward_requests';
    public $timestamps = false;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }
}
