@extends('layouts.admin')

@section('title', 'Top Wallet Holders List')
@section('page_title', '👑 Top Wallet Holders (অধিক ব্যালেন্সধারী ইউজার)')

@section('content')
@php
    $currentTier = $minBalance ?? '1000';
    $currentVer = $verType ?? 'real_verified';
@endphp
<div class="fade-in max-w-1200 mx-auto">
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

    <!-- ১. স্ট্যাটিস্টিকস কার্ড (Quick Summary Cards) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card-modern p-3 border-0 shadow-sm rounded-4 bg-white d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 fs-3">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold">১,০০০+ টাকা আছে</div>
                    <h4 class="fw-bold mb-0 text-dark">{{ number_format($stats['total_1k'] ?? 0) }} <span class="fs-6 fw-normal text-muted">জন</span></h4>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card-modern p-3 border-0 shadow-sm rounded-4 bg-white d-flex align-items-center gap-3">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-4 fs-3">
                    <i class="fa-solid fa-money-bill-trend-up"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold">৫,০০০+ টাকা আছে</div>
                    <h4 class="fw-bold mb-0 text-dark">{{ number_format($stats['total_5k'] ?? 0) }} <span class="fs-6 fw-normal text-muted">জন</span></h4>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card-modern p-3 border-0 shadow-sm rounded-4 bg-white d-flex align-items-center gap-3">
                <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-4 fs-3">
                    <i class="fa-solid fa-gem"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold">১০,০০০+ টাকা আছে</div>
                    <h4 class="fw-bold mb-0 text-dark">{{ number_format($stats['total_10k'] ?? 0) }} <span class="fs-6 fw-normal text-muted">জন</span></h4>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card-modern p-3 border-0 shadow-sm rounded-4 bg-white d-flex align-items-center gap-3">
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-4 fs-3">
                    <i class="fa-solid fa-vault"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold">১,০০০+ ওয়ালেটে মোট জমা</div>
                    <h4 class="fw-bold mb-0 text-success">৳{{ number_format($stats['total_balance_1k'] ?? 0, 0) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- ২. ফিল্টার ও সার্চ বার (Tiered Buttons & Search) -->
    <div class="card-modern p-4 mb-4 shadow-sm border-0 rounded-4">
        <!-- ভেরিফিকেশন টাইপ ফিল্টার (ডেমো বাদে বা রিয়েল ভেরিফাইড) -->
        <div class="mb-3 pb-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="fw-bold text-dark small me-2"><i class="fa-solid fa-user-shield text-success me-1"></i> একাউন্ট ভেরিফিকেশন ফিল্টার:</span>
                
                <a href="{{ route('admin.users.top-holders', ['min_balance' => $currentTier, 'ver_type' => 'real_verified', 'search' => $search]) }}" 
                   class="btn rounded-pill px-4 fw-bold small shadow-sm {{ $currentVer === 'real_verified' ? 'btn-success text-white' : 'btn-light border' }}">
                    <i class="fa-solid fa-check-circle me-1"></i> রিয়েল ভেরিফাইড (is_verified = 1)
                </a>

                <a href="{{ route('admin.users.top-holders', ['min_balance' => $currentTier, 'ver_type' => 'no_demo', 'search' => $search]) }}" 
                   class="btn rounded-pill px-4 fw-bold small shadow-sm {{ $currentVer === 'no_demo' ? 'btn-primary text-white' : 'btn-light border' }}">
                    <i class="fa-solid fa-ban me-1"></i> ডেমো বাদে সকল (is_verified != 3)
                </a>

                <a href="{{ route('admin.users.top-holders', ['min_balance' => $currentTier, 'ver_type' => 'demo_only', 'search' => $search]) }}" 
                   class="btn rounded-pill px-4 fw-bold small shadow-sm {{ $currentVer === 'demo_only' ? 'btn-warning text-dark' : 'btn-light border' }}">
                    <i class="fa-solid fa-flask me-1"></i> শুধুমাত্র ডেমো (is_verified = 3)
                </a>

                <a href="{{ route('admin.users.top-holders', ['min_balance' => $currentTier, 'ver_type' => 'all', 'search' => $search]) }}" 
                   class="btn rounded-pill px-4 fw-bold small shadow-sm {{ $currentVer === 'all' ? 'btn-dark text-white' : 'btn-light border' }}">
                    <i class="fa-solid fa-globe me-1"></i> সকল ইউজার (All)
                </a>
            </div>
            
            <div class="text-muted small">
                @if($currentVer === 'real_verified')
                    <span class="badge bg-success bg-opacity-10 text-success"><i class="fa-solid fa-shield-check me-1"></i> শুধুমাত্র রিয়েল ভেরিফাইড ইউজার দেখানো হচ্ছে</span>
                @elseif($currentVer === 'no_demo')
                    <span class="badge bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-info-circle me-1"></i> ডেমো ব্যালেন্স বাদে আসল ইউজার দেখানো হচ্ছে</span>
                @elseif($currentVer === 'demo_only')
                    <span class="badge bg-warning bg-opacity-10 text-dark"><i class="fa-solid fa-flask me-1"></i> শুধুমাত্র ডেমো একাউন্ট দেখানো হচ্ছে</span>
                @else
                    <span class="badge bg-secondary bg-opacity-10 text-secondary">সকল একাউন্ট দেখানো হচ্ছে</span>
                @endif
            </div>
        </div>

        <div class="row g-3 align-items-center justify-content-between">
            <div class="col-lg-7">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="fw-bold text-muted small me-2"><i class="fa-solid fa-filter text-primary me-1"></i> ব্যালেন্স অ্যামাউন্ট:</span>
                    
                    <a href="{{ route('admin.users.top-holders', ['min_balance' => '1000', 'ver_type' => $currentVer, 'search' => $search]) }}" 
                       class="btn rounded-pill px-4 fw-bold small shadow-sm {{ $currentTier === '1000' ? 'btn-primary' : 'btn-light border' }}">
                        <i class="fa-solid fa-coins me-1"></i> ১,০০০+ টাকা
                    </a>

                    <a href="{{ route('admin.users.top-holders', ['min_balance' => '5000', 'ver_type' => $currentVer, 'search' => $search]) }}" 
                       class="btn rounded-pill px-4 fw-bold small shadow-sm {{ $currentTier === '5000' ? 'btn-success text-white' : 'btn-light border' }}">
                        <i class="fa-solid fa-money-bill-trend-up me-1"></i> ৫,০০০+ টাকা
                    </a>

                    <a href="{{ route('admin.users.top-holders', ['min_balance' => '10000', 'ver_type' => $currentVer, 'search' => $search]) }}" 
                       class="btn rounded-pill px-4 fw-bold small shadow-sm {{ $currentTier === '10000' ? 'btn-warning text-dark' : 'btn-light border' }}">
                        <i class="fa-solid fa-gem me-1"></i> ১০,০০০+ টাকা
                    </a>

                    <a href="{{ route('admin.users.top-holders', ['min_balance' => 'all', 'ver_type' => $currentVer, 'search' => $search]) }}" 
                       class="btn rounded-pill px-4 fw-bold small shadow-sm {{ $currentTier === 'all' ? 'btn-dark' : 'btn-light border' }}">
                        <i class="fa-solid fa-users me-1"></i> সকল (All)
                    </a>
                </div>
            </div>

            <div class="col-lg-5">
                <form action="{{ route('admin.users.top-holders') }}" method="GET" class="d-flex gap-2">
                    <input type="hidden" name="min_balance" value="{{ $currentTier }}">
                    <input type="hidden" name="ver_type" value="{{ $currentVer }}">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 rounded-end-pill p-2" 
                               placeholder="নাম, ফোন বা রেফার কোড দিয়ে খুঁজুন..." value="{{ $search }}">
                    </div>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">খুঁজুন</button>
                    @if($search)
                    <a href="{{ route('admin.users.top-holders', ['min_balance' => $currentTier, 'ver_type' => $currentVer]) }}" class="btn btn-outline-danger rounded-pill px-3" title="Clear Search"><i class="fa-solid fa-xmark"></i></a>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <!-- ৩. ইউজার তালিকা (Top Holders Table) -->
    <div class="card-modern shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="p-4 bg-light border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-trophy text-warning me-2"></i> অধিক ব্যালেন্সধারী ইউজারদের তালিকা (সর্বোচ্চ ব্যালেন্স সবার উপরে)</h5>
            <span class="badge bg-primary rounded-pill px-3 py-2">মোট পাওয়া গেছে: {{ $users->total() }} জন</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 text-center" style="width: 70px;">Rank</th>
                        <th>User Name (নাম)</th>
                        <th>Mobile Number</th>
                        <th>Refer Code</th>
                        <th>Referred By</th>
                        <th class="text-end">Wallet Balance (ব্যালেন্স)</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $user)
                        @php
                            $rank = ($users->currentPage() - 1) * $users->perPage() + $index + 1;
                        @endphp
                        <tr class="{{ $rank === 1 ? 'table-warning bg-opacity-25' : ($rank === 2 ? 'bg-light' : '') }}">
                            <td class="ps-4 text-center">
                                @if($rank === 1)
                                    <span class="badge bg-warning text-dark fs-6 rounded-pill shadow-sm" title="1st Place">🥇 #1</span>
                                @elseif($rank === 2)
                                    <span class="badge bg-secondary text-white fs-6 rounded-pill shadow-sm" title="2nd Place">🥈 #2</span>
                                @elseif($rank === 3)
                                    <span class="badge bg-info text-dark fs-6 rounded-pill shadow-sm" title="3rd Place">🥉 #3</span>
                                @else
                                    <span class="fw-bold text-muted">#{{ $rank }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-box rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-6">{{ $user->name ?? 'Unnamed User' }}</div>
                                        <div class="small text-muted">ID: #{{ $user->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><a href="tel:{{ $user->number }}" class="text-decoration-none fw-bold text-dark"><i class="fa-solid fa-phone small text-muted me-1"></i>{{ $user->number }}</a></td>
                            <td><span class="badge bg-primary bg-opacity-10 text-primary fw-bold font-monospace px-3 py-2 rounded-pill">{{ $user->referCode }}</span></td>
                            <td>
                                @if($user->refer_id)
                                    <span class="badge bg-light text-dark border font-monospace">{{ $user->refer_id }}</span>
                                @else
                                    <span class="text-muted small">Direct</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="fs-5 fw-extrabold text-success font-monospace">
                                    <i class="fa-solid fa-vault small me-1 opacity-75"></i>৳{{ number_format($user->wallet_balance, 2) }}
                                </div>
                            </td>
                            <td class="text-center">
                                @if($user->is_verified == 1 || $user->is_verified == 3)
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill"><i class="fa-solid fa-check-circle me-1"></i>Verified</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-1 rounded-pill"><i class="fa-solid fa-times-circle me-1"></i>Unverified</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                                    <i class="fa-solid fa-eye me-1"></i> View Profile
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-vault fs-1 mb-3 d-block text-secondary opacity-50"></i>
                                <h5 class="fw-bold">কোনো ইউজার পাওয়া যায়নি!</h5>
                                <p class="small mb-0">নির্বাচিত ধাপে (৳{{ $currentTier }}+) কোনো ইউজারের একাউন্টে ব্যালেন্স নেই।</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-top bg-light">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

<style>
.fw-extrabold { font-weight: 800; }
.card-modern { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.card-modern:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.06) !important; }
</style>
@endsection
