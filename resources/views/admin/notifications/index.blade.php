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

                    <form id="sendNotificationForm" action="{{ route('admin.notifications.send') }}" method="POST" enctype="multipart/form-data">
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
                            <label class="form-label fw-bold small">Action Link / Meeting Link (Optional)</label>
                            <input type="text" name="link" class="form-control" placeholder="https://zoom.us/j/... or https://meet.google.com/...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label d-block fw-bold small">Target Audience</label>
                            <div class="d-flex flex-wrap gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="target" id="targetAll" value="all" checked onchange="handleTargetChange()">
                                    <label class="form-check-label small" for="targetAll">All Users</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="target" id="targetSpecific" value="specific" onchange="handleTargetChange()">
                                    <label class="form-check-label small" for="targetSpecific">Specific Refer Code</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="target" id="targetVerified" value="verified" onchange="handleTargetChange()">
                                    <label class="form-check-label small" for="targetVerified">Verified Users (ভেরিফাইড)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="target" id="targetUnverified" value="unverified" onchange="handleTargetChange()">
                                    <label class="form-check-label small" for="targetUnverified">Unverified Users (আনভেরিফাইড)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="target" id="targetRegistered" value="registered" onchange="handleTargetChange()">
                                    <label class="form-check-label small" for="targetRegistered">New Account / Registered (নতুন একাউন্ট)</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3" id="referCodeDiv" style="display: none;">
                            <label class="form-label fw-bold small">Refer Code <span class="text-danger">*</span></label>
                            <input type="text" name="referCode" id="referCodeInput" class="form-control form-control-sm" placeholder="Enter User Refer Code" oninput="updateRecipientCount()">
                        </div>

                        <!-- Date Filter Section -->
                        <div class="card border border-info-subtle bg-light mb-3" id="dateFilterDiv" style="display: none;">
                            <div class="card-body p-3">
                                <label class="form-label fw-bold small d-block mb-2 text-dark" id="dateFilterTitle">
                                    <i class="fa-regular fa-calendar-days text-primary me-1"></i> Date Filter (তারিখ ফিল্টার)
                                </label>
                                
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="date_filter" id="dateAll" value="all" checked onchange="handleDateFilterChange()">
                                        <label class="form-check-label small" for="dateAll">All Time (সব সময়)</label>
                                    </div>
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="date_filter" id="dateToday" value="today" onchange="handleDateFilterChange()">
                                        <label class="form-check-label small" for="dateToday">Today (আজকে)</label>
                                    </div>
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="date_filter" id="dateYesterday" value="yesterday" onchange="handleDateFilterChange()">
                                        <label class="form-check-label small" for="dateYesterday">Yesterday (গতকাল)</label>
                                    </div>
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="date_filter" id="dateCustom" value="custom" onchange="handleDateFilterChange()">
                                        <label class="form-check-label small" for="dateCustom">Specific Date (নির্দিষ্ট তারিখ)</label>
                                    </div>
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="date_filter" id="dateRange" value="range" onchange="handleDateFilterChange()">
                                        <label class="form-check-label small" for="dateRange">Date Range (তারিখ রেঞ্জ)</label>
                                    </div>
                                </div>

                                <!-- Single Custom Date Picker -->
                                <div id="customDateDiv" style="display: none;" class="mb-2">
                                    <label class="form-label small text-muted mb-1">Select Date:</label>
                                    <input type="date" name="custom_date" id="customDateInput" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" onchange="updateRecipientCount()">
                                </div>

                                <!-- Date Range Pickers -->
                                <div id="rangeDateDiv" style="display: none;" class="mb-2">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small text-muted mb-1">From:</label>
                                            <input type="date" name="date_from" id="dateFromInput" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" onchange="updateRecipientCount()">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small text-muted mb-1">To:</label>
                                            <input type="date" name="date_to" id="dateToInput" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" onchange="updateRecipientCount()">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Target Recipient Counter Badge -->
                        <div class="d-flex align-items-center justify-content-between p-2 px-3 bg-light rounded-3 border mb-3">
                            <span class="small text-muted"><i class="fa-solid fa-users me-1 text-primary"></i> Target Recipients:</span>
                            <span id="recipientCountBadge" class="badge bg-primary fs-6">Loading...</span>
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
    let countDebounceTimer = null;

    function handleTargetChange() {
        const specificRadio = document.getElementById('targetSpecific');
        const referInputDiv = document.getElementById('referCodeDiv');
        const referInput = document.getElementById('referCodeInput');
        const dateFilterDiv = document.getElementById('dateFilterDiv');
        const dateFilterTitle = document.getElementById('dateFilterTitle');
        const selectedTarget = document.querySelector('input[name="target"]:checked')?.value;

        if (specificRadio && referInputDiv) {
            referInputDiv.style.display = specificRadio.checked ? 'block' : 'none';
            if (referInput) referInput.required = specificRadio.checked;
        }

        if (dateFilterDiv && dateFilterTitle) {
            if (selectedTarget === 'verified') {
                dateFilterDiv.style.display = 'block';
                dateFilterTitle.innerHTML = '<i class="fa-regular fa-calendar-check text-success me-1"></i> Verification Date (ভেরিফিকেশনের তারিখ)';
            } else if (selectedTarget === 'unverified') {
                dateFilterDiv.style.display = 'block';
                dateFilterTitle.innerHTML = '<i class="fa-regular fa-calendar-days text-warning me-1"></i> Registration Date (একাউন্ট খোলার তারিখ)';
            } else if (selectedTarget === 'registered') {
                dateFilterDiv.style.display = 'block';
                dateFilterTitle.innerHTML = '<i class="fa-regular fa-calendar-plus text-info me-1"></i> Registration Date (একাউন্ট খোলার তারিখ)';
            } else {
                dateFilterDiv.style.display = 'none';
            }
        }

        updateRecipientCount();
    }

    function handleDateFilterChange() {
        const selectedFilter = document.querySelector('input[name="date_filter"]:checked')?.value;
        const customDateDiv = document.getElementById('customDateDiv');
        const rangeDateDiv = document.getElementById('rangeDateDiv');

        if (customDateDiv && rangeDateDiv) {
            if (selectedFilter === 'custom') {
                customDateDiv.style.display = 'block';
                rangeDateDiv.style.display = 'none';
            } else if (selectedFilter === 'range') {
                customDateDiv.style.display = 'none';
                rangeDateDiv.style.display = 'block';
            } else {
                customDateDiv.style.display = 'none';
                rangeDateDiv.style.display = 'none';
            }
        }

        updateRecipientCount();
    }

    function updateRecipientCount() {
        clearTimeout(countDebounceTimer);
        const badge = document.getElementById('recipientCountBadge');
        if (!badge) return;
        badge.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

        countDebounceTimer = setTimeout(() => {
            const form = document.getElementById('sendNotificationForm');
            if (!form) return;
            const formData = new FormData(form);
            const params = new URLSearchParams();
            for (let [key, val] of formData.entries()) {
                if (key !== '_token' && key !== 'image') {
                    params.append(key, val);
                }
            }

            fetch(`{{ route('admin.notifications.target_count') }}?` + params.toString())
                .then(res => res.json())
                .then(data => {
                    if (data && data.success) {
                        badge.className = data.count > 0 ? 'badge bg-success fs-6' : 'badge bg-secondary fs-6';
                        badge.innerText = data.count + ' user(s)';
                    } else {
                        badge.className = 'badge bg-secondary fs-6';
                        badge.innerText = '0 user(s)';
                    }
                })
                .catch(() => {
                    badge.className = 'badge bg-secondary fs-6';
                    badge.innerText = '-';
                });
        }, 300);
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function () {
        handleTargetChange();
        handleDateFilterChange();
    });

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
