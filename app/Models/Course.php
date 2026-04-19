<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'course_videos';
    public $timestamps = false;
    protected $guarded = [];
}
