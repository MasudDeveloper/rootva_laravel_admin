@extends('layouts.admin')

@section('title', 'PCash API Settings')
@section('page_title', 'PCash Automated API Settings')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-plug-circle-bolt text-primary me-2"></i> API Live Balance</h6>
            </div>
            <div class="card-body">
                @if(isset($apiBalance) && $apiBalance !== null)
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="fa-solid fa-wallet fs-4 me-3"></i>
                        <div>
                            <div class="small fw-bold text-uppercase">Current API Balance</div>
                            <div class="fs-4 fw-bold text-primary">{{ $apiBalance }} ৳</div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> Could not fetch API balance. Please check your credentials.
                    </div>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-gears text-primary me-2"></i> Configure PCash API</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.pcash.settings.update') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">API Username</label>
                        <input type="text" name="api_user" class="form-control" value="{{ $settings->api_user ?? '' }}" required>
                        <small class="text-muted">Username provided by flexisoftwarebd/pcashmoney</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">API Key</label>
                        <input type="text" name="api_key" class="form-control" value="{{ $settings->api_key ?? '' }}" required>
                        <small class="text-muted">Secret API Key (e.g., xxxxxxxxxxxxxxxxxxxxxxxx)</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Default Service Code</label>
                        <input type="text" name="default_service_code" class="form-control" value="{{ $settings->default_service_code ?? '64' }}" required>
                        <small class="text-muted">Usually 64 for flexiload services</small>
                    </div>

                    <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-save me-2"></i> Save Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
