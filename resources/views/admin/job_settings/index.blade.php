@extends('layouts.admin')

@section('title', 'Job Settings')
@section('page_title', 'Microjob Configuration')

@section('content')
<div class="fade-in">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="card-modern border-0 shadow-sm overflow-hidden">
        <div class="bg-white border-bottom p-2">
            <ul class="nav nav-pills nav-fill" id="settingsTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active rounded-pill fw-bold" data-bs-toggle="tab" data-bs-target="#tabStatus">
                        <i class="fa-solid fa-toggle-on me-2"></i> Job Status
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill fw-bold" data-bs-toggle="tab" data-bs-target="#tabTexts">
                        <i class="fa-solid fa-file-lines me-2"></i> Job Texts
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill fw-bold" data-bs-toggle="tab" data-bs-target="#tabTutorials">
                        <i class="fa-solid fa-video me-2"></i> Job Tutorials
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content @yield('tab_content_class', 'p-4')" id="settingsTabsContent">
            <!-- Job Status Tab -->
            <div class="tab-pane fade show active" id="tabStatus">
                <form action="{{ route('admin.job-settings.updateStatus') }}" method="POST">
                    @csrf
                    <div class="row g-4">
                        @php
                            $fields = [
                                'facebook' => 'Facebook Job',
                                'instagram' => 'Instagram Job',
                                'email' => 'Email/Gmail Job',
                                'tiktok' => 'TikTok Job',
                                'review' => 'Review Job',
                                'ads' => 'Ads Job',
                                'dollar' => 'Dollar Income',
                                'recharge' => 'Mobile Recharge',
                                'sim_offer' => 'Sim Offer'
                            ];
                        @endphp

                        @foreach($fields as $field => $label)
                        <div class="col-md-4">
                            <div class="p-3 border rounded-4 d-flex justify-content-between align-items-center bg-gray-50">
                                <div>
                                    <div class="fw-bold mb-0 text-dark">{{ $label }}</div>
                                    <div class="extra-small text-muted">Toggle visibility</div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="{{ $field }}" 
                                           id="switch_{{ $field }}" {{ $status->$field ? 'checked' : '' }} style="width: 3em; height: 1.5em;">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Job Texts Tab -->
            <div class="tab-pane fade" id="tabTexts">
                <form action="{{ route('admin.job-settings.updateTexts') }}" method="POST">
                    @csrf
                    <div class="row g-4">
                        @foreach($texts as $text)
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-uppercase small text-muted letter-spacing-1">{{ str_replace('_', ' ', $text->job_type) }} Content</label>
                            <textarea name="texts[{{ $text->id }}]" class="form-control rounded-4 p-3" rows="3">{{ $text->content }}</textarea>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Update All Texts</button>
                    </div>
                </form>
            </div>

            <!-- Job Tutorials Tab -->
            <div class="tab-pane fade" id="tabTutorials">
                <form action="{{ route('admin.job-settings.updateTutorials') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        @foreach($tutorials as $tutorial)
                        <div class="col-md-6">
                            <div class="p-3 border rounded-4 bg-gray-50">
                                <label class="form-label fw-bold small text-muted">{{ strtoupper(str_replace('_', ' ', $tutorial->job_type)) }} Tutorial URL</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="fa-brands fa-youtube text-danger"></i></span>
                                    <input type="url" name="tutorials[{{ $tutorial->id }}]" class="form-control border-start-0 rounded-end-pill" 
                                           value="{{ $tutorial->tutorial }}" placeholder="https://youtube.com/watch?v=...">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-4 pt-3 border-top text-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Update All Tutorials</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
