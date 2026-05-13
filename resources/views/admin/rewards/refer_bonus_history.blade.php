@extends('layouts.admin')

@section('title', 'Referral Bonus History')
@section('page_title', '📜 Refer Bonus History')

@section('content')
<div class="fade-in">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="card-modern p-0 overflow-hidden mb-4">
        <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center bg-gray-50">
            <h5 class="fw-bold mb-0">Distributed Bonuses</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.rewards.refer-bonus') }}" class="btn btn-light btn-sm rounded-pill px-3 border fw-bold">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Recipient (Upliner)</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Subject Code</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Amount</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Level / Description</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Date</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bonuses as $bonus)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="fw-bold text-dark">{{ $bonus->user->name ?? 'Unknown' }}</div>
                            <div class="extra-small text-muted">{{ $bonus->user->referCode ?? 'N/A' }}</div>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-light text-primary border rounded-pill px-3">{{ $bonus->refer_id }}</span>
                        </td>
                        <td class="py-3 text-center">
                            <span class="fw-bold text-success">৳{{ number_format($bonus->amount, 2) }}</span>
                        </td>
                        <td class="py-3 small">
                            @if($bonus->payment_gateway)
                                <span class="badge bg-primary-soft text-primary extra-small mb-1" style="font-size: 0.65rem;">{{ $bonus->payment_gateway }}</span><br>
                            @endif
                            {{ $bonus->description }}
                        </td>
                        <td class="py-3 small text-muted">
                            {{ $bonus->update_at }}
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <button class="btn btn-light btn-sm rounded-circle shadow-sm p-2" 
                                        data-bs-toggle="modal" data-bs-target="#editModal{{ $bonus->id }}" title="Edit Amount">
                                    <i class="fa-solid fa-pen text-primary"></i>
                                </button>
                                <form action="{{ route('admin.rewards.refer-bonus.delete', $bonus->id) }}" method="POST" onsubmit="return confirm('Delete this bonus? The amount will be subtracted from user\'s wallet.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-light btn-sm rounded-circle shadow-sm p-2" title="Delete Bonus">
                                        <i class="fa-solid fa-trash text-danger"></i>
                                    </button>
                                </form>
                            </div>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal{{ $bonus->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                        <div class="modal-header border-0 pb-0">
                                            <h6 class="modal-title fw-bold">Edit Bonus Amount</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('admin.rewards.refer-bonus.update', $bonus->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <div class="modal-body py-3">
                                                <div class="text-center mb-3">
                                                    <div class="small text-muted mb-1">Current Amount: ৳{{ $bonus->amount }}</div>
                                                    <input type="number" step="0.01" name="amount" class="form-control text-center fw-bold rounded-pill p-3 border-primary" value="{{ $bonus->amount }}" required>
                                                </div>
                                                <p class="extra-small text-muted mb-0 text-center">
                                                    <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>
                                                    This will automatically adjust the user's current wallet balance.
                                                </p>
                                            </div>
                                            <div class="modal-footer border-0 pt-0 pb-3 justify-content-center">
                                                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No referral bonus transactions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bonuses->hasPages())
        <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
            {{ $bonuses->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
