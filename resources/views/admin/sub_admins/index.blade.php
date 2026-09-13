@extends('layouts.admin')

@section('title', 'Sub-Admin Management')
@section('page_title', 'Sub-Admin & Role Management')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-user-shield text-primary me-2"></i>Sub-Admin & Support Staff</h4>
            <p class="text-muted small mb-0">Create support admin accounts and assign module-based access permissions.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#createAdminModal">
            <i class="fa-solid fa-user-plus me-2"></i>Add Sub-Admin
        </button>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
        <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm rounded-pill px-4 mb-4">
        <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('error') }}
    </div>
    @endif

    <div class="card-modern p-0 overflow-hidden shadow-sm border-0 rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Admin</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Role</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Allowed Modules / Permissions</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admins as $adminUser)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3 bg-primary-soft text-primary fw-bold d-flex align-items-center justify-content-center rounded-circle" style="width: 42px; height: 42px;">
                                    {{ strtoupper(substr($adminUser->name ?: $adminUser->username, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $adminUser->name ?: 'Admin Staff' }}</div>
                                    <div class="small text-muted">@ {{ $adminUser->username }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            @if($adminUser->isSuperAdmin())
                                <span class="badge bg-danger-soft text-danger rounded-pill px-3 py-2 fw-bold">
                                    <i class="fa-solid fa-crown me-1"></i>Super Admin
                                </span>
                            @else
                                <span class="badge bg-info-soft text-info rounded-pill px-3 py-2 fw-bold">
                                    <i class="fa-solid fa-user-gear me-1"></i>Support Admin
                                </span>
                            @endif
                        </td>
                        <td class="py-3">
                            @if($adminUser->isSuperAdmin())
                                <span class="badge bg-success-soft text-success rounded-pill px-3 py-1 extra-small">
                                    <i class="fa-solid fa-shield-halved me-1"></i>Full Access (All Modules)
                                </span>
                            @else
                                <div class="d-flex flex-wrap gap-1">
                                    @php
                                        $userPerms = $adminUser->permissions ?? [];
                                    @endphp
                                    @forelse($userPerms as $permKey)
                                        <span class="badge bg-primary-soft text-primary rounded-pill px-2 py-1 extra-small">
                                            {{ $availablePermissions[$permKey] ?? $permKey }}
                                        </span>
                                    @empty
                                        <span class="text-muted small italic">No module permissions assigned</span>
                                    @endforelse
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-sm btn-light rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#editAdminModal{{ $adminUser->id }}">
                                    <i class="fa-solid fa-pen-to-square me-1 text-primary"></i>Edit
                                </button>
                                @if(!$adminUser->isSuperAdmin() && $adminUser->id !== auth()->id())
                                <form action="{{ route('admin.sub-admins.destroy', $adminUser->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this sub-admin account?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger-soft text-danger rounded-pill px-3 fw-bold">
                                        <i class="fa-solid fa-trash me-1"></i>Delete
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">No admin users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Admin Modals (Placed outside table to prevent HTML layout distortion) -->
@foreach($admins as $adminUser)
<div class="modal fade" id="editAdminModal{{ $adminUser->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content border-0 shadow rounded-4" action="{{ route('admin.sub-admins.update', $adminUser->id) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fa-solid fa-user-pen text-primary me-2"></i>Edit Admin & Permissions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control rounded-pill px-3" value="{{ $adminUser->name }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold">Username</label>
                        <input type="text" name="username" class="form-control rounded-pill px-3" value="{{ $adminUser->username }}" required>
                    </div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold">New Password (Leave blank to keep unchanged)</label>
                        <input type="password" name="password" class="form-control rounded-pill px-3" placeholder="••••••••">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold">Role</label>
                        <select name="role" class="form-select rounded-pill px-3 role-select" onchange="togglePermissionsEdit(this, {{ $adminUser->id }})" required>
                            <option value="sub_admin" {{ $adminUser->role === 'sub_admin' ? 'selected' : '' }}>Sub-Admin / Support Staff</option>
                            <option value="super_admin" {{ $adminUser->role === 'super_admin' ? 'selected' : '' }}>Super Admin (Full Access)</option>
                        </select>
                    </div>
                </div>

                <div class="permission-box-{{ $adminUser->id }}" style="{{ $adminUser->role === 'super_admin' ? 'display:none;' : '' }}">
                    <label class="form-label small text-muted text-uppercase fw-bold mb-3 d-block">Module Access Permissions (মডিউল অনুমতি)</label>
                    <div class="row g-3">
                        @foreach($availablePermissions as $key => $label)
                        <div class="col-md-6">
                            <div class="form-check p-3 bg-light rounded-4 border">
                                <input class="form-check-input ms-0 me-3" type="checkbox" name="permissions[]" value="{{ $key }}" id="edit_perm_{{ $adminUser->id }}_{{ $key }}" {{ in_array($key, $adminUser->permissions ?? []) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold small" for="edit_perm_{{ $adminUser->id }}_{{ $key }}">
                                    {{ $label }}
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<!-- Create Admin Modal -->
<div class="modal fade" id="createAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content border-0 shadow rounded-4" action="{{ route('admin.sub-admins.store') }}" method="POST">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fa-solid fa-user-plus text-primary me-2"></i>Create New Sub-Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control rounded-pill px-3" placeholder="e.g. Rahul Hasan" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold">Username</label>
                        <input type="text" name="username" class="form-control rounded-pill px-3" placeholder="e.g. rahul_support" required>
                    </div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold">Password</label>
                        <input type="password" name="password" class="form-control rounded-pill px-3" placeholder="••••••••" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted text-uppercase fw-bold">Role</label>
                        <select name="role" id="createRoleSelect" class="form-select rounded-pill px-3" onchange="togglePermissionsCreate(this)" required>
                            <option value="sub_admin" selected>Sub-Admin / Support Staff</option>
                            <option value="super_admin">Super Admin (Full Access)</option>
                        </select>
                    </div>
                </div>

                <div id="createPermissionBox">
                    <label class="form-label small text-muted text-uppercase fw-bold mb-3 d-block">Module Access Permissions (মডিউল অনুমতি)</label>
                    <div class="row g-3">
                        @foreach($availablePermissions as $key => $label)
                        <div class="col-md-6">
                            <div class="form-check p-3 bg-light rounded-4 border">
                                <input class="form-check-input ms-0 me-3" type="checkbox" name="permissions[]" value="{{ $key }}" id="create_perm_{{ $key }}">
                                <label class="form-check-label fw-semibold small" for="create_perm_{{ $key }}">
                                    {{ $label }}
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">Create Account</button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePermissionsCreate(select) {
    const box = document.getElementById('createPermissionBox');
    if (select.value === 'super_admin') {
        box.style.display = 'none';
    } else {
        box.style.display = 'block';
    }
}

function togglePermissionsEdit(select, id) {
    const box = document.querySelector('.permission-box-' + id);
    if (select.value === 'super_admin') {
        box.style.display = 'none';
    } else {
        box.style.display = 'block';
    }
}
</script>
@endsection
