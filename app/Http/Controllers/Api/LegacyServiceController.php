<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\SignUp;
use App\Models\OnlineServiceOrder;
use Illuminate\Support\Facades\DB;

class LegacyServiceController extends Controller
{
    /**
     * Get Online Services (get_online_services.php)
     */
    public function getOnlineServices()
    {
        $services = Service::orderBy('id', 'DESC')->get();
        return response()->json([
            'error' => false,
            'services' => $services
        ]);
    }

    /**
     * Get Service By ID (get_service.php)
     */
    public function getServiceById(Request $request)
    {
        $id = $request->query('id');
        $service = Service::find($id);
        return response()->json([
            'success' => true,
            'service' => $service
        ]);
    }

    /**
     * Submit Online Service Order (submit_online_service_order.php)
     */
    public function submitOnlineServiceOrder(Request $request)
    {
        $user_id = intval($request->input('user_id'));
        $service_id = intval($request->input('service_id'));
        $whatsapp = trim($request->input('whatsapp'));
        $telegram = trim($request->input('telegram'));
        $price = floatval($request->input('price'));

        if (!$user_id || !$service_id || !$whatsapp || !$telegram) {
            return response()->json(['success' => false, 'message' => 'Missing fields']);
        }

        try {
            return DB::transaction(function () use ($user_id, $service_id, $whatsapp, $telegram, $price) {
                // ✅ ইউজারের ব্যালেন্স চেক করা
                $user = SignUp::find($user_id);

                if (!$user) {
                    return response()->json(['success' => false, 'message' => 'User not found']);
                }

                if ($user->voucher_balance < $price) {
                    return response()->json(['success' => false, 'message' => 'Insufficient balance']);
                }

                // ✅ ব্যালেন্স কমানো
                $user->decrement('voucher_balance', $price);

                // ✅ রিকোয়েস্ট সেভ করা
                OnlineServiceOrder::create([
                    'user_id' => $user_id,
                    'service_id' => $service_id,
                    'whatsapp' => $whatsapp,
                    'telegram' => $telegram,
                    'status' => 'pending',
                    'created_at' => now()
                ]);

                return response()->json(["error" => false, "message" => "Order placed successfully"]);
            });
        } catch (\Exception $e) {
            return response()->json(["error" => true, "message" => "Database error: " . $e->getMessage()]);
        }
    }

    /**
     * Get User Online Service Orders (get_user_online_service_orders.php)
     */
    public function getUserOnlineServiceOrders(Request $request)
    {
        $userId = $request->query('user_id');
        if (!$userId) {
            return response()->json(['error' => true, 'message' => 'Invalid user ID']);
        }

        $orders = DB::table('online_service_orders as oso')
            ->join('services as os', 'oso.service_id', '=', 'os.id')
            ->select('oso.*', 'os.name as service_name', 'os.price')
            ->where('oso.user_id', $userId)
            ->orderBy('oso.id', 'DESC')
            ->get();

        return response()->json([
            'error' => false,
            'orders' => $orders
        ]);
    }
}
