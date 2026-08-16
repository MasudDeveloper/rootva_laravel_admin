@extends('layouts.admin')

@section('title', 'PCash API Logs')
@section('page_title', 'PCash Recharge Logs')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-file-invoice text-primary me-2"></i> API Transaction Logs</h6>
    </div>
    <div class="card-body">
        
        <!-- Search Filter -->
        <div class="mb-4">
            <form action="{{ route('admin.pcash.logs.index') }}" method="GET" class="row g-3">
                <div class="col-md-9">
                    <input type="text" name="search" class="form-control rounded-pill border-0 bg-light px-4" 
                           placeholder="Search by Mobile or Referral Code..." value="{{ $search ?? '' }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">
                        <i class="fa-solid fa-magnifying-glass me-2"></i>Search
                    </button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Referral Code</th>
                        <th>Mobile Number</th>
                        <th>Operator</th>
                        <th>Amount</th>
                        <th>API Status</th>
                        <th>API Message</th>
                        <th>API Request ID</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-muted small">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                        <td class="fw-bold">
                            @if($log->user)
                                <a href="{{ route('admin.users.show', $log->user_id) }}" class="text-primary text-decoration-none">
                                    {{ $log->user->referCode ?? 'No Refer Code' }}
                                </a>
                            @else
                                <span class="text-muted">#{{ $log->user_id }} (Deleted)</span>
                            @endif
                        </td>
                        <td class="fw-semibold">{{ $log->number }}</td>
                        <td><span class="badge bg-secondary">{{ $log->operator }}</span></td>
                        <td class="text-success fw-bold">{{ $log->amount }} ৳</td>
                        <td>
                            @if($log->api_status == 'success')
                                <span class="badge bg-success">Success</span>
                            @elseif($log->api_status == 'failed')
                                <span class="badge bg-danger">Failed</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </td>
                        <td class="small">{{ $log->api_message ?? 'N/A' }}</td>
                        <td class="text-muted small" style="font-family: monospace;">{{ $log->api_id }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No recharge logs found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
