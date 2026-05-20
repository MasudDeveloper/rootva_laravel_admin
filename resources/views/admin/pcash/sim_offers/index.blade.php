@extends('layouts.admin')

@section('title', 'Automated SIM Offers')
@section('page_title', 'Automated SIM Offers (PCash API)')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-sim-card text-primary me-2"></i> Manage Offers</h6>
        <a href="{{ route('admin.pcash.sim_offers.create') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Add New Offer
        </a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Operator</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($offers as $offer)
                    <tr>
                        <td>#{{ $offer->id }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ $offer->operator }}</span>
                        </td>
                        <td class="fw-semibold">{{ $offer->title }}</td>
                        <td class="text-success fw-bold">{{ $offer->price }} ৳</td>
                        <td>
                            @if($offer->type == 1)
                                <span class="badge bg-info text-dark">Prepaid</span>
                            @else
                                <span class="badge bg-warning text-dark">Postpaid</span>
                            @endif
                        </td>
                        <td>
                            @if($offer->status)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.pcash.sim_offers.edit', $offer->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('admin.pcash.sim_offers.destroy', $offer->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this offer?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No automated SIM offers found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            {{ $offers->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
