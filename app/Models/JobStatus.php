<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobStatus extends Model
{
    protected $table = 'job_status';
    public $timestamps = false;
    protected $fillable = [
        'facebook', 'instagram', 'email', 'tiktok', 'review', 'ads', 'dollar', 
        'recharge', 'sim_offer', 'microjob', 'job_post', 'spin_bonus', 
        'math_game', 'leadership', 'daily_bonus', 'weekly_salary', 
        'monthly_salary', 'leaderboard', 'reselling_shop', 'course', 
        'freelancing_course', 'online_service'
    ];

    protected $casts = [
        'facebook' => 'integer',
        'instagram' => 'integer',
        'email' => 'integer',
        'tiktok' => 'integer',
        'review' => 'integer',
        'ads' => 'integer',
        'dollar' => 'integer',
        'recharge' => 'integer',
        'sim_offer' => 'integer',
        'microjob' => 'integer',
        'job_post' => 'integer',
        'spin_bonus' => 'integer',
        'math_game' => 'integer',
        'leadership' => 'integer',
        'daily_bonus' => 'integer',
        'weekly_salary' => 'integer',
        'monthly_salary' => 'integer',
        'leaderboard' => 'integer',
        'reselling_shop' => 'integer',
        'course' => 'integer',
        'freelancing_course' => 'integer',
        'online_service' => 'integer',
    ];
}
