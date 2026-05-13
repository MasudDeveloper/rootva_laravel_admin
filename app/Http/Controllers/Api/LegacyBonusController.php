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
        if (!$request->has('date')) {
            return response()->json([
                'status' => false,
                'message' => 'Date is required',
                'winner' => []
            ]);
        }

        $dateStr = $request->query('date');
        $start = $dateStr . ' 00:00:00';
        $end = $dateStr . ' 23:59:59';
        
        $winners = DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->join('sign_up as r', 's.referredBy', '=', 'r.referCode')
            ->select(
                's.referredBy as refer_id',
                'r.id as user_id',
                'r.name',
                'r.profile_pic_url',
                DB::raw('COUNT(*) as total_verifications')
            )
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$start, $end])
            ->groupBy('s.referredBy', 'r.id', 'r.name', 'r.profile_pic_url')
            ->having('total_verifications', '>=', 4)
            ->orderByDesc('total_verifications')
            ->limit(1)
            ->get();

        return response()->json([
            'status' => true,
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
        if (!$request->has('week_start_date')) {
            return response()->json([
                'status' => false,
                'message' => 'Week start date is required',
                'winner' => []
            ]);
        }

        $week_start_date = $request->query('week_start_date');
        $week_end_date = Carbon::parse($week_start_date)->addDays(6)->toDateString();

        $start = $week_start_date . ' 00:00:00';
        $end = $week_end_date . ' 23:59:59';
        
        $winners = DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->join('sign_up as r', 's.referredBy', '=', 'r.referCode')
            ->select(
                's.referredBy as refer_id',
                'r.id as user_id',
                'r.name',
                'r.profile_pic_url',
                DB::raw('COUNT(*) as total_verifications')
            )
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$start, $end])
            ->groupBy('s.referredBy', 'r.id', 'r.name', 'r.profile_pic_url')
            ->having('total_verifications', '>=', 20)
            ->orderByDesc('total_verifications')
            ->limit(1)
            ->get();

        return response()->json([
            'status' => true,
            'winner' => $winners,
            'week_info' => [
                'start_date' => $week_start_date,
                'end_date' => $week_end_date
            ]
        ]);
    }

    private function getRanking($filter)
    {
        $query = DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->join('sign_up as r', 's.referredBy', '=', 'r.referCode')
            ->select(
                's.referredBy as referrer_id',
                'r.name',
                'r.profile_pic_url',
                DB::raw('COUNT(*) as total_verifications')
            )
            ->where('vr.status', 'Approved');

        $metadata = [];

        switch ($filter) {
            case 'today':
                $date = Carbon::today()->toDateString();
                $start = $date . ' 00:00:00';
                $end = $date . ' 23:59:59';
                $query->whereBetween('vr.verified_raw_time', [$start, $end]);
                break;
            case 'weekly':
                // Start week from Saturday to match legacy logic
                $startOfWeek = Carbon::now()->startOfWeek(Carbon::SATURDAY);
                $endOfWeek = $startOfWeek->copy()->addDays(6)->endOfDay();
                
                $query->whereBetween('vr.verified_raw_time', [
                    $startOfWeek->toDateTimeString(), 
                    $endOfWeek->toDateTimeString()
                ]);

                $metadata['start_of_week'] = $startOfWeek->toDateString();
                $metadata['end_of_week'] = $endOfWeek->toDateString();
                break;
        }

        $rankings = $query->groupBy('s.referredBy', 'r.name', 'r.profile_pic_url')
            ->orderBy('total_verifications', 'desc')
            ->get();

        $response = $rankings->map(function ($rank) {
            return [
                'referrer_id' => $rank->referrer_id,
                'name' => $rank->name,
                'profile_pic_url' => $rank->profile_pic_url ?? "",
                'total_verifications' => (int)$rank->total_verifications,
            ];
        });

        $result = ['status' => true];
        if (!empty($metadata)) {
            $result = array_merge($result, $metadata);
        }
        $result['ranking'] = $response;

        return response()->json($result);
    }
}
