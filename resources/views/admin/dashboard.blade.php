@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page_title', 'Welcome back, Admin')

@section('content')
<div class="fade-in">
    <!-- Financial Overview -->
    <h6 class="text-uppercase text-muted small fw-bold mb-3 mt-2">Financial Overview</h6>
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card-modern">
                <div class="stat-icon bg-primary-soft">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <h6 class="text-muted small mb-1">Total Balance</h6>
                <h3 class="fw-extrabold mb-0">৳{{ number_format($stats['total_balance'], 2) }}</h3>
                <div class="mt-2 small text-primary">All users combined</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-modern">
                <div class="stat-icon bg-warning-soft">
                    <i class="fa-solid fa-flask"></i>
                </div>
                <h6 class="text-muted small mb-1">Demo Balance</h6>
                <h3 class="fw-extrabold mb-0">৳{{ number_format($stats['demo_balance'], 2) }}</h3>
                <div class="mt-2 small text-warning">Test/Demo accounts</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-modern">
                <div class="stat-icon bg-success-soft">
                    <i class="fa-solid fa-shield-check"></i>
                </div>
                <h6 class="text-muted small mb-1">Real Liabilities</h6>
                <h3 class="fw-extrabold mb-0">৳{{ number_format($stats['real_balance'], 2) }}</h3>
                <div class="mt-2 small text-success">Actual due amount</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <!-- User Statistics -->
        <div class="col-lg-8">
            <h6 class="text-uppercase text-muted small fw-bold mb-3">User Statistics</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card-modern border-start border-4 border-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted small mb-1">Total Users</h6>
                                <h4 class="fw-bold mb-0" id="stat-total">{{ $stats['users']['total'] }}</h4>
                            </div>
                            <div class="bg-info-soft p-2 rounded-3 text-info"><i class="fa-solid fa-users"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-modern border-start border-4 border-success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted small mb-1">Verified</h6>
                                <h4 class="fw-bold mb-0" id="stat-verified">{{ $stats['users']['verified'] }}</h4>
                            </div>
                            <div class="bg-success-soft p-2 rounded-3 text-success"><i class="fa-solid fa-user-check"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-modern border-start border-4 border-warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted small mb-1">Pending</h6>
                                <h4 class="fw-bold mb-0" id="stat-pending">{{ $stats['users']['pending'] }}</h4>
                            </div>
                            <div class="bg-warning-soft p-2 rounded-3 text-warning"><i class="fa-solid fa-user-clock"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-modern border-start border-4 border-secondary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted small mb-1">Unverified</h6>
                                <h4 class="fw-bold mb-0">{{ $stats['users']['unverified'] }}</h4>
                            </div>
                            <div class="bg-light p-2 rounded-3 text-secondary"><i class="fa-solid fa-user-slash"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-modern border-start border-4 border-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted small mb-1">Demo Users</h6>
                                <h4 class="fw-bold mb-0">{{ $stats['users']['demo'] }}</h4>
                            </div>
                            <div class="bg-primary-soft p-2 rounded-3 text-primary"><i class="fa-solid fa-user-gear"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-modern border-start border-4 border-danger">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted small mb-1">Suspended</h6>
                                <h4 class="fw-bold mb-0">{{ $stats['users']['suspended'] }}</h4>
                            </div>
                            <div class="bg-danger-soft p-2 rounded-3 text-danger"><i class="fa-solid fa-user-xmark"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Requests Table Style -->
        <div class="col-lg-4">
            <h6 class="text-uppercase text-muted small fw-bold mb-3">Pending Action Required</h6>
            <div class="card-modern">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3">
                        <a href="{{ route('admin.verifications.index') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-info-soft p-2 rounded-pill text-info"><i class="fa-solid fa-id-card"></i></div>
                            <span>Verification Requests</span>
                        </a>
                        <span class="badge bg-info-soft text-info rounded-pill" id="badge-verification">{{ $stats['pending_requests']['verification'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3">
                        <a href="{{ route('admin.money-requests.index') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-success-soft p-2 rounded-pill text-success"><i class="fa-solid fa-circle-dollar-to-slot"></i></div>
                            <span>Add Money Requests</span>
                        </a>
                        <span class="badge bg-success-soft text-success rounded-pill" id="badge-money">{{ $stats['pending_requests']['money'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3">
                        <a href="{{ route('admin.withdraw-requests.index') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-danger-soft p-2 rounded-pill text-danger"><i class="fa-solid fa-money-bill-transfer"></i></div>
                            <span>Withdraw Requests</span>
                        </a>
                        <span class="badge bg-danger-soft text-danger rounded-pill" id="badge-withdraw">{{ $stats['pending_requests']['withdraw'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3">
                        <a href="{{ route('admin.microjobs.index') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-warning-soft p-2 rounded-pill text-warning"><i class="fa-solid fa-briefcase"></i></div>
                            <span>Microjob Posts</span>
                        </a>
                        <span class="badge bg-warning-soft text-warning rounded-pill" id="badge-microjobs">{{ $stats['pending_requests']['microjobs'] }}</span>
                    </li>
                </ul>
                <div class="mt-3">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-primary-soft w-100 rounded-pill">View All Users</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Grid (Matching the concept of the old sidebar) -->
    <h6 class="text-uppercase text-muted small fw-bold mb-3">Quick Navigation (Management)</h6>
    <div class="row g-3 row-cols-2 row-cols-md-4 row-cols-lg-6 mb-5">
        <div class="col">
            <a href="{{ route('admin.users.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-primary">
                <i class="fa-solid fa-users fa-xl"></i>
                <span class="small fw-bold">All Users</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.banners.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-success">
                <i class="fa-solid fa-images fa-xl"></i>
                <span class="small fw-bold">Banners</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.rewards.daily') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-warning">
                <i class="fa-solid fa-gift fa-xl"></i>
                <span class="small fw-bold">Daily Bonus</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.job-settings.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-danger">
                <i class="fa-solid fa-circle-question fa-xl"></i>
                <span class="small fw-bold">Tutorials</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.settings.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-info">
                <i class="fa-solid fa-gears fa-xl"></i>
                <span class="small fw-bold">App Settings</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.settings.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-secondary">
                <i class="fa-solid fa-star fa-xl"></i>
                <span class="small fw-bold">Review Link</span>
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function refreshStats() {
        fetch('/admin/api/stats')
            .then(res => res.json())
            .then(data => {
                document.getElementById('stat-total').innerText = data.total;
                document.getElementById('stat-verified').innerText = data.verified;
                document.getElementById('stat-pending').innerText = data.pending;
                document.getElementById('badge-verification').innerText = data.verification_requests;
                document.getElementById('badge-money').innerText = data.money_requests;
                document.getElementById('badge-withdraw').innerText = data.withdraw_requests;
                document.getElementById('badge-microjobs').innerText = data.microjobs_requests;
            });
    }

    // Refresh every 10 seconds
    setInterval(refreshStats, 10000);
</script>
@endsection
