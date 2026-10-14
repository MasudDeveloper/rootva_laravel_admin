@extends('layouts.admin')

@section('title', 'Crash Report #' . $report->id)
@section('page_title', 'Crash Report Details')

@section('content')
<div class="fade-in">
    <!-- Top Action Bar -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <a href="{{ route('admin.crash-reports.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to All Reports
            </a>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Status Update Form -->
            <form action="{{ route('admin.crash-reports.updateStatus', $report->id) }}" method="POST" class="d-flex align-items-center gap-2">
                @csrf
                <label class="small text-muted fw-bold mb-0">Status:</label>
                <select name="status" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                    <option value="unresolved" {{ $report->status === 'unresolved' ? 'selected' : '' }}>🔴 Unresolved</option>
                    <option value="investigating" {{ $report->status === 'investigating' ? 'selected' : '' }}>🟡 Investigating</option>
                    <option value="resolved" {{ $report->status === 'resolved' ? 'selected' : '' }}>🟢 Resolved</option>
                </select>
            </form>
            <form action="{{ route('admin.crash-reports.destroy', $report->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this crash report?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                    <i class="fa-solid fa-trash me-1"></i> Delete
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Column: Exception Details & Stack Trace -->
        <div class="col-lg-8">
            <div class="card-modern mb-4">
                <div class="d-flex align-items-start justify-content-between mb-3 border-bottom pb-3">
                    <div>
                        <span class="badge bg-danger mb-2 px-2.5 py-1.5"><i class="fa-solid fa-bug me-1"></i> Crash Log #{{ $report->id }}</span>
                        <h4 class="fw-bold text-danger mb-1 font-monospace">{{ $report->exception_class }}</h4>
                        <div class="text-secondary fs-6">{{ $report->message ?: 'No error message provided' }}</div>
                    </div>
                </div>

                @if($report->crash_location)
                <div class="alert alert-light border d-flex align-items-center gap-3 py-2 px-3 mb-4 rounded-3">
                    <i class="fa-solid fa-location-crosshairs text-danger fs-5"></i>
                    <div>
                        <div class="extra-small text-muted text-uppercase fw-bold">Crash Origin File & Line:</div>
                        <div class="fw-bold font-monospace text-dark">{{ $report->crash_location }}</div>
                    </div>
                </div>
                @endif

                <!-- Stack Trace Viewer -->
                <div class="mb-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-uppercase small text-muted mb-0">Complete Stack Trace</h6>
                    <button class="btn btn-sm btn-light border rounded-pill px-3" onclick="copyStackTrace()">
                        <i class="fa-regular fa-copy me-1"></i> Copy Trace
                    </button>
                </div>
                <div class="bg-dark text-light p-3 rounded-4 shadow-inner" style="max-height: 480px; overflow-y: auto;">
                    <pre id="stackTraceText" class="mb-0 text-white font-monospace small" style="white-space: pre-wrap; word-break: break-all;">{{ $report->stack_trace }}</pre>
                </div>
            </div>
        </div>

        <!-- Right Column: User & Device Details -->
        <div class="col-lg-4">
            <!-- User Info Card -->
            <div class="card-modern mb-4">
                <h6 class="fw-bold text-uppercase small text-muted mb-3">
                    <i class="fa-solid fa-user me-1 text-primary"></i> User Information
                </h6>
                <div class="list-group list-group-flush small">
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">User ID</span>
                        <span class="fw-bold">{{ $report->user_id ?: 'Guest / Logged out' }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">User Name</span>
                        <span class="fw-bold">{{ $report->user->name ?? 'N/A' }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Phone Number</span>
                        <span class="fw-bold text-primary">{{ $report->user_phone ?: ($report->user->phone ?? 'N/A') }}</span>
                    </div>
                    @if($report->user)
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Account Profile</span>
                        <a href="{{ route('admin.users.show', $report->user->id) }}" class="btn btn-xs btn-outline-primary rounded-pill py-0 px-2" target="_blank">
                            Open Profile <i class="fa-solid fa-external-link ms-1"></i>
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Device & Environment Info Card -->
            <div class="card-modern mb-4">
                <h6 class="fw-bold text-uppercase small text-muted mb-3">
                    <i class="fa-solid fa-mobile-screen me-1 text-success"></i> Device & Environment
                </h6>
                <div class="list-group list-group-flush small">
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Device Model</span>
                        <span class="fw-bold">{{ $report->device_model ?: 'Unknown' }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Android Version</span>
                        <span class="fw-bold">Android {{ $report->android_version ?: 'N/A' }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">SDK API Level</span>
                        <span class="fw-bold font-monospace">API {{ $report->sdk_version ?: 'N/A' }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">App Version</span>
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                            v{{ $report->app_version ?: '1.0' }} (Code: {{ $report->app_version_code ?: '-' }})
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Client IP</span>
                        <span class="font-monospace text-muted">{{ $report->ip_address ?: 'Unknown' }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Crash Timestamp</span>
                        <span class="fw-bold">{{ $report->created_at ? $report->created_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyStackTrace() {
    const text = document.getElementById('stackTraceText').innerText;
    navigator.clipboard.writeText(text).then(function() {
        alert('Stack trace copied to clipboard!');
    }, function() {
        alert('Failed to copy stack trace.');
    });
}
</script>
@endsection
