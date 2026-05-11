<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LegacyBonusController extends Controller
{
    /**
     * Legacy Daily Winners (get_daily_winners_by_date.php)
     */
    public function getWinnersByDate(Request $request)
    {
        $dateStr = $request->query('date', Carbon::today()->toDateString());
        $start = $dateStr . ' 00:00:00';
        $end = $dateStr . ' 23:59:59';
        
        $winners = DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->join('sign_up as r', 's.referredBy', '=', 'r.referCode')
            ->select(
                'r.id as user_id',
                'r.referCode as refer_id',
                'r.name',
                'r.profile_pic_url',
                DB::raw('COUNT(*) as total_verifications')
            )
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$start, $end])
            ->groupBy('s.referredBy', 'r.id', 'r.referCode', 'r.name', 'r.profile_pic_url')
            ->having('total_verifications', '>=', 4)
            ->orderByDesc('total_verifications')
            ->limit(1)
            ->get()
            ->map(function($w) {
                return [
                    'user_id' => (string)$w->user_id,
                    'refer_id' => $w->refer_id,
                    'name' => $w->name,
                    'profile_pic_url' => $w->profile_pic_url,
                    'total_verifications' => (string)$w->total_verifications,
                ];
            });

        return response()->json([
            'status' => true,
            'success' => true,
            'date' => $dateStr,
            'winner' => $winners
        ]);
    }

    /**
     * Legacy Today Live Ranking (get_daily_live_ranking.php)
     */
    public function getTodayLiveRanking()
    {
        return $this->getRanking('today');
    }

    /**
     * Legacy Weekly Ranking (get_weekly_ranking.php)
     */
    public function getWeeklyRanking()
    {
        return $this->getRanking('weekly');
    }

    /**
     * Legacy Weekly Winners by Date (get_weekly_winners_by_date.php)
     */
    public function getWeeklyWinnersByDate(Request $request)
    {
        $dateStr = $request->query('week_start_date', $request->query('date', Carbon::today()->toDateString()));
        $start = Carbon::parse($dateStr)->startOfDay()->toDateTimeString();
        $end = Carbon::parse($dateStr)->addDays(7)->endOfDay()->toDateTimeString();
        
        $winners = DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->join('sign_up as r', 's.referredBy', '=', 'r.referCode')
            ->select(
                'r.id as user_id',
                'r.referCode as refer_id',
                'r.name',
                'r.profile_pic_url',
                DB::raw('COUNT(*) as total_verifications')
            )
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$start, $end])
            ->groupBy('s.referredBy', 'r.id', 'r.referCode', 'r.name', 'r.profile_pic_url')
            ->having('total_verifications', '>=', 15)
            ->orderByDesc('total_verifications')
            ->limit(1)
            ->get()
            ->map(function($w) {
                return [
                    'user_id' => (string)$w->user_id,
                    'refer_id' => $w->refer_id,
                    'name' => $w->name,
                    'profile_pic_url' => $w->profile_pic_url,
                    'total_verifications' => (string)$w->total_verifications,
                ];
            });

        return response()->json([
            'status' => true,
            'success' => true,
            'date' => $dateStr,
            'winner' => $winners
        ]);
    }

    private function getRanking($filter)
    {
        $query = DB::table('sign_up')
            ->select('referredBy', DB::raw('count(*) as total_verifications'))
            ->where('is_verified', 1)
            ->whereNotNull('referredBy')
            ->where('referredBy', '!=', '');

        switch ($filter) {
            case 'today':
                $query->whereRaw("STR_TO_DATE(verified_at, '%d-%m-%Y') = ?", [Carbon::today()->toDateString()]);
                break;
            case 'weekly':
                // Start week from Saturday to match Android app logic
                $startOfWeek = Carbon::now()->startOfWeek(Carbon::SATURDAY)->toDateString();
                $query->whereRaw("STR_TO_DATE(verified_at, '%d-%m-%Y') >= ?", [$startOfWeek]);
                break;
        }

        $rankings = $query->groupBy('referredBy')
            ->orderBy('total_verifications', 'desc')
            ->limit(20)
            ->get();

        $response = $rankings->map(function ($rank, $index) {
            $user = SignUp::where('referCode', $rank->referredBy)->first();
            return [
                'rank' => $index + 1,
                'user_id' => $user ? (string)$user->id : "0",
                'name' => $user ? $user->name : 'Unknown User',
                'profile_pic_url' => $user ? $user->profile_pic_url : null,
                'total_verifications' => (int)$rank->total_verifications,
                'total_income' => (int)$rank->total_verifications, // For backward compatibility
            ];
        });

        return response()->json([
            'status' => true,
            'success' => true,
            'ranking' => $response
        ]);
    }
}
