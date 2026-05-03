@extends('layouts.admin')

@section('title', 'Reselling Orders')
@section('page_title', 'Order Management')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">
                @if($status === 'Pending') Pending @elseif($status === 'Confirmed') Confirmed @elseif($status === 'Delivered') Delivered @else Cancelled @endif
                Orders: {{ $orders->total() }}
            </h4>
            <p class="text-muted small mb-0">Manage customer orders for reselling products</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.orders.index', ['status' => 'Pending']) }}"
               class="btn btn-{{ $status === 'Pending' ? 'warning' : 'light' }} rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-clock me-2"></i>Pending
            </a>
            <a href="{{ route('admin.orders.index', ['status' => 'Confirmed']) }}"
               class="btn btn-{{ $status === 'Confirmed' ? 'info' : 'light' }} rounded-pill px-4 shadow-sm text-{{ $status === 'Confirmed' ? 'white' : 'dark' }}">
                <i class="fa-solid fa-spinner fa-spin-pulse me-2"></i>Confirmed
            </a>
            <a href="{{ route('admin.orders.index', ['status' => 'Delivered']) }}"
               class="btn btn-{{ $status === 'Delivered' ? 'success' : 'light' }} rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-check me-2"></i>Delivered
            </a>
            <a href="{{ route('admin.orders.index', ['status' => 'Cancelled']) }}"
               class="btn btn-{{ $status === 'Cancelled' ? 'danger' : 'light' }} rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-xmark me-2"></i>Cancelled
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <!-- Orders Table -->
    <div class="card-modern p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Order ID / User</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Product</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Customer</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-end">Price</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="fw-bold">#ORD-{{ $order->id }}</div>
                            <div class="text-muted small">
                                User: <span class="text-primary">{{ $order->user->name ?? 'Unknown' }}</span>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="fw-medium text-dark">{{ $order->product_name }}</div>
                            <div class="text-muted small">Qty: {{ $order->quantity }} | Delivery: ৳{{ number_format($order->delivery_charge, 2) }}</div>
                        </td>
                        <td class="py-3">
                            <div class="fw-medium text-dark">{{ $order->customer_name }}</div>
                            <div class="text-muted small">{{ $order->customer_number }}</div>
                        </td>
                        <td class="py-3 text-end fw-bold text-success">
                            ৳{{ number_format($order->total_price, 2) }}
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-light btn-sm rounded-pill px-3 shadow-sm">
                                    <i class="fa-solid fa-eye me-1"></i>View
                                </a>
                                @if($order->order_status === 'Pending')
                                <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="Confirmed">
                                    <button type="submit" class="btn btn-info btn-sm rounded-pill px-3 shadow-sm text-white">
                                        <i class="fa-solid fa-spinner me-1"></i>Confirm
                                    </button>
                                </form>
                                @endif
                                @if($order->order_status === 'Confirmed')
                                <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="Delivered">
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">
                                        <i class="fa-solid fa-check me-1"></i>Deliver
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-cart-shopping fa-3x mb-3 d-block opacity-25"></i>
                            No {{ strtolower($status) }} orders found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($orders->hasPages())
        <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
