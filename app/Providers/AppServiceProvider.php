<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrap();

        \Carbon\Carbon::macro('parseMixed', function ($time = null, $tz = null) {
            if (is_string($time)) {
                $bengali = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
                $english = ['0','1','2','3','4','5','6','7','8','9'];
                $time = str_replace($bengali, $english, $time);
            }
            return new static($time, $tz);
        });
    }
}
