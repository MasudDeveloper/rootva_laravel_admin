<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LegacyTeamController extends Controller
{
    /**
     * Legacy User Tree (get_referral_tree.php)
     */
    public function getReferralTree(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        
        $referCode = $request->input('referCode');
        
        // If referCode is empty, find it using the logged-in user ID
        if (empty($referCode)) {
            $authUserId = $request->header('Auth-User-Id') ?? $request->input('user_id');
            if ($authUserId) {
                $authUser = SignUp::find($authUserId);
                if ($authUser) {
                    $referCode = $authUser->referCode;
                }
            }
        }
        
        $isUpdated = $request->input('isUpdated') === 'true';
        $limit = (int)$request->input('limit', 20);
        $offset = (int)$request->input('offset', 0);
        $targetLevel = $request->input('level'); // Optional level filter
        $statusFilter = $request->input('status'); // Optional status filter: 'verified', 'unverified'
        
        $startTime = microtime(true);
        
        $query = "
            WITH RECURSIVE referral_tree AS (
                SELECT id, referCode, referredBy, is_verified, 1 as level
                FROM sign_up
                WHERE referredBy = ?
                
                UNION ALL
                
                SELECT s.id, s.referCode, s.referredBy, s.is_verified, rt.level + 1
                FROM sign_up s
                INNER JOIN referral_tree rt ON s.referredBy = rt.referCode
                WHERE rt.level < 10
            )
            SELECT id, level, is_verified FROM referral_tree
        ";
        
        $results = DB::select($query, [$referCode]);
        
        $treeNodes = [];
        foreach ($results as $row) {
            if (!$targetLevel || $row->level == $targetLevel) {
                $treeNodes[] = [
                    'id' => (int)$row->id,
                    'level' => (int)$row->level,
                    'is_verified' => (int)$row->is_verified
                ];
            }
        }

        // 1. Calculate TOTAL counts for the whole tree/level BEFORE status filtering
        $allNodes = collect($treeNodes);
        
        // Apply level filter to nodes before counting if targetLevel is set
        if ($targetLevel) {
            $allNodes = $allNodes->where('level', (int)$targetLevel);
        }
        
        $total = $allNodes->count();
        $verifiedTotal = $allNodes->filter(function($u) {
            return in_array($u['is_verified'], [1, 3]);
        })->count();
        $unverifiedTotal = $total - $verifiedTotal;

        // 2. Now apply status filtering for the paginated result
        if ($statusFilter) {
            $pageNodes = $allNodes->filter(function($u) use ($statusFilter) {
                $isV = in_array($u['is_verified'], [1, 3]);
                if ($statusFilter === 'verified') return $isV;
                if ($statusFilter === 'unverified') return !$isV;
                return true;
            });
        } else {
            $pageNodes = $allNodes;
        }
        
        if ($isUpdated) {
            // 1. Sort IDs DESC (matches old PHP behavior) and Slice
            $hasMore = $pageNodes->count() > ($offset + $limit);
            $pageNodes = $pageNodes->sortByDesc('id')->values()->slice($offset, $limit);
            
            // 2. Fetch full objects only for the current page
            $pageIds = $pageNodes->pluck('id')->toArray();
            $users = SignUp::whereIn('id', $pageIds)
                ->orderBy('id', 'desc')
                ->get(['id', 'name', 'number', 'referCode', 'is_verified', 'profile_pic_url', 'created_at', 'verified_at', 'referredBy']);
                
            // 3. Re-attach Level and UserID fields
            $levelMap = $pageNodes->pluck('level', 'id')->toArray();
            $users = $users->map(function($user) use ($levelMap) {
                $user->level = $levelMap[$user->id] ?? 0;
                $user->user_id = $user->id;
                $user->is_verified = (int)$user->is_verified; // Explicit cast
                return $user;
            });

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "ডেটা সফলভাবে লোড হয়েছে",
                'data' => [[
                    'level' => (int)($targetLevel ?? 0),
                    'users' => $users->values()
                ]],
                'total' => $total,
                'verified_total' => $verifiedTotal,
                'unverified_total' => $unverifiedTotal,
                'hasMore' => $hasMore,
                'load_time' => round(microtime(true) - $startTime, 4) . " sec"
            ]);
        } else {
            // Legacy Non-Paginated Tree (Grouped by Level)
            $allIds = $allNodes->pluck('id')->toArray();
            $users = SignUp::whereIn('id', $allIds)
                ->orderBy('id', 'desc')
                ->limit(500) // Hard limit for non-paginated to prevent crashes
                ->get(['id', 'name', 'number', 'referCode', 'is_verified', 'profile_pic_url', 'created_at', 'referredBy']);
                
            $levelMap = $allNodes->pluck('level', 'id')->toArray();
            foreach ($users as $user) {
                $user->level = $levelMap[$user->id] ?? 0;
                $user->user_id = $user->id;
            }
            
            $levels = [];
            $grouped = $users->groupBy('level');
            foreach ($grouped as $lvl => $group) {
                $levels[] = [
                    'level' => (int)$lvl,
                    'users' => $group->sortByDesc('id')->values()
                ];
            }
            usort($levels, fn($a, $b) => $a['level'] <=> $b['level']);

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "ডেটা সফলভাবে লোড হয়েছে",
                'data' => $levels,
                'load_time' => round(microtime(true) - $startTime, 4) . " sec"
            ]);
        }
    }

    /**
     * Get Team Summary (Counts per Level)
     */
    public function getTeamSummary(Request $request)
    {
        $referCode = $request->input('referCode');
        $startTime = microtime(true);
        
        $query = "
            WITH RECURSIVE referral_tree AS (
                SELECT id, referCode, referredBy, is_verified, 1 as level
                FROM sign_up
                WHERE referredBy = ?
                
                UNION ALL
                
                SELECT s.id, s.referCode, s.referredBy, s.is_verified, rt.level + 1
                FROM sign_up s
                INNER JOIN referral_tree rt ON s.referredBy = rt.referCode
                WHERE rt.level < 10
            )
            SELECT 
                level,
                COUNT(id) as total,
                SUM(CASE WHEN is_verified IN (1, 3) THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN is_verified NOT IN (1, 3) THEN 1 ELSE 0 END) as unverified
            FROM referral_tree
            GROUP BY level
            ORDER BY level ASC
        ";

        $results = DB::select($query, [$referCode]);
        
        $summary = [];
        foreach ($results as $row) {
            $summary[] = [
                'level' => (int)$row->level,
                'total' => (int)$row->total,
                'verified' => (int)$row->verified,
                'unverified' => (int)$row->unverified
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $summary,
            'load_time' => round(microtime(true) - $startTime, 4) . " sec"
        ]);
    }

    /**
     * Legacy Search User in Tree (get_referral_tree2.php)
     */
    public function searchUserInMyTree(Request $request)
    {
        $myReferCode = $request->query('referCode');
        $searchCode = $request->query('searchReferCode');

        if (!$myReferCode || !$searchCode) {
            return response()->json(['status' => 'error', 'message' => 'অবৈধ ডেটা']);
        }

        // 1. Check if the searched user exists globally first
        $targetUser = SignUp::where('referCode', $searchCode)->first();
        if (!$targetUser) {
            return response()->json(['status' => 'error', 'message' => 'ইউজার পাওয়া যায়নি']);
        }

        // 2. Perform a Breadth-First search up to 7 levels to verify ownership
        $currentLevelCodes = [$myReferCode];
        $foundInTree = false;
        $foundLevel = 0;

        for ($level = 1; $level <= 7; $level++) {
            $usersInLevel = DB::table('sign_up')
                ->whereIn('referredBy', $currentLevelCodes)
                ->get(['id', 'referCode']);

            if ($usersInLevel->isEmpty()) break;

            foreach ($usersInLevel as $user) {
                if ($user->referCode === $searchCode) {
                    $foundInTree = true;
                    $foundLevel = $level;
                    break 2; // Exit both loop and foreach
                }
            }

            $currentLevelCodes = $usersInLevel->pluck('referCode')->toArray();
        }

        if ($foundInTree) {
            $targetUser->user_id = $targetUser->id;
            $targetUser->level = $foundLevel;
            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "ইউজার পাওয়া গেছে",
                'referUsers' => [$targetUser]
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => '❌ এই রেফার কোডটি আপনার ট্রিতে খুঁজে পাওয়া যায়নি'
        ]);
    }

    /**
     * Legacy Upline Details (get_upline_details.php)
     */
    public function getUplineDetails(Request $request)
    {
        $referCode = $request->input('referCode');
        $user = SignUp::where('referCode', $referCode)->first();
        
        if ($user && $user->referredBy) {
            $upline = SignUp::where('referCode', $user->referredBy)->first();
            if ($upline) {
                return response()->json([
                    'status' => 'success',
                    'success' => true,
                    'message' => "UpLine info found",
                    'user' => $upline // Expected by ReferralResponse.java
                ]);
            }
        }
        
        return response()->json([
            'status' => 'error',
            'success' => false,
            'message' => "No UpLine found"
        ]);
    }
}
