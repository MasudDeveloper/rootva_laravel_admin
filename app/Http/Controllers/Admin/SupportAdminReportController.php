<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\SupportAdmin;
use Illuminate\Http\Request;

class SupportAdminReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Summary Stats
        $stats = [
            'total_added' => Transaction::whereNotNull('support_admin_id')
                ->where('type', 'income')
                ->sum('amount'),
            'total_withdrawn' => Transaction::whereNotNull('support_admin_id')
                ->where('type', 'withdraw')
                ->sum('amount')
        ];

        // 2. Daily Report (Grouped by date & support admin)
        $dailyReport = Transaction::whereNotNull('support_admin_id')
            ->selectRaw('DATE(date) as transaction_date, support_admin_id, 
                         SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_added,
                         SUM(CASE WHEN type = "withdraw" THEN amount ELSE 0 END) as total_withdrawn')
            ->groupByRaw('DATE(date), support_admin_id')
            ->orderByRaw('DATE(date) DESC')
            ->with('supportAdmin')
            ->get();

        // 3. Lifetime Report (Grouped by support admin)
        $lifetimeReport = Transaction::whereNotNull('support_admin_id')
            ->selectRaw('support_admin_id, 
                         SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_added,
                         SUM(CASE WHEN type = "withdraw" THEN amount ELSE 0 END) as total_withdrawn')
            ->groupBy('support_admin_id')
            ->with('supportAdmin')
            ->get();

        // 4. Filterable and Paginated Transaction History
        $query = Transaction::whereNotNull('support_admin_id')
            ->with(['user', 'supportAdmin'])
            ->orderBy('id', 'DESC');

        if ($request->filled('support_admin_id')) {
            $query->where('support_admin_id', $request->support_admin_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date')) {
            $query->whereRaw('DATE(date) = ?', [$request->date]);
        }

        $transactions = $query->paginate(20)->withQueryString();
        $supportAdmins = SupportAdmin::all();

        return view('admin.support-admin-report.index', compact(
            'stats',
            'dailyReport',
            'lifetimeReport',
            'transactions',
            'supportAdmins'
        ));
    }
}
