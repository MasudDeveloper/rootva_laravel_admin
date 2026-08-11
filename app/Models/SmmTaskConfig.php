<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmmTaskConfig extends Model
{
    protected $table = 'smm_tasks_config';
    protected $primaryKey = 'task_type';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'task_type',
        'name',
        'rate',
        'status',
        'notice',
        'video_url',
        'daily_password',
        'required_fields'
    ];

    protected $casts = [
        'rate' => 'double',
        'required_fields' => 'array'
    ];
}
