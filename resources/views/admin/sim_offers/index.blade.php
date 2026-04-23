@extends('layouts.admin')

@section('page_title', 'Mobile Package Management')

@section('content')
<div class="fade-in">

    {{-- Tab Navigation --}}
    <ul class="nav nav-pills mb-4 gap-2" id="simTabs">
        <li class="nav-item">
            <button class="nav-link active px-4 rounded-pill" onclick="switchTab('list')">
                <i class="fa-solid fa-list me-2"></i>Offer List
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-4 rounded-pill" onclick="switchTab('requests')">
                <i class="fa-solid fa-envelope-open-text me-2"></i>Requests
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-4 rounded-pill" onclick="switchTab('paste')">
                <i class="fa-solid fa-paste me-2"></i>Paste & Parse
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-4 rounded-pill" onclick="switchTab('settings')">
                <i class="fa-solid fa-gear me-2"></i>Settings
            </button>
        </li>
    </ul>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    {{-- ===== TAB 1: Offer List ===== --}}
    <div id="tab-list">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Available SIM Offers</h4>
                <p class="text-muted small mb-0">Manage mobile data and minute packages across operators.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openAddModal()">
                <i class="fa-solid fa-plus me-2"></i>Add New Offer
            </button>
        </div>

        <div class="card-modern p-0 overflow-hidden shadow-lg border-0 mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-muted small text-uppercase fw-bold border-0">Operator</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold border-0">Package Details</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold border-0 text-center">Price</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold border-0 text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($offers as $offer)
                            <tr>
                                <td class="px-4">
                                    <div class="d-flex align-items-center">
                                        @php
                                            $logo = match($offer->operator_name) {
                                                'Grameenphone' => 'gp_logo.png',
                                                'Robi' => 'robi_logo.png',
                                                'Banglalink' => 'bl_logo.png',
                                                'Airtel' => 'airtel_logo.png',
                                                'Teletalk' => 'teletalk_logo.png',
                                                default => 'default_sim.png'
                                            };
                                        @endphp
                                        <img src="{{ asset('assets/img/operators/' . $logo) }}" class="me-3 rounded-circle border" width="40" height="40" alt="{{ $offer->operator_name }}">
                                        <div class="fw-bold">{{ $offer->operator_name }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark mb-0">{{ $offer->title }}</div>
                                    <div class="text-muted small">{{ $offer->offer_details }}</div>
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold text-primary">৳{{ $offer->offer_price }}</div>
                                    <div class="text-muted extra-small text-decoration-line-through">৳{{ $offer->regular_price }}</div>
                                </td>
                                <td class="text-end px-4">
                                    <div class="btn-group shadow-sm rounded-pill bg-white border p-1">
                                        <button class="btn btn-sm btn-light border-0 rounded-circle text-primary me-1" 
                                                onclick="copyOffer('{{ addslashes($offer->operator_name) }}', '{{ addslashes($offer->title) }}', '{{ addslashes($offer->offer_details) }}', {{ $offer->regular_price }}, {{ $offer->offer_price }})"
                                                title="Copy & Add New">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                        <form action="{{ route('admin.sim-offers.destroy', $offer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this offer?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border-0 rounded-circle text-danger">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-5 text-muted">No SIM offers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $offers->appends(['requests_page' => $requests->currentPage()])->links() }}
    </div>

    {{-- ===== TAB 2: Requests ===== --}}
    <div id="tab-requests" style="display:none;">
        <div class="card-modern p-4 shadow-lg">
            <h5 class="fw-bold mb-4">📥 SIM Offer Requests</h5>
            <div class="table-responsive rounded-3 border shadow-sm mb-3">
                <table class="table table-hover align-middle mb-0 bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-muted small text-uppercase fw-bold border-0">User</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold border-0">Offer</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold border-0">Phone</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold border-0">Price</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold border-0 text-center">Status</th>
                            <th class="py-3 text-muted small text-uppercase fw-bold border-0 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr>
                                <td class="px-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary-soft text-primary rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold" style="width:32px; height:32px; font-size:12px;">
                                            {{ substr($req->user->name ?? 'U', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold small">{{ $req->user->name ?? 'Unknown' }}</div>
                                            <div class="text-muted extra-small">{{ $req->user->number ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-bold text-truncate" style="max-width:200px">{{ $req->offer->title ?? 'Deleted Offer' }}</div>
                                    <div class="extra-small text-muted">{{ $req->offer->operator_name ?? '' }}</div>
                                </td>
                                <td class="small fw-bold">{{ $req->phone_number }}</td>
                                <td class="small">৳{{ $req->price }}</td>
                                <td class="text-center">
                                    @php
                                        $badgeClass = match($req->status) {
                                            'pending' => 'bg-warning-soft text-warning',
                                            'approved' => 'bg-success-soft text-success',
                                            'rejected' => 'bg-danger-soft text-danger',
                                            'confirmed' => 'bg-info-soft text-info',
                                            default => 'bg-secondary-soft text-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} rounded-pill px-3">{{ ucfirst($req->status) }}</span>
                                </td>
                                <td class="text-end">
                                    @if($req->status == 'pending')
                                        <div class="btn-group">
                                            <form action="{{ route('admin.sim-offers.update-request-status', $req->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 me-1">Approve</button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-danger rounded-pill px-3" 
                                                    onclick="showRejectModal({{ $req->id }})">Reject</button>
                                        </div>
                                    @elseif($req->status == 'rejected')
                                        <span class="text-muted extra-small" title="{{ $req->reject_reason }}">Reason: {{ Str::limit($req->reject_reason, 15) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-5 text-muted small">No requests found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $requests->appends(['offers_page' => $offers->currentPage()])->links() }}
        </div>
    </div>

    {{-- ===== TAB 3: Paste & Parse ===== --}}
    <div id="tab-paste" style="display:none;">
        <div class="card-modern p-4 shadow-lg">
            <h5 class="fw-bold mb-1">📋 Paste & Parse Offers</h5>
            <p class="text-muted small mb-4">অফার লিস্ট paste করো। প্রতিটি লাইনে একটি অফার। Format: <code class="text-primary">1987👉1976TK 180GB 1200M 180D</code></p>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Operator</label>
                    <select id="paste_operator" class="form-select rounded-3 shadow-sm" onchange="parseOffers()">
                        <option value="">Select Operator first...</option>
                        <option value="Grameenphone">Grameenphone</option>
                        <option value="Robi">Robi</option>
                        <option value="Banglalink">Banglalink</option>
                        <option value="Airtel">Airtel</option>
                        <option value="Teletalk">Teletalk</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-bold">Offer Text (প্রতি লাইনে একটি অফার)</label>
                    <textarea id="paste_textarea" class="form-control rounded-3 shadow-sm font-monospace"
                        rows="6"
                        placeholder="উদাহরণ:&#10;1987👉1976TK 180GB 1200M 180D&#10;1187👉1181TK 60GB 600M 180D&#10;498👉495TK 30GB 300M 30D"
                        oninput="parseOffers()"></textarea>
                </div>
            </div>

            {{-- Parse Preview --}}
            <div id="parse_preview" style="display:none;">
                <hr class="my-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">✅ Preview — এখানে edit করে তারপর Save করো</h6>
                    <span class="badge bg-primary-soft text-primary rounded-pill px-3" id="parse_count_badge"></span>
                </div>

                <form action="{{ route('admin.sim-offers.bulk-store') }}" method="POST" id="bulkSaveForm">
                    @csrf
                    <input type="hidden" name="operator_name" id="bulk_operator_hidden">

                    <div class="table-responsive rounded-3 border shadow-sm mb-3">
                        <table class="table table-sm table-hover align-middle mb-0 bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-3 text-muted small text-uppercase fw-bold border-0">#</th>
                                    <th class="py-3 text-muted small text-uppercase fw-bold border-0">Raw Text</th>
                                    <th class="py-3 text-muted small text-uppercase fw-bold border-0">Title (Edit করো)</th>
                                    <th class="py-3 text-muted small text-uppercase fw-bold border-0">Details</th>
                                    <th class="py-3 text-muted small text-uppercase fw-bold border-0 text-center" style="width:110px">Regular</th>
                                    <th class="py-3 text-muted small text-uppercase fw-bold border-0 text-center" style="width:110px">Offer</th>
                                </tr>
                            </thead>
                            <tbody id="parse_table_body">
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success rounded-pill px-5 shadow-sm">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Bulk Save All
                        </button>
                        <button type="button" class="btn btn-light rounded-pill px-4" onclick="clearPaste()">
                            <i class="fa-solid fa-eraser me-2"></i>Clear
                        </button>
                    </div>
                </form>
            </div>

            <div id="parse_empty" class="text-center py-5 text-muted" style="display:none;">
                <i class="fa-solid fa-triangle-exclamation fa-2x mb-2 d-block opacity-50 text-warning"></i>
                কোনো valid অফার সনাক্ত করা যায়নি। Format চেক করো।
            </div>
        </div>
    </div>

    {{-- ===== TAB 4: Settings ===== --}}
    <div id="tab-settings" style="display:none;">
        <div class="card-modern p-4 shadow-lg">
            <h5 class="fw-bold mb-4">⚙️ SIM Offer Settings</h5>
            
            <form action="{{ route('admin.sim-offers.update-settings') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-4 p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold mb-1">Service Status</h6>
                                    <p class="text-muted small mb-0">Enable or disable SIM offers globally</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input h4" type="checkbox" name="status" 
                                           {{ ($settings && $settings->status) ? 'checked' : '' }} id="serviceStatus">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-4 p-4 h-100">
                            <h6 class="fw-bold mb-3">Scrolling Notice</h6>
                            <textarea name="notice_text" class="form-control rounded-3 border-0 shadow-sm p-3" 
                                      rows="3" placeholder="Enter scrolling notice here...">{{ $settings->notice_text ?? '' }}</textarea>
                            <p class="text-muted extra-small mt-2 mb-0">This text will scroll at the top of the SIM Offer screen in the app.</p>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm">
                            <i class="fa-solid fa-save me-2"></i>Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add / Copy Offer Modal --}}
<div class="modal fade" id="addOfferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="fw-bold mb-0" id="modalTitle">Add SIM Offer</h5>
                    <p class="text-muted extra-small mb-0" id="modalSubtitle"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.sim-offers.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Operator Name</label>
                        <select name="operator_name" id="modal_operator" class="form-select rounded-3 shadow-sm" required>
                            <option value="Grameenphone">Grameenphone</option>
                            <option value="Robi">Robi</option>
                            <option value="Banglalink">Banglalink</option>
                            <option value="Airtel">Airtel</option>
                            <option value="Teletalk">Teletalk</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Offer Title</label>
                        <input type="text" name="title" id="modal_title" class="form-control rounded-3 shadow-sm" placeholder="e.g. 50GB + 1000 Min" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Offer Details</label>
                        <textarea name="offer_details" id="modal_details" class="form-control rounded-3 shadow-sm" rows="3"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Regular Price</label>
                            <input type="number" name="regular_price" id="modal_regular_price" class="form-control rounded-3 shadow-sm" step="0.01" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Offer Price</label>
                            <input type="number" name="offer_price" id="modal_offer_price" class="form-control rounded-3 shadow-sm" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Save Offer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectRequestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold mb-0">Reject Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="rejectForm">
                @csrf
                <input type="hidden" name="status" value="rejected">
                <div class="modal-body p-4">
                    <label class="form-label small fw-bold">Reject Reason</label>
                    <textarea name="reject_reason" class="form-control rounded-3 shadow-sm" rows="3" required placeholder="e.g. Invalid number or technical issue"></textarea>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 shadow-sm">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-gray-50 { background-color: #f9fafb; }
    .extra-small { font-size: 0.75rem; }
    .card-modern { border-radius: 1.25rem; }
    .nav-pills .nav-link { color: var(--primary); }
    .nav-pills .nav-link.active { background: var(--primary); color: white; }
    .font-monospace { font-family: 'Courier New', monospace; font-size: 0.85rem; }
    .bg-primary-soft { background-color: rgba(var(--primary-rgb), 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .bg-secondary-soft { background-color: rgba(108, 117, 125, 0.1); }
</style>

@endsection

@section('scripts')
<script>
    // ─── Tab switching ───────────────────────────────────────────
    function switchTab(tab) {
        document.getElementById('tab-list').style.display     = (tab === 'list')     ? '' : 'none';
        document.getElementById('tab-requests').style.display = (tab === 'requests') ? '' : 'none';
        document.getElementById('tab-paste').style.display    = (tab === 'paste')    ? '' : 'none';
        document.getElementById('tab-settings').style.display = (tab === 'settings') ? '' : 'none';
        
        document.querySelectorAll('#simTabs .nav-link').forEach((btn, i) => {
            btn.classList.toggle('active', 
                (i === 0 && tab === 'list') || 
                (i === 1 && tab === 'requests') || 
                (i === 2 && tab === 'paste') || 
                (i === 3 && tab === 'settings')
            );
        });
    }

    // ─── Single Add / Copy Modal ─────────────────────────────────
    function openAddModal() {
        document.getElementById('modalTitle').textContent    = 'Add SIM Offer';
        document.getElementById('modalSubtitle').textContent = '';
        document.getElementById('modal_operator').value      = 'Grameenphone';
        document.getElementById('modal_title').value         = '';
        document.getElementById('modal_details').value       = '';
        document.getElementById('modal_regular_price').value = '';
        document.getElementById('modal_offer_price').value   = '';
        new bootstrap.Modal(document.getElementById('addOfferModal')).show();
    }

    function copyOffer(operator, title, details, regularPrice, offerPrice) {
        document.getElementById('modalTitle').textContent    = '📋 Copy & Add Offer';
        document.getElementById('modalSubtitle').textContent = 'Pre-filled from existing offer. Modify and save.';
        document.getElementById('modal_operator').value      = operator;
        document.getElementById('modal_title').value         = title;
        document.getElementById('modal_details').value       = details;
        document.getElementById('modal_regular_price').value = regularPrice;
        document.getElementById('modal_offer_price').value   = offerPrice;
        new bootstrap.Modal(document.getElementById('addOfferModal')).show();
    }

    function showRejectModal(requestId) {
        const form = document.getElementById('rejectForm');
        form.action = `/admin/services/sim-offers/requests/${requestId}/update`;
        new bootstrap.Modal(document.getElementById('rejectRequestModal')).show();
    }

    // ─── Paste & Parse Logic ─────────────────────────────────────
    function makeBanglaTitle(gb, minutes, days) {
        let title = '✅ ';
        if (gb)      title += gb.replace('GB', ' জিবি') + ' ';
        if (minutes) title += '📳 ' + minutes.replace(/M$/i, '') + ' মিনিট ';
        if (days)    title += '➡️ ' + days.replace(/D$/i, '') + ' দিন';
        return title.trim();
    }

    function parseOfferLine(line) {
        line = line.replace(/[→►\-\>]/g, '👉');
        const match = line.match(/^\s*(\d+)\s*👉\s*(\d+)[Tt][Kk]\s+(.+)$/u);
        if (!match) return null;

        const regular = match[1];
        const offer   = match[2];
        const rest    = match[3].trim();

        const gbMatch  = rest.match(/(আনলিমিটেডGB|আনলিমিটেড GB|\d+GB)/iu);
        const minMatch = rest.match(/(\d+)M\b/i);
        const dayMatch = rest.match(/(\d+)D\b/i);

        const gb      = gbMatch  ? gbMatch[1]  : '';
        const minutes = minMatch ? minMatch[1] + 'M' : '';
        const days    = dayMatch ? dayMatch[1] + 'D' : '';

        return {
            raw:           line,
            regular_price: regular,
            offer_price:   offer,
            title:         makeBanglaTitle(gb, minutes, days),
            offer_details: [gb, minutes, days].filter(Boolean).join(' ')
        };
    }

    function parseOffers() {
        const operator = document.getElementById('paste_operator').value;
        const text     = document.getElementById('paste_textarea').value;
        const lines    = text.split(/\r?\n/);
        const results  = lines.map(l => parseOfferLine(l.trim())).filter(Boolean);

        const preview = document.getElementById('parse_preview');
        const empty   = document.getElementById('parse_empty');
        const tbody   = document.getElementById('parse_table_body');

        if (results.length === 0) {
            preview.style.display = 'none';
            empty.style.display   = text.trim().length > 5 ? '' : 'none';
            return;
        }

        empty.style.display   = 'none';
        preview.style.display = '';
        document.getElementById('parse_count_badge').textContent = results.length + ' টি অফার';
        document.getElementById('bulk_operator_hidden').value    = operator;

        tbody.innerHTML = results.map((r, i) => `
            <tr>
                <td class="px-3 py-2 text-muted small">${i + 1}</td>
                <td class="py-2" style="max-width:160px; font-size:0.75rem; color:#6c757d; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                    title="${escHtml(r.raw)}">${escHtml(r.raw)}</td>
                <td class="py-2">
                    <input type="text" name="title[]" value="${escHtml(r.title)}"
                        class="form-control form-control-sm rounded-2" required>
                </td>
                <td class="py-2">
                    <input type="text" name="offer_details[]" value="${escHtml(r.offer_details)}"
                        class="form-control form-control-sm rounded-2">
                </td>
                <td class="py-2">
                    <input type="number" name="regular_price[]" value="${r.regular_price}"
                        class="form-control form-control-sm rounded-2 text-center" required>
                </td>
                <td class="py-2">
                    <input type="number" name="offer_price[]" value="${r.offer_price}"
                        class="form-control form-control-sm rounded-2 text-center" required>
                </td>
            </tr>`
        ).join('');
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function clearPaste() {
        document.getElementById('paste_textarea').value = '';
        document.getElementById('paste_operator').value = '';
        document.getElementById('parse_preview').style.display = 'none';
        document.getElementById('parse_empty').style.display   = 'none';
    }

    document.getElementById('bulkSaveForm').addEventListener('submit', function(e) {
        const op = document.getElementById('bulk_operator_hidden').value;
        if (!op) {
            e.preventDefault();
            alert('অনুগ্রহ করে প্রথমে Operator select করুন।');
        }
    });
</script>
@endsection
