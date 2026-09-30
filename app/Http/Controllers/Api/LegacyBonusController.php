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

        $bengali_digits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $raw_date = $request->query('week_start_date');
        $week_start_date = str_replace($bengali_digits, $english_digits, $raw_date);

        try {
            $week_end_date = Carbon::parse($week_start_date)->addDays(6)->toDateString();
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid date format',
                'winner' => []
            ]);
        }

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

    /**
     * Daily Target Status (get_daily_target_status.php)
     */
    public function getDailyTargetStatus(Request $request)
    {
        $userId = $request->input('user_id');
        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'User ID is required'
            ]);
        }

        $user = SignUp::find($userId);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ]);
        }

        $today = Carbon::today()->toDateString();
        $start = $today . ' 00:00:00';
        $end = $today . ' 23:59:59';

        // Count verifications for today
        $verificationsToday = DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->where('s.referredBy', $user->referCode)
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$start, $end])
            ->count();

        // Get history of daily bonus for this user
        $history = Transaction::where('user_id', $userId)
            ->where('payment_gateway', 'Daily Bonus')
            ->orderBy('id', 'desc')
            ->get(['amount', 'description', 'created_at']);
            
        $totalEarned = 0;
        $tierCounts = [
            '20' => 0,
            '30' => 0,
            '40' => 0,
            '60' => 0
        ];

        foreach($history as $item) {
            $totalEarned += (float) $item->amount;
            $amtStr = (string) intval($item->amount);
            if (isset($tierCounts[$amtStr])) {
                $tierCounts[$amtStr]++;
            }
        }

        // Expected reward structure
        $structure = [
            ['verifications' => 2, 'reward' => 20],
            ['verifications' => 3, 'reward' => 30],
            ['verifications' => 4, 'reward' => 40],
            ['verifications' => '5+', 'reward' => 60],
        ];

        return response()->json([
            'status' => true,
            'today_verifications' => $verificationsToday,
            'total_earned' => $totalEarned,
            'tier_counts' => $tierCounts,
            'reward_structure' => $structure,
            'history' => $history
        ]);
    }

    public function getWeeklyTargetStatus(Request $request)
    {
        $userId = $request->input('user_id');
        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'User ID is required'
            ]);
        }

        $user = SignUp::find($userId);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ]);
        }

        // Current week (Saturday to Friday)
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::SATURDAY);
        $endOfWeek = $startOfWeek->copy()->addDays(6)->endOfDay();

        // Count verifications for this week
        $verificationsWeek = DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->where('s.referredBy', $user->referCode)
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$startOfWeek->toDateTimeString(), $endOfWeek->toDateTimeString()])
            ->count();

        // Get history of weekly bonus for this user
        $history = Transaction::where('user_id', $userId)
            ->where('payment_gateway', 'Weekly Bonus')
            ->orderBy('id', 'desc')
            ->get(['amount', 'description', 'created_at']);
            
        $totalEarned = 0;
        $tierCounts = [
            '100' => 0,
            '150' => 0,
            '200' => 0,
            '400' => 0
        ];

        foreach($history as $item) {
            $totalEarned += (float) $item->amount;
            $amtStr = (string) intval($item->amount);
            if (isset($tierCounts[$amtStr])) {
                $tierCounts[$amtStr]++;
            }
        }

        // Expected reward structure
        $structure = [
            ['verifications' => '10', 'reward' => 100],
            ['verifications' => '15', 'reward' => 150],
            ['verifications' => '20', 'reward' => 200],
            ['verifications' => '30+', 'reward' => 400],
        ];

        return response()->json([
            'status' => true,
            'week_verifications' => $verificationsWeek,
            'total_earned' => $totalEarned,
            'tier_counts' => $tierCounts,
            'reward_structure' => $structure,
            'history' => $history
        ]);
    }
}
