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
                $query->whereRaw("STR_TO_DATE(verified_at, '%d-%m-%Y') = ?", [Carbon::today()->toDateString()]);
                break;
            case 'weekly':
                $query->whereRaw("STR_TO_DATE(verified_at, '%d-%m-%Y') >= ?", [Carbon::now()->startOfWeek()->toDateString()]);
                break;
            case 'monthly':
                $query->whereRaw("STR_TO_DATE(verified_at, '%d-%m-%Y') >= ?", [Carbon::now()->startOfMonth()->toDateString()]);
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
        
        $winners = DB::table('transactions')
            ->join('sign_up', 'transactions.user_id', '=', 'sign_up.id')
            ->select(
                'sign_up.id as user_id',
                'sign_up.name',
                'sign_up.profile_pic_url',
                'transactions.description',
                'transactions.created_at'
            )
            ->where('transactions.payment_gateway', 'Daily Bonus')
            ->whereDate('transactions.created_at', $date)
            ->get()
            ->map(function($w) {
                // Extract "X verifications" from description if available
                preg_match('/(\d+)\s+verifications/', $w->description, $matches);
                return [
                    'user_id' => (string)$w->user_id,
                    'name' => $w->name,
                    'profile_pic_url' => $w->profile_pic_url,
                    'total_verifications' => isset($matches[1]) ? $matches[1] : "4+",
                ];
            });

        return response()->json([
            'status' => true,
            'success' => true,
            'date' => $date,
            'winner' => $winners // Android expects "winner"
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
        $winner = DB::table('transactions')
            ->join('sign_up', 'transactions.user_id', '=', 'sign_up.id')
            ->select(
                'sign_up.id as user_id',
                'sign_up.name',
                'sign_up.profile_pic_url',
                'sign_up.referCode',
                'sign_up.number',
                'transactions.description',
                'transactions.created_at'
            )
            ->where('transactions.payment_gateway', 'Weekly Bonus')
            ->orderBy('transactions.created_at', 'desc')
            ->first();

        $response = [];
        if ($winner) {
            // Mask phone
            $maskedNumber = substr($winner->number, 0, 7) . '****';
            
            // Extract verifications
            preg_match('/(\d+)\s+verifications/', $winner->description, $matches);
            
            $response[] = [
                'rank' => 1,
                'user_id' => (string)$winner->user_id,
                'name' => $winner->name,
                'number' => $maskedNumber,
                'profile_pic_url' => $winner->profile_pic_url,
                'total_verifications' => isset($matches[1]) ? (int)$matches[1] : 15,
                'referCode' => $winner->referCode,
            ];
        }

        return response()->json([
            'status' => true,
            'success' => true,
            'ranking' => $response
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
        
        $query = DB::table('transactions')
            ->join('sign_up', 'transactions.user_id', '=', 'sign_up.id')
            ->select(
                'sign_up.id as user_id',
                'sign_up.name',
                'sign_up.profile_pic_url',
                'transactions.description',
                'transactions.created_at'
            )
            ->where('transactions.payment_gateway', 'Weekly Bonus');

        if ($weekStart) {
            $query->whereDate('transactions.created_at', $weekStart);
        }
        
        $winners = $query->get()->map(function($w) {
            preg_match('/(\d+)\s+verifications/', $w->description, $matches);
            return [
                'user_id' => (string)$w->user_id,
                'name' => $w->name,
                'profile_pic_url' => $w->profile_pic_url,
                'total_verifications' => isset($matches[1]) ? $matches[1] : "15+",
            ];
        });
        
        return response()->json([
            'status' => true,
            'success' => true,
            'winner' => $winners // Android expects "winner"
        ]);
    }
}
