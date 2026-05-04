<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LegacyOrderController extends Controller
{
    /**
     * Legacy Order Submission (submit_order_request.php)
     */
    public function submitOrder(Request $request)
    {
        $user_id = intval($request->input('user_id', 0));
        $product_id = intval($request->input('product_id', 0));
        $product_name = trim($request->input('product_name', ''));
        $product_price = floatval($request->input('product_price', 0));
        $quantity = intval($request->input('quantity', 0));
        $total_price = floatval($request->input('total_price', 0));
        $total_earning = floatval($request->input('total_earning', 0));
        $total_product_price = floatval($request->input('total_product_price', 0));
        $delivery_charge = floatval($request->input('delivery_charge', 0));
        $customer_name = trim($request->input('customer_name', ''));
        $customer_number = trim($request->input('customer_number', ''));
        $customer_address = trim($request->input('customer_address', ''));
        $account_number = trim($request->input('account_number', ''));
        $transaction_id = intval($request->input('transaction_id', 0));
        $amount = floatval($request->input('amount', 0));
        $payment_gateway = trim($request->input('payment_gateway', ''));

        // Basic validation
        if (
            $user_id <= 0 || 
            $product_id <= 0 || 
            empty($product_name) || 
            $product_price <= 0 || 
            empty($customer_name) || 
            empty($customer_number)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Required fields are missing or invalid.'
            ]);
        }

        $user = SignUp::find($user_id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found']);
        }

        if ($user->voucher_balance < $delivery_charge) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance']);
        }

        DB::beginTransaction();
        try {
            // Create Order
            $order = Order::create([
                'user_id' => $user_id,
                'product_id' => $product_id,
                'product_name' => $product_name,
                'product_price' => $product_price,
                'customer_name' => $customer_name,
                'customer_number' => $customer_number,
                'quantity' => $quantity,
                'total_price' => $total_price,
                'total_earning' => $total_earning,
                'total_product_price' => $total_product_price,
                'delivery_charge' => $delivery_charge,
                'customer_address' => $customer_address,
                'account_number' => $account_number,
                'transaction_id' => $transaction_id,
                'payment_gateway' => $payment_gateway,
                'amount' => $amount,
                'created_at' => date('Y-m-d H:i:s'),
                'order_status' => 'Pending'
            ]);

            // Create Transaction
            $currentTime2 = date('d-m-Y h:i A');
            $transaction = Transaction::create([
                'user_id' => $user_id,
                'refer_id' => $user->referCode,
                'amount' => $delivery_charge,
                'payment_gateway' => 'Reselling Advance',
                'type' => 'voucher_payment',
                'description' => 'Payment For Reselling',
                'update_at' => $currentTime2,
                'created_at' => date('Y-m-d H:i:s'),
                'date' => date('Y-m-d H:i:s')
            ]);

            Log::info('Transaction created', ['id' => $transaction->id]);

            // Deduct Balance
            $user->decrement('voucher_balance', $delivery_charge);

            // Update Order with Transaction ID
            $order->update(['transaction_id' => $transaction->id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order submitted successfully.',
                'order_id' => $order->id,
                'transaction_id' => $transaction->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order Submission Error: ' . $e->getMessage(), [
                'user_id' => $user_id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit order.',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Legacy User Orders (get_user_orders.php)
     */
    public function getUserOrders(Request $request)
    {
        $userId = $request->query('user_id');
        
        if (!$userId || $userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid user ID']);
        }

        $orders = Order::where('user_id', $userId)
            ->select('id', 'product_name', 'product_price', 'order_status', 'cancel_reason', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }
}
