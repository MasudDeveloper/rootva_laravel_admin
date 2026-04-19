<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ApiDocumentController extends Controller
{
    public function index()
    {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            return str_starts_with($route->uri(), 'api/');
        })->map(function ($route) {
            $action = $route->getActionName();
            $action = str_replace(['App\Http\Controllers\Api\\', 'App\Http\Controllers\Admin\\', 'App\Http\Controllers\\'], '', $action);
            
            // Extract method name (e.g., login from LegacyApiController@login)
            $methodName = str_contains($action, '@') ? explode('@', $action)[1] : $action;

            return [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'name' => $route->getName(),
                'action' => $action,
                'method_name' => $methodName,
                'is_legacy' => str_ends_with($route->uri(), '.php'),
            ];
        });

        // Smart deduplication: If multiple routes share the same method name (e.g. login, register), keep legacy.
        $deduplicated = collect();
        foreach ($routes->groupBy('method_name') as $methodName => $group) {
            $legacy = $group->where('is_legacy', true)->first();
            $deduplicated->push($legacy ?: $group->first());
        }
        $routes = $deduplicated->sortBy('uri');

        // Manually define descriptions for key legacy endpoints
        $descriptions = [
            'api/login.php' => 'Legacy User Login Endpoint. Parameters: number, password.',
            'api/register.php' => 'Legacy User Registration. Parameters: name, number, password, referredBy.',
            'api/get_Data.php' => 'Fetches complete User details for the profile and home screens.',
            'api/get_banners.php' => 'Retrieves all active billboard/banners from the database.',
            'api/get_reviews.php' => 'Retrieves user success stories and reviews.',
            'api/get_social_links.php' => 'Retrieves social and support links (WhatsApp, Telegram, etc).',
            'api/get_latest_update.php' => 'Returns latest app version info for update checks.',
            'api/get_wallet_balance.php' => 'Returns current wallet and voucher balance for a user.',
            'api/get_transaction_history.php' => 'Returns all transaction logs (add/withdraw) for a user.',
            'api/get_income_report.php' => 'Returns referral commission history.',
            'api/get_sim_offer.php' => 'Retrieves current SIM/Drive offers.',
            'api/get_categories.php' => 'Retrieves product categories for the shop.',
            'api/get_referral_tree.php' => 'Retrieves recruited users list for the referral tree.',
            'api/update_profile.php' => 'Updates name, email, address, and gender for a user.',
            'api/get_payment_numbers.php' => 'Returns admin bkash/nagad/rocket numbers for additions.',
            'api/get_microjobs2.php' => 'Lists all currently available micro jobs.',
            'api/recharge_request.php' => 'Submits a new mobile recharge request.',
            'api/get_recharge_history.php' => 'Returns history of mobile recharge requests.',
            'api/get_course_progress.php' => 'Returns current learning progress for a user.',
            'api/salary_request.php' => 'Submits a request for monthly salary.',
            'api/getRefer.php' => 'Validates a refer code and returns the user details.',
            'api/save_fcm_token.php' => 'Saves Firebase Cloud Messaging token for notifications.',
        ];

        return view('admin.api.endpoints', compact('routes', 'descriptions'));
    }
}
