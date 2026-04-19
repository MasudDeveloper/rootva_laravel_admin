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
            ->where('status', 'Pending')
            ->orderBy('id', 'desc')
            ->get();

        $orderHistory = OnlineServiceOrder::with(['user', 'service'])
            ->where('status', '!=', 'Pending')
            ->orderBy('id', 'desc')
            ->paginate(25);

        return view('admin.online_service_orders.index', compact('pendingOrders', 'orderHistory'));
    }

    public function updateStatus(Request $request, $id)
    {
        $order = OnlineServiceOrder::findOrFail($id);
        $action = $request->input('action'); // 'Approved' or 'Rejected'
        
        if ($action == 'Approved') {
            $order->update([
                'status' => 'Approved',
                'updated_at' => now()->toDateTimeString()
            ]);
            return back()->with('success', 'Order approved successfully!');
        } elseif ($action == 'Rejected') {
            $request->validate(['reject_reason' => 'required']);
            $order->update([
                'status' => 'Rejected',
                'reject_reason' => $request->reject_reason,
                'updated_at' => now()->toDateTimeString()
            ]);
            return back()->with('success', 'Order rejected!');
        }

        return back()->with('error', 'Invalid action!');
    }
}
