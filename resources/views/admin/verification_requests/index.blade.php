@extends('layouts.admin')

@section('title', 'Verification Requests')
@section('page_title', 'Identity Verifications')

@section('content')
<div class="fade-in">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0">Pending Verifications: {{ $requests->total() }}</h4>
            <p class="text-muted small mb-0">Approve user accounts to grant full feature access</p>
        </div>
        <div class="d-flex flex-column flex-lg-row gap-3 w-100 justify-content-md-end align-items-lg-center">
            <form action="{{ route('admin.verifications.index') }}" method="GET" class="d-flex gap-2 w-100" style="max-width: 100%; @media(min-width: 992px) { max-width: 300px; }">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="text" name="search" class="form-control rounded-pill w-100" placeholder="Search TrxID/REF ID..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary rounded-pill px-3 shadow-sm"><i class="fa-solid fa-search"></i></button>
            </form>
            <div class="d-flex flex-wrap gap-2 justify-content-start">
                @if($status == 'Approved')
                <button type="button" class="btn btn-dark rounded-pill px-3 shadow-sm flex-grow-1 flex-sm-grow-0 text-center" data-bs-toggle="modal" data-bs-target="#bulkDownloadModal">
                    <i class="fa-solid fa-download me-1"></i> Bulk Download
                </button>
                @endif
                <a href="{{ route('admin.verifications.index', ['status' => 'Pending', 'search' => request('search')]) }}" 
                   class="btn btn-{{ $status == 'Pending' ? 'primary' : 'light' }} rounded-pill px-3 shadow-sm flex-grow-1 flex-sm-grow-0 text-center">Pending</a>
                <a href="{{ route('admin.verifications.index', ['status' => 'Approved', 'search' => request('search')]) }}" 
                   class="btn btn-{{ $status == 'Approved' ? 'primary' : 'light' }} rounded-pill px-3 shadow-sm flex-grow-1 flex-sm-grow-0 text-center">Approved</a>
                <a href="{{ route('admin.verifications.index', ['status' => 'Rejected', 'search' => request('search')]) }}" 
                   class="btn btn-{{ $status == 'Rejected' ? 'primary' : 'light' }} rounded-pill px-3 shadow-sm flex-grow-1 flex-sm-grow-0 text-center">Rejected</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('error') }}
        </div>
    @endif

    <!-- Requests Table -->
    <div class="card-modern p-0 overflow-hidden border-0 shadow-lg">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold">User Information</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Payment Details</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Transaction ID</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold">Date/Time</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-end">Amount</th>
                        <th class="py-3 border-0 text-muted small text-uppercase fw-bold text-center">Status</th>
                        <th class="px-4 py-3 border-0 text-muted small text-uppercase fw-bold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @forelse($requests as $req)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px">
                                    {{ substr($req->name, 0, 1) }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.users.show', $req->user_id) }}" class="text-decoration-none fw-bold text-dark" target="_blank">
                                        {{ $req->name }} <i class="fa-solid fa-arrow-up-right-from-square small ms-1" style="font-size: 9px; opacity: 0.7;"></i>
                                    </a>
                                    <div class="text-muted small">{{ $req->number }}</div>
                                    <div class="badge bg-light text-primary rounded-pill extra-small fw-bold">REF: {{ $req->referCode }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="fw-semibold text-capitalize">{{ $req->payment_gateway }}</div>
                            <div class="text-muted small">{{ $req->account_number }}</div>
                        </td>
                        <td class="py-3">
                            <code class="text-primary bg-primary-soft px-2 py-1 rounded small fw-bold">{{ $req->transaction_id }}</code>
                        </td>
                        <td class="py-3">
                            <div class="text-dark small fw-medium">
                                @php
                                    try {
                                        $date = \Carbon\Carbon::parse($req->created_at)->format('d-m-Y h:i A');
                                    } catch (\Exception $e) {
                                        $date = $req->created_at;
                                    }
                                @endphp
                                {{ $date }}
                            </div>
                        </td>
                        <td class="py-3 text-end fw-extrabold text-dark">
                            ৳{{ number_format($req->amount ?? 0, 2) }}
                        </td>
                        <td class="py-3 text-center">
                            @if($req->status == 'Pending')
                                <span class="badge bg-warning-soft text-warning rounded-pill px-3">Pending</span>
                            @elseif($req->status == 'Approved')
                                <span class="badge bg-success-soft text-success rounded-pill px-3">Approved</span>
                            @else
                                <span class="badge bg-danger-soft text-danger rounded-pill px-3">Rejected</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            @if($req->status == 'Pending')
                            <div class="d-flex justify-content-end gap-2">
                                <form action="{{ route('admin.verifications.approve', $req->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" onclick="return confirm('Verify this user?')">
                                        <i class="fa-solid fa-check-circle me-1"></i>Approve
                                    </button>
                                </form>
                                <form action="{{ route('admin.verifications.reject', $req->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm" onclick="return confirm('Reject this verification request?')">
                                        <i class="fa-solid fa-ban me-1"></i>Reject
                                    </button>
                                </form>
                            </div>
                            @else
                                <span class="text-muted small italic">Processed</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="opacity-50">
                                <i class="fa-solid fa-shield-halved fa-4x mb-3 d-block"></i>
                                <h5 class="fw-bold">No {{ strtolower($status) }} requests found</h5>
                                <p class="small">Check back later for new verification submissions.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
        <div class="px-4 py-3 bg-light border-top d-flex justify-content-center">
            {{ $requests->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Bulk Download Modal -->
<div class="modal fade" id="bulkDownloadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Bulk Download Verified Cards</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Select a date to download all ID cards for users verified on that day.</p>
                <div class="mb-3">
                    <label class="form-label fw-bold">Verification Date</label>
                    <input type="date" id="bulkDate" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div id="bulkProgressContainer" class="d-none mt-3">
                    <p class="small fw-bold text-primary mb-1" id="bulkProgressText">Generating cards: 0 / 0</p>
                    <div class="progress" style="height: 10px; border-radius: 5px;">
                        <div id="bulkProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnBulkDownload" onclick="startBulkDownload()">
                    <i class="fa-solid fa-file-zipper me-2"></i>Generate & Download
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden template for bulk download -->
<div style="position: absolute; left: -9999px; top: -9999px;">
    <div id="bulk-verified-card" style="width: 400px; background: #f8fafc; border-radius: 24px; padding: 0; text-align: center; font-family: 'Hind Siliguri', sans-serif; overflow: hidden; position: relative; border: 1px solid #e2e8f0;">
        <div style="height: 140px; background: linear-gradient(135deg, #1e40af, #3b82f6); width: 100%; position: absolute; top: 0; left: 0; z-index: 1;">
            <div style="position: absolute; top: -20px; left: -20px; width: 120px; height: 120px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            <div style="position: absolute; bottom: -30px; right: 20px; width: 80px; height: 80px; background: rgba(255,255,255,0.15); border-radius: 50%;"></div>
        </div>
        <div style="position: relative; z-index: 2; padding: 0 30px 40px 30px;">
            <div style="margin-top: 70px; margin-bottom: 20px;">
                <div style="position: relative; display: inline-block;">
                    <img id="bulk-card-img" src="" alt="Profile" style="width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 6px solid #ffffff; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); background-color: #ffffff;">
                    <div style="position: absolute; bottom: 8px; right: 5px; background: #10b981; color: white; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border: 4px solid #ffffff; font-size: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                        <i class="fa-solid fa-check"></i>
                    </div>
                </div>
            </div>
            <h2 style="color: #1e3a8a; font-weight: 800; margin-bottom: 5px; font-size: 32px; letter-spacing: -0.5px;">অভিনন্দন!</h2>
            <h4 id="bulk-card-name" style="color: #0f172a; font-weight: 700; margin-bottom: 25px; font-size: 24px;">Name</h4>
            <div style="background-color: #ffffff; border-radius: 16px; padding: 22px 20px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
                <p style="color: #475569; font-size: 16px; line-height: 1.6; margin: 0; font-weight: 500;">
                    আপনার অ্যাকাউন্টটি সফলভাবে ভেরিফাই করা হয়েছে! <strong style="color: #2563eb;">Rootva</strong>-এর একজন সম্মানিত মেম্বার হিসেবে আপনাকে স্বাগতম। এখন থেকে আপনি আমাদের সকল প্রিমিয়াম সুবিধা উপভোগ করতে পারবেন।
                </p>
            </div>
            <div style="background: #003366; color: white; border-radius: 50px; padding: 15px 30px; font-weight: bold; font-size: 18px; box-shadow: 0 10px 20px rgba(0, 51, 102, 0.3); display: inline-block; width: 80%; letter-spacing: 0.5px;">
                ধন্যবাদ
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
async function startBulkDownload() {
    const date = document.getElementById('bulkDate').value;
    if (!date) return alert('Select a date first');
    
    const btn = document.getElementById('btnBulkDownload');
    const progContainer = document.getElementById('bulkProgressContainer');
    const progText = document.getElementById('bulkProgressText');
    const progBar = document.getElementById('bulkProgressBar');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Fetching data...';
    
    try {
        const res = await fetch(`{{ route('admin.verifications.bulk-cards-data') }}?date=${date}`);
        const users = await res.json();
        
        if (!users || users.length === 0) {
            alert('No verified users found on this date.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-file-zipper me-2"></i>Generate & Download';
            return;
        }

        progContainer.classList.remove('d-none');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Generating...';
        
        const zip = new JSZip();
        const imgFolder = zip.folder(`Verified_Cards_${date}`);
        
        for (let i = 0; i < users.length; i++) {
            const user = users[i];
            progText.innerText = `Generating card for ${user.name} (${i + 1}/${users.length})...`;
            progBar.style.width = `${((i) / users.length) * 100}%`;
            
            document.getElementById('bulk-card-name').innerText = user.name;
            const imgEl = document.getElementById('bulk-card-img');
            imgEl.src = user.image;
            
            await new Promise(r => setTimeout(r, 100)); // wait for image render
            
            const canvas = await html2canvas(document.getElementById('bulk-verified-card'), {
                useCORS: true,
                scale: 3,
                backgroundColor: null
            });
            
            const dataUrl = canvas.toDataURL('image/png');
            const base64Data = dataUrl.split(',')[1];
            imgFolder.file(`card_${user.referCode}_${i}.png`, base64Data, {base64: true});
        }
        
        progText.innerText = `Compressing ZIP file...`;
        progBar.style.width = '100%';
        
        const content = await zip.generateAsync({type:"blob"});
        const link = document.createElement("a");
        link.href = URL.createObjectURL(content);
        link.download = `Rootva_Verified_Cards_${date}.zip`;
        link.click();
        
        progText.innerText = `Done!`;
        setTimeout(() => {
            progContainer.classList.add('d-none');
            const modalEl = document.getElementById('bulkDownloadModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }, 2000);
        
    } catch(err) {
        console.error(err);
        alert('An error occurred during generation.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-file-zipper me-2"></i>Generate & Download';
    }
}
</script>

@section('styles')
<style>
    .bg-gray-50 { background-color: #f9fafb; }
    .extra-small { font-size: 0.7rem; }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .card-modern { border-radius: 1.25rem; }
    .letter-spacing-1 { letter-spacing: 0.05em; }
</style>
@endsection
