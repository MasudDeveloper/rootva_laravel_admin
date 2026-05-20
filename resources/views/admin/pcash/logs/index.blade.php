@extends('layouts.admin')

@section('title', 'PCash API Logs')
@section('page_title', 'PCash Recharge Logs')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-file-invoice text-primary me-2"></i> API Transaction Logs</h6>
    </div>
    <div class="card-body">
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>User ID</th>
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
                        <td class="fw-bold text-primary">#{{ $log->user_id }}</td>
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
