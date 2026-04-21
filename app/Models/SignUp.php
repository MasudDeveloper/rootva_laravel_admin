<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SignUp extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'sign_up';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'number',
        'password',
        'email',
        'address',
        'profile_pic_url',
        'gender',
        'referCode',
        'referredBy',
        'is_verified',
        'wallet_balance',
        'voucher_balance',
        'math_game',
        'fcm_token',
        'api_token',
        'verified_at',
        'created_at',
        'verified_raw_time',
        'upline_changed_at',
        'verification_popup_shown',
    ];

    protected $hidden = [
        'password',
        'api_token',
    ];
}
