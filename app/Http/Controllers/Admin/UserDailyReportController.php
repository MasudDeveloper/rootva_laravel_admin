<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\VerificationRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserDailyReportController extends Controller
{
    /**
     * Helper to convert English digits to Bengali
     */
    private function toBengaliDigits($str)
    {
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        return str_replace($en, $bn, (string)$str);
    }

    /**
     * Helper to convert Bengali digits to English
     */
    private function toEnglishDigits($str)
    {
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        return str_replace($bn, $en, (string)$str);
    }

    /**
     * Daily User Registrations & Verifications Report
     */
    public function index(Request $request)
    {
        // Determine default date (use latest registered user date or current date)
        $latestCreated = SignUp::orderBy('id', 'desc')->value('created_at');
        $defaultDate = date('Y-m-d');
        if ($latestCreated) {
            $normalizedLatest = $this->toEnglishDigits($latestCreated);
            if (preg_match('/^(\d{2})-(\d{2})-(\d{4})/', $normalizedLatest, $matches)) {
                $defaultDate = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
            }
        }

        $selectedDate = $request->input('date', $defaultDate);
        // Validate date format Y-m-d
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = $defaultDate;
        }

        $dateCarbon = Carbon::parse($selectedDate);
        $selectedMonth = $request->input('month', $dateCarbon->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = $dateCarbon->format('Y-m');
        }

        $activeTab = $request->input('tab', 'overview');

        // Formats for selected single date
        $dmyEn = $dateCarbon->format('d-m-Y');
        $dmyBn = $this->toBengaliDigits($dmyEn);

        // 1. Single Selected Day Metrics
        $dayNewUsersCount = SignUp::where(function ($q) use ($dmyEn, $dmyBn) {
            $q->where('created_at', 'LIKE', "{$dmyEn}%")
              ->orWhere('created_at', 'LIKE', "{$dmyBn}%");
        })->count();

        $dayVerifiedCount = SignUp::where('is_verified', 1)
            ->whereDate('verified_raw_time', $selectedDate)
            ->count();

        $dayVerReqsApproved = VerificationRequest::where('status', 'Approved')
            ->whereDate('verified_raw_time', $selectedDate)
            ->count();

        $dayConversionRate = $dayNewUsersCount > 0 ? round(($dayVerifiedCount / $dayNewUsersCount) * 100, 1) : 0;

        // 2. Month Timeline & Day-by-Day Breakdown
        $monthCarbon = Carbon::createFromFormat('Y-m', $selectedMonth);
        $monthStart = $monthCarbon->copy()->startOfMonth()->format('Y-m-d 00:00:00');
        $monthEnd = $monthCarbon->copy()->endOfMonth()->format('Y-m-d 23:59:59');

        // Total users verified in the selected month
        $monthVerifiedTotal = SignUp::where('is_verified', 1)
            ->whereBetween('verified_raw_time', [$monthStart, $monthEnd])
            ->count();

        // Group verified accounts by day in this month
        $verifiedByDay = SignUp::where('is_verified', 1)
            ->whereBetween('verified_raw_time', [$monthStart, $monthEnd])
            ->selectRaw('DATE(verified_raw_time) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd')
            ->toArray();

        // Month formats for registration search (e.g. -04-2026 and -০৪-২০২৬)
        $mYEn = '-' . $monthCarbon->format('m-Y');
        $mYBn = '-' . $this->toBengaliDigits($monthCarbon->format('m-Y'));

        $monthSignups = SignUp::where(function ($q) use ($mYEn, $mYBn) {
            $q->where('created_at', 'LIKE', "%{$mYEn}%")
              ->orWhere('created_at', 'LIKE', "%{$mYBn}%");
        })->select('created_at')->get();

        $newUsersByDay = [];
        $monthNewUsersTotal = 0;
        foreach ($monthSignups as $su) {
            $monthNewUsersTotal++;
            $raw = $this->toEnglishDigits($su->created_at);
            if (preg_match('/^(\d{2})-(\d{2})-(\d{4})/', $raw, $m)) {
                $dayKey = "{$m[3]}-{$m[2]}-{$m[1]}";
                $newUsersByDay[$dayKey] = ($newUsersByDay[$dayKey] ?? 0) + 1;
            }
        }

        // Build list of days in the month (descending order)
        $daysInMonth = $monthCarbon->daysInMonth;
        $dayBreakdown = [];
        for ($i = $daysInMonth; $i >= 1; $i--) {
            $currentDayStr = sprintf('%s-%02d', $selectedMonth, $i);
            $regCount = $newUsersByDay[$currentDayStr] ?? 0;
            $verCount = $verifiedByDay[$currentDayStr] ?? 0;
            $rate = $regCount > 0 ? round(($verCount / $regCount) * 100, 1) : ($verCount > 0 ? 100 : 0);

            $dayBreakdown[] = [
                'date' => $currentDayStr,
                'day_num' => $i,
                'carbon' => Carbon::parse($currentDayStr),
                'new_users' => $regCount,
                'verified_users' => $verCount,
                'conversion_rate' => $rate,
                'is_selected' => ($currentDayStr === $selectedDate),
            ];
        }

        // 3. User lists for selected single day
        $newUsersList = SignUp::where(function ($q) use ($dmyEn, $dmyBn) {
            $q->where('created_at', 'LIKE', "{$dmyEn}%")
              ->orWhere('created_at', 'LIKE', "{$dmyBn}%");
        })->orderBy('id', 'desc')->paginate(25, ['*'], 'new_page')->withQueryString();

        $verifiedUsersList = SignUp::where('is_verified', 1)
            ->whereDate('verified_raw_time', $selectedDate)
            ->orderBy('verified_raw_time', 'desc')
            ->paginate(25, ['*'], 'ver_page')
            ->withQueryString();

        // 4. Recent active dates for quick pills
        $recentDays = collect($dayBreakdown)
            ->filter(function ($item) {
                return $item['new_users'] > 0 || $item['verified_users'] > 0;
            })
            ->take(8);

        return view('admin.users.daily_report', compact(
            'selectedDate',
            'selectedMonth',
            'activeTab',
            'dayNewUsersCount',
            'dayVerifiedCount',
            'dayVerReqsApproved',
            'dayConversionRate',
            'monthNewUsersTotal',
            'monthVerifiedTotal',
            'dayBreakdown',
            'newUsersList',
            'verifiedUsersList',
            'recentDays'
        ));
    }
}
