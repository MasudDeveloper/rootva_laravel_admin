<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SignUp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VerifyLegacyApiAuth
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
        $user_id = $request->input('user_id') ?? $request->query('user_id') ?? ($_POST['user_id'] ?? null);

        // If no user_id is provided, let it pass (e.g. login, register endpoints)
        if (!$user_id) {
            return $next($request);
        }

        $user = SignUp::find($user_id);
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $api_token = $request->header('Authorization') ?: $request->query('api_token') ?: $request->input('api_token');
        if (strpos($api_token, 'Bearer ') === 0) {
            $api_token = substr($api_token, 7);
        }
        $password = $request->input('password');

        if ($api_token && $user->api_token === $api_token) {
            return $next($request);
        } elseif ($password && (Hash::check($password, $user->password) || $password === $user->password)) {
            return $next($request);
        }

        // Heuristic fallback for legacy apps without auth
        $ip = $request->ip();
        
        // Basic User-Agent check
        $ua = strtolower($request->header('User-Agent', ''));
        if (empty($ua) || str_contains($ua, 'postman') || str_contains($ua, 'python') || str_contains($ua, 'curl') || str_contains($ua, 'guzzle') || str_contains($ua, 'insomnia')) {
            Log::warning('Legacy API Blocked: Suspicious User-Agent', [
                'ip' => $ip, 
                'ua' => $ua,
                'url' => $request->fullUrl(),
                'payload' => $request->except(['password'])
            ]);
            return response()->json(['message' => 'Unauthorized request'], 401);
        }

        $cacheKey = 'legacy_api_users_by_ip_' . md5($ip);
        $userIds = Cache::get($cacheKey, []);
        
        if (!in_array($user_id, $userIds)) {
            $userIds[] = $user_id;
            if (count($userIds) > 20) { // Max 20 distinct users per IP (to allow CGNAT Mobile users)
                Log::warning('Legacy API Blocked: Too many distinct users from IP', [
                    'ip' => $ip, 
                    'user_id' => $user_id,
                    'url' => $request->fullUrl(),
                    'user_agent' => $request->header('User-Agent'),
                    'payload' => $request->except(['password'])
                ]);
                return response()->json(['message' => 'Unauthorized request. Suspicious activity detected.'], 401);
            }
            Cache::put($cacheKey, $userIds, now()->addHours(1));
        }

        return $next($request);
    }
}
