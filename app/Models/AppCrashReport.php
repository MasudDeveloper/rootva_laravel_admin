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
     * Extract significant root keywords from error message or stack trace,
     * stripping dynamic memory addresses (0x...), object hash codes (a{...}),
     * UUIDs, and numbers (e.g. 485 vs 53 vs 39).
     */
    public function getSignificantKeywords()
    {
        $text = $this->exception_message;
        if (empty($text) && !empty($this->stack_trace)) {
            $text = trim(strtok($this->stack_trace, "\r\n"));
        }

        if (empty($text)) {
            return [];
        }

        // 1. Remove UUIDs
        $text = preg_replace('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', '', $text);
        // 2. Remove 0x... hex addresses
        $text = preg_replace('/0x[a-f0-9]+/i', '', $text);
        // 3. Remove object hash references like a{2e9571a} or a{b2fb126}
        $text = preg_replace('/[a-zA-Z0-9_$.]+\{[a-f0-9]+\}/i', '', $text);
        // 4. Remove all digits/numbers (e.g. 485, 53, 39, 20)
        $text = preg_replace('/\d+/', '', $text);

        // Extract words of length >= 3
        preg_match_all('/[a-zA-Z]{3,}/', $text, $matches);
        $words = $matches[0] ?? [];

        // Exclude generic stop words
        $stopWords = [
            'java', 'lang', 'android', 'com', 'mrdeveloper', 'rootva',
            'error', 'exception', 'caused', 'the', 'and', 'for', 'with',
            'from', 'ends', 'null', 'true', 'false', 'sourcefile', 'view'
        ];

        $filtered = [];
        foreach ($words as $w) {
            $lower = strtolower($w);
            if (!in_array($lower, $stopWords) && strlen($w) >= 3) {
                $filtered[] = $w;
            }
        }

        return array_slice(array_values(array_unique($filtered)), 0, 3);
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

        if (!empty($this->exception_class)) {
            $query->where('exception_class', $this->exception_class);
        }

        if (!empty($this->crash_location)) {
            $query->where('crash_location', $this->crash_location);
            return $query;
        }

        $keywords = $this->getSignificantKeywords();
        if (!empty($keywords)) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->where(function ($sub) use ($word) {
                        $sub->where('exception_message', 'like', "%{$word}%")
                            ->orWhere('stack_trace', 'like', "%{$word}%");
                    });
                }
            });
        } elseif (!empty($this->stack_trace)) {
            // If no message keywords, match by top frame of stack trace
            if (preg_match('/at\s+([a-zA-Z0-9_$.]+)/', $this->stack_trace, $frameMatch)) {
                $frame = $frameMatch[1];
                $query->where('stack_trace', 'like', "%{$frame}%");
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
