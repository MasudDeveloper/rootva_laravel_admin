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
                <!-- Global Announcement Settings Card -->
                <div class="dark-card rounded-3xl p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <h4 class="font-bold text-white uppercase text-xs tracking-wider flex items-center space-x-2">
                            <i class="fa-solid fa-bullhorn text-indigo-500 animate-pulse"></i>
                            <span>Global Announcement Notice</span>
                        </h4>
                    </div>
                    <form action="{{ route('admin.smm.config.update', 'global_notice') }}" method="POST" class="space-y-3.5">
                        @csrf
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 block mb-1">Marquee Message Text</label>
                            <textarea name="notice" class="w-full bg-slate-900 border border-slate-800 text-xs px-3 py-2 rounded-xl text-white focus:outline-none focus:border-indigo-500" rows="3" placeholder="রুটবা SMM পোর্টাল থেকে সরাসরি সাবমিট করে ইনকাম করুন ঝামেলা মুক্তভাবে!">{{ $globalNotice ? $globalNotice->notice : '' }}</textarea>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-xl text-xs transition-all active:scale-95 shadow-md shadow-indigo-600/10">Update Message</button>
                    </form>
                </div>

                <div class="flex items-center justify-between px-1 mb-2">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">SMM Service Settings</h3>
                    <button type="button" onclick="openCreateTaskModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1.5 rounded-xl font-bold transition-all active:scale-95 flex items-center space-x-1.5 shadow shadow-indigo-600/30">
                        <i class="fa-solid fa-plus"></i>
                        <span>Add New Project</span>
                    </button>
                </div>
                <div class="space-y-4">
                    @foreach($configs as $conf)
                    <div class="dark-card rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h4 class="font-bold text-white uppercase text-xs tracking-wider flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full {{ $conf->status === 'active' ? 'bg-emerald-500 shadow shadow-emerald-500/50' : 'bg-red-500 shadow shadow-red-500/50' }}"></span>
                                <span>{{ $conf->name }}</span>
                            </h4>
                            <div class="flex items-center space-x-2">
                                <span class="text-[10px] text-slate-400">Type: {{ $conf->task_type }}</span>
                                <button type="button" onclick="confirmDeleteProject('{{ $conf->task_type }}')" class="text-red-400 hover:text-red-300 text-xs p-1" title="Delete Project">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Dynamic configuration modification form -->
                        <form action="{{ route('admin.smm.config.update', $conf->task_type) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 block mb-1">Project Name</label>
                                <input type="text" name="name" value="{{ $conf->name }}" class="w-full bg-slate-900 border border-slate-800 text-xs px-2.5 py-2 rounded-lg text-white focus:outline-none focus:border-indigo-500">
                            </div>
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
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 block mb-1">YouTube Tutorial Video URL</label>
                                <input type="url" name="video_url" value="{{ $conf->video_url }}" placeholder="https://youtube.com/watch?v=..." class="w-full bg-slate-900 border border-slate-800 text-xs px-2.5 py-2 rounded-lg text-white focus:outline-none focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 block mb-1">Service Notice Guidelines</label>
                                <textarea name="notice" class="w-full bg-slate-900 border border-slate-800 text-xs px-2.5 py-2 rounded-lg text-white focus:outline-none focus:border-indigo-500" rows="2">{{ $conf->notice }}</textarea>
                            </div>

                            <!-- Custom Required Input Fields -->
                            <div class="border-t border-slate-800/80 pt-2.5">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-[10px] font-bold text-slate-400">User Input Fields Required</label>
                                    <button type="button" onclick="addFieldRow('fields-container-{{ $conf->task_type }}')" class="text-[10px] text-indigo-400 font-bold hover:underline">+ Add Field</button>
                                </div>
                                <div id="fields-container-{{ $conf->task_type }}" class="space-y-2">
                                    @if(!empty($conf->required_fields) && is_array($conf->required_fields))
                                        @foreach($conf->required_fields as $idx => $f)
                                            <div class="flex items-center space-x-2 text-xs">
                                                <input type="text" name="field_labels[]" value="{{ is_array($f) ? ($f['label'] ?? '') : $f }}" placeholder="Field Label (e.g. Channel Link)" class="flex-1 bg-slate-900 border border-slate-800 text-[11px] px-2 py-1.5 rounded-lg text-white focus:outline-none">
                                                <select name="field_types[]" class="bg-slate-900 border border-slate-800 text-[11px] px-2 py-1.5 rounded-lg text-white">
                                                    <option value="text" {{ (is_array($f) && ($f['type'] ?? '') === 'text') ? 'selected' : '' }}>Text</option>
                                                    <option value="url" {{ (is_array($f) && ($f['type'] ?? '') === 'url') ? 'selected' : '' }}>URL</option>
                                                    <option value="number" {{ (is_array($f) && ($f['type'] ?? '') === 'number') ? 'selected' : '' }}>Number</option>
                                                </select>
                                                <input type="hidden" name="field_required[{{ $loop->index }}]" value="1">
                                                <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300 p-1"><i class="fa-solid fa-trash-can text-[11px]"></i></button>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 block mb-1">Status</label>
                                    <select name="status" class="w-full bg-slate-900 border border-slate-800 text-xs px-2.5 py-2 rounded-lg text-white focus:outline-none focus:border-indigo-500">
                                        <option value="active" {{ $conf->status === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ $conf->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-lg text-xs transition-all active:scale-95">Update Project</button>
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
                                    <th class="py-4 px-5 font-bold uppercase tracking-wider">Submitted Credentials</th>
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
                                        <div class="flex flex-col gap-1 text-[11px]">
                                            <span class="text-blue-400 font-mono"><strong class="text-slate-500 mr-1">Field 1:</strong>{{ $sub->input_field_1 }}</span>
                                            @if($sub->input_field_2)
                                                <span class="text-pink-400 font-mono"><strong class="text-slate-500 mr-1">Field 2:</strong>{{ $sub->input_field_2 }}</span>
                                            @endif
                                            @if($sub->input_field_3)
                                                <span class="text-emerald-400 font-mono"><strong class="text-slate-500 mr-1">Field 3:</strong>{{ $sub->input_field_3 }}</span>
                                            @endif
                                            @if($sub->input_field_4)
                                                <span class="text-amber-400 font-mono"><strong class="text-slate-500 mr-1">Field 4:</strong>{{ $sub->input_field_4 }}</span>
                                            @endif
                                        </div>
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

    <!-- Create New SMM Project Modal -->
    <div id="create-task-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[999] flex justify-center items-center p-4">
        <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-3xl p-6 text-white space-y-5 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                <h4 class="font-bold text-md text-white flex items-center space-x-2">
                    <i class="fa-solid fa-folder-plus text-indigo-500"></i>
                    <span>Add New SMM Project</span>
                </h4>
                <button type="button" onclick="closeCreateTaskModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ Route::has('admin.smm.task.store') ? route('admin.smm.task.store') : url('/admin/smm-panel/task/store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-bold text-slate-400 block mb-1">Project Name <span class="text-red-400">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Telegram Channel Join" class="w-full bg-slate-950 border border-slate-800 text-xs px-3 py-2.5 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-400 block mb-1">Task Type Identifier / Slug (Optional)</label>
                    <input type="text" name="task_type_input" placeholder="e.g. telegram_channel_join (blank for auto)" class="w-full bg-slate-950 border border-slate-800 text-xs px-3 py-2.5 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold text-slate-400 block mb-1">Payout Rate (৳) <span class="text-red-400">*</span></label>
                        <input type="number" step="0.01" name="rate" required value="1.00" class="w-full bg-slate-950 border border-slate-800 text-xs px-3 py-2.5 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 block mb-1">Daily PW (Optional)</label>
                        <input type="text" name="daily_password" placeholder="Code required to submit" class="w-full bg-slate-950 border border-slate-800 text-xs px-3 py-2.5 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-400 block mb-1">YouTube Tutorial Video URL (Optional)</label>
                    <input type="url" name="video_url" placeholder="https://youtube.com/watch?v=..." class="w-full bg-slate-950 border border-slate-800 text-xs px-3 py-2.5 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-400 block mb-1">Project Rules / Guidelines Notice</label>
                    <textarea name="notice" rows="2" placeholder="প্রজেক্টের নিয়মাবলী লিখুন..." class="w-full bg-slate-950 border border-slate-800 text-xs px-3 py-2.5 rounded-xl text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>
                
                <!-- Dynamic Input Fields Setup -->
                <div class="border-t border-slate-800 pt-3">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold text-slate-300">User Input Fields Required</label>
                        <button type="button" onclick="addFieldRow('new-project-fields-container')" class="text-xs text-indigo-400 font-bold hover:underline">+ Add Input Field</button>
                    </div>
                    <div id="new-project-fields-container" class="space-y-2">
                        <div class="flex items-center space-x-2 text-xs">
                            <input type="text" name="field_labels[]" value="Work Proof / Username" placeholder="Field Label (e.g. Channel Link)" class="flex-1 bg-slate-950 border border-slate-800 text-xs px-3 py-2 rounded-xl text-white focus:outline-none">
                            <select name="field_types[]" class="bg-slate-950 border border-slate-800 text-xs px-3 py-2 rounded-xl text-white">
                                <option value="text">Text</option>
                                <option value="url">URL</option>
                                <option value="number">Number</option>
                            </select>
                            <input type="hidden" name="field_required[]" value="1">
                            <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300 p-1"><i class="fa-solid fa-trash-can"></i></button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-3 border-t border-slate-800">
                    <button type="button" onclick="closeCreateTaskModal()" class="bg-slate-800 text-xs px-4 py-2.5 rounded-xl font-bold">Cancel</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-xs px-5 py-2.5 rounded-xl font-bold text-white shadow-lg shadow-indigo-600/20">Create Project</button>
                </div>
            </form>
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
        function openCreateTaskModal() {
            document.getElementById('create-task-modal').classList.remove('hidden');
        }

        function closeCreateTaskModal() {
            document.getElementById('create-task-modal').classList.add('hidden');
        }

        function addFieldRow(containerId) {
            const container = document.getElementById(containerId);
            const count = container.children.length;
            if (count >= 4) {
                alert('সর্বোচ্চ ৪টি ইনপুট ফিল্ড যোগ করতে পারবেন।');
                return;
            }
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2 text-xs';
            div.innerHTML = `
                <input type="text" name="field_labels[]" placeholder="Field Label (e.g. Account Link)" class="flex-1 bg-slate-900 border border-slate-800 text-xs px-2.5 py-1.5 rounded-lg text-white focus:outline-none">
                <select name="field_types[]" class="bg-slate-900 border border-slate-800 text-xs px-2 py-1.5 rounded-lg text-white">
                    <option value="text">Text</option>
                    <option value="url">URL</option>
                    <option value="number">Number</option>
                </select>
                <input type="hidden" name="field_required[${count}]" value="1">
                <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300 p-1"><i class="fa-solid fa-trash-can text-xs"></i></button>
            `;
            container.appendChild(div);
        }

        function confirmDeleteProject(taskType) {
            if (confirm('আপনি কি নিশ্চিত যে এই প্রজেক্টটি মুছে ফেলতে চান?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                let actionUrl = '/admin/smm/task/' + taskType;
                if (window.location.pathname.includes('smm-panel')) {
                    actionUrl = '/admin/smm-panel/task/' + taskType;
                }
                form.action = actionUrl;
                
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);

                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);

                document.body.appendChild(form);
                form.submit();
            }
        }

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