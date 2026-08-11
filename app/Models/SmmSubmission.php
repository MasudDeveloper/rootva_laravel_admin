<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmmSubmission extends Model
{
    protected $table = 'smm_submissions';

    protected $fillable = [
        'user_id',
        'task_type',
        'input_field_1',
        'input_field_2',
        'price',
        'status',
        'admin_feedback'
    ];

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }
}
