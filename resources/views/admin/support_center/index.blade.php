@extends('layouts.admin')

@section('title', 'Support Center Manager')
@section('page_title', 'Support Center Management')

@section('content')
<div class="fade-in">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="row g-4">
        <!-- Members Management -->
        <div class="col-md-6">
            <div class="card-modern mb-4">
                <h5 class="fw-bold mb-4 text-primary"><i class="fa-solid fa-user-tie me-2"></i>Add Support Member</h5>
                <form action="{{ route('admin.support-center.members.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Hasnatul Islam Ovi" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Designation</label>
                            <input type="text" name="designation" class="form-control" placeholder="e.g. Chairman" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted text-uppercase fw-bold">Quote / Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Our goal is your success..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Avatar / Photo</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Facebook URL (Optional)</label>
                            <input type="url" name="fb_link" class="form-control" placeholder="https://facebook.com/username">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">WhatsApp Link (Optional)</label>
                            <input type="url" name="wa_link" class="form-control" placeholder="https://wa.me/number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Telegram Link (Optional)</label>
                            <input type="url" name="tg_link" class="form-control" placeholder="https://t.me/username">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Phone Number (Optional)</label>
                            <input type="text" name="phone_link" class="form-control" placeholder="tel:+8801700000000">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill shadow-sm mt-4">
                        <i class="fa-solid fa-plus me-2"></i>Add Member
                    </button>
                </form>
            </div>

            <div class="card-modern">
                <h5 class="fw-bold mb-4">Active Support Members ({{ $members->count() }})</h5>
                <div class="row g-3">
                    @forelse($members as $member)
                        <div class="col-12">
                            <div class="card border rounded-4 shadow-sm p-3 position-relative">
                                <div class="position-absolute top-0 end-0 p-2">
                                    <form action="{{ route('admin.support-center.members.destroy', $member->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm rounded-circle shadow" onclick="return confirm('Delete this member?')" style="width: 32px; height: 32px; padding: 0;">
                                            <i class="fa-solid fa-trash-can small"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $member->avatar_url ?: asset('images/placeholder_avatar.png') }}" class="rounded-circle border border-primary" style="width: 60px; height: 60px; object-fit: cover;">
                                    <div>
                                        <h6 class="fw-bold mb-1">{{ $member->name }} <i class="fa-solid fa-circle-check text-primary small"></i></h6>
                                        <span class="badge bg-light text-primary border border-primary-subtle mb-2">{{ $member->designation }}</span>
                                        <p class="text-muted extra-small mb-2">{{ $member->description }}</p>
                                        <div class="d-flex gap-2">
                                            @if($member->fb_link) <span class="badge bg-blue text-white"><i class="fa-brands fa-facebook me-1"></i>FB</span> @endif
                                            @if($member->wa_link) <span class="badge bg-success text-white"><i class="fa-brands fa-whatsapp me-1"></i>WA</span> @endif
                                            @if($member->tg_link) <span class="badge bg-info text-white"><i class="fa-brands fa-telegram me-1"></i>TG</span> @endif
                                            @if($member->phone_link) <span class="badge bg-purple text-white"><i class="fa-solid fa-phone me-1"></i>Phone</span> @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-4">No support members created yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Services Management -->
        <div class="col-md-6">
            <div class="card-modern mb-4">
                <h5 class="fw-bold mb-4 text-success"><i class="fa-solid fa-headset me-2"></i>Add Support Service</h5>
                <form action="{{ route('admin.support-center.services.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Service Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Reselling Support" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="e.g. Order, Payment and Product Support">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Icon Image</label>
                            <input type="file" name="icon" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Button Text</label>
                            <input type="text" name="button_text" class="form-control" placeholder="e.g. WhatsApp সাপোর্ট বা যোগাযোগ করুন">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted text-uppercase fw-bold">WhatsApp / Support URL</label>
                            <input type="url" name="link" class="form-control" placeholder="https://wa.me/number" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100 rounded-pill shadow-sm mt-4">
                        <i class="fa-solid fa-plus me-2"></i>Add Service
                    </button>
                </form>
            </div>

            <div class="card-modern">
                <h5 class="fw-bold mb-4">Active Support Services ({{ $services->count() }})</h5>
                <div class="row g-3">
                    @forelse($services as $service)
                        <div class="col-12">
                            <div class="card border rounded-4 shadow-sm p-3 position-relative">
                                <div class="position-absolute top-0 end-0 p-2">
                                    <form action="{{ route('admin.support-center.services.destroy', $service->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm rounded-circle shadow" onclick="return confirm('Delete this service?')" style="width: 32px; height: 32px; padding: 0;">
                                            <i class="fa-solid fa-trash-can small"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $service->icon_url ?: asset('images/placeholder_icon.png') }}" class="rounded-3" style="width: 45px; height: 45px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-1">{{ $service->name }}</h6>
                                        <p class="text-muted extra-small mb-2">{{ $service->description }}</p>
                                        <a href="{{ $service->link }}" target="_blank" class="badge bg-light text-success border border-success-subtle text-decoration-none">
                                            <i class="fa-solid fa-link me-1"></i>{{ $service->button_text }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-4">No support services created yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
