<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimOffer extends Model
{
    protected $table = 'sim_offers';
    public $timestamps = false; // The schema showed created_at but not updated_at
    protected $guarded = [];
}
