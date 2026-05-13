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

    public function getFullImageUrlAttribute()
    {
        $url = $this->image_url;

        // Use default image if none provided
        if (!$url) {
            $url = '1752247689_images%20(5).jpeg';
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // Review images are stored in service/review_jobs/reviewJobImage/ on the admin subdomain
        return "https://admin.rootvabd.com/service/review_jobs/reviewJobImage/" . $url;
    }
}
