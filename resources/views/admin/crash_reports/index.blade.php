@extends('layouts.admin')

@section('title', 'App Crash Reports')
@section('page_title', 'App Crash & Error Analysis')

@section('content')
<div class="fade-in">
    <!-- Top Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card-modern p-3 d-flex align-items-center justify-content-between border-start border-danger border-4 shadow-sm">
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Unresolved Crashes</div>
                    <h3 class="fw-bold mb-0 text-danger">{{ $stats['unresolved'] ?? ($counts['unresolved'] ?? 0) }}</h3>
                </div>
                <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle">
                    <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern p-3 d-flex align-items-center justify-content-between border-start border-warning border-4 shadow-sm">
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Investigating</div>
                    <h3 class="fw-bold mb-0 text-warning">{{ $stats['investigating'] ?? ($counts['investigating'] ?? 0) }}</h3>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle">
                    <i class="fa-solid fa-magnifying-glass-chart fa-lg"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern p-3 d-flex align-items-center justify-content-between border-start border-success border-4 shadow-sm">
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Resolved</div>
                    <h3 class="fw-bold mb-0 text-success">{{ $stats['resolved'] ?? ($counts['resolved'] ?? 0) }}</h3>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                    <i class="fa-solid fa-circle-check fa-lg"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern p-3 d-flex align-items-center justify-content-between border-start border-primary border-4 shadow-sm">
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Total Recorded</div>
                    <h3 class="fw-bold mb-0 text-primary">{{ $stats['total'] ?? ($counts['total'] ?? 0) }}</h3>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                    <i class="fa-solid fa-bug fa-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Action Bar -->
    <div class="card-modern mb-4">
        <form action="{{ route('admin.crash-reports.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search error, location, phone, device..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="Unresolved" {{ strcasecmp(request('status'), 'unresolved') === 0 ? 'selected' : '' }}>🔴 Unresolved</option>
                    <option value="Investigating" {{ strcasecmp(request('status'), 'investigating') === 0 ? 'selected' : '' }}>🟡 Investigating</option>
                    <option value="Resolved" {{ strcasecmp(request('status'), 'resolved') === 0 ? 'selected' : '' }}>🟢 Resolved</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 rounded-pill">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-3 text-md-end">
                @if(request('search') || request('status'))
                    <a href="{{ route('admin.crash-reports.index') }}" class="btn btn-outline-secondary rounded-pill me-2">Clear</a>
                @endif
                <form action="{{ route('admin.crash-reports.clearResolved') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete all resolved crash reports?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger rounded-pill">
                        <i class="fa-solid fa-trash-can me-1"></i> Clear Resolved
                    </button>
                </form>
            </div>
        </form>
    </div>

    <!-- Reports Table with Bulk Action Form -->
    <div class="card-modern">
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

        <form id="bulkActionForm" action="{{ route('admin.crash-reports.bulkAction') }}" method="POST">
            @csrf

            <!-- Bulk Toolbar (Hidden until at least one item is checked) -->
            <div id="bulkToolbar" class="d-flex align-items-center justify-content-between p-2 mb-3 bg-light rounded-3 border" style="display: none !important;">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-pill px-2.5 py-1.5" id="selectedCountBadge">0 Selected</span>
                    <span class="small text-muted">একসাথে সমাধান করতে একশন বেছে নিন:</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" name="action" value="Resolved" class="btn btn-sm btn-success rounded-pill px-3">
                        <i class="fa-solid fa-check me-1"></i> Mark Resolved
                    </button>
                    <button type="submit" name="action" value="Investigating" class="btn btn-sm btn-warning text-dark rounded-pill px-3">
                        <i class="fa-solid fa-spinner me-1"></i> Mark Investigating
                    </button>
                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger rounded-pill px-3" onclick="return confirm('সিলেক্ট করা ক্র্যাশ রিপোর্টগুলো মুছে ফেলতে চান?')">
                        <i class="fa-solid fa-trash me-1"></i> Delete Selected
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 40px;">
                                <input type="checkbox" id="selectAllCheckbox" class="form-check-input" title="Select All">
                            </th>
                            <th style="width: 60px;">#</th>
                            <th>Exception & Crash Location</th>
                            <th>User / Phone</th>
                            <th>Device & OS</th>
                            <th>App Version</th>
                            <th>Status</th>
                            <th>Occurred At</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                        @php
                            $isResolved = strcasecmp($report->status, 'resolved') === 0;
                            $isInvestigating = strcasecmp($report->status, 'investigating') === 0;
                        @endphp
                        <tr>
                            <td class="ps-3">
                                <input type="checkbox" name="ids[]" value="{{ $report->id }}" class="form-check-input report-checkbox">
                            </td>
                            <td class="fw-bold text-muted">{{ $report->id }}</td>
                            <td>
                                <div class="fw-bold text-danger text-truncate" style="max-width: 320px;" title="{{ $report->exception_class }}">
                                    {{ class_basename($report->exception_class) ?: 'Exception' }}
                                </div>
                                <div class="small text-muted text-truncate" style="max-width: 320px;" title="{{ $report->exception_message ?: ($report->message ?: '') }}">
                                    {{ $report->exception_message ?: ($report->message ?: 'No message') }}
                                </div>
                                @if($report->crash_location)
                                    <span class="badge bg-light text-dark border extra-small mt-1 font-monospace">
                                        <i class="fa-solid fa-map-pin text-primary me-1"></i>{{ $report->crash_location }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($report->user)
                                    <div class="fw-bold">{{ $report->user->name }}</div>
                                    <div class="small text-muted">{{ $report->user->phone ?? $report->user_phone }}</div>
                                @elseif($report->user_phone)
                                    <div class="fw-bold">{{ $report->user_phone }}</div>
                                    <div class="extra-small text-muted">ID: {{ $report->user_id ?? 'N/A' }}</div>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Guest / Unknown</span>
                                @endif
                            </td>
                            <td>
                                <div class="small fw-semibold">{{ $report->device_model ?: 'Android Device' }}</div>
                                <div class="extra-small text-muted">Android {{ $report->android_version ?: 'N/A' }} (SDK {{ $report->sdk_version ?: 'N/A' }})</div>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                    v{{ $report->app_version ?: '1.0' }} ({{ $report->app_version_code ?: '-' }})
                                </span>
                            </td>
                            <td>
                                @if($isResolved)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                        <i class="fa-solid fa-check me-1"></i>Resolved
                                    </span>
                                @elseif($isInvestigating)
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                                        <i class="fa-solid fa-spinner fa-spin-pulse me-1"></i>Investigating
                                    </span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                        <i class="fa-solid fa-circle-exclamation me-1"></i>Unresolved
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="small">{{ $report->created_at ? $report->created_at->format('M d, Y h:i A') : 'N/A' }}</div>
                                <div class="extra-small text-muted">{{ $report->created_at ? $report->created_at->diffForHumans() : '' }}</div>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex align-items-center gap-1">
                                    @if(!$isResolved)
                                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2.5 py-0.5" title="Mark this and all similar resolved" onclick="quickResolveSimilar({{ $report->id }})">
                                            <i class="fa-solid fa-check-double me-1"></i>Solve All
                                        </button>
                                    @endif
                                    <a href="{{ route('admin.crash-reports.show', $report->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-0.5">
                                        <i class="fa-solid fa-eye me-1"></i>Details
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; padding: 0;" onclick="deleteReport({{ $report->id }})">
                                        <i class="fa-solid fa-trash-can small"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-shield-cat fa-3x mb-3 text-secondary opacity-50"></i>
                                <h6>No crash reports found!</h6>
                                <p class="small mb-0">The application is running stable or no crashes match your filter criteria.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        @if($reports->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Hidden Quick Action Forms -->
<form id="quickResolveForm" method="POST" style="display: none;">
    @csrf
</form>
<form id="singleDeleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.report-checkbox');
    const bulkToolbar = document.getElementById('bulkToolbar');
    const countBadge = document.getElementById('selectedCountBadge');

    function updateToolbar() {
        const checkedCount = document.querySelectorAll('.report-checkbox:checked').length;
        if (checkedCount > 0) {
            bulkToolbar.style.setProperty('display', 'flex', 'important');
            countBadge.innerText = checkedCount + ' Selected';
        } else {
            bulkToolbar.style.setProperty('display', 'none', 'important');
        }
        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateToolbar();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateToolbar);
    });
});

function quickResolveSimilar(id) {
    if (confirm('এই ক্র্যাশ এবং এর সাথে মিল থাকা একই ধরনের সব ক্র্যাশ রিপোর্ট একসাথে "Resolved" করতে চান?')) {
        const form = document.getElementById('quickResolveForm');
        form.action = "{{ url('admin/crash-reports') }}/" + id + "/resolve-similar";
        form.submit();
    }
}

function deleteReport(id) {
    if (confirm('Delete this crash report?')) {
        const form = document.getElementById('singleDeleteForm');
        form.action = "{{ url('admin/crash-reports') }}/" + id;
        form.submit();
    }
}
</script>
@endsection
