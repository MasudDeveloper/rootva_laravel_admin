<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $table = 'social_links';
    
    // The table has created_at but not updated_at (based on schema)
    // and we usually update via ID. 
    // To be safe and simple, let's use guarded empty.
    protected $guarded = [];
    
    public $timestamps = false; // We'll handle manual created_at if needed, or let DB handle it.
}
