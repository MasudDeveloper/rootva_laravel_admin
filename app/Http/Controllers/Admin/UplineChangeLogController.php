<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UplineChangeLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class UplineChangeLogController extends Controller
{
    public function index(Request $request)
    {
        // Ensure table exists safely
        if (!Schema::hasTable('upline_change_logs')) {
            Schema::create('upline_change_logs', function ($table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->nullable()->index();
                $table->string('admin_name')->nullable();
                $table->string('admin_username')->nullable();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('user_name')->nullable();
                $table->string('user_number')->nullable();
                $table->string('user_refer_code')->nullable();
                $table->string('old_upline_code')->nullable();
                $table->string('old_upline_name')->nullable();
                $table->string('old_upline_number')->nullable();
                $table->string('new_upline_code')->nullable();
                $table->string('new_upline_name')->nullable();
                $table->string('new_upline_number')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }

        $search = $request->input('search');
        $dateFilter = $request->input('date_filter', 'all');
        $customDate = $request->input('custom_date');

        $query = UplineChangeLog::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('user_number', 'like', "%{$search}%")
                  ->orWhere('user_refer_code', 'like', "%{$search}%")
                  ->orWhere('admin_name', 'like', "%{$search}%")
                  ->orWhere('admin_username', 'like', "%{$search}%")
                  ->orWhere('old_upline_code', 'like', "%{$search}%")
                  ->orWhere('new_upline_code', 'like', "%{$search}%");
            });
        }

        if ($dateFilter === 'today') {
            $query->whereDate('created_at', Carbon::today('Asia/Dhaka'));
        } elseif ($dateFilter === 'yesterday') {
            $query->whereDate('created_at', Carbon::yesterday('Asia/Dhaka'));
        } elseif ($dateFilter === 'custom' && $customDate) {
            $query->whereDate('created_at', $customDate);
        }

        $logs = $query->orderBy('id', 'desc')->paginate(30)->withQueryString();

        $stats = [
            'total_changes' => UplineChangeLog::count(),
            'today_changes' => UplineChangeLog::whereDate('created_at', Carbon::today('Asia/Dhaka'))->count(),
        ];

        return view('admin.users.upline_logs', compact('logs', 'search', 'dateFilter', 'customDate', 'stats'));
    }
}

