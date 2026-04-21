<?php

use App\Models\SignUp;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$userId = 2870; // Sample user ID we found
$user = SignUp::find($userId);

if (!$user) {
    echo "User not found\n";
    exit;
}

$referCode = $user->referCode;
echo "Refer Code: $referCode\n";

$lastBonus = DB::table('bonus_tracker')
    ->where('user_id', $userId)
    ->where('bonus_type', 'monthly_salary')
    ->latest('created_at')
    ->first();

$startDate = $lastBonus ? $lastBonus->created_at : '2000-01-01 00:00:00';
echo "Start Date: $startDate\n";

$l1Users = DB::table('sign_up')
    ->where('referredBy', $referCode)
    ->whereIn('is_verified', [1, 3])
    ->where('created_at', '>', $startDate)
    ->get(['id', 'referCode']);

$level1Verified = $l1Users->count();
echo "Level 1 Verified: $level1Verified\n";

$l1Codes = $l1Users->pluck('referCode')->filter()->toArray();
$level1Active = 0;
if (!empty($l1Codes)) {
    $level1Active = DB::table('sign_up')
        ->whereIn('referredBy', $l1Codes)
        ->whereIn('is_verified', [1, 3])
        ->where('created_at', '>', $startDate)
        ->distinct('referredBy')
        ->count('referredBy');
}
echo "Level 1 Active: $level1Active\n";

$level2Verified = 0;
if (!empty($l1Codes)) {
    $level2Verified = DB::table('sign_up')
        ->whereIn('referredBy', $l1Codes)
        ->whereIn('is_verified', [1, 3])
        ->where('created_at', '>', $startDate)
        ->count();
}
echo "Level 2 Verified: $level2Verified\n";

$totalOrders = DB::table('orders')
    ->where('user_id', $userId)
    ->where('created_at', '>', $startDate)
    ->count();
echo "Total Orders: $totalOrders\n";

$isEligible = ($level1Verified >= 30 && $level1Active >= 10 && $level2Verified >= 60 && $totalOrders >= 1);
echo "Is Eligible: " . ($isEligible ? 'Yes' : 'No') . "\n";

$lastRequest = \App\Models\SalaryRequest::where('user_id', $userId)->latest('requested_at')->first();
$status = $lastRequest ? $lastRequest->status : 'None';
echo "Status: $status\n";
