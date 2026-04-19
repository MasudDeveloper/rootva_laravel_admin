<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewJob extends Model
{
    protected $table = 'review_job';
    public $timestamps = false;
    protected $guarded = [];

    public function submissions()
    {
        return $this->hasMany(ReviewSubmission::class, 'job_id');
    }
}
