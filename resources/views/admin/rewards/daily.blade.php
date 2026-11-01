@extends('layouts.admin')

@section('title', 'Daily Bonus Monthly Report')
@section('page_title', '🎉 Daily Bonus - মাসিক রিপোর্ট ও বিজয়ী তালিকা')

@section('content')
<div class="fade-in">
    {{-- Header Actions --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="fa-solid fa-calendar-day text-primary me-2"></i>ডেইলি বোনাস মাসিক রিপোর্ট
            </h4>
            <p class="text-muted small mb-0">মাস ভিত্তিক মোট বিতরণ, দৈনিক ব্রেকডাউন এবং বিজয়ীদের বিস্তারিত তালিকা</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('admin.rewards.salary-bonus-report') }}" class="btn btn-outline-primary rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-chart-pie me-1"></i>সব বোনাস অডিট রিপোর্ট
            </a>
            <form action="{{ route('admin.rewards.daily.run') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary rounded-pill px-3 shadow-sm" onclick="return confirm('Distribute tiered daily bonus to all qualified referrers for yesterday?')">
                    <i class="fa-solid fa-bolt-lightning me-2"></i>Distribute Daily Bonus
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

    {{-- Month Selector & Filter Card --}}
    <div class="card-modern p-4 mb-4 shadow-sm border-0">
        <form method="GET" action="{{ route('admin.rewards.daily') }}" id="filterForm" class="row g-3 align-items-center">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">রিপোর্ট মাস নির্বাচন করুন</label>
                <div class="input-group shadow-sm rounded-pill overflow-hidden">
                    <span class="input-group-text bg-white border-end-0 ps-3">
                        <i class="fa-solid fa-calendar-alt text-primary"></i>
                    </span>
                    <input type="month" name="month" class="form-control border-start-0 py-2" value="{{ $selectedMonth }}" onchange="document.getElementById('filterForm').submit()">
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="fa-solid fa-filter me-1"></i>দেখুন
                    </button>
                </div>
            </div>
            
            <div class="col-md-8">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">দ্রুত মাস ফিল্টার</label>
                <div class="d-flex flex-wrap gap-2">
                    @forelse($availableMonths->take(6) as $mRow)
                        <a href="{{ route('admin.rewards.daily', ['month' => $mRow->m]) }}" 
                           class="btn btn-sm rounded-pill {{ $selectedMonth === $mRow->m ? 'btn-primary' : 'btn-outline-secondary' }} px-3 shadow-sm">
                            <i class="fa-solid fa-calendar me-1"></i>{{ \Carbon\Carbon::parse($mRow->m . '-01')->format('M Y') }}
                            <span class="badge {{ $selectedMonth === $mRow->m ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">৳{{ number_format($mRow->total, 0) }}</span>
                        </a>
                    @empty
                        <span class="text-muted small">কোনো পূর্ববর্তী রেকর্ড নেই</span>
                    @endforelse
                </div>
            </div>
        </form>
    </div>

    {{-- Monthly KPI Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card-modern p-3 border-0 shadow-sm bg-gradient-primary text-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="extra-small text-white-50 text-uppercase fw-bold">{{ \Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }} মোট বোনাস</span>
                    <div class="rounded-circle bg-white bg-opacity-25 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-coins text-white"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0">৳{{ number_format($stats->total_amount ?? 0, 2) }}</h3>
                <span class="extra-small text-white-50 mt-1 d-block">মোট প্রদানকৃত ডেইলি বোনাস</span>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card-modern p-3 border-0 shadow-sm bg-white h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="extra-small text-muted text-uppercase fw-bold">মোট বিজয়ী ট্রানজেকশন</span>
                    <div class="rounded-circle bg-success bg-opacity-10 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-trophy text-success"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-0">{{ number_format($stats->total_count ?? 0) }}</h3>
                <span class="extra-small text-muted mt-1 d-block">মোট বোনাস প্রাপ্তির সংখ্যা</span>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card-modern p-3 border-0 shadow-sm bg-white h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="extra-small text-muted text-uppercase fw-bold">ইউনিক বিজয়ী ইউজার</span>
                    <div class="rounded-circle bg-info bg-opacity-10 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-users text-info"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-0">{{ number_format($stats->unique_users ?? 0) }}</h3>
                <span class="extra-small text-muted mt-1 d-block">আলাদা আলাদা ব্যবহারকারী</span>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card-modern p-3 border-0 shadow-sm bg-white h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="extra-small text-muted text-uppercase fw-bold">গড় / সর্বোচ্চ পুরস্কার</span>
                    <div class="rounded-circle bg-warning bg-opacity-10 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fa-solid fa-award text-warning"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-dark mb-0">৳{{ number_format($stats->avg_amount ?? 0, 1) }} / ৳{{ number_format($stats->max_amount ?? 0, 0) }}</h4>
                <span class="extra-small text-muted mt-1 d-block">গড় বোনাস / সর্বোচ্চ একক বোনাস</span>
            </div>
        </div>
    </div>

    {{-- Daily Breakdown within Month (তারিখ অনুযায়ী দিনভিত্তিক ব্রেকডাউন) --}}
    <div class="card-modern p-0 overflow-hidden mb-4 shadow-sm border-0">
        <div class="p-4 bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-1">
                    <i class="fa-solid fa-chart-column text-primary me-2"></i>{{ \Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }} - তারিখ অনুযায়ী দিনভিত্তিক ব্রেকডাউন
                </h5>
                <p class="text-muted extra-small mb-0">যেকোনো নির্দিষ্ট দিনের বিস্তারিত বিজয়ী তালিকা দেখতে উক্ত দিনের বাটনে ক্লিক করুন</p>
            </div>
            @if(!empty($selectedDate))
                <a href="{{ route('admin.rewards.daily', ['month' => $selectedMonth]) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-xmark me-1"></i>নির্দিষ্ট তারিখ ফিল্টার সরান ({{ date('d M, Y', strtotime($selectedDate)) }})
                </a>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">তারিখ</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">বিজয়ী সংখ্যা</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">ইউনিক ইউজার</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">মোট বোনাস</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dailyBreakdown as $bRow)
                    <tr class="{{ $selectedDate === $bRow->day ? 'table-primary' : '' }}">
                        <td class="px-4 py-3">
                            <span class="fw-bold text-dark">
                                <i class="fa-regular fa-calendar-check me-2 text-primary"></i>{{ date('d F, Y (l)', strtotime($bRow->day)) }}
                            </span>
                            @if($selectedDate === $bRow->day)
                                <span class="badge bg-primary ms-2">সিলেক্টেড</span>
                            @endif
                        </td>
                        <td class="py-3 text-center">
                            <span class="badge bg-info-soft text-info px-3 py-2 rounded-pill fw-bold">{{ $bRow->count }} জন</span>
                        </td>
                        <td class="py-3 text-center text-muted fw-semibold">{{ $bRow->users_count }} জন</td>
                        <td class="py-3 text-center fw-bold text-success fs-6">৳{{ number_format($bRow->total, 2) }}</td>
                        <td class="px-4 py-3 text-end">
                            @if($selectedDate === $bRow->day)
                                <a href="{{ route('admin.rewards.daily', ['month' => $selectedMonth]) }}" class="btn btn-secondary btn-sm rounded-pill px-3 shadow-sm">
                                    <i class="fa-solid fa-arrows-rotate me-1"></i>সব দেখুন
                                </a>
                            @else
                                <a href="{{ route('admin.rewards.daily', ['month' => $selectedMonth, 'date' => $bRow->day]) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm">
                                    <i class="fa-solid fa-list-check me-1"></i>তালিকা দেখুন
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-calendar-xmark fa-3x mb-2 text-muted opacity-50 d-block"></i>
                            এই মাসে ({{ \Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }}) কোনো ডেইলি বোনাস প্রদান করা হয়নি।
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Detailed Winners Ledger Table --}}
    <div class="card-modern p-0 overflow-hidden shadow-sm border-0">
        <div class="p-4 bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="fw-bold mb-1">
                    <i class="fa-solid fa-receipt text-success me-2"></i>বিজয়ীদের বিস্তারিত তালিকা
                    @if(!empty($selectedDate))
                        <span class="badge bg-primary ms-2">{{ date('d M, Y', strtotime($selectedDate)) }} এর তালিকা</span>
                    @else
                        <span class="badge bg-light text-dark border ms-2">{{ \Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }} এর তালিকা</span>
                    @endif
                </h5>
                <p class="text-muted extra-small mb-0">মোট প্রদর্শিত রেকর্ড: {{ $winners->total() }} টি</p>
            </div>
            @if(!empty($selectedDate))
                <a href="{{ route('admin.rewards.daily', ['month' => $selectedMonth]) }}" class="btn btn-light btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-chevron-left me-1"></i>পুরো মাসের সব বিজয়ী দেখুন
                </a>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Winner Name</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Mobile</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Refer Code</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Reward Amount</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Date & Time</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($winners as $row)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="fw-bold text-dark d-block">{{ $row->user->name ?? 'N/A' }}</span>
                            <span class="text-muted extra-small">ID: #{{ $row->user_id }}</span>
                        </td>
                        <td class="py-3 text-muted">{{ $row->user->number ?? 'N/A' }}</td>
                        <td class="py-3">
                            <span class="badge bg-light text-dark border py-2 px-3 fw-semibold">{{ $row->refer_id ?? ($row->user->referCode ?? 'N/A') }}</span>
                        </td>
                        <td class="py-3 text-center fw-bold text-success fs-6">৳{{ number_format($row->amount, 2) }}</td>
                        <td class="py-3 text-center text-muted small">
                            <div class="fw-semibold text-dark">{{ date('d M, Y', strtotime($row->date ?? $row->created_at)) }}</div>
                            <div class="extra-small text-muted">{{ date('h:i A', strtotime($row->date ?? $row->created_at)) }}</div>
                        </td>
                        <td class="px-4 py-3 text-end extra-small text-muted">{{ $row->description }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">কোনো বোনাস ট্রানজেকশন পাওয়া যায়নি।</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($winners->hasPages())
        <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
            {{ $winners->links() }}
        </div>
        @endif
    </div>
</div>

<style>
    .bg-gradient-primary { background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.12); }
    .extra-small { font-size: 0.75rem; }
    .card-modern { border-radius: 1.25rem; }
</style>
@endsection
