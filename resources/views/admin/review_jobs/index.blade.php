@extends('layouts.admin')

@section('title', 'Review Jobs')
@section('page_title', 'Microtask Review Management')

@section('content')
<div class="fade-in">
    <div class="card-modern p-0 overflow-hidden">
        <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center bg-gray-50">
            <h5 class="fw-bold mb-0">Active Review Jobs</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Job ID</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Title</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Reward</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Pending Submissions</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                    <tr>
                        <td class="px-4 py-3 text-muted fw-medium">#{{ $job->id }}</td>
                        <td class="py-3">
                            <div class="fw-bold text-dark">{{ $job->title }}</div>
                            <div class="extra-small text-muted">Created: {{ $job->created_at }}</div>
                        </td>
                        <td class="py-3 text-center fw-bold text-success">
                            ৳{{ number_format($job->amount_per_worker, 2) }}
                        </td>
                        <td class="py-3 text-center">
                            @if($job->submissions_count > 0)
                                <span class="badge bg-danger rounded-pill px-3">{{ $job->submissions_count }} Pending</span>
                            @else
                                <span class="badge bg-light text-muted rounded-pill px-3 border">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <a href="{{ route('admin.review-jobs.submissions', $job->id) }}" class="btn btn-primary-soft btn-sm rounded-pill px-3 fw-bold">
                                View Submissions <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No review jobs found.</td>
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
