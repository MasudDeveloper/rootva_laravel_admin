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
            <div class="card-modern shadow-sm border-0">
                <ul class="list-group list-group-flush" id="pending-requests-list">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2 border-bottom">
                        <a href="{{ route('admin.verifications.index') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-info-soft p-2 rounded-pill text-info" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-id-card small"></i></div>
                            <span class="small">Verification</span>
                        </a>
                        <span class="badge bg-info text-white rounded-pill" id="badge-verification">{{ $stats['pending_requests']['verification'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2 border-bottom">
                        <a href="{{ route('admin.money-requests.index') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-success-soft p-2 rounded-pill text-success" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-circle-dollar-to-slot small"></i></div>
                            <span class="small">Add Money</span>
                        </a>
                        <span class="badge bg-success text-white rounded-pill" id="badge-money">{{ $stats['pending_requests']['money'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2 border-bottom">
                        <a href="{{ route('admin.withdraw-requests.index') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-danger-soft p-2 rounded-pill text-danger" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-money-bill-transfer small"></i></div>
                            <span class="small">Withdraws</span>
                        </a>
                        <span class="badge bg-danger text-white rounded-pill" id="badge-withdraw">{{ $stats['pending_requests']['withdraw'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2 border-bottom">
                        <a href="{{ route('admin.orders.index', ['status' => 'Pending']) }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-primary-soft p-2 rounded-pill text-primary" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-cart-shopping small"></i></div>
                            <span class="small">Reselling Orders</span>
                        </a>
                        <span class="badge bg-primary text-white rounded-pill" id="badge-reselling">{{ $stats['pending_requests']['reselling'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2 border-bottom">
                        <a href="{{ route('admin.sim-offers.index') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-warning-soft p-2 rounded-pill text-warning" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-sim-card small"></i></div>
                            <span class="small">SIM Requests</span>
                        </a>
                        <span class="badge bg-warning text-white rounded-pill" id="badge-sim">{{ $stats['pending_requests']['sim_offers'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2 border-bottom">
                        <a href="{{ route('admin.online-service-orders.index') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-secondary-soft p-2 rounded-pill text-secondary" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-globe small"></i></div>
                            <span class="small">Online Services</span>
                        </a>
                        <span class="badge bg-secondary text-white rounded-pill" id="badge-services">{{ $stats['pending_requests']['services'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2 border-bottom">
                        <a href="{{ route('admin.salary-requests.index') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-dark-soft p-2 rounded-pill text-dark" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-file-invoice-dollar small"></i></div>
                            <span class="small">Salary Requests</span>
                        </a>
                        <span class="badge bg-dark text-white rounded-pill" id="badge-salary">{{ $stats['pending_requests']['salary'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2">
                        <a href="{{ route('admin.leadership.requests') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-primary-soft p-2 rounded-pill text-primary" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-trophy small"></i></div>
                            <span class="small">Leadership Rewards</span>
                        </a>
                        <span class="badge bg-info text-white rounded-pill" id="badge-leadership">{{ $stats['pending_requests']['leadership'] }}</span>
                    </li>
                </ul>
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

<audio id="notification-sound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let lastStats = {
        verification_requests: null,
        money_requests: null,
        withdraw_requests: null,
        reselling_orders: null,
        sim_requests: null,
        service_orders: null,
        salary_requests: null,
        leadership_requests: null
    };

    const requestLabels = {
        verification_requests: 'User Verification',
        money_requests: 'Add Money',
        withdraw_requests: 'Withdraw',
        reselling_orders: 'Reselling Order',
        sim_requests: 'SIM Offer',
        service_orders: 'Online Service',
        salary_requests: 'Salary',
        leadership_requests: 'Leadership Reward'
    };

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

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
                document.getElementById('badge-reselling').innerText = data.reselling_orders;
                document.getElementById('badge-sim').innerText = data.sim_requests;
                document.getElementById('badge-services').innerText = data.service_orders;
                document.getElementById('badge-salary').innerText = data.salary_requests;
                document.getElementById('badge-leadership').innerText = data.leadership_requests;

                // Check for new requests
                for (let key in lastStats) {
                    if (lastStats[key] !== null && data[key] > lastStats[key]) {
                        let newCount = data[key] - lastStats[key];
                        showNotification(requestLabels[key], newCount);
                    }
                    lastStats[key] = data[key];
                }
            });
    }

    function showNotification(label, count) {
        // Play Sound
        document.getElementById('notification-sound').play().catch(e => console.log('Audio play failed:', e));
        
        // Show Toast
        Toast.fire({
            icon: 'info',
            title: `New ${label} request received!`,
            text: count > 1 ? `${count} new requests` : `A new ${label.toLowerCase()} request is waiting.`
        });
    }

    // Refresh every 10 seconds
    setInterval(refreshStats, 10000);
    
    // Initial load
    window.onload = function() {
        // Initialize lastStats from current badges
        lastStats.verification_requests = parseInt(document.getElementById('badge-verification').innerText) || 0;
        lastStats.money_requests = parseInt(document.getElementById('badge-money').innerText) || 0;
        lastStats.withdraw_requests = parseInt(document.getElementById('badge-withdraw').innerText) || 0;
        lastStats.reselling_orders = parseInt(document.getElementById('badge-reselling').innerText) || 0;
        lastStats.sim_requests = parseInt(document.getElementById('badge-sim').innerText) || 0;
        lastStats.service_orders = parseInt(document.getElementById('badge-services').innerText) || 0;
        lastStats.salary_requests = parseInt(document.getElementById('badge-salary').innerText) || 0;
        lastStats.leadership_requests = parseInt(document.getElementById('badge-leadership').innerText) || 0;
    };
</script>
@endsection
