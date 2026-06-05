@extends('layouts.admin')

@section('title', 'Push Notifications')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-primary text-white p-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-paper-plane me-2"></i>Send New Notification</h5>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('admin.notifications.send') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Notification Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. নতুন অফার!" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Message Body</label>
                            <textarea name="body" class="form-control" rows="4" placeholder="আপনার বার্তাটি লিখুন..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label d-block fw-bold small">Target Audience</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="target" id="targetAll" value="all" checked onclick="toggleReferInput()">
                                <label class="form-check-label" for="targetAll">All Users</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="target" id="targetSpecific" value="specific" onclick="toggleReferInput()">
                                <label class="form-check-label" for="targetSpecific">Specific Refer Code</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="target" id="targetVerified" value="verified" onclick="toggleReferInput()">
                                <label class="form-check-label" for="targetVerified">Verified Users</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="target" id="targetUnverified" value="unverified" onclick="toggleReferInput()">
                                <label class="form-check-label" for="targetUnverified">Unverified Users</label>
                            </div>
                        </div>

                        <div class="mb-4" id="referCodeDiv" style="display: none;">
                            <label class="form-label fw-bold small">Refer Code</label>
                            <input type="text" name="referCode" class="form-control" placeholder="Enter User Refer Code">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Notification Banner Image (Optional)</label>
                            <div class="card bg-light border-0 mb-2 p-3">
                                <nav>
                                    <div class="nav nav-pills nav-fill mb-2" id="nav-tab" role="tablist">
                                        <button class="nav-link active btn-sm small py-1" id="nav-upload-tab" data-bs-toggle="tab" data-bs-target="#nav-upload" type="button" role="tab" aria-controls="nav-upload" aria-selected="true"><i class="fa-solid fa-cloud-arrow-up me-1"></i>Upload Image File</button>
                                        <button class="nav-link btn-sm small py-1" id="nav-url-tab" data-bs-toggle="tab" data-bs-target="#nav-url" type="button" role="tab" aria-controls="nav-url" aria-selected="false"><i class="fa-solid fa-link me-1"></i>Image URL Link</button>
                                    </div>
                                </nav>
                                <div class="tab-content" id="nav-tabContent">
                                    <div class="tab-pane fade show active" id="nav-upload" role="tabpanel" aria-labelledby="nav-upload-tab">
                                        <input type="file" name="image" class="form-control form-control-sm" accept="image/*" id="imageFileInput" onchange="previewImage(this, 'file')">
                                    </div>
                                    <div class="tab-pane fade" id="nav-url" role="tabpanel" aria-labelledby="nav-url-tab">
                                        <input type="url" name="image_url_input" class="form-control form-control-sm" placeholder="https://example.com/image.jpg" id="imageUrlInput" onchange="previewImage(this, 'url')">
                                    </div>
                                </div>
                                <div id="imagePreviewContainer" class="mt-2 text-center" style="display: none;">
                                    <img id="imagePreview" src="" class="img-thumbnail" style="max-height: 120px;">
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0 mt-1 d-block mx-auto" onclick="removePreview()"><i class="fa-solid fa-trash me-1"></i>Remove Image</button>
                                </div>
                            </div>
                        </div>

                        <!-- Image Guidelines Box -->
                        <div class="alert alert-info border-0 shadow-sm rounded-3 p-3 mb-4">
                            <h6 class="fw-bold alert-heading mb-2"><i class="fa-solid fa-circle-info me-2"></i>Image Guidelines</h6>
                            <ul class="mb-0 ps-3 small text-muted">
                                <li><strong>Aspect Ratio:</strong> Recommended 2:1 ratio (e.g. 1024x512px) for best fit in push banners.</li>
                                <li><strong>File Size:</strong> Maximum size allowed is 2 MB.</li>
                                <li><strong>Automatic Hosting:</strong> Uploading will save the file to this server and use the generated URL. Alternatively, paste any direct image link in the URL tab.</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                            <i class="fa-solid fa-rocket me-2"></i>Send Notification
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white p-3 border-bottom">
                    <h5 class="mb-0 fw-bold">Recent Sent History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3 small text-uppercase fw-bold text-muted border-0">User ID</th>
                                    <th class="py-3 small text-uppercase fw-bold text-muted border-0">Message</th>
                                    <th class="py-3 small text-uppercase fw-bold text-muted border-0">Sent At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($notifications as $notif)
                                <tr>
                                    <td class="px-4"><span class="badge bg-light text-dark border">#{{ $notif->user_id }}</span></td>
                                    <td class="small">{{ Str::limit($notif->message, 60) }}</td>
                                    <td class="small text-muted">{{ $notif->created_at }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted small">No notification history found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">
                        {{ $notifications->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleReferInput() {
        const specificRadio = document.getElementById('targetSpecific');
        const referInputDiv = document.getElementById('referCodeDiv');
        referInputDiv.style.display = specificRadio.checked ? 'block' : 'none';
    }

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
                // Clear the URL input if a file is selected
                document.getElementById('imageUrlInput').value = '';
            }
        } else if (type === 'url') {
            const url = input.value.trim();
            if (url) {
                previewImage.src = url;
                previewContainer.style.display = 'block';
                // Clear the File input if a URL is entered
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
</script>
@endsection
