@extends('layouts.admin')

@section('title', 'Leaders List')
@section('page_title', 'Leaders List')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Leaders List</h4>
            <p class="text-muted small mb-0">Overview of all users with leadership rewards</p>
        </div>
        <form action="{{ route('admin.leadership.leaders') }}" method="GET" class="d-flex gap-2">
            <input type="hidden" name="filter" value="{{ $filter ?? 'rootva' }}">
            <input type="text" name="search" class="form-control rounded-pill px-3" placeholder="Search by name, number, refer code..." value="{{ $search ?? '' }}">
            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-search me-2"></i>Search
            </button>
            @if($search)
                <a href="{{ route('admin.leadership.leaders', ['filter' => $filter]) }}" class="btn btn-light border rounded-pill px-3 shadow-sm">Clear</a>
            @endif
        </form>
    </div>

    {{-- Level Filters --}}
    <div class="card-modern border-0 shadow-sm p-3 mb-4 overflow-auto">
        <div class="d-flex gap-2 flex-nowrap align-items-center">
            <span class="text-muted small fw-bold me-2"><i class="fa-solid fa-filter me-1"></i> Sort By:</span>
            <a href="{{ route('admin.leadership.leaders', ['filter' => 'rootva', 'search' => $search]) }}" class="btn {{ ($filter ?? 'rootva') === 'rootva' ? 'btn-primary' : 'btn-light border text-muted' }} rounded-pill px-4 btn-sm fw-bold">Rootva Leader</a>
            <a href="{{ route('admin.leadership.leaders', ['filter' => 'silver', 'search' => $search]) }}" class="btn {{ ($filter ?? '') === 'silver' ? 'btn-primary' : 'btn-light border text-muted' }} rounded-pill px-4 btn-sm fw-bold">Silver Leader</a>
            <a href="{{ route('admin.leadership.leaders', ['filter' => 'gold', 'search' => $search]) }}" class="btn {{ ($filter ?? '') === 'gold' ? 'btn-primary' : 'btn-light border text-muted' }} rounded-pill px-4 btn-sm fw-bold">Gold Leader</a>
            <a href="{{ route('admin.leadership.leaders', ['filter' => 'diamond', 'search' => $search]) }}" class="btn {{ ($filter ?? '') === 'diamond' ? 'btn-primary' : 'btn-light border text-muted' }} rounded-pill px-4 btn-sm fw-bold">Diamond Leader</a>
            <a href="{{ route('admin.leadership.leaders', ['filter' => 'top', 'search' => $search]) }}" class="btn {{ ($filter ?? '') === 'top' ? 'btn-primary' : 'btn-light border text-muted' }} rounded-pill px-4 btn-sm fw-bold">Top Leader</a>
        </div>
    </div>

    {{-- Leaders Table --}}
    <div class="card-modern border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-start">Leader Info</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Refer Code</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Rootva Leader</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Silver</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Gold</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Diamond</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Top</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @forelse($leaders as $leader)
                    <tr>
                        <td class="px-4 py-3 text-start">
                            <div class="fw-bold text-dark">{{ $leader->name }}</div>
                            <div class="text-muted extra-small"><i class="fa-solid fa-phone-volume me-1"></i>{{ $leader->number }}</div>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-light text-primary border rounded-pill px-3">{{ $leader->referCode }}</span>
                        </td>
                        <td class="py-3">
                            <span class="badge {{ $leader->normal_leadership > 0 ? 'bg-success' : 'bg-secondary' }} rounded-pill px-2">
                                {{ $leader->normal_leadership }}
                            </span>
                        </td>
                        <td class="py-3">
                            <span class="badge {{ $leader->silver_leadership > 0 ? 'bg-primary' : 'bg-secondary' }} rounded-pill px-2">
                                {{ $leader->silver_leadership }}
                            </span>
                        </td>
                        <td class="py-3">
                            <span class="badge {{ $leader->gold_leadership > 0 ? 'bg-warning text-dark' : 'bg-secondary' }} rounded-pill px-2">
                                {{ $leader->gold_leadership }}
                            </span>
                        </td>
                        <td class="py-3">
                            <span class="badge {{ $leader->diamond_leadership > 0 ? 'bg-info text-dark' : 'bg-secondary' }} rounded-pill px-2">
                                {{ $leader->diamond_leadership }}
                            </span>
                        </td>
                        <td class="py-3">
                            <span class="badge {{ $leader->top_leadership > 0 ? 'bg-dark' : 'bg-secondary' }} rounded-pill px-2">
                                {{ $leader->top_leadership }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.users.show', $leader->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="fa-solid fa-user me-1"></i> Profile
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No leaders found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($leaders->hasPages())
        <div class="px-4 py-3 border-top bg-light d-flex justify-content-center">
            {{ $leaders->links() }}
        </div>
        @endif
    </div>
</div>

<style>
    .bg-gray-50 { background-color: #f9fafb; }
    .card-modern { border-radius: 1.25rem; }
    .extra-small { font-size: 0.75rem; }
</style>
@endsection
