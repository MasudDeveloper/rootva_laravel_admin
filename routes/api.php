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
use App\Http\Controllers\Api\LeaderboardController;

Route::get('get_leaderboard.php', [LeaderboardController::class, 'getRanking']);
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
Route::get('get_income_history.php', [LegacyApiController::class, 'getIncomeHistory']);
Route::get('get_sim_offer.php', [LegacyApiController::class, 'getSimOffers']);
Route::get('get_categories.php', [LegacyApiController::class, 'getCategories']);
Route::get('get_products.php', [ModuleController::class, 'getProducts']);
Route::get('get_products_by_category.php', [ModuleController::class, 'getProducts']);
Route::get('get_referral_tree.php', [LegacyApiController::class, 'getReferralTree']);
Route::get('get_team_summary.php', [LegacyApiController::class, 'getTeamSummary']);
Route::get('get_referral_tree2.php', [LegacyApiController::class, 'searchUserInMyTree']);
Route::post('get_upline_details.php', [LegacyApiController::class, 'getUplineDetails']);
Route::post('update_profile.php', [LegacyApiController::class, 'updateProfile']);
Route::get('get_payment_numbers.php', [LegacyApiController::class, 'getPaymentNumbers']);
Route::post('get_microjobs2.php', [LegacyApiController::class, 'getAvailableJobs']);
Route::get('get_microjobs.php', [LegacyApiController::class, 'getAllMicrojobs']);
Route::post('submit_microjob.php', [LegacyApiController::class, 'submitMicrojob']);
Route::post('add_money_request.php', [LegacyApiController::class, 'addMoneyRequest']);
Route::get('get_money_requests.php', [LegacyApiController::class, 'getMoneyRequests']);
Route::post('submit_money_withdraw_request.php', [LegacyApiController::class, 'withdrawRequest']);
Route::get('get_withdraw_request.php', [LegacyApiController::class, 'getWithdrawRequests']);
Route::post('submit_verification_request.php', [LegacyApiController::class, 'submitVerificationRequest']);
Route::post('recharge_request.php', [LegacyApiController::class, 'doRecharge']);
Route::get('get_recharge_history.php', [LegacyApiController::class, 'getRechargeHistory']);
Route::get('get_courses.php', [LegacyApiController::class, 'getAllVideos']);
Route::get('get_course_progress.php', [LegacyApiController::class, 'getCourseProgress']);
Route::post('salary_request.php', [LegacyApiController::class, 'salaryRequest']);
Route::get('get_salary_request_status.php', [LegacyApiController::class, 'getSalaryStatus']);
Route::post('getRefer.php', [LegacyApiController::class, 'checkReferCode']);
Route::post('save_fcm_token.php', [LegacyApiController::class, 'saveFcmToken']);
Route::post('get_spin_data.php', [LegacyApiController::class, 'getSpinData']);
Route::post('spin_wheel.php', [LegacyApiController::class, 'submitSpinResult']);
Route::get('check-password-update.php', [LegacyApiController::class, 'checkPasswordUpdate']);
Route::post('upload_profile_pic.php', [LegacyApiController::class, 'uploadProfilePic']);
Route::post('submit_order_request.php', [LegacyApiController::class, 'submitOrder']);
Route::post('solve_math.php', [LegacyApiController::class, 'solveMath']);
Route::get('get_profile.php', [LegacyApiController::class, 'getProfile']);
Route::post('get_review_job.php', [LegacyApiController::class, 'getAvailableReviewJobs']);
Route::post('get_lock_review_job.php', [LegacyApiController::class, 'getLockReviewJobs']);
Route::get('get_review_job_social.php', [LegacyApiController::class, 'getReviewJobSocial']);
Route::post('lock_review_job.php', [LegacyApiController::class, 'lockReviewJob']);
Route::post('unlock_review_job.php', [LegacyApiController::class, 'unlockReviewJob']);
Route::post('submit_review_job_proof.php', [LegacyApiController::class, 'submitReviewJobProof']);
Route::post('mark_verification_popup_seen.php', [LegacyApiController::class, 'markVerificationPopupSeen']);

// Missing Endpoints added for functional parity
Route::get('get_daily_winners.php', [LeaderboardController::class, 'getDailyWinners']);
Route::get('get_daily_winners_by_date.php', [LeaderboardController::class, 'getDailyWinners']);
Route::get('get_daily_live_ranking.php', [LeaderboardController::class, 'getTodayLiveRanking']);
Route::get('get_weekly_winner.php', [LeaderboardController::class, 'getWeeklyWinner']);
Route::get('get_weekly_ranking.php', [LeaderboardController::class, 'getWeeklyRanking']);
Route::get('get_weekly_winners_by_date.php', [LeaderboardController::class, 'getWeeklyWinnersByDate']);

Route::get('get_popup.php', [LegacyApiController::class, 'getPopup']);
Route::get('get_tutorials.php', [LegacyApiController::class, 'getTutorials']);
Route::post('get_math_income.php', [LegacyApiController::class, 'getMathIncome']);
Route::get('get_salary_progress.php', [LegacyApiController::class, 'getSalaryProgress']);
Route::get('get_spin_progress.php', [LegacyApiController::class, 'getSpinProgress']);

Route::get('get_services.php', [LegacyApiController::class, 'getServices']);
Route::get('get_service.php', [LegacyApiController::class, 'getServiceById']);
Route::post('submit_online_service_order.php', [LegacyApiController::class, 'submitOnlineServiceOrder']);
Route::get('get_user_online_service_orders.php', [LegacyApiController::class, 'getUserOnlineServiceOrders']);

Route::post('update_course_progress.php', [LegacyApiController::class, 'updateCourseProgress']);
Route::post('claim_course_bonus.php', [LegacyApiController::class, 'claimCourseBonus']);
Route::post('update_course_completion.php', [LegacyApiController::class, 'updateCourseCompletion']);

Route::get('get_job_status.php', [LegacyApiController::class, 'getJobStatus']);
Route::get('get_job_text.php', [LegacyApiController::class, 'getJobText']);
Route::get('get_job_tutorial.php', [LegacyApiController::class, 'getJobTutorial']);

Route::post('send_email_otp.php', [LegacyApiController::class, 'sendEmailOtp']);
Route::post('reset_password.php', [LegacyApiController::class, 'resetPassword']);
Route::post('send_withdraw_otp.php', [LegacyApiController::class, 'sendWithdrawOtp']);

Route::post('recharge.php', [LegacyApiController::class, 'recharge']);
Route::post('recharge_success_handler.php', [LegacyApiController::class, 'rechargeSuccessHandler']);
Route::post('confirm_sim_offer.php', [LegacyApiController::class, 'confirmSimOffer']);
Route::get('get_sim_offer_history.php', [LegacyApiController::class, 'getUserOfferHistory']);
Route::post('submit_sim_offer_request.php', [LegacyApiController::class, 'submitSimOfferRequest']);
Route::post('convert_to_voucher.php', [LegacyApiController::class, 'convertToVoucher']);
Route::post('get_voucher_balance.php', [LegacyApiController::class, 'getVoucherBalance']);
Route::get('sim_offer_manage_api.php', [LegacyApiController::class, 'getSimOfferManage']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
