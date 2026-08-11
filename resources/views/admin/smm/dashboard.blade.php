<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMM Dedicated Panel Admin</title>
    <!-- Fonts & Tailwind -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0b0f19;
            min-height: 100vh;
        }
        .dark-card {
            background-color: #111827;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="text-slate-200 py-8 px-4 sm:px-6 lg:px-8">

    <div class="max-w-6xl mx-auto space-y-8">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 border-b border-slate-800 pb-6">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-extrabold text-xl shadow shadow-indigo-600/30">
                    RV
                </div>
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-white">SMM Master Command Control</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Approve tasks, manage dynamic rates, and update dynamic verification passwords</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <div class="flex bg-slate-800 rounded-xl p-0.5 border border-slate-700">
                    <a href="{{ route('admin.smm.dashboard', ['status' => 'pending']) }}" class="text-xs px-4 py-2 rounded-lg font-bold transition-all {{ $status === 'pending' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400' }}">Pending</a>
                    <a href="{{ route('admin.smm.dashboard', ['status' => 'approved']) }}" class="text-xs px-4 py-2 rounded-lg font-bold transition-all {{ $status === 'approved' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400' }}">Approved</a>
                    <a href="{{ route('admin.smm.dashboard', ['status' => 'rejected']) }}" class="text-xs px-4 py-2 rounded-lg font-bold transition-all {{ $status === 'rejected' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400' }}">Rejected</a>
                </div>
                <a href="{{ route('admin.smm.logout') }}" class="bg-red-950/40 text-red-400 border border-red-900/30 text-xs px-4 py-2.5 rounded-xl font-bold hover:bg-red-900/20 active:scale-95 transition-all">
                    <i class="fa-solid fa-power-off"></i> Logout
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs px-5 py-4 rounded-2xl flex items-center space-x-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/20 text-red-400 text-xs px-5 py-4 rounded-2xl flex items-center space-x-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- left column: SMM Task Dynamic configuration controls -->
            <div class="space-y-6">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest px-1">SMM Service Settings</h3>
                <div class="space-y-4">
                    @foreach($configs as $conf)
                        <div class="dark-card rounded-2xl p-5 space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                                <h4 class="font-bold text-white uppercase text-xs tracking-wider flex items-center space-x-2">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $conf->status === 'active' ? 'bg-emerald-500 shadow shadow-emerald-500/50' : 'bg-red-500 shadow shadow-red-500/50' }}"></span>
                                    <span>{{ $conf->name }}</span>
                                </h4>
                                <span class="text-[10px] text-slate-400">Type: {{ $conf->task_type }}</span>
                            </div>
                            
                            <!-- Dynamic configuration modification form -->
                            <form action="{{ route('admin.smm.config.update', $conf->task_type) }}" method="POST" class="space-y-3">
                                @csrf
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-400 block mb-1">Today's Price (৳)</label>
                                        <input type="number" step="0.01" name="rate" value="{{ $conf->rate }}" class="w-full bg-slate-900 border border-slate-800 text-xs px-2.5 py-2 rounded-lg text-white focus:outline-none focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-400 block mb-1">Daily PW to Register</label>
                                        <input type="text" name="daily_password" value="{{ $conf->daily_password }}" class="w-full bg-slate-900 border border-slate-800 text-xs px-2.5 py-2 rounded-lg text-white focus:outline-none focus:border-indigo-500">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-400 block mb-1">Status</label>
                                        <select name="status" class="w-full bg-slate-900 border border-slate-800 text-xs px-2.5 py-2 rounded-lg text-white focus:outline-none focus:border-indigo-500">
                                            <option value="active" {{ $conf->status === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ $conf->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="flex items-end">
                                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-lg text-xs transition-all active:scale-95">Update</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- right column: Submissions listing -->
            <div class="lg:col-span-2 space-y-6">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest px-1">Verification Console ({{ $submissions->total() }})</h3>
                <div class="dark-card rounded-3xl overflow-hidden shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-900 border-b border-slate-800 text-slate-400">
                                    <th class="py-4 px-5 font-bold uppercase tracking-wider">User Store</th>
                                    <th class="py-4 px-5 font-bold uppercase tracking-wider">Task Type</th>
                                    <th class="py-4 px-5 font-bold uppercase tracking-wider">Credential (Field 1)</th>
                                    <th class="py-4 px-5 font-bold uppercase tracking-wider">Pass/Code (Field 2)</th>
                                    <th class="py-4 px-5 font-bold uppercase tracking-wider">Payout</th>
                                    @if($status === 'pending')
                                        <th class="py-4 px-5 font-bold uppercase tracking-wider text-right">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/50">
                                @forelse($submissions as $sub)
                                    <tr class="hover:bg-slate-900/40 transition-colors">
                                        <td class="py-4 px-5">
                                            <span class="font-bold text-white block">{{ $sub->user->name ?? 'N/A' }}</span>
                                            <span class="text-slate-500 text-[10px] mt-0.5"><i class="fa-solid fa-phone mr-1"></i>{{ $sub->user->number ?? 'N/A' }}</span>
                                        </td>
                                        <td class="py-4 px-5">
                                            <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 uppercase">{{ $sub->task_type }}</span>
                                        </td>
                                        <td class="py-4 px-5">
                                            <code class="text-blue-400 font-mono">{{ $sub->input_field_1 }}</code>
                                        </td>
                                        <td class="py-4 px-5">
                                            @if($sub->input_field_2)
                                                <code class="text-pink-400 font-mono">{{ $sub->input_field_2 }}</code>
                                            @else
                                                <span class="text-slate-600 italic">None</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-5">
                                            <span class="font-bold text-emerald-400">৳{{ number_format($sub->price, 2) }}</span>
                                        </td>
                                        @if($status === 'pending')
                                            <td class="py-4 px-5 text-right space-x-2">
                                                <form action="{{ route('admin.smm.approve', $sub->id) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg transition-all active:scale-95" onclick="return confirm('Approve submission and distribute payment?')">Approve</button>
                                                </form>
                                                <button type="button" onclick="openRejectModal({{ $sub->id }})" class="bg-red-950/40 text-red-400 border border-red-950 px-3 py-1.5 rounded-lg transition-all active:scale-95">Reject</button>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-16 text-center text-slate-500">
                                            <i class="fa-solid fa-folder-open text-4xl mb-4 opacity-25"></i>
                                            <p class="text-xs">No submissions found in this state</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="py-4">
                    {{ $submissions->links() }}
                </div>
            </div>

        </div>

    </div>

    <!-- Standalone Reject Feedback Form Modal -->
    <div id="reject-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[999] flex justify-center items-center p-4">
        <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-6 text-white space-y-5">
            <div class="flex justify-between items-center">
                <h4 class="font-bold text-md">Reject SMM Submission</h4>
                <button onclick="closeRejectModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="reject-form" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="text-xs text-slate-400 block mb-1">Reason for Rejection</label>
                        <textarea name="feedback" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-red-500" rows="3">Incorrect credentials or duplicate sell.</textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeRejectModal()" class="bg-slate-800 text-xs px-4 py-2.5 rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-xs px-4 py-2.5 rounded-xl font-bold text-white">Confirm Reject</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(id) {
            document.getElementById('reject-form').action = '/admin/smm/submissions/' + id + '/reject';
            document.getElementById('reject-modal').classList.remove('hidden');
        }
        function closeRejectModal() {
            document.getElementById('reject-modal').classList.add('hidden');
        }
    </script>
</body>
</html>
