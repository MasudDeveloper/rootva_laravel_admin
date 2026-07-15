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
        // Basic User-Agent check to block scripts globally (Postman, Python, etc.)
        $ua = strtolower($request->header('User-Agent', ''));
        if (empty($ua) || str_contains($ua, 'postman') || str_contains($ua, 'python') || str_contains($ua, 'curl') || str_contains($ua, 'guzzle') || str_contains($ua, 'insomnia')) {
            Log::warning('Legacy API Blocked: Suspicious User-Agent', [
                'ip' => $request->ip(), 
                'ua' => $ua,
                'url' => $request->fullUrl()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Unauthorized request'], 401);
        }

        // Always prioritize the Auth-User-Id header injected by the App for authentication.
        // If not present, fallback to request inputs (for backward compatibility).
        $user_id = $request->header('Auth-User-Id') ?? $request->input('user_id') ?? $request->query('user_id') ?? ($_POST['user_id'] ?? null);
        $isNewApp = $request->hasHeader('Auth-User-Id');

        // Strictly enforce that a user_id must be provided for all legacy.auth APIs
        if (!$user_id || $user_id == '0') {
            if (!$isNewApp) {
                // Backward compatibility: If no user_id is provided by an old app, let it pass (e.g. get_Data.php)
                return $next($request);
            }
            Log::warning('Legacy API Blocked: Missing User ID', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Unauthorized request. Missing user identifier.'], 401);
        }

        $user = SignUp::find($user_id);
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $api_token = $request->header('Authorization') ?: $request->query('api_token') ?: $request->input('api_token');
        if (strpos((string)$api_token, 'Bearer ') === 0) {
            $api_token = substr($api_token, 7);
        }
        $password = $request->input('password') ?: $request->header('Auth-Password');

        $isAuthenticated = false;

        if ($api_token && $user->api_token === $api_token) {
            $isAuthenticated = true;
        } elseif ($password && (Hash::check($password, $user->password) || $password === $user->password)) {
            $isAuthenticated = true;
        }

        if (!$isAuthenticated) {
            if ($isNewApp) {
                Log::warning('Legacy API Blocked: Missing or Invalid Credentials', [
                    'ip' => $request->ip(),
                    'user_id' => $user_id,
                    'url' => $request->fullUrl()
                ]);
                return response()->json(['status' => 'error', 'message' => 'Unauthorized request'], 401);
            } else {
                // Heuristic fallback for legacy apps without auth
                $ip = $request->ip();
                $cacheKey = 'legacy_api_users_by_ip_' . md5($ip);
                $userIds = Cache::get($cacheKey, []);
                
                if (!in_array($user_id, $userIds)) {
                    $userIds[] = $user_id;
                    if (count($userIds) > 20) { // Max 20 distinct users per IP
                        Log::warning('Legacy API Blocked: Too many distinct users from IP', [
                            'ip' => $ip,
                            'user_id' => $user_id,
                            'url' => $request->fullUrl()
                        ]);
                        return response()->json(['status' => 'error', 'message' => 'Unauthorized request. Suspicious activity detected.'], 401);
                    }
                    Cache::put($cacheKey, $userIds, now()->addHours(1));
                }
            }
        }

        // --- IDOR Prevention ---
        // Force all controllers to act ONLY on the authenticated user's ID
        // Exceptions for endpoints that legitimately read other users' data
        $exceptions = ['get_profile.php'];
        $isException = false;
        foreach ($exceptions as $ex) {
            if (str_ends_with($request->path(), $ex)) {
                $isException = true;
                break;
            }
        }

        if (!$isException) {
            // Overwrite any forged user_id in the request payload
            $request->merge(['user_id' => $user_id]);
            $request->query->set('user_id', $user_id);
        }

        return $next($request);
    }
}
