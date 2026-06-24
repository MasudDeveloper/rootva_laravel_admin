@extends('layouts.admin')

@section('title', 'Saved Push Notifications')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 fw-bold"><i class="fa-solid fa-bookmark text-primary me-2"></i>Saved Notifications (Templates)</h4>
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
                <i class="fa-solid fa-plus me-2"></i>Create New Template
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3 small text-uppercase fw-bold text-muted border-0">Image</th>
                                    <th class="py-3 small text-uppercase fw-bold text-muted border-0">Title & Body</th>
                                    <th class="py-3 small text-uppercase fw-bold text-muted border-0">Created At</th>
                                    <th class="px-4 py-3 small text-uppercase fw-bold text-muted border-0 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($savedNotifications as $notif)
                                <tr>
                                    <td class="px-4">
                                        @if($notif->image)
                                            <img src="{{ $notif->image }}" alt="Banner" class="img-thumbnail" style="height: 50px; width: 50px; object-fit: cover; border-radius: 8px;">
                                        @else
                                            <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height: 50px; width: 50px; border-radius: 8px; border: 1px dashed #ccc;">
                                                <i class="fa-solid fa-image"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold mb-1">{{ $notif->title }}</div>
                                        <div class="small text-muted" style="max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            {{ $notif->body }}
                                        </div>
                                        @if($notif->link)
                                            <div class="small mt-1"><a href="{{ $notif->link }}" target="_blank" class="text-decoration-none"><i class="fa-solid fa-link me-1"></i>{{ Str::limit($notif->link, 30) }}</a></div>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $notif->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="px-4 text-end">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-success shadow-sm" data-id="{{ $notif->id }}" data-title="{{ $notif->title }}" onclick="openSendModal(this.getAttribute('data-id'), this.getAttribute('data-title'))">
                                                <i class="fa-solid fa-paper-plane me-1"></i> Send
                                            </button>
                                            <form action="{{ route('admin.notifications.saved.destroy', $notif->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted small">
                                        <div class="mb-3"><i class="fa-solid fa-folder-open fa-3x text-light"></i></div>
                                        No saved notification templates found. Create one to get started.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($savedNotifications->hasPages())
                    <div class="p-3 border-top">
                        {{ $savedNotifications->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Template Modal -->
<div class="modal fade" id="createTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-bookmark me-2"></i>Save New Template</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.notifications.saved.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Notification Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. নতুন অফার!" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Message Body <span class="text-danger">*</span></label>
                        <textarea name="body" class="form-control" rows="4" placeholder="আপনার বার্তাটি লিখুন..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Action Link / Meeting Link (Optional)</label>
                        <input type="url" name="link" class="form-control" placeholder="https://...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Notification Banner Image (Optional)</label>
                        <div class="card bg-light border-0 p-3">
                            <nav>
                                <div class="nav nav-pills nav-fill mb-2" id="nav-tab" role="tablist">
                                    <button class="nav-link active btn-sm small py-1" id="nav-upload-tab" data-bs-toggle="tab" data-bs-target="#nav-upload" type="button" role="tab" aria-controls="nav-upload" aria-selected="true">Upload File</button>
                                    <button class="nav-link btn-sm small py-1" id="nav-url-tab" data-bs-toggle="tab" data-bs-target="#nav-url" type="button" role="tab" aria-controls="nav-url" aria-selected="false">Image URL</button>
                                </div>
                            </nav>
                            <div class="tab-content" id="nav-tabContent">
                                <div class="tab-pane fade show active" id="nav-upload" role="tabpanel">
                                    <input type="file" name="image" class="form-control form-control-sm" accept="image/*" id="imageFileInput" onchange="previewImage(this, 'file')">
                                </div>
                                <div class="tab-pane fade" id="nav-url" role="tabpanel">
                                    <input type="url" name="image_url_input" class="form-control form-control-sm" placeholder="https://example.com/image.jpg" id="imageUrlInput" onchange="previewImage(this, 'url')">
                                </div>
                            </div>
                            <div id="imagePreviewContainer" class="mt-2 text-center" style="display: none;">
                                <img id="imagePreview" src="" class="img-thumbnail" style="max-height: 100px;">
                                <button type="button" class="btn btn-sm btn-outline-danger border-0 mt-1 d-block mx-auto" onclick="removePreview()"><i class="fa-solid fa-trash me-1"></i>Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Send Notification Modal -->
<div class="modal fade" id="sendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-paper-plane me-2"></i>Send Notification</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-success bg-success bg-opacity-10 border-0 mb-4">
                        <strong>Template:</strong> <span id="sendTemplateTitle"></span>
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-block fw-bold mb-3">Select Target Audience</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="target" id="targetAll" value="all" checked onclick="toggleReferInput()">
                            <label class="form-check-label" for="targetAll">All Users</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="target" id="targetSpecific" value="specific" onclick="toggleReferInput()">
                            <label class="form-check-label" for="targetSpecific">Specific Refer Code</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="target" id="targetVerified" value="verified" onclick="toggleReferInput()">
                            <label class="form-check-label" for="targetVerified">Verified Users</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="target" id="targetUnverified" value="unverified" onclick="toggleReferInput()">
                            <label class="form-check-label" for="targetUnverified">Unverified Users</label>
                        </div>
                    </div>

                    <div class="mb-3" id="referCodeDiv" style="display: none;">
                        <label class="form-label fw-bold small">Refer Code <span class="text-danger">*</span></label>
                        <input type="text" name="referCode" class="form-control" placeholder="Enter User Refer Code" id="referCodeInput">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm" onclick="return confirm('Are you sure you want to send this notification now?');">
                        <i class="fa-solid fa-rocket me-2"></i>Send Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function previewImage(input, type) {
        const previewContainer = document.getElementById('imagePreviewContainer');
        const previewImage = document.getElementById('imagePreview');
        
        if (type === 'file') {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
                document.getElementById('imageUrlInput').value = '';
            }
        } else if (type === 'url') {
            const url = input.value.trim();
            if (url) {
                previewImage.src = url;
                previewContainer.style.display = 'block';
                document.getElementById('imageFileInput').value = '';
            } else {
                previewContainer.style.display = 'none';
            }
        }
    }

    function removePreview() {
        document.getElementById('imagePreviewContainer').style.display = 'none';
        document.getElementById('imagePreview').src = '';
        document.getElementById('imageFileInput').value = '';
        document.getElementById('imageUrlInput').value = '';
    }

    function toggleReferInput() {
        const specificRadio = document.getElementById('targetSpecific');
        const referInputDiv = document.getElementById('referCodeDiv');
        const referInput = document.getElementById('referCodeInput');
        
        if (specificRadio.checked) {
            referInputDiv.style.display = 'block';
            referInput.required = true;
        } else {
            referInputDiv.style.display = 'none';
            referInput.required = false;
        }
    }

    function openSendModal(id, title) {
        const modal = new bootstrap.Modal(document.getElementById('sendModal'));
        document.getElementById('sendTemplateTitle').innerText = title;
        document.getElementById('sendForm').action = `/admin/services/notifications/saved/${id}/send`;
        
        // Reset form to default
        document.getElementById('targetAll').checked = true;
        toggleReferInput();
        
        modal.show();
    }
</script>
@endsection
