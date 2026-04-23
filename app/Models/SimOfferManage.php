<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimOfferManage extends Model
{
    protected $table = 'sim_offer_manage';
    protected $fillable = ['status', 'notice_text'];
    public $timestamps = false;
}
