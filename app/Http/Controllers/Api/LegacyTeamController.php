<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use Illuminate\Support\Facades\DB;

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
        $isUpdated = $request->input('isUpdated') === 'true';
        $limit = (int)$request->input('limit', 20);
        $offset = (int)$request->input('offset', 0);
        $targetLevel = $request->input('level'); // Optional level filter
        
        $startTime = microtime(true);
        
        // Use a lean array to store [id, level]
        $treeNodes = [];
        
        // Level 1: Fetch only necessary columns to save memory
        $level1 = DB::table('sign_up')
            ->where('referredBy', $referCode)
            ->get(['id', 'referCode']);
            
        $currentLevelCodes = [];
        foreach ($level1 as $user) {
            if (!$targetLevel || $targetLevel == 1) {
                $treeNodes[] = ['id' => $user->id, 'level' => 1];
            }
            $currentLevelCodes[] = $user->referCode;
        }
        
        // Levels 2-10: Iterative Breadth-First Scan
        for ($i = 2; $i <= 10; $i++) {
            if (empty($currentLevelCodes)) break;
            
            $nextLevel = DB::table('sign_up')
                ->whereIn('referredBy', $currentLevelCodes)
                ->get(['id', 'referCode']);
                
            if ($nextLevel->isEmpty()) break;
            
            $currentLevelCodes = [];
            foreach ($nextLevel as $user) {
                if (!$targetLevel || $targetLevel == $i) {
                    $treeNodes[] = ['id' => $user->id, 'level' => $i];
                }
                $currentLevelCodes[] = $user->referCode;
            }
            
            // If we only wanted a specific level and we just finished it, we can stop scanning further
            if ($targetLevel && $i >= $targetLevel) break;
        }
        
        // Collect all IDs for processing
        $allNodes = collect($treeNodes);
        $total = $allNodes->count();
        
        if ($isUpdated) {
            // 1. Sort IDs DESC (matches old PHP behavior) and Slice
            $pageNodes = $allNodes->sortByDesc('id')->values()->slice($offset, $limit);
            $hasMore = ($offset + $limit) < $total;
            
            // 2. Fetch full objects only for the current page
            $pageIds = $pageNodes->pluck('id')->toArray();
            $users = SignUp::whereIn('id', $pageIds)
                ->orderBy('id', 'desc')
                ->get();
                
            // 3. Re-attach Level and UserID fields
            $levelMap = $pageNodes->pluck('level', 'id')->toArray();
            foreach ($users as $user) {
                $user->level = $levelMap[$user->id] ?? 0;
                $user->user_id = $user->id;
            }

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "ডেটা সফলভাবে লোড হয়েছে",
                'data' => [['users' => $users->values()]], // data[0].users structure
                'total' => $total,
                'hasMore' => $hasMore,
                'load_time' => round(microtime(true) - $startTime, 4) . " sec"
            ]);
        } else {
            // Legacy Non-Paginated Tree (Grouped by Level)
            $allIds = $allNodes->pluck('id')->toArray();
            $users = SignUp::whereIn('id', $allIds)
                ->orderBy('id', 'desc')
                ->get();
                
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
        
        $summary = [];
        $currentLevelCodes = [$referCode];
        
        for ($i = 1; $i <= 10; $i++) {
            if (empty($currentLevelCodes)) break;
            
            $users = DB::table('sign_up')
                ->whereIn('referredBy', $currentLevelCodes)
                ->select('id', 'referCode', 'is_verified')
                ->get();
                
            if ($users->isEmpty()) break;
            
            $totalCount = $users->count();
            $verifiedCount = $users->where('is_verified', 1)->count() + $users->where('is_verified', 3)->count();
            $unverifiedCount = $totalCount - $verifiedCount;
            
            $summary[] = [
                'level' => $i,
                'total' => $totalCount,
                'verified' => $verifiedCount,
                'unverified' => $unverifiedCount
            ];
            
            $currentLevelCodes = $users->pluck('referCode')->filter()->toArray();
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
        
        $user = SignUp::where('referCode', $searchCode)->first();
        
        if ($user) {
            $user->user_id = $user->id;
            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "ইউজার পাওয়া গেছে",
                'referUsers' => [$user]
            ]);
        }
        
        return response()->json(['status' => 'error', 'message' => 'ইউজার পাওয়া যায়নি']);
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
