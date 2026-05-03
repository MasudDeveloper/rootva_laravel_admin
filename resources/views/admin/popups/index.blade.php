@extends('layouts.admin')

@section('title', 'Popup Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Upload Section -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-success text-white p-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-cloud-arrow-up me-2"></i>Upload Popup Banner</h5>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('admin.popups.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Popup Image (Required)</label>
                            <div class="input-group">
                                <input type="file" name="image" class="form-control" accept="image/*" required>
                            </div>
                            <div class="form-text small">Recommended size: 800x800 or similar square.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Display Message</label>
                            <textarea name="message" class="form-control" rows="3" placeholder="Enter message to display below image..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Button Text (Optional)</label>
                            <input type="text" name="button_text" class="form-control" placeholder="e.g. Visit Now">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small">Button URL (Optional)</label>
                            <input type="url" name="button_url" class="form-control" placeholder="https://example.com">
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm">
                            <i class="fa-solid fa-plus me-2"></i>Create Popup
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- List Section -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Current Active Popups</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        @forelse($popups as $popup)
                        <div class="col-md-6">
                            <div class="card h-100 border rounded-4 overflow-hidden shadow-sm hover-shadow transition">
                                <div class="position-relative">
                                    <img src="{{ $popup->image_url }}" class="card-img-top" alt="Popup" style="height: 220px; object-fit: cover;">
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <form action="{{ route('admin.popups.destroy', $popup->id) }}" method="POST" onsubmit="return confirm('Delete this popup?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 shadow">
                                                <i class="fa-solid fa-trash me-1"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="small fw-bold text-dark mb-2">{{ $popup->message }}</p>
                                    @if($popup->button_text)
                                        <a href="{{ $popup->button_url }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="fa-solid fa-link me-1"></i>{{ $popup->button_text }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12 text-center py-5">
                            <div class="text-muted mb-3"><i class="fa-solid fa-images fa-3x opacity-25"></i></div>
                            <p class="text-muted">No popup banners found. Upload one to get started.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .transition {
        transition: all 0.3s ease;
    }
</style>
@endsection
