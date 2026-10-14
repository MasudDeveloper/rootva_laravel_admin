@extends('layouts.admin')

@section('title', 'App Crash Reports')
@section('page_title', 'App Crash & Error Analysis')

@section('content')
<div class="fade-in">
    <!-- Top Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card-modern p-3 d-flex align-items-center justify-content-between border-start border-danger border-4">
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Unresolved Crashes</div>
                    <h3 class="fw-bold mb-0 text-danger">{{ $stats['unresolved'] ?? 0 }}</h3>
                </div>
                <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle">
                    <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern p-3 d-flex align-items-center justify-content-between border-start border-warning border-4">
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Investigating</div>
                    <h3 class="fw-bold mb-0 text-warning">{{ $stats['investigating'] ?? 0 }}</h3>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle">
                    <i class="fa-solid fa-magnifying-glass-chart fa-lg"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern p-3 d-flex align-items-center justify-content-between border-start border-success border-4">
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Resolved</div>
                    <h3 class="fw-bold mb-0 text-success">{{ $stats['resolved'] ?? 0 }}</h3>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                    <i class="fa-solid fa-circle-check fa-lg"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern p-3 d-flex align-items-center justify-content-between border-start border-primary border-4">
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Total Recorded</div>
                    <h3 class="fw-bold mb-0 text-primary">{{ $stats['total'] ?? 0 }}</h3>
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
                    <option value="unresolved" {{ request('status') === 'unresolved' ? 'selected' : '' }}>Unresolved</option>
                    <option value="investigating" {{ request('status') === 'investigating' ? 'selected' : '' }}>Investigating</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
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
                        <i class="fa-solid fa-trash-sweep me-1"></i> Clear Resolved
                    </button>
                </form>
            </div>
        </form>
    </div>

    <!-- Reports Table -->
    <div class="card-modern">
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">#</th>
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
                    <tr>
                        <td class="ps-3 fw-bold text-muted">{{ $report->id }}</td>
                        <td>
                            <div class="fw-bold text-danger text-truncate" style="max-width: 320px;" title="{{ $report->exception_class }}">
                                {{ class_basename($report->exception_class) ?: 'Exception' }}
                            </div>
                            <div class="small text-muted text-truncate" style="max-width: 320px;" title="{{ $report->message }}">
                                {{ $report->message ?: 'No message' }}
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
                            @if($report->status === 'resolved')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                    <i class="fa-solid fa-check me-1"></i>Resolved
                                </span>
                            @elseif($report->status === 'investigating')
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
                            <a href="{{ route('admin.crash-reports.show', $report->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1">
                                <i class="fa-solid fa-eye me-1"></i>View Details
                            </a>
                            <form action="{{ route('admin.crash-reports.destroy', $report->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this crash log?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" style="width: 32px; height: 32px; padding: 0;">
                                    <i class="fa-solid fa-trash-can small"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-shield-cat fa-3x mb-3 text-muted opacity-50"></i>
                            <h6>No crash reports found!</h6>
                            <p class="small mb-0">The application is running stable or no crashes match your filter criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reports->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $reports->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
