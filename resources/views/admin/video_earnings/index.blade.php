@extends('layouts.admin')

@section('title', 'Video Earning Management')
@section('page_title', 'Video Earning System')

@section('content')
<div class="fade-in">
    {{-- Statistics Dashboard --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card-modern border-0 shadow-sm p-4 text-center">
                <div class="rounded-circle bg-danger-soft text-danger mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background: rgba(220, 53, 69, 0.1);">
                    <i class="fa-brands fa-youtube fa-xl"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['total_videos'] }}</h3>
                <p class="text-muted small mb-0">Total Videos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern border-0 shadow-sm p-4 text-center">
                <div class="rounded-circle bg-success-soft text-success mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background: rgba(25, 135, 84, 0.1);">
                    <i class="fa-solid fa-circle-check fa-xl"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ $stats['active_videos'] }}</h3>
                <p class="text-muted small mb-0">Active Videos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern border-0 shadow-sm p-4 text-center">
                <div class="rounded-circle bg-primary-soft text-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background: rgba(13, 110, 253, 0.1);">
                    <i class="fa-solid fa-eye fa-xl"></i>
                </div>
                <h3 class="fw-bold mb-1">{{ number_format($stats['total_watches']) }}</h3>
                <p class="text-muted small mb-0">Total Watches</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern border-0 shadow-sm p-4 text-center">
                <div class="rounded-circle bg-warning-soft text-warning mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background: rgba(255, 193, 7, 0.15);">
                    <i class="fa-solid fa-bangladeshi-taka-sign fa-xl"></i>
                </div>
                <h3 class="fw-bold mb-1">৳{{ number_format($stats['total_paid'], 2) }}</h3>
                <p class="text-muted small mb-0">Total Distributed</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 px-4 py-3 mb-4 d-flex align-items-center">
            <i class="fa-solid fa-circle-check me-2 fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 px-4 py-3 mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header & Action --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-1">ভিডিও আর্নিং লিস্ট</h5>
            <p class="text-muted small mb-0">ইউজারদের জন্য ভিডিও যোগ করুন, এডিট করুন বা ক্রম বিন্যাস করুন</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addVideoModal">
            <i class="fa-solid fa-plus me-2"></i>নতুন ভিডিও যোগ করুন
        </button>
    </div>

    {{-- Video Table --}}
    <div class="card-modern p-0 overflow-hidden border-0 shadow-sm bg-white rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold" style="width: 60px;">ক্রম</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold" style="width: 140px;">থাম্বনেইল</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">ভিডিও টাইটেল ও লিংক</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center" style="width: 100px;">সময়সীমা</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center" style="width: 100px;">স্ট্যাটাস</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end" style="width: 130px;">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($videos as $video)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="badge bg-light text-dark border rounded-pill px-3 py-2 fw-bold">
                                #{{ $video->serial }}
                            </span>
                        </td>
                        <td class="py-3">
                            @php
                                $thumbUrl = $video->video_id 
                                    ? "https://img.youtube.com/vi/{$video->video_id}/hqdefault.jpg" 
                                    : asset('assets/img/logo.png');
                            @endphp
                            <div class="position-relative rounded-3 overflow-hidden shadow-sm" style="width: 120px; height: 68px;">
                                <img src="{{ $thumbUrl }}" alt="Thumb" class="w-100 h-100 object-fit-cover">
                                <a href="{{ $video->youtube_url }}" target="_blank" class="position-absolute top-50 start-50 translate-middle text-white" style="text-shadow: 0 1px 4px rgba(0,0,0,0.8);">
                                    <i class="fa-brands fa-youtube fa-2xl text-danger"></i>
                                </a>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="fw-bold text-dark mb-1">{{ $video->title }}</div>
                            <div class="small text-muted text-truncate" style="max-width: 380px;">
                                <i class="fa-solid fa-link me-1 text-primary"></i>
                                <a href="{{ $video->youtube_url }}" target="_blank" class="text-decoration-none text-muted">
                                    {{ $video->youtube_url }}
                                </a>
                            </div>
                        </td>
                        <td class="py-3 text-center">
                            <span class="badge bg-primary-soft text-primary rounded-pill px-3 py-2">
                                <i class="fa-regular fa-clock me-1"></i>{{ $video->duration }} সেকেন্ড
                            </span>
                        </td>
                        <td class="py-3 text-center">
                            <form action="{{ route('admin.video-earnings.toggle', $video->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="border-0 bg-transparent p-0" title="স্ট্যাটাস পরিবর্তন করতে ক্লিক করুন">
                                    @if($video->status)
                                        <span class="badge bg-success rounded-pill px-3 py-2 cursor-pointer">সচল</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill px-3 py-2 cursor-pointer">নিষ্ক্রিয়</span>
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary rounded-start-pill px-3" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editVideoModal{{ $video->id }}"
                                        title="এডিট">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('admin.video-earnings.destroy', $video->id) }}" method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই ভিডিওটি মুছে ফেলতে চান?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-end-pill px-3" title="মুছে ফেলুন">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- Edit Modal for each video --}}
                    <div class="modal fade" id="editVideoModal{{ $video->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg rounded-4">
                                <form action="{{ route('admin.video-earnings.update', $video->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-header border-0 px-4 pt-4">
                                        <h5 class="modal-title fw-bold">ভিডিও এডিট করুন</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body px-4 py-3">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">ভিডিও টাইটেল</label>
                                            <input type="text" name="title" class="form-control rounded-3" value="{{ $video->title }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">ইউটিউব লিংক (YouTube URL)</label>
                                            <input type="text" name="youtube_url" class="form-control rounded-3" value="{{ $video->youtube_url }}" required>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small text-muted">ক্রম বিন্যাস (Serial)</label>
                                                <input type="number" name="serial" class="form-control rounded-3" value="{{ $video->serial }}" min="1">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small text-muted">সময়সীমা (সেকেন্ড)</label>
                                                <input type="number" name="duration" class="form-control rounded-3" value="{{ $video->duration }}" min="10" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">স্ট্যাটাস</label>
                                            <select name="status" class="form-select rounded-3">
                                                <option value="1" {{ $video->status ? 'selected' : '' }}>সচল (Active)</option>
                                                <option value="0" {{ !$video->status ? 'selected' : '' }}>নিষ্ক্রিয় (Inactive)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 px-4 pb-4">
                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">বাতিল</button>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4">সংরক্ষণ করুন</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="py-4">
                                <i class="fa-solid fa-video-slash fa-3x text-muted mb-3 opacity-50"></i>
                                <h6 class="text-muted fw-bold">কোনো ভিডিও যোগ করা হয়নি</h6>
                                <p class="text-muted small">উপরের বাটনে ক্লিক করে প্রথম ভিডিওটি যোগ করুন।</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($videos->hasPages())
        <div class="p-3 border-top">
            {{ $videos->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Add Video Modal --}}
<div class="modal fade" id="addVideoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('admin.video-earnings.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">নতুন ভিডিও যোগ করুন</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">ভিডিও টাইটেল</label>
                        <input type="text" name="title" class="form-control rounded-3" placeholder="যেমন: অনলাইন থেকে আয়ের সহজ উপায়" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">ইউটিউব লিংক (YouTube URL)</label>
                        <input type="text" name="youtube_url" class="form-control rounded-3" placeholder="https://www.youtube.com/watch?v=..." required>
                        <div class="form-text small">ইউটিউবের স্বাভাবিক লিংক বা শেয়ার লিংক দিন।</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small text-muted">ক্রম বিন্যাস (Serial)</label>
                            <input type="number" name="serial" class="form-control rounded-3" placeholder="স্বয়ংক্রিয়" min="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small text-muted">মিনিমাম দেখার সময় (সেকেন্ড)</label>
                            <input type="number" name="duration" class="form-control rounded-3" value="60" min="10" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">যোগ করুন</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
