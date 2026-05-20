@extends('layouts.admin')

@section('title', 'Add SIM Offer')
@section('page_title', 'Add Automated SIM Offer')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Create New Offer</h6>
                <a href="{{ route('admin.pcash.sim_offers.index') }}" class="btn btn-sm btn-light border">Back</a>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.pcash.sim_offers.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title/Package Name</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. 500 Min + 10 GB" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Operator</label>
                            <select name="operator" class="form-select" required>
                                <option value="GP">Grameenphone (GP)</option>
                                <option value="RB">Robi (RB)</option>
                                <option value="AT">Airtel (AT)</option>
                                <option value="BL">Banglalink (BL)</option>
                                <option value="TT">Teletalk (TT)</option>
                                <option value="SK">Skitto (SK)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Connection Type</label>
                            <select name="type" class="form-select" required>
                                <option value="1">Prepaid</option>
                                <option value="2">Postpaid</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price (৳)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Validity, details, etc."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary px-4">Create Offer</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
