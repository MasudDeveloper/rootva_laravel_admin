@extends('layouts.admin')

@section('title', 'Global App Settings')
@section('page_title', 'System Configuration')

@section('content')
<div class="fade-in" x-data="{ activeTab: 'social' }">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">App Settings</h4>
            <p class="text-muted small mb-0">Manage social links, payment numbers, and app updates</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-pill px-4 mb-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        
        {{-- Custom Tabs --}}
        <div class="card-modern border-0 shadow-sm mb-4 p-2 bg-white rounded-pill d-inline-flex border overflow-auto max-w-100">
            <button type="button" class="btn rounded-pill px-3 transition-all whitespace-nowrap" :class="activeTab === 'social' ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none'" @click="activeTab = 'social'">
                <i class="fa-solid fa-share-nodes me-2"></i>Social Groups & Media
            </button>
            <button type="button" class="btn rounded-pill px-3 transition-all whitespace-nowrap" :class="activeTab === 'support' ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none'" @click="activeTab = 'support'">
                <i class="fa-solid fa-headset me-2"></i>Support & Meetings
            </button>
            <button type="button" class="btn rounded-pill px-3 transition-all whitespace-nowrap" :class="activeTab === 'work' ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none'" @click="activeTab = 'work'">
                <i class="fa-solid fa-briefcase me-2"></i>Project Work Links
            </button>
            <button type="button" class="btn rounded-pill px-4 transition-all whitespace-nowrap" :class="activeTab === 'payments' ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none'" @click="activeTab = 'payments'">
                <i class="fa-solid fa-credit-card me-2"></i>Payments & Fees
            </button>
            <button type="button" class="btn rounded-pill px-3 transition-all whitespace-nowrap" :class="activeTab === 'updates' ? 'btn-primary shadow-sm' : 'btn-link text-muted text-decoration-none'" @click="activeTab = 'updates'">
                <i class="fa-solid fa-cloud-arrow-up me-2"></i>App Version & Maintenance
            </button>
        </div>

        {{-- Tab Content --}}
        <div class="card-modern border-0 shadow-sm p-4 mb-4">
            
            {{-- Tab 1: Social Groups & Media --}}
            <div x-show="activeTab === 'social'" x-transition>
                <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-4">
                    <div>
                        <h5 class="fw-bold mb-1 text-primary"><i class="fa-solid fa-users-rectangle me-2"></i>কমিউনিটি ও সোশ্যাল মিডিয়া লিংক</h5>
                        <p class="text-muted small mb-0">অ্যান্ড্রয়েড অ্যাপের সাইডবার, হোমপেজ ও ড্যাশবোর্ডে ব্যবহৃত সোশ্যাল গ্রুপ ও চ্যানেলের লিংক</p>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Facebook Section -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-brands fa-facebook text-primary me-1"></i> Facebook Official Group Link</label>
                        <input type="text" name="facebook_group" class="form-control rounded-3" value="{{ $social->facebook_group }}" placeholder="https://www.facebook.com/groups/...">
                        <span class="text-muted extra-small">হোম স্ক্রিন ও সাইড মেনুতে "Facebook Group" ক্লিক করলে এই লিংক খুলবে।</span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-brands fa-youtube text-danger me-1"></i> YouTube Channel Link</label>
                        <input type="text" name="youtube_channel" class="form-control rounded-3" value="{{ $social->youtube_channel }}" placeholder="https://www.youtube.com/@...">
                        <span class="text-muted extra-small">হোম স্ক্রিন ও সাইড মেনুতে "YouTube" ক্লিক করলে এই চ্যানেল খুলবে।</span>
                    </div>

                    <!-- WhatsApp Section -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-brands fa-whatsapp text-success me-1"></i> WhatsApp Group (General)</label>
                        <input type="text" name="whatsapp_group" class="form-control rounded-3" value="{{ $social->whatsapp_group }}" placeholder="https://chat.whatsapp.com/...">
                        <span class="text-muted extra-small">সকল ইউজারদের জন্য সাধারণ WhatsApp গ্রুপ লিংক।</span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-brands fa-whatsapp text-success me-1"></i> WhatsApp Business Group</label>
                        <input type="text" name="whatsapp_business_group" class="form-control rounded-3" value="{{ $social->whatsapp_business_group }}" placeholder="https://chat.whatsapp.com/...">
                        <span class="text-muted extra-small">ভেরিফাইড মেম্বারদের জন্য প্রিমিয়াম বিজনেস WhatsApp গ্রুপ লিংক।</span>
                    </div>

                    <!-- Telegram Section -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-brands fa-telegram text-info me-1"></i> Telegram Main Group</label>
                        <input type="text" name="telegram_group" class="form-control rounded-3" value="{{ $social->telegram_group }}" placeholder="https://t.me/...">
                        <span class="text-muted extra-small">সাইডবারের মূল "টেলিগ্রাম গ্রুপ"-এ ব্যবহৃত লিংক।</span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-brands fa-telegram text-info me-1"></i> Telegram Customer Group</label>
                        <input type="text" name="telegram_customer_group" class="form-control rounded-3" value="{{ $social->telegram_customer_group }}" placeholder="https://t.me/...">
                        <span class="text-muted extra-small">সাইডবারের "টেলিগ্রাম কাস্টমার গ্রুপ"-এ ব্যবহৃত লিংক।</span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-brands fa-telegram text-info me-1"></i> Telegram Business Group</label>
                        <input type="text" name="telegram_business_group" class="form-control rounded-3" value="{{ $social->telegram_business_group }}" placeholder="https://t.me/...">
                        <span class="text-muted extra-small">লগইন বা অ্যাপ শুরু হওয়ার পর পপআপে "Telegram Business Group" হিসেবে ব্যবহৃত লিংক।</span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-solid fa-robot text-secondary me-1"></i> Telegram Earning Bot Link</label>
                        <input type="text" name="telegram_bot" class="form-control rounded-3" value="{{ $social->telegram_bot }}" placeholder="https://t.me/..._bot">
                        <span class="text-muted extra-small">অ্যাড ইনকাম ও আর্নিং বটের টেলিগ্রাম লিংক।</span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-brands fa-telegram text-info me-1"></i> Telegram Reselling Group</label>
                        <input type="text" name="telegram_reselling_group" class="form-control rounded-3" value="{{ $social->telegram_reselling_group }}" placeholder="https://t.me/rootvashop">
                        <span class="text-muted extra-small">রিসেলিং মেম্বারদের টেলিগ্রাম গ্রুপ লিংক।</span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-brands fa-telegram text-info me-1"></i> Telegram Sim Offer Group</label>
                        <input type="text" name="telegram_sim_offer_group" class="form-control rounded-3" value="{{ $social->telegram_sim_offer_group }}" placeholder="https://t.me/rootvasim">
                        <span class="text-muted extra-small">সিম অফার / ড্রাইভ সেকশনে ব্যবহৃত টেলিগ্রাম গ্রুপ লিংক।</span>
                    </div>
                </div>
            </div>

            {{-- Tab 2: Support Channels & Meeting Links --}}
            <div x-show="activeTab === 'support'" x-transition style="display: none;">
                <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-4">
                    <div>
                        <h5 class="fw-bold mb-1 text-info"><i class="fa-solid fa-headset me-2"></i>কাস্টমার কেয়ার, সাপোর্ট ও মিটিং লিংক</h5>
                        <p class="text-muted small mb-0">হোম স্ক্রিনের হেল্প আইকন, সাইডবার ও সাপোর্ট সংক্রান্ত সকল লিংক</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-brands fa-whatsapp text-success me-1"></i> Main Support Link / WhatsApp</label>
                        <input type="text" name="support_number" class="form-control rounded-3" value="{{ $social->support_number }}" placeholder="https://wa.me/... বা WhatsApp/FB লিংক">
                        <span class="text-muted extra-small">হোম স্ক্রিনের টপবারে থাকা সরাসরি সাপোর্ট আইকনে ক্লিক করলে এই লিংকে যাবে।</span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-brands fa-facebook text-primary me-1"></i> Support Facebook Official Page</label>
                        <input type="text" name="support_facebook" class="form-control rounded-3" value="{{ $social->support_facebook }}" placeholder="https://www.facebook.com/share/...">
                        <span class="text-muted extra-small">হোম স্ক্রিনের সোশ্যাল বক্সে "Facebook Page" বাটনে ক্লিক করলে এই পেজে নিয়ে যাবে।</span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-solid fa-user-shield text-warning me-1"></i> Verification Help / Support Link</label>
                        <input type="text" name="support_verify" class="form-control rounded-3" value="{{ $social->support_verify }}" placeholder="https://...">
                        <span class="text-muted extra-small">আইডি ভেরিফিকেশনে সহায়তার জন্য সাপোর্ট লিংক।</span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-solid fa-key text-danger me-1"></i> Password Recovery Support</label>
                        <input type="text" name="support_password" class="form-control rounded-3" value="{{ $social->support_password }}" placeholder="https://...">
                        <span class="text-muted extra-small">পাসওয়ার্ড রিসেট বা রিকভারির সহায়তার জন্য সাপোর্ট লিংক।</span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-solid fa-cart-shopping text-info me-1"></i> Reselling Support Link</label>
                        <input type="text" name="support_reselling" class="form-control rounded-3" value="{{ $social->support_reselling }}" placeholder="https://...">
                        <span class="text-muted extra-small">রিসেলিং সংক্রান্ত সাপোর্টের জন্য নির্ধারিত লিংক।</span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-success"><i class="fa-solid fa-qrcode text-success me-1"></i> বাংলা কিউআর সাপোর্ট সেন্টার লিঙ্ক (Bangla QR Support)</label>
                        <input type="text" name="bangla_qr_support_link" class="form-control rounded-3 border-success-subtle shadow-sm" value="{{ $payments->bangla_qr_support_link ?? $social->bangla_qr_support_link }}" placeholder="https://t.me/... বা https://wa.me/... বা FB লিঙ্ক">
                        <span class="text-muted extra-small">পেমেন্ট পেজে "সাপোর্ট সেন্টারে যোগাযোগ করুন" বাটনে ক্লিক করলে ইউজার এই লিংকে যাবে।</span>
                    </div>

                    <!-- Meeting Links -->
                    <div class="col-12 mt-4 pt-3 border-top">
                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-video text-primary me-2"></i>অনলাইন মিটিং লিংক (Meeting Links)</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-solid fa-user text-secondary me-1"></i> Customer Meeting Link</label>
                        <input type="text" name="customer_meeting" class="form-control rounded-3" value="{{ $social->customer_meeting }}" placeholder="https://...">
                        <span class="text-muted extra-small">সাধারণ (আনভেরিফাইড) ইউজারদের জন্য নির্ধারিত মিটিং ইউআরএল।</span>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold"><i class="fa-solid fa-user-tie text-success me-1"></i> Business Meeting Link</label>
                        <input type="text" name="business_meeting" class="form-control rounded-3" value="{{ $social->business_meeting }}" placeholder="https://...">
                        <span class="text-muted extra-small">ভেরিফাইড বিজনেস মেম্বারদের জন্য নির্ধারিত স্পেশাল মিটিং ইউআরএল।</span>
                    </div>
                </div>
            </div>

            {{-- Tab 3: Project Work Links --}}
            <div x-show="activeTab === 'work'" x-transition style="display: none;">
                <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-4">
                    <div>
                        <h5 class="fw-bold mb-1 text-success"><i class="fa-solid fa-briefcase me-2"></i>প্রজেক্ট ওয়ার্ক ও সাবমিশন লিংক</h5>
                        <p class="text-muted small mb-0">অ্যাপের প্রজেক্ট সেকশনের ফেসবুক, ইনস্টাগ্রাম, ইমেইল এবং টিকটক কাজের সাবমিট, টেলিগ্রাম ও মেসেঞ্জার লিংক</p>
                    </div>
                </div>

                <!-- Facebook Work -->
                <div class="card bg-light border-0 p-3 rounded-4 mb-4">
                    <h6 class="fw-bold text-primary mb-3"><i class="fa-brands fa-facebook me-2"></i>১. Facebook Project Work</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Facebook Work Submit URL</label>
                            <input type="text" name="facebook_work_submit" class="form-control rounded-3" value="{{ $social->facebook_work_submit }}" placeholder="Google Form বা সাবমিট লিংক">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Facebook Work Telegram</label>
                            <input type="text" name="facebook_work_telegram" class="form-control rounded-3" value="{{ $social->facebook_work_telegram }}" placeholder="টেলিগ্রাম চ্যানেল/গ্রুপ">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Facebook Work Messenger</label>
                            <input type="text" name="facebook_work_massenger" class="form-control rounded-3" value="{{ $social->facebook_work_massenger }}" placeholder="মেসেঞ্জার চ্যাট/গ্রুপ">
                        </div>
                    </div>
                </div>

                <!-- Instagram Work -->
                <div class="card bg-light border-0 p-3 rounded-4 mb-4">
                    <h6 class="fw-bold text-danger mb-3"><i class="fa-brands fa-instagram me-2"></i>২. Instagram Project Work</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Instagram Work Submit URL</label>
                            <input type="text" name="instagram_work_submit" class="form-control rounded-3" value="{{ $social->instagram_work_submit }}" placeholder="Google Form বা সাবমিট লিংক">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Instagram Work Telegram</label>
                            <input type="text" name="instagram_work_telegram" class="form-control rounded-3" value="{{ $social->instagram_work_telegram }}" placeholder="টেলিগ্রাম চ্যানেল/গ্রুপ">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Instagram Work Messenger</label>
                            <input type="text" name="instagram_work_massenger" class="form-control rounded-3" value="{{ $social->instagram_work_massenger }}" placeholder="মেসেঞ্জার চ্যাট/গ্রুপ">
                        </div>
                    </div>
                </div>

                <!-- TikTok Work -->
                <div class="card bg-light border-0 p-3 rounded-4 mb-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="fa-brands fa-tiktok me-2"></i>৩. TikTok Project Work</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">TikTok Work Submit URL</label>
                            <input type="text" name="tiktok_work_submit" class="form-control rounded-3" value="{{ $social->tiktok_work_submit }}" placeholder="Google Form বা সাবমিট লিংক">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">TikTok Work Telegram</label>
                            <input type="text" name="tiktok_work_telegram" class="form-control rounded-3" value="{{ $social->tiktok_work_telegram }}" placeholder="টেলিগ্রাম চ্যানেল/গ্রুপ">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">TikTok Work Messenger</label>
                            <input type="text" name="tiktok_work_massenger" class="form-control rounded-3" value="{{ $social->tiktok_work_massenger }}" placeholder="মেসেঞ্জার চ্যাট/গ্রুপ">
                        </div>
                    </div>
                </div>

                <!-- Email Work -->
                <div class="card bg-light border-0 p-3 rounded-4 mb-2">
                    <h6 class="fw-bold text-info mb-3"><i class="fa-solid fa-envelope me-2"></i>৪. Email Project Work</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Email Work Submit URL</label>
                            <input type="text" name="email_work_submit" class="form-control rounded-3" value="{{ $social->email_work_submit }}" placeholder="Google Form বা সাবমিট লিংক">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Email Work Telegram</label>
                            <input type="text" name="email_work_telegram" class="form-control rounded-3" value="{{ $social->email_work_telegram }}" placeholder="টেলিগ্রাম চ্যানেল/গ্রুপ">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Email Work Messenger</label>
                            <input type="text" name="email_work_massenger" class="form-control rounded-3" value="{{ $social->email_work_massenger }}" placeholder="মেসেঞ্জার চ্যাট/গ্রুপ">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 4: Payment Numbers --}}
            <div x-show="activeTab === 'payments'" x-transition style="display: none;">
                <h5 class="fw-bold mb-4 text-danger border-bottom pb-2">Cash-In Payment Methods</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">bKash Number</label>
                        <input type="text" name="bkash" class="form-control rounded-3" value="{{ $payments->bkash }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nagad Number</label>
                        <input type="text" name="nagad" class="form-control rounded-3" value="{{ $payments->nagad }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Rocket Number</label>
                        <input type="text" name="rocket" class="form-control rounded-3" value="{{ $payments->rocket }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Upay Number</label>
                        <input type="text" name="upay" class="form-control rounded-3" value="{{ $payments->upay }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Bangla QR / Merchant Number</label>
                        <input type="text" name="bangla_qr" class="form-control rounded-3" value="{{ $payments->bangla_qr ?? '01577095615' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-success"><i class="fa-solid fa-headset me-1"></i> Bangla QR Support Center Link</label>
                        <input type="text" name="bangla_qr_support_link" class="form-control rounded-3" value="{{ $payments->bangla_qr_support_link ?? $social->bangla_qr_support_link }}" placeholder="https://t.me/... বা https://wa.me/...">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Identity Verification Fee (৳)</label>
                        <input type="number" name="verify_amount" class="form-control rounded-3 shadow-sm border-primary" value="{{ $payments->verify_amount }}">
                        <p class="text-muted extra-small mt-1">Users will be charged this amount for account verification.</p>
                    </div>
                </div>
            </div>

            {{-- Tab 5: App Updates & Maintenance --}}
            <div x-show="activeTab === 'updates'" x-transition style="display: none;">
                <h5 class="fw-bold mb-4 text-warning border-bottom pb-2">Android App Versions</h5>
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">New Version Code (Int)</label>
                        <input type="number" name="version_code" class="form-control rounded-3" placeholder="e.g. 15">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Update URL (PlayStore/APK)</label>
                        <input type="text" name="update_link" class="form-control rounded-3" placeholder="https://...">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Update Message</label>
                        <textarea name="update_message" class="form-control rounded-3" rows="2" placeholder="What's new in this version?"></textarea>
                    </div>
                </div>

                <h5 class="fw-bold mt-5 mb-4 text-danger border-bottom pb-2">System Maintenance Mode</h5>
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Maintenance Status</label>
                        <select name="is_maintenance" class="form-select rounded-3">
                            <option value="0" {{ $maintenance['is_maintenance'] == 0 ? 'selected' : '' }}>Disabled (App is Online)</option>
                            <option value="1" {{ $maintenance['is_maintenance'] == 1 ? 'selected' : '' }}>Enabled (App is Offline)</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Countdown End Time (Select from Calendar / Time Picker)</label>
                        <input type="datetime-local" name="maintenance_countdown" class="form-control rounded-3" value="{{ !empty($maintenance['maintenance_countdown']) ? date('Y-m-d\TH:i', strtotime($maintenance['maintenance_countdown'])) : '' }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Maintenance Message</label>
                        <textarea name="maintenance_message" class="form-control rounded-3" rows="2" placeholder="Explain the maintenance details to the users...">{{ $maintenance['maintenance_message'] }}</textarea>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 text-muted text-uppercase">Update History</h6>
                <div class="table-responsive bg-light rounded-3 p-2 border">
                    <table class="table table-sm table-hover mb-0 extra-small">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Message</th>
                                <th>Link</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($updates as $update)
                            <tr>
                                <td class="fw-bold text-primary">{{ $update->version_code }}</td>
                                <td class="text-truncate" style="max-width: 200px;">{{ $update->update_message }}</td>
                                <td><a href="{{ $update->update_link }}" target="_blank" class="text-decoration-none"><i class="fa-solid fa-link"></i></a></td>
                                <td>{{ date('d-m-Y', strtotime($update->created_at)) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-2">No update history found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div class="d-flex justify-content-end gap-3">
            <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-lg">
                <i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings
            </button>
        </div>
    </form>
</div>

<style>
    .transition-all { transition: all 0.3s ease; }
    .card-modern { border-radius: 1.5rem; }
    .extra-small { font-size: 0.75rem; }
    .whitespace-nowrap { white-space: nowrap; }
    .max-w-100 { max-width: 100%; }
</style>
@endsection
