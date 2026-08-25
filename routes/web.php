<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\MoneyRequestController;
use App\Http\Controllers\Admin\WithdrawRequestController;
use App\Http\Controllers\Admin\MicrojobController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ReviewJobManagementController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\JobSettingsController;
use App\Http\Controllers\Admin\VerificationRequestController;
use App\Http\Controllers\Admin\RewardController;
use App\Http\Controllers\Admin\SimOfferController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\OnlineServiceOrderController;
use App\Http\Controllers\Admin\LeadershipController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PcashSettingsController;
use App\Http\Controllers\Admin\PcashSimOfferController;
use App\Http\Controllers\Admin\PcashLogController;
use App\Http\Controllers\Admin\SupportAdminReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $host = request()->getHost();
    $smmDomain = config('app.domain');
    if ($smmDomain && ($host === $smmDomain || $host === 'smm.' . $smmDomain)) {
        return view('smm.index');
    }
    return redirect()->route('admin.login');
});

// Admin Auth Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// SMM Subdomain Routing Groupings (For Live Production Subdomains)
if (config('app.domain')) {
    // SMM Portal Subdomain
    Route::domain(config('app.smm_portal_subdomain', 'smm') . '.' . config('app.domain'))->group(function () {
        Route::get('/', function () {
            return view('smm.index');
        })->name('smm.portal');
    });

    // SMM Dedicated Admin Subdomain
    Route::domain(config('app.smm_admin_subdomain', 'smmadmin') . '.' . config('app.domain'))->name('admin.smm.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.smm.login');
        });
        Route::get('/login', [\App\Http\Controllers\Admin\SmmPortalAdminController::class, 'showLogin'])->name('login');
        Route::post('/login', [\App\Http\Controllers\Admin\SmmPortalAdminController::class, 'login'])->name('login.submit');
        Route::get('/dashboard', [\App\Http\Controllers\Admin\SmmPortalAdminController::class, 'dashboard'])->name('dashboard');
        Route::post('/config/{taskType}', [\App\Http\Controllers\Admin\SmmPortalAdminController::class, 'updateConfig'])->name('config.update');
        Route::get('/logout', [\App\Http\Controllers\Admin\SmmPortalAdminController::class, 'logout'])->name('logout');
    });
}

// Fallback Path-Based Routes (Works locally on localhost/IP and as alternate URLs)
Route::get('/smm', function () {
    return view('smm.index');
})->name('smm.portal.fallback');

Route::prefix('admin/smm-panel')->name('admin.smm.fallback.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Admin\SmmPortalAdminController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Admin\SmmPortalAdminController::class, 'login'])->name('login.submit');
    Route::get('/dashboard', [\App\Http\Controllers\Admin\SmmPortalAdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/config/{taskType}', [\App\Http\Controllers\Admin\SmmPortalAdminController::class, 'updateConfig'])->name('config.update');
    Route::get('/logout', [\App\Http\Controllers\Admin\SmmPortalAdminController::class, 'logout'])->name('logout');
});

// Protected Admin Routes
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/stats', [DashboardController::class, 'getStatsJson'])->name('api.stats');
    Route::get('/clear-cache', [DashboardController::class, 'clearCache'])->name('clear-cache');

    // API Documentation
    Route::get('/api-endpoints', [\App\Http\Controllers\Admin\ApiDocumentController::class, 'index'])->name('api-endpoints.index');

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/top-holders', [UserController::class, 'topHolders'])->name('users.top-holders');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::patch('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{id}/add-money', [UserController::class, 'addMoney'])->name('users.add-money');
    Route::post('/users/{id}/withdraw-money', [UserController::class, 'withdrawMoney'])->name('users.withdraw-money');
    Route::post('/users/{id}/transfer-voucher', [UserController::class, 'transferVoucher'])->name('users.transfer-voucher');
    Route::post('/users/{id}/add-demo-order', [UserController::class, 'addDemoOrder'])->name('users.add-demo-order');

    // Financials
    Route::get('/money-requests', [MoneyRequestController::class, 'index'])->name('money-requests.index');
    Route::patch('/money-requests/{id}', [MoneyRequestController::class, 'update'])->name('money-requests.update');

    Route::get('/withdraw-requests', [WithdrawRequestController::class, 'index'])->name('withdraw-requests.index');
    Route::patch('/withdraw-requests/{id}', [WithdrawRequestController::class, 'update'])->name('withdraw-requests.update');
    Route::get('/support-admin-report', [SupportAdminReportController::class, 'index'])->name('support-admin-report.index');

    // Microjobs
    Route::get('/microjobs', [MicrojobController::class, 'index'])->name('microjobs.index');
    Route::get('/microjobs/create', [MicrojobController::class, 'create'])->name('microjobs.create');
    Route::post('/microjobs', [MicrojobController::class, 'store'])->name('microjobs.store');
    Route::get('/microjobs/{id}/edit', [MicrojobController::class, 'edit'])->name('microjobs.edit');
    Route::patch('/microjobs/{id}/update-job', [MicrojobController::class, 'updateJob'])->name('microjobs.update-job');
    Route::patch('/microjobs/{id}', [MicrojobController::class, 'update'])->name('microjobs.update');
    Route::delete('/microjobs/{id}', [MicrojobController::class, 'destroy'])->name('microjobs.destroy');

    Route::get('/microjobs/{job_id}/submissions', [MicrojobController::class, 'viewSubmissions'])->name('microjobs.submissions');
    Route::post('/microjobs/submissions/{id}/approve', [MicrojobController::class, 'approveSubmission'])->name('microjobs.submissions.approve');
    Route::post('/microjobs/submissions/{id}/reject', [MicrojobController::class, 'rejectSubmission'])->name('microjobs.submissions.reject');

    // Salary Requests
    Route::get('/salary-requests', [\App\Http\Controllers\Admin\SalaryRequestController::class, 'index'])->name('salary-requests.index');
    Route::post('/salary-requests/{id}/approve', [\App\Http\Controllers\Admin\SalaryRequestController::class, 'approve'])->name('salary-requests.approve');
    Route::post('/salary-requests/{id}/reject', [\App\Http\Controllers\Admin\SalaryRequestController::class, 'reject'])->name('salary-requests.reject');

    // Banners
    Route::get('/review-jobs', [ReviewJobManagementController::class, 'index'])->name('review-jobs.index');
    Route::get('/review-jobs/create', [ReviewJobManagementController::class, 'create'])->name('review-jobs.create');
    Route::post('/review-jobs', [ReviewJobManagementController::class, 'store'])->name('review-jobs.store');
    Route::get('/review-jobs/{id}/edit', [ReviewJobManagementController::class, 'edit'])->name('review-jobs.edit');
    Route::patch('/review-jobs/{id}', [ReviewJobManagementController::class, 'update'])->name('review-jobs.update');
    Route::delete('/review-jobs/{id}', [ReviewJobManagementController::class, 'destroy'])->name('review-jobs.destroy');

    Route::get('/review-jobs/{job_id}/submissions', [ReviewJobManagementController::class, 'submissions'])->name('review-jobs.submissions');
    Route::post('/review-jobs/submissions/{id}/approve', [ReviewJobManagementController::class, 'approve'])->name('review-jobs.approve');
    Route::post('/review-jobs/submissions/{id}/reject', [ReviewJobManagementController::class, 'reject'])->name('review-jobs.reject');

    // Banners
    Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
    Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
    Route::delete('/banners/{id}', [BannerController::class, 'destroy'])->name('banners.destroy');

    // Bottom Banners
    Route::get('/bottom-banners', [\App\Http\Controllers\Admin\BottomBannerController::class, 'index'])->name('bottom-banners.index');
    Route::post('/bottom-banners', [\App\Http\Controllers\Admin\BottomBannerController::class, 'store'])->name('bottom-banners.store');
    Route::delete('/bottom-banners/{id}', [\App\Http\Controllers\Admin\BottomBannerController::class, 'destroy'])->name('bottom-banners.destroy');

    // Reviews
    Route::get('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'store'])->name('reviews.store');
    Route::delete('/reviews/{id}', [\App\Http\Controllers\Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Support Center
    Route::get('/support-center', [\App\Http\Controllers\Admin\SupportCenterController::class, 'index'])->name('support-center.index');
    Route::post('/support-center/members', [\App\Http\Controllers\Admin\SupportCenterController::class, 'storeMember'])->name('support-center.members.store');
    Route::post('/support-center/members/{id}', [\App\Http\Controllers\Admin\SupportCenterController::class, 'updateMember'])->name('support-center.members.update');
    Route::delete('/support-center/members/{id}', [\App\Http\Controllers\Admin\SupportCenterController::class, 'destroyMember'])->name('support-center.members.destroy');
    Route::post('/support-center/services', [\App\Http\Controllers\Admin\SupportCenterController::class, 'storeService'])->name('support-center.services.store');
    Route::post('/support-center/services/{id}', [\App\Http\Controllers\Admin\SupportCenterController::class, 'updateService'])->name('support-center.services.update');
    Route::delete('/support-center/services/{id}', [\App\Http\Controllers\Admin\SupportCenterController::class, 'destroyService'])->name('support-center.services.destroy');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Service Modules
    Route::prefix('services')->group(function () {
        // Job Config
        Route::get('/job-settings', [JobSettingsController::class, 'index'])->name('job-settings.index');
        Route::post('/job-settings/status', [JobSettingsController::class, 'updateStatus'])->name('job-settings.updateStatus');
        Route::post('/job-settings/texts', [JobSettingsController::class, 'updateTexts'])->name('job-settings.updateTexts');
        Route::post('/job-settings/tutorials', [JobSettingsController::class, 'updateTutorials'])->name('job-settings.updateTutorials');

        // Verifications
        Route::get('/verifications/bulk-cards-data', [VerificationRequestController::class, 'bulkCardsData'])->name('verifications.bulk-cards-data');
        Route::get('/verifications', [VerificationRequestController::class, 'index'])->name('verifications.index');
        Route::post('/verifications/{id}/approve', [VerificationRequestController::class, 'approve'])->name('verifications.approve');
        Route::post('/verifications/{id}/reject', [VerificationRequestController::class, 'reject'])->name('verifications.reject');

        // SIM Offers
        Route::get('/sim-offers', [SimOfferController::class, 'index'])->name('sim-offers.index');
        Route::post('/sim-offers', [SimOfferController::class, 'store'])->name('sim-offers.store');
        Route::prefix('sim-offers')->name('sim-offers.')->group(function () {
            Route::post('/bulk-store', [SimOfferController::class, 'bulkStore'])->name('bulk-store');
            Route::post('/update-settings', [SimOfferController::class, 'updateSettings'])->name('update-settings');
            Route::post('/requests/{id}/update', [SimOfferController::class, 'updateRequestStatus'])->name('update-request-status');
            Route::patch('/{id}', [SimOfferController::class, 'update'])->name('update');
            Route::delete('/{id}', [SimOfferController::class, 'destroy'])->name('destroy');
        });

        // Reselling Shop
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::post('/products/{id}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{id}/approve', [ProductController::class, 'approve'])->name('products.approve');

        // Vendors
        Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
        Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
        Route::post('/vendors/{id}/update', [VendorController::class, 'update'])->name('vendors.update');
        Route::delete('/vendors/{id}', [VendorController::class, 'destroy'])->name('vendors.destroy');

        // Orders
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::delete('/orders/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');

        // Product Categories
        Route::get('/product-categories', [ProductCategoryController::class, 'index'])->name('product-categories.index');
        Route::post('/product-categories', [ProductCategoryController::class, 'store'])->name('product-categories.store');
        Route::post('/product-categories/{id}/update', [ProductCategoryController::class, 'update'])->name('product-categories.update');
        Route::delete('/product-categories/{id}', [ProductCategoryController::class, 'destroy'])->name('product-categories.destroy');

        // Courses
        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::post('/courses/{id}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{id}', [CourseController::class, 'destroy'])->name('courses.destroy');

        // Online Services
        Route::get('/online-services', [ServiceController::class, 'index'])->name('online-services.index');
        Route::post('/online-services', [ServiceController::class, 'store'])->name('online-services.store');
        Route::post('/online-services/{id}', [ServiceController::class, 'update'])->name('online-services.update');
        Route::delete('/online-services/{id}', [ServiceController::class, 'destroy'])->name('online-services.destroy');

        // Online Service Orders
        Route::get('/online-service-orders', [OnlineServiceOrderController::class, 'index'])->name('online-service-orders.index');
        Route::post('/online-service-orders/{id}/status', [OnlineServiceOrderController::class, 'updateStatus'])->name('online-service-orders.updateStatus');

        // Leadership Rewards
        Route::prefix('leadership')->name('leadership.')->group(function () {
            Route::get('/leaders', [LeadershipController::class, 'leaders'])->name('leaders');
            Route::get('/history', [LeadershipController::class, 'history'])->name('history');
            Route::get('/requests', [LeadershipController::class, 'requests'])->name('requests');
            Route::post('/requests/{id}/process', [LeadershipController::class, 'processRequest'])->name('process');
        });



        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/send', [\App\Http\Controllers\Admin\NotificationController::class, 'send'])->name('notifications.send');
        Route::get('/notifications/saved', [\App\Http\Controllers\Admin\NotificationController::class, 'savedIndex'])->name('notifications.saved.index');
        Route::post('/notifications/saved', [\App\Http\Controllers\Admin\NotificationController::class, 'saveDraft'])->name('notifications.saved.store');
        Route::delete('/notifications/saved/{id}', [\App\Http\Controllers\Admin\NotificationController::class, 'deleteDraft'])->name('notifications.saved.destroy');
        Route::post('/notifications/saved/{id}/send', [\App\Http\Controllers\Admin\NotificationController::class, 'sendDraft'])->name('notifications.saved.send');

        // Popups
        Route::get('/popups', [\App\Http\Controllers\Admin\PopupController::class, 'index'])->name('popups.index');
        Route::post('/popups', [\App\Http\Controllers\Admin\PopupController::class, 'store'])->name('popups.store');
        Route::delete('/popups/{id}', [\App\Http\Controllers\Admin\PopupController::class, 'destroy'])->name('popups.destroy');

        // Rewards
        Route::prefix('rewards')->name('rewards.')->group(function () {
            Route::get('/daily', [RewardController::class, 'dailyIndex'])->name('daily');
            Route::post('/daily/run', [RewardController::class, 'runDailyDistribution'])->name('daily.run');
            Route::get('/weekly', [RewardController::class, 'weeklyIndex'])->name('weekly');
            Route::post('/weekly/run', [RewardController::class, 'runWeeklyDistribution'])->name('weekly.run');
            Route::get('/spin', [RewardController::class, 'spinHistory'])->name('spin');
            Route::get('/refer-bonus', [RewardController::class, 'referBonusIndex'])->name('refer-bonus');
            Route::post('/refer-bonus/distribute', [RewardController::class, 'distributeManualReferBonus'])->name('refer-bonus.distribute');
            Route::get('/refer-bonus/history', [RewardController::class, 'referBonusHistory'])->name('refer-bonus.history');
            Route::patch('/refer-bonus/{id}', [RewardController::class, 'editReferBonus'])->name('refer-bonus.update');
            Route::delete('/refer-bonus/{id}', [RewardController::class, 'deleteReferBonus'])->name('refer-bonus.delete');
            Route::get('/date-bonus', [RewardController::class, 'dateBonusIndex'])->name('date-bonus');
            Route::post('/date-bonus/distribute', [RewardController::class, 'distributeDateBonus'])->name('date-bonus.distribute');
        });

        // PCashMoney API Integration
        Route::prefix('pcash')->name('pcash.')->group(function () {
            Route::get('/settings', [PcashSettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings', [PcashSettingsController::class, 'update'])->name('settings.update');
            Route::resource('sim_offers', PcashSimOfferController::class);
            Route::get('/logs', [PcashLogController::class, 'index'])->name('logs.index');
        });

        // SMM Submissions Management
        Route::prefix('smm')->name('smm.')->group(function () {
            Route::get('/submissions', [\App\Http\Controllers\Admin\SmmSubmissionController::class, 'index'])->name('index');
            Route::post('/submissions/{id}/approve', [\App\Http\Controllers\Admin\SmmSubmissionController::class, 'approve'])->name('approve');
            Route::post('/submissions/{id}/reject', [\App\Http\Controllers\Admin\SmmSubmissionController::class, 'reject'])->name('reject');
        });
    });
});
