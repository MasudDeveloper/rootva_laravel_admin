@extends('layouts.admin')

@section('title', 'Product Categories')
@section('page_title', 'Manage Shop Categories')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Categories: {{ count($categories) }}</h4>
            <p class="text-muted small mb-0">Organize your products into categories</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.products.index') }}" class="btn btn-light rounded-pill px-4">
                <i class="fa-solid fa-arrow-left me-2"></i>Back to Products
            </a>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fa-solid fa-plus me-2"></i>Add Category
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="card-modern border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Image</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Category Name</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Products</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @forelse($categories as $category)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="rounded-3 overflow-hidden" style="width: 50px; height: 50px;">
                                @if($category->image)
                                    <img src="{{ asset('uploads/product_categories/' . $category->image) }}" class="w-100 h-100" style="object-fit: cover;">
                                @else
                                    <div class="bg-light w-100 h-100 d-flex align-items-center justify-content-center">
                                        <i class="fa-solid fa-image text-muted opacity-25"></i>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="py-3">
                            <span class="fw-bold text-dark">{{ $category->name }}</span>
                        </td>
                        <td class="py-3 text-center">
                            <span class="badge bg-light text-dark rounded-pill px-3">{{ $category->products_count ?? $category->products()->count() }}</span>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <button class="btn btn-light btn-sm rounded-circle shadow-sm text-primary" 
                                    onclick="editCategory({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ asset('uploads/product_categories/' . $category->image) }}')">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form action="{{ route('admin.product-categories.destroy', $category->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-light btn-sm rounded-circle text-danger shadow-sm" onclick="return confirm('Delete category? All products in this category will be affected.')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">No categories found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add Category Modal --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.product-categories.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category Name</label>
                        <input type="text" name="name" class="form-control rounded-3 shadow-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category Image</label>
                        <input type="file" name="image" class="form-control rounded-pill shadow-sm" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Category Modal --}}
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCategoryForm" action="" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category Name</label>
                        <input type="text" name="name" id="edit_cat_name" class="form-control rounded-3 shadow-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Change Image (Optional)</label>
                        <div id="current_image_preview" class="mb-2 text-center">
                            <img src="" id="edit_cat_img_preview" class="rounded shadow-sm" style="max-height: 100px;">
                        </div>
                        <input type="file" name="image" class="form-control rounded-pill shadow-sm" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-gray-50 { background-color: #f9fafb; }
    .card-modern { border-radius: 1.25rem; }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
</style>

@section('scripts')
<script>
    function editCategory(id, name, imgPath) {
        document.getElementById('edit_cat_name').value = name;
        document.getElementById('edit_cat_img_preview').src = imgPath;
        document.getElementById('editCategoryForm').action = `{{ url('/services/product-categories') }}/${id}/update`;
        new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
    }
</script>
@endsection
@endsection
