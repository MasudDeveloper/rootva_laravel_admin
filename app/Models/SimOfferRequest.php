<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimOfferRequest extends Model
{
    protected $table = 'sim_offer_requests';
    protected $guarded = [];

    public function offer()
    {
        return $this->belongsTo(SimOffer::class, 'offer_id');
    }

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }
}
