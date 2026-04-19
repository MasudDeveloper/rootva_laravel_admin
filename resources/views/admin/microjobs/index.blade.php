@extends('layouts.admin')

@section('title', 'Microjob Posts')
@section('page_title', 'Microjob Moderation')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">
                @if($status === 'pending') Pending @elseif($status === 'approved') Approved @else Rejected @endif
                Jobs: {{ $jobs->total() }}
            </h4>
            <p class="text-muted small mb-0">Review and approve user-submitted microtasks</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.microjobs.index', ['status' => 'pending']) }}"
               class="btn btn-{{ $status === 'pending' ? 'warning' : 'light' }} rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-clock me-2"></i>Pending
            </a>
            <a href="{{ route('admin.microjobs.index', ['status' => 'approved']) }}"
               class="btn btn-{{ $status === 'approved' ? 'success' : 'light' }} rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-check me-2"></i>Approved
            </a>
            <a href="{{ route('admin.microjobs.index', ['status' => 'rejected']) }}"
               class="btn btn-{{ $status === 'rejected' ? 'danger' : 'light' }} rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-xmark me-2"></i>Rejected
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <!-- Jobs Table -->
    <div class="card-modern p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-center">Image</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Job / Poster</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Target</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-end">Budget</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                    <tr>
                        <td class="px-4 py-3 text-center">
                            @if($job->image_url)
                                <img src="https://admin.rootvabd.com/service/microjobs/microjobImage/{{ $job->image_url }}" 
                                     class="rounded shadow-sm" style="width: 60px; height: 60px; object-fit: cover;">
                            @else
                                <div class="bg-light text-muted rounded d-flex align-items-center justify-content-center mx-auto" 
                                     style="width: 60px; height: 60px;">
                                    <i class="fa-solid fa-image fa-lg"></i>
                                </div>
                            @endif
                        </td>
                        <td class="py-3">
                            <div class="fw-bold">{{ $job->title }}</div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="text-muted small">By: {{ $job->name }}</span>
                                <span class="badge bg-primary-soft text-primary extra-small rounded-pill">#{{ $job->referCode }}</span>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="fw-medium text-dark">{{ $job->total_target }} Slots</div>
                            <a href="{{ $job->job_url }}" target="_blank" class="text-primary small text-decoration-none">
                                <i class="fa-solid fa-link me-1"></i>View Link
                            </a>
                        </td>
                        <td class="py-3 text-end fw-bold text-success">
                            ৳{{ number_format($job->total_amount, 2) }}
                        </td>
                        <td class="px-4 py-3 text-end">
                            @if($job->status === 'pending')
                            <div class="d-flex justify-content-end gap-2">
                                <form action="{{ route('admin.microjobs.update', $job->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="approved">
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" onclick="return confirm('Approve this job?')">
                                        <i class="fa-solid fa-check me-1"></i>Approve
                                    </button>
                                </form>
                                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" 
                                        onclick="rejectJob({{ $job->id }})">
                                    <i class="fa-solid fa-xmark me-1"></i>Reject
                                </button>
                                
                                <form id="reject-form-{{ $job->id }}" action="{{ route('admin.microjobs.update', $job->id) }}" method="POST" style="display: none;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="rejected">
                                    <input type="hidden" name="reject_reason" id="reject-reason-{{ $job->id }}">
                                </form>
                            </div>
                            @elseif($job->status === 'approved')
                                <span class="badge bg-success-soft text-success rounded-pill px-3 py-2">
                                    <i class="fa-solid fa-check-circle me-1"></i>Approved
                                </span>
                            @else
                                <span class="badge bg-danger-soft text-danger rounded-pill px-3 py-2">
                                    <i class="fa-solid fa-ban me-1"></i>Rejected
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-briefcase fa-3x mb-3 d-block opacity-25"></i>
                            No {{ strtolower($status) }} microjobs found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($jobs->hasPages())
        <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
            {{ $jobs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function rejectJob(id) {
        Swal.fire({
            title: 'Reject Microjob',
            text: 'Please provide a reason for rejection (this will be visible to the user):',
            input: 'text',
            inputPlaceholder: 'e.g. Invalid job link or inappropriate content',
            showCancelButton: true,
            confirmButtonText: 'Confirm Reject',
            confirmButtonColor: '#ef4444',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value) {
                    return 'A rejection reason is required!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('reject-reason-' + id).value = result.value;
                document.getElementById('reject-form-' + id).submit();
            }
        });
    }
</script>
@endsection
