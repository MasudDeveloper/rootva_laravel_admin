@extends('layouts.admin')

@section('title', 'Withdraw Requests')
@section('page_title', 'Payout Approvals')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">
                @if($status === 'Pending') Pending @elseif($status === 'Approved') Approved @else Rejected @endif
                Payouts: {{ $requests->total() }}
            </h4>
            <p class="text-muted small mb-0">Review and approve user withdrawal requests</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.withdraw-requests.index', ['status' => 'Pending']) }}"
               class="btn btn-{{ $status === 'Pending' ? 'warning' : 'light' }} rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-clock me-2"></i>Pending
            </a>
            <a href="{{ route('admin.withdraw-requests.index', ['status' => 'Approved']) }}"
               class="btn btn-{{ $status === 'Approved' ? 'success' : 'light' }} rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-check me-2"></i>Approved
            </a>
            <a href="{{ route('admin.withdraw-requests.index', ['status' => 'Rejected']) }}"
               class="btn btn-{{ $status === 'Rejected' ? 'danger' : 'light' }} rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-xmark me-2"></i>Rejected
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('error') }}
        </div>
    @endif

    <!-- Requests Table -->
    <div class="card-modern p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">User / ID</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Gateway</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Type</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Date/Time</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-end">Net Amount</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="fw-bold">{{ $req->name }}</div>
                            <div class="badge bg-primary-soft text-primary rounded-pill small">#{{ $req->referCode }}</div>
                        </td>
                        <td class="py-3">
                            <div class="fw-medium text-capitalize">{{ $req->payment_gateway }}</div>
                            <div class="text-muted small">{{ $req->account_number }}</div>
                        </td>
                        <td class="py-3">
                            <span class="badge {{ $req->balance_type === 'voucher' ? 'bg-info-soft text-info' : 'bg-primary-soft text-primary' }} rounded-pill text-capitalize">
                                {{ $req->balance_type }}
                            </span>
                        </td>
                        <td class="py-3">
                            <div class="text-dark small fw-medium">
                                {{ \Carbon\Carbon::parse($req->created_at)->format('d-m-Y h:i A') }}
                            </div>
                        </td>
                        <td class="py-3 text-end">
                            <div class="fw-bold text-danger">৳{{ number_format($req->amount, 2) }}</div>
                            <div class="text-muted extra-small" style="font-size: 10px">Fee: ৳{{ $req->fee }}</div>
                        </td>
                        <td class="px-4 py-3 text-end">
                            @if($req->status === 'Pending')
                            <div class="d-flex justify-content-end gap-2">
                                <form action="{{ route('admin.withdraw-requests.update', $req->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="Approved">
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" onclick="return confirm('Approve this withdrawal?')">
                                        <i class="fa-solid fa-check me-1"></i>Approve
                                    </button>
                                </form>
                                <form action="{{ route('admin.withdraw-requests.update', $req->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="Rejected">
                                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" onclick="return confirm('Reject and refund balance?')">
                                        <i class="fa-solid fa-xmark me-1"></i>Reject
                                    </button>
                                </form>
                            </div>
                            @elseif($req->status === 'Approved')
                                <span class="badge bg-success-soft text-success rounded-pill px-3 py-2">
                                    <i class="fa-solid fa-check-circle me-1"></i>Approved
                                </span>
                            @else
                                <span class="badge bg-danger-soft text-danger rounded-pill px-3 py-2">
                                    <i class="fa-solid fa-ban me-1"></i>Rejected
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-money-bill-transfer fa-3x mb-3 d-block opacity-25"></i>
                            No {{ strtolower($status) }} withdrawal requests found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($requests->hasPages())
        <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
            {{ $requests->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
