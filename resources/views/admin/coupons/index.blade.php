@extends('layouts.admin')

@section('title', 'Coupon Bonus Management')
@section('page_title', 'Coupon Bonus System')

@section('content')
<div class="fade-in">
    {{-- Statistics Dashboard --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card-modern border-0 shadow-sm p-4 text-center">
                <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                    <i class="fa-solid fa-ticket-simple fa-xl"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['total_coupons'] }}</h3>
                <p class="text-muted small mb-0">Total Coupons</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern border-0 shadow-sm p-4 text-center">
                <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background: rgba(25, 135, 84, 0.1); color: #198754;">
                    <i class="fa-solid fa-circle-check fa-xl"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['active_coupons'] }}</h3>
                <p class="text-muted small mb-0">Active Coupons</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern border-0 shadow-sm p-4 text-center">
                <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background: rgba(255, 193, 7, 0.15); color: #b78103;">
                    <i class="fa-solid fa-users fa-xl"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ number_format($stats['total_claims']) }}</h3>
                <p class="text-muted small mb-0">Total Claims</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern border-0 shadow-sm p-4 text-center">
                <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                    <i class="fa-solid fa-bangladeshi-taka-sign fa-xl"></i>
                </div>
                <h3 class="fw-bold mb-1">৳{{ number_format($stats['total_distributed'], 2) }}</h3>
                <p class="text-muted small mb-0">Total Distributed</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 px-4 py-3 mb-4 d-flex align-items-center">
            <i class="fa-solid fa-circle-check me-2 fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 px-4 py-3 mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header & Action --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-1">মিটিং কুপন কোড লিস্ট</h5>
            <p class="text-muted small mb-0">মিটিং চলাকালীন লাইভে শেয়ার করার জন্য কুপন কোড তৈরি করুন। যে আগে ক্লেইম করবে সে বোনাস পাবে।</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addCouponModal">
            <i class="fa-solid fa-plus me-2"></i>নতুন কুপন তৈরি করুন
        </button>
    </div>

    {{-- Coupons Table --}}
    <div class="card-modern p-0 overflow-hidden border-0 shadow-sm bg-white rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">কুপন কোড ও শিরোনাম</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">বোনাস পরিমাণ</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">ক্লেইম লিমিট</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">ক্লেইমার (কে পেয়েছে)</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">মেয়াদ</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">স্ট্যাটাস</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary-soft text-primary font-monospace fs-6 px-3 py-2 border rounded-3 fw-bold">
                                    {{ $coupon->code }}
                                </span>
                            </div>
                            <div class="small text-muted mt-1">{{ $coupon->title ?: 'মিটিং স্পেশাল বোনাস' }}</div>
                        </td>
                        <td class="py-3 text-center">
                            <span class="fw-bold text-success fs-6">৳{{ number_format($coupon->amount, 2) }}</span>
                        </td>
                        <td class="py-3 text-center">
                            @if($coupon->used_count >= $coupon->max_uses)
                                <span class="badge bg-danger rounded-pill px-3 py-2">
                                    {{ $coupon->used_count }} / {{ $coupon->max_uses }} (শেষ)
                                </span>
                            @else
                                <span class="badge bg-success rounded-pill px-3 py-2">
                                    {{ $coupon->used_count }} / {{ $coupon->max_uses }} (চলছে)
                                </span>
                            @endif
                        </td>
                        <td class="py-3">
                            @if($coupon->claims->count() > 0)
                                <div class="small">
                                    @foreach($coupon->claims->take(2) as $claim)
                                        <div class="text-dark fw-semibold">
                                            <i class="fa-solid fa-user-check text-success me-1"></i>
                                            {{ $claim->user->name ?? 'User #' . $claim->user_id }} 
                                            <span class="text-muted fw-normal">({{ $claim->user->number ?? '' }})</span>
                                        </div>
                                    @endforeach
                                    @if($coupon->claims->count() > 2)
                                        <span class="text-muted small">+আরও {{ $coupon->claims->count() - 2 }} জন</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted small">এখনও কেউ ক্লেইম করেনি</span>
                            @endif
                        </td>
                        <td class="py-3 text-center small text-muted">
                            {{ $coupon->expires_at ? $coupon->expires_at->format('d M, Y h:i A') : 'আজীবন' }}
                        </td>
                        <td class="py-3 text-center">
                            <form action="{{ route('admin.coupons.toggle', $coupon->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="border-0 bg-transparent p-0" title="স্ট্যাটাস পরিবর্তন করুন">
                                    @if($coupon->status)
                                        <span class="badge bg-success rounded-pill px-3 py-2 cursor-pointer">সচল</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill px-3 py-2 cursor-pointer">নিষ্ক্রিয়</span>
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary rounded-start-pill px-3" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editCouponModal{{ $coupon->id }}"
                                        title="এডিট">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('admin.coupons.destroy', $coupon->id) }}" method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই কুপনটি মুছে ফেলতে চান?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-end-pill px-3" title="মুছে ফেলুন">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- Edit Modal --}}
                    <div class="modal fade" id="editCouponModal{{ $coupon->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg rounded-4">
                                <form action="{{ route('admin.coupons.update', $coupon->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-header border-0 px-4 pt-4">
                                        <h5 class="modal-title fw-bold">কুপন এডিট করুন</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body px-4 py-3">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">কুপন কোড</label>
                                            <input type="text" name="code" class="form-control rounded-3 font-monospace text-uppercase" value="{{ $coupon->code }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">কুপন শিরোনাম (Title)</label>
                                            <input type="text" name="title" class="form-control rounded-3" value="{{ $coupon->title }}">
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small text-muted">টাকার পরিমাণ (৳)</label>
                                                <input type="number" step="0.5" name="amount" class="form-control rounded-3" value="{{ $coupon->amount }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small text-muted">সর্বোচ্চ ক্লেইম সংখ্যা</label>
                                                <input type="number" name="max_uses" class="form-control rounded-3" value="{{ $coupon->max_uses }}" min="1" required>
                                                <div class="form-text small">১ দিলে শুধুমাত্র প্রথমজন পাবে।</div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">মেয়াদ উত্তীর্ণের তারিখ (ঐচ্ছিক)</label>
                                            <input type="datetime-local" name="expires_at" class="form-control rounded-3" value="{{ $coupon->expires_at ? $coupon->expires_at->format('Y-m-d\TH:i') : '' }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">স্ট্যাটাস</label>
                                            <select name="status" class="form-select rounded-3">
                                                <option value="1" {{ $coupon->status ? 'selected' : '' }}>সচল (Active)</option>
                                                <option value="0" {{ !$coupon->status ? 'selected' : '' }}>নিষ্ক্রিয় (Inactive)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 px-4 pb-4">
                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">বাতিল</button>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4">আপডেট করুন</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="py-4">
                                <i class="fa-solid fa-ticket-simple fa-3x text-muted mb-3 opacity-50"></i>
                                <h6 class="text-muted fw-bold">কোনো কুপন কোড পাওয়া যায়নি</h6>
                                <p class="text-muted small">মিটিংয়ের জন্য প্রথম কুপন কোডটি তৈরি করুন।</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($coupons->hasPages())
        <div class="p-3 border-top">
            {{ $coupons->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Add Coupon Modal --}}
<div class="modal fade" id="addCouponModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('admin.coupons.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">নতুন কুপন কোড তৈরি করুন</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">কুপন কোড</label>
                        <div class="input-group">
                            <input type="text" id="couponCodeInput" name="code" class="form-control rounded-start-3 font-monospace text-uppercase" placeholder="যেমন: MEET20" required>
                            <button class="btn btn-outline-secondary rounded-end-3" type="button" onclick="generateRandomCode()">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i>র‍্যান্ডম কোড
                            </button>
                        </div>
                        <div class="form-text small">ইউজাররা মিটিং দেখে এই কোডটি লিখবে।</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">শিরোনাম (Title)</label>
                        <input type="text" name="title" class="form-control rounded-3" placeholder="যেমন: রবিবার মিটিং স্পেশাল বোনাস" value="মিটিং স্পেশাল কুপন">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small text-muted">টাকার পরিমাণ (৳)</label>
                            <input type="number" step="0.5" name="amount" class="form-control rounded-3" placeholder="যেমন: 20" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small text-muted">কতজন পাবে (লিমিট)</label>
                            <input type="number" name="max_uses" class="form-control rounded-3" value="1" min="1" required>
                            <div class="form-text small text-primary fw-semibold">১ দিলে যে প্রথম ক্লেইম করবে শুধুমাত্র সেই পাবে।</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">মেয়াদ উত্তীর্ণের তারিখ ও সময় (ঐচ্ছিক)</label>
                        <input type="datetime-local" name="expires_at" class="form-control rounded-3">
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">তৈরি করুন</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function generateRandomCode() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let code = 'MEET';
    for (let i = 0; i < 5; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('couponCodeInput').value = code;
}
</script>
@endsection
