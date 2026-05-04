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
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\OnlineServiceOrderController;
use App\Http\Controllers\Admin\LeadershipController;
use App\Http\Controllers\Admin\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Admin Auth Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// Protected Admin Routes
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/stats', [DashboardController::class, 'getStatsJson'])->name('api.stats');

    // API Documentation
    Route::get('/api-endpoints', [\App\Http\Controllers\Admin\ApiDocumentController::class, 'index'])->name('api-endpoints.index');

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    Route::patch('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{id}/add-money', [UserController::class, 'addMoney'])->name('users.add-money');
    Route::post('/users/{id}/withdraw-money', [UserController::class, 'withdrawMoney'])->name('users.withdraw-money');

    // Financials
    Route::get('/money-requests', [MoneyRequestController::class, 'index'])->name('money-requests.index');
    Route::patch('/money-requests/{id}', [MoneyRequestController::class, 'update'])->name('money-requests.update');
    
    Route::get('/withdraw-requests', [WithdrawRequestController::class, 'index'])->name('withdraw-requests.index');
    Route::patch('/withdraw-requests/{id}', [WithdrawRequestController::class, 'update'])->name('withdraw-requests.update');

    // Microjobs
    Route::get('/microjobs', [MicrojobController::class, 'index'])->name('microjobs.index');
    Route::patch('/microjobs/{id}', [MicrojobController::class, 'update'])->name('microjobs.update');

    // Salary Requests
    Route::get('/salary-requests', [\App\Http\Controllers\Admin\SalaryRequestController::class, 'index'])->name('salary-requests.index');
    Route::post('/salary-requests/{id}/approve', [\App\Http\Controllers\Admin\SalaryRequestController::class, 'approve'])->name('salary-requests.approve');
    Route::post('/salary-requests/{id}/reject', [\App\Http\Controllers\Admin\SalaryRequestController::class, 'reject'])->name('salary-requests.reject');

    // Banners
    Route::get('/review-jobs', [ReviewJobManagementController::class, 'index'])->name('review-jobs.index');
    Route::get('/review-jobs/{job_id}/submissions', [ReviewJobManagementController::class, 'submissions'])->name('review-jobs.submissions');
    Route::post('/review-jobs/submissions/{id}/approve', [ReviewJobManagementController::class, 'approve'])->name('review-jobs.approve');
    Route::post('/review-jobs/submissions/{id}/reject', [ReviewJobManagementController::class, 'reject'])->name('review-jobs.reject');

    // Banners
    Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
    Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
    Route::delete('/banners/{id}', [BannerController::class, 'destroy'])->name('banners.destroy');

    // Reviews
    Route::get('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'store'])->name('reviews.store');
    Route::delete('/reviews/{id}', [\App\Http\Controllers\Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

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
            Route::get('/history', [LeadershipController::class, 'history'])->name('history');
            Route::get('/requests', [LeadershipController::class, 'requests'])->name('requests');
            Route::post('/requests/{id}/process', [LeadershipController::class, 'processRequest'])->name('process');
        });



        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/send', [\App\Http\Controllers\Admin\NotificationController::class, 'send'])->name('notifications.send');

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
        });
    });
});
