<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Microjob extends Model
{
    protected $table = 'microjobs';
    public $timestamps = false;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }

    public function submissions()
    {
        return $this->hasMany(MicrojobSubmission::class, 'job_id');
    }

    public function getFullImageUrlAttribute()
    {
        if (!$this->image_url) {
            return null;
        }

        $url = $this->image_url;

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // Images are saved on the admin sub-domain as per instructions
        return "https://admin.rootvabd.com/service/microjobs/microjobImage/" . $url;
    }
}
