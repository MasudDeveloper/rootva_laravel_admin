@extends('layouts.admin')

@section('title', 'Add New Microjob')
@section('page_title', 'Create Microtask')

@section('content')
<div class="fade-in">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card-modern">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0">Job Details</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.microjobs.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        @if(session('error'))
                            <div class="alert alert-danger border-0 shadow-sm rounded-pill px-4 mb-4 small">
                                <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
                            </div>
                        @endif
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Job Title</label>
                            <input type="text" name="title" class="form-control rounded-pill px-4 @error('title') is-invalid @enderror" 
                                   placeholder="e.g. Subscribe to YouTube Channel" value="{{ old('title') }}" required>
                            @error('title')
                                <div class="invalid-feedback ml-3">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Description / Instructions</label>
                            <textarea name="description" class="form-control rounded-4 px-4 py-3 @error('description') is-invalid @enderror" 
                                      rows="5" placeholder="Step by step instructions for the worker..." required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback ml-3">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Amount per Worker (৳)</label>
                                <input type="number" step="0.01" name="amount_per_worker" class="form-control rounded-pill px-4 @error('amount_per_worker') is-invalid @enderror" 
                                       placeholder="0.00" value="{{ old('amount_per_worker') }}" required>
                                @error('amount_per_worker')
                                    <div class="invalid-feedback ml-3">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Total Workers (Target)</label>
                                <input type="number" name="total_target" class="form-control rounded-pill px-4 @error('total_target') is-invalid @enderror" 
                                       placeholder="100" value="{{ old('total_target') }}" required>
                                @error('total_target')
                                    <div class="invalid-feedback ml-3">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Job URL</label>
                            <input type="url" name="job_url" class="form-control rounded-pill px-4 @error('job_url') is-invalid @enderror" 
                                   placeholder="https://example.com/job-link" value="{{ old('job_url') }}" required>
                            @error('job_url')
                                <div class="invalid-feedback ml-3">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Job Image (Optional)</label>
                            <input type="file" name="image" class="form-control rounded-pill px-4 @error('image') is-invalid @enderror">
                            <div class="form-text text-muted small ml-3">Recommended size: 600x400. Formats: JPG, PNG, GIF.</div>
                            @error('image')
                                <div class="invalid-feedback ml-3">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm">
                                <i class="fa-solid fa-cloud-arrow-up me-2"></i>Publish Job
                            </button>
                            <a href="{{ route('admin.microjobs.index') }}" class="btn btn-light rounded-pill px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
