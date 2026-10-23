@extends('layouts.admin')

@section('title', 'Crash Report #' . $report->id)
@section('page_title', 'Crash Report Details')

@section('content')
<div class="fade-in">
    <!-- Top Action Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <a href="{{ route('admin.crash-reports.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to All Reports
            </a>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if(strcasecmp($report->status, 'resolved') !== 0 && $similarCount > 0)
                <form action="{{ route('admin.crash-reports.resolveSimilar', $report->id) }}" method="POST" class="d-inline" onsubmit="return confirm('একই ধরনের সব ক্র্যাশ রিপোর্ট ({{ $similarCount + 1 }} টি) সমাধান হিসেবে মার্ক করতে চান?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm">
                        <i class="fa-solid fa-check-double me-1"></i> Resolve All Similar ({{ $similarCount + 1 }})
                    </button>
                </form>
            @endif

            <!-- Status Update Form -->
            <form action="{{ route('admin.crash-reports.updateStatus', $report->id) }}" method="POST" class="d-flex align-items-center gap-2 bg-white p-1 px-3 border rounded-pill shadow-sm">
                @csrf
                <label class="small text-muted fw-bold mb-0">Status:</label>
                <select name="status" class="form-select form-select-sm border-0 bg-transparent fw-bold text-dark" style="cursor: pointer;" onchange="this.form.submit()">
                    <option value="Unresolved" {{ strcasecmp($report->status, 'unresolved') === 0 ? 'selected' : '' }}>🔴 Unresolved</option>
                    <option value="Investigating" {{ strcasecmp($report->status, 'investigating') === 0 ? 'selected' : '' }}>🟡 Investigating</option>
                    <option value="Resolved" {{ strcasecmp($report->status, 'resolved') === 0 ? 'selected' : '' }}>🟢 Resolved</option>
                </select>
                @if($similarCount > 0)
                    <div class="form-check form-check-inline ms-1 mb-0 border-start ps-3" title="একই ধরনের সব ক্র্যাশে এই স্ট্যাটাস প্রয়োগ করুন">
                        <input class="form-check-input" type="checkbox" name="apply_to_similar" id="applyToSimilar" value="1" checked>
                        <label class="form-check-label extra-small text-muted fw-bold" for="applyToSimilar">
                            All Similar ({{ $similarCount }})
                        </label>
                    </div>
                @endif
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
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('error') }}
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Column: Exception Details & Stack Trace -->
        <div class="col-lg-8">
            <div class="card-modern mb-4">
                <div class="d-flex align-items-start justify-content-between mb-3 border-bottom pb-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-danger px-2.5 py-1.5"><i class="fa-solid fa-bug me-1"></i> Crash Log #{{ $report->id }}</span>
                            @if(strcasecmp($report->status, 'resolved') === 0)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                    <i class="fa-solid fa-check me-1"></i> Resolved
                                </span>
                            @elseif(strcasecmp($report->status, 'investigating') === 0)
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Investigating
                                </span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i> Unresolved
                                </span>
                            @endif
                            @if($similarCount > 0)
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                                    <i class="fa-solid fa-layer-group me-1"></i> {{ $similarCount }} Similar Crashes
                                </span>
                            @endif
                        </div>
                        <h4 class="fw-bold text-danger mb-1 font-monospace">{{ $report->exception_class ?: 'Exception' }}</h4>
                        <div class="text-secondary fs-6">{{ $report->exception_message ?: ($report->message ?: 'No error message provided') }}</div>
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

            <!-- Similar Crashes Section -->
            @if(isset($similarReports) && $similarReports->count() > 0)
            <div class="card-modern mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h6 class="fw-bold text-uppercase small text-muted mb-0">
                        <i class="fa-solid fa-layer-group text-primary me-1"></i> Similar Crash Occurrences ({{ $similarCount }})
                    </h6>
                    @if(strcasecmp($report->status, 'resolved') !== 0)
                    <form action="{{ route('admin.crash-reports.resolveSimilar', $report->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-xs btn-outline-success rounded-pill px-3 py-1">
                            <i class="fa-solid fa-check-double me-1"></i> Mark All as Resolved
                        </button>
                    </form>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 extra-small">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Device / OS</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Time</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($similarReports as $sim)
                            <tr>
                                <td class="fw-bold text-muted">#{{ $sim->id }}</td>
                                <td>{{ $sim->device_model ?: 'Android' }} (Android {{ $sim->android_version ?: 'N/A' }})</td>
                                <td>{{ $sim->user_phone ?: ($sim->user ? $sim->user->phone : 'Guest') }}</td>
                                <td>
                                    @if(strcasecmp($sim->status, 'resolved') === 0)
                                        <span class="badge bg-success bg-opacity-10 text-success">Resolved</span>
                                    @elseif(strcasecmp($sim->status, 'investigating') === 0)
                                        <span class="badge bg-warning bg-opacity-10 text-warning">Investigating</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger">Unresolved</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $sim->created_at ? $sim->created_at->diffForHumans() : 'N/A' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.crash-reports.show', $sim->id) }}" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
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
