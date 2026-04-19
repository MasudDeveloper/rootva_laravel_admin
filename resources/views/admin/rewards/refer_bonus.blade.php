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

    <div class="card-modern">
        <div class="p-4">
            <div class="mb-4">
                <h5 class="fw-bold mb-1">Trigger 10-Level Distribution</h5>
                <p class="text-muted small">Enter a user's Refer Code to manually trigger the affiliate commission chain up to 10 levels.</p>
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

                <div class="alert alert-info border-0 rounded-4 p-3 mb-4">
                    <div class="d-flex">
                        <i class="fa-solid fa-circle-info mt-1 me-3"></i>
                        <div>
                            <div class="fw-bold small">System Logic:</div>
                            <ul class="extra-small mb-0 mt-1 ps-3">
                                <li>Level 1: ৳76 + 4 Math Game Spins</li>
                                <li>Level 2: ৳35</li>
                                <li>Level 3: ৳15 ... and so on down to Level 10.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm" onclick="return confirm('Are you sure you want to distribute commissions for this user?')">
                        Distribute Bonus Chain
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
