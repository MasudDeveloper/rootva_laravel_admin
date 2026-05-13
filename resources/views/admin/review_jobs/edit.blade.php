@extends('layouts.admin')

@section('title', 'Edit Review Job')
@section('page_title', 'Update Review Task')

@section('content')
<div class="fade-in">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card-modern">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0">Edit Job: #{{ $job->id }}</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.review-jobs.update', $job->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        
                        @if(session('error'))
                            <div class="alert alert-danger border-0 shadow-sm rounded-pill px-4 mb-4 small">
                                <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
                            </div>
                        @endif
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Job Title</label>
                            <input type="text" name="title" class="form-control rounded-pill px-4 @error('title') is-invalid @enderror" 
                                   placeholder="e.g. Google Maps Review" value="{{ old('title', $job->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback ml-3">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Description / Instructions</label>
                            <textarea name="description" class="form-control rounded-4 px-4 py-3 @error('description') is-invalid @enderror" 
                                      rows="5" placeholder="Step by step instructions for the reviewer..." required>{{ old('description', $job->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback ml-3">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Amount per Reviewer (৳)</label>
                                <input type="number" step="0.01" name="amount_per_worker" class="form-control rounded-pill px-4 @error('amount_per_worker') is-invalid @enderror" 
                                       placeholder="0.00" value="{{ old('amount_per_worker', $job->amount_per_worker) }}" required>
                                @error('amount_per_worker')
                                    <div class="invalid-feedback ml-3">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Total Reviewers (Target)</label>
                                <input type="number" name="total_target" class="form-control rounded-pill px-4 @error('total_target') is-invalid @enderror" 
                                       placeholder="100" value="{{ old('total_target', $job->total_target) }}" required>
                                @error('total_target')
                                    <div class="invalid-feedback ml-3">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Review URL (Link to Review Page)</label>
                            <input type="url" name="review_url" class="form-control rounded-pill px-4 @error('review_url') is-invalid @enderror" 
                                   placeholder="https://maps.app.goo.gl/..." value="{{ old('review_url', $job->review_url) }}" required>
                            @error('review_url')
                                <div class="invalid-feedback ml-3">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Schedule Time (Optional)</label>
                            <input type="datetime-local" name="scheduled_at" class="form-control rounded-pill px-4 @error('scheduled_at') is-invalid @enderror" 
                                   value="{{ old('scheduled_at', $job->scheduled_at ? date('Y-m-d\TH:i', strtotime($job->scheduled_at)) : '') }}">
                            @error('scheduled_at')
                                <div class="invalid-feedback ml-3">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Current Image</label>
                            <div class="mb-2">
                                @if($job->image_url)
                                    <img src="{{ $job->full_image_url }}" alt="Job Image" class="img-thumbnail rounded" style="max-height: 150px;">
                                @else
                                    <span class="text-muted small">No image uploaded</span>
                                @endif
                            </div>
                            <label class="form-label fw-bold">Change Image (Optional)</label>
                            <input type="file" name="image" class="form-control rounded-pill px-4 @error('image') is-invalid @enderror">
                            <div class="form-text text-muted small ml-3">Recommended size: 600x400. Formats: JPG, PNG, GIF.</div>
                            @error('image')
                                <div class="invalid-feedback ml-3">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-success rounded-pill px-5 shadow-sm text-white">
                                <i class="fa-solid fa-check me-2"></i>Update Review Job
                            </button>
                            <a href="{{ route('admin.review-jobs.index') }}" class="btn btn-light rounded-pill px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
