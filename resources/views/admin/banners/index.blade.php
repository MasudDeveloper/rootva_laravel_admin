@extends('layouts.admin')

@section('title', 'Banner Manager')
@section('page_title', 'App Banner Management')

@section('content')
<div class="fade-in">
    <div class="row g-4">
        <!-- Upload Form -->
        <div class="col-md-4">
            <div class="card-modern">
                <h5 class="fw-bold mb-4">Upload New Banner</h5>
                <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase fw-bold">Banner Image</label>
                        <input type="file" name="banner" class="form-control" accept="image/*" required>
                        <div class="extra-small text-muted mt-1">Recommended size: 1200x600px</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small text-muted text-uppercase fw-bold">Redirect URL (Optional)</label>
                        <input type="url" name="redirect_url" class="form-control" placeholder="https://example.com/promo">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill shadow-sm">
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i>Upload Banner
                    </button>
                </form>
            </div>
        </div>

        <!-- Banners List -->
        <div class="col-md-8">
            <div class="card-modern">
                <h5 class="fw-bold mb-4">Active Banners ({{ $banners->count() }})</h5>
                
                @if(session('success'))
                    <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
                        <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                    </div>
                @endif

                <div class="row g-3">
                    @forelse($banners as $banner)
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                            <div class="position-relative">
                                <img src="{{ $banner->image_url }}" class="card-img-top" style="height: 160px; object-fit: cover;">
                                <div class="position-absolute top-0 end-0 p-2">
                                    <form action="{{ route('admin.banners.destroy', $banner->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm rounded-circle shadow" 
                                                onclick="return confirm('Are you sure you want to delete this banner?')"
                                                style="width: 32px; height: 32px; padding: 0;">
                                            <i class="fa-solid fa-trash-can small"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                @if($banner->redirect_url)
                                    <div class="d-flex align-items-center text-primary small">
                                        <i class="fa-solid fa-link me-2"></i>
                                        <span class="text-truncate">{{ $banner->redirect_url }}</span>
                                    </div>
                                @else
                                    <span class="text-muted extra-small">No redirect URL set</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="fa-solid fa-images fa-3x mb-3 d-block opacity-25"></i>
                        No banners uploaded yet.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
