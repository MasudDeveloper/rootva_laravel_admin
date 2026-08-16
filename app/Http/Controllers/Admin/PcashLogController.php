<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PcashRechargeLog;

class PcashLogController extends Controller
{
    public function index(Request $request)
    {
        PcashRechargeLog::checkAndUpdatePendingLogs();
        
        $search = $request->input('search');
        $query = PcashRechargeLog::with('user');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('referCode', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->latest()->paginate(20)->withQueryString();

        return view('admin.pcash.logs.index', compact('logs', 'search'));
    }
}
