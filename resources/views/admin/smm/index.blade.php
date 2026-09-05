@extends('layouts.admin')

@section('title', 'SMM Submissions')
@section('page_title', 'SMM Work Verification')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">SMM Submissions</h4>
            <p class="text-muted small mb-0">Approve and verify Gmail, Facebook, Telegram, WhatsApp or Instagram selling submissions</p>
        </div>
        <div class="d-flex gap-3 align-items-center flex-wrap">
            <form action="{{ route('admin.smm.index') }}" method="GET" class="d-flex gap-2 flex-wrap" id="smmFilterForm">
                <input type="hidden" name="status" value="{{ $status }}">
                <select name="task_type" class="form-select rounded-pill border shadow-sm px-3 text-xs" style="width: auto;" onchange="this.form.submit()">
                    <option value="">All Tasks</option>
                    @foreach(['gmail', 'facebook_cookies', 'facebook_zero_friend', 'facebook_number_id', 'instagram_2fa', 'instagram_cookies', 'whatsapp', 'telegram'] as $type)
                        <option value="{{ $type }}" {{ request('task_type') == $type ? 'selected' : '' }}>{{ strtoupper(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
                
                <input type="date" name="start_date" class="form-control rounded-pill border shadow-sm px-3 text-xs" style="width: auto;" value="{{ request('start_date') }}" onchange="this.form.submit()" placeholder="Start Date">
                <input type="date" name="end_date" class="form-control rounded-pill border shadow-sm px-3 text-xs" style="width: auto;" value="{{ request('end_date') }}" onchange="this.form.submit()" placeholder="End Date">
                
                <input type="text" name="search" class="form-control rounded-pill border shadow-sm px-3 text-xs" style="width: auto; max-width: 180px;" value="{{ request('search') }}" placeholder="Search Phone/Refer/Name..." onkeypress="if(event.key === 'Enter') { this.form.submit(); }">
                
                @if(request('task_type') || request('start_date') || request('end_date') || request('search'))
                    <a href="{{ route('admin.smm.index', ['status' => $status]) }}" class="btn btn-outline-secondary btn-sm rounded-pill d-flex align-items-center px-3"><i class="fa-solid fa-xmark me-1"></i>Clear</a>
                @endif

                <button type="submit" name="export" value="csv" class="btn btn-success text-white rounded-pill px-4 shadow-sm text-xs fw-bold">
                    <i class="fa-solid fa-file-excel me-2"></i>Export CSV
                </button>
            </form>

            <div class="btn-group rounded-pill overflow-hidden shadow-sm">
                <a href="{{ route('admin.smm.index', array_merge(request()->all(), ['status' => 'pending'])) }}" class="btn {{ $status === 'pending' ? 'btn-primary' : 'btn-outline-primary' }} px-4">Pending</a>
                <a href="{{ route('admin.smm.index', array_merge(request()->all(), ['status' => 'approved'])) }}" class="btn {{ $status === 'approved' ? 'btn-primary' : 'btn-outline-primary' }} px-4">Approved</a>
                <a href="{{ route('admin.smm.index', array_merge(request()->all(), ['status' => 'rejected'])) }}" class="btn {{ $status === 'rejected' ? 'btn-primary' : 'btn-outline-primary' }} px-4">Rejected</a>
            </div>
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

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3">User Details</th>
                        <th class="py-3">Task Type</th>
                        <th class="py-3">Account Info (Field 1)</th>
                        <th class="py-3">Secret/Password (Field 2)</th>
                        <th class="py-3">Payout Rate</th>
                        <th class="py-3">Submitted At</th>
                        @if($status === 'rejected')
                            <th class="py-3">Feedback</th>
                        @endif
                        @if($status === 'pending')
                            <th class="py-3 text-end px-4">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $sub)
                    <tr>
                        <td class="px-4">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">{{ $sub->user->name ?? 'Unknown User' }}</h6>
                                <span class="text-muted extra-small d-block"><i class="fa-solid fa-phone me-1"></i>{{ $sub->user->number ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary text-white rounded-pill px-3 py-1 uppercase">{{ strtoupper($sub->task_type) }}</span>
                        </td>
                        <td>
                            <code class="text-primary">{{ $sub->input_field_1 }}</code>
                        </td>
                        <td>
                            @if($sub->input_field_2)
                                <code class="text-danger">{{ $sub->input_field_2 }}</code>
                            @else
                                <span class="text-muted italic">N/A</span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold text-success">৳{{ number_format($sub->price, 2) }}</span>
                        </td>
                        <td>
                            <span class="text-muted small">{{ $sub->created_at->format('d M Y, h:i A') }}</span>
                        </td>
                        @if($status === 'rejected')
                            <td>
                                <span class="text-danger small">{{ $sub->admin_feedback ?: 'No reason specified' }}</span>
                            </td>
                        @endif
                        @if($status === 'pending')
                        <td class="text-end px-4">
                            <div class="d-flex justify-content-end gap-2">
                                <form action="{{ route('admin.smm.approve', $sub->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" onclick="return confirm('Approve submission and add ৳{{ $sub->price }} to user wallet?')">
                                        <i class="fa-solid fa-check me-1"></i>Approve
                                    </button>
                                </form>

                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $sub->id }}">
                                    <i class="fa-solid fa-xmark me-1"></i>Reject
                                </button>
                            </div>

                            <!-- Reject Feedback Modal -->
                            <div class="modal fade text-start" id="rejectModal-{{ $sub->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content rounded-4 border-0 shadow-lg">
                                        <form action="{{ route('admin.smm.reject', $sub->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="fw-bold">Reject SMM Submission</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body py-3">
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">Reason for Rejection</label>
                                                    <textarea name="feedback" class="form-control rounded-3" rows="3" placeholder="Enter reason (e.g. Incorrect credentials, already used account etc.)" required>Incorrect credentials or duplicate sell.</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pt-0">
                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger rounded-pill px-4">Confirm Reject</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fa-3x mb-3 d-block opacity-25"></i>
                            No SMM submissions found in this tab.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-3">
        {{ $submissions->links() }}
    </div>
</div>
@endsection
