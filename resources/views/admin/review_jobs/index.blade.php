@extends('layouts.admin')

@section('title', 'Review Jobs')
@section('page_title', 'Microtask Review Management')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Review Jobs</h4>
            <p class="text-muted small mb-0">Manage tasks where users provide reviews for rewards</p>
        </div>
        <a href="{{ route('admin.review-jobs.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="fa-solid fa-plus me-2"></i>Add New Review Job
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
        </div>
    @endif

    <div class="card-modern p-0 overflow-hidden">
        <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center bg-gray-50">
            <h5 class="fw-bold mb-0">Active Review Jobs</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Image</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Title</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Reward</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Progress</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Pending</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                    <tr>
                        <td class="px-4 py-3">
                            <img src="{{ $job->full_image_url }}" alt="Job" class="rounded shadow-sm" style="width: 50px; height: 35px; object-fit: cover;">
                        </td>
                        <td class="py-3">
                            <div class="fw-bold text-dark">{{ $job->title }}</div>
                            <div class="extra-small text-muted">ID: #{{ $job->id }} | Created: {{ $job->created_at }}</div>
                        </td>
                        <td class="py-3 text-center fw-bold text-success">
                            ৳{{ number_format($job->amount_per_worker, 2) }}
                        </td>
                        <td class="py-3 text-center">
                            <div class="small fw-bold">{{ $job->total_target - $job->remaining_target }} / {{ $job->total_target }}</div>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar bg-primary" role="progressbar" 
                                     style="width: {{ $job->total_target > 0 ? (($job->total_target - $job->remaining_target) / $job->total_target) * 100 : 0 }}%"></div>
                            </div>
                        </td>
                        <td class="py-3 text-center">
                            @if($job->submissions_count > 0)
                                <span class="badge bg-danger rounded-pill px-3">{{ $job->submissions_count }} Pending</span>
                            @else
                                <span class="badge bg-light text-muted rounded-pill px-3 border">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.review-jobs.submissions', $job->id) }}" class="btn btn-primary-soft btn-sm rounded-pill px-3 fw-bold" title="View Submissions">
                                    <i class="fa-solid fa-users me-1"></i> Submissions
                                </a>
                                <a href="{{ route('admin.review-jobs.edit', $job->id) }}" class="btn btn-light btn-sm rounded-circle shadow-sm p-2" title="Edit Job">
                                    <i class="fa-solid fa-pen-to-square text-primary"></i>
                                </a>
                                <form action="{{ route('admin.review-jobs.destroy', $job->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure? This will delete the job and all its submissions.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-light btn-sm rounded-circle shadow-sm p-2" title="Delete Job">
                                        <i class="fa-solid fa-trash text-danger"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No review jobs found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jobs->hasPages())
        <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
            {{ $jobs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
