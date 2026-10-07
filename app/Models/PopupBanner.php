<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopupBanner extends Model
{
    protected $table = 'popup_banner';
    public $timestamps = false;
    protected $guarded = [];

    public function getImageUrlAttribute($value)
    {
        if (empty($value)) return null;

        $path = null;
        if (preg_match('#/(uploads/.*)$#i', $value, $matches)) {
            $path = $matches[1];
        } else if (str_starts_with($value, '/')) {
            $path = ltrim($value, '/');
        } else if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $parsed = parse_url($value, PHP_URL_PATH);
            $path = $parsed ? ltrim(preg_replace('#^/public/#', '/', $parsed), '/') : ltrim($value, '/');
        } else {
            $path = 'uploads/popups/' . ltrim($value, '/');
        }

        return 'https://rootvaadmin.rootvabd.com/' . ltrim($path, '/');
    }
}
