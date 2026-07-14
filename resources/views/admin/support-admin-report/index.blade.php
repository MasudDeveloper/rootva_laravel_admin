@extends('layouts.admin')

@section('title', 'Support Admin Report')
@section('page_title', 'Support Admin Transaction Report')

@section('styles')
<style>
    .card-stat {
        background: #fff;
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
        padding: 24px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card-stat:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    }
    .stat-circle {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    .nav-tabs-custom {
        border-bottom: 2px solid #f1f3f5;
        gap: 16px;
    }
    .nav-tabs-custom .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 600;
        padding: 12px 24px;
        border-radius: 8px 8px 0 0;
        position: relative;
        transition: color 0.2s ease;
    }
    .nav-tabs-custom .nav-link:hover {
        color: #495057;
    }
    .nav-tabs-custom .nav-link.active {
        color: #0d6efd;
        background: transparent;
    }
    .nav-tabs-custom .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 3px;
        background-color: #0d6efd;
        border-radius: 3px;
    }
    .table-modern th {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 2px solid #dee2e6;
    }
    .table-modern td {
        vertical-align: middle;
        font-size: 14px;
    }
    .filter-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #e9ecef;
    }
    .badge-income {
        background-color: #d1e7dd;
        color: #0f5132;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
    }
    .badge-withdraw {
        background-color: #f8d7da;
        color: #842029;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
    }
</style>
@endsection

@section('content')
<div class="fade-in">
    <!-- Stat Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-6">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small mb-1 text-uppercase fw-bold">Total Added by Support Admin</h6>
                    <h2 class="fw-extrabold mb-0 text-success">৳{{ number_format($stats['total_added'], 2) }}</h2>
                    <span class="small text-muted">All-time wallet additions</span>
                </div>
                <div class="stat-circle bg-success text-success bg-opacity-10">
                    <i class="fa-solid fa-circle-arrow-up"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-6">
            <div class="card-stat d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small mb-1 text-uppercase fw-bold">Total Withdrawn by Support Admin</h6>
                    <h2 class="fw-extrabold mb-0 text-danger">৳{{ number_format($stats['total_withdrawn'], 2) }}</h2>
                    <span class="small text-muted">All-time wallet withdrawals</span>
                </div>
                <div class="stat-circle bg-danger text-danger bg-opacity-10">
                    <i class="fa-solid fa-circle-arrow-down"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Section -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <ul class="nav nav-tabs nav-tabs-custom mb-4" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily" type="button" role="tab" aria-controls="daily" aria-selected="true">
                    <i class="fa-solid fa-calendar-days me-2"></i>Daily Report
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">
                    <i class="fa-solid fa-clock-rotate-left me-2"></i>Detailed History
                </button>
            </li>
        </ul>

        <div class="tab-content" id="reportTabsContent">
            <!-- 📅 Daily Report Tab -->
            <div class="tab-pane fade show active" id="daily" role="tabpanel" aria-labelledby="daily-tab">
                <div class="table-responsive">
                    <table class="table table-hover table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Total Added</th>
                                <th>Total Withdrawn</th>
                                <th>Net Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailyReport as $row)
                                @php
                                    $net = $row->total_added - $row->total_withdrawn;
                                @endphp
                                <tr>
                                    <td class="fw-semibold text-dark">{{ \Carbon\Carbon::parseMixed($row->transaction_date)->format('d M, Y') }}</td>
                                    <td class="text-success fw-bold">+৳{{ number_format($row->total_added, 2) }}</td>
                                    <td class="text-danger fw-bold">-৳{{ number_format($row->total_withdrawn, 2) }}</td>
                                    <td class="fw-bold {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $net >= 0 ? '+' : '' }}৳{{ number_format($net, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No daily transaction activity found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ⏳ Detailed History Tab -->
            <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                <!-- Filters -->
                <div class="filter-card mb-4">
                    <form method="GET" action="{{ route('admin.support-admin-report.index') }}#history" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Transaction Type</label>
                            <select name="type" class="form-select">
                                <option value="">All Types</option>
                                <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Add Money (+)</option>
                                <option value="withdraw" {{ request('type') == 'withdraw' ? 'selected' : '' }}>Withdraw (-)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Date</label>
                            <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary w-100 fw-bold">
                                <i class="fa-solid fa-filter me-2"></i>Filter
                            </button>
                            <a href="{{ route('admin.support-admin-report.index') }}#history" class="btn btn-outline-secondary w-100 fw-bold">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Txn ID</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Gateway</th>
                                <th>Reason/Description</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $txn)
                                <tr>
                                    <td class="text-muted fw-bold">#{{ $txn->id }}</td>
                                    <td>
                                        @if($txn->user)
                                            <a href="{{ route('admin.users.show', $txn->user->id) }}" class="text-decoration-none fw-semibold text-dark">
                                                {{ $txn->user->name }}
                                            </a>
                                            <div class="text-muted small">{{ $txn->user->number }}</div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold {{ $txn->type == 'income' ? 'text-success' : 'text-danger' }}">
                                        {{ $txn->type == 'income' ? '+' : '-' }}৳{{ number_format($txn->amount, 2) }}
                                    </td>
                                    <td>
                                        <span class="{{ $txn->type == 'income' ? 'badge-income' : 'badge-withdraw' }}">
                                            {{ $txn->type == 'income' ? 'Add' : 'Withdraw' }}
                                        </span>
                                    </td>
                                    <td class="text-muted font-monospace fw-bold">
                                        {{ str_replace('Support Admin - ', '', $txn->payment_gateway) }}
                                    </td>
                                    <td><span class="text-muted small">{{ $txn->description ?: 'No description' }}</span></td>
                                    <td class="text-muted small">
                                        {{ $txn->date ? \Carbon\Carbon::parseMixed($txn->date)->format('d M, Y - h:i A') : $txn->update_at }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No transactions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    {{ $transactions->withQueryString()->fragment('history')->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Automatically activate tab from URL hash
        var hash = window.location.hash;
        if (hash) {
            var triggerEl = document.querySelector('button[data-bs-target="' + hash + '"]');
            if (triggerEl) {
                var tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }
        }

        // Update URL hash on tab click
        var tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabEls.forEach(function (tabEl) {
            tabEl.addEventListener('shown.bs.tab', function (event) {
                window.location.hash = event.target.getAttribute('data-bs-target');
            });
        });
    });
</script>
@endsection
