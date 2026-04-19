@extends('layouts.admin')

@section('title', 'User Details')
@section('page_title', 'User Profile: ' . $user->name)

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('admin.users.index') }}" class="btn btn-light rounded-pill shadow-sm">
            <i class="fa-solid fa-arrow-left me-2"></i>Back to List
        </a>
        <div class="d-flex gap-2">
            <button class="btn btn-success rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addMoneyModal">
                <i class="fa-solid fa-plus me-2"></i>Add Balance
            </button>
            <button class="btn btn-danger rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#withdrawMoneyModal">
                <i class="fa-solid fa-minus me-2"></i>Withdraw
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('error') }}
        </div>
    @endif

    <div class="row g-4">
        <!-- Profile Card -->
        <div class="col-md-4">
            <div class="card-modern text-center p-4">
                <div class="mb-4">
                    <img src="{{ $user->profile_pic_url ?: 'https://thumb.ac-illust.com/b1/b170870007dfa419295d949814474ab2_t.jpeg' }}" 
                         class="rounded-circle shadow-sm border border-4 border-white" 
                         style="width: 150px; height: 150px; object-fit: cover;">
                </div>
                <h4 class="fw-bold mb-1">{{ $user->name }}</h4>
                <div class="badge bg-primary-soft text-primary rounded-pill px-3 mb-3">#{{ $user->referCode }}</div>
                
                <div class="row g-2 mt-2">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-4">
                            <div class="extra-small text-muted text-uppercase fw-bold mb-1">Wallet</div>
                            <div class="fw-bold text-dark">৳{{ number_format($user->wallet_balance, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-4">
                            <div class="extra-small text-muted text-uppercase fw-bold mb-1">Voucher</div>
                            <div class="fw-bold text-dark">৳{{ number_format($user->voucher_balance, 2) }}</div>
                        </div>
                    </div>
                </div>

                <hr class="my-4 opacity-50">

                <div class="text-start">
                    <div class="mb-3">
                        <label class="extra-small text-muted text-uppercase fw-bold d-block">Contact</label>
                        <div class="fw-medium">{{ $user->number }}</div>
                        <div class="small text-muted">{{ $user->email ?? 'No email provided' }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="extra-small text-muted text-uppercase fw-bold d-block">Referral Info</label>
                        <div class="fw-medium">Referred By: {{ $user->referredBy ?: 'System' }}</div>
                    </div>
                    <div class="mb-0">
                        <label class="extra-small text-muted text-uppercase fw-bold d-block">Member Since</label>
                        <div class="small fw-medium">{{ $user->created_at }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Form & Stats -->
        <div class="col-md-8">
            <div class="card-modern mb-4" id="edit">
                <h5 class="fw-bold mb-4">Edit User Account</h5>
                <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Full Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Phone Number</label>
                            <input type="text" name="number" class="form-control" value="{{ $user->number }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Referred By (Code)</label>
                            <input type="text" name="referredBy" class="form-control" value="{{ $user->referredBy }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Verification Status</label>
                            <select name="is_verified" class="form-select">
                                <option value="0" {{ $user->is_verified == 0 ? 'selected' : '' }}>Unverified</option>
                                <option value="1" {{ $user->is_verified == 1 ? 'selected' : '' }}>Verified</option>
                                <option value="2" {{ $user->is_verified == 2 ? 'selected' : '' }}>Pending</option>
                                <option value="3" {{ $user->is_verified == 3 ? 'selected' : '' }}>Demo Verified</option>
                                <option value="4" {{ $user->is_verified == 4 ? 'selected' : '' }}>Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small text-muted text-uppercase fw-bold">Change Password (Leave blank to keep current)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-key text-muted"></i></span>
                                <input type="text" name="password" class="form-control border-start-0 ps-0" placeholder="New Password">
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Update Profile</button>
                    </div>
                </form>
            </div>

            <!-- Transactions List -->
            <div class="card-modern p-0 overflow-hidden">
                <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                    <h5 class="fw-bold mb-0">Recent Transactions</h5>
                    <span class="text-muted small">Showing latest {{ $transactions->count() }} of {{ $transactions->total() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Type</th>
                                <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Amount</th>
                                <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Gateway</th>
                                <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Description</th>
                                <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $txn)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="badge {{ in_array($txn->type, ['income', 'add', 'commission']) ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger' }} rounded-pill text-capitalize">
                                        {{ $txn->type }}
                                    </span>
                                </td>
                                <td class="py-3 fw-bold {{ in_array($txn->type, ['income', 'add', 'commission']) ? 'text-success' : 'text-danger' }}">
                                    {{ in_array($txn->type, ['income', 'add', 'commission']) ? '+' : '-' }} ৳{{ number_format($txn->amount, 2) }}
                                </td>
                                <td class="py-3 text-muted small">{{ $txn->payment_gateway }}</td>
                                <td class="py-3">
                                    <div class="small fw-medium">{{ $txn->description }}</div>
                                </td>
                                <td class="px-4 py-3 text-muted extra-small">
                                    {{ $txn->update_at }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No transactions found for this user.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($transactions->hasPages())
                <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
                    {{ $transactions->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Money Modal -->
<div class="modal fade" id="addMoneyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow rounded-4" action="{{ route('admin.users.add-money', $user->id) }}" method="POST">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Add Balance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase fw-bold">Amount (৳)</label>
                    <input type="number" step="0.01" name="amount" class="form-control form-control-lg" placeholder="0.00" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase fw-bold">Gateway / Source</label>
                    <input type="text" name="payment_gateway" class="form-control" placeholder="e.g. Bkash, Refund, etc." required>
                </div>
                <div class="mb-4">
                    <label class="form-label small text-muted text-uppercase fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Reason for adding money..."></textarea>
                </div>
                <div class="form-check form-switch p-3 bg-light rounded-4">
                    <input class="form-check-input ms-0 me-3" type="checkbox" name="give_commission" id="give_commission" value="1">
                    <label class="form-check-label fw-bold small" for="give_commission">Distribute 10% Referral Commission</label>
                    <div class="extra-small text-muted mt-1">This will split 10% across 5 levels of referrers.</div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">Confirm Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Withdraw Money Modal -->
<div class="modal fade" id="withdrawMoneyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow rounded-4" action="{{ route('admin.users.withdraw-money', $user->id) }}" method="POST">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Withdraw Balance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-4">Current Balance: <span class="fw-bold text-dark">৳{{ number_format($user->wallet_balance, 2) }}</span></p>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase fw-bold">Withdraw Amount (৳)</label>
                    <input type="number" step="0.01" name="amount" class="form-control form-control-lg text-danger fw-bold" placeholder="0.00" required max="{{ $user->wallet_balance }}">
                </div>
                <div class="mb-0">
                    <label class="form-label small text-muted text-uppercase fw-bold">Description / Reason</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="e.g. Manual correction, Fine, etc." required></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger rounded-pill px-4 shadow-sm">Confirm Withdraw</button>
            </div>
        </form>
    </div>
</div>
@endsection
