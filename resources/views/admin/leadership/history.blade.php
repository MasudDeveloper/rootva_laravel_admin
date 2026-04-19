@extends('layouts.admin')

@section('title', 'Leadership Bonus Winners')
@section('page_title', 'Leadership Rewards')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Disbursed Bonuses</h4>
            <p class="text-muted small mb-0">List of users who achieved leadership ranks</p>
        </div>
        <a href="{{ route('admin.leadership.requests') }}" class="btn btn-outline-primary rounded-pill px-4 shadow-sm">
            <i class="fa-solid fa-clock-rotate-left me-2"></i>Manage Pending Claims
        </a>
    </div>

    {{-- Level Filters --}}
    <div class="card-modern border-0 shadow-sm p-3 mb-4 overflow-auto">
        <div class="d-flex gap-2 flex-nowrap">
            <a href="{{ route('admin.leadership.history') }}" class="btn {{ !$rewardFilter ? 'btn-primary' : 'btn-light border text-muted' }} rounded-pill px-4 btn-sm fw-bold">All Ranks</a>
            <a href="{{ route('admin.leadership.history', ['reward' => 'Rootva Leader']) }}" class="btn {{ $rewardFilter === 'Rootva Leader' ? 'btn-primary' : 'btn-light border text-muted' }} rounded-pill px-4 btn-sm fw-bold">Rootva Leader</a>
            <a href="{{ route('admin.leadership.history', ['reward' => 'Silver']) }}" class="btn {{ $rewardFilter === 'Silver' ? 'btn-primary' : 'btn-light border text-muted' }} rounded-pill px-4 btn-sm fw-bold">Silver</a>
            <a href="{{ route('admin.leadership.history', ['reward' => 'Gold']) }}" class="btn {{ $rewardFilter === 'Gold' ? 'btn-primary' : 'btn-light border text-muted' }} rounded-pill px-4 btn-sm fw-bold">Gold</a>
            <a href="{{ route('admin.leadership.history', ['reward' => 'Diamond']) }}" class="btn {{ $rewardFilter === 'Diamond' ? 'btn-primary' : 'btn-light border text-muted' }} rounded-pill px-4 btn-sm fw-bold">Diamond</a>
            <a href="{{ route('admin.leadership.history', ['reward' => 'Top']) }}" class="btn {{ $rewardFilter === 'Top' ? 'btn-primary' : 'btn-light border text-muted' }} rounded-pill px-4 btn-sm fw-bold">Top Reward</a>
        </div>
    </div>

    {{-- Winners Table --}}
    <div class="card-modern border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">#</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-start">Achiever</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Refer Code</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-end">Amount</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Achieved Rank</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Disbursed At</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @forelse($winners as $winner)
                    <tr>
                        <td class="px-4 py-3 text-muted small">#{{ $loop->iteration + ($winners->currentPage() - 1) * $winners->perPage() }}</td>
                        <td class="py-3 text-start">
                            <div class="fw-bold text-dark">{{ $winner->name }}</div>
                            <div class="text-muted extra-small"><i class="fa-solid fa-phone-volume me-1"></i>{{ $winner->number }}</div>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-light text-primary border rounded-pill px-3">{{ $winner->referCode }}</span>
                        </td>
                        <td class="py-3 text-end fw-bold text-success">
                            ৳{{ number_format($winner->amount, 2) }}
                        </td>
                        <td class="py-3">
                            <span class="badge bg-primary-soft text-primary rounded-pill px-3">
                                {{ str_replace(' Reward', '', $winner->description) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted small">
                            {{ date('d M Y', strtotime($winner->created_at)) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No disbursed bonuses found for this category.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($winners->hasPages())
        <div class="px-4 py-3 border-top bg-light d-flex justify-content-center">
            {{ $winners->links() }}
        </div>
        @endif
    </div>
</div>

<style>
    .bg-gray-50 { background-color: #f9fafb; }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .card-modern { border-radius: 1.25rem; }
    .extra-small { font-size: 0.75rem; }
</style>
@endsection
