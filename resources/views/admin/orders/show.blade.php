@extends('layouts.admin')

@section('title', 'Order Details')
@section('page_title', 'Order #ORD-' . $order->id)

@section('content')
<div class="fade-in">
    <div class="mb-4">
        <a href="{{ route('admin.orders.index', ['status' => $order->order_status]) }}" class="btn btn-light rounded-pill px-4 shadow-sm mb-3">
            <i class="fa-solid fa-arrow-left me-2"></i>Back to Orders
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Order Info Card -->
            <div class="card-modern mb-4 p-0 overflow-hidden">
                <div class="px-4 py-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Product & Pricing</h5>
                    <span class="badge bg-{{ $order->order_status === 'Pending' ? 'warning' : ($order->order_status === 'Confirmed' ? 'info' : ($order->order_status === 'Delivered' ? 'success' : ($order->order_status === 'Shipped' ? 'primary' : 'danger'))) }} rounded-pill px-3 py-2">
                        {{ $order->order_status }}
                    </span>
                </div>
                <div class="p-4">
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Product Name</label>
                            <div class="h6 fw-bold text-dark">{{ $order->product_name }}</div>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Ordered At</label>
                            <div class="h6 fw-bold text-dark">{{ $order->created_at }}</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Metric</th>
                                    <th class="text-end">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Unit Price</td>
                                    <td class="text-end">৳{{ number_format($order->product_price, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Quantity</td>
                                    <td class="text-end">{{ $order->quantity }}</td>
                                </tr>
                                <tr>
                                    <td>Delivery Charge (Paid by User)</td>
                                    <td class="text-end">৳{{ number_format($order->delivery_charge, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Total Product Price</td>
                                    <td class="text-end">৳{{ number_format($order->total_product_price, 2) }}</td>
                                </tr>
                                <tr class="bg-light fw-bold">
                                    <td class="h6 mb-0 fw-bold">Total Bill (For Customer)</td>
                                    <td class="text-end h6 mb-0 fw-bold text-success">৳{{ number_format($order->total_price, 2) }}</td>
                                </tr>
                                <tr class="bg-primary-soft">
                                    <td class="fw-bold text-primary">User Earning (Commission)</td>
                                    <td class="text-end fw-bold text-primary">৳{{ number_format($order->total_earning, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Customer Info Card -->
            <div class="card-modern p-0 overflow-hidden">
                <div class="px-4 py-3 bg-light border-bottom">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-user-tag me-2 text-primary"></i>Customer Shipping Details</h5>
                </div>
                <div class="p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Customer Name</label>
                            <div class="fw-bold">{{ $order->customer_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Phone Number</label>
                            <div class="fw-bold">{{ $order->customer_number }}</div>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Delivery Address</label>
                            <div class="p-3 bg-light rounded border">{{ $order->customer_address }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Order Actions Card -->
            <div class="card-modern mb-4 p-0 overflow-hidden">
                <div class="px-4 py-3 bg-light border-bottom text-center">
                    <h6 class="mb-0 fw-bold">Management Actions</h6>
                </div>
                <div class="p-4">
                    <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST" class="mb-3">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Update Order Status</label>
                            <select name="status" class="form-select rounded-pill px-3 shadow-sm border-0 bg-light">
                                <option value="Pending" {{ $order->order_status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                <option value="Confirmed" {{ $order->order_status === 'Confirmed' ? 'selected' : '' }}>Confirmed</option>
                                <option value="Shipped" {{ $order->order_status === 'Shipped' ? 'selected' : '' }}>Shipped</option>
                                <option value="Delivered" {{ $order->order_status === 'Delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="Cancelled" {{ $order->order_status === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="mb-3 d-none" id="cancelReasonBox">
                            <label class="form-label small fw-bold text-muted">Cancellation Reason</label>
                            <textarea name="cancel_reason" class="form-control rounded-4 shadow-sm border-0 bg-light" rows="3">{{ $order->cancel_reason }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm fw-bold">
                            Update Order
                        </button>
                    </form>

                    @if($order->order_status === 'Cancelled')
                    <div class="alert alert-danger border-0 rounded-4 p-3 small">
                        <i class="fa-solid fa-circle-exclamation me-1"></i>
                        <strong>Reason:</strong> {{ $order->cancel_reason ?? 'No reason provided.' }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- Reseller Info Card -->
            <div class="card-modern p-0 overflow-hidden">
                <div class="px-4 py-3 bg-light border-bottom text-center">
                    <h6 class="mb-0 fw-bold">Reseller (User) Information</h6>
                </div>
                <div class="p-4 text-center">
                    @if($order->user)
                        <div class="mb-3">
                            <div class="h5 fw-bold text-dark mb-0">{{ $order->user->name }}</div>
                            <div class="badge bg-primary-soft text-primary rounded-pill px-3">#{{ $order->user->referCode }}</div>
                        </div>
                        <div class="text-muted small mb-3">
                            <i class="fa-solid fa-phone me-1"></i>{{ $order->user->number }}
                        </div>
                        <a href="{{ route('admin.users.show', $order->user->id) }}" class="btn btn-outline-primary btn-sm rounded-pill w-100">
                            View User Profile
                        </a>
                    @else
                        <div class="text-muted py-3">User information not available</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelector('select[name="status"]').addEventListener('change', function() {
        const reasonBox = document.getElementById('cancelReasonBox');
        if (this.value === 'Cancelled') {
            reasonBox.classList.remove('d-none');
        } else {
            reasonBox.classList.add('d-none');
        }
    });
</script>
@endsection
