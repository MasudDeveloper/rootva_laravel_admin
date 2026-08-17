<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') | Rootva</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Heroicons / FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/admin-custom.css') }}">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @yield('styles')
</head>
<body x-data="{ sidebarOpen: true }">

    <!-- Sidebar -->
    <aside class="sidebar" :class="sidebarOpen || 'sidebar-closed'" 
           :style="sidebarOpen ? '' : 'transform: translateX(-100%)'">
        <div class="sidebar-header d-flex align-items-center gap-3">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" style="width: 40px; height: 40px; border-radius: 10px;">
            <span class="sidebar-logo">Rootva Admin</span>
        </div>
        
        <nav class="mt-4">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->is('admin/dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ (request()->is('admin/users') || (request()->is('admin/users/*') && !request()->is('admin/users/top-holders*'))) ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i>
                <span>Users Management</span>
            </a>
            
            <a href="{{ route('admin.users.top-holders') }}" class="nav-link {{ request()->is('admin/users/top-holders*') ? 'active' : '' }}">
                <i class="fa-solid fa-vault text-success"></i>
                <span>Top Wallet Holders</span>
            </a>
            
            <div class="px-4 mt-4 mb-2 text-uppercase text-muted small fw-bold">Service Modules</div>
            
            <a href="{{ route('admin.microjobs.index') }}" class="nav-link {{ request()->is('admin/microjobs*') ? 'active' : '' }}">
                <i class="fa-solid fa-briefcase"></i>
                <span>Micro Jobs</span>
            </a>

            <a href="{{ route('admin.review-jobs.index') }}" class="nav-link {{ request()->is('admin/review-jobs*') ? 'active' : '' }}">
                <i class="fa-solid fa-star"></i>
                <span>Review Jobs</span>
            </a>

            <a href="{{ route('admin.job-settings.index') }}" class="nav-link {{ request()->is('admin/services/job-settings*') ? 'active' : '' }}">
                <i class="fa-solid fa-list-check"></i>
                <span>Job Configurations</span>
            </a>

            <a href="{{ route('admin.verifications.index') }}" class="nav-link {{ request()->is('admin/services/verifications*') ? 'active' : '' }}">
                <i class="fa-solid fa-shield-check"></i>
                <span>User Verifications</span>
            </a>

            <a href="{{ route('admin.sim-offers.index') }}" class="nav-link {{ request()->is('admin/services/sim-offers*') ? 'active' : '' }}">
                <i class="fa-solid fa-sim-card"></i>
                <span>SIM Offers</span>
            </a>

            <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->is('admin/services/products*') ? 'active' : '' }}">
                <i class="fa-solid fa-shop"></i>
                <span>Reselling Shop</span>
            </a>

            <a href="{{ route('admin.vendors.index') }}" class="nav-link {{ request()->is('admin/services/vendors*') ? 'active' : '' }}">
                <i class="fa-solid fa-users-gear"></i>
                <span>Vendor Management</span>
            </a>

            <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->is('admin/orders*') ? 'active' : '' }}">
                <i class="fa-solid fa-truck-ramp-box"></i>
                <span>Reselling Orders</span>
            </a>

            <a href="{{ route('admin.courses.index') }}" class="nav-link {{ request()->is('admin/services/courses*') ? 'active' : '' }}">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>Courses</span>
            </a>

            <a href="{{ route('admin.online-services.index') }}" class="nav-link {{ request()->is('admin/services/online-services*') ? 'active' : '' }}">
                <i class="fa-solid fa-gear"></i>
                <span>Service List</span>
            </a>

            <a href="{{ route('admin.online-service-orders.index') }}" class="nav-link {{ request()->is('admin/services/online-service-orders*') ? 'active' : '' }}">
                <i class="fa-solid fa-cart-shopping"></i>
                <span>Online Service Orders</span>
            </a>

            <div class="px-4 mt-4 mb-2 text-uppercase text-muted small fw-bold">Admin Tools</div>
            <a href="{{ route('admin.notifications.index') }}" class="nav-link {{ request()->is('admin/notifications') || request()->is('admin/notifications/send') ? 'active' : '' }}">
                <i class="fa-solid fa-bullhorn"></i>
                <span>Push Notifications</span>
            </a>
            <a href="{{ route('admin.notifications.saved.index') }}" class="nav-link {{ request()->is('admin/notifications/saved*') ? 'active' : '' }}">
                <i class="fa-solid fa-bookmark"></i>
                <span>Saved Notifications</span>
            </a>
            <a href="{{ route('admin.popups.index') }}" class="nav-link {{ request()->is('admin/popups*') ? 'active' : '' }}">
                <i class="fa-solid fa-window-maximize"></i>
                <span>Popup Banners</span>
            </a>

            <div class="px-4 mt-4 mb-2 text-uppercase text-muted small fw-bold">Leadership & Rewards</div>
            <a href="{{ route('admin.leadership.leaders') }}" class="nav-link {{ request()->is('admin/services/leadership/leaders*') ? 'active' : '' }}">
                <i class="fa-solid fa-users-viewfinder"></i>
                <span>Leaders List</span>
            </a>
            <a href="{{ route('admin.leadership.requests') }}" class="nav-link {{ request()->is('admin/services/leadership/requests*') ? 'active' : '' }}">
                <i class="fa-solid fa-crown"></i>
                <span>Reward Claims</span>
            </a>
            <a href="{{ route('admin.leadership.history') }}" class="nav-link {{ request()->is('admin/services/leadership/history*') ? 'active' : '' }}">
                <i class="fa-solid fa-trophy"></i>
                <span>Winners History</span>
            </a>

            <div class="px-4 mt-4 mb-2 text-uppercase text-muted small fw-bold">System Management</div>
            
            <a href="{{ route('admin.api-endpoints.index') }}" class="nav-link {{ request()->is('admin/api-endpoints*') ? 'active' : '' }}">
                <i class="fa-solid fa-code"></i>
                <span>API Endpoints</span>
            </a>

            <div class="px-4 mt-4 mb-2 text-uppercase text-muted small fw-bold">PCash Automated API</div>
            
            <a href="{{ route('admin.pcash.settings.index') }}" class="nav-link {{ request()->is('admin/pcash/settings*') ? 'active' : '' }}">
                <i class="fa-solid fa-plug-circle-bolt"></i>
                <span>API Settings & Balance</span>
            </a>
            <a href="{{ route('admin.pcash.sim_offers.index') }}" class="nav-link {{ request()->is('admin/pcash/sim_offers*') ? 'active' : '' }}">
                <i class="fa-solid fa-sim-card"></i>
                <span>Automated SIM Offers</span>
            </a>
            <a href="{{ route('admin.pcash.logs.index') }}" class="nav-link {{ request()->is('admin/pcash/logs*') ? 'active' : '' }}">
                <i class="fa-solid fa-file-invoice"></i>
                <span>Recharge Logs</span>
            </a>

            <a href="{{ route('admin.smm.index') }}" class="nav-link {{ request()->is('admin/smm*') ? 'active' : '' }}">
                <i class="fa-solid fa-share-nodes"></i>
                <span>SMM Submissions</span>
            </a>

            <div class="px-4 mt-4 mb-2 text-uppercase text-muted small fw-bold">Financials</div>
            
            <a href="{{ route('admin.money-requests.index') }}" class="nav-link {{ request()->is('admin/money-requests*') ? 'active' : '' }}">
                <i class="fa-solid fa-wallet"></i>
                <span>Money Requests</span>
            </a>
            
            <a href="{{ route('admin.withdraw-requests.index') }}" class="nav-link {{ request()->is('admin/withdraw-requests*') ? 'active' : '' }}">
                <i class="fa-solid fa-money-bill-transfer"></i>
                <span>Withdrawal</span>
            </a>

            <a href="{{ route('admin.salary-requests.index') }}" class="nav-link {{ request()->is('admin/salary-requests*') ? 'active' : '' }}">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                <span>Salary Requests</span>
            </a>

            <a href="{{ route('admin.support-admin-report.index') }}" class="nav-link {{ request()->is('admin/support-admin-report*') ? 'active' : '' }}">
                <i class="fa-solid fa-user-tie"></i>
                <span>Support Admin Report</span>
            </a>

            <div class="px-4 mt-4 mb-2 text-uppercase text-muted small fw-bold">Rewards & Bonuses</div>
            
            <a href="{{ route('admin.rewards.daily') }}" class="nav-link {{ request()->is('admin/rewards/daily*') ? 'active' : '' }}">
                <i class="fa-solid fa-calendar-day"></i>
                <span>Daily Bonus</span>
            </a>

            <a href="{{ route('admin.rewards.weekly') }}" class="nav-link {{ request()->is('admin/rewards/weekly*') ? 'active' : '' }}">
                <i class="fa-solid fa-calendar-week"></i>
                <span>Weekly Bonus</span>
            </a>

            <a href="{{ route('admin.rewards.spin') }}" class="nav-link {{ request()->is('admin/rewards/spin*') ? 'active' : '' }}">
                <i class="fa-solid fa-circle-notch fa-spin-slow"></i>
                <span>Spin History</span>
            </a>

            <a href="{{ route('admin.rewards.refer-bonus') }}" class="nav-link {{ request()->is('admin/rewards/refer-bonus*') ? 'active' : '' }}">
                <i class="fa-solid fa-sitemap"></i>
                <span>Manual Refer Bonus</span>
            </a>

            <a href="{{ route('admin.rewards.date-bonus') }}" class="nav-link {{ request()->is('admin/rewards/date-bonus*') ? 'active' : '' }}">
                <i class="fa-solid fa-calendar-check text-warning"></i>
                <span>Date-wise Bulk Bonus</span>
            </a>

            <div class="px-4 mt-4 mb-2 text-uppercase text-muted small fw-bold">Settings</div>
            
            <a href="{{ route('admin.banners.index') }}" class="nav-link {{ request()->is('admin/banners*') ? 'active' : '' }}">
                <i class="fa-solid fa-images"></i>
                <span>Banner Manager</span>
            </a>

            <a href="{{ route('admin.reviews.index') }}" class="nav-link {{ request()->is('admin/reviews*') ? 'active' : '' }}">
                <i class="fa-solid fa-star-half-stroke"></i>
                <span>Review Banners</span>
            </a>

            <a href="{{ route('admin.bottom-banners.index') }}" class="nav-link {{ request()->is('admin/bottom-banners*') ? 'active' : '' }}">
                <i class="fa-solid fa-rectangle-ad"></i>
                <span>Bottom Banners</span>
            </a>

            <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}">
                <i class="fa-solid fa-gears"></i>
                <span>Global Settings</span>
            </a>
        </nav>
    </aside>

    <!-- App Content Wrapper -->
    <div class="main-content" :style="sidebarOpen ? '' : 'margin-left: 0'">
        
        <!-- Topbar -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="btn btn-light rounded-circle shadow-sm">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
                <h5 class="mb-0 fw-bold d-none d-md-block">@yield('page_title', 'Dashboard')</h5>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Cache Clear Button -->
                <a href="{{ route('admin.clear-cache') }}" class="btn btn-outline-warning rounded-pill px-3 shadow-sm d-flex align-items-center gap-2" onclick="return confirm('Are you sure you want to clear system cache?');">
                    <i class="fa-solid fa-broom text-warning"></i>
                    <span class="d-none d-lg-inline fw-semibold small text-dark">Clear Cache</span>
                </a>

                <div class="position-relative">
                    <button class="btn btn-light rounded-circle shadow-sm">
                        <i class="fa-solid fa-bell"></i>
                    </button>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px">
                        3+
                    </span>
                </div>
                
                <div class="dropdown">
                    <button class="btn btn-light d-flex align-items-center gap-2 p-1 pe-3 rounded-pill shadow-sm dropdown-toggle" 
                            type="button" data-bs-toggle="dropdown">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 32px; height: 32px; font-weight: 600;">
                            A
                        </div>
                        <span class="d-none d-md-inline small fw-semibold">Admin</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><a class="dropdown-item p-2 px-3" href="#"><i class="fa-solid fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('admin.logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item p-2 px-3 text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Content Inner -->
        <main class="p-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-radius: 10px;">
                    <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-radius: 10px;">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @yield('content')
        </main>
        
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @yield('scripts')
</body>
</html>
