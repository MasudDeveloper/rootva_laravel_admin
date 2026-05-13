@extends('layouts.admin')

@section('title', 'Referral Bonus Distribute')
@section('page_title', '🔗 Manual Refer Bonus')

@section('content')
<div class="fade-in max-w-700 mx-auto">
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

    <div class="card-modern mb-4">
        <div class="p-4">
            <div class="mb-4">
                <h5 class="fw-bold mb-1">Trigger Selective Level Distribution</h5>
                <p class="text-muted small">Enter a user's Refer Code and select the levels you want to distribute commissions to.</p>
            </div>

            <form action="{{ route('admin.rewards.refer-bonus.distribute') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">User Refer Code</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="fa-solid fa-hashtag text-primary"></i></span>
                        <input type="text" name="refer_code" class="form-control border-start-0 rounded-end-pill p-3 @error('refer_code') is-invalid @enderror" 
                               placeholder="e.g. AB1234" value="{{ old('refer_code') }}" required>
                        @error('refer_code')
                            <div class="invalid-feedback ms-3">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">Select Levels to Award</label>
                    <div class="row g-3">
                        @php $levels = [76, 35, 15, 10, 6, 5, 4, 3, 2, 2]; @endphp
                        @foreach($levels as $index => $amount)
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="form-check card-modern-select p-3 rounded-4 border">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="selected_levels[]" value="{{ $index + 1 }}" id="lvl{{ $index + 1 }}">
                                    <label class="form-check-label fw-bold small mb-0" for="lvl{{ $index + 1 }}">
                                        Level {{ $index + 1 }} <span class="text-success ms-1">৳{{ $amount }}</span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-text text-muted small mt-2">If no levels are selected, the system will distribute to all 10 levels by default.</div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm" onclick="return confirm('Are you sure you want to distribute commissions for the selected levels?')">
                        Distribute Bonus
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Section -->
    <div class="card-modern">
        <div class="p-4">
            <div class="mb-4">
                <h5 class="fw-bold mb-1">Search & Manage Distributed Bonuses</h5>
                <p class="text-muted small">Search for referral bonuses already given for a specific user to edit or delete them.</p>
            </div>

            <form action="{{ route('admin.rewards.refer-bonus.history') }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-8 mb-3 mb-md-0">
                        <label class="form-label fw-bold text-muted small text-uppercase">Subject User Refer Code</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="fa-solid fa-search text-muted"></i></span>
                            <input type="text" name="refer_code" class="form-control border-start-0 rounded-end-pill p-3" 
                                   placeholder="Search by subject user code..." required>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="submit" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm border">
                            Search History <i class="fa-solid fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
