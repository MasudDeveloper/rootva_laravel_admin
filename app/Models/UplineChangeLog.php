<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UplineChangeLog extends Model
{
    protected $table = 'upline_change_logs';

    protected $guarded = [];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }
}

