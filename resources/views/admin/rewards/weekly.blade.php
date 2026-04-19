@extends('layouts.admin')

@section('title', 'Weekly Bonus')
@section('page_title', '🏆 Weekly Top Performers')

@section('content')
<div class="fade-in">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h5 class="fw-bold mb-0">Weekly Reward History</h5>
        </div>
        <div class="col-auto">
            <form action="{{ route('admin.rewards.weekly.run') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-warning rounded-pill shadow-sm" onclick="return confirm('Award ৳500 to the past 7 days\' top referrer?')">
                    <i class="fa-solid fa-trophy me-2"></i>Run Weekly Reward
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

    <div class="card-modern p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Winner</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Mobile</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Reward</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Date</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($winners as $row)
                    <tr>
                        <td class="px-4 py-3 fw-bold text-dark">{{ $row->user->name ?? 'N/A' }}</td>
                        <td class="py-3 text-muted">{{ $row->user->number ?? 'N/A' }}</td>
                        <td class="py-3 text-center fw-bold text-warning">৳{{ number_format($row->amount, 2) }}</td>
                        <td class="py-3 text-center text-muted small">{{ date('d-m-Y', strtotime($row->created_at)) }}</td>
                        <td class="px-4 py-3 text-end extra-small text-muted">{{ $row->description }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No weekly bonus winners recorded yet.</td>
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
@endsection
