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
}
