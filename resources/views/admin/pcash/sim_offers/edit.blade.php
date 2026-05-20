@extends('layouts.admin')

@section('title', 'Edit SIM Offer')
@section('page_title', 'Edit Automated SIM Offer')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Edit Offer #{{ $offer->id }}</h6>
                <a href="{{ route('admin.pcash.sim_offers.index') }}" class="btn btn-sm btn-light border">Back</a>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.pcash.sim_offers.update', $offer->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title/Package Name</label>
                        <input type="text" name="title" class="form-control" value="{{ $offer->title }}" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Operator</label>
                            <select name="operator" class="form-select" required>
                                <option value="GP" {{ $offer->operator == 'GP' ? 'selected' : '' }}>Grameenphone (GP)</option>
                                <option value="RB" {{ $offer->operator == 'RB' ? 'selected' : '' }}>Robi (RB)</option>
                                <option value="AT" {{ $offer->operator == 'AT' ? 'selected' : '' }}>Airtel (AT)</option>
                                <option value="BL" {{ $offer->operator == 'BL' ? 'selected' : '' }}>Banglalink (BL)</option>
                                <option value="TT" {{ $offer->operator == 'TT' ? 'selected' : '' }}>Teletalk (TT)</option>
                                <option value="SK" {{ $offer->operator == 'SK' ? 'selected' : '' }}>Skitto (SK)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Connection Type</label>
                            <select name="type" class="form-select" required>
                                <option value="1" {{ $offer->type == 1 ? 'selected' : '' }}>Prepaid</option>
                                <option value="2" {{ $offer->type == 2 ? 'selected' : '' }}>Postpaid</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Price (৳)</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="{{ $offer->price }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="1" {{ $offer->status == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ $offer->status == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="3">{{ $offer->description }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary px-4">Update Offer</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
