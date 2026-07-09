<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpinHistory extends Model
{
    use HasFactory;

    protected $table = 'spin_history';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'amount',
        'is_free_spin',
        'created_at',
    ];
}
