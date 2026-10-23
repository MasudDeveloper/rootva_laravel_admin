<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppCrashReport extends Model
{
    protected $table = 'app_crash_reports';

    protected $fillable = [
        'user_id',
        'user_phone',
        'app_version',
        'device_model',
        'android_version',
        'exception_class',
        'exception_message',
        'crash_location',
        'stack_trace',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }

    public function getMessageAttribute()
    {
        return $this->exception_message;
    }

    /**
     * Query crashes that are identical or similar in origin
     */
    public function getSimilarQuery($includeSelf = false)
    {
        $query = static::query();
        if (!$includeSelf) {
            $query->where('id', '!=', $this->id);
        }

        if (!empty($this->crash_location)) {
            $query->where('crash_location', $this->crash_location);
            if (!empty($this->exception_class)) {
                $query->where('exception_class', $this->exception_class);
            }
        } elseif (!empty($this->exception_class)) {
            $query->where('exception_class', $this->exception_class);
            if (!empty($this->exception_message)) {
                $query->where('exception_message', $this->exception_message);
            } elseif (!empty($this->stack_trace)) {
                $firstLine = trim(strtok($this->stack_trace, "\r\n"));
                if (preg_match('/([A-Za-z]+Exception[^\r\n]{0,50})/', $firstLine, $m)) {
                    $cleanPattern = preg_replace('/\d+|0x[a-fA-F0-9]+/', '', trim($m[1]));
                    if (strlen($cleanPattern) >= 10) {
                        $query->where('stack_trace', 'like', '%' . substr($cleanPattern, 0, 25) . '%');
                    }
                }
            }
        }

        return $query;
    }

    public function getSimilarCountAttribute()
    {
        return $this->getSimilarQuery(false)->count();
    }

    public function getSimilarUnresolvedCountAttribute()
    {
        return $this->getSimilarQuery(false)->whereRaw('LOWER(status) = ?', ['unresolved'])->count();
    }
}
