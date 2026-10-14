<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppCrashReport extends Model
{
    protected $table = 'app_crash_reports';

    protected $fillable = [
        'user_id',
        'user_phone',
        'app_version',
        'device_model',
        'android_version',
        'exception_class',
        'exception_message',
        'crash_location',
        'stack_trace',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }
}
