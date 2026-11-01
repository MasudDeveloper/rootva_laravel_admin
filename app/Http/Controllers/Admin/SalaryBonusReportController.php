<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\SignUp;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalaryBonusReportController extends Controller
{
    /**
     * Display Monthly Salary and Bonus Accounting & Summary Report
     */
    public function index(Request $request)
    {
        $selectedMonth = $request->input('month', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = date('Y-m');
        }

        $filterGateway = $request->input('gateway', 'all');
        $search = trim($request->input('search', ''));

        try {
            $monthCarbon = Carbon::createFromFormat('Y-m', $selectedMonth);
        } catch (\Exception $e) {
            $monthCarbon = Carbon::now();
            $selectedMonth = $monthCarbon->format('Y-m');
        }

        $startOfMonth = $monthCarbon->copy()->startOfMonth()->toDateTimeString();
        $endOfMonth = $monthCarbon->copy()->endOfMonth()->toDateTimeString();

        // Base bonus & salary criteria (reusable closure)
        $bonusCriteria = function ($query) {
            $query->where(function ($q) {
                $q->where('payment_gateway', 'like', '%Bonus%')
                  ->orWhere('payment_gateway', 'like', '%Salary%')
                  ->orWhere('payment_gateway', 'like', '%Reward%')
                  ->orWhere('payment_gateway', 'like', '%Prize%')
                  ->orWhereIn('payment_gateway', [
                      'Daily Bonus', 'Weekly Bonus', 'Monthly Salary', 'Monthly Bonus',
                      'Spin Bonus', 'bonus', 'Refer Bonus', 'Manual Refer Bonus',
                      'Date Bonus', 'All Verified Bonus', 'Coupon Bonus', 'Course Bonus',
                      'Leadership Reward', 'SMM Referral Bonus'
                  ]);
            });
        };

        // 1. Single Blazing-Fast Aggregated Query for all Key KPI Metrics
        $stats = DB::table('transactions')
            ->where('type', 'income')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where($bonusCriteria)
            ->selectRaw("
                COUNT(*) as total_count,
                COUNT(DISTINCT user_id) as total_users,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN payment_gateway = 'Daily Bonus' THEN amount ELSE 0 END), 0) as daily_bonus_total,
                COUNT(CASE WHEN payment_gateway = 'Daily Bonus' THEN 1 END) as daily_bonus_count,
                COALESCE(SUM(CASE WHEN payment_gateway = 'Weekly Bonus' THEN amount ELSE 0 END), 0) as weekly_bonus_total,
                COUNT(CASE WHEN payment_gateway = 'Weekly Bonus' THEN 1 END) as weekly_bonus_count,
                COALESCE(SUM(CASE WHEN payment_gateway = 'Monthly Salary' THEN amount ELSE 0 END), 0) as monthly_salary_total,
                COUNT(CASE WHEN payment_gateway = 'Monthly Salary' THEN 1 END) as monthly_salary_count,
                COALESCE(SUM(CASE WHEN payment_gateway = 'Monthly Bonus' THEN amount ELSE 0 END), 0) as monthly_bonus_total,
                COUNT(CASE WHEN payment_gateway = 'Monthly Bonus' THEN 1 END) as monthly_bonus_count,
                COALESCE(SUM(CASE WHEN payment_gateway LIKE '%Refer%' OR payment_gateway IN ('Refer Bonus', 'Manual Refer Bonus', '10-Level Refer Bonus', 'SMM Referral Bonus') THEN amount ELSE 0 END), 0) as refer_bonus_total,
                COUNT(CASE WHEN payment_gateway LIKE '%Refer%' OR payment_gateway IN ('Refer Bonus', 'Manual Refer Bonus', '10-Level Refer Bonus', 'SMM Referral Bonus') THEN 1 END) as refer_bonus_count,
                COALESCE(SUM(CASE WHEN payment_gateway LIKE '%Date%' OR payment_gateway IN ('Date Bonus', 'All Verified Bonus', 'Bulk Bonus') THEN amount ELSE 0 END), 0) as date_bonus_total,
                COUNT(CASE WHEN payment_gateway LIKE '%Date%' OR payment_gateway IN ('Date Bonus', 'All Verified Bonus', 'Bulk Bonus') THEN 1 END) as date_bonus_count,
                COALESCE(SUM(CASE WHEN payment_gateway IN ('Spin Bonus', 'bonus') THEN amount ELSE 0 END), 0) as spin_bonus_total,
                COUNT(CASE WHEN payment_gateway IN ('Spin Bonus', 'bonus') THEN 1 END) as spin_bonus_count,
                COALESCE(SUM(CASE WHEN payment_gateway NOT IN ('Daily Bonus', 'Weekly Bonus', 'Monthly Salary', 'Monthly Bonus', 'Refer Bonus', 'Manual Refer Bonus', '10-Level Refer Bonus', 'SMM Referral Bonus', 'Date Bonus', 'All Verified Bonus', 'Bulk Bonus', 'Spin Bonus', 'bonus') AND payment_gateway NOT LIKE '%Refer%' THEN amount ELSE 0 END), 0) as other_bonus_total,
                COUNT(CASE WHEN payment_gateway NOT IN ('Daily Bonus', 'Weekly Bonus', 'Monthly Salary', 'Monthly Bonus', 'Refer Bonus', 'Manual Refer Bonus', '10-Level Refer Bonus', 'SMM Referral Bonus', 'Date Bonus', 'All Verified Bonus', 'Bulk Bonus', 'Spin Bonus', 'bonus') AND payment_gateway NOT LIKE '%Refer%' THEN 1 END) as other_bonus_count
            ")
            ->first();

        $selectedMonthTotal = (double)($stats->total_amount ?? 0);
        $selectedMonthTxCount = (int)($stats->total_count ?? 0);
        $selectedMonthUsersCount = (int)($stats->total_users ?? 0);

        $dailyBonusTotal = (double)($stats->daily_bonus_total ?? 0);
        $dailyBonusCount = (int)($stats->daily_bonus_count ?? 0);

        $weeklyBonusTotal = (double)($stats->weekly_bonus_total ?? 0);
        $weeklyBonusCount = (int)($stats->weekly_bonus_count ?? 0);

        $monthlySalaryTotal = (double)($stats->monthly_salary_total ?? 0);
        $monthlySalaryCount = (int)($stats->monthly_salary_count ?? 0);

        $monthlyBonusTotal = (double)($stats->monthly_bonus_total ?? 0);
        $monthlyBonusCount = (int)($stats->monthly_bonus_count ?? 0);

        $referBonusTotal = (double)($stats->refer_bonus_total ?? 0);
        $referBonusCount = (int)($stats->refer_bonus_count ?? 0);

        $dateBonusTotal = (double)($stats->date_bonus_total ?? 0);
        $dateBonusCount = (int)($stats->date_bonus_count ?? 0);

        $spinBonusTotal = (double)($stats->spin_bonus_total ?? 0);
        $spinBonusCount = (int)($stats->spin_bonus_count ?? 0);

        $otherBonusTotal = (double)($stats->other_bonus_total ?? 0);
        $otherBonusCount = (int)($stats->other_bonus_count ?? 0);

        // 2. Category Summary Breakdown Table
        $categoryBreakdown = DB::table('transactions')
            ->select('payment_gateway', DB::raw('COUNT(*) as tx_count'), DB::raw('COUNT(DISTINCT user_id) as users_count'), DB::raw('SUM(amount) as total_amount'))
            ->where('type', 'income')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where($bonusCriteria)
            ->groupBy('payment_gateway')
            ->orderByDesc('total_amount')
            ->get();

        // 3. Day-by-Day Distribution Breakdown
        $dailyTransactions = DB::table('transactions')
            ->select(
                DB::raw("DATE(`date`) as day_date"),
                DB::raw("SUM(CASE WHEN `payment_gateway` = 'Daily Bonus' THEN `amount` ELSE 0 END) as daily_bonus"),
                DB::raw("SUM(CASE WHEN `payment_gateway` = 'Weekly Bonus' THEN `amount` ELSE 0 END) as weekly_bonus"),
                DB::raw("SUM(CASE WHEN `payment_gateway` = 'Monthly Salary' THEN `amount` ELSE 0 END) as monthly_salary"),
                DB::raw("SUM(CASE WHEN `payment_gateway` NOT IN ('Daily Bonus', 'Weekly Bonus', 'Monthly Salary') THEN `amount` ELSE 0 END) as other_bonuses"),
                DB::raw("SUM(`amount`) as day_total"),
                DB::raw("COUNT(*) as day_tx_count")
            )
            ->where('type', 'income')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where($bonusCriteria)
            ->groupBy(DB::raw("DATE(`date`)"))
            ->orderByDesc('day_date')
            ->get();

        // 4. Comparison: This Month vs Last Month vs 2 Months Ago (Single Query)
        $currentMonthCode = date('Y-m');
        $lastMonthCode = date('Y-m', strtotime('first day of -1 month'));
        $prev2MonthCode = date('Y-m', strtotime('first day of -2 months'));

        $twoMonthsAgoStart = Carbon::now()->subMonths(2)->startOfMonth()->toDateTimeString();
        $thisMonthEnd = Carbon::now()->endOfMonth()->toDateTimeString();

        $comparisonData = DB::table('transactions')
            ->selectRaw("DATE_FORMAT(`date`, '%Y-%m') as m_code, COALESCE(SUM(amount), 0) as total")
            ->where('type', 'income')
            ->whereBetween('date', [$twoMonthsAgoStart, $thisMonthEnd])
            ->where($bonusCriteria)
            ->groupBy('m_code')
            ->pluck('total', 'm_code')
            ->toArray();

        $currentMonthTotal = (double)($comparisonData[$currentMonthCode] ?? 0);
        $lastMonthTotal = (double)($comparisonData[$lastMonthCode] ?? 0);
        $prev2MonthTotal = (double)($comparisonData[$prev2MonthCode] ?? 0);

        // 5. Paginated Transaction Logs with Search and Filter
        $logQuery = Transaction::with('user:id,name,number,referCode,referredBy')
            ->where('type', 'income')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where($bonusCriteria);

        if ($filterGateway !== 'all') {
            if ($filterGateway === 'refer_group') {
                $logQuery->where(function ($q) {
                    $q->where('payment_gateway', 'like', '%Refer%')
                      ->orWhereIn('payment_gateway', ['Refer Bonus', 'Manual Refer Bonus', '10-Level Refer Bonus', 'SMM Referral Bonus']);
                });
            } elseif ($filterGateway === 'date_group') {
                $logQuery->where(function ($q) {
                    $q->where('payment_gateway', 'like', '%Date%')
                      ->orWhereIn('payment_gateway', ['Date Bonus', 'All Verified Bonus', 'Bulk Bonus']);
                });
            } elseif ($filterGateway === 'spin_group') {
                $logQuery->whereIn('payment_gateway', ['Spin Bonus', 'bonus']);
            } else {
                $logQuery->where('payment_gateway', $filterGateway);
            }
        }

        if (!empty($search)) {
            $logQuery->where(function ($q) use ($search) {
                $q->where('payment_gateway', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('refer_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('number', 'like', "%{$search}%")
                        ->orWhere('referCode', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $logQuery->orderBy('id', 'desc')->paginate(30)->withQueryString();

        return view('admin.rewards.salary_bonus_report', compact(
            'selectedMonth', 'filterGateway', 'search',
            'selectedMonthTotal', 'selectedMonthTxCount', 'selectedMonthUsersCount',
            'dailyBonusTotal', 'dailyBonusCount',
            'weeklyBonusTotal', 'weeklyBonusCount',
            'monthlySalaryTotal', 'monthlySalaryCount',
            'monthlyBonusTotal', 'monthlyBonusCount',
            'referBonusTotal', 'referBonusCount',
            'dateBonusTotal', 'dateBonusCount',
            'spinBonusTotal', 'spinBonusCount',
            'otherBonusTotal', 'otherBonusCount',
            'categoryBreakdown', 'dailyTransactions',
            'currentMonthTotal', 'lastMonthTotal', 'prev2MonthTotal',
            'logs'
        ));
    }

    /**
     * Export Complete Salary & Bonus Ledger for the Selected Month to CSV
     */
    public function exportCsv(Request $request)
    {
        $selectedMonth = $request->input('month', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = date('Y-m');
        }

        $filterGateway = $request->input('gateway', 'all');

        try {
            $monthCarbon = Carbon::createFromFormat('Y-m', $selectedMonth);
        } catch (\Exception $e) {
            $monthCarbon = Carbon::now();
            $selectedMonth = $monthCarbon->format('Y-m');
        }

        $startOfMonth = $monthCarbon->copy()->startOfMonth()->toDateTimeString();
        $endOfMonth = $monthCarbon->copy()->endOfMonth()->toDateTimeString();

        $bonusCriteria = function ($query) {
            $query->where(function ($q) {
                $q->where('payment_gateway', 'like', '%Bonus%')
                  ->orWhere('payment_gateway', 'like', '%Salary%')
                  ->orWhere('payment_gateway', 'like', '%Reward%')
                  ->orWhere('payment_gateway', 'like', '%Prize%')
                  ->orWhereIn('payment_gateway', [
                      'Daily Bonus', 'Weekly Bonus', 'Monthly Salary', 'Monthly Bonus',
                      'Spin Bonus', 'bonus', 'Refer Bonus', 'Manual Refer Bonus',
                      'Date Bonus', 'All Verified Bonus', 'Coupon Bonus', 'Course Bonus',
                      'Leadership Reward', 'SMM Referral Bonus'
                  ]);
            });
        };

        $query = Transaction::with('user:id,name,number,referCode,referredBy')
            ->where('type', 'income')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where($bonusCriteria);

        if ($filterGateway !== 'all') {
            if ($filterGateway === 'refer_group') {
                $query->where(function ($q) {
                    $q->where('payment_gateway', 'like', '%Refer%')
                      ->orWhereIn('payment_gateway', ['Refer Bonus', 'Manual Refer Bonus', '10-Level Refer Bonus', 'SMM Referral Bonus']);
                });
            } elseif ($filterGateway === 'date_group') {
                $query->where(function ($q) {
                    $q->where('payment_gateway', 'like', '%Date%')
                      ->orWhereIn('payment_gateway', ['Date Bonus', 'All Verified Bonus', 'Bulk Bonus']);
                });
            } elseif ($filterGateway === 'spin_group') {
                $query->whereIn('payment_gateway', ['Spin Bonus', 'bonus']);
            } else {
                $query->where('payment_gateway', $filterGateway);
            }
        }

        $records = $query->orderBy('id', 'desc')->get();

        $filename = "salary_and_bonus_report_" . $selectedMonth . "_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Expires"             => "0"
        ];

        return response()->stream(function () use ($records) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'Trx ID', 'User Name', 'Phone Number', 'Refer Code',
                'Upline Referrer', 'Bonus / Salary Category', 'Amount (TK)',
                'Description', 'Date / Time'
            ]);

            foreach ($records as $row) {
                $user = $row->user;
                $dateFormatted = $row->date ? Carbon::parse($row->date)->format('h:i A, d M, Y') : ($row->created_at ?: 'N/A');

                fputcsv($file, [
                    $row->id,
                    $user ? $user->name : 'N/A',
                    $user ? $user->number : 'N/A',
                    $user ? $user->referCode : ($row->refer_id ?: 'N/A'),
                    $user ? ($user->referredBy ?: 'System') : 'System',
                    $row->payment_gateway,
                    number_format((double)$row->amount, 2, '.', ''),
                    $row->description,
                    $dateFormatted
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }
}
