@extends('layouts.admin')

@section('title', 'Upline Change Logs')
@section('page_title', '🔄 Upline Change Logs (আপলাইন পরিবর্তন লগ)')

@section('content')
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

    <!-- ১. স্ট্যাটিস্টিকস কার্ড (Quick Stats) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card-modern p-3 border-0 shadow-sm rounded-4 bg-white d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 fs-3">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold">সর্বমোট পরিবর্তন</div>
                    <h4 class="fw-bold mb-0 text-dark">{{ number_format($stats['total_changes'] ?? 0) }} <span class="fs-6 fw-normal text-muted">বার</span></h4>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="card-modern p-3 border-0 shadow-sm rounded-4 bg-white d-flex align-items-center gap-3">
                <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-4 fs-3">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold">আজকের পরিবর্তন</div>
                    <h4 class="fw-bold mb-0 text-dark">{{ number_format($stats['today_changes'] ?? 0) }} <span class="fs-6 fw-normal text-muted">বার</span></h4>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card-modern p-3 border-0 shadow-sm rounded-4 bg-white d-flex align-items-center gap-3">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-4 fs-3">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold">অডিট ও নিরাপত্তা</div>
                    <div class="text-success small fw-bold mt-1"><i class="fa-solid fa-check-circle me-1"></i>প্রতিটি পরিবর্তন সংরক্ষিত হচ্ছে</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ২. সার্চ ও ফিল্টার বক্স -->
    <div class="card-modern p-4 mb-4 border-0 shadow-sm rounded-4 bg-white">
        <form action="{{ route('admin.users.upline-logs') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted text-uppercase">ইউজার, এডমিন বা রেফার কোড দিয়ে খুঁজুন</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="নাম, ফোন, রেফার কোড বা এডমিন ইউজারনেম..." value="{{ $search ?? '' }}">
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted text-uppercase">তারিখ ফিল্টার</label>
                <div class="d-flex gap-2">
                    <select name="date_filter" class="form-select rounded-pill" onchange="toggleCustomDate(this.value)">
                        <option value="all" {{ ($dateFilter ?? '') === 'all' ? 'selected' : '' }}>সকল তারিখ (All)</option>
                        <option value="today" {{ ($dateFilter ?? '') === 'today' ? 'selected' : '' }}>আজকে (Today)</option>
                        <option value="yesterday" {{ ($dateFilter ?? '') === 'yesterday' ? 'selected' : '' }}>গতকাল (Yesterday)</option>
                        <option value="custom" {{ ($dateFilter ?? '') === 'custom' ? 'selected' : '' }}>কাস্টম তারিখ (Custom Date)</option>
                    </select>
                    <input type="date" name="custom_date" id="custom_date_input" class="form-control rounded-pill {{ ($dateFilter ?? '') === 'custom' ? '' : 'd-none' }}" value="{{ $customDate ?? '' }}">
                </div>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 w-100 fw-bold shadow-sm">
                    <i class="fa-solid fa-filter me-2"></i>ফিল্টার করুন
                </button>
                @if(!empty($search) || ($dateFilter ?? 'all') !== 'all')
                <a href="{{ route('admin.users.upline-logs') }}" class="btn btn-light rounded-pill px-3 shadow-sm" title="রিসেট">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- ৩. লগ টেবিল -->
    <div class="card-modern p-0 overflow-hidden shadow-sm border-0 rounded-4 bg-white mb-4">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-list-check text-primary me-2"></i>আপলাইন পরিবর্তনের বিস্তারিত রেকর্ড
            </h6>
            <span class="badge bg-secondary-soft text-secondary rounded-pill px-3 py-1">মোট {{ $logs->total() }} টি লগ পাওয়া গেছে</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">তারিখ ও সময়</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">কে পরিবর্তন করেছে (Admin)</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">যে ইউজারের পরিবর্তন (Target User)</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">আগের আপলাইন (Old)</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">নতুন আপলাইন (New)</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="fw-bold text-dark small">{{ $log->created_at ? $log->created_at->timezone('Asia/Dhaka')->format('d M, Y') : 'N/A' }}</div>
                            <div class="text-muted extra-small">{{ $log->created_at ? $log->created_at->timezone('Asia/Dhaka')->format('h:i A') : '' }}</div>
                        </td>

                        <td class="py-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-2 bg-primary-soft text-primary fw-bold d-flex align-items-center justify-content-center rounded-circle" style="width: 32px; height: 32px; font-size: 12px;">
                                    {{ strtoupper(substr($log->admin_name ?: $log->admin_username ?: 'A', 0, 2)) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small">{{ $log->admin_name ?: 'Admin' }}</div>
                                    <div class="extra-small text-muted">@ {{ $log->admin_username }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="py-3">
                            <div>
                                <a href="{{ route('admin.users.show', $log->user_id) }}" class="fw-bold text-primary text-decoration-none small">
                                    {{ $log->user_name ?: 'User #' . $log->user_id }}
                                </a>
                                <div class="text-muted extra-small">
                                    <i class="fa-solid fa-phone me-1"></i>{{ $log->user_number ?: 'N/A' }} &bull; 
                                    <span class="badge bg-light text-dark border">Ref: {{ $log->user_refer_code ?: 'N/A' }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="py-3">
                            @if($log->old_upline_code)
                                <div class="p-2 bg-light rounded-3 d-inline-block border">
                                    <div class="badge bg-danger text-white mb-1"><i class="fa-solid fa-arrow-left me-1"></i>{{ $log->old_upline_code }}</div>
                                    @if($log->old_upline_name)
                                        <div class="extra-small text-dark fw-bold">{{ $log->old_upline_name }}</div>
                                    @endif
                                    @if($log->old_upline_number)
                                        <div class="extra-small text-muted">{{ $log->old_upline_number }}</div>
                                    @endif
                                </div>
                            @else
                                <span class="badge bg-light text-muted border">ডিরেক্ট / ছিল না</span>
                            @endif
                        </td>

                        <td class="py-3">
                            @if($log->new_upline_code)
                                <div class="p-2 bg-light rounded-3 d-inline-block border border-success">
                                    <div class="badge bg-success text-white mb-1"><i class="fa-solid fa-arrow-right me-1"></i>{{ $log->new_upline_code }}</div>
                                    @if($log->new_upline_name)
                                        <div class="extra-small text-dark fw-bold">{{ $log->new_upline_name }}</div>
                                    @endif
                                    @if($log->new_upline_number)
                                        <div class="extra-small text-muted">{{ $log->new_upline_number }}</div>
                                    @endif
                                </div>
                            @else
                                <span class="badge bg-warning-soft text-warning border">ডিরেক্ট / রিমুভড</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-end">
                            <a href="{{ route('admin.users.show', $log->user_id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" title="ইউজার প্রোফাইল দেখুন">
                                <i class="fa-solid fa-user me-1"></i>View Profile
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-clipboard-check fs-1 text-muted opacity-50 mb-3 d-block"></i>
                            <h6 class="fw-bold">কোনো আপলাইন পরিবর্তনের রেকর্ড পাওয়া যায়নি</h6>
                            <p class="small mb-0 text-muted">পূর্বে ইউজার প্রোফাইল থেকে কোনো আপলাইন রেফার কোড পরিবর্তন করা হলে তা এখানে তালিকাভুক্ত হবে।</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="p-4 border-top">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>

<script>
function toggleCustomDate(val) {
    const input = document.getElementById('custom_date_input');
    if (val === 'custom') {
        input.classList.remove('d-none');
        input.focus();
    } else {
        input.classList.add('d-none');
    }
}
</script>
@endsection

