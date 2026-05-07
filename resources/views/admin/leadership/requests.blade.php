@extends('layouts.admin')

@section('title', 'Leadership Reward Claims')
@section('page_title', 'Reward Management')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Pending Claims</h4>
            <p class="text-muted small mb-0">Review and verify leadership reward achievement requests</p>
        </div>
        <a href="{{ route('admin.leadership.history') }}" class="btn btn-outline-primary rounded-pill px-4 shadow-sm">
            <i class="fa-solid fa-list-check me-2"></i>Disbursed History
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="card-modern border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">ID</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-start">User Info</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Reward Level</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Requirement Met</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-end">Bonus Amount</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Requested At</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @forelse($requests as $req)
                    <tr>
                        <td class="px-4 py-3 text-muted small fw-bold">#{{ $req->id }}</td>
                        <td class="py-3 text-start">
                            <div class="fw-bold text-dark">{{ $req->user->name ?? 'Unknown' }}</div>
                            <div class="text-muted extra-small">Ref: {{ $req->user->referCode ?? 'N/A' }}</div>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-primary-soft text-primary rounded-pill px-3">{{ $req->reward_type }}</span>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-info-soft text-info rounded-pill px-3">{{ $req->times }} Target Met</span>
                        </td>
                        <td class="py-3 text-end fw-bold text-dark">
                            ৳{{ number_format($req->amount, 2) }}
                        </td>
                        <td class="py-3 text-muted small">
                            {{ \Carbon\Carbon::parse($req->created_at)->format('d-m-Y h:i A') }}
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <form action="{{ route('admin.leadership.process', $req->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action" value="Approved">
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm fw-bold" onclick="return confirm('Approve this achievement and disburse bonus?')">Approve</button>
                                </form>
                                <form action="{{ route('admin.leadership.process', $req->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action" value="Rejected">
                                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm fw-bold" onclick="return confirm('Reject this achievement request?')">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">🎉 No pending reward claims at the moment.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-gray-50 { background-color: #f9fafb; }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .card-modern { border-radius: 1.25rem; }
    .extra-small { font-size: 0.75rem; }
</style>
@endsection
