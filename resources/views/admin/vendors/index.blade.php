@extends('layouts.admin')

@section('title', 'Vendor Management')
@section('page_title', 'Vendor Stores')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Vendors: {{ $vendors->total() }}</h4>
            <p class="text-muted small mb-0">Manage registered seller stores, statuses, and commissions</p>
        </div>
        <div>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addVendorModal">
                <i class="fa-solid fa-user-plus me-2"></i>Add Vendor
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3">Store Details</th>
                        <th class="py-3">Owner Contact</th>
                        <th class="py-3">Commission Rate</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-end px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vendors as $vendor)
                    <tr>
                        <td class="px-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                    <i class="fa-solid fa-store"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">{{ $vendor->store_name }}</h6>
                                    <span class="text-muted extra-small d-block">{{ \Illuminate\Support\Str::limit($vendor->store_description, 50) ?: 'No description' }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <h6 class="mb-0 text-dark">{{ $vendor->user->name ?? 'N/A' }}</h6>
                                <span class="text-muted extra-small d-block"><i class="fa-solid fa-phone me-1"></i>{{ $vendor->user->number ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="fw-bold">{{ $vendor->commission_rate }}%</span>
                        </td>
                        <td class="text-center">
                            @if($vendor->status === 'approved')
                                <span class="badge bg-success-soft text-success rounded-pill px-3">Approved</span>
                            @elseif($vendor->status === 'suspended')
                                <span class="badge bg-danger-soft text-danger rounded-pill px-3">Suspended</span>
                            @else
                                <span class="badge bg-warning-soft text-warning rounded-pill px-3">Pending</span>
                            @endif
                        </td>
                        <td class="text-end px-4">
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm rounded-pill px-3 edit-btn"
                                        data-vendor="{{ json_encode([
                                            'id' => $vendor->id,
                                            'store_name' => $vendor->store_name,
                                            'store_description' => $vendor->store_description,
                                            'commission_rate' => $vendor->commission_rate,
                                            'status' => $vendor->status
                                        ]) }}">
                                    <i class="fa-solid fa-pen me-1"></i>Edit
                                </button>
                                <form action="{{ route('admin.vendors.destroy', $vendor->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Remove vendor status for this store?')">
                                        <i class="fa-solid fa-trash me-1"></i>Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-users-slash fa-3x mb-3 d-block opacity-25"></i>
                            No vendors registered yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vendors->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $vendors->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Add Vendor Modal -->
<div class="modal fade" id="addVendorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Register Vendor Store</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.vendors.store') }}" method="POST">
                @csrf
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">User ID</label>
                        <input type="number" name="user_id" class="form-control rounded-pill px-3" placeholder="Enter User ID (e.g. 346)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Store Name</label>
                        <input type="text" name="store_name" class="form-control rounded-pill px-3" placeholder="Enter Store Name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Store Description</label>
                        <textarea name="store_description" class="form-control rounded-4 p-3" rows="3" placeholder="Optional details..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Commission Rate (%)</label>
                            <input type="number" step="0.01" name="commission_rate" class="form-control rounded-pill px-3" value="10.00" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Initial Status</label>
                            <select name="status" class="form-select rounded-pill px-3" required>
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Register Store</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Vendor Modal -->
<div class="modal fade" id="editVendorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Edit Vendor Store</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Store Name</label>
                        <input type="text" name="store_name" id="edit_store_name" class="form-control rounded-pill px-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Store Description</label>
                        <textarea name="store_description" id="edit_store_description" class="form-control rounded-4 p-3" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Commission Rate (%)</label>
                            <input type="number" step="0.01" name="commission_rate" id="edit_commission_rate" class="form-control rounded-pill px-3" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" id="edit_status" class="form-select rounded-pill px-3" required>
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = new bootstrap.Modal(document.getElementById('editVendorModal'));
        const editForm = document.getElementById('editForm');
        
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const data = JSON.parse(this.dataset.vendor);
                editForm.action = `/admin/services/vendors/${data.id}/update`;
                document.getElementById('edit_store_name').value = data.store_name;
                document.getElementById('edit_store_description').value = data.store_description || '';
                document.getElementById('edit_commission_rate').value = data.commission_rate;
                document.getElementById('edit_status').value = data.status;
                editModal.show();
            });
        });
    });
</script>
@endsection
