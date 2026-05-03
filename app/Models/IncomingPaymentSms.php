<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomingPaymentSms extends Model
{
    protected $table = 'incoming_payment_sms';
    public $timestamps = false;
    protected $guarded = [];
}
