@extends('layouts.admin')

@section('title', 'Spin Rewards')
@section('page_title', '🎡 Spin Bonus History')

@section('content')
<div class="fade-in">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h5 class="fw-bold mb-0">Recent Spin Payouts</h5>
        </div>
    </div>

    <div class="card-modern p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">User</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Mobile</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Amount</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Time</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($spins as $row)
                    <tr>
                        <td class="px-4 py-3 fw-bold text-dark">{{ $row->user->name ?? 'N/A' }}</td>
                        <td class="py-3 text-muted">{{ $row->user->number ?? 'N/A' }}</td>
                        <td class="py-3 text-center fw-bold text-primary">৳{{ number_format($row->amount, 2) }}</td>
                        <td class="py-3 text-center text-muted small">
                            {{ $row->update_at }}
                        </td>
                        <td class="px-4 py-3 text-end extra-small text-muted">{{ $row->description }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No spin reward history found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($spins->hasPages())
        <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
            {{ $spins->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
