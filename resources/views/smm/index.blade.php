<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMM Work Portal</title>
    <!-- Google Fonts & Tailwind -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.05);
        }

        .active-tab {
            color: #2563eb;
        }

        /* Page Transition Animations */
        .fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }

        .scale-in {
            animation: scaleIn 0.3s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.97);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        /* Smooth Scrolling Marquee Animation */
        @keyframes marquee {
            0% {
                transform: translateX(100%);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        .animate-marquee {
            display: inline-block;
            white-space: nowrap;
            animation: marquee 15s linear infinite;
        }

        .animate-marquee:hover {
            animation-play-state: paused;
        }
    </style>
</head>

<body class="flex justify-center items-center">

    <!-- Mobile Device Frame Wrapper Simulator -->
    <div class="w-full max-w-md bg-[#fafbfc] min-h-screen shadow-2xl relative flex flex-col justify-between overflow-x-hidden" id="app-container">

        <!-- ==================== STATE 1: SPLASH SCREEN ==================== -->
        <div id="state-splash" class="absolute inset-0 bg-gradient-to-tr from-blue-600 to-indigo-700 z-[999] flex flex-col justify-between items-center py-16 px-6">
            <div></div>
            <div class="text-center scale-in space-y-6">
                <!-- Branding Circle Icon -->
                <div class="w-24 h-24 rounded-3xl bg-white flex items-center justify-center mx-auto shadow-2xl shadow-indigo-900/30">
                    <span class="text-4xl font-extrabold text-indigo-700 tracking-tighter">RV</span>
                </div>
                <div class="space-y-2">
                    <h1 class="text-3xl font-extrabold text-white tracking-wide">Rootva SMM</h1>
                    <p class="text-blue-100 text-xs tracking-widest uppercase">Premium Work Portal</p>
                </div>
            </div>

            <div class="flex flex-col items-center space-y-4">
                <!-- Advanced Spinner -->
                <div class="w-8 h-8 border-4 border-white/20 border-t-white rounded-full animate-spin"></div>
                <p class="text-blue-200 text-[10px] uppercase tracking-wider font-semibold">Secure Connection Establishing...</p>
            </div>
        </div>

        <!-- ==================== STATE 2: DEDICATED LOGIN PAGE ==================== -->
        <div id="state-login" class="hidden absolute inset-0 bg-slate-50 z-[90] flex flex-col justify-center px-6 py-12">
            <div class="w-full max-w-sm mx-auto space-y-8 fade-in">
                <!-- Branding Header -->
                <div class="text-center space-y-3">
                    <div class="w-16 h-16 rounded-2xl bg-blue-600 text-white flex items-center justify-center mx-auto shadow-lg shadow-blue-600/20 font-bold text-xl">
                        RV
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">Welcome Back</h2>
                        <p class="text-xs text-slate-500 mt-1">Sign in with your Rootva mobile account</p>
                    </div>
                </div>

                <!-- Login form card -->
                <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-xl shadow-slate-100/50 space-y-5">
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-semibold text-slate-600 block mb-1">Mobile Number</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-sm">
                                    <i class="fa-solid fa-phone"></i>
                                </span>
                                <input type="text" id="login-number" placeholder="01XXXXXXXXX" class="w-full text-sm border border-slate-200 pl-10 pr-4 py-3 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 transition-all placeholder:text-slate-400">
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600 block mb-1">Password</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-sm">
                                    <i class="fa-solid fa-lock"></i>
                                </span>
                                <input type="password" id="login-password" placeholder="••••••••" class="w-full text-sm border border-slate-200 pl-10 pr-4 py-3 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 transition-all placeholder:text-slate-400">
                            </div>
                        </div>
                    </div>

                    <button onclick="handleLogin()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl shadow-lg shadow-blue-600/20 active:scale-95 transition-all text-sm flex items-center justify-center space-x-2">
                        <span>Sign In</span> <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>

                <div class="text-center">
                    <p class="text-[11px] text-slate-400">Secured with Rootva Standard Encryption</p>
                </div>
            </div>
        </div>

        <!-- ==================== STATE 3: MAIN APP MODULE ==================== -->
        <!-- App Header (Only visible post-login) -->
        <div id="app-header" class="hidden bg-blue-600 text-white px-5 py-4 flex items-center justify-between rounded-b-3xl shadow-lg z-40">
            <div class="flex items-center space-x-3">
                <div onclick="toggleProfileModal()" class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center font-bold text-lg shadow-inner cursor-pointer overflow-hidden relative active:scale-95 transition-all">
                    <img id="user-profile-pic" src="" class="w-full h-full object-cover hidden">
                    <span id="user-profile-placeholder">R</span>
                </div>
                <div>
                    <h3 class="text-xs font-semibold opacity-85 tracking-wide">Good Evening 👋</h3>
                    <p class="text-sm font-bold" id="username">Loading...</p>
                </div>
            </div>
            <div class="relative cursor-pointer active:scale-95 transition-all">
                <i class="fa-regular fa-bell text-xl"></i>
                <span class="absolute -top-1 -right-1 bg-amber-400 text-black text-[9px] font-bold px-1.5 py-0.5 rounded-full">2</span>
            </div>
        </div>

        <!-- Scrollable Workspace (Only visible post-login) -->
        <div id="app-workspace" class="hidden flex-1 overflow-y-auto px-4 py-4 space-y-4 pb-20 fade-in">

            <!-- Dashboard Section (Advanced Redesign) -->
            <div id="section-dashboard" class="space-y-5">

                <!-- Premium Glassmorphic Promo Card -->
                <div class="relative rounded-3xl overflow-hidden shadow-2xl border border-white/10 group">
                    <img src="/download_page/banner_website_free.png" alt="SMM Banner" class="w-full h-40 object-cover transform group-hover:scale-105 transition-all duration-700" onerror="this.src='https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=500&q=80'">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/40 to-transparent flex flex-col justify-end p-5 text-white">
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-2.5 py-0.5 rounded-full w-max mb-2">Social Selling Network</span>
                        <h4 class="text-lg font-black tracking-wide">Social Media Selling</h4>
                        <p class="text-[10px] text-slate-300 mt-1">কমিশন সিস্টেমে রুটবা SMM এর সাথে ইনকাম শুরু করুন</p>
                    </div>
                </div>

                <!-- Custom Modern Banner Notice (frosted glowing outline) -->
                <div class="bg-indigo-950/5 border border-indigo-500/15 rounded-2xl p-3.5 flex items-center space-x-3 shadow-inner">
                    <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center text-sm shadow">
                        <i class="fa-solid fa-bullhorn animate-pulse"></i>
                    </div>
                    <div class="flex-1 overflow-hidden relative h-5">
                        <div class="absolute whitespace-nowrap text-xs text-indigo-950 font-bold animate-marquee" id="marquee-notice">
                            রুটবা SMM পোর্টাল থেকে সরাসরি সাবমিট করে ইনকাম করুন ঝামেলা মুক্তভাবে!
                        </div>
                    </div>
                </div>

                <!-- Wallet Card Widget (Luxurious Dark Navy Gradient with Cyan Glow Elements) -->
                <div class="bg-gradient-to-tr from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 text-white shadow-2xl border border-indigo-500/25 relative overflow-hidden">
                    <div class="absolute -right-12 -top-12 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl"></div>
                    <div class="absolute -left-12 -bottom-12 w-32 h-32 bg-cyan-500/10 rounded-full blur-2xl"></div>

                    <div class="relative z-10 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-indigo-300 uppercase tracking-widest block">Wallet Balance</span>
                            <h2 class="text-3xl font-black mt-2 bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">৳ <span id="dashboard-balance" class="text-white">0.00</span></h2>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-lg text-indigo-300 shadow">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>
                </div>

                <!-- Projects grid items -->
                <div class="space-y-3.5">
                    <div class="flex items-center justify-between px-1">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">সক্রিয় কাজ সমূহ</h3>
                        <span class="text-[9px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full font-bold">Grid Matrix</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4" id="projects-grid">
                        <!-- Loaded dynamically -->
                    </div>
                </div>
            </div>

            <!-- Task Detail submission UI -->
            <div id="section-task" class="hidden space-y-4">
                <button onclick="showDashboard()" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center space-x-1 active:scale-95 transition-all">
                    <i class="fa-solid fa-arrow-left"></i> <span>Go Back</span>
                </button>

                <!-- Facebook Sub-task Selector Buttons -->
                <div id="facebook-subtask-container" class="hidden bg-slate-100 p-1 rounded-2xl border border-slate-200/50 flex space-x-1">
                    <button onclick="switchFacebookSubtask('facebook_cookies')" id="btn-subtask-facebook_cookies" class="flex-1 text-[10px] font-bold py-2 rounded-xl transition-all bg-white text-blue-600 shadow-sm">Cookies</button>
                    <button onclick="switchFacebookSubtask('facebook_zero_friend')" id="btn-subtask-facebook_zero_friend" class="flex-1 text-[10px] font-bold py-2 rounded-xl transition-all text-slate-600 hover:bg-slate-50">0 Friend ID</button>
                    <button onclick="switchFacebookSubtask('facebook_number_id')" id="btn-subtask-facebook_number_id" class="flex-1 text-[10px] font-bold py-2 rounded-xl transition-all text-slate-600 hover:bg-slate-50">Number ID</button>
                </div>

                <!-- Instagram Sub-task Selector Buttons -->
                <div id="instagram-subtask-container" class="hidden bg-slate-100 p-1 rounded-2xl border border-slate-200/50 flex space-x-1">
                    <button onclick="switchInstagramSubtask('instagram_2fa')" id="btn-subtask-instagram_2fa" class="flex-1 text-[10px] font-bold py-2 rounded-xl transition-all bg-white text-pink-600 shadow-sm">2FA</button>
                    <button onclick="switchInstagramSubtask('instagram_cookies')" id="btn-subtask-instagram_cookies" class="flex-1 text-[10px] font-bold py-2 rounded-xl transition-all text-slate-600 hover:bg-slate-50">Cookies</button>
                </div>

                <!-- TikTok Sub-task Selector Buttons -->
                <div id="tiktok-subtask-container" class="hidden bg-slate-100 p-1 rounded-2xl border border-slate-200/50 flex space-x-1">
                    <button onclick="switchTiktokSubtask('tiktok_2fa')" id="btn-subtask-tiktok_2fa" class="flex-1 text-[10px] font-bold py-2 rounded-xl transition-all bg-white text-red-600 shadow-sm">2FA ID</button>
                    <button onclick="switchTiktokSubtask('tiktok_cookies')" id="btn-subtask-tiktok_cookies" class="flex-1 text-[10px] font-bold py-2 rounded-xl transition-all text-slate-600 hover:bg-slate-50">Cookies String</button>
                </div>

                <!-- Guidelines Info Card -->
                <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl shadow" id="task-icon-container">
                                <i id="task-icon"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-md" id="task-title">Gmail Marketing</h3>
                                <span class="inline-block text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-full mt-1" id="task-rate">Today's Price: ৳ 18.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-xs text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100" id="task-notice"></div>

                    <!-- Dynamic password / category price list display -->
                    <div class="border border-indigo-100/80 rounded-2xl p-4 bg-indigo-50/40 space-y-2" id="wrapper-daily-password">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] text-indigo-700 font-extrabold uppercase tracking-wider" id="daily-password-label">Required Password to Register</span>
                            <button onclick="copyDailyPassword()" id="btn-copy-daily-password" class="text-xs text-blue-600 bg-white border border-blue-200 px-3 py-1 rounded-lg active:scale-95 transition-all font-semibold shadow-xs">Copy</button>
                        </div>
                        <div id="task-daily-password" class="text-sm font-extrabold text-indigo-950">Loading...</div>
                    </div>

                    <a id="task-tutorial-btn" target="_blank" class="hidden w-full items-center justify-center space-x-2 text-xs font-bold text-blue-600 bg-blue-50 py-3 rounded-xl">
                        <i class="fa-brands fa-youtube text-red-500 text-sm"></i> <span>ভিডিও টিউটোরিয়াল দেখুন</span>
                    </a>
                </div>

                <!-- Input Submissions form layout -->
                <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm space-y-4">
                    <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Submit Work Proof</h3>
                    <div class="space-y-3" id="dynamic-inputs-container">
                        <!-- Inputs render dynamically -->
                    </div>
                    <button onclick="submitTaskProof()" class="w-full bg-blue-600 text-white font-semibold py-3 rounded-xl shadow-md active:scale-95 transition-all text-sm flex items-center justify-center space-x-2">
                        <i class="fa-regular fa-paper-plane"></i> <span>Submit Work</span>
                    </button>
                </div>
            </div>

            <!-- Wallet History Custom High-End Redesign -->
            <div id="section-wallet" class="hidden space-y-6 fade-in">

                <!-- Premium Frosted Glow Panel -->
                <div class="bg-gradient-to-tr from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 text-white shadow-2xl border border-indigo-500/20 relative overflow-hidden">
                    <div class="absolute -right-12 -top-12 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl"></div>
                    <div class="absolute -left-12 -bottom-12 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl"></div>

                    <div class="relative z-10 space-y-4">
                        <div class="flex items-center justify-between border-b border-white/5 pb-3">
                            <span class="text-[11px] font-bold text-indigo-300 uppercase tracking-widest">SMM Account Balance</span>
                            <span class="text-[10px] bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded-full border border-indigo-500/30">Verified Ledger</span>
                        </div>
                        <div>
                            <span class="text-xs opacity-60 block">সর্বমোট অর্জিত ব্যালেন্স</span>
                            <h2 class="text-4xl font-black tracking-tight mt-1 bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">৳ <span id="wallet-total-balance" class="text-white">0.00</span></h2>
                        </div>
                        <div class="flex justify-between items-center text-[10px] text-slate-400 pt-1">
                            <span>Last Updated: Just Now</span>
                            <span class="text-emerald-400 font-bold"><i class="fa-solid fa-circle-nodes mr-1"></i>Secure Node</span>
                        </div>
                    </div>
                </div>

                <!-- Custom Modern Status Badges -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Pending Box -->
                    <div class="bg-white border border-slate-100 rounded-2xl p-4 flex items-center space-x-3.5 shadow-sm">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">পেন্ডিং কাজ</span>
                            <h3 class="text-lg font-extrabold text-slate-800" id="wallet-pending-count">0</h3>
                        </div>
                    </div>

                    <!-- Today Complete Box -->
                    <div class="bg-white border border-slate-100 rounded-2xl p-4 flex items-center space-x-3.5 shadow-sm">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-lg">
                            <i class="fa-regular fa-calendar-check"></i>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">আজকের কাজ</span>
                            <h3 class="text-lg font-extrabold text-slate-800" id="wallet-today-count">0</h3>
                        </div>
                    </div>

                    <!-- Success Rate Box -->
                    <div class="col-span-2 bg-white border border-slate-100 rounded-2xl p-4 flex items-center space-x-3.5 shadow-sm">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">সাকসেস রেট</span>
                            <div class="flex items-center justify-between mt-1">
                                <h3 class="text-lg font-extrabold text-slate-800"><span id="wallet-success-rate">100</span>%</h3>
                                <div class="w-24 bg-slate-100 rounded-full h-2 overflow-hidden">
                                    <div id="wallet-success-rate-bar" class="bg-blue-600 h-full rounded-full transition-all duration-500" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Analytics Listing Layout (Not copying mockup grid) -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between px-1">
                        <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider">পরিষেবা ভিত্তিক বিক্রয় বিশ্লেষণ</h3>
                        <span class="text-[10px] text-slate-400">Detailed Analytics</span>
                    </div>

                    <!-- Advanced custom list containers -->
                    <div class="space-y-2.5" id="wallet-analytics-grid">
                        <!-- Loaded dynamically -->
                    </div>
                </div>

                <!-- Submissions History Section -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider">কাজের ইতিহাস (Submissions)</h3>
                        <span class="text-[10px] text-slate-400">Recent 100 Tasks</span>
                    </div>

                    <!-- Platform Filter Tabs -->
                    <div class="flex items-center space-x-1.5 overflow-x-auto pb-1" id="history-filter-tabs">
                        <button onclick="filterHistory('all')" class="px-3.5 py-1.5 rounded-full text-[10px] font-bold transition-all bg-blue-600 text-white shadow-sm" id="btn-filter-all">All</button>
                        <button onclick="filterHistory('facebook')" class="px-3.5 py-1.5 rounded-full text-[10px] font-bold transition-all bg-white border border-slate-100 text-slate-600 hover:bg-slate-50" id="btn-filter-facebook">Facebook</button>
                        <button onclick="filterHistory('gmail')" class="px-3.5 py-1.5 rounded-full text-[10px] font-bold transition-all bg-white border border-slate-100 text-slate-600 hover:bg-slate-50" id="btn-filter-gmail">Gmail</button>
                        <button onclick="filterHistory('instagram')" class="px-3.5 py-1.5 rounded-full text-[10px] font-bold transition-all bg-white border border-slate-100 text-slate-600 hover:bg-slate-50" id="btn-filter-instagram">Instagram</button>
                        <button onclick="filterHistory('whatsapp')" class="px-3.5 py-1.5 rounded-full text-[10px] font-bold transition-all bg-white border border-slate-100 text-slate-600 hover:bg-slate-50" id="btn-filter-whatsapp">WhatsApp</button>
                        <button onclick="filterHistory('telegram')" class="px-3.5 py-1.5 rounded-full text-[10px] font-bold transition-all bg-white border border-slate-100 text-slate-600 hover:bg-slate-50" id="btn-filter-telegram">Telegram</button>
                    </div>

                    <!-- Submissions List -->
                    <div class="space-y-3" id="submissions-list">
                        <!-- Loaded dynamically -->
                    </div>
                </div>
            </div>
            
            <!-- Support & Help Section -->
            <div id="section-support" class="hidden space-y-6 fade-in">
                <!-- Premium Frosted Glow Panel -->
                <div class="bg-gradient-to-tr from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 text-white shadow-2xl border border-indigo-500/20 relative overflow-hidden flex flex-col items-center text-center space-y-3.5">
                    <div class="absolute -right-12 -top-12 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl"></div>
                    <div class="absolute -left-12 -bottom-12 w-32 h-32 bg-cyan-500/10 rounded-full blur-2xl"></div>
                    
                    <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl text-indigo-400 shadow">
                        <i class="fa-solid fa-headset animate-pulse"></i>
                    </div>
                    
                    <div class="space-y-1 relative z-10">
                        <h3 class="text-sm font-black tracking-wide">হেল্প ও সাপোর্ট সেন্টার</h3>
                        <p class="text-[10px] text-indigo-300 font-medium">যেকোনো সমস্যা সমাধানে আমরা আছি আপনার পাশে</p>
                    </div>
                </div>

                <!-- Support Channels Grid -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between px-1">
                        <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider">আমাদের অফিশিয়াল সাপোর্ট চ্যানেলসমূহ</h3>
                        <span class="text-[10px] text-slate-400">Live Support</span>
                    </div>

                    <div class="space-y-3">
                        <!-- Telegram Support Bot/Group -->
                        <a href="https://t.me/rootvaofficialsell" target="_blank" class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm flex items-center justify-between hover:bg-slate-50 active:scale-[0.98] transition-all">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-11 h-11 rounded-xl bg-sky-500/10 text-sky-500 flex items-center justify-center text-lg shadow-inner">
                                    <i class="fa-brands fa-telegram"></i>
                                </div>
                                <div class="text-left">
                                    <h4 class="text-xs font-bold text-slate-800">টেলিগ্রাম সাপোর্ট গ্রুপ</h4>
                                    <p class="text-[9px] text-slate-400 mt-0.5">সবচেয়ে দ্রুত সাপোর্ট পেতে জয়েন করুন</p>
                                </div>
                            </div>
                            <span class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-2.5 py-1.5 rounded-xl flex items-center space-x-1">
                                <span>Join Now</span> <i class="fa-solid fa-arrow-right text-[8px]"></i>
                            </span>
                        </a>

                        <!-- Telegram Workers Group -->
                        <a href="https://t.me/smm_workers" target="_blank" class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm flex items-center justify-between hover:bg-slate-50 active:scale-[0.98] transition-all">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-11 h-11 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center text-lg shadow-inner">
                                    <i class="fa-brands fa-telegram"></i>
                                </div>
                                <div class="text-left">
                                    <h4 class="text-xs font-bold text-slate-800">অফিশিয়াল ওয়ার্কার্স গ্রুপ</h4>
                                    <p class="text-[9px] text-slate-400 mt-0.5">নিয়মিত কাজের আপডেট ও নির্দেশিকা</p>
                                </div>
                            </div>
                            <span class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-2.5 py-1.5 rounded-xl flex items-center space-x-1">
                                <span>Join Group</span> <i class="fa-solid fa-arrow-right text-[8px]"></i>
                            </span>
                        </a>

                        <!-- WhatsApp Support -->
                        <a href="https://wa.me/8801725838080" target="_blank" class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm flex items-center justify-between hover:bg-slate-50 active:scale-[0.98] transition-all">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-lg shadow-inner">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </div>
                                <div class="text-left">
                                    <h4 class="text-xs font-bold text-slate-800">হোয়াটসঅ্যাপ ডিরেক্ট সাপোর্ট</h4>
                                    <p class="text-[9px] text-slate-400 mt-0.5">সরাসরি চ্যাট করতে ক্লিক করুন</p>
                                </div>
                            </div>
                            <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2.5 py-1.5 rounded-xl flex items-center space-x-1">
                                <span>Chat Now</span> <i class="fa-solid fa-arrow-right text-[8px]"></i>
                            </span>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <!-- Non-Verified Account Overlay Lock Screen -->
        <div id="verification-lock-screen" class="absolute inset-0 bg-slate-50/95 backdrop-blur-md z-[80] flex flex-col justify-center items-center p-6 text-center space-y-6 hidden">
            <div class="w-20 h-20 rounded-3xl bg-red-500/10 text-red-600 flex items-center justify-center text-4xl shadow-md border border-red-500/20 animate-bounce">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="space-y-2 max-w-xs">
                <h2 class="text-lg font-black text-slate-800">অ্যাকাউন্ট ভেরিফিকেশন প্রয়োজন</h2>
                <p class="text-xs text-slate-500 leading-relaxed font-medium">দুঃখিত, রুটবা SMM পোর্টাল ব্যবহার করতে হলে প্রথমে আপনার রুটবা মেম্বারশিপ অ্যাকাউন্টটি ভেরিফাই করতে হবে।</p>
            </div>
            <button onclick="showCustomAlert('ভেরিফিকেশন সম্পন্ন করার জন্য মূল রুটবা অ্যাপ ওপেন করুন।', 'info')" class="w-full max-w-xs bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl active:scale-95 transition-all text-xs flex items-center justify-center space-x-2 shadow-lg shadow-blue-600/10">
                <i class="fa-solid fa-key"></i> <span>ভেরিফাই করুন</span>
            </button>
        </div>

        <!-- Unified Bottom Navigation Menu (Only visible post-login) -->
        <div id="app-nav" class="hidden absolute bottom-0 left-0 right-0 bg-white border-t border-slate-100 px-4 py-2 flex items-center justify-between rounded-t-3xl shadow-[0_-8px_30px_rgb(0,0,0,0.04)] z-50">
            <button onclick="navClick('home')" class="flex flex-col items-center justify-center py-1 flex-1 text-slate-400 active-tab" id="nav-home">
                <i class="fa-solid fa-house text-lg"></i>
                <span class="text-[10px] mt-0.5 font-medium">Home</span>
            </button>
            <button onclick="navClick('wallet')" class="flex flex-col items-center justify-center py-1 flex-1 text-slate-400" id="nav-wallet">
                <i class="fa-solid fa-wallet text-lg"></i>
                <span class="text-[10px] mt-0.5 font-medium">History</span>
            </button>
            <button onclick="navClick('support')" class="flex flex-col items-center justify-center py-1 flex-1 text-slate-400" id="nav-support">
                <i class="fa-solid fa-headset text-lg"></i>
                <span class="text-[10px] mt-0.5 font-medium">Support</span>
            </button>
        </div>

    </div>

    <!-- Custom Glassmorphic Alert Modal -->
    <div id="custom-alert-modal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden transition-all duration-300">
        <div class="glass-card w-11/12 max-w-sm rounded-3xl p-6 border border-white/20 shadow-2xl flex flex-col items-center text-center space-y-4 scale-in">
            <!-- Icon Wrapper -->
            <div id="alert-icon-wrapper" class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl shadow-md">
                <i id="alert-icon" class="fa-solid"></i>
            </div>
            <!-- Title -->
            <h3 id="alert-title" class="text-md font-bold text-slate-800 tracking-wide">Alert</h3>
            <!-- Message -->
            <p id="alert-message" class="text-xs text-slate-500 leading-relaxed font-medium whitespace-pre-line"></p>
            <!-- Close Button -->
            <button onclick="closeCustomAlert()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl shadow-lg shadow-blue-600/10 active:scale-95 transition-all text-sm">
                OK
            </button>
        </div>
    </div>

    <!-- Profile Info Modal -->
    <div id="profile-modal" class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden transition-all duration-300">
        <div class="glass-card w-11/12 max-w-sm rounded-3xl p-6 border border-white/20 shadow-2xl space-y-5 scale-in">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-800 text-sm">আমার প্রোফাইল</h3>
                <button onclick="toggleProfileModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <!-- User Info Card -->
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 rounded-2xl bg-blue-500/10 flex items-center justify-center font-bold text-xl text-blue-600 overflow-hidden relative shadow-inner">
                    <img id="modal-profile-pic" src="" class="w-full h-full object-cover hidden">
                    <span id="modal-profile-placeholder">R</span>
                </div>
                <div>
                    <h4 id="modal-username" class="font-bold text-slate-800 text-sm">Name</h4>
                    <span id="modal-verification-badge" class="inline-block text-[9px] px-2 py-0.5 rounded-full font-bold uppercase mt-1">Status</span>
                </div>
            </div>

            <!-- Detail List -->
            <div class="space-y-3.5 text-xs text-slate-600 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-400 uppercase tracking-wider text-[10px]">User ID (Phone)</span>
                    <span id="modal-user-id" class="font-semibold text-slate-800">01XXXXXXX</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-400 uppercase tracking-wider text-[10px]">Email Address</span>
                    <span id="modal-user-email" class="font-semibold text-slate-800">email@example.com</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-400 uppercase tracking-wider text-[10px]">Refer Code</span>
                    <div class="flex items-center space-x-2">
                        <span id="modal-refer-code" class="font-extrabold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">XXXXXX</span>
                        <button onclick="copyReferCode()" class="text-indigo-600 font-bold active:scale-95 transition-all"><i class="fa-solid fa-copy"></i></button>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-2.5">
                <button onclick="showCustomAlert('প্রোফাইল পরিবর্তনের জন্য অনুগ্রহ করে রুটবা মূল অ্যাপ ব্যবহার করুন।', 'info')" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-3 rounded-xl active:scale-95 transition-all text-xs flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-user-pen"></i> <span>Edit Profile</span>
                </button>
                <button onclick="handleLogout()" class="w-full bg-red-50 hover:bg-red-100 text-red-600 font-semibold py-3 rounded-xl active:scale-95 transition-all text-xs flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-power-off"></i> <span>Logout</span>
                </button>
            </div>
        </div>
    </div>

    <!-- SMM Entrance Announcement Pop-up Modal -->
    <div id="entrance-popup-modal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden transition-all duration-300">
        <div class="glass-card w-11/12 max-w-sm rounded-3xl overflow-hidden border border-white/20 shadow-2xl flex flex-col scale-in relative max-h-[85vh]">
            <!-- Close Button -->
            <button onclick="closeEntrancePopup()" class="absolute top-3 right-3 z-50 w-7 h-7 rounded-full bg-slate-950/40 hover:bg-slate-950/60 text-white flex items-center justify-center transition-all active:scale-90">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <!-- Banner Image -->
            <div class="w-full bg-slate-900 overflow-hidden relative border-b border-white/10">
                <img src="/assets/img/smm_banner.png" alt="SMM Announcement" style="width: 100% !important; height: auto !important; display: block !important; max-width: 100% !important;">
            </div>

            <!-- Scrollable Content -->
            <div class="p-5 overflow-y-auto space-y-4 text-slate-700 bg-white/95">
                <div class="text-center pb-2 border-b border-slate-100">
                    <h3 class="text-sm font-black text-slate-800 tracking-wide">🎉 SMM Work এ স্বাগতম! 🎉</h3>
                    <p class="text-[10px] text-slate-400 font-semibold mt-1">Social Media Marketing Platform</p>
                </div>

                <div class="space-y-3 text-xs leading-relaxed font-medium">
                    <p class="text-center font-bold text-blue-600 text-[13px] bg-blue-50 py-1.5 rounded-xl">
                        💻 Social Work Rootva
                    </p>
                    <p class="text-center text-slate-600 font-bold">
                        ঘরে বসে অনলাইনে কাজ করে আয়ের সুযোগ! 💰
                    </p>

                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 space-y-1.5">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">💼 ৩টি Marketing সেবা:</span>
                        <div class="grid grid-cols-3 gap-1.5 text-center text-[10px] font-bold text-slate-700">
                            <span class="bg-white border border-slate-200/50 py-1 rounded-lg">📧 Gmail</span>
                            <span class="bg-white border border-slate-200/50 py-1 rounded-lg">📘 Facebook</span>
                            <span class="bg-white border border-slate-200/50 py-1 rounded-lg">📸 Instagram</span>
                        </div>
                    </div>

                    <div class="bg-indigo-50/50 p-3.5 rounded-2xl border border-indigo-100/50 space-y-2">
                        <span class="text-[10px] font-black text-indigo-700 uppercase tracking-wider block">📲 যোগ দিন আমাদের টেলিগ্রাম Official Sell Group-এ:</span>
                        <div class="space-y-1.5 text-[10px]">
                            <a href="https://t.me/+G7xXBRMdniQ0M2Q1" target="_blank" class="flex items-center justify-between bg-white hover:bg-slate-50 border border-slate-100 px-2.5 py-1.5 rounded-xl text-slate-700 active:scale-95 transition-all">
                                <span class="font-bold flex items-center"><i class="fa-brands fa-instagram text-pink-500 mr-1.5"></i> Instagram Sell Group</span>
                                <i class="fa-solid fa-chevron-right text-[8px] text-slate-400"></i>
                            </a>
                            <a href="https://t.me/rootvaemail" target="_blank" class="flex items-center justify-between bg-white hover:bg-slate-50 border border-slate-100 px-2.5 py-1.5 rounded-xl text-slate-700 active:scale-95 transition-all">
                                <span class="font-bold flex items-center"><i class="fa-solid fa-envelope text-blue-500 mr-1.5"></i> Gmail Sell Group</span>
                                <i class="fa-solid fa-chevron-right text-[8px] text-slate-400"></i>
                            </a>
                            <a href="https://t.me/rootvaofficialsell" target="_blank" class="flex items-center justify-between bg-white hover:bg-slate-50 border border-slate-100 px-2.5 py-1.5 rounded-xl text-slate-700 active:scale-95 transition-all">
                                <span class="font-bold flex items-center"><i class="fa-brands fa-facebook text-blue-600 mr-1.5"></i> Facebook Sell Group</span>
                                <i class="fa-solid fa-chevron-right text-[8px] text-slate-400"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="text-center pt-2 border-t border-slate-100 space-y-1.5">
                    <p class="text-[10px] text-slate-500 font-bold">✨ ঘরে বসে আয় করুন নিরাপদে!</p>
                    <button onclick="closeEntrancePopup()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-blue-600/10 active:scale-95 transition-all text-xs">
                        আজই Rootva-এর সাথে কাজ শুরু করুন
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripting for UI operations & API communication -->
    <script>
        // Custom Alert Modal Support
        let alertCallback = null;

        function showCustomAlert(message, type = 'info', title = '', callback = null) {
            alertCallback = callback;
            const modal = document.getElementById('custom-alert-modal');
            const wrapper = document.getElementById('alert-icon-wrapper');
            const icon = document.getElementById('alert-icon');
            const titleEl = document.getElementById('alert-title');
            const msgEl = document.getElementById('alert-message');

            msgEl.innerText = message;

            if (type === 'success') {
                titleEl.innerText = title || 'সফল হয়েছে';
                titleEl.className = 'text-md font-bold text-emerald-600 tracking-wide';
                wrapper.className = 'w-16 h-16 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-2xl shadow-md border border-emerald-500/20';
                icon.className = 'fa-solid fa-circle-check';
            } else if (type === 'error') {
                titleEl.innerText = title || 'ভুল হয়েছে';
                titleEl.className = 'text-md font-bold text-red-600 tracking-wide';
                wrapper.className = 'w-16 h-16 rounded-2xl bg-red-500/10 text-red-600 flex items-center justify-center text-2xl shadow-md border border-red-500/20';
                icon.className = 'fa-solid fa-circle-xmark';
            } else if (type === 'warning') {
                titleEl.innerText = title || 'সতর্কতা';
                titleEl.className = 'text-md font-bold text-amber-600 tracking-wide';
                wrapper.className = 'w-16 h-16 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center text-2xl shadow-md border border-amber-500/20';
                icon.className = 'fa-solid fa-triangle-exclamation';
            } else {
                titleEl.innerText = title || 'তথ্য';
                titleEl.className = 'text-md font-bold text-blue-600 tracking-wide';
                wrapper.className = 'w-16 h-16 rounded-2xl bg-blue-500/10 text-blue-600 flex items-center justify-center text-2xl shadow-md border border-blue-500/20';
                icon.className = 'fa-solid fa-circle-info';
            }

            modal.classList.remove('hidden');
        }

        function closeCustomAlert() {
            document.getElementById('custom-alert-modal').classList.add('hidden');
            if (alertCallback) {
                alertCallback();
                alertCallback = null;
            }
        }

        // Override default window.alert
        window.alert = function(msg) {
            let type = 'info';
            if (msg.includes('ভুল') || msg.includes('ব্যর্থ') || msg.includes('সমস্যা') || msg.includes('হয়নি') || msg.includes('অবৈধ')) {
                type = 'error';
            } else if (msg.includes('সফল') || msg.includes('কপি')) {
                type = 'success';
            }
            showCustomAlert(msg, type);
        };

        let currentUser = null;
        let smmRates = {};
        let currentSelectedTask = '';

        // Helper function for FontAwesome SMM platform icons
        function getIconClass(key) {
            if (key === 'gmail') return 'fa-solid fa-envelope';
            if (key.startsWith('facebook')) return 'fa-brands fa-facebook';
            if (key === 'instagram') return 'fa-brands fa-instagram';
            if (key.startsWith('tiktok')) return 'fa-brands fa-tiktok';
            if (key === 'whatsapp') return 'fa-brands fa-whatsapp';
            if (key === 'telegram') return 'fa-brands fa-telegram';
            return 'fa-solid fa-envelope';
        }

        // Check if user is already logged in
        window.addEventListener('DOMContentLoaded', () => {
            const savedUser = localStorage.getItem('smm_user');
            if (savedUser) {
                try {
                    currentUser = JSON.parse(savedUser);
                    // Fetch status directly
                    fetch('/api/get_smm_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                number: currentUser.number,
                                password: currentUser.password
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            document.getElementById('state-splash').classList.add('hidden');
                            if (data.status === 'success') {
                                currentUser.details = data.user;
                                document.getElementById('app-header').classList.remove('hidden');
                                document.getElementById('app-workspace').classList.remove('hidden');
                                document.getElementById('app-nav').classList.remove('hidden');
                                document.getElementById('username').innerText = data.user.name;
                                showDashboard();
                                showEntrancePopup();
                            } else {
                                localStorage.removeItem('smm_user');
                                document.getElementById('state-login').classList.remove('hidden');
                            }
                        })
                        .catch(err => {
                            document.getElementById('state-splash').classList.add('hidden');
                            document.getElementById('state-login').classList.remove('hidden');
                        });
                } catch (e) {
                    localStorage.removeItem('smm_user');
                    setTimeout(() => {
                        document.getElementById('state-splash').classList.add('hidden');
                        document.getElementById('state-login').classList.remove('hidden');
                    }, 1000);
                }
            } else {
                setTimeout(() => {
                    // Remove splash screen and show login
                    document.getElementById('state-splash').classList.add('hidden');
                    document.getElementById('state-login').classList.remove('hidden');
                }, 1500);
            }
        });

        function handleLogin() {
            const num = document.getElementById('login-number').value;
            const pass = document.getElementById('login-password').value;

            if (!num || !pass) {
                alert('দয়া করে নম্বর ও পাসওয়ার্ড দিন');
                return;
            }

            fetch('/api/get_smm_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        number: num,
                        password: pass
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        currentUser = {
                            number: num,
                            password: pass,
                            details: data.user
                        };
                        localStorage.setItem('smm_user', JSON.stringify({
                            number: num,
                            password: pass
                        }));

                        // Trigger state switch
                        document.getElementById('state-login').classList.add('hidden');
                        document.getElementById('app-header').classList.remove('hidden');
                        document.getElementById('app-workspace').classList.remove('hidden');
                        document.getElementById('app-nav').classList.remove('hidden');

                        document.getElementById('username').innerText = data.user.name;
                        showDashboard();
                        showEntrancePopup();
                    } else {
                        alert(data.message || 'লগইন ব্যর্থ হয়েছে');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('কানেকশন সমস্যা। অনুগ্রহ করে আবার চেষ্টা করুন।');
                });
        }

        // Entrance Pop-up support
        function showEntrancePopup() {
            document.getElementById('entrance-popup-modal').classList.remove('hidden');
        }

        function closeEntrancePopup() {
            document.getElementById('entrance-popup-modal').classList.add('hidden');
        }

        // Profile Modal and Logout triggers
        function toggleProfileModal() {
            const modal = document.getElementById('profile-modal');
            modal.classList.toggle('hidden');
        }

        function copyReferCode() {
            const rc = document.getElementById('modal-refer-code').innerText;
            navigator.clipboard.writeText(rc).then(() => {
                showCustomAlert('রেফার কোড কপি করা হয়েছে!', 'success');
            });
        }

        function handleLogout() {
            localStorage.removeItem('smm_user');
            currentUser = null;

            // Hide modal, header, work screens, lockscreen, and show login form
            document.getElementById('profile-modal').classList.add('hidden');
            document.getElementById('app-header').classList.add('hidden');
            document.getElementById('app-workspace').classList.add('hidden');
            document.getElementById('app-nav').classList.add('hidden');
            document.getElementById('verification-lock-screen').classList.add('hidden');
            document.getElementById('section-support').classList.add('hidden');
            document.getElementById('state-login').classList.remove('hidden');
        }

        // Handle Android Device Back Button Navigation using HTML5 History API
        function pushSectionState(sectionName) {
            if (window.location.hash !== '#' + sectionName) {
                history.pushState({ section: sectionName }, '', '#' + sectionName);
            }
        }

        window.addEventListener('popstate', function(e) {
            const taskSec = document.getElementById('section-task');
            const walletSec = document.getElementById('section-wallet');
            const supportSec = document.getElementById('section-support');

            const isSubSectionOpen = (taskSec && !taskSec.classList.contains('hidden')) ||
                                    (walletSec && !walletSec.classList.contains('hidden')) ||
                                    (supportSec && !supportSec.classList.contains('hidden'));

            if (isSubSectionOpen) {
                showDashboard(false);
            }
        });

        function navClick(tab, pushState = true) {
            document.querySelectorAll('button[id^="nav-"]').forEach(btn => btn.classList.remove('active-tab'));
            const targetNav = document.getElementById('nav-' + tab);
            if (targetNav) targetNav.classList.add('active-tab');

            // Hide sections
            document.getElementById('section-dashboard').classList.add('hidden');
            document.getElementById('section-task').classList.add('hidden');
            document.getElementById('section-wallet').classList.add('hidden');
            document.getElementById('section-support').classList.add('hidden');

            if (tab === 'home') {
                showDashboard(pushState);
            } else if (tab === 'wallet') {
                document.getElementById('section-wallet').classList.remove('hidden');
                if (pushState) pushSectionState('wallet');
                loadSmmData();
            } else if (tab === 'support') {
                document.getElementById('section-support').classList.remove('hidden');
                if (pushState) pushSectionState('support');
            }
        }

        function showDashboard(pushState = true) {
            document.getElementById('section-task').classList.add('hidden');
            document.getElementById('section-wallet').classList.add('hidden');
            document.getElementById('section-support').classList.add('hidden');
            document.getElementById('section-dashboard').classList.remove('hidden');
            document.querySelectorAll('button[id^="nav-"]').forEach(btn => btn.classList.remove('active-tab'));
            const homeNav = document.getElementById('nav-home');
            if (homeNav) homeNav.classList.add('active-tab');
            if (pushState && window.location.hash) {
                history.replaceState({ section: 'home' }, '', window.location.pathname);
            }
            loadSmmData();
        }

        function loadSmmData() {
            if (!currentUser) return;
            fetch('/api/get_smm_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        number: currentUser.number,
                        password: currentUser.password
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        smmRates = data.rates;

                        // Enforce verification restriction
                        const lockScreen = document.getElementById('verification-lock-screen');

                        if (data.user.is_verified !== 1) {
                            lockScreen.classList.remove('hidden');
                            document.getElementById('wallet-pending-count').innerText = '0';
                            document.getElementById('wallet-success-rate').innerText = '0';
                            document.getElementById('wallet-success-rate-bar').style.width = '0%';
                            document.getElementById('wallet-today-count').innerText = '0';
                            document.getElementById('wallet-total-balance').innerText = '0.00';
                            document.getElementById('dashboard-balance').innerText = '0.00';
                            document.getElementById('projects-grid').innerHTML = '<p class="text-xs text-red-500 text-center col-span-2 py-4"><i class="fa-solid fa-lock mr-1.5"></i>কাজ দেখতে একাউন্ট ভেরিফাই করুন</p>';
                            document.getElementById('submissions-list').innerHTML = '<p class="text-xs text-red-500 text-center py-4">হিস্ট্রি দেখতে একাউন্ট ভেরিফাই করুন</p>';

                            // Fill Modal Details
                            document.getElementById('modal-username').innerText = data.user.name;
                            document.getElementById('modal-user-id').innerText = data.user.number;
                            document.getElementById('modal-user-email').innerText = data.user.email || 'N/A';
                            document.getElementById('modal-refer-code').innerText = data.user.referCode || 'N/A';

                            const badge = document.getElementById('modal-verification-badge');
                            badge.innerText = 'Unverified';
                            badge.className = 'inline-block text-[9px] px-2.5 py-0.5 rounded-full font-bold uppercase mt-1 bg-red-50 text-red-700 border border-red-100';

                            // Header and modal placeholders
                            const nameLetter = data.user.name ? data.user.name.charAt(0).toUpperCase() : 'U';
                            document.getElementById('user-profile-placeholder').innerText = nameLetter;
                            document.getElementById('modal-profile-placeholder').innerText = nameLetter;
                            return; // Stop rendering dashboard details
                        } else {
                            lockScreen.classList.add('hidden');
                        }

                        document.getElementById('dashboard-balance').innerText = data.user.wallet_balance.toFixed(2);

                        // Update Redesigned Wallet values
                        document.getElementById('wallet-total-balance').innerText = data.total_smm_earnings.toFixed(2);

                        // Set pending count from backend actual count
                        document.getElementById('wallet-pending-count').innerText = data.pending_count;

                        // Set success rate stats and progress bar
                        document.getElementById('wallet-success-rate').innerText = data.success_rate.toFixed(1);
                        document.getElementById('wallet-success-rate-bar').style.width = data.success_rate + '%';

                        // Dynamic User Header & Modal Profile Image / placeholders
                        const avatarUrl = data.user.profile_pic_url;
                        const nameLetter = data.user.name ? data.user.name.charAt(0).toUpperCase() : 'U';

                        const headerImg = document.getElementById('user-profile-pic');
                        const headerPl = document.getElementById('user-profile-placeholder');
                        const modalImg = document.getElementById('modal-profile-pic');
                        const modalPl = document.getElementById('modal-profile-placeholder');

                        if (avatarUrl) {
                            headerImg.src = avatarUrl;
                            headerImg.classList.remove('hidden');
                            headerPl.classList.add('hidden');

                            modalImg.src = avatarUrl;
                            modalImg.classList.remove('hidden');
                            modalPl.classList.add('hidden');
                        } else {
                            headerImg.classList.add('hidden');
                            headerPl.innerText = nameLetter;
                            headerPl.classList.remove('hidden');

                            modalImg.classList.add('hidden');
                            modalPl.innerText = nameLetter;
                            modalPl.classList.remove('hidden');
                        }

                        // Fill Modal Details
                        document.getElementById('modal-username').innerText = data.user.name;
                        document.getElementById('modal-user-id').innerText = data.user.number;
                        document.getElementById('modal-user-email').innerText = data.user.email || 'N/A';
                        document.getElementById('modal-refer-code').innerText = data.user.referCode || 'N/A';

                        const badge = document.getElementById('modal-verification-badge');
                        badge.innerText = 'Verified';
                        badge.className = 'inline-block text-[9px] px-2.5 py-0.5 rounded-full font-bold uppercase mt-1 bg-emerald-50 text-emerald-700 border border-emerald-100';

                        // Calculate today's tasks
                        let todayCount = 0;
                        const todayStr = new Date().toISOString().split('T')[0];
                        data.recent_submissions.forEach(sub => {
                            if (sub.created_at && sub.created_at.startsWith(todayStr)) {
                                todayCount++;
                            }
                        });
                        document.getElementById('wallet-today-count').innerText = todayCount;

                        // Set dynamic global marquee announcement notice
                        if (data.global_notice) {
                            document.getElementById('marquee-notice').innerText = data.global_notice;
                        }

                        // Render project grid cards
                        let gridHtml = '';
                        let hasFacebook = false;
                        let hasInstagram = false;
                        let hasTiktok = false;
                        for (const [key, details] of Object.entries(data.rates)) {
                            if (key.startsWith('facebook')) {
                                if (!hasFacebook) {
                                    hasFacebook = true;
                                    // Output unified Facebook Sell card
                                    gridHtml += `
                                    <div onclick="selectTask('facebook')" class="glass-card rounded-2xl p-4 flex flex-col items-center text-center cursor-pointer active:scale-95 transition-all">
                                        <div class="w-11 h-11 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-lg mb-2.5 shadow">
                                            <i class="fa-brands fa-facebook text-white"></i>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800">Facebook Sell</h4>
                                        <span class="text-[10px] text-emerald-600 font-bold mt-1">Multi-Forms</span>
                                    </div>
                                `;
                                }
                                continue;
                            }

                            if (key.startsWith('instagram')) {
                                if (!hasInstagram) {
                                    hasInstagram = true;
                                    // Output unified Instagram Sell card
                                    gridHtml += `
                                    <div onclick="selectTask('instagram')" class="glass-card rounded-2xl p-4 flex flex-col items-center text-center cursor-pointer active:scale-95 transition-all">
                                        <div class="w-11 h-11 rounded-2xl bg-pink-500 text-white flex items-center justify-center text-lg mb-2.5 shadow">
                                            <i class="fa-brands fa-instagram text-white"></i>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800">Instagram Sell</h4>
                                        <span class="text-[10px] text-emerald-600 font-bold mt-1">Multi-Forms</span>
                                    </div>
                                `;
                                }
                                continue;
                            }

                            if (key.startsWith('tiktok')) {
                                if (!hasTiktok) {
                                    hasTiktok = true;
                                    // Output unified TikTok Sell card
                                    gridHtml += `
                                    <div onclick="selectTask('tiktok')" class="glass-card rounded-2xl p-4 flex flex-col items-center text-center cursor-pointer active:scale-95 transition-all">
                                        <div class="w-11 h-11 rounded-2xl bg-black text-white flex items-center justify-center text-lg mb-2.5 shadow">
                                            <i class="fa-brands fa-tiktok text-white"></i>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800">TikTok Sell</h4>
                                        <span class="text-[10px] text-emerald-600 font-bold mt-1">Multi-Forms</span>
                                    </div>
                                `;
                                }
                                continue;
                            }

                            let color = 'bg-blue-500';
                            if (key === 'whatsapp') {
                                color = 'bg-emerald-500';
                            } else if (key === 'telegram') {
                                color = 'bg-sky-500';
                            }

                            const inactiveClass = details.status !== 'active' ? 'opacity-60 grayscale' : '';

                            gridHtml += `
                            <div onclick="selectTask('${key}')" class="glass-card rounded-2xl p-4 flex flex-col items-center text-center cursor-pointer active:scale-95 transition-all ${inactiveClass}">
                                <div class="w-11 h-11 rounded-2xl ${color} text-white flex items-center justify-center text-lg mb-2.5 shadow">
                                    <i class="${getIconClass(key)} text-white"></i>
                                </div>
                                <h4 class="text-xs font-bold text-slate-800">${details.name}</h4>
                                <span class="text-[10px] text-emerald-600 font-bold mt-1">৳ ${details.rate.toFixed(2)}</span>
                            </div>
                        `;
                        }
                        document.getElementById('projects-grid').innerHTML = gridHtml;

                        // Render Custom Premium Wallet Analytics list cards (not duplicating mockup grid)
                        let analyticsHtml = '';
                        for (const [key, details] of Object.entries(data.rates)) {
                            let color = 'bg-blue-500/10 text-blue-600';
                            if (key === 'facebook') {
                                color = 'bg-blue-600/10 text-blue-600';
                            } else if (key === 'instagram') {
                                color = 'bg-pink-500/10 text-pink-500';
                            } else if (key === 'whatsapp') {
                                color = 'bg-emerald-500/10 text-emerald-600';
                            } else if (key === 'telegram') {
                                color = 'bg-sky-500/10 text-sky-500';
                            }

                            // Extract total counts and earnings per type
                            const typeInfo = data.analytics[key] || {
                                count: 0,
                                earnings: 0.0
                            };

                            analyticsHtml += `
                            <div class="bg-white border border-slate-100 rounded-2xl p-4 flex items-center justify-between shadow-sm">
                                <div class="flex items-center space-x-3.5">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg ${color}">
                                        <i class="${getIconClass(key)}"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800">${details.name}</h4>
                                        <span class="text-[10px] text-slate-400">Total Sells: ${typeInfo.count} items</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-black text-indigo-950 block">৳ ${typeInfo.earnings.toFixed(2)}</span>
                                    <span class="text-[9px] text-slate-400 font-medium">Earned</span>
                                </div>
                            </div>
                        `;
                        }
                        document.getElementById('wallet-analytics-grid').innerHTML = analyticsHtml;

                        // Store all submissions globally for client-side filtering
                        window.allRecentSubmissions = data.recent_submissions;
                        renderHistoryList(activeFilter);
                    }
                });
        }

        let activeFilter = 'all';

        function filterHistory(platform) {
            activeFilter = platform;

            // Update filter button styling
            const tabs = ['all', 'facebook', 'gmail', 'instagram', 'whatsapp', 'telegram'];
            tabs.forEach(t => {
                const btn = document.getElementById('btn-filter-' + t);
                if (!btn) return;
                if (t === platform) {
                    btn.className = 'px-3.5 py-1.5 rounded-full text-[10px] font-bold transition-all bg-blue-600 text-white shadow-sm';
                } else {
                    btn.className = 'px-3.5 py-1.5 rounded-full text-[10px] font-bold transition-all bg-white border border-slate-100 text-slate-600 hover:bg-slate-50';
                }
            });

            renderHistoryList(platform);
        }

        function renderHistoryList(filter) {
            const submissions = window.allRecentSubmissions || [];
            let filtered = submissions;
            if (filter !== 'all') {
                if (filter === 'facebook') {
                    filtered = submissions.filter(sub => sub.task_type.toLowerCase().startsWith('facebook'));
                } else if (filter === 'instagram') {
                    filtered = submissions.filter(sub => sub.task_type.toLowerCase().startsWith('instagram'));
                } else {
                    filtered = submissions.filter(sub => sub.task_type.toLowerCase() === filter.toLowerCase());
                }
            }

            let historyHtml = '';
            if (filtered.length === 0) {
                historyHtml = '<p class="text-xs text-slate-400 text-center py-8">কোনো কাজের হিস্ট্রি পাওয়া যায়নি</p>';
            } else {
                filtered.forEach(task => {
                    let badgeColor = 'bg-amber-50 text-amber-700 border border-amber-100';
                    if (task.status === 'approved') badgeColor = 'bg-emerald-50 text-emerald-700 border border-emerald-100';
                    else if (task.status === 'rejected') badgeColor = 'bg-red-50 text-red-700 border border-red-100';

                    // Premium brand colors and icons for history list cards
                    let bgIconColor = 'bg-blue-500/10 text-blue-600';
                    if (task.task_type === 'facebook') {
                        bgIconColor = 'bg-blue-600/10 text-blue-600';
                    } else if (task.task_type === 'instagram') {
                        bgIconColor = 'bg-pink-500/10 text-pink-500';
                    } else if (task.task_type === 'whatsapp') {
                        bgIconColor = 'bg-emerald-500/10 text-emerald-600';
                    } else if (task.task_type === 'telegram') {
                        bgIconColor = 'bg-sky-500/10 text-sky-500';
                    }

                    // Admin feedback (rejection reason) section
                    let feedbackHtml = '';
                    if (task.status === 'rejected' && task.admin_feedback) {
                        feedbackHtml = `
                            <div class="mt-2 text-[10px] text-red-500 bg-red-50/50 border border-red-100/50 rounded-lg p-2 flex items-start space-x-1.5">
                                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                                <span><strong>কারণ:</strong> ${task.admin_feedback}</span>
                            </div>
                        `;
                    }

                    historyHtml += `
                        <div class="bg-white border border-slate-100 p-3.5 rounded-2xl shadow-sm space-y-1">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2.5">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm ${bgIconColor}">
                                        <i class="${getIconClass(task.task_type)}"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 uppercase">${task.task_type} Sell</h4>
                                        <p class="text-[9px] text-slate-400 mt-0.5">${task.input_field_1}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-slate-800 block">৳ ${task.price}</span>
                                    <span class="inline-block text-[8px] px-2 py-0.5 rounded-full font-bold uppercase mt-1 ${badgeColor}">${task.status}</span>
                                </div>
                            </div>
                            ${feedbackHtml}
                        </div>
                    `;
                });
            }
            document.getElementById('submissions-list').innerHTML = historyHtml;
        }

        function selectTask(key) {
            const fbContainer = document.getElementById('facebook-subtask-container');
            const instaContainer = document.getElementById('instagram-subtask-container');
            const tiktokContainer = document.getElementById('tiktok-subtask-container');

            if (key === 'facebook') {
                fbContainer.classList.remove('hidden');
                fbContainer.classList.add('flex');
                instaContainer.classList.add('hidden');
                instaContainer.classList.remove('flex');
                if (tiktokContainer) {
                    tiktokContainer.classList.add('hidden');
                    tiktokContainer.classList.remove('flex');
                }
                switchFacebookSubtask('facebook_cookies');
                return;
            }

            if (key === 'instagram') {
                instaContainer.classList.remove('hidden');
                instaContainer.classList.add('flex');
                fbContainer.classList.add('hidden');
                fbContainer.classList.remove('flex');
                if (tiktokContainer) {
                    tiktokContainer.classList.add('hidden');
                    tiktokContainer.classList.remove('flex');
                }
                switchInstagramSubtask('instagram_2fa');
                return;
            }

            if (key === 'tiktok') {
                if (tiktokContainer) {
                    tiktokContainer.classList.remove('hidden');
                    tiktokContainer.classList.add('flex');
                }
                fbContainer.classList.add('hidden');
                fbContainer.classList.remove('flex');
                instaContainer.classList.add('hidden');
                instaContainer.classList.remove('flex');
                loadTaskDetails('tiktok');
                return;
            }

            fbContainer.classList.add('hidden');
            fbContainer.classList.remove('flex');
            instaContainer.classList.add('hidden');
            instaContainer.classList.remove('flex');
            if (tiktokContainer) {
                tiktokContainer.classList.add('hidden');
                tiktokContainer.classList.remove('flex');
            }

            loadTaskDetails(key);
        }

        function switchFacebookSubtask(subKey) {
            const subtaskKeys = ['facebook_cookies', 'facebook_zero_friend', 'facebook_number_id'];
            subtaskKeys.forEach(k => {
                const btn = document.getElementById('btn-subtask-' + k);
                if (!btn) return;
                if (k === subKey) {
                    btn.className = 'flex-1 text-[10px] font-bold py-2 rounded-xl transition-all bg-white text-blue-600 shadow-sm';
                } else {
                    btn.className = 'flex-1 text-[10px] font-bold py-2 rounded-xl transition-all text-slate-600 hover:bg-slate-50';
                }
            });

            loadTaskDetails(subKey);
        }

        function switchInstagramSubtask(subKey) {
            const subtaskKeys = ['instagram_2fa', 'instagram_cookies'];
            subtaskKeys.forEach(k => {
                const btn = document.getElementById('btn-subtask-' + k);
                if (!btn) return;
                if (k === subKey) {
                    btn.className = 'flex-1 text-[10px] font-bold py-2 rounded-xl transition-all bg-white text-pink-600 shadow-sm';
                } else {
                    btn.className = 'flex-1 text-[10px] font-bold py-2 rounded-xl transition-all text-slate-600 hover:bg-slate-50';
                }
            });

            loadTaskDetails(subKey);
        }

        function switchTiktokSubtask(subKey) {
            loadTaskDetails('tiktok');
        }

        function loadTaskDetails(key) {
            const task = smmRates[key];
            if (!task) return;

            if (task.status !== 'active') {
                alert(task.notice);
                return;
            }

            currentSelectedTask = key;

            // Show task workspace
            document.getElementById('section-dashboard').classList.add('hidden');
            document.getElementById('section-task').classList.remove('hidden');
            pushSectionState('task');

            document.getElementById('task-title').innerText = task.name;
            document.getElementById('task-rate').innerText = "Today's Price: ৳ " + task.rate.toFixed(2);
            document.getElementById('task-notice').innerText = task.notice;

            // Handle Brand Icons
            let bgClass = 'bg-blue-500';
            if (key.startsWith('facebook')) {
                bgClass = 'bg-blue-600';
            } else if (key === 'instagram') {
                bgClass = 'bg-pink-500';
            } else if (key.startsWith('tiktok')) {
                bgClass = 'bg-black';
            } else if (key === 'whatsapp') {
                bgClass = 'bg-emerald-500';
            } else if (key === 'telegram') {
                bgClass = 'bg-sky-500';
            }

            const iconCont = document.getElementById('task-icon-container');
            iconCont.className = `w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl shadow ${bgClass}`;
            document.getElementById('task-icon').className = getIconClass(key) + ' text-white';

            // Video Tutorial
            const tutBtn = document.getElementById('task-tutorial-btn');
            if (task.video_url) {
                tutBtn.href = task.video_url;
                tutBtn.classList.remove('hidden');
                tutBtn.classList.add('flex');
            } else {
                tutBtn.classList.add('hidden');
                tutBtn.classList.remove('flex');
            }

            // Daily password
            const pwdWrap = document.getElementById('wrapper-daily-password');
            if (task.daily_password) {
                document.getElementById('task-daily-password').innerText = task.daily_password;
                pwdWrap.classList.remove('hidden');
            } else {
                pwdWrap.classList.add('hidden');
            }

            // Load inputs dynamically based on config
            const inputsCont = document.getElementById('dynamic-inputs-container');
            let inputsHtml = '';

            if (task.required_fields && task.required_fields.length > 0) {
                task.required_fields.forEach((field, index) => {
                    // Make labels human readable
                    let labelName = field.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
                    let inputType = field.includes('password') ? 'text' : 'text';
                    let placeholder = `Enter your ${labelName.toLowerCase()}...`;

                    inputsHtml += `
                        <div>
                            <label class="text-xs font-semibold text-slate-600 block mb-1">${labelName}</label>
                            <input type="${inputType}" id="task-field-${index}" placeholder="${placeholder}" class="w-full text-sm border border-slate-200 px-3.5 py-2.5 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 transition-all">
                        </div>
                    `;
                });
            } else {
                // Fallback basic input
                inputsHtml = `
                    <div>
                        <label class="text-xs font-semibold text-slate-600 block mb-1">Details Link/Username</label>
                        <input type="text" id="task-field-0" placeholder="Type here..." class="w-full text-sm border border-slate-200 px-3.5 py-2.5 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 transition-all">
                    </div>
                `;
            }
            inputsCont.innerHTML = inputsHtml;
        }

        function copyDailyPassword() {
            const pwd = document.getElementById('task-daily-password').innerText;
            navigator.clipboard.writeText(pwd).then(() => {
                alert('পাসওয়ার্ড কপি করা হয়েছে!');
            });
        }

        function submitTaskProof() {
            const task = smmRates[currentSelectedTask];
            if (!task) return;

            let field1 = '';
            let field2 = '';
            let field3 = '';
            let field4 = '';

            if (task.required_fields && task.required_fields.length > 0) {
                const f1 = document.getElementById('task-field-0');
                const f2 = document.getElementById('task-field-1');
                const f3 = document.getElementById('task-field-2');
                const f4 = document.getElementById('task-field-3');

                field1 = f1 ? f1.value : '';
                field2 = f2 ? f2.value : '';
                field3 = f3 ? f3.value : '';
                field4 = f4 ? f4.value : '';
            } else {
                const f = document.getElementById('task-field-0');
                field1 = f ? f.value : '';
            }

            if (!field1) {
                alert('দয়া করে প্রয়োজনীয় ফিল্ডটি পূরণ করুন');
                return;
            }

            // Verify all configured fields are filled
            if (task.required_fields && task.required_fields.length > 0) {
                for (let i = 0; i < task.required_fields.length; i++) {
                    const el = document.getElementById('task-field-' + i);
                    if (el && !el.value) {
                        alert('দয়া করে সব ফিল্ড পূরণ করুন');
                        return;
                    }
                }
            }

            fetch('/api/submit_smm_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        number: currentUser.number,
                        password: currentUser.password,
                        task_type: currentSelectedTask,
                        field1: field1,
                        field2: field2,
                        field3: field3,
                        field4: field4
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        showDashboard();
                    } else {
                        alert(data.message || 'সাবমিট ব্যর্থ হয়েছে');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('কানেকশন সমস্যা। অনুগ্রহ করে আবার চেষ্টা করুন।');
                });
        }

        // Intercept Telegram URI schemes in Android WebView
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link || !link.href) return;
            
            let url = link.href;
            if (url.includes('t.me/') || url.startsWith('tg:')) {
                // 1. If link is tg:resolve?domain=username
                if (url.startsWith('tg:')) {
                    e.preventDefault();
                    let domainMatch = url.match(/domain=([a-zA-Z0-9_]+)/);
                    if (domainMatch) {
                        window.location.href = 'https://t.me/' + domainMatch[1];
                    }
                    return;
                }

                // 2. Direct Telegram Links
                if (/Android/i.test(navigator.userAgent)) {
                    e.preventDefault();
                    if (url.includes('/+')) {
                        // For private invite links, try intent:// to open Telegram app directly
                        let cleanPath = url.replace(/^https?:\/\//, '');
                        window.location.href = 'intent://' + cleanPath + '#Intent;scheme=https;package=org.telegram.messenger;end';
                    } else {
                        // Public link -> Open Telegram link directly
                        let match = url.match(/t\.me\/([a-zA-Z0-9_]{4,})$/);
                        if (match) {
                            window.location.href = 'https://t.me/' + match[1];
                        } else {
                            window.location.href = url;
                        }
                    }
                }
            }
        }, true);
    </script>
</body>

</html>