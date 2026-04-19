<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ModuleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/home', [ModuleController::class, 'getHomeData']);
Route::get('/sim-offers', [ModuleController::class, 'getSimOffers']);
Route::get('/products', [ModuleController::class, 'getProducts']);
Route::get('/courses', [ModuleController::class, 'getCourses']);
Route::get('/services', [ModuleController::class, 'getServices']);
Route::get('/profile', [ModuleController::class, 'getProfile']);

// Legacy API Routes (for Android App compatibility)
use App\Http\Controllers\Api\LegacyApiController;
Route::post('login.php', [LegacyApiController::class, 'login']);
Route::post('register.php', [AuthController::class, 'register']);
Route::post('get_Data.php', [LegacyApiController::class, 'getUserData']);
Route::get('get_banners.php', [LegacyApiController::class, 'getBanners']);
Route::get('get_reviews.php', [LegacyApiController::class, 'getReviews']);
Route::get('get_social_links.php', [LegacyApiController::class, 'getSocialLinks']);
Route::get('get_latest_update.php', [LegacyApiController::class, 'getUpdate']);
Route::post('get_wallet_balance.php', [LegacyApiController::class, 'getBalance']);
Route::get('get_transaction_history.php', [LegacyApiController::class, 'getTransactionHistory']);
Route::get('get_income_report.php', [LegacyApiController::class, 'getIncomeReport']);
Route::get('get_sim_offer.php', [LegacyApiController::class, 'getSimOffers']);
Route::get('get_categories.php', [LegacyApiController::class, 'getCategories']);
Route::get('get_products.php', [ModuleController::class, 'getProducts']);
Route::get('get_products_by_category.php', [ModuleController::class, 'getProducts']);
Route::get('get_referral_tree.php', [LegacyApiController::class, 'getReferralTree']);
Route::post('update_profile.php', [LegacyApiController::class, 'updateProfile']);
Route::get('get_payment_numbers.php', [LegacyApiController::class, 'getPaymentNumbers']);
Route::post('get_microjobs2.php', [LegacyApiController::class, 'getAvailableJobs']);
Route::post('add_money_request.php', [LegacyApiController::class, 'addMoneyRequest']);
Route::post('submit_money_withdraw_request.php', [LegacyApiController::class, 'withdrawRequest']);
Route::post('submit_verification_request.php', [LegacyApiController::class, 'submitVerificationRequest']);
Route::post('recharge_request.php', [LegacyApiController::class, 'doRecharge']);
Route::get('get_recharge_history.php', [LegacyApiController::class, 'getRechargeHistory']);
Route::get('get_course_progress.php', [LegacyApiController::class, 'getCourseProgress']);
Route::post('salary_request.php', [LegacyApiController::class, 'salaryRequest']);
Route::post('getRefer.php', [LegacyApiController::class, 'checkReferCode']);
Route::post('save_fcm_token.php', [LegacyApiController::class, 'saveFcmToken']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
