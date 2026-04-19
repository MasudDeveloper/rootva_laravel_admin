@extends('layouts.admin')

@section('title', 'Job Submissions')
@section('page_title', 'Reviewing: ' . $job->title)

@section('content')
<div class="fade-in">
    <div class="mb-4">
        <a href="{{ route('admin.review-jobs.index') }}" class="btn btn-light rounded-pill shadow-sm">
            <i class="fa-solid fa-arrow-left me-2"></i>Back to Jobs
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="card-modern p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Worker</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Proof Details</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Proof Image</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Status</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $sub)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="fw-bold text-dark">{{ $sub->user->name ?? 'Unknown User' }}</div>
                            <div class="extra-small text-muted">{{ $sub->user->number ?? '' }}</div>
                        </td>
                        <td class="py-3">
                            <div class="small fw-medium mb-1">Refer ID: {{ $sub->refer_id }}</div>
                            <div class="extra-small text-muted text-wrap" style="max-width: 250px;">{{ $sub->proof_message }}</div>
                        </td>
                        <td class="py-3 text-center">
                            @if($sub->proof_image_url)
                                <a href="javascript:void(0)" onclick="openImage('https://api.rootvabd.com/{{ $sub->proof_image_url }}', 'Proof by {{ $sub->user->name ?? 'User' }}')">
                                    <img src="https://api.rootvabd.com/{{ $sub->proof_image_url }}" 
                                         class="rounded shadow-sm border p-1" style="height: 60px; object-fit: cover;">
                                </a>
                            @else
                                <span class="text-muted small italic">No image proof</span>
                            @endif
                        </td>
                        <td class="py-3 text-center text-capitalize">
                            @if($sub->status == 'approved')
                                <span class="badge bg-success-soft text-success rounded-pill px-3">Approved</span>
                            @elseif($sub->status == 'pending')
                                <span class="badge bg-warning-soft text-warning rounded-pill px-3">Pending</span>
                            @else
                                <span class="badge bg-danger-soft text-danger rounded-pill px-3">{{ $sub->status }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            @if($sub->status == 'pending')
                                <div class="d-flex gap-2 justify-content-end">
                                    <form action="{{ route('admin.review-jobs.approve', $sub->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm"
                                                onclick="return confirm('Approve this submission? Worker will be paid.')">
                                            Approve
                                        </button>
                                    </form>
                                    <button class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" 
                                            data-bs-toggle="modal" data-bs-target="#rejectModal{{ $sub->id }}">
                                        Reject
                                    </button>
                                </div>

                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal{{ $sub->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered text-start">
                                        <form class="modal-content border-0 shadow rounded-4" action="{{ route('admin.review-jobs.reject', $sub->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="fw-bold">Reject Submission</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4 text-center">
                                                <i class="fa-solid fa-triangle-exclamation text-danger fa-3x mb-3"></i>
                                                <p class="mb-4 text-muted">Are you sure you want to reject this submission? In the legacy system, this will delete the submission record.</p>
                                                <textarea name="reason" class="form-control" rows="3" placeholder="Reason for rejection (Optional)" required></textarea>
                                            </div>
                                            <div class="modal-footer border-0 p-4 pt-0">
                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger rounded-pill px-4 shadow-sm">Confirm Reject</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @elseif($sub->status == 'approved')
                                <span class="text-muted extra-small">Paid on: {{ $sub->updated_at ?? 'N/A' }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No submissions found for this job.</td>
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

<!-- Image View Modal -->
<div class="modal fade" id="imageViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <img id="fullProofImage" src="" class="img-fluid rounded-4 shadow-lg">
                <div class="mt-3 text-white small" id="imageCaption"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function openImage(src, caption) {
    document.getElementById('fullProofImage').src = src;
    document.getElementById('imageCaption').innerText = caption;
    new bootstrap.Modal(document.getElementById('imageViewModal')).show();
}
</script>
@endsection
