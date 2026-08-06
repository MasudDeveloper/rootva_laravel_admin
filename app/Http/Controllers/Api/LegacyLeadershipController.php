<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\Transaction;
use App\Models\VerificationRequest;
use App\Models\LeadershipRewardRequest;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class LegacyLeadershipController extends Controller
{
    /**
     * Legacy Leadership Level (check_leadership_level.php)
     */
    public function checkLeadershipLevel(Request $request)
    {
        $user_id = $request->query('user_id');
        if (!$user_id) return response()->json(["status" => "error", "message" => "User ID missing."]);

        $user = SignUp::find($user_id);
        if (!$user) return response()->json(["status" => "error", "message" => "User not found."]);

        // Step 1: Get user's referCode
        $referCode = $user->referCode;

        // Step 2: Get Level 1 referred users (minimal data)
        $level1 = SignUp::where('referredBy', $referCode)->select('id', 'referCode')->get();
        $l1Codes = $level1->pluck('referCode')->filter()->toArray();
        $l1Ids = $level1->pluck('id')->toArray();

        // Step 3: Get verified counts for referrals of L1 (L2 verified counts) in bulk (Distinct users)
        $verifiedCountsL2 = collect();
        if (!empty($l1Codes)) {
            foreach (array_chunk($l1Codes, 1000) as $chunk) {
                $counts = VerificationRequest::join('sign_up', 'verification_requests.user_id', '=', 'sign_up.id')
                    ->where('verification_requests.status', 'Approved')
                    ->whereIn('sign_up.referredBy', $chunk)
                    ->groupBy('sign_up.referredBy')
                    ->select('sign_up.referredBy', DB::raw('COUNT(DISTINCT verification_requests.user_id) as count'))
                    ->pluck('count', 'referredBy');
                $verifiedCountsL2 = $verifiedCountsL2->merge($counts);
            }
        }

        // Step 4: Get verified count of L1 members themselves (Distinct users)
        $verifiedCount = 0;
        if (!empty($l1Ids)) {
            foreach (array_chunk($l1Ids, 1000) as $chunk) {
                $count = VerificationRequest::whereIn('user_id', $chunk)
                    ->where('status', 'Approved')
                    ->distinct('user_id')
                    ->count('user_id');
                $verifiedCount += $count;
            }
        }
        
        // Count leaders (L1 members with 15+ verified referrals)
        $silverCandidateCodes = $verifiedCountsL2->filter(fn($count) => $count >= 15)->keys()->toArray();
        $leaderCount = count($silverCandidateCodes);

        // Order count check for the current user
        $orderCount = Order::where('user_id', $user_id)
            ->whereIn('order_status', ['Confirmed', 'Delivered'])
            ->count();

        // Step 5: Evaluate Gold/Diamond/Top Candidates (Fully optimized)
        $goldCount = 0;
        $diamondCount = 0;
        $topCount = 0;

        if ($leaderCount > 0) {
            // Fetch all Level 2 members under all silver candidates
            $allL2s = collect();
            if (!empty($silverCandidateCodes)) {
                foreach (array_chunk($silverCandidateCodes, 1000) as $chunk) {
                    $l2s = DB::table('sign_up')
                        ->whereIn('referredBy', $chunk)
                        ->whereExists(function ($query) {
                            $query->select(DB::raw(1))
                                  ->from('sign_up as child')
                                  ->join('verification_requests as v', 'v.user_id', '=', 'child.id')
                                  ->whereColumn('child.referredBy', 'sign_up.referCode')
                                  ->where('v.status', 'Approved');
                        })
                        ->select('referCode', 'referredBy')
                        ->get();
                    
                    foreach ($l2s as $l2) {
                        $allL2s->push($l2);
                    }
                }
            }
            $l2Codes = $allL2s->pluck('referCode')->filter()->toArray();

            // Verified count per L2 member's referrals (L3 verified counts) in bulk (Distinct users)
            $verifiedCountsL3 = collect();
            if (!empty($l2Codes)) {
                foreach (array_chunk($l2Codes, 1000) as $chunk) {
                    $counts = VerificationRequest::join('sign_up', 'verification_requests.user_id', '=', 'sign_up.id')
                        ->where('verification_requests.status', 'Approved')
                        ->whereIn('sign_up.referredBy', $chunk)
                        ->groupBy('sign_up.referredBy')
                        ->select('sign_up.referredBy', DB::raw('COUNT(DISTINCT verification_requests.user_id) as count'))
                        ->pluck('count', 'referredBy');
                    $verifiedCountsL3 = $verifiedCountsL3->merge($counts);
                }
            }

            // Identify sub-leaders (L2s with 15+ verified L3 referrals)
            $subLeaderReferrers = $allL2s->filter(function($l2) use ($verifiedCountsL3) {
                return ($verifiedCountsL3[$l2->referCode] ?? 0) >= 15;
            })->pluck('referredBy'); // These are the silver candidate codes

            // Count how many sub-leaders each silver candidate has
            $subLeaderCountsPerCandidate = $subLeaderReferrers->countBy();

            // Candidates with 10+ sub-leaders qualify for higher ranks
            $qualifiedCandidatesCount = $subLeaderCountsPerCandidate->filter(fn($count) => $count >= 10)->count();
            
            $goldCount = $qualifiedCandidatesCount;
            $diamondCount = ($orderCount >= 3) ? $qualifiedCandidatesCount : 0;
            $topCount = ($orderCount >= 10) ? $qualifiedCandidatesCount : 0;
        }

        // Milestones for payouts
        $leaderTimesOverall = floor($verifiedCount / 15);
        $silverTimes = floor($leaderCount / 10);
        $goldTimes = floor($goldCount / 10);
        $diamondTimes = floor($diamondCount / 10);
        $topTimes = floor($topCount / 10);

        // Determine final reward string (Formatted for Android parsing)
        $reward = "None";
        if ($verifiedCount >= 15) $reward = "Rootva Leader " . $leaderTimesOverall;
        if ($leaderCount >= 10 && $orderCount >= 1) $reward = "Silver " . $silverTimes;
        if ($goldCount >= 10 && $orderCount >= 3) $reward = "Gold " . $goldTimes;
        if ($diamondCount >= 10 && $orderCount >= 5) $reward = "Diamond " . $diamondTimes;
        if ($topCount >= 10 && $orderCount >= 10) $reward = "Top " . $topTimes;

        // --- Bonus Payout Logic ---
        if ($leaderTimesOverall >= 1) {
            $approvedLeaderRewards = Transaction::where('user_id', $user_id)
                ->where('payment_gateway', 'Leadership Bonus')
                ->where(function($q) {
                    $q->where('description', 'like', '%Leader%')
                      ->orWhere('description', 'like', '%Leadership%');
                })
                ->count();
            
            if ($leaderTimesOverall > $approvedLeaderRewards) {
                $newTimes = (int)($leaderTimesOverall - $approvedLeaderRewards);
                for ($i = 0; $i < $newTimes; $i++) {
                    $amount = 80;
                    $user->increment('wallet_balance', $amount);
                    Transaction::create([
                        'user_id' => $user_id,
                        'refer_id' => $user->referCode,
                        'amount' => $amount,
                        'type' => 'income',
                        'payment_gateway' => 'Leadership Bonus',
                        'description' => 'Rootva Leader reward',
                        'update_at' => date('d-m-Y h:i A'),
                        'created_at' => date('d-m-Y h:i A'),
                        'date' => now()
                    ]);
                    
                    LeadershipRewardRequest::create([
                        'user_id' => $user_id,
                        'reward_type' => 'Rootva Leader',
                        'times' => 1,
                        'amount' => $amount,
                        'status' => 'Approved'
                    ]);
                }
            }
        }

        $leaderships = [
            "Silver" => ["times" => $silverTimes, "amount" => 500],
            "Gold" => ["times" => $goldTimes, "amount" => 1000],
            "Diamond" => ["times" => $diamondTimes, "amount" => 2000],
            "Top" => ["times" => $topTimes, "amount" => 4000]
        ];

        foreach ($leaderships as $type => $data) {
            $times = $data['times'];
            if ($times <= 0) continue;

            if (LeadershipRewardRequest::where('user_id', $user_id)
                ->where('reward_type', $type)
                ->where('status', 'Pending')
                ->exists()) continue;
            
            $approvedTimes = LeadershipRewardRequest::where('user_id', $user_id)
                ->where('reward_type', $type)
                ->where('status', 'Approved')
                ->sum('times');

            if ($times > $approvedTimes) {
                $newTimes = (int)($times - $approvedTimes);
                LeadershipRewardRequest::create([
                    'user_id' => $user_id,
                    'reward_type' => $type,
                    'times' => $newTimes,
                    'amount' => $data['amount'] * $newTimes,
                    'status' => 'Pending'
                ]);
            }
        }

        return response()->json([
            "status" => "success",
            "user_id" => (int)$user_id,
            "reward" => $reward,
            "order_count" => $orderCount,
            "level1_summary" => [
                "total_referred" => count($level1),
                "verified" => $verifiedCount,
                "leaders" => $leaderCount,
                "remaining_to_leader" => max(0, 15 - $verifiedCount),
                "times" => (int)$leaderTimesOverall,
                "reward" => $leaderTimesOverall >= 1 ? "Rootva Leader $leaderTimesOverall" : null
            ],
            "silver_summary" => [
                "total_candidates" => $leaderCount,
                "times" => (int)$silverTimes,
                "total_orders" => $orderCount,
                "reward" => $silverTimes >= 1 ? "Silver $silverTimes" : null
            ],
            "gold_summary" => [
                "total_candidates" => $goldCount,
                "times" => (int)$goldTimes,
                "total_orders" => $orderCount,
                "reward" => $goldTimes >= 1 ? "Gold $goldTimes" : null
            ],
            "diamond_summary" => [
                "total_candidates" => $diamondCount,
                "times" => (int)$diamondTimes,
                "total_orders" => $orderCount,
                "reward" => $diamondTimes >= 1 ? "Diamond $diamondTimes" : null
            ],
            "top_summary" => [
                "total_candidates" => $topCount,
                "times" => (int)$topTimes,
                "total_orders" => $orderCount,
                "reward" => $topTimes >= 1 ? "Top $topTimes" : null
            ],
            "milestones" => [
                "Leader" => (int)$leaderTimesOverall,
                "Silver" => (int)$silverTimes,
                "Gold" => (int)$goldTimes,
                "Diamond" => (int)$diamondTimes,
                "Top" => (int)$topTimes
            ]
        ]);
    }

}
