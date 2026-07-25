@extends('layouts.admin')

@section('title', 'Date-wise Bulk Bonus Distribution')
@section('page_title', '📅 Date-wise Bulk Bonus')

@section('content')
@php
    $defaultDate = old('target_date', now()->format('Y-m-d'));
    $todayDate = now()->format('Y-m-d');
    $yesterdayDate = now()->subDay()->format('Y-m-d');
@endphp
<div class="fade-in max-w-800 mx-auto">
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
    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-4 px-4 mb-4">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li><i class="fa-solid fa-exclamation-circle me-2"></i>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card-modern mb-4 shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="bg-gradient-primary text-white p-4 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-gift me-2"></i> তারিখ ভিত্তিক বোনাস বিতরণ (Date-wise Bonus)</h4>
                <p class="mb-0 text-white-50 small">নির্দিষ্ট তারিখের রেজিস্ট্রেশনকৃত/ভেরিফাইড ইউজার বা তাদের রেফারারদের এক ক্লিকে বোনাস প্রেরণ করুন।</p>
            </div>
            <div class="d-none d-md-block">
                <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-bold shadow-sm"><i class="fa-solid fa-bolt me-1"></i> Bulk Action</span>
            </div>
        </div>

        <div class="p-4">
            <form action="{{ route('admin.rewards.date-bonus.distribute') }}" method="POST">
                @csrf

                <!-- ১. ডেট নির্বাচন -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark small text-uppercase"><i class="fa-solid fa-calendar-day text-primary me-2"></i> তারিখ নির্বাচন করুন (Select Date)</label>
                    <div class="row g-2 align-items-center">
                        <div class="col-md-6">
                            <input type="date" name="target_date" id="target_date" class="form-control rounded-pill p-3 border-2 @error('target_date') is-invalid @enderror"
                                value="{{ $defaultDate }}" required>
                        </div>
                        <div class="col-md-6 d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary rounded-pill px-4 py-2 flex-grow-1 fw-bold small" onclick="setQuickDate('{{ $todayDate }}')">
                                <i class="fa-solid fa-clock me-1"></i> আজকে (Today)
                            </button>
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 flex-grow-1 fw-bold small" onclick="setQuickDate('{{ $yesterdayDate }}')">
                                <i class="fa-solid fa-history me-1"></i> গতকাল (Yesterday)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ২. প্রাপক নির্বাচন (Who gets the bonus) -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark small text-uppercase"><i class="fa-solid fa-users text-primary me-2"></i> কাদের বোনাস দিতে চান? (Select Recipient Type)</label>
                    <div class="row g-3">
                        <!-- অপশন ১: রেফারারদের বোনাস -->
                        <div class="col-md-4">
                            <label class="card-modern-select p-3 rounded-4 border border-2 d-block h-100 cursor-pointer position-relative text-start">
                                <input class="form-check-input float-end" type="radio" name="bonus_type" value="referrer_bonus" {{ old('bonus_type', 'referrer_bonus') === 'referrer_bonus' ? 'checked' : '' }} required>
                                <div class="fw-bold text-primary mb-1"><i class="fa-solid fa-share-nodes me-1"></i> রেফারার বোনাস</div>
                                <div class="text-muted small">ওই তারিখে ভেরিফাই হওয়া ইউজারদের <b>রেফারারদের</b> (যিনি ইনভাইট করেছেন) বোনাস দেওয়া হবে।</div>
                            </label>
                        </div>

                        <!-- অপশন ২: নিজেরা ভেরিফাইড হওয়া ইউজার -->
                        <div class="col-md-4">
                            <label class="card-modern-select p-3 rounded-4 border border-2 d-block h-100 cursor-pointer position-relative text-start">
                                <input class="form-check-input float-end" type="radio" name="bonus_type" value="verified_user_bonus" {{ old('bonus_type') === 'verified_user_bonus' ? 'checked' : '' }}>
                                <div class="fw-bold text-success mb-1"><i class="fa-solid fa-user-check me-1"></i> ভেরিফাইড ইউজার বোনাস</div>
                                <div class="text-muted small">ওই তারিখে যে সকল ইউজার নিজেরা রেজিস্ট্রেশন/অ্যাকাউন্ট ভেরিফাই করেছে সরাসরি তাদের দেওয়া হবে।</div>
                            </label>
                        </div>

                        <!-- অপশন ৩: সকল ভেরিফাইড ইউজার -->
                        <div class="col-md-4">
                            <label class="card-modern-select p-3 rounded-4 border border-2 d-block h-100 cursor-pointer position-relative text-start">
                                <input class="form-check-input float-end" type="radio" name="bonus_type" value="all_verified_bonus" {{ old('bonus_type') === 'all_verified_bonus' ? 'checked' : '' }}>
                                <div class="fw-bold text-info mb-1"><i class="fa-solid fa-globe me-1"></i> সকল ভেরিফাইড ইউজার</div>
                                <div class="text-muted small">তারিখ নির্বিশেষে সিস্টেমের <b>সকল অ্যাক্টিভ ভেরিফাইড</b> ইউজারদের ওয়ালেটে বোনাস যোগ হবে।</div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ৩. বোনাসের পরিমাণ ও টাইটেল -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark small text-uppercase"><i class="fa-solid fa-coins text-warning me-2"></i> টাকার অ্যামাউন্ট (৳ BDT)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-pill"><b class="text-success fs-5">৳</b></span>
                            <input type="number" step="0.01" min="1" name="amount" class="form-control border-start-0 rounded-end-pill p-3 fs-5 fw-bold text-success @error('amount') is-invalid @enderror"
                                placeholder="50.00" value="{{ old('amount', '50') }}" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark small text-uppercase"><i class="fa-solid fa-tag text-info me-2"></i> পেমেন্ট গেটওয়ে / টাইটেল</label>
                        <input type="text" name="payment_gateway" class="form-control rounded-pill p-3 @error('payment_gateway') is-invalid @enderror"
                            placeholder="e.g. Registration Bonus" value="{{ old('payment_gateway', 'Registration Bonus') }}" required>
                    </div>
                </div>

                <!-- ৪. ডেসক্রিপশন -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark small text-uppercase"><i class="fa-solid fa-align-left text-secondary me-2"></i> বাংলায় ডেসক্রিপশন (Notification/Transaction Text)</label>
                    <input type="text" name="description" class="form-control rounded-pill p-3 @error('description') is-invalid @enderror"
                        placeholder="যেমন: আপনার রেজিস্ট্রেশন বোনাস ৫০ টাকা জমা হয়েছে" value="{{ old('description', 'আপনার রেজিস্ট্রেশন বোনাস ৫০ টাকা জমা হয়েছে') }}" required>
                    <div class="form-text text-muted small mt-1 ms-3">এই লেখাটি ইউজারের ট্রানজেকশন হিস্ট্রিতে ডেসক্রিপশন হিসেবে প্রদর্শিত হবে।</div>
                </div>

                <hr class="my-4 text-muted">

                <!-- ৫. সাবমিট বাটন -->
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i> ডুপ্লিকেট বোনাস প্রোটেকশন সক্রিয় রয়েছে।
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow" onclick="return confirm('আপনি কি নিশ্চিত যে নির্বাচিত তারিখ ও অপশন অনুযায়ী সকল ইউজারের ওয়ালেটে বোনাস পাঠাতে চান?')">
                        <i class="fa-solid fa-paper-plane me-2"></i> বোনাস বিতরণ করুন (Send Bonus)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- সাম্প্রতিক বোনাস হিস্ট্রি -->
    <div class="card-modern shadow-sm border-0 rounded-4">
        <div class="p-4 border-bottom bg-light d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> সাম্প্রতিক বোনাস বিতরণ হিস্ট্রি (Recent Distributed Bonuses)</h5>
            <span class="badge bg-secondary rounded-pill">{{ $recentBonuses->total() }} Records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>User (প্রাপক)</th>
                        <th>Refer ID</th>
                        <th>Gateway (টাইটেল)</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBonuses as $bonus)
                    <tr>
                        <td class="ps-4 fw-bold text-muted">#{{ $bonus->id }}</td>
                        <td>
                            @if($bonus->user)
                            <div class="fw-bold text-dark">{{ $bonus->user->name ?? 'User' }}</div>
                            <div class="small text-muted">{{ $bonus->user->number ?? '' }} (Code: <b>{{ $bonus->user->referCode }}</b>)</div>
                            @else
                            <span class="text-danger">User Deleted (ID: {{ $bonus->user_id }})</span>
                            @endif
                        </td>
                        <td>
                            @if($bonus->refer_id)
                            <span class="badge bg-light text-dark border font-monospace">{{ $bonus->refer_id }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td><span class="badge bg-info bg-opacity-10 text-info fw-bold px-3 py-1 rounded-pill">{{ $bonus->payment_gateway }}</span></td>
                        <td><span class="fw-bold text-success fs-6">+৳{{ number_format($bonus->amount, 2) }}</span></td>
                        <td class="small text-muted max-w-200 text-truncate" title="{{ $bonus->description }}">{{ $bonus->description }}</td>
                        <td class="small text-muted">{{ $bonus->created_at ?: $bonus->date }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fs-2 mb-2 d-block text-secondary"></i>
                            কোনো বোনাস রেকর্ড পাওয়া যায়নি।
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($recentBonuses->hasPages())
        <div class="p-4 border-top">
            {{ $recentBonuses->links() }}
        </div>
        @endif
    </div>
</div>

<script>
    function setQuickDate(dateStr) {
        document.getElementById('target_date').value = dateStr;
    }
</script>

<style>
    .cursor-pointer {
        cursor: pointer;
    }

    .card-modern-select {
        transition: all 0.2s ease;
        background: #fff;
    }

    .card-modern-select:hover {
        border-color: #4f46e5 !important;
        background: #f8fafc;
    }

    input[type="radio"]:checked+div,
    input[type="radio"]:checked~div {
        color: #4f46e5 !important;
    }

    .card-modern-select:has(input[type="radio"]:checked) {
        border-color: #4f46e5 !important;
        background: #eef2ff !important;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
    }
</style>
@endsection