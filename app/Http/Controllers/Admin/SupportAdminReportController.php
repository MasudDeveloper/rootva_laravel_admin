<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class SupportAdminReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Summary Stats
        $stats = [
            'total_added' => Transaction::where('payment_gateway', 'like', 'Support Admin%')
                ->where('type', 'income')
                ->sum('amount'),
            'total_withdrawn' => Transaction::where('payment_gateway', 'like', 'Support Admin%')
                ->where('type', 'withdraw')
                ->sum('amount')
        ];

        // 2. Daily Report (Grouped by date)
        $dailyReport = Transaction::where('payment_gateway', 'like', 'Support Admin%')
            ->selectRaw('DATE(date) as transaction_date, 
                         SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_added,
                         SUM(CASE WHEN type = "withdraw" THEN amount ELSE 0 END) as total_withdrawn')
            ->groupByRaw('DATE(date)')
            ->orderByRaw('DATE(date) DESC')
            ->get();

        // 3. Lifetime Report
        $lifetimeReport = Transaction::where('payment_gateway', 'like', 'Support Admin%')
            ->selectRaw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_added,
                         SUM(CASE WHEN type = "withdraw" THEN amount ELSE 0 END) as total_withdrawn')
            ->get();

        // 4. Filterable and Paginated Transaction History
        $query = Transaction::where('payment_gateway', 'like', 'Support Admin%')
            ->with('user')
            ->orderBy('id', 'DESC');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date')) {
            $query->whereRaw('DATE(date) = ?', [$request->date]);
        }

        $transactions = $query->paginate(20)->withQueryString();

        return view('admin.support-admin-report.index', compact(
            'stats',
            'dailyReport',
            'lifetimeReport',
            'transactions'
        ));
    }
}
