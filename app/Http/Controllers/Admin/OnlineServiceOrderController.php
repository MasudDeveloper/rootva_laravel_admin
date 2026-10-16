<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OnlineServiceOrder;

class OnlineServiceOrderController extends Controller
{
    public function index()
    {
        $pendingOrders = OnlineServiceOrder::with(['user', 'service'])
            ->whereIn('status', ['Pending', 'pending'])
            ->orderBy('id', 'desc')
            ->get();

        $orderHistory = OnlineServiceOrder::with(['user', 'service'])
            ->whereNotIn('status', ['Pending', 'pending'])
            ->orderBy('id', 'desc')
            ->paginate(25);

        return view('admin.online_service_orders.index', compact('pendingOrders', 'orderHistory'));
    }

    public function updateStatus(Request $request, $id)
    {
        $order = OnlineServiceOrder::findOrFail($id);
        $action = strtolower($request->input('action')); // 'approved' or 'rejected'
        
        if ($action == 'approved') {
            $order->update([
                'status' => 'approved',
                'updated_at' => now()->toDateTimeString()
            ]);
            return back()->with('success', 'Order approved successfully!');
        } elseif ($action == 'rejected') {
            $request->validate(['reject_reason' => 'required']);
            $order->update([
                'status' => 'rejected',
                'reject_reason' => $request->reject_reason,
                'updated_at' => now()->toDateTimeString()
            ]);
            return back()->with('success', 'Order rejected!');
        }

        return back()->with('error', 'Invalid action!');
    }
}
