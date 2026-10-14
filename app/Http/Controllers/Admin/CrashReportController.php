<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppCrashReport;

class CrashReportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = AppCrashReport::latest('id');

        if ($status && in_array($status, ['Unresolved', 'Investigating', 'Resolved'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('exception_class', 'like', "%{$search}%")
                  ->orWhere('exception_message', 'like', "%{$search}%")
                  ->orWhere('crash_location', 'like', "%{$search}%")
                  ->orWhere('device_model', 'like', "%{$search}%")
                  ->orWhere('user_phone', 'like', "%{$search}%");
            });
        }

        $reports = $query->paginate(25)->withQueryString();

        $counts = [
            'total' => AppCrashReport::count(),
            'unresolved' => AppCrashReport::where('status', 'Unresolved')->count(),
            'investigating' => AppCrashReport::where('status', 'Investigating')->count(),
            'resolved' => AppCrashReport::where('status', 'Resolved')->count(),
        ];

        return view('admin.crash_reports.index', compact('reports', 'counts', 'status', 'search'));
    }

    public function show($id)
    {
        $report = AppCrashReport::findOrFail($id);
        return view('admin.crash_reports.show', compact('report'));
    }

    public function updateStatus(Request $request, $id)
    {
        $report = AppCrashReport::findOrFail($id);
        $request->validate([
            'status' => 'required|in:Unresolved,Investigating,Resolved',
        ]);

        $report->status = $request->status;
        $report->save();

        return back()->with('success', 'Crash status updated to ' . $request->status);
    }

    public function destroy($id)
    {
        $report = AppCrashReport::findOrFail($id);
        $report->delete();

        return redirect()->route('admin.crash-reports.index')->with('success', 'Crash report deleted.');
    }

    public function clearResolved()
    {
        AppCrashReport::where('status', 'Resolved')->delete();
        return back()->with('success', 'Resolved crash reports cleared.');
    }
}
