<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobStatus extends Model
{
    protected $table = 'job_status';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'facebook' => 'boolean',
        'instagram' => 'boolean',
        'email' => 'boolean',
        'tiktok' => 'boolean',
        'review' => 'boolean',
        'ads' => 'boolean',
        'dollar' => 'boolean',
        'recharge' => 'boolean',
        'sim_offer' => 'boolean',
    ];
}
