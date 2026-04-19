@extends('layouts.admin')

@section('title', 'SIM Offers')
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
            <button class="nav-link px-4 rounded-pill" onclick="switchTab('paste')">
                <i class="fa-solid fa-paste me-2"></i>Paste & Parse
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
                <h4 class="fw-bold mb-0">Active Offers: {{ $offers->total() }}</h4>
                <p class="text-muted small mb-0">Manage and update mobile data/minute packages</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="openAddModal()">
                <i class="fa-solid fa-plus me-2"></i>Add New Offer
            </button>
        </div>

        <div class="card-modern p-0 overflow-hidden border-0 shadow-lg">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">Operator</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Offer Title</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Regular Price</th>
                            <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Offer Price</th>
                            <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse($offers as $offer)
                        <tr>
                            <td class="px-4 py-3">
                                @php
                                    $colors = [
                                        'Grameenphone' => 'bg-info',
                                        'Robi'         => 'bg-danger',
                                        'Banglalink'   => 'bg-warning',
                                        'Airtel'       => 'bg-danger',
                                        'Teletalk'     => 'bg-success'
                                    ];
                                    $color = $colors[$offer->operator_name] ?? 'bg-secondary';
                                @endphp
                                <div class="{{ $color }} text-white rounded-pill px-3 py-1 small fw-bold shadow-sm d-inline-block">
                                    {{ $offer->operator_name }}
                                </div>
                            </td>
                            <td class="py-3">
                                <div class="fw-bold text-dark">{{ $offer->title }}</div>
                                <div class="text-muted extra-small">{{ Str::limit($offer->offer_details, 50) }}</div>
                            </td>
                            <td class="py-3 text-center text-muted">
                                <del>৳{{ number_format($offer->regular_price, 2) }}</del>
                            </td>
                            <td class="py-3 text-center fw-extrabold text-primary">
                                ৳{{ number_format($offer->offer_price, 2) }}
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button"
                                        class="btn btn-light btn-sm rounded-circle shadow-sm text-primary"
                                        title="Copy & Add Similar Offer"
                                        onclick="copyOffer(
                                            '{{ addslashes($offer->operator_name) }}',
                                            '{{ addslashes($offer->title) }}',
                                            '{{ addslashes($offer->offer_details) }}',
                                            '{{ $offer->regular_price }}',
                                            '{{ $offer->offer_price }}'
                                        )">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                    <form action="{{ route('admin.sim-offers.destroy', $offer->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light btn-sm rounded-circle text-danger shadow-sm" onclick="return confirm('Delete this offer?')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-sim-card fa-3x mb-3 d-block opacity-25"></i>
                                No active SIM offers found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($offers->hasPages())
            <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
                {{ $offers->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- ===== TAB 2: Paste & Parse ===== --}}
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

<style>
    .bg-gray-50 { background-color: #f9fafb; }
    .extra-small { font-size: 0.75rem; }
    .card-modern { border-radius: 1.25rem; }
    .nav-pills .nav-link { color: var(--primary); }
    .nav-pills .nav-link.active { background: var(--primary); color: white; }
    .font-monospace { font-family: 'Courier New', monospace; font-size: 0.85rem; }
</style>

@endsection

@section('scripts')
<script>
    // ─── Tab switching ───────────────────────────────────────────
    function switchTab(tab) {
        document.getElementById('tab-list').style.display  = (tab === 'list')  ? '' : 'none';
        document.getElementById('tab-paste').style.display = (tab === 'paste') ? '' : 'none';
        document.querySelectorAll('#simTabs .nav-link').forEach((btn, i) => {
            btn.classList.toggle('active', (i === 0 && tab === 'list') || (i === 1 && tab === 'paste'));
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

    // ─── Paste & Parse Logic ─────────────────────────────────────
    function makeBanglaTitle(gb, minutes, days) {
        let title = '✅ ';
        if (gb)      title += gb.replace('GB', ' জিবি') + ' ';
        if (minutes) title += '📳 ' + minutes.replace(/M$/i, '') + ' মিনিট ';
        if (days)    title += '➡️ ' + days.replace(/D$/i, '') + ' দিন';
        return title.trim();
    }

    function parseOfferLine(line) {
        // Normalize arrows
        line = line.replace(/[→►\-\>]/g, '👉');

        // Pattern: 1987👉1976TK 180GB 1200M 180D
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

    // Submit validation
    document.getElementById('bulkSaveForm').addEventListener('submit', function(e) {
        const op = document.getElementById('bulk_operator_hidden').value;
        if (!op) {
            e.preventDefault();
            alert('অনুগ্রহ করে প্রথমে Operator select করুন।');
        }
    });
</script>
@endsection
