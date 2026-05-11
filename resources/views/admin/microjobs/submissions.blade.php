@extends('layouts.admin')

@section('title', 'Microjob Submissions')
@section('page_title', 'Job Submissions: ' . $job->title)

@section('styles')
<style>
    .img-hover-zoom {
        transition: transform .3s ease, box-shadow .3s ease;
        cursor: pointer;
    }
    .img-hover-zoom:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 20px rgba(0,0,0,0.12) !important;
    }
</style>
@endsection

@section('content')
<div class="fade-in">
    <div class="card-modern p-0 overflow-hidden">
        <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center bg-gray-50">
            <div>
                <h5 class="fw-bold mb-0">Total Submissions: {{ $submissions->total() }}</h5>
                <p class="text-muted small mb-0">Review and approve worker submissions for this job</p>
            </div>
            <a href="{{ route('admin.microjobs.index') }}" class="btn btn-light rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-arrow-left me-2"></i>Back to Jobs
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mx-4 mt-4">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm rounded-pill px-4 mx-4 mt-4">
                <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Worker</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Proof Details</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Status</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $sub)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="fw-bold">{{ $sub->user->name ?? 'Unknown' }}</div>
                            <div class="text-muted extra-small">ID: #{{ $sub->worker_user_id }}</div>
                        </td>
                        <td class="py-3">
                            <div class="d-flex align-items-start gap-3">
                                @if($sub->proof_image_full_url)
                                    <a href="javascript:void(0)" onclick="showImage('{{ $sub->proof_image_full_url }}')">
                                        <img src="{{ $sub->proof_image_full_url }}" 
                                             class="rounded border shadow-sm img-hover-zoom" style="width: 80px; height: 60px; object-fit: cover;">
                                    </a>
                                @else
                                    <div class="bg-light text-muted rounded d-flex align-items-center justify-content-center" 
                                         style="width: 80px; height: 60px;">
                                        <i class="fa-solid fa-image-slash"></i>
                                    </div>
                                @endif
                                <div class="flex-grow-1">
                                    <div class="small text-dark fw-medium">{{ $sub->proof_message }}</div>
                                    <div class="extra-small text-muted mt-1">Submitted: {{ $sub->created_at }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 text-center">
                            @if($sub->status === 'pending')
                                <span class="badge bg-warning-soft text-warning rounded-pill px-3 py-2">Pending</span>
                            @elseif($sub->status === 'approved')
                                <span class="badge bg-success-soft text-success rounded-pill px-3 py-2">Approved</span>
                            @else
                                <span class="badge bg-danger-soft text-danger rounded-pill px-3 py-2" title="{{ $sub->reject_reason }}">Rejected</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            @if($sub->status === 'pending')
                            <div class="d-flex justify-content-end gap-2">
                                <form action="{{ route('admin.microjobs.submissions.approve', $sub->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" onclick="return confirm('Approve this submission?')">
                                        <i class="fa-solid fa-check me-1"></i>Approve
                                    </button>
                                </form>
                                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" onclick="rejectSubmission({{ $sub->id }})">
                                    <i class="fa-solid fa-xmark me-1"></i>Reject
                                </button>
                                
                                <form id="reject-sub-form-{{ $sub->id }}" action="{{ route('admin.microjobs.submissions.reject', $sub->id) }}" method="POST" style="display: none;">
                                    @csrf
                                    <input type="hidden" name="reason" id="reject-sub-reason-{{ $sub->id }}">
                                </form>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            No submissions found for this job.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($submissions->hasPages())
        <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
            {{ $submissions->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg bg-transparent">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 shadow-none" data-bs-dismiss="modal" aria-label="Close" style="z-index: 1060;"></button>
                <img src="" id="modalImage" class="img-fluid rounded shadow-lg" style="max-height: 85vh; cursor: pointer;" data-bs-dismiss="modal">
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function showImage(url) {
        document.getElementById('modalImage').src = url;
        var myModal = new bootstrap.Modal(document.getElementById('imageModal'));
        myModal.show();
    }

    function rejectSubmission(id) {
        Swal.fire({
            title: 'Reject Submission',
            text: 'Please provide a reason for rejection:',
            input: 'text',
            inputPlaceholder: 'e.g. Invalid proof image or incomplete task',
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
                document.getElementById('reject-sub-reason-' + id).value = result.value;
                document.getElementById('reject-sub-form-' + id).submit();
            }
        });
    }
</script>
@endsection
