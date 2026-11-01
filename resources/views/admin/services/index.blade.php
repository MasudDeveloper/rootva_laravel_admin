@extends('layouts.admin')

@section('title', 'Online Services')
@section('page_title', 'Managed Services')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Total Services: {{ $services->total() }}</h4>
            <p class="text-muted small mb-0">Manage premium digital services for users</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            <i class="fa-solid fa-plus me-2"></i>Add New Service
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="row g-4">
        @forelse($services as $service)
        <div class="col-md-4">
            <div class="card-modern h-100 border-0 shadow-sm overflow-hidden d-flex flex-column">
                <div class="p-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-4 overflow-hidden shadow-sm" style="width: 80px; height: 80px; flex-shrink: 0;">
                            <img src="{{ $service->image_url }}" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($service->name) }}&background=random'">
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <h6 class="fw-bold text-dark mb-1 text-truncate">{{ $service->name }}</h6>
                            <p class="text-muted extra-small mb-2 text-truncate">{{ $service->description }}</p>
                            <div class="d-flex gap-2">
                                <span class="badge bg-light text-dark shadow-sm extra-small">Cost: ৳{{ number_format($service->price, 0) }}</span>
                                <span class="badge bg-success-soft text-success shadow-sm extra-small">Resell: ৳{{ number_format($service->resell_price, 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-light border-top mt-auto d-flex justify-content-between align-items-center">
                    <a href="{{ $service->buylink }}" target="_blank" class="btn btn-white btn-sm px-3 rounded-pill shadow-sm border small">
                        <i class="fa-solid fa-link me-1"></i>View Link
                    </a>
                    <div class="d-flex gap-2">
                        <button onclick="editService({{ $service->id }}, '{{ addslashes($service->name) }}', '{{ addslashes($service->description) }}', '{{ $service->image_url }}', '{{ $service->price }}', '{{ $service->resell_price }}', '{{ $service->buylink }}')" class="btn btn-primary btn-sm rounded-circle shadow-sm">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form action="{{ route('admin.online-services.destroy', $service->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm rounded-circle shadow-sm" onclick="return confirm('Delete this service?')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <i class="fa-solid fa-globe fa-4x mb-3 d-block opacity-25"></i>
            <h5 class="text-muted">No services found.</h5>
        </div>
        @endforelse
    </div>

    @if($services->hasPages())
    <div class="mt-4 d-flex justify-content-center">
        {{ $services->links() }}
    </div>
    @endif
</div>

{{-- Add Service Modal --}}
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">নতুন অনলাইন সেবা যুক্ত করুন</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.online-services.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">সার্ভিসের নাম <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control rounded-3 shadow-sm" placeholder="যেমন: Canva Pro, Netflix, ইত্যাদি" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">সার্ভিস বিবরণ (Description)</label>
                        <textarea name="description" class="form-control rounded-3 shadow-sm" rows="3" placeholder="সার্ভিস সম্পর্কে সংক্ষিপ্ত বিবরণ লিখুন..."></textarea>
                    </div>
                    
                    {{-- Image Upload and Preview --}}
                    <div class="card p-3 bg-light border-0 rounded-3 mb-3">
                        <label class="form-label small fw-bold mb-2 d-flex align-items-center justify-content-between">
                            <span><i class="fa-solid fa-image me-1 text-primary"></i>সার্ভিসের ছবি আপলোড করুন</span>
                            <span class="text-muted extra-small">JPG, PNG, WEBP (Max 5MB)</span>
                        </label>
                        <input type="file" name="image" id="add_service_image_file" class="form-control rounded-3 shadow-sm bg-white mb-2" accept="image/*" onchange="previewSelectedImage(this, 'add_image_preview', 'add_image_preview_box')">
                        
                        <div class="text-center my-1 text-muted small fw-semibold">— অথবা ছবি লিংক দিতে পারেন —</div>
                        <input type="url" name="image_url" id="add_service_image_url" class="form-control rounded-3 shadow-sm bg-white" placeholder="https://example.com/logo.png" oninput="previewUrlImage(this.value, 'add_image_preview', 'add_image_preview_box')">
                        
                        <div id="add_image_preview_box" class="mt-2 text-center" style="display: none;">
                            <img id="add_image_preview" src="" class="rounded-3 shadow-sm border" style="max-height: 90px; max-width: 140px; object-fit: contain;">
                            <div class="extra-small text-success mt-1"><i class="fa-solid fa-circle-check me-1"></i>ছবির প্রিভিউ</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">ক্রয় মূল্য / Cost Price (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" class="form-control rounded-3 shadow-sm" placeholder="0.00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">বিক্রয় মূল্য / Resell Price (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="resell_price" class="form-control rounded-3 shadow-sm" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label small fw-bold">বাই লিংক / ডেলিভারি ইনফো লিংক</label>
                        <input type="text" name="buylink" class="form-control rounded-3 shadow-sm" placeholder="https://wa.me/... অথবা যেকোনো লিংক">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fa-solid fa-save me-1"></i>সার্ভিস সেভ করুন</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Service Modal --}}
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">সার্ভিস এডিট করুন</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editServiceForm" action="" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">সার্ভিসের নাম <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control rounded-3 shadow-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">সার্ভিস বিবরণ</label>
                        <textarea name="description" id="edit_desc" class="form-control rounded-3 shadow-sm" rows="3"></textarea>
                    </div>

                    {{-- Image Upload and Preview for Edit --}}
                    <div class="card p-3 bg-light border-0 rounded-3 mb-3">
                        <label class="form-label small fw-bold mb-2 d-flex align-items-center justify-content-between">
                            <span><i class="fa-solid fa-image me-1 text-primary"></i>নতুন ছবি আপলোড করুন</span>
                            <span class="text-muted extra-small">ছবি পরিবর্তন করতে চাইলে সিলেক্ট করুন</span>
                        </label>
                        <input type="file" name="image" id="edit_service_image_file" class="form-control rounded-3 shadow-sm bg-white mb-2" accept="image/*" onchange="previewSelectedImage(this, 'edit_image_preview', 'edit_image_preview_box')">
                        
                        <div class="text-center my-1 text-muted small fw-semibold">— অথবা ছবির নতুন লিংক —</div>
                        <input type="text" name="image_url" id="edit_image" class="form-control rounded-3 shadow-sm bg-white" placeholder="https://example.com/logo.png" oninput="previewUrlImage(this.value, 'edit_image_preview', 'edit_image_preview_box')">
                        
                        <div id="edit_image_preview_box" class="mt-2 text-center">
                            <img id="edit_image_preview" src="" class="rounded-3 shadow-sm border" style="max-height: 90px; max-width: 140px; object-fit: contain;">
                            <div class="extra-small text-muted mt-1" id="edit_image_caption">বর্তমান ছবি</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">ক্রয় মূল্য (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" id="edit_price" class="form-control rounded-3 shadow-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">বিক্রয় মূল্য (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="resell_price" id="edit_resell" class="form-control rounded-3 shadow-sm" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label small fw-bold">বাই লিংক / ইনফো লিংক</label>
                        <input type="text" name="buylink" id="edit_buylink" class="form-control rounded-3 shadow-sm">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fa-solid fa-check me-1"></i>আপডেট করুন</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .extra-small { font-size: 0.75rem; }
    .card-modern { border-radius: 1.25rem; transition: transform 0.2s; }
    .card-modern:hover { transform: translateY(-5px); }
    .btn-white { background: #fff; color: #333; }
</style>

@section('scripts')
<script>
    function previewSelectedImage(input, previewId, boxId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(previewId);
                const box = document.getElementById(boxId);
                img.src = e.target.result;
                box.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function previewUrlImage(url, previewId, boxId) {
        if (url && url.trim().length > 5) {
            const img = document.getElementById(previewId);
            const box = document.getElementById(boxId);
            img.src = url.trim();
            box.style.display = 'block';
        }
    }

    function editService(id, name, desc, img, price, resell, link) {
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_desc').value = desc;
        document.getElementById('edit_image').value = img;
        document.getElementById('edit_price').value = price;
        document.getElementById('edit_resell').value = resell;
        document.getElementById('edit_buylink').value = link;
        document.getElementById('edit_service_image_file').value = '';
        
        const previewImg = document.getElementById('edit_image_preview');
        const previewBox = document.getElementById('edit_image_preview_box');
        const previewCap = document.getElementById('edit_image_caption');
        if (img) {
            previewImg.src = img;
            previewBox.style.display = 'block';
            previewCap.textContent = 'বর্তমান ছবি';
        } else {
            previewBox.style.display = 'none';
        }

        document.getElementById('editServiceForm').action = `{{ route('admin.online-services.index') }}/${id}`;
        new bootstrap.Modal(document.getElementById('editServiceModal')).show();
    }
</script>
@endsection
@endsection
