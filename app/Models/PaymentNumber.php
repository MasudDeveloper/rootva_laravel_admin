<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentNumber extends Model
{
    protected $table = 'payment_number';
    public $timestamps = false;
    protected $guarded = [];
}
