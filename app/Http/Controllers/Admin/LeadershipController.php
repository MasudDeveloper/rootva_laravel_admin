<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadershipRewardRequest;
use App\Models\Transaction;
use App\Models\SignUp;
use App\Models\VerificationRequest;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

use Illuminate\Pagination\LengthAwarePaginator;

class LeadershipController extends Controller
{
    /**
     * Display a list of leaders with their achieved rewards count.
     */
    public function leaders(Request $request)
    {
        $search = $request->input('search');
        $filter = $request->input('filter', 'rootva'); // rootva, silver, gold, diamond, top

        // Dynamically find refer codes of users who have at least 15 verified L1 members
        $leaderReferCodes = DB::table('sign_up')
            ->join('verification_requests', 'verification_requests.user_id', '=', 'sign_up.id')
            ->where('verification_requests.status', 'Approved')
            ->groupBy('sign_up.referredBy')
            ->havingRaw('COUNT(DISTINCT verification_requests.user_id) >= 15')
            ->pluck('sign_up.referredBy')
            ->toArray();
        
        $silverCandidateCodes = [];
        foreach (array_chunk($leaderReferCodes, 1000) as $chunk) {
            $codes = DB::table('sign_up')
                ->whereIn('referCode', $chunk)
                ->groupBy('referredBy')
                ->havingRaw('COUNT(id) >= 10')
                ->pluck('referredBy')
                ->toArray();
            $silverCandidateCodes = array_merge($silverCandidateCodes, $codes);
        }

        $allLeaderCodes = array_unique(array_merge($leaderReferCodes, $silverCandidateCodes));

        // Fetch ALL leaders first to compute their stats for accurate sorting across all pages
        $allLeaders = collect();
        $searchQuery = function($q) use ($search) {
            return $q->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('number', 'like', "%{$search}%")
                      ->orWhere('referCode', 'like', "%{$search}%");
            });
        };

        foreach (array_chunk($allLeaderCodes, 1000) as $chunk) {
            $chunkLeaders = SignUp::whereIn('referCode', $chunk)
                ->when($search, $searchQuery)
                ->get();
            // Use push/all to avoid reindexing issues on models, or just push models individually
            foreach ($chunkLeaders as $leader) {
                $allLeaders->push($leader);
            }
        }

        // --- BULK CALCULATION TO PREVENT 500 ERROR (N+1 PROBLEM) & MySQL IN() LIMITS ---
        $leaderIds = $allLeaders->pluck('id')->toArray();
        $leaderCodesArr = $allLeaders->pluck('referCode')->filter()->toArray();

        // 1. Order counts for all leaders
        $orderCounts = [];
        foreach (array_chunk($leaderIds, 1000) as $chunk) {
            $counts = DB::table('orders')
                ->whereIn('user_id', $chunk)
                ->whereIn('order_status', ['Confirmed', 'Delivered'])
                ->groupBy('user_id')
                ->select('user_id', DB::raw('COUNT(id) as count'))
                ->pluck('count', 'user_id')
                ->toArray();
            // Use += to preserve integer keys (user_id)
            $orderCounts += $counts;
        }

        // 2. Level 1 Verified Counts (for Rootva Leader)
        $l1VerifiedCounts = [];
        foreach (array_chunk($leaderCodesArr, 1000) as $chunk) {
            $counts = DB::table('sign_up as u1')
                ->join('verification_requests as v', 'v.user_id', '=', 'u1.id')
                ->whereIn('u1.referredBy', $chunk)
                ->where('v.status', 'Approved')
                ->groupBy('u1.referredBy')
                ->select('u1.referredBy', DB::raw('COUNT(DISTINCT v.user_id) as count'))
                ->pluck('count', 'referredBy')
                ->toArray();
            $l1VerifiedCounts += $counts;
        }

        // 3. Get all L1 members for our leaders (only those who have at least 1 verified L2)
        $allL1s = collect();
        foreach (array_chunk($leaderCodesArr, 1000) as $chunk) {
            $l1s = DB::table('sign_up')
                ->whereIn('referredBy', $chunk)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('sign_up as child')
                          ->join('verification_requests as v', 'v.user_id', '=', 'child.id')
                          ->whereColumn('child.referredBy', 'sign_up.referCode')
                          ->where('v.status', 'Approved');
                })
                ->select('id', 'referCode', 'referredBy')
                ->get();
            foreach ($l1s as $l1) {
                $allL1s->push($l1);
            }
        }
        $allL1Codes = $allL1s->pluck('referCode')->filter()->toArray();

        // 4. L2 Verified Counts (for Silver Leader check on L1s)
        $l2VerifiedCounts = [];
        if (!empty($allL1Codes)) {
            foreach (array_chunk($allL1Codes, 1000) as $chunk) {
                $counts = DB::table('sign_up as u2')
                    ->join('verification_requests as v', 'v.user_id', '=', 'u2.id')
                    ->whereIn('u2.referredBy', $chunk)
                    ->where('v.status', 'Approved')
                    ->groupBy('u2.referredBy')
                    ->select('u2.referredBy', DB::raw('COUNT(DISTINCT v.user_id) as count'))
                    ->pluck('count', 'referredBy')
                    ->toArray();
                $l2VerifiedCounts += $counts;
            }
        }

        $l1IsLeader = [];
        foreach ($allL1s as $l1) {
            $count = $l2VerifiedCounts[$l1->referCode] ?? 0;
            if ($count >= 15) {
                $l1IsLeader[$l1->referCode] = true;
            }
        }

        $leaderSilverCounts = [];
        foreach ($allL1s as $l1) {
            if (isset($l1IsLeader[$l1->referCode])) {
                $leaderSilverCounts[$l1->referredBy] = ($leaderSilverCounts[$l1->referredBy] ?? 0) + 1;
            }
        }

        // 5. Get all L2 members for the L1s that are leaders (only those who have at least 1 verified L3)
        $l1LeaderCodesOnly = array_keys($l1IsLeader);
        $allL2s = collect();
        if (!empty($l1LeaderCodesOnly)) {
            foreach (array_chunk($l1LeaderCodesOnly, 1000) as $chunk) {
                $l2s = DB::table('sign_up')
                    ->whereIn('referredBy', $chunk)
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                              ->from('sign_up as child')
                              ->join('verification_requests as v', 'v.user_id', '=', 'child.id')
                              ->whereColumn('child.referredBy', 'sign_up.referCode')
                              ->where('v.status', 'Approved');
                    })
                    ->select('id', 'referCode', 'referredBy')
                    ->get();
                foreach ($l2s as $l2) {
                    $allL2s->push($l2);
                }
            }
        }
        $allL2Codes = $allL2s->pluck('referCode')->filter()->toArray();

        // 6. L3 Verified Counts
        $l3VerifiedCounts = [];
        if (!empty($allL2Codes)) {
            foreach (array_chunk($allL2Codes, 1000) as $chunk) {
                $counts = DB::table('sign_up as u3')
                    ->join('verification_requests as v', 'v.user_id', '=', 'u3.id')
                    ->whereIn('u3.referredBy', $chunk)
                    ->where('v.status', 'Approved')
                    ->groupBy('u3.referredBy')
                    ->select('u3.referredBy', DB::raw('COUNT(DISTINCT v.user_id) as count'))
                    ->pluck('count', 'referredBy')
                    ->toArray();
                $l3VerifiedCounts += $counts;
            }
        }

        $l2IsLeader = []; // maps L1 referCode -> count of sub-leaders
        foreach ($allL2s as $l2) {
            $count = $l3VerifiedCounts[$l2->referCode] ?? 0;
            if ($count >= 15) {
                $l1Referrer = $l2->referredBy;
                $l2IsLeader[$l1Referrer] = ($l2IsLeader[$l1Referrer] ?? 0) + 1;
            }
        }

        $l1QualifiesGold = [];
        foreach ($l2IsLeader as $l1Code => $subLeaderCount) {
            if ($subLeaderCount >= 10) {
                $l1QualifiesGold[$l1Code] = true;
            }
        }

        $leaderGoldCounts = [];
        foreach ($allL1s as $l1) {
            if (isset($l1QualifiesGold[$l1->referCode])) {
                $leaderGoldCounts[$l1->referredBy] = ($leaderGoldCounts[$l1->referredBy] ?? 0) + 1;
            }
        }

        // Assign calculated values
        foreach ($allLeaders as $user) {
            $referCode = $user->referCode;
            
            $verifiedCount = $l1VerifiedCounts[$referCode] ?? 0;
            $silverCount = $leaderSilverCounts[$referCode] ?? 0;
            $goldCount = $leaderGoldCounts[$referCode] ?? 0;
            $userOrderCount = $orderCounts[$user->id] ?? 0;

            $user->normal_leadership = floor($verifiedCount / 15);
            $user->silver_leadership = floor($silverCount / 10);
            
            $goldLeadership = floor($goldCount / 10);
            $user->gold_leadership = $goldLeadership;
            
            $user->diamond_leadership = ($userOrderCount >= 3) ? $goldLeadership : 0;
            $user->top_leadership = ($userOrderCount >= 10) ? $goldLeadership : 0;
        }

        // Sort the collection based on the selected filter
        if ($filter === 'silver') {
            $allLeaders = $allLeaders->sortByDesc('silver_leadership');
        } elseif ($filter === 'gold') {
            $allLeaders = $allLeaders->sortByDesc('gold_leadership');
        } elseif ($filter === 'diamond') {
            $allLeaders = $allLeaders->sortByDesc('diamond_leadership');
        } elseif ($filter === 'top') {
            $allLeaders = $allLeaders->sortByDesc('top_leadership');
        } else {
            $allLeaders = $allLeaders->sortByDesc('normal_leadership');
        }

        // Manually paginate the sorted collection
        $perPage = 25;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;
        
        $leaders = new LengthAwarePaginator(
            $allLeaders->slice($offset, $perPage)->values(), // Ensure it's re-indexed
            $allLeaders->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.leadership.leaders', compact('leaders', 'search', 'filter'));
    }
    /**
     * Display a list of disbursed leadership bonuses (History).
     */
    public function history(Request $request)
    {
        $rewardFilter = $request->input('reward');

        $winners = Transaction::select('transactions.*', 'sign_up.name', 'sign_up.number', 'sign_up.referCode')
            ->join('sign_up', 'transactions.user_id', '=', 'sign_up.id')
            ->where('transactions.payment_gateway', 'Leadership Bonus')
            ->when($rewardFilter, function($q) use ($rewardFilter) {
                return $q->where('transactions.description', 'like', "%{$rewardFilter}%");
            })
            ->orderBy('transactions.id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.leadership.history', compact('winners', 'rewardFilter'));
    }

    /**
     * Display pending reward claims.
     */
    public function requests()
    {
        $requests = LeadershipRewardRequest::with('user')
            ->where('status', 'Pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.leadership.requests', compact('requests'));
    }

    /**
     * Approve or Reject a leadership reward claim.
     */
    public function processRequest(Request $request, $id)
    {
        $claim = LeadershipRewardRequest::findOrFail($id);
        $action = $request->input('action'); // 'Approved' or 'Rejected'

        if ($claim->status !== 'Pending') {
            return back()->with('error', 'This claim has already been processed.');
        }

        DB::transaction(function () use ($claim, $action) {
            if ($action === 'Approved') {
                // Update User Balance
                $user = SignUp::findOrFail($claim->user_id);
                $user->increment('wallet_balance', $claim->amount);

                // Create Transaction record
                Transaction::create([
                    'user_id' => $user->id,
                    'refer_id' => $user->referCode,
                    'amount' => $claim->amount,
                    'type' => 'income',
                    'payment_gateway' => 'Leadership Bonus',
                    'description' => $claim->reward_type . " Reward",
                    'update_at' => now()->toDateTimeString(),
                    'created_at' => now()->toDateTimeString(),
                    'date' => now()
                ]);
            }

            // Update Claim Status
            $claim->update([
                'status' => $action,
                'updated_at' => now()->toDateTimeString(),
            ]);

            // Create notification
            $message = $action === 'Approved' ? "অভিনন্দন! আপনার ৳{$claim->amount} এর {$claim->reward_type} লিডারশিপ বোনাসটি অ্যাপ্রুভ হয়েছে।" : "দুঃখিত, আপনার {$claim->reward_type} লিডারশিপ ক্লেইমটি রিজেক্ট করা হয়েছে।";
            \App\Models\Notification::create([
                'user_id' => $claim->user_id,
                'message' => $message,
                'is_read' => 0,
                'created_at' => now()->format('d-m-Y h:i A')
            ]);
        });

        return back()->with('success', "Claim has been {$action} successfully.");
    }
}
