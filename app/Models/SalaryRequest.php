<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryRequest extends Model
{
    protected $table = 'salary_requests';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'request_type',
        'status',
        'requested_at',
        'approved_at',
        'admin_note'
    ];
}
