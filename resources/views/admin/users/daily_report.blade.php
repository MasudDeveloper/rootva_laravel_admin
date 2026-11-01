@extends('layouts.admin')

@section('title', 'Daily Registrations & Verifications Report')
@section('page_title', '📊 দৈনিক নতুন একাউন্ট ও ভেরিফিকেশন রিপোর্ট')

@section('content')
<div class="fade-in">
    {{-- Top Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="fa-solid fa-chart-line text-primary me-2"></i>দৈনিক নতুন একাউন্ট ও ভেরিফিকেশন রিপোর্ট
            </h4>
            <p class="text-muted small mb-0">তারিখ অনুযায়ী নতুন একাউন্ট খোলার সংখ্যা এবং একাউন্ট ভেরিফিকেশনের পূর্ণাঙ্গ পরিসংখ্যান</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @php
                $prevDate = \Carbon\Carbon::parse($selectedDate)->subDay()->format('Y-m-d');
                $nextDate = \Carbon\Carbon::parse($selectedDate)->addDay()->format('Y-m-d');
            @endphp
            <a href="{{ route('admin.users.daily-report', ['date' => $prevDate, 'month' => substr($prevDate, 0, 7), 'tab' => $activeTab]) }}" 
               class="btn btn-outline-secondary rounded-pill px-3 shadow-sm btn-sm">
                <i class="fa-solid fa-chevron-left me-1"></i>আগের দিন ({{ date('d M', strtotime($prevDate)) }})
            </a>
            
            <a href="{{ route('admin.users.daily-report', ['date' => $nextDate, 'month' => substr($nextDate, 0, 7), 'tab' => $activeTab]) }}" 
               class="btn btn-outline-secondary rounded-pill px-3 shadow-sm btn-sm">
                পরের দিন ({{ date('d M', strtotime($nextDate)) }})<i class="fa-solid fa-chevron-right ms-1"></i>
            </a>

            <a href="{{ route('admin.users.index') }}" class="btn btn-primary rounded-pill px-3 shadow-sm btn-sm">
                <i class="fa-solid fa-users me-1"></i>সকল ইউজার
            </a>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card-modern p-4 mb-4 shadow-sm border-0">
        <form method="GET" action="{{ route('admin.users.daily-report') }}" id="dateFilterForm" class="row g-3 align-items-center">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">নির্দিষ্ট তারিখ নির্বাচন করুন</label>
                <div class="input-group shadow-sm rounded-pill overflow-hidden">
                    <span class="input-group-text bg-white border-end-0 ps-3">
                        <i class="fa-solid fa-calendar-day text-primary"></i>
                    </span>
                    <input type="date" name="date" class="form-control border-start-0 py-2" value="{{ $selectedDate }}" onchange="document.getElementById('dateFilterForm').submit()">
                    <button type="submit" class="btn btn-primary px-3 fw-semibold">
                        <i class="fa-solid fa-search me-1"></i>দেখুন
                    </button>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">রিপোর্ট মাস পরিবর্তন</label>
                <div class="input-group shadow-sm rounded-pill overflow-hidden">
                    <span class="input-group-text bg-white border-end-0 ps-3">
                        <i class="fa-solid fa-calendar-alt text-info"></i>
                    </span>
                    <input type="month" name="month" class="form-control border-start-0 py-2" value="{{ $selectedMonth }}" onchange="document.getElementById('dateFilterForm').submit()">
                </div>
            </div>

            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">দ্রুত তারিখ নির্বাচন</label>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($recentDays as $rDay)
                        <a href="{{ route('admin.users.daily-report', ['date' => $rDay['date'], 'month' => substr($rDay['date'], 0, 7), 'tab' => $activeTab]) }}" 
                           class="btn btn-sm rounded-pill {{ $selectedDate === $rDay['date'] ? 'btn-primary' : 'btn-outline-secondary' }} px-3 shadow-sm">
                            {{ date('d M', strtotime($rDay['date'])) }}
                            <span class="badge {{ $selectedDate === $rDay['date'] ? 'bg-white text-primary' : 'bg-primary' }} ms-1">{{ $rDay['new_users'] }}</span>
                            <span class="badge {{ $selectedDate === $rDay['date'] ? 'bg-success text-white' : 'bg-success' }} ms-0.5">✓{{ $rDay['verified_users'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </form>
    </div>

    {{-- Date Header Banner --}}
    <div class="alert bg-white border-0 shadow-sm rounded-4 p-3 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                <i class="fa-solid fa-calendar-check fa-lg"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0">
                    {{ date('d F, Y', strtotime($selectedDate)) }} <span class="text-muted fs-6 fw-normal">({{ date('l', strtotime($selectedDate)) }})</span>
                </h5>
                <span class="extra-small text-muted">নির্বাচিত তারিখের রেজিস্ট্রেশন ও ভেরিফিকেশন হিসাব</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small">
                মাস: {{ \Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }}
            </span>
        </div>
    </div>

    {{-- KPI Metric Cards for Selected Date --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card-modern p-3 border-0 shadow-sm bg-gradient-primary text-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="extra-small text-white-50 text-uppercase fw-bold">নতুন একাউন্ট (এই দিনে)</span>
                    <div class="rounded-circle bg-white bg-opacity-25 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-user-plus text-white"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-0">{{ number_format($dayNewUsersCount) }}</h2>
                <span class="extra-small text-white-50 mt-1 d-block">জন ইউজার রেজিস্ট্রেশন করেছেন</span>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card-modern p-3 border-0 shadow-sm bg-white h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="extra-small text-muted text-uppercase fw-bold">ভেরিফাইড একাউন্ট (এই দিনে)</span>
                    <div class="rounded-circle bg-success bg-opacity-10 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-shield-check text-success"></i>
                    </div>
                </div>
                <h2 class="fw-bold text-success mb-0">{{ number_format($dayVerifiedCount) }}</h2>
                <span class="extra-small text-muted mt-1 d-block">রিকোয়েস্ট অ্যাপ্রুভ: {{ number_format($dayVerReqsApproved) }} টি</span>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card-modern p-3 border-0 shadow-sm bg-white h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="extra-small text-muted text-uppercase fw-bold">ভেরিফিকেশন কনভার্সন রেট</span>
                    <div class="rounded-circle bg-info bg-opacity-10 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-percent text-info"></i>
                    </div>
                </div>
                <h2 class="fw-bold text-info mb-0">{{ $dayConversionRate }}%</h2>
                <span class="extra-small text-muted mt-1 d-block">নতুন ইউজারের ভেরিফিকেশন অনুপাত</span>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card-modern p-3 border-0 shadow-sm bg-white h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="extra-small text-muted text-uppercase fw-bold">{{ \Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }} পুরো মাসের মোট</span>
                    <div class="rounded-circle bg-warning bg-opacity-10 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-calendar-days text-warning"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-dark mb-0">{{ number_format($monthNewUsersTotal) }} / {{ number_format($monthVerifiedTotal) }}</h4>
                <span class="extra-small text-muted mt-1 d-block">মাসের মোট নতুন / ভেরিফাইড ইউজার</span>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-4 shadow-sm border" id="reportTabs">
        <li class="nav-item">
            <a class="nav-link rounded-pill {{ $activeTab === 'overview' ? 'active' : '' }} px-4 fw-semibold" 
               href="{{ route('admin.users.daily-report', ['date' => $selectedDate, 'month' => $selectedMonth, 'tab' => 'overview']) }}">
                <i class="fa-solid fa-calendar-days me-2"></i>তারিখ অনুযায়ী দিনভিত্তিক চার্ট ও টেবিল
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill {{ $activeTab === 'new_users' ? 'active' : '' }} px-4 fw-semibold" 
               href="{{ route('admin.users.daily-report', ['date' => $selectedDate, 'month' => $selectedMonth, 'tab' => 'new_users']) }}">
                <i class="fa-solid fa-user-plus me-2"></i>এই দিনে খোলা নতুন একাউন্টের তালিকা 
                <span class="badge {{ $activeTab === 'new_users' ? 'bg-white text-primary' : 'bg-primary' }} ms-2">{{ number_format($dayNewUsersCount) }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill {{ $activeTab === 'verified_users' ? 'active' : '' }} px-4 fw-semibold" 
               href="{{ route('admin.users.daily-report', ['date' => $selectedDate, 'month' => $selectedMonth, 'tab' => 'verified_users']) }}">
                <i class="fa-solid fa-shield-check me-2"></i>এই দিনে ভেরিফাই হওয়া একাউন্টের তালিকা 
                <span class="badge {{ $activeTab === 'verified_users' ? 'bg-white text-success' : 'bg-success' }} ms-2">{{ number_format($dayVerifiedCount) }}</span>
            </a>
        </li>
    </ul>

    {{-- TAB 1: OVERVIEW (Day by day ledger for the month) --}}
    @if($activeTab === 'overview')
    <div class="card-modern p-0 overflow-hidden shadow-sm border-0 mb-4">
        <div class="p-4 bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-1">
                    <i class="fa-solid fa-table-list text-primary me-2"></i>{{ \Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }} - দিনভিত্তিক বিস্তারিত রিপোর্ট
                </h5>
                <p class="text-muted extra-small mb-0">প্রতিটি তারিখে কতজন নতুন একাউন্ট খুলেছে এবং কতজন ভেরিফাই হয়েছে তা নিচে দেওয়া হলো</p>
            </div>
            <div class="text-muted small">
                মোট দিন: <span class="fw-bold text-dark">{{ count($dayBreakdown) }}</span> টি
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">তারিখ</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">নতুন একাউন্ট সংখ্যা</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">ভেরিফাইড একাউন্ট সংখ্যা</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">ভেরিফিকেশন রেট (%)</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dayBreakdown as $row)
                    <tr class="{{ $row['is_selected'] ? 'table-primary border-start border-4 border-primary' : '' }}">
                        <td class="px-4 py-3">
                            <span class="fw-bold text-dark">
                                <i class="fa-regular fa-calendar-check me-2 text-primary"></i>{{ $row['carbon']->format('d F, Y (l)') }}
                            </span>
                            @if($row['is_selected'])
                                <span class="badge bg-primary ms-2">সিলেক্টেড</span>
                            @endif
                        </td>
                        <td class="py-3 text-center">
                            @if($row['new_users'] > 0)
                                <span class="badge bg-primary px-3 py-2 rounded-pill fw-bold fs-6">{{ $row['new_users'] }} জন</span>
                            @else
                                <span class="text-muted small">০ জন</span>
                            @endif
                        </td>
                        <td class="py-3 text-center">
                            @if($row['verified_users'] > 0)
                                <span class="badge bg-success px-3 py-2 rounded-pill fw-bold fs-6">✓ {{ $row['verified_users'] }} জন</span>
                            @else
                                <span class="text-muted small">০ জন</span>
                            @endif
                        </td>
                        <td class="py-3 text-center">
                            @if($row['conversion_rate'] > 0)
                                <span class="badge bg-info-soft text-info px-3 py-2 rounded-pill fw-bold">{{ $row['conversion_rate'] }}%</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="d-inline-flex gap-2">
                                <a href="{{ route('admin.users.daily-report', ['date' => $row['date'], 'month' => $selectedMonth, 'tab' => 'new_users']) }}" 
                                   class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm" title="নতুন ইউজার তালিকা দেখুন">
                                    <i class="fa-solid fa-user-plus me-1"></i>নতুন ইউজার
                                </a>
                                <a href="{{ route('admin.users.daily-report', ['date' => $row['date'], 'month' => $selectedMonth, 'tab' => 'verified_users']) }}" 
                                   class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-sm" title="ভেরিফাইড ইউজার তালিকা দেখুন">
                                    <i class="fa-solid fa-shield-check me-1"></i>ভেরিফাইড ইউজার
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- TAB 2: NEW USERS ON SELECTED DATE --}}
    @if($activeTab === 'new_users')
    <div class="card-modern p-0 overflow-hidden shadow-sm border-0 mb-4">
        <div class="p-4 bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-1">
                    <i class="fa-solid fa-user-plus text-primary me-2"></i>{{ date('d F, Y', strtotime($selectedDate)) }} এ রেজিস্ট্রেশনকৃত নতুন ইউজার
                </h5>
                <p class="text-muted extra-small mb-0">মোট রেজিস্ট্রেশন: {{ $newUsersList->total() }} জন</p>
            </div>
            <a href="{{ route('admin.users.daily-report', ['date' => $selectedDate, 'month' => $selectedMonth, 'tab' => 'overview']) }}" class="btn btn-light btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i>দিনভিত্তিক চার্টে ফিরে যান
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">User</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Mobile</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Refer Code</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Referred By</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Registration Time</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Verified?</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($newUsersList as $u)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="fw-bold text-dark d-block">{{ $u->name ?? 'N/A' }}</span>
                            <span class="text-muted extra-small">ID: #{{ $u->id }}</span>
                        </td>
                        <td class="py-3 text-muted">{{ $u->number ?? 'N/A' }}</td>
                        <td class="py-3"><span class="badge bg-light text-dark border py-2 px-3 fw-semibold">{{ $u->referCode ?? 'N/A' }}</span></td>
                        <td class="py-3"><span class="text-muted small">{{ $u->referredBy ?? 'Direct' }}</span></td>
                        <td class="py-3 text-center text-muted small">{{ $u->created_at }}</td>
                        <td class="py-3 text-center">
                            @if($u->is_verified)
                                <span class="badge bg-success-soft text-success px-3 py-1 rounded-pill fw-semibold">
                                    <i class="fa-solid fa-check me-1"></i>Verified
                                </span>
                            @else
                                <span class="badge bg-secondary px-3 py-1 rounded-pill text-white fw-normal">Unverified</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <a href="{{ route('admin.users.show', $u->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm">
                                <i class="fa-solid fa-eye me-1"></i>প্রোফাইল
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-users-slash fa-3x mb-2 text-muted opacity-50 d-block"></i>
                            এই তারিখে ({{ date('d M, Y', strtotime($selectedDate)) }}) কোনো নতুন রেজিস্ট্রেশন পাওয়া যায়নি।
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($newUsersList->hasPages())
        <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
            {{ $newUsersList->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- TAB 3: VERIFIED USERS ON SELECTED DATE --}}
    @if($activeTab === 'verified_users')
    <div class="card-modern p-0 overflow-hidden shadow-sm border-0 mb-4">
        <div class="p-4 bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-1">
                    <i class="fa-solid fa-shield-check text-success me-2"></i>{{ date('d F, Y', strtotime($selectedDate)) }} এ ভেরিফাইড একাউন্টসমূহ
                </h5>
                <p class="text-muted extra-small mb-0">মোট ভেরিফাইড ইউজার: {{ $verifiedUsersList->total() }} জন</p>
            </div>
            <a href="{{ route('admin.users.daily-report', ['date' => $selectedDate, 'month' => $selectedMonth, 'tab' => 'overview']) }}" class="btn btn-light btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i>দিনভিত্তিক চার্টে ফিরে যান
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">User</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Mobile</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Refer Code</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Referred By</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Verified Date & Time</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Wallet Balance</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($verifiedUsersList as $vu)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="fw-bold text-dark d-block">{{ $vu->name ?? 'N/A' }}</span>
                            <span class="text-muted extra-small">ID: #{{ $vu->id }}</span>
                        </td>
                        <td class="py-3 text-muted">{{ $vu->number ?? 'N/A' }}</td>
                        <td class="py-3"><span class="badge bg-light text-dark border py-2 px-3 fw-semibold">{{ $vu->referCode ?? 'N/A' }}</span></td>
                        <td class="py-3"><span class="text-muted small">{{ $vu->referredBy ?? 'Direct' }}</span></td>
                        <td class="py-3 text-center text-muted small">
                            <div class="fw-semibold text-dark">{{ date('d M, Y', strtotime($vu->verified_raw_time ?? $vu->verified_at)) }}</div>
                            <div class="extra-small text-muted">{{ date('h:i:s A', strtotime($vu->verified_raw_time ?? $vu->verified_at)) }}</div>
                        </td>
                        <td class="py-3 text-center fw-bold text-success fs-6">৳{{ number_format($vu->wallet_balance ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-end">
                            <a href="{{ route('admin.users.show', $vu->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm">
                                <i class="fa-solid fa-eye me-1"></i>প্রোফাইল
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-user-xmark fa-3x mb-2 text-muted opacity-50 d-block"></i>
                            এই তারিখে ({{ date('d M, Y', strtotime($selectedDate)) }}) কোনো একাউন্ট ভেরিফাই হয়নি।
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($verifiedUsersList->hasPages())
        <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
            {{ $verifiedUsersList->links() }}
        </div>
        @endif
    </div>
    @endif
</div>

<style>
    .bg-gradient-primary { background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.12); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.12); }
    .extra-small { font-size: 0.75rem; }
    .card-modern { border-radius: 1.25rem; }
</style>
@endsection
