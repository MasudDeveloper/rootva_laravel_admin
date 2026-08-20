<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MicrojobSubmission extends Model
{
    protected $table = 'microjob_submissions';
    public $timestamps = false; // Legacy table uses custom timestamps
    protected $guarded = [];

    public function job()
    {
        return $this->belongsTo(Microjob::class, 'job_id');
    }

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'worker_user_id');
    }

    public function getProofImageFullUrlAttribute()
    {
        if (!$this->proof_image_url) {
            return null;
        }

        $url = $this->proof_image_url;
        
        // If it's already a full URL, return it
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // If it doesn't have the uploads/proofs prefix, add it (based on user link)
        if (!str_starts_with($url, 'uploads/proofs/')) {
            $url = 'uploads/proofs/' . $url;
        }

        return "https://rootvaadmin.rootvabd.com/public/" . $url;
    }
}
