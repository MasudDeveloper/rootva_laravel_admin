<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoBlockMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        // Localhost IPs bypass geo-blocking
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return $next($request);
        }

        $cacheKey = 'geoip_country_' . str_replace(':', '_', $ip);

        $countryCode = Cache::remember($cacheKey, now()->addHours(24), function () use ($ip) {
            try {
                // Using ip-api.com to get the country code
                $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}?fields=countryCode");
                if ($response->successful()) {
                    return $response->json('countryCode');
                }
            } catch (\Exception $e) {
                Log::error("GeoIP API error: " . $e->getMessage());
            }
            return null; // If API fails, allow request to prevent downtime
        });

        // If the country is determined and it's not Bangladesh (BD), block it
        if ($countryCode && strtoupper($countryCode) !== 'BD') {
            Log::warning("GeoBlock: Blocked request from {$ip} ({$countryCode}) to URL: " . $request->fullUrl());
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['status' => 'error', 'message' => 'Access denied from your region.'], 403);
            }
            abort(403, 'Access denied from your region.');
        }

        return $next($request);
    }
}
