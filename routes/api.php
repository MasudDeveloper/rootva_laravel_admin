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
use App\Http\Controllers\Api\LegacyAuthController;
use App\Http\Controllers\Api\LegacySimOfferController;
use App\Http\Controllers\Api\LegacyUserController;
use App\Http\Controllers\Api\LegacyTeamController;
use App\Http\Controllers\Api\LegacyWalletController;
use App\Http\Controllers\Api\LegacyWithdrawController;
use App\Http\Controllers\Api\LegacyDepositController;
use App\Http\Controllers\Api\LegacyMicrojobController;
use App\Http\Controllers\Api\LegacyReviewJobController;
use App\Http\Controllers\Api\LegacyRechargeController;
use App\Http\Controllers\Api\LegacyCourseController;
use App\Http\Controllers\Api\LegacyOrderController;
use App\Http\Controllers\Api\LegacyServiceController;
use App\Http\Controllers\Api\LegacySalaryController;
use App\Http\Controllers\Api\LegacySpinController;
use App\Http\Controllers\Api\LegacyLeaderboardController;
use App\Http\Controllers\Api\LegacyJobController;
use App\Http\Controllers\Api\LegacyContentController;
use App\Http\Controllers\Api\LegacyNotificationController;
use App\Http\Controllers\Api\LegacyVerifyController;
use App\Http\Controllers\Api\LeaderboardController;

Route::get('get_leaderboard.php', [LeaderboardController::class, 'getRanking']);
Route::post('login.php', [LegacyAuthController::class, 'login']);
Route::post('register.php', [AuthController::class, 'register']);
Route::post('get_Data.php', [LegacyUserController::class, 'getUserData']);
Route::get('get_banners.php', [LegacyContentController::class, 'getBanners']);
Route::get('get_reviews.php', [LegacyContentController::class, 'getReviews']);
Route::get('get_social_links.php', [LegacyContentController::class, 'getSocialLinks']);
Route::get('get_latest_update.php', [LegacyUserController::class, 'getUpdate']);
Route::post('get_wallet_balance.php', [LegacyWalletController::class, 'getBalance']);
Route::get('get_transaction_history.php', [LegacyWalletController::class, 'getTransactionHistory']);
Route::get('get_income_report.php', [LegacyWalletController::class, 'getIncomeReport']);
Route::get('get_income_history.php', [LegacyWalletController::class, 'getIncomeHistory']);
Route::get('get_sim_offer.php', [LegacySimOfferController::class, 'getSimOffers']);
Route::get('get_categories.php', [LegacyContentController::class, 'getCategories']);
Route::get('get_products.php', [ModuleController::class, 'getProducts']);
Route::get('get_products_by_category.php', [ModuleController::class, 'getProducts']);
Route::get('get_referral_tree.php', [LegacyTeamController::class, 'getReferralTree']);
Route::get('get_team_summary.php', [LegacyTeamController::class, 'getTeamSummary']);
Route::get('get_referral_tree2.php', [LegacyTeamController::class, 'searchUserInMyTree']);
Route::post('get_upline_details.php', [LegacyTeamController::class, 'getUplineDetails']);
Route::post('update_profile.php', [LegacyUserController::class, 'updateProfile']);
Route::get('get_payment_numbers.php', [LegacyDepositController::class, 'getPaymentNumbers']);
Route::post('get_microjobs.php', [LegacyMicrojobController::class, 'getAllMicrojobs']);
Route::post('submit_microjob.php', [LegacyMicrojobController::class, 'submitMicrojob']);
Route::post('submit_proof.php', [LegacyMicrojobController::class, 'submitMicrojob']);
Route::post('add_money_request.php', [LegacyDepositController::class, 'addMoneyRequest']);
Route::get('get_money_requests.php', [LegacyDepositController::class, 'getMoneyRequests']);
Route::post('submit_money_withdraw_request.php', [LegacyWithdrawController::class, 'withdrawRequest']);
Route::get('get_withdraw_request.php', [LegacyWithdrawController::class, 'getWithdrawRequests']);
Route::post('submit_verification_request.php', [LegacyVerifyController::class, 'submitVerificationRequest']);
Route::post('recharge_request.php', [LegacyRechargeController::class, 'doRecharge']);
Route::get('get_recharge_history.php', [LegacyRechargeController::class, 'getRechargeHistory']);
Route::get('get_courses.php', [LegacyCourseController::class, 'getAllVideos']);
Route::get('get_course_progress.php', [LegacyCourseController::class, 'getCourseProgress']);
Route::post('salary_request.php', [LegacySalaryController::class, 'applySalaryRequest']);
Route::get('get_salary_request_status.php', [LegacySalaryController::class, 'getSalaryRequestStatus']);
Route::post('getRefer.php', [LegacyUserController::class, 'checkReferCode']);
Route::post('save_fcm_token.php', [LegacyUserController::class, 'saveFcmToken']);
Route::post('verify_password.php', [LegacyAuthController::class, 'verifyPassword']);
Route::post('get_spin_data.php', [LegacySpinController::class, 'getSpinData']);
Route::get('get_notifications.php', [LegacyNotificationController::class, 'getNotifications']);
Route::post('mark_notifications_read.php', [LegacyNotificationController::class, 'markNotificationsAsRead']);
Route::post('spin_wheel.php', [LegacySpinController::class, 'submitSpinResult']);
Route::get('check-password-update.php', [LegacyAuthController::class, 'checkPasswordUpdate']);
Route::post('upload_profile_pic.php', [LegacyUserController::class, 'uploadProfilePic']);
Route::post('submit_order_request.php', [LegacyOrderController::class, 'submitOrder']);
Route::get('get_user_orders.php', [LegacyOrderController::class, 'getUserOrders']);
Route::post('solve_math.php', [LegacyJobController::class, 'submitMathAnswer']);
Route::get('get_profile.php', [LegacyUserController::class, 'getProfile']);
Route::post('get_review_job.php', [LegacyReviewJobController::class, 'getAvailableReviewJobs']);
Route::post('get_lock_review_job.php', [LegacyReviewJobController::class, 'getLockReviewJobs']);
Route::get('get_review_job_social.php', [LegacyReviewJobController::class, 'getReviewJobSocial']);
Route::post('lock_review_job.php', [LegacyReviewJobController::class, 'lockReviewJob']);
Route::post('unlock_review_job.php', [LegacyReviewJobController::class, 'unlockReviewJob']);
Route::post('submit_review_job_proof.php', [LegacyReviewJobController::class, 'submitReviewJobProof']);
Route::post('mark_verification_popup_seen.php', [LegacyUserController::class, 'markVerificationPopupSeen']);

// Missing Endpoints added for functional parity
Route::get('get_daily_winners.php', [LeaderboardController::class, 'getDailyWinners']);
Route::get('get_weekly_winner.php', [LegacyLeaderboardController::class, 'getWeeklyWinner']);
Route::get('get_weekly_ranking.php', [LegacyLeaderboardController::class, 'getWeeklyRanking']);
Route::get('get_weekly_winners_by_date.php', [LegacyLeaderboardController::class, 'getWeeklyWinnersByDate']);

Route::get('get_popup.php', [LegacyContentController::class, 'getPopupData']);
Route::get('get_tutorials.php', [LegacyContentController::class, 'getTutorials']);
Route::post('get_math_income.php', [LegacyWalletController::class, 'getMathIncome']);
Route::get('get_salary_progress.php', [LegacySalaryController::class, 'getSalaryProgress']);
Route::get('get_spin_progress.php', [LegacySpinController::class, 'getSpinProgress']);

Route::get('get_services.php', [LegacyServiceController::class, 'getOnlineServices']);
Route::get('get_online_services.php', [LegacyServiceController::class, 'getOnlineServices']);
Route::get('get_service.php', [LegacyServiceController::class, 'getServiceById']);
Route::post('submit_online_service_order.php', [LegacyServiceController::class, 'submitOnlineServiceOrder']);
Route::get('get_user_online_service_orders.php', [LegacyServiceController::class, 'getUserOnlineServiceOrders']);

Route::post('update_course_progress.php', [LegacyCourseController::class, 'updateCourseProgress']);
Route::post('claim_course_bonus.php', [LegacyCourseController::class, 'claimCourseBonus']);
Route::post('update_course_completion.php', [LegacyCourseController::class, 'updateCourseCompletion']);

Route::get('get_job_status.php', [LegacyJobController::class, 'getJobStatus']);
Route::get('get_job_text.php', [LegacyJobController::class, 'getJobText']);
Route::get('get_job_tutorial.php', [LegacyJobController::class, 'getJobTutorial']);

Route::post('send_email_otp.php', [LegacyAuthController::class, 'sendEmailOtp']);
Route::post('reset_password.php', [LegacyAuthController::class, 'resetPassword']);
Route::post('send_withdraw_otp.php', [LegacyWithdrawController::class, 'sendWithdrawOtp']);

Route::post('recharge.php', [LegacyRechargeController::class, 'recharge']);
Route::post('recharge_success_handler.php', [LegacyRechargeController::class, 'rechargeSuccessHandler']);
Route::post('confirm_sim_offer.php', [LegacySimOfferController::class, 'confirmSimOffer']);
Route::get('get_sim_offer_history.php', [LegacySimOfferController::class, 'getUserOfferHistory']);
Route::post('submit_sim_offer_request.php', [LegacySimOfferController::class, 'submitSimOfferRequest']);
Route::post('convert_to_voucher.php', [LegacyWalletController::class, 'convertToVoucher']);
Route::post('get_voucher_balance.php', [LegacyWalletController::class, 'getVoucherBalance']);
Route::get('sim_offer_manage_api.php', [LegacySimOfferController::class, 'getSimOfferManage']);

Route::get('check_leadership_level.php', [LegacyLeaderboardController::class, 'checkLeadershipLevel']);
Route::get('check_leadership_level2.php', [LegacyLeaderboardController::class, 'checkLeadershipLevel']);
Route::get('check_spin_target_bonus.php', [LegacySpinController::class, 'checkSpinTargetBonus']);
Route::post('claim_spin_bonus.php', [LegacySpinController::class, 'claimSpinBonus']);
Route::post('payment_sms_hook.php', [LegacyVerifyController::class, 'handlePaymentSmsHook']);

Route::post('create_microjob.php', [LegacyMicrojobController::class, 'addMicrojob']);
Route::get('get_posted_jobs.php', [LegacyMicrojobController::class, 'getPostedJobs']);
Route::get('get_job_submissions.php', [LegacyMicrojobController::class, 'getJobSubmissions']);
Route::post('update_submission_status.php', [LegacyMicrojobController::class, 'updateSubmissionStatus']);
Route::post('update_microjob_status.php', [LegacyMicrojobController::class, 'updateMicrojobStatus']);
Route::get('get_users_microjobs_posts.php', [LegacyMicrojobController::class, 'getUserMicrojobsPosts']);
Route::get('get_daily_winners_by_date.php', [LegacyLeaderboardController::class, 'getWinnersByDate']);
Route::get('get_daily_live_ranking.php', [LegacyLeaderboardController::class, 'getTodayLiveRanking']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');