@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page_title', 'Welcome back, Admin')

@section('content')
<div class="fade-in">
    @if(auth()->user()->isSuperAdmin())
    <!-- Financial Overview (Super Admin Only) -->
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
    @endif

    <div class="row g-4 mb-5">
        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('users'))
        <!-- User Statistics -->
        <div class="{{ (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('withdrawals') || auth()->user()->hasPermission('microjobs') || auth()->user()->hasPermission('reselling') || auth()->user()->hasPermission('sim_offers') || auth()->user()->hasPermission('courses') || auth()->user()->hasPermission('leadership')) ? 'col-lg-8' : 'col-lg-12' }}">
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
                                <h6 class="text-muted small mb-1">
                                    <span class="spinner-grow spinner-grow-sm text-success me-1" role="status" style="width: 8px; height: 8px; animation-duration: 1.5s; vertical-align: middle;"></span>
                                    Active Now
                                </h6>
                                <h4 class="fw-bold mb-0" id="stat-active">{{ $stats['users']['active_now'] ?? 0 }}</h4>
                            </div>
                            <div class="bg-success-soft p-2 rounded-3 text-success"><i class="fa-solid fa-users-viewfinder"></i></div>
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
        @endif

        <!-- Pending Requests Table Style (Module Permission Filtered) -->
        <div class="{{ (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('users')) ? 'col-lg-4' : 'col-lg-12' }}">
            <h6 class="text-uppercase text-muted small fw-bold mb-3">Pending Action Required</h6>
            <div class="card-modern shadow-sm border-0">
                <ul class="list-group list-group-flush" id="pending-requests-list">
                    @if(auth()->user()->hasPermission('users'))
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2 border-bottom">
                        <a href="{{ route('admin.verifications.index') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-info-soft p-2 rounded-pill text-info" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-id-card small"></i></div>
                            <span class="small">Verification</span>
                        </a>
                        <span class="badge bg-info text-white rounded-pill" id="badge-verification">{{ $stats['pending_requests']['verification'] }}</span>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('withdrawals'))
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
                    @endif

                    @if(auth()->user()->hasPermission('reselling'))
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2 border-bottom">
                        <a href="{{ route('admin.orders.index', ['status' => 'Pending']) }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-primary-soft p-2 rounded-pill text-primary" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-cart-shopping small"></i></div>
                            <span class="small">Reselling Orders</span>
                        </a>
                        <span class="badge bg-primary text-white rounded-pill" id="badge-reselling">{{ $stats['pending_requests']['reselling'] }}</span>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('sim_offers'))
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2 border-bottom">
                        <a href="{{ route('admin.sim-offers.index') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-warning-soft p-2 rounded-pill text-warning" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-sim-card small"></i></div>
                            <span class="small">SIM Requests</span>
                        </a>
                        <span class="badge bg-warning text-white rounded-pill" id="badge-sim">{{ $stats['pending_requests']['sim_offers'] }}</span>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('courses'))
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2 border-bottom">
                        <a href="{{ route('admin.online-service-orders.index') }}" class="text-decoration-none d-flex align-items-center gap-3 text-dark">
                            <div class="bg-secondary-soft p-2 rounded-pill text-secondary" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-globe small"></i></div>
                            <span class="small">Online Services</span>
                        </a>
                        <span class="badge bg-secondary text-white rounded-pill" id="badge-services">{{ $stats['pending_requests']['services'] }}</span>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('leadership'))
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
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <!-- Quick Actions Grid (Permission-Filtered Navigation) -->
    <h6 class="text-uppercase text-muted small fw-bold mb-3">Quick Navigation (Management)</h6>
    <div class="row g-3 row-cols-2 row-cols-md-4 row-cols-lg-6 mb-5">
        @if(auth()->user()->hasPermission('users'))
        <div class="col">
            <a href="{{ route('admin.users.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-primary">
                <i class="fa-solid fa-users fa-xl"></i>
                <span class="small fw-bold">All Users</span>
            </a>
        </div>
        @endif

        @if(auth()->user()->hasPermission('microjobs'))
        <div class="col">
            <a href="{{ route('admin.microjobs.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-primary">
                <i class="fa-solid fa-briefcase fa-xl"></i>
                <span class="small fw-bold">Micro Jobs</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.job-settings.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-danger">
                <i class="fa-solid fa-circle-question fa-xl"></i>
                <span class="small fw-bold">Job Config</span>
            </a>
        </div>
        @endif

        @if(auth()->user()->hasPermission('sim_offers'))
        <div class="col">
            <a href="{{ route('admin.sim-offers.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-warning">
                <i class="fa-solid fa-sim-card fa-xl"></i>
                <span class="small fw-bold">SIM Offers</span>
            </a>
        </div>
        @endif

        @if(auth()->user()->hasPermission('reselling'))
        <div class="col">
            <a href="{{ route('admin.orders.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-success">
                <i class="fa-solid fa-truck-ramp-box fa-xl"></i>
                <span class="small fw-bold">Reselling Orders</span>
            </a>
        </div>
        @endif

        @if(auth()->user()->hasPermission('courses'))
        <div class="col">
            <a href="{{ route('admin.courses.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-info">
                <i class="fa-solid fa-graduation-cap fa-xl"></i>
                <span class="small fw-bold">Courses</span>
            </a>
        </div>
        @endif

        @if(auth()->user()->hasPermission('smm'))
        <div class="col">
            <a href="{{ route('admin.smm.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-purple">
                <i class="fa-solid fa-share-nodes fa-xl"></i>
                <span class="small fw-bold">SMM Submissions</span>
            </a>
        </div>
        @endif

        @if(auth()->user()->hasPermission('withdrawals'))
        <div class="col">
            <a href="{{ route('admin.withdraw-requests.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-danger">
                <i class="fa-solid fa-money-bill-transfer fa-xl"></i>
                <span class="small fw-bold">Withdrawals</span>
            </a>
        </div>
        @endif

        @if(auth()->user()->isSuperAdmin())
        <div class="col">
            <a href="{{ route('admin.banners.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-success">
                <i class="fa-solid fa-images fa-xl"></i>
                <span class="small fw-bold">Banners</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.bottom-banners.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-success">
                <i class="fa-solid fa-rectangle-ad fa-xl"></i>
                <span class="small fw-bold">Bottom Banners</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.support-center.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-info">
                <i class="fa-solid fa-headset fa-xl"></i>
                <span class="small fw-bold">Support Center</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('admin.settings.index') }}" class="btn btn-light w-100 p-3 h-100 shadow-sm border-0 d-flex flex-column gap-2 text-info">
                <i class="fa-solid fa-gears fa-xl"></i>
                <span class="small fw-bold">App Settings</span>
            </a>
        </div>
        @endif
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
                const elTotal = document.getElementById('stat-total');
                if (elTotal) elTotal.innerText = data.total;
                const elVerified = document.getElementById('stat-verified');
                if (elVerified) elVerified.innerText = data.verified;
                const elPending = document.getElementById('stat-pending');
                if (elPending) elPending.innerText = data.pending;
                const elActive = document.getElementById('stat-active');
                if (elActive) elActive.innerText = data.active_now || 0;
                
                const elBVerif = document.getElementById('badge-verification');
                if (elBVerif) elBVerif.innerText = data.verification_requests;
                const elBMoney = document.getElementById('badge-money');
                if (elBMoney) elBMoney.innerText = data.money_requests;
                const elBWithdraw = document.getElementById('badge-withdraw');
                if (elBWithdraw) elBWithdraw.innerText = data.withdraw_requests;
                const elBReselling = document.getElementById('badge-reselling');
                if (elBReselling) elBReselling.innerText = data.reselling_orders;
                const elBSim = document.getElementById('badge-sim');
                if (elBSim) elBSim.innerText = data.sim_requests;
                const elBServices = document.getElementById('badge-services');
                if (elBServices) elBServices.innerText = data.service_orders;
                const elBSalary = document.getElementById('badge-salary');
                if (elBSalary) elBSalary.innerText = data.salary_requests;
                const elBLeadership = document.getElementById('badge-leadership');
                if (elBLeadership) elBLeadership.innerText = data.leadership_requests;

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
        let getVal = id => {
            let el = document.getElementById(id);
            return el ? (parseInt(el.innerText) || 0) : null;
        };
        lastStats.verification_requests = getVal('badge-verification');
        lastStats.money_requests = getVal('badge-money');
        lastStats.withdraw_requests = getVal('badge-withdraw');
        lastStats.reselling_orders = getVal('badge-reselling');
        lastStats.sim_requests = getVal('badge-sim');
        lastStats.service_orders = getVal('badge-services');
        lastStats.salary_requests = getVal('badge-salary');
        lastStats.leadership_requests = getVal('badge-leadership');
    };
</script>
@endsection
