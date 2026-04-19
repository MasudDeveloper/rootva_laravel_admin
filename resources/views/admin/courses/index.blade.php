@extends('layouts.admin')

@section('title', 'Courses Management')
@section('page_title', 'Curriculum & Progress')

@section('content')
<div class="fade-in" x-data="{ activeTab: 'videos' }">
    {{-- Statistics Dashboard --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card-modern border-0 shadow-sm p-4 text-center cursor-pointer" @click="activeTab = 'videos'" :class="activeTab === 'videos' ? 'border border-primary' : ''">
                <div class="rounded-circle bg-primary-soft text-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-video fa-xl"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['total_videos'] }}</h3>
                <p class="text-muted small mb-0">Total Lessons</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-modern border-0 shadow-sm p-4 text-center cursor-pointer" @click="activeTab = 'progress'" :class="activeTab === 'progress' ? 'border border-primary' : ''">
                <div class="rounded-circle bg-info-soft text-info mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-users-gear fa-xl"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['users_with_progress'] }}</h3>
                <p class="text-muted small mb-0">Users with Progress</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-modern border-0 shadow-sm p-4 text-center cursor-pointer" @click="activeTab = 'bonuses'" :class="activeTab === 'bonuses' ? 'border border-primary' : ''">
                <div class="rounded-circle bg-success-soft text-success mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-gift fa-xl"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['bonuses_claimed'] }}</h3>
                <p class="text-muted small mb-0">Bonuses Claimed</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    {{-- Tabs Content --}}
    
    {{-- Tab 1: Video Management --}}
    <div x-show="activeTab === 'videos'" x-transition>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">Video Lessons</h5>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                <i class="fa-solid fa-video me-2"></i>Add New Lesson
            </button>
        </div>
        <div class="card-modern p-0 overflow-hidden border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Lesson Title</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Video Thumbnail</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Duration</th>
                            <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse($courses as $course)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="fw-bold text-dark">{{ $course->title }}</div>
                                <div class="text-muted extra-small">
                                    <a href="{{ $course->youtube_url }}" target="_blank" class="text-primary text-decoration-none">
                                        <i class="fa-brands fa-youtube me-1"></i>View Link
                                    </a>
                                </div>
                            </td>
                            <td class="py-3">
                                @php
                                    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $course->youtube_url, $match);
                                    $youtube_id = $match[1] ?? null;
                                @endphp
                                @if($youtube_id)
                                    <img src="https://img.youtube.com/vi/{{ $youtube_id }}/mqdefault.jpg" class="rounded-3 shadow-sm" style="height: 60px">
                                @else
                                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="width: 100px; height: 60px">
                                        <i class="fa-solid fa-play text-muted opacity-25"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="py-3">
                                <span class="badge bg-light text-dark rounded-pill">{{ $course->duration }}s</span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button onclick="editCourse({{ $course->id }}, '{{ addslashes($course->title) }}', '{{ $course->youtube_url }}', '{{ $course->duration }}')" class="btn btn-light btn-sm rounded-circle text-primary shadow-sm">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.courses.destroy', $course->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light btn-sm rounded-circle text-danger shadow-sm" onclick="return confirm('Remove this lesson?')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">No lessons found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab 2: User Progress --}}
    <div x-show="activeTab === 'progress'" x-transition style="display: none;">
        <h5 class="fw-bold mb-4">Tracked User Progress</h5>
        <div class="card-modern p-0 overflow-hidden border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-start">User / Affiliate</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Completed Videos</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Overall Progress</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse($userProgress as $item)
                        <tr>
                            <td class="px-4 py-3 text-start">
                                <div class="fw-bold text-dark">{{ $item->name }}</div>
                                <div class="text-muted extra-small">ID: {{ $item->referCode }}</div>
                            </td>
                            <td class="py-3">
                                <span class="fw-bold text-primary">{{ $item->completed }}</span> <span class="text-muted">/ {{ $item->total }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px; border-radius: 10px;">
                                        <div class="progress-bar rounded-pill" role="progressbar" style="width: {{ $item->percent }}%" :class="'{{ $item->percent }}' > 80 ? 'bg-success' : 'bg-primary'"></div>
                                    </div>
                                    <span class="small fw-bold">{{ $item->percent }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">No progress data recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab 3: Claims --}}
    <div x-show="activeTab === 'bonuses'" x-transition style="display: none;">
        <h5 class="fw-bold mb-4">Bonus Payment Claims</h5>
        <div class="card-modern p-0 overflow-hidden border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-start">User Info</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Amount</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Claim Date</th>
                            <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse($claimedBonuses as $claim)
                        <tr>
                            <td class="px-4 py-3 text-start">
                                <div class="fw-bold text-dark">{{ $claim->name }}</div>
                                <div class="extra-small text-muted">Affiliate: {{ $claim->referCode }}</div>
                            </td>
                            <td class="py-3 fw-bold text-success">৳{{ number_format($claim->amount, 2) }}</td>
                            <td class="py-3 text-muted small">{{ $claim->date }}</td>
                            <td class="px-4 py-3 text-end">
                                <span class="badge bg-success-soft text-success rounded-pill px-3 fw-normal">Paid</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">No bonus claims found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Add Lesson Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.courses.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Lesson Title</label>
                        <input type="text" name="title" class="form-control rounded-3 shadow-sm" placeholder="e.g. Intro to Freelancing" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">YouTube URL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-brands fa-youtube text-danger"></i></span>
                            <input type="url" name="youtube_url" class="form-control border-start-0 rounded-end-3 shadow-sm" placeholder="https://youtube.com/watch?v=..." required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Duration (Seconds)</label>
                        <input type="number" name="duration" class="form-control rounded-3 shadow-sm" placeholder="e.g. 600">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Lesson</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Edit Lesson Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCourseForm" action="" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Lesson Title</label>
                        <input type="text" name="title" id="edit_title" class="form-control rounded-3 shadow-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">YouTube URL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-brands fa-youtube text-danger"></i></span>
                            <input type="url" name="youtube_url" id="edit_url" class="form-control border-start-0 rounded-end-3 shadow-sm" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Duration (Seconds)</label>
                        <input type="number" name="duration" id="edit_duration" class="form-control rounded-3 shadow-sm">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Update Lesson</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-gray-50 { background-color: #f9fafb; }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .extra-small { font-size: 0.75rem; }
    .card-modern { border-radius: 1.25rem; }
</style>

@endsection

@section('scripts')
<script>
    function editCourse(id, title, url, duration) {
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_url').value = url;
        document.getElementById('edit_duration').value = duration;
        document.getElementById('editCourseForm').action = `{{ url('/services/courses') }}/${id}`;
        new bootstrap.Modal(document.getElementById('editCourseModal')).show();
    }
</script>
@endsection
