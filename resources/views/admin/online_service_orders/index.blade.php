@extends('layouts.admin')

@section('title', 'Online Service Orders')
@section('page_title', 'Service Requests')

@section('content')
<div class="fade-in" x-data="{ activeTab: 'pending' }">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Service Requests</h4>
            <p class="text-muted small mb-0">Manage customer orders for digital services</p>
        </div>
        <a href="{{ route('admin.online-services.index') }}" class="btn btn-outline-primary rounded-pill px-4">
            <i class="fa-solid fa-gear me-2"></i>Configure Services
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    {{-- Tabs --}}
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded-pill shadow-sm d-inline-flex border">
        <li class="nav-item">
            <button class="nav-link rounded-pill px-4" :class="activeTab === 'pending' ? 'active' : 'text-muted'" @click="activeTab = 'pending'">
                Pending <span class="badge bg-danger ms-2">{{ count($pendingOrders) }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-pill px-4" :class="activeTab === 'history' ? 'active' : 'text-muted'" @click="activeTab = 'history'">
                History
            </button>
        </li>
    </ul>

    {{-- Pending Section --}}
    <div x-show="activeTab === 'pending'" x-transition>
        <div class="card-modern border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">ID</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-start">User / Affiliate</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Service</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Price</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Contact Info</th>
                            <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse($pendingOrders as $order)
                        <tr>
                            <td class="px-4 py-3 text-muted small fw-bold">#{{ $order->id }}</td>
                            <td class="py-3 text-start">
                                <div class="fw-bold text-dark">{{ $order->user->name ?? 'User Not Found' }}</div>
                                <div class="text-primary small">ID: {{ $order->user->referCode ?? 'N/A' }}</div>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-light text-dark rounded-pill">{{ $order->service->name ?? 'Deleted Service' }}</span>
                            </td>
                            <td class="py-3 fw-bold text-dark">৳{{ number_format($order->service->price ?? 0, 2) }}</td>
                            <td class="py-3">
                                <div class="small"><i class="fa-brands fa-whatsapp text-success me-1"></i>{{ $order->whatsapp ?? 'N/A' }}</div>
                                <div class="extra-small text-muted"><i class="fa-brands fa-telegram text-primary me-1"></i>{{ $order->telegram ?? 'N/A' }}</div>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <form action="{{ route('admin.online-service-orders.updateStatus', $order->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="action" value="Approved">
                                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">Approve</button>
                                    </form>
                                    <button onclick="rejectOrder({{ $order->id }})" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm">Reject</button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No pending orders found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- History Section --}}
    <div x-show="activeTab === 'history'" x-transition style="display: none;">
        <div class="card-modern border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">ID</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-start">User Info</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Service</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Price</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Status</th>
                            <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse($orderHistory as $order)
                        <tr>
                            <td class="px-4 py-3 text-muted small fw-bold">#{{ $order->id }}</td>
                            <td class="py-3 text-start">
                                <div class="fw-bold">{{ $order->user->name ?? 'N/A' }}</div>
                                <div class="extra-small text-muted">ID: {{ $order->user->referCode ?? 'N/A' }}</div>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-light text-dark rounded-pill">{{ $order->service->name ?? 'N/A' }}</span>
                            </td>
                            <td class="py-3 fw-bold">৳{{ number_format($order->service->price ?? 0, 2) }}</td>
                            <td class="py-3">
                                <span class="badge {{ $order->status === 'Approved' ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 small text-muted">
                                {{ $order->reject_reason ?? '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Order history is empty.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($orderHistory->hasPages())
                <div class="px-4 py-3 border-top bg-light d-flex justify-content-center">
                    {{ $orderHistory->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Reject Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" action="" method="POST">
                @csrf
                <input type="hidden" name="action" value="Rejected">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Reason for Rejection</label>
                        <textarea name="reject_reason" class="form-control rounded-3 shadow-sm" rows="3" placeholder="Explain why the order is being rejected..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 shadow-sm">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-gray-50 { background-color: #f9fafb; }
    .extra-small { font-size: 0.7rem; }
    .card-modern { border-radius: 1.25rem; }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
</style>


@endsection

@section('scripts')
<script>
    function rejectOrder(id) {
        document.getElementById('rejectForm').action = `{{ url('/services/online-service-orders') }}/${id}/status`;
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    }
</script>
@endsection
