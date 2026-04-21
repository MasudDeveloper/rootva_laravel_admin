<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SignUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaderboardController extends Controller
{
    /**
     * Get Leaderboard Ranking
     * filters: all_time (default), today, weekly, monthly
     */
    public function getRanking(Request $request)
    {
        $filter = $request->query('filter', 'all_time');

        $query = DB::table('sign_up')
            ->select('referredBy', DB::raw('count(*) as total_verifications'))
            ->where('is_verified', 1)
            ->whereNotNull('referredBy')
            ->where('referredBy', '!=', '');

        // Apply Time Filter
        switch ($filter) {
            case 'today':
                $query->whereDate('verified_at', Carbon::today());
                break;
            case 'weekly':
                $query->where('verified_at', '>=', Carbon::now()->startOfWeek());
                break;
            case 'monthly':
                $query->whereYear('verified_at', Carbon::now()->year)
                      ->whereMonth('verified_at', Carbon::now()->month);
                break;
            case 'all_time':
            default:
                // No date filter
                break;
        }

        $rankings = $query->groupBy('referredBy')
            ->orderBy('total_verifications', 'desc')
            ->limit(100)
            ->get();

        // Map recruiter details
        $response = $rankings->map(function ($rank, $index) {
            $user = SignUp::where('referCode', $rank->referredBy)->first();
            
            // Masking phone number (e.g., 0171638****)
            $maskedNumber = '';
            if ($user && $user->number) {
                $maskedNumber = substr($user->number, 0, 7) . '****';
            }

            return [
                'rank' => $index + 1,
                'user_id' => $user ? (string)$user->id : "0",
                'name' => $user ? $user->name : 'Unknown User',
                'number' => $maskedNumber,
                'profile_pic_url' => $user ? $user->profile_pic_url : null,
                'total_verifications' => (int)$rank->total_verifications,
                'referCode' => $rank->referredBy,
            ];
        });

        return response()->json([
            'success' => true,
            'filter' => $filter,
            'ranking' => $response
        ]);
    }

    /**
     * Get Daily Winners
     */
    public function getDailyWinners(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        
        $winners = DB::table('daily_winners')
            ->whereDate('date', $date)
            ->get();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'winners' => $winners
        ]);
    }

    /**
     * Get Today Live Ranking
     */
    public function getTodayLiveRanking()
    {
        $request = new Request(['filter' => 'today']);
        return $this->getRanking($request);
    }

    /**
     * Get Weekly Winner
     */
    public function getWeeklyWinner()
    {
        $winner = DB::table('weekly_winners')
            ->orderBy('week_start_date', 'desc')
            ->first();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'ranking' => $winner ? [$winner] : []
        ]);
    }

    /**
     * Get Weekly Ranking
     */
    public function getWeeklyRanking()
    {
        $request = new Request(['filter' => 'weekly']);
        return $this->getRanking($request);
    }

    /**
     * Get Weekly Winners by Date
     */
    public function getWeeklyWinnersByDate(Request $request)
    {
        $weekStart = $request->query('week_start_date');
        
        $winners = DB::table('weekly_winners');
        if ($weekStart) {
            $winners->where('week_start_date', $weekStart);
        }
        
        return response()->json([
            'status' => 'success',
            'success' => true,
            'winners' => $winners->get()
        ]);
    }
}
