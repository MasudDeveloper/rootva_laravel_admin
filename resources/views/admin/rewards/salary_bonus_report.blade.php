@extends('layouts.admin')

@section('title', 'Salary & Bonus Report')
@section('page_title', '📊 বেতন ও বোনাস হিসাব রিপোর্ট')

@section('content')
<div class="fade-in">
    <!-- Top Action & Month Filter Bar -->
    <div class="card-modern p-4 mb-4 border-0 shadow-sm">
        <div class="row g-3 align-items-center justify-content-between">
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-center" style="width: 52px; height: 52px;">
                        <i class="fa-solid fa-file-invoice-dollar fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">
                            বেতন ও বোনাস অডিট ও সামারি রিপোর্ট
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 ms-2 px-3 py-1 rounded-pill">
                                {{ date('F Y', strtotime($selectedMonth . '-01')) }}
                            </span>
                        </h5>
                        <p class="text-muted small mb-0">নির্বাচিত মাসের সকল বেতন, ডেইলি বোনাস, সাপ্তাহিক বোনাস এবং অন্যান্য বোনাসের সম্পূর্ণ অডিট ও হিসাব বিবরণী</p>
                    </div>
                </div>
            </div>

            <!-- Month Selector & Export Form -->
            <div class="col-lg-6">
                <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                    <form action="{{ route('admin.rewards.salary-bonus-report') }}" method="GET" class="d-flex align-items-center gap-2">
                        @if(!empty($filterGateway) && $filterGateway !== 'all')
                            <input type="hidden" name="gateway" value="{{ $filterGateway }}">
                        @endif
                        @if(!empty($search))
                            <input type="hidden" name="search" value="{{ $search }}">
                        @endif
                        <div class="input-group input-group-sm" style="max-width: 200px;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-regular fa-calendar"></i></span>
                            <input type="month" name="month" value="{{ $selectedMonth }}" class="form-control form-control-sm border-start-0 fw-bold shadow-none" required>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                            <i class="fa-solid fa-filter me-1"></i>হিসাব দেখুন
                        </button>
                    </form>

                    <a href="{{ route('admin.rewards.salary-bonus-report.export', ['month' => $selectedMonth, 'gateway' => $filterGateway]) }}" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm text-white">
                        <i class="fa-solid fa-file-csv me-1"></i>CSV ডাউনলোড
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Month Switcher Pills -->
        <hr class="my-3 opacity-10">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted small fw-bold me-1"><i class="fa-solid fa-bolt text-warning me-1"></i>দ্রুত মাস নির্বাচন:</span>
            @php
            $quickMonths = [
                'current' => date('Y-m'),
                'last'    => date('Y-m', strtotime('first day of -1 month')),
                'prev2'   => date('Y-m', strtotime('first day of -2 months')),
                'prev3'   => date('Y-m', strtotime('first day of -3 months')),
                'prev4'   => date('Y-m', strtotime('first day of -4 months')),
            ];
            @endphp
            @foreach($quickMonths as $key => $mCode)
                @php
                $isActive = ($selectedMonth === $mCode);
                $label = date('M Y', strtotime($mCode . '-01'));
                if ($key === 'current') $label .= ' (চলতি মাস)';
                elseif ($key === 'last') $label .= ' (গত মাস)';
                @endphp
                <a href="{{ route('admin.rewards.salary-bonus-report', ['month' => $mCode]) }}" class="btn btn-sm {{ $isActive ? 'btn-primary text-white shadow-sm fw-bold' : 'btn-light text-secondary border' }} rounded-pill px-3 py-1" style="font-size: 11px;">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Key Metrics Cards for Selected Month -->
    <div class="row g-3 mb-4">
        <!-- Grand Total Disbursed Card -->
        <div class="col-xl-4 col-md-6">
            <div class="card-modern p-4 h-100 border-0 shadow-sm bg-gradient text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                <div class="position-absolute end-0 bottom-0 opacity-25 pe-3 pb-2 fs-1">
                    <i class="fa-solid fa-coins fa-2x"></i>
                </div>
                <span class="text-uppercase small fw-bold text-white text-opacity-75 tracking-wider d-block mb-1">
                    নির্বাচিত মাসের সর্বমোট বিতরণ ({{ date('M Y', strtotime($selectedMonth . '-01')) }})
                </span>
                <h2 class="fw-black mb-2 text-white">৳{{ number_format($selectedMonthTotal, 2) }}</h2>
                <div class="d-flex align-items-center gap-3 small text-white text-opacity-80">
                    <span><i class="fa-solid fa-receipt me-1"></i>{{ number_format($selectedMonthTxCount) }} টি ট্রানজ্যাকশন</span>
                    <span>•</span>
                    <span><i class="fa-solid fa-users me-1"></i>{{ number_format($selectedMonthUsersCount) }} জন ইউজার</span>
                </div>
            </div>
        </div>

        <!-- Monthly Salary Card -->
        <div class="col-xl-2 col-md-3 col-6">
            <div class="card-modern p-3 h-100 border-0 shadow-sm border-start border-4 border-success">
                <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">মাসিক বেতন (Salary)</span>
                <h4 class="fw-bold text-success mb-1">৳{{ number_format($monthlySalaryTotal, 2) }}</h4>
                <span class="text-muted small"><i class="fa-solid fa-user-check me-1"></i>{{ number_format($monthlySalaryCount) }} জন অনুমোদিত</span>
            </div>
        </div>

        <!-- Daily Bonus Card -->
        <div class="col-xl-2 col-md-3 col-6">
            <div class="card-modern p-3 h-100 border-0 shadow-sm border-start border-4 border-warning">
                <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">ডেইলি বোনাস (Daily)</span>
                <h4 class="fw-bold text-dark mb-1">৳{{ number_format($dailyBonusTotal, 2) }}</h4>
                <span class="text-muted small"><i class="fa-solid fa-calendar-day me-1"></i>{{ number_format($dailyBonusCount) }} টি পুরস্কার</span>
            </div>
        </div>

        <!-- Weekly Bonus Card -->
        <div class="col-xl-2 col-md-3 col-6">
            <div class="card-modern p-3 h-100 border-0 shadow-sm border-start border-4 border-info">
                <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">সাপ্তাহিক বোনাস (Weekly)</span>
                <h4 class="fw-bold text-info mb-1">৳{{ number_format($weeklyBonusTotal, 2) }}</h4>
                <span class="text-muted small"><i class="fa-solid fa-calendar-week me-1"></i>{{ number_format($weeklyBonusCount) }} টি পুরস্কার</span>
            </div>
        </div>

        <!-- Referral & Other Bonuses Card -->
        <div class="col-xl-2 col-md-3 col-6">
            <div class="card-modern p-3 h-100 border-0 shadow-sm border-start border-4 border-purple" style="border-left-color: #8b5cf6 !important;">
                <span class="text-muted extra-small text-uppercase fw-bold d-block mb-1">রেফার ও অন্যান্য বোনাস</span>
                <h4 class="fw-bold mb-1" style="color: #8b5cf6;">৳{{ number_format($referBonusTotal + $dateBonusTotal + $spinBonusTotal + $otherBonusTotal + $monthlyBonusTotal, 2) }}</h4>
                <span class="text-muted small"><i class="fa-solid fa-sitemap me-1"></i>১০-লেভেল ও স্পিন বোনাস</span>
            </div>
        </div>
    </div>

    <!-- Month-over-Month Comparison Strip -->
    <div class="card-modern p-3 mb-4 border-0 shadow-sm bg-light">
        <div class="row align-items-center g-3">
            <div class="col-lg-3">
                <span class="fw-bold text-dark small d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chart-line text-primary"></i>
                    <span>মাসভিত্তিক তুলনামূলক হিসাব (Trend)</span>
                </span>
            </div>
            <div class="col-lg-3 col-4">
                <div class="bg-white p-2 rounded-3 border text-center">
                    <span class="text-muted extra-small text-uppercase d-block">চলতি মাস ({{ date('M Y') }})</span>
                    <span class="fw-bold text-primary">৳{{ number_format($currentMonthTotal, 2) }}</span>
                </div>
            </div>
            <div class="col-lg-3 col-4">
                <div class="bg-white p-2 rounded-3 border text-center">
                    <span class="text-muted extra-small text-uppercase d-block">গত মাস ({{ date('M Y', strtotime('-1 month')) }})</span>
                    <span class="fw-bold text-dark">৳{{ number_format($lastMonthTotal, 2) }}</span>
                </div>
            </div>
            <div class="col-lg-3 col-4">
                <div class="bg-white p-2 rounded-3 border text-center">
                    <span class="text-muted extra-small text-uppercase d-block">২ মাস আগে ({{ date('M Y', strtotime('-2 months')) }})</span>
                    <span class="fw-bold text-secondary">৳{{ number_format($prev2MonthTotal, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Category Summary Table -->
        <div class="col-lg-6">
            <div class="card-modern p-0 h-100 overflow-hidden border-0 shadow-sm">
                <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-layer-group text-primary"></i>
                        <span>বোনাস ও বেতন ক্যাটাগরি বিশ্লেষণ ({{ date('M Y', strtotime($selectedMonth . '-01')) }})</span>
                    </h6>
                    <span class="badge bg-light text-muted border">{{ count($categoryBreakdown) }} টি ক্যাটাগরি</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-muted small text-uppercase fw-bold">ক্যাটাগরি</th>
                                <th class="py-2 text-center text-muted small text-uppercase fw-bold">বিতরণ সংখ্যা</th>
                                <th class="py-2 text-center text-muted small text-uppercase fw-bold">ইউজার</th>
                                <th class="py-2 text-end text-muted small text-uppercase fw-bold">মোট টাকা</th>
                                <th class="px-3 py-2 text-end text-muted small text-uppercase fw-bold">শেয়ার (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categoryBreakdown as $cat)
                                @php
                                $pct = $selectedMonthTotal > 0 ? round(($cat->total_amount / $selectedMonthTotal) * 100, 1) : 0;
                                @endphp
                                <tr>
                                    <td class="px-3 py-2.5">
                                        <a href="{{ route('admin.rewards.salary-bonus-report', ['month' => $selectedMonth, 'gateway' => $cat->payment_gateway]) }}" class="fw-bold text-dark text-decoration-none hover-primary">
                                            @if($cat->payment_gateway === 'Monthly Salary')
                                                <i class="fa-solid fa-hand-holding-dollar text-success me-1"></i>
                                            @elseif($cat->payment_gateway === 'Daily Bonus')
                                                <i class="fa-solid fa-calendar-day text-warning me-1"></i>
                                            @elseif($cat->payment_gateway === 'Weekly Bonus')
                                                <i class="fa-solid fa-calendar-week text-info me-1"></i>
                                            @elseif(str_contains($cat->payment_gateway, 'Refer'))
                                                <i class="fa-solid fa-sitemap text-purple me-1"></i>
                                            @else
                                                <i class="fa-solid fa-gift text-primary me-1"></i>
                                            @endif
                                            {{ $cat->payment_gateway }}
                                        </a>
                                    </td>
                                    <td class="py-2.5 text-center text-muted">{{ number_format($cat->tx_count) }}</td>
                                    <td class="py-2.5 text-center text-muted">{{ number_format($cat->users_count) }}</td>
                                    <td class="py-2.5 text-end fw-bold text-success">৳{{ number_format($cat->total_amount, 2) }}</td>
                                    <td class="px-3 py-2.5 text-end">
                                        <div class="d-inline-flex align-items-center gap-1.5" style="width: 70px;">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, $pct) }}%"></div>
                                            </div>
                                            <span class="extra-small text-muted fw-bold">{{ $pct }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted small">এই মাসে কোনো বোনাস বা বেতন বিতরণ পাওয়া যায়নি।</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Day-by-Day Timeline Breakdown -->
        <div class="col-lg-6">
            <div class="card-modern p-0 h-100 overflow-hidden border-0 shadow-sm">
                <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-calendar-days text-success"></i>
                        <span>দৈনিক বিতরণ হিসাব টাইমলাইন ({{ date('M Y', strtotime($selectedMonth . '-01')) }})</span>
                    </h6>
                    <span class="badge bg-light text-muted border">{{ count($dailyTransactions) }} দিন ট্রানজ্যাকশন</span>
                </div>
                <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-gray-50 sticky-top">
                            <tr>
                                <th class="px-3 py-2 text-muted small text-uppercase fw-bold">তারিখ</th>
                                <th class="py-2 text-center text-muted small text-uppercase fw-bold">ডেইলি</th>
                                <th class="py-2 text-center text-muted small text-uppercase fw-bold">উইকলি</th>
                                <th class="py-2 text-center text-muted small text-uppercase fw-bold">বেতন</th>
                                <th class="py-2 text-center text-muted small text-uppercase fw-bold">অন্যান্য</th>
                                <th class="px-3 py-2 text-end text-muted small text-uppercase fw-bold">মোট বিতরণ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailyTransactions as $day)
                                <tr>
                                    <td class="px-3 py-2 fw-bold text-dark" style="font-size: 11px;">
                                        <i class="fa-regular fa-calendar-check text-muted me-1"></i>
                                        {{ date('d M (D)', strtotime($day->day_date)) }}
                                    </td>
                                    <td class="py-2 text-center text-muted" style="font-size: 11px;">
                                        {{ $day->daily_bonus > 0 ? '৳' . number_format($day->daily_bonus, 0) : '-' }}
                                    </td>
                                    <td class="py-2 text-center text-muted" style="font-size: 11px;">
                                        {{ $day->weekly_bonus > 0 ? '৳' . number_format($day->weekly_bonus, 0) : '-' }}
                                    </td>
                                    <td class="py-2 text-center text-muted" style="font-size: 11px;">
                                        {{ $day->monthly_salary > 0 ? '৳' . number_format($day->monthly_salary, 0) : '-' }}
                                    </td>
                                    <td class="py-2 text-center text-muted" style="font-size: 11px;">
                                        {{ $day->other_bonuses > 0 ? '৳' . number_format($day->other_bonuses, 0) : '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-end fw-bold text-success" style="font-size: 12px;">
                                        ৳{{ number_format($day->day_total, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted small">কোনো দৈনিক রেকর্ড পাওয়া যায়নি।</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Transaction Ledger Table -->
    <div class="card-modern p-0 overflow-hidden border-0 shadow-sm mb-4">
        <!-- Table Filter & Search Header -->
        <div class="p-4 bg-white border-bottom">
            <div class="row g-3 align-items-center justify-content-between">
                <div class="col-md-4">
                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-list-ul text-primary"></i>
                        <span>বিস্তারিত ট্রানজ্যাকশন হিস্ট্রি</span>
                    </h5>
                    <span class="text-muted extra-small">ফিল্টার ও সার্চ অনুযায়ী ট্রানজ্যাকশন রেকর্ড</span>
                </div>

                <div class="col-md-8">
                    <form action="{{ route('admin.rewards.salary-bonus-report') }}" method="GET" class="row g-2 justify-content-md-end">
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        
                        <!-- Category Filter -->
                        <div class="col-auto">
                            <select name="gateway" class="form-select form-select-sm shadow-none" onchange="this.form.submit()">
                                <option value="all" {{ $filterGateway === 'all' ? 'selected' : '' }}>সকল ক্যাটাগরি (All Bonuses)</option>
                                <option value="Monthly Salary" {{ $filterGateway === 'Monthly Salary' ? 'selected' : '' }}>মাসিক বেতন (Monthly Salary)</option>
                                <option value="Daily Bonus" {{ $filterGateway === 'Daily Bonus' ? 'selected' : '' }}>ডেইলি বোনাস (Daily Bonus)</option>
                                <option value="Weekly Bonus" {{ $filterGateway === 'Weekly Bonus' ? 'selected' : '' }}>সাপ্তাহিক বোনাস (Weekly Bonus)</option>
                                <option value="refer_group" {{ $filterGateway === 'refer_group' ? 'selected' : '' }}>সকল রেফারেল বোনাস (Referral)</option>
                                <option value="date_group" {{ $filterGateway === 'date_group' ? 'selected' : '' }}>তারিখ ভিত্তিক বোনাস (Date Bonus)</option>
                                <option value="spin_group" {{ $filterGateway === 'spin_group' ? 'selected' : '' }}>স্পিন বোনাস (Spin Bonus)</option>
                            </select>
                        </div>

                        <!-- User Search -->
                        <div class="col-auto">
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" value="{{ $search }}" placeholder="নাম, ফোন অথবা রেফার কোড..." class="form-control shadow-none">
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
                                @if(!empty($search) || $filterGateway !== 'all')
                                    <a href="{{ route('admin.rewards.salary-bonus-report', ['month' => $selectedMonth]) }}" class="btn btn-outline-secondary" title="রিসেট"><i class="fa-solid fa-xmark"></i></a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">#ID</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">ইউজার তথ্য</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">রেফার কোড</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">বোনাস ধরন</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">পরিমাণ</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">বিবরণ</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">তারিখ ও সময়</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-4 py-3 text-muted small fw-mono">#{{ $log->id }}</td>
                            <td class="py-3">
                                @if($log->user)
                                    <a href="{{ route('admin.users.show', $log->user->id) }}" class="fw-bold text-dark text-decoration-none d-block">
                                        {{ $log->user->name }}
                                    </a>
                                    <span class="extra-small text-muted"><i class="fa-solid fa-phone me-1"></i>{{ $log->user->number }}</span>
                                @else
                                    <span class="text-muted">User #{{ $log->user_id }}</span>
                                @endif
                            </td>
                            <td class="py-3 text-center">
                                <span class="badge bg-light text-dark border py-1.5 px-2.5 fw-mono" style="font-size: 11px;">
                                    {{ $log->user ? $log->user->referCode : ($log->refer_id ?: 'N/A') }}
                                </span>
                            </td>
                            <td class="py-3">
                                @if($log->payment_gateway === 'Monthly Salary')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill">
                                        <i class="fa-solid fa-hand-holding-dollar me-1"></i>Monthly Salary
                                    </span>
                                @elseif($log->payment_gateway === 'Daily Bonus')
                                    <span class="badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 px-2.5 py-1 rounded-pill">
                                        <i class="fa-solid fa-calendar-day me-1"></i>Daily Bonus
                                    </span>
                                @elseif($log->payment_gateway === 'Weekly Bonus')
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2.5 py-1 rounded-pill">
                                        <i class="fa-solid fa-calendar-week me-1"></i>Weekly Bonus
                                    </span>
                                @elseif(str_contains($log->payment_gateway, 'Refer'))
                                    <span class="badge bg-purple bg-opacity-10 text-purple border px-2.5 py-1 rounded-pill" style="color: #8b5cf6 !important; border-color: rgba(139, 92, 246, 0.25) !important;">
                                        <i class="fa-solid fa-sitemap me-1"></i>{{ $log->payment_gateway }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2.5 py-1 rounded-pill">
                                        {{ $log->payment_gateway }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 text-center fw-bold text-success fs-6">
                                ৳{{ number_format($log->amount, 2) }}
                            </td>
                            <td class="py-3 extra-small text-muted" style="max-width: 250px;">
                                {{ $log->description ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-end extra-small text-muted">
                                @if($log->date)
                                    <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($log->date)->format('h:i A') }}</div>
                                    <div class="text-muted text-opacity-75">{{ \Carbon\Carbon::parse($log->date)->format('d M, Y') }}</div>
                                @else
                                    {{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('h:i A, d M, Y') : '-' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-folder-open fs-2 mb-2 d-block text-muted opacity-50"></i>
                                নির্বাচিত ফিল্টার ও মাসে কোনো ট্রানজ্যাকশন পাওয়া যায়নি।
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-3 border-top bg-white d-flex justify-content-end">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
