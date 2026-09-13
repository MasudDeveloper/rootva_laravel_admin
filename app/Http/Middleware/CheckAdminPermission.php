<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = auth()->user();

        if (!$admin) {
            return redirect()->route('admin.login');
        }

        // If admin instance doesn't have hasPermission method (e.g. standard auth object)
        if (method_exists($admin, 'hasPermission') && !$admin->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized access. You do not have permission for this module.'], 403);
            }

            return redirect()->route('admin.dashboard')->with('error', 'অ্যাক্সেস প্রত্যাখ্যান করা হয়েছে! আপনার এই মডিউলে প্রবেশের অনুমতি নেই।');
        }

        return $next($request);
    }
}
