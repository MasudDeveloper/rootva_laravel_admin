<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewSubmission extends Model
{
    protected $table = 'review_submissions';
    public $timestamps = false;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'worker_user_id');
    }

    public function job()
    {
        return $this->belongsTo(ReviewJob::class, 'job_id');
    }
}
