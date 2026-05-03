<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\SignUp;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'Pending');
        
        $orders = Order::with('user')
            ->when($status, function($q) use ($status) {
                return $q->where('order_status', $status);
            })
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'status'));
    }

    public function show($id)
    {
        $order = Order::with('user')->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $oldStatus = $order->order_status;
        $newStatus = $request->input('status');
        
        $order->update([
            'order_status' => $newStatus,
            'cancel_reason' => $request->input('cancel_reason')
        ]);

        return back()->with('success', "Order status updated from {$oldStatus} to {$newStatus}.");
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();
        return redirect()->route('admin.orders.index')->with('success', 'Order deleted successfully.');
    }
}
