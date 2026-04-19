@extends('layouts.admin')

@section('title', 'API Endpoints')
@section('page_title', 'API Documentation')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">System API Endpoints</h6>
                    <span class="badge bg-primary">{{ count($routes) }} Active Routes</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 10%;">Method</th>
                                    <th style="width: 25%;">Endpoint URL</th>
                                    <th style="width: 15%;">Handler</th>
                                    <th style="width: 50%;">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($routes as $route)
                                <tr>
                                    <td>
                                        @foreach($route['methods'] as $method)
                                            @if($method != 'HEAD')
                                                <span class="badge @if($method == 'GET') bg-success @elseif($method == 'POST') bg-primary @else bg-secondary @endif">
                                                    {{ $method }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </td>
                                    <td>
                                        <code class="text-dark">/{{ $route['uri'] }}</code>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $route['action'] }}</small>
                                    </td>
                                    <td>
                                        <p class="mb-0 small">
                                            {{ $descriptions[$route['uri']] ?? 'Standard API endpoint.' }}
                                        </p>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light py-3">
                    <div class="alert alert-info mb-0 py-2">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        <strong>Note:</strong> Endpoints ending in <code>.php</code> are maintained for backward compatibility with the legacy Android APK.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .table code {
        font-size: 0.95rem;
        padding: 0.2rem 0.4rem;
        background-color: #f8f9fa;
        border-radius: 4px;
    }
    .badge {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
    }
    .fa-spin-slow {
        animation: fa-spin 3s linear infinite;
    }
</style>
@endsection
