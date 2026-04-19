@extends('layouts.admin')

@section('title', 'Verification Requests')
@section('page_title', 'Identity Verifications')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Pending Verifications: {{ $requests->total() }}</h4>
            <p class="text-muted small mb-0">Approve user accounts to grant full feature access</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.verifications.index', ['status' => 'Pending']) }}" 
               class="btn btn-{{ $status == 'Pending' ? 'primary' : 'light' }} rounded-pill px-4 shadow-sm">Pending</a>
            <a href="{{ route('admin.verifications.index', ['status' => 'Approved']) }}" 
               class="btn btn-{{ $status == 'Approved' ? 'primary' : 'light' }} rounded-pill px-4 shadow-sm">Approved</a>
            <a href="{{ route('admin.verifications.index', ['status' => 'Rejected']) }}" 
               class="btn btn-{{ $status == 'Rejected' ? 'primary' : 'light' }} rounded-pill px-4 shadow-sm">Rejected</a>
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
    <div class="card-modern p-0 overflow-hidden border-0 shadow-lg">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">User Information</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Payment Details</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Transaction ID</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-end">Amount</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Status</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @forelse($requests as $req)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px">
                                    {{ substr($req->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $req->name }}</div>
                                    <div class="text-muted small">{{ $req->number }}</div>
                                    <div class="badge bg-light text-primary rounded-pill extra-small fw-bold">REF: {{ $req->referCode }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="fw-semibold text-capitalize">{{ $req->payment_gateway }}</div>
                            <div class="text-muted small">{{ $req->account_number }}</div>
                        </td>
                        <td class="py-3">
                            <code class="text-primary bg-primary-soft px-2 py-1 rounded small fw-bold">{{ $req->transaction_id }}</code>
                        </td>
                        <td class="py-3 text-end fw-extrabold text-dark">
                            ৳{{ number_format($req->amount ?? 0, 2) }}
                        </td>
                        <td class="py-3 text-center">
                            @if($req->status == 'Pending')
                                <span class="badge bg-warning-soft text-warning rounded-pill px-3">Pending</span>
                            @elseif($req->status == 'Approved')
                                <span class="badge bg-success-soft text-success rounded-pill px-3">Approved</span>
                            @else
                                <span class="badge bg-danger-soft text-danger rounded-pill px-3">Rejected</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            @if($req->status == 'Pending')
                            <div class="d-flex justify-content-end gap-2">
                                <form action="{{ route('admin.verifications.approve', $req->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" onclick="return confirm('Verify this user?')">
                                        <i class="fa-solid fa-check-circle me-1"></i>Approve
                                    </button>
                                </form>
                                <form action="{{ route('admin.verifications.reject', $req->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm" onclick="return confirm('Reject this verification request?')">
                                        <i class="fa-solid fa-ban me-1"></i>Reject
                                    </button>
                                </form>
                            </div>
                            @else
                                <span class="text-muted small italic">Processed</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <div class="opacity-50">
                                <i class="fa-solid fa-shield-halved fa-4x mb-3 d-block"></i>
                                <h5 class="fw-bold">No {{ strtolower($status) }} requests found</h5>
                                <p class="small">Check back later for new verification submissions.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
        <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
            {{ $requests->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('styles')
<style>
    .bg-gray-50 { background-color: #f9fafb; }
    .extra-small { font-size: 0.7rem; }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .card-modern { border-radius: 1.25rem; }
    .letter-spacing-1 { letter-spacing: 0.05em; }
</style>
@endsection
