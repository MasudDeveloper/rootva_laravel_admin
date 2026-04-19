@extends('layouts.admin')

@section('title', 'Reselling Shop')
@section('page_title', 'Shop Inventory')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Products: {{ $products->total() }}</h4>
            <p class="text-muted small mb-0">Manage items for the user reselling marketplace</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.product-categories.index') }}" class="btn btn-outline-primary rounded-pill px-4">
                <i class="fa-solid fa-tags me-2"></i>Manage Categories
            </a>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fa-solid fa-cart-plus me-2"></i>Add Product
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="row g-4">
        @forelse($products as $product)
        <div class="col-md-3">
            <div class="card-modern h-100 border-0 shadow-sm overflow-hidden d-flex flex-column">
                <div class="position-relative">
                    @php
                        $imgUrl = $product->image ? (str_starts_with($product->image, '/') ? asset($product->image) : asset('uploads/products/' . $product->image)) : null;
                    @endphp
                    @if($imgUrl)
                        <img src="{{ $imgUrl }}" class="card-img-top" style="height: 180px; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="bg-gray-100 d-flex align-items-center justify-content-center" style="height: 180px; display:none !important">
                            <i class="fa-solid fa-image fa-3x text-muted opacity-25"></i>
                        </div>
                    @else
                        <div class="bg-gray-100 d-flex align-items-center justify-content-center" style="height: 180px">
                            <i class="fa-solid fa-image fa-3x text-muted opacity-25"></i>
                        </div>
                    @endif
                    <div class="position-absolute top-0 start-0 p-2">
                        <span class="badge bg-white text-dark shadow-sm rounded-pill small px-2">
                            {{ $product->category->name ?? 'None' }}
                        </span>
                    </div>
                    <div class="position-absolute top-0 end-0 p-2">
                        <div class="d-flex flex-column gap-1">
                            <button type="button" 
                                    class="btn btn-white btn-sm rounded-circle shadow-sm text-primary edit-btn"
                                    data-product="{{ json_encode([
                                        'id' => $product->id,
                                        'name' => $product->name,
                                        'category_id' => $product->category_id,
                                        'price' => $product->price,
                                        'reselling_price' => $product->reselling_price,
                                        'description' => $product->description,
                                        'images' => $product->images
                                    ]) }}">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-white btn-sm rounded-circle shadow-sm text-danger" onclick="return confirm('Delete this product?')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="p-3 flex-grow-1">
                    <h6 class="fw-bold text-dark mb-1">{{ $product->name }}</h6>
                    <p class="text-muted small mb-2" style="height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">{{ $product->description }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="extra-small text-muted">Cost Price</div>
                            <div class="fw-bold">৳{{ number_format($product->price, 0) }}</div>
                        </div>
                        <div class="text-end">
                            <div class="extra-small text-muted">Resell Price</div>
                            <div class="fw-bold text-success">৳{{ number_format($product->reselling_price, 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-light border-top text-center">
                    <span class="badge bg-primary-soft text-primary rounded-pill px-3">
                        Profit: ৳{{ number_format($product->reselling_price - $product->price, 0) }}
                    </span>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <i class="fa-solid fa-shop fa-4x mb-3 d-block opacity-25"></i>
            <h5 class="text-muted">No products in the shop.</h5>
        </div>
        @endforelse
    </div>

    @if($products->hasPages())
    <div class="mt-4 d-flex justify-content-center">
        {{ $products->links() }}
    </div>
    @endif
</div>

{{-- Add Product Modal --}}
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-5">
                            <div class="mb-3 text-center">
                                <label class="form-label small fw-bold d-block">Product Images (Multiple)</label>
                                <div id="addPasteArea" class="image-preview mb-2 bg-light rounded-4 d-flex align-items-center justify-content-center mx-auto overflow-hidden position-relative flex-wrap gap-2 p-2" style="width: 100%; min-height: 250px; border: 2px dashed #ddd; cursor: pointer;">
                                    <div id="addImgPlaceholder">
                                        <i class="fa-solid fa-cloud-arrow-up fa-2x text-muted d-block mb-2"></i>
                                        <span class="extra-small text-muted">Click to upload or<br><b>Ctrl+V</b> to paste</span>
                                    </div>
                                    <div id="addImgPreviewContainer" class="d-flex flex-wrap gap-2 w-100 justify-content-center"></div>
                                </div>
                                <input type="file" name="images[]" id="addProductImgInput" class="form-control form-control-sm rounded-pill" accept="image/*" multiple>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Category</label>
                                <select name="category_id" class="form-select rounded-3 shadow-sm" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Product Name</label>
                                <input type="text" name="name" class="form-control rounded-3 shadow-sm" required>
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Cost Price (৳)</label>
                                    <input type="number" name="price" class="form-control rounded-3 shadow-sm" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Resell Price (৳)</label>
                                    <input type="number" name="reselling_price" class="form-control rounded-3 shadow-sm" required>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label small fw-bold">Description</label>
                                <textarea name="description" class="form-control rounded-3 shadow-sm" rows="4" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Product Modal --}}
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProductForm" action="" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-5">
                            <div class="mb-3 text-center">
                                <label class="form-label small fw-bold d-block">Change Images (Multiple)</label>
                                <div id="editPasteArea" class="image-preview mb-2 bg-light rounded-4 d-flex align-items-center justify-content-center mx-auto overflow-hidden position-relative flex-wrap gap-2 p-2" style="width: 100%; min-height: 250px; border: 2px dashed #ddd; cursor: pointer;">
                                    <div id="editImgPreviewContainer" class="d-flex flex-wrap gap-2 w-100 justify-content-center"></div>
                                </div>
                                <input type="file" name="images[]" id="editProductImgInput" class="form-control form-control-sm rounded-pill" accept="image/*" multiple>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Category</label>
                                <select name="category_id" id="edit_p_category" class="form-select rounded-3 shadow-sm" required>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Product Name</label>
                                <input type="text" name="name" id="edit_p_name" class="form-control rounded-3 shadow-sm" required>
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Cost Price (৳)</label>
                                    <input type="number" name="price" id="edit_p_price" class="form-control rounded-3 shadow-sm" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Resell Price (৳)</label>
                                    <input type="number" name="reselling_price" id="edit_p_resell" class="form-control rounded-3 shadow-sm" required>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label small fw-bold">Description</label>
                                <textarea name="description" id="edit_p_desc" class="form-control rounded-3 shadow-sm" rows="4" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-gray-100 { background-color: #f3f4f6; }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .extra-small { font-size: 0.7rem; }
    .card-modern { border-radius: 1rem; transition: transform 0.2s; }
    .card-modern:hover { transform: translateY(-5px); }
    .btn-white { background: #fff; color: #333; }
</style>

@section('scripts')
<script>
    // Paste Image Logic
    function setupPaste(areaId, previewContainerId, inputId, placeholderId) {
        const pasteArea = document.getElementById(areaId);
        const previewContainer = document.getElementById(previewContainerId);
        const input = document.getElementById(inputId);
        const placeholder = placeholderId ? document.getElementById(placeholderId) : null;

        function updatePreviews(files) {
            previewContainer.innerHTML = '';
            if (files.length > 0) {
                if (placeholder) placeholder.style.display = "none";
                Array.from(files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '80px';
                        img.style.height = '80px';
                        img.style.objectFit = 'cover';
                        img.classList.add('rounded-3', 'shadow-sm');
                        previewContainer.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                });
            } else {
                if (placeholder) placeholder.style.display = "block";
            }
        }

        pasteArea.addEventListener("paste", function(e) {
            let items = e.clipboardData.items;
            let container = new DataTransfer();
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf("image") !== -1) {
                    let file = items[i].getAsFile();
                    container.items.add(file);
                }
            }
            if (container.items.length > 0) {
                input.files = container.files;
                updatePreviews(input.files);
            }
        });

        pasteArea.addEventListener("click", (e) => {
            if (e.target.tagName !== 'INPUT') input.click();
        });

        input.addEventListener("change", function() {
            updatePreviews(this.files);
        });
    }

    setupPaste('addPasteArea', 'addImgPreviewContainer', 'addProductImgInput', 'addImgPlaceholder');
    setupPaste('editPasteArea', 'editImgPreviewContainer', 'editProductImgInput');

    // Edit Product Event Listener
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const product = JSON.parse(this.getAttribute('data-product'));
            
            document.getElementById('edit_p_name').value = product.name;
            document.getElementById('edit_p_category').value = product.category_id;
            document.getElementById('edit_p_price').value = product.price;
            document.getElementById('edit_p_resell').value = product.reselling_price;
            document.getElementById('edit_p_desc').value = product.description;
            
            const container = document.getElementById('editImgPreviewContainer');
            container.innerHTML = '';
            
            if (product.images && product.images.length > 0) {
                product.images.forEach(imgUrl => {
                    const fullUrl = imgUrl.startsWith('http') ? imgUrl : (imgUrl.startsWith('/') ? `{{ asset('') }}${imgUrl.substring(1)}` : `{{ asset('uploads/products') }}/${imgUrl}`);
                    const img = document.createElement('img');
                    img.src = fullUrl;
                    img.style.width = '80px';
                    img.style.height = '80px';
                    img.style.objectFit = 'cover';
                    img.classList.add('rounded-3', 'shadow-sm');
                    container.appendChild(img);
                });
            }

            // Corrected target URL with /admin prefix
            document.getElementById('editProductForm').action = `{{ url('/admin/services/products') }}/${product.id}`;
            new bootstrap.Modal(document.getElementById('editProductModal')).show();
        });
    });
</script>
@endsection
@endsection
