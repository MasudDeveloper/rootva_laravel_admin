@extends('layouts.admin')

@section('title', 'Global App Settings')
@section('page_title', 'System Configuration')

@section('content')
<div class="fade-in" x-data="{ activeTab: 'social' }">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">App Settings</h4>
            <p class="text-muted small mb-0">Manage social links, payment numbers, and app updates</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        
        {{-- Custom Tabs --}}
        <div class="card-modern border-0 shadow-sm mb-4 p-2 bg-white rounded-pill d-inline-flex border overflow-auto max-w-100">
            <button type="button" class="btn rounded-pill px-3 transition-all whitespace-nowrap" :class="activeTab === 'social' ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none'" @click="activeTab = 'social'">
                <i class="fa-solid fa-share-nodes me-2"></i>Social
            </button>
            <button type="button" class="btn rounded-pill px-3 transition-all whitespace-nowrap" :class="activeTab === 'support' ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none'" @click="activeTab = 'support'">
                <i class="fa-solid fa-headset me-2"></i>Support
            </button>
            <button type="button" class="btn rounded-pill px-4 transition-all whitespace-nowrap" :class="activeTab === 'payments' ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none'" @click="activeTab = 'payments'">
                <i class="fa-solid fa-credit-card me-2"></i>Payments
            </button>
            <button type="button" class="btn rounded-pill px-3 transition-all whitespace-nowrap" :class="activeTab === 'updates' ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none'" @click="activeTab = 'updates'">
                <i class="fa-solid fa-cloud-arrow-up me-2"></i>App Updates
            </button>
            <button type="button" class="btn rounded-pill px-3 transition-all whitespace-nowrap" :class="activeTab === 'work' ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none'" @click="activeTab = 'work'">
                <i class="fa-solid fa-briefcase me-2"></i>Work
            </button>
        </div>

        {{-- Tab Content --}}
        <div class="card-modern border-0 shadow-sm p-4 mb-4">
            
            {{-- Tab 1: Social Groups --}}
            <div x-show="activeTab === 'social'" x-transition>
                <h5 class="fw-bold mb-4 text-primary border-bottom pb-2">Community & Social Media</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Facebook Group Link</label>
                        <input type="text" name="facebook_group" class="form-control rounded-3" value="{{ $social->facebook_group }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">WhatsApp Group Link</label>
                        <input type="text" name="whatsapp_group" class="form-control rounded-3" value="{{ $social->whatsapp_group }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Telegram Group</label>
                        <input type="text" name="telegram_group" class="form-control rounded-3" value="{{ $social->telegram_group }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">YouTube Channel Link</label>
                        <input type="text" name="youtube_channel" class="form-control rounded-3" value="{{ $social->youtube_channel }}">
                    </div>
                </div>
            </div>

            {{-- Tab 2: Support Channels --}}
            <div x-show="activeTab === 'support'" x-transition style="display: none;">
                <h5 class="fw-bold mb-4 text-info border-bottom pb-2">Customer & Admin Support</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Main Support Number</label>
                        <input type="text" name="support_number" class="form-control rounded-3" value="{{ $social->support_number }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Reselling Support Number</label>
                        <input type="text" name="support_reselling" class="form-control rounded-3" value="{{ $social->support_reselling }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Support Facebook Page</label>
                        <input type="text" name="support_facebook" class="form-control rounded-3" value="{{ $social->support_facebook }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Verification Help Link</label>
                        <input type="text" name="support_verify" class="form-control rounded-3" value="{{ $social->support_verify }}">
                    </div>
                </div>
            </div>

            {{-- Tab 3: Payment Numbers --}}
            <div x-show="activeTab === 'payments'" x-transition style="display: none;">
                <h5 class="fw-bold mb-4 text-danger border-bottom pb-2">Cash-In Payment Methods</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">bKash Number</label>
                        <input type="text" name="bkash" class="form-control rounded-3" value="{{ $payments->bkash }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nagad Number</label>
                        <input type="text" name="nagad" class="form-control rounded-3" value="{{ $payments->nagad }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Rocket Number</label>
                        <input type="text" name="rocket" class="form-control rounded-3" value="{{ $payments->rocket }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Upay Number</label>
                        <input type="text" name="upay" class="form-control rounded-3" value="{{ $payments->upay }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Identity Verification Fee (৳)</label>
                        <input type="number" name="verify_amount" class="form-control rounded-3 shadow-sm border-primary" value="{{ $payments->verify_amount }}">
                        <p class="text-muted extra-small mt-1">Users will be charged this amount for account verification.</p>
                    </div>
                </div>
            </div>

            {{-- Tab 4: App Updates --}}
            <div x-show="activeTab === 'updates'" x-transition style="display: none;">
                <h5 class="fw-bold mb-4 text-warning border-bottom pb-2">Android App Versions</h5>
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">New Version Code (Int)</label>
                        <input type="number" name="version_code" class="form-control rounded-3" placeholder="e.g. 15">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Update URL (PlayStore/APK)</label>
                        <input type="text" name="update_link" class="form-control rounded-3" placeholder="https://...">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Update Message</label>
                        <textarea name="update_message" class="form-control rounded-3" rows="2" placeholder="What's new in this version?"></textarea>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 text-muted text-uppercase">Update History</h6>
                <div class="table-responsive bg-light rounded-3 p-2 border">
                    <table class="table table-sm table-hover mb-0 extra-small">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Message</th>
                                <th>Link</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($updates as $update)
                            <tr>
                                <td class="fw-bold text-primary">{{ $update->version_code }}</td>
                                <td class="text-truncate" style="max-width: 200px;">{{ $update->update_message }}</td>
                                <td><a href="{{ $update->update_link }}" target="_blank" class="text-decoration-none"><i class="fa-solid fa-link"></i></a></td>
                                <td>{{ date('d-m-Y', strtotime($update->created_at)) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-2">No update history found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab 5: Work Links --}}
            <div x-show="activeTab === 'work'" x-transition style="display: none;">
                <h5 class="fw-bold mb-4 text-success border-bottom pb-2">Work & Submissions</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Facebook Submission Link</label>
                        <input type="text" name="facebook_work_submit" class="form-control rounded-3" value="{{ $social->facebook_work_submit }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Email Submission Link</label>
                        <input type="text" name="email_work_submit" class="form-control rounded-3" value="{{ $social->email_work_submit }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Customer Meeting URL</label>
                        <input type="text" name="customer_meeting" class="form-control rounded-3" value="{{ $social->customer_meeting }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Business Meeting URL</label>
                        <input type="text" name="business_meeting" class="form-control rounded-3" value="{{ $social->business_meeting }}">
                    </div>
                </div>
            </div>

        </div>

        <div class="d-flex justify-content-end gap-3">
            <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-lg">
                <i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings
            </button>
        </div>
    </form>
</div>

<style>
    .transition-all { transition: all 0.3s ease; }
    .card-modern { border-radius: 1.5rem; }
    .extra-small { font-size: 0.75rem; }
    .whitespace-nowrap { white-space: nowrap; }
    .max-w-100 { max-width: 100%; }
</style>
@endsection
