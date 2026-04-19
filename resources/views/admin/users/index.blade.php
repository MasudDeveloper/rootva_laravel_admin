@extends('layouts.admin')

@section('title', 'All Users')
@section('page_title', 'User Management')

@section('content')
<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Total Members: {{ $users->total() }}</h4>
            <p class="text-muted small mb-0">Manage and verify registered users</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card-modern mb-4">
        <form action="{{ route('admin.users.index') }}" method="GET" class="row g-3">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control rounded-pill border-0 bg-light px-4" 
                       placeholder="Search by Mobile, Refer ID or Name..." value="{{ $search }}">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select rounded-pill border-0 bg-light px-4">
                    <option value="">All Statuses</option>
                    <option value="0" {{ "$status" === "0" ? 'selected' : '' }}>Unverified</option>
                    <option value="1" {{ "$status" === "1" ? 'selected' : '' }}>Verified</option>
                    <option value="2" {{ "$status" === "2" ? 'selected' : '' }}>Pending</option>
                    <option value="3" {{ "$status" === "3" ? 'selected' : '' }}>Demo Verified</option>
                    <option value="4" {{ "$status" === "4" ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 rounded-pill">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="card-modern p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">User</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Contact</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Refer ID</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Status</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-end">Balance</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                @if($user->profile_pic_url)
                                    <img src="{{ $user->profile_pic_url }}" class="rounded-circle shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                                @else
                                    <div class="bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold">{{ $user->name }}</div>
                                    <div class="text-muted small">ID: #{{ $user->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="fw-medium">{{ $user->number }}</div>
                            <div class="text-muted small">{{ $user->email }}</div>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-light text-dark fw-bold px-3 py-2 rounded-pill shadow-sm">
                                {{ $user->referCode }}
                            </span>
                        </td>
                        <td class="py-3">
                            @php
                                $statusMap = [
                                    0 => ['label' => 'Unverified', 'class' => 'bg-danger-soft text-danger border-danger'],
                                    1 => ['label' => 'Verified', 'class' => 'bg-success-soft text-success border-success'],
                                    2 => ['label' => 'Pending', 'class' => 'bg-warning-soft text-warning border-warning'],
                                    3 => ['label' => 'Demo', 'class' => 'bg-info-soft text-info border-info'],
                                    4 => ['label' => 'Suspended', 'class' => 'bg-secondary text-white'],
                                ];
                                $s = $statusMap[$user->is_verified] ?? ['label' => 'Unknown', 'class' => 'bg-light'];
                            @endphp
                            <span class="badge {{ $s['class'] }} px-3 py-2 rounded-pill">
                                {{ $s['label'] }}
                            </span>
                        </td>
                        <td class="py-3 text-end fw-bold text-primary">
                            ৳{{ number_format($user->wallet_balance, 2) }}
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="dropdown">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-primary-soft btn-sm rounded-pill px-3">
                                    <i class="fa-solid fa-eye me-1"></i>View
                                </a>
                                <button class="btn btn-light btn-sm rounded-circle shadow-sm" data-bs-toggle="dropdown">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                                    <li><a class="dropdown-item p-2 px-3" href="{{ route('admin.users.show', $user->id) }}"><i class="fa-solid fa-eye me-2 text-primary"></i> View Details</a></li>
                                    <li><a class="dropdown-item p-2 px-3" href="{{ route('admin.users.show', $user->id) }}#edit"><i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Edit User</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_verified" value="4">
                                            <button type="submit" class="dropdown-item p-2 px-3 text-danger" onclick="return confirm('Suspend {{ addslashes($user->name) }}?')"><i class="fa-solid fa-ban me-2"></i> Suspend User</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-user-slash fa-3x mb-3 d-block opacity-25"></i>
                            No users found matching your search.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
        <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
