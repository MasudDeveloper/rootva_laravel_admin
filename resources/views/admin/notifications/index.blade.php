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

                    <form action="{{ route('admin.notifications.send') }}" method="POST">
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
                        </div>

                        <div class="mb-4" id="referCodeDiv" style="display: none;">
                            <label class="form-label fw-bold small">Refer Code</label>
                            <input type="text" name="referCode" class="form-control" placeholder="Enter User Refer Code">
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
</script>
@endsection
