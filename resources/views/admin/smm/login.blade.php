<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMM Standalone Admin Login</title>
    <!-- Fonts & Tailwind -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top right, #1e1b4b 0%, #0f172a 100%);
            min-height: 100vh;
        }
        .glass-panel {
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(99, 102, 241, 0.15);
            box-shadow: 0 20px 50px -15px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body class="flex justify-center items-center px-4">

    <div class="w-full max-w-md glass-panel rounded-3xl p-8 space-y-6 text-white">
        <!-- Brand Header -->
        <div class="text-center space-y-3">
            <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white flex items-center justify-center mx-auto shadow-lg shadow-indigo-600/30 font-extrabold text-2xl tracking-tighter">
                RV
            </div>
            <div>
                <h2 class="text-2xl font-bold tracking-tight">SMM Portal Control</h2>
                <p class="text-xs text-slate-400 mt-1">Dedicated Standalone SMM Admin Authentication</p>
            </div>
        </div>

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/20 text-red-400 text-xs px-4 py-3 rounded-xl flex items-center space-x-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Login form -->
        <form action="{{ route('admin.smm.login.submit') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="text-xs font-bold text-slate-300 block mb-1.5 uppercase tracking-wider">Admin Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-sm">
                        <i class="fa-solid fa-user-shield"></i>
                    </span>
                    <input type="text" name="username" placeholder="Username..." required class="w-full bg-slate-900/50 text-sm border border-slate-800 text-white pl-10 pr-4 py-3.5 rounded-xl focus:outline-none focus:border-indigo-500 transition-all placeholder:text-slate-600">
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-300 block mb-1.5 uppercase tracking-wider">Security Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-sm">
                        <i class="fa-solid fa-key"></i>
                    </span>
                    <input type="password" name="password" placeholder="••••••••" required class="w-full bg-slate-900/50 text-sm border border-slate-800 text-white pl-10 pr-4 py-3.5 rounded-xl focus:outline-none focus:border-indigo-500 transition-all placeholder:text-slate-600">
                </div>
            </div>

            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-indigo-600/20 active:scale-[0.98] transition-all text-sm flex items-center justify-center space-x-2">
                <span>Access Dashboard</span>
                <i class="fa-solid fa-circle-arrow-right text-xs"></i>
            </button>
        </form>
    </div>

</body>
</html>
