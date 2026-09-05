@extends('layouts.admin')

@section('title', 'User Details')
@section('page_title', 'User Profile: ' . $user->name)

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('admin.users.index') }}" class="btn btn-light rounded-pill shadow-sm">
            <i class="fa-solid fa-arrow-left me-2"></i>Back to List
        </a>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-success rounded-pill px-3 shadow-sm mb-2" data-bs-toggle="modal" data-bs-target="#addMoneyModal">
                <i class="fa-solid fa-plus me-2"></i>Add Balance
            </button>
            <button class="btn btn-danger rounded-pill px-3 shadow-sm mb-2" data-bs-toggle="modal" data-bs-target="#withdrawMoneyModal">
                <i class="fa-solid fa-minus me-2"></i>Withdraw
            </button>
            <button class="btn btn-warning text-white rounded-pill px-3 shadow-sm mb-2" data-bs-toggle="modal" data-bs-target="#transferVoucherModal">
                <i class="fa-solid fa-right-left me-2"></i>Transfer Voucher
            </button>
            <button class="btn btn-info text-white rounded-pill px-3 shadow-sm mb-2" data-bs-toggle="modal" data-bs-target="#addDemoOrderModal">
                <i class="fa-solid fa-cart-plus me-2"></i>Add Demo Order
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

                @if($user->is_verified == 1)
                <div class="mb-4">
                    <button type="button" class="btn btn-primary rounded-pill w-100 shadow-sm fw-bold" onclick="downloadCard(this)">
                        <i class="fa-solid fa-download me-2"></i>Download Verified Card
                    </button>
                </div>
                @endif

                <div class="text-start">
                    <div class="mb-3">
                        <label class="extra-small text-muted text-uppercase fw-bold d-block">Contact</label>
                        <div class="fw-medium">{{ $user->number }}</div>
                        <div class="small text-muted">{{ $user->email ?? 'No email provided' }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="extra-small text-muted text-uppercase fw-bold d-block">Referral Info</label>
                        <div class="fw-medium">Referred By Code: {{ $user->referredBy ?: 'System (Direct)' }}</div>
                        @if($user->referredBy)
                            @php
                                $referrer = \App\Models\SignUp::where('referCode', $user->referredBy)->first();
                            @endphp
                            @if($referrer)
                                <div class="small text-muted">
                                    Name: <strong>{{ $referrer->name }}</strong><br>
                                    Phone: <strong>{{ $referrer->number }}</strong>
                                </div>
                            @else
                                <div class="small text-danger italic">Referrer not found in database</div>
                            @endif
                        @endif
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
            <!-- Leadership Progress Status -->
            <div class="card-modern mb-4 shadow-sm border-0 rounded-4">
                <h5 class="fw-bold mb-4"><i class="fa-solid fa-medal text-warning me-2"></i>Leadership Achievement Progress</h5>
                <div class="row g-3">
                    <!-- Rootva Leader -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-light h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-primary">Rootva Leader</span>
                                <span class="badge bg-primary-soft text-primary rounded-pill">{{ $leadership['l1_verified'] }} / 15 L1 Verified</span>
                            </div>
                            <div class="progress rounded-pill mb-2" style="height: 10px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $leadership['rootva_progress'] }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted extra-small">Requires 15 L1 Verified Members</small>
                                @if($leadership['rootva_achieved'])
                                    <span class="badge bg-success text-white rounded-pill"><i class="fa-solid fa-circle-check"></i> Achieved</span>
                                @else
                                    <span class="badge bg-secondary-soft text-secondary rounded-pill">In Progress</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Silver Leader -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-light h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-success">Silver Leader</span>
                                <span class="badge bg-success-soft text-success rounded-pill">{{ $leadership['l1_rootvas'] }} / 10 Rootvas</span>
                            </div>
                            <div class="progress rounded-pill mb-2" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $leadership['silver_progress'] }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted extra-small">Requires 10 L1 Rootva Leaders</small>
                                @if($leadership['silver_achieved'])
                                    <span class="badge bg-success text-white rounded-pill"><i class="fa-solid fa-circle-check"></i> Achieved</span>
                                @else
                                    <span class="badge bg-secondary-soft text-secondary rounded-pill">In Progress</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Gold Leader -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-light h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-warning">Gold Leader</span>
                                <span class="badge bg-warning-soft text-warning rounded-pill">{{ $leadership['l1_silvers'] }} / 10 Silvers</span>
                            </div>
                            <div class="progress rounded-pill mb-2" style="height: 10px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $leadership['gold_progress'] }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted extra-small">Requires 10 L1 Silver Leaders</small>
                                @if($leadership['gold_achieved'])
                                    <span class="badge bg-success text-white rounded-pill"><i class="fa-solid fa-circle-check"></i> Achieved</span>
                                @else
                                    <span class="badge bg-secondary-soft text-secondary rounded-pill">In Progress</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Diamond & Top Leader Summary -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-light h-100 d-flex flex-column justify-content-between">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-info">Personal Confirmed Orders</span>
                                <span class="badge bg-info-soft text-info rounded-pill">{{ $leadership['order_count'] }} Orders</span>
                            </div>
                            <div class="small mb-1">
                                <span class="fw-semibold text-muted">Diamond Leader:</span>
                                @if($leadership['diamond_achieved'])
                                    <span class="badge bg-success text-white rounded-pill"><i class="fa-solid fa-circle-check"></i> Achieved</span>
                                @else
                                    <span class="text-muted extra-small">(Needs Gold & 3 Personal Orders)</span>
                                @endif
                            </div>
                            <div class="small">
                                <span class="fw-semibold text-muted">Top Leader:</span>
                                @if($leadership['top_achieved'])
                                    <span class="badge bg-success text-white rounded-pill"><i class="fa-solid fa-circle-check"></i> Achieved</span>
                                @else
                                    <span class="text-muted extra-small">(Needs Gold & 10 Personal Orders)</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Salary Progress Status -->
            <div class="card-modern mb-4 shadow-sm border-0 rounded-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-briefcase text-primary me-2"></i>Monthly Salary Progress</h5>
                    <span class="text-muted small">Tracking since: <strong class="text-dark">{{ $salaryProgress['start_date'] != '2000-01-01 00:00:00' ? date('d M Y, h:i A', strtotime($salaryProgress['start_date'])) : 'Beginning' }}</strong></span>
                </div>
                
                @if($salaryProgress['eligible'])
                    <div class="alert alert-success border-0 rounded-pill px-4 py-2 mb-4 d-flex align-items-center">
                        <i class="fa-solid fa-circle-check fa-lg me-3"></i>
                        <div>
                            <span class="fw-bold text-success me-1">Eligible for Monthly Salary!</span> This user qualifies for salary bonus.
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning border-0 rounded-pill px-4 py-2 mb-4 d-flex align-items-center" style="background-color: #fffbeb; color: #b45309;">
                        <i class="fa-solid fa-circle-info fa-lg me-3"></i>
                        <div>
                            <span class="fw-bold">Incomplete Requirements.</span> User is not yet eligible for salary.
                        </div>
                    </div>
                @endif

                <div class="row g-3">
                    <!-- Level 1 Verified -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-light h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark">L1 Verified (New)</span>
                                <span class="badge bg-primary-soft text-primary rounded-pill">{{ $salaryProgress['l1_verified'] }} / 30</span>
                            </div>
                            <div class="progress rounded-pill mb-2" style="height: 10px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $salaryProgress['l1_verified_progress'] }}%"></div>
                            </div>
                            <small class="text-muted extra-small">Requires 30 verified Level 1 members since last salary.</small>
                        </div>
                    </div>

                    <!-- Level 2 Verified -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-light h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark">L2 Verified (New)</span>
                                <span class="badge bg-success-soft text-success rounded-pill">{{ $salaryProgress['l2_verified'] }} / 60</span>
                            </div>
                            <div class="progress rounded-pill mb-2" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $salaryProgress['l2_verified_progress'] }}%"></div>
                            </div>
                            <small class="text-muted extra-small">Requires 60 verified Level 2 members since last salary.</small>
                        </div>
                    </div>

                    <!-- Level 1 Active -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-light h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark">L1 Active Members</span>
                                <span class="badge bg-warning-soft text-warning rounded-pill">{{ $salaryProgress['l1_active'] }} / 10</span>
                            </div>
                            <div class="progress rounded-pill mb-2" style="height: 10px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $salaryProgress['l1_active_progress'] }}%"></div>
                            </div>
                            <small class="text-muted extra-small d-block">Requires 10 Active Level 1 members (each has verified L1 + 2 L2 verified).</small>
                            
                            @if(count($salaryProgress['active_members']) > 0)
                                <div class="mt-2">
                                    <a class="btn btn-warning-soft text-warning btn-sm w-100 rounded-pill extra-small fw-bold py-1" data-bs-toggle="collapse" href="#activeMembersList" role="button">
                                        <i class="fa-solid fa-users me-1"></i> View Active Members ({{ count($salaryProgress['active_members']) }})
                                    </a>
                                    <div class="collapse mt-2" id="activeMembersList">
                                        <div class="bg-white border rounded-3 p-2" style="max-height: 200px; overflow-y: auto;">
                                            @foreach($salaryProgress['active_members'] as $member)
                                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size: 11px;">
                                                    <div>
                                                        <div class="fw-bold text-dark">{{ $member['name'] }}</div>
                                                        <div class="text-muted" style="font-size: 10px;">{{ $member['number'] }} (REF: {{ $member['refer_code'] }})</div>
                                                    </div>
                                                    <span class="badge bg-success-soft text-success rounded-pill font-monospace">+{{ $member['l2_count'] }} L2</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Delivered Orders -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-light h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-info">Delivered Orders</span>
                                <span class="badge bg-info-soft text-info rounded-pill">{{ $salaryProgress['orders'] }} / 1 Delivered</span>
                            </div>
                            <div class="progress rounded-pill mb-2" style="height: 10px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: {{ $salaryProgress['orders_progress'] }}%"></div>
                            </div>
                            <small class="text-muted extra-small">Requires 1 delivered reselling order since last salary.</small>
                        </div>
                    </div>
                </div>
            </div>

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
                                    <span class="badge {{ in_array($txn->type, ['income', 'add', 'commission', 'course_bonus', 'voucher_convert']) ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger' }} rounded-pill text-capitalize">
                                        {{ str_replace('_', ' ', $txn->type) }}
                                    </span>
                                </td>
                                <td class="py-3 fw-bold {{ in_array($txn->type, ['income', 'add', 'commission', 'course_bonus', 'voucher_convert']) ? 'text-success' : 'text-danger' }}">
                                    {{ in_array($txn->type, ['income', 'add', 'commission', 'course_bonus', 'voucher_convert']) ? '+' : '-' }} ৳{{ number_format($txn->amount, 2) }}
                                </td>
                                <td class="py-3 text-muted small">{{ $txn->payment_gateway }}</td>
                                <td class="py-3">
                                    <div class="small fw-medium">{{ $txn->description }}</div>
                                    @if($txn->refer_id)
                                    <div class="badge bg-primary-soft text-primary rounded-pill extra-small mt-1 fw-bold">From REF: {{ $txn->refer_id }}</div>
                                    @endif
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
        <form class="modal-content border-0 shadow rounded-4" action="{{ route('admin.users.add-money', $user->id) }}" method="POST" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = '<i class=\'fa-solid fa-spinner fa-spin me-2\'></i>Processing...';">
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

<!-- Transfer Voucher Modal -->
<div class="modal fade" id="transferVoucherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow rounded-4" action="{{ route('admin.users.transfer-voucher', $user->id) }}" method="POST">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Transfer Voucher Balance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-4">Voucher Balance: <span class="fw-bold text-dark">৳{{ number_format($user->voucher_balance, 2) }}</span></p>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase fw-bold">Transfer Amount (৳)</label>
                    <input type="number" step="0.01" name="amount" class="form-control form-control-lg text-warning fw-bold" placeholder="0.00" required max="{{ $user->voucher_balance }}">
                    <div class="extra-small text-muted mt-1">This will deduct from Voucher balance and add directly to Main Wallet.</div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning text-white rounded-pill px-4 shadow-sm">Confirm Transfer</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Demo Order Modal -->
<div class="modal fade" id="addDemoOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow rounded-4" action="{{ route('admin.users.add-demo-order', $user->id) }}" method="POST">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Add Confirmed Demo Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase fw-bold">Product Name</label>
                    <input type="text" name="product_name" class="form-control" placeholder="e.g. Premium Reselling Package" required value="Demo Reselling Order">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold">Product Price (৳)</label>
                        <input type="number" step="0.01" name="product_price" class="form-control" placeholder="0.00" required value="500">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold">Quantity</label>
                        <input type="number" name="quantity" class="form-control" placeholder="1" required value="1">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted text-uppercase fw-bold">Order Date (Custom Date)</label>
                    <input type="date" name="created_at" class="form-control" required value="{{ date('Y-m-d') }}">
                    <div class="extra-small text-muted mt-1">This order will count towards this user's leadership/salary on the selected date.</div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info text-white rounded-pill px-4 shadow-sm">Confirm Demo Order</button>
            </div>
        </form>
    </div>
</div>

@if($user->is_verified == 1)
<!-- Hidden Card for Download -->
@php
$imageUrl = $user->profile_pic_url ?: 'https://thumb.ac-illust.com/b1/b170870007dfa419295d949814474ab2_t.jpeg';
$base64Image = $imageUrl;
try {
$response = \Illuminate\Support\Facades\Http::timeout(5)->get($imageUrl);
if ($response->successful()) {
$contentType = $response->header('Content-Type') ?? 'image/jpeg';
$base64Image = 'data:' . $contentType . ';base64,' . base64_encode($response->body());
}
} catch (\Exception $e) {
// Fallback to URL if base64 conversion fails
}
@endphp
<div style="position: absolute; left: -9999px; top: -9999px;">
    <div id="verified-card" style="width: 420px; background: #ffffff; border-radius: 20px; padding: 30px 20px; text-align: center; font-family: 'Hind Siliguri', sans-serif; position: relative; border: 2px solid #e2e8f0; overflow: hidden; box-shadow: inset 0 0 50px rgba(0,50,200,0.05);">
        
        <!-- Background accents -->
        <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: linear-gradient(135deg, #0d47a1, #1976d2); transform: rotate(45deg); border-radius: 30px; opacity: 0.1;"></div>
        <div style="position: absolute; bottom: -50px; left: -50px; width: 150px; height: 150px; background: linear-gradient(135deg, #1976d2, #0d47a1); transform: rotate(45deg); border-radius: 30px; opacity: 0.1;"></div>

        <div style="position: relative; z-index: 2;">
            <!-- अभिनंदन! Text with 3D Gold Shadow -->
            <h1 style="color: #1e3a8a; font-weight: 900; font-size: 54px; margin: 0 0 20px 0; text-shadow: 2px 2px 0px #fde68a, 4px 4px 0px #fbbf24, 6px 6px 15px rgba(0,0,0,0.15); letter-spacing: 1px;">অভিনন্দন!</h1>
            
            <!-- Image with Gold Ring -->
            <div style="position: relative; display: inline-block; margin-bottom: 20px;">
                <div style="position: absolute; top: -12px; left: -12px; right: -12px; bottom: -12px; border: 4px dashed #d4af37; border-radius: 50%;"></div>
                <div style="position: absolute; top: -6px; left: -6px; right: -6px; bottom: -6px; border: 2px solid #ffd700; border-radius: 50%;"></div>
                
                <img src="{{ $base64Image }}" alt="Profile" style="width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid #ffffff; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15); position: relative; z-index: 3;">
                
                <!-- Verified Checkmark -->
                <div style="position: absolute; bottom: 5px; right: 0px; background: #0055ff; color: white; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border: 3px solid #ffffff; font-size: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.3); z-index: 4;">
                    <i class="fa-solid fa-check"></i>
                </div>
            </div>

            <!-- Name Ribbon -->
            <div style="margin-bottom: 25px;">
                <div style="display: inline-block; background: #0022aa; color: white; padding: 8px 50px; border-radius: 8px; font-weight: 800; font-size: 24px; box-shadow: 0 6px 12px rgba(0,34,170,0.3); position: relative;">
                    {{ $user->name }}
                    <div style="position: absolute; left: -10px; top: 10px; width: 0; height: 0; border-top: 10px solid transparent; border-bottom: 10px solid transparent; border-right: 10px solid #001155;"></div>
                    <div style="position: absolute; right: -10px; top: 10px; width: 0; height: 0; border-top: 10px solid transparent; border-bottom: 10px solid transparent; border-left: 10px solid #001155;"></div>
                </div>
            </div>

            <!-- Text Box -->
            <div style="background-color: #ffffff; border-radius: 12px; padding: 15px 20px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                <p style="color: #1e293b; font-size: 15px; line-height: 1.6; margin: 0; font-weight: 600;">
                    আপনার ২৫০ টাকা পেমেন্ট সফলভাবে সম্পন্ন হয়েছে এবং <span style="color: #0055ff; font-weight: 800;">Rootva</span> অ্যাকাউন্ট ভেরিফাই হয়েছে। এখন থেকে আপনি সকল <span style="color: #0055ff; font-weight: 800;">প্রিমিয়াম সুবিধা</span> উপভোগ করতে পারবেন।
                </p>
            </div>

            <!-- 4 Icons Section -->
            <div style="background: linear-gradient(to right, #002288, #0044cc); border-radius: 16px; padding: 15px 10px; margin-bottom: 25px; display: flex; justify-content: space-around; box-shadow: 0 6px 15px rgba(0,34,136,0.3);">
                <div style="text-align: center; color: white; width: 25%;">
                    <div style="background: rgba(255,255,255,0.15); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px;">
                        <i class="fa-solid fa-shield-halved" style="font-size: 18px; color: #99ccff;"></i>
                    </div>
                    <div style="font-size: 10px; font-weight: bold; line-height: 1.3;">একাউন্ট<br>নিরাপদ</div>
                </div>
                <div style="text-align: center; color: white; width: 25%;">
                    <div style="background: rgba(255,255,255,0.15); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px;">
                        <i class="fa-solid fa-medal" style="font-size: 18px; color: #ffd700;"></i>
                    </div>
                    <div style="font-size: 10px; font-weight: bold; line-height: 1.3;">প্রিমিয়াম<br>সুবিধা</div>
                </div>
                <div style="text-align: center; color: white; width: 25%;">
                    <div style="background: rgba(255,255,255,0.15); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px;">
                        <i class="fa-solid fa-gift" style="font-size: 18px; color: #66d9ff;"></i>
                    </div>
                    <div style="font-size: 10px; font-weight: bold; line-height: 1.3;">এক্সক্লুসিভ<br>অফার</div>
                </div>
                <div style="text-align: center; color: white; width: 25%;">
                    <div style="background: rgba(255,255,255,0.15); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px;">
                        <i class="fa-solid fa-bolt" style="font-size: 18px; color: #ffcc00;"></i>
                    </div>
                    <div style="font-size: 10px; font-weight: bold; line-height: 1.3;">দ্রুত<br>সাপোর্ট</div>
                </div>
            </div>

            <!-- Bottom Button -->
            <div style="background: linear-gradient(to right, #002288, #001155); color: #ffffff; border-radius: 30px; padding: 10px 40px; font-weight: 900; font-size: 20px; display: inline-block; box-shadow: 0 4px 10px rgba(0, 17, 85, 0.4); border: 2px solid #ffffff;">
                ★ ধন্যবাদ ★
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
@if($user->is_verified == 1)
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    function downloadCard(btn) {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Generating...';
        btn.disabled = true;

        html2canvas(document.getElementById('verified-card'), {
            useCORS: true,
            scale: 3, // Higher scale for better image quality
            backgroundColor: null // Transparent background to keep rounded corners
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'verified-card-{{ $user->referCode }}.png';
            link.href = canvas.toDataURL('image/png');
            link.click();

            btn.innerHTML = originalText;
            btn.disabled = false;
        }).catch(err => {
            console.error('Error generating card:', err);
            alert('Could not generate card. Please try again.');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
</script>
@endif
@endsection