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

        if (!empty($status)) {
            $query->whereRaw('LOWER(status) = ?', [strtolower(trim($status))]);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('exception_class', 'like', "%{$search}%")
                  ->orWhere('exception_message', 'like', "%{$search}%")
                  ->orWhere('crash_location', 'like', "%{$search}%")
                  ->orWhere('device_model', 'like', "%{$search}%")
                  ->orWhere('user_phone', 'like', "%{$search}%");
            });
        }

        $reports = $query->paginate(25)->withQueryString();

        $stats = [
            'total' => AppCrashReport::count(),
            'unresolved' => AppCrashReport::whereRaw('LOWER(status) = ?', ['unresolved'])->count(),
            'investigating' => AppCrashReport::whereRaw('LOWER(status) = ?', ['investigating'])->count(),
            'resolved' => AppCrashReport::whereRaw('LOWER(status) = ?', ['resolved'])->count(),
        ];
        $counts = $stats;

        return view('admin.crash_reports.index', compact('reports', 'stats', 'counts', 'status', 'search'));
    }

    public function show($id)
    {
        $report = AppCrashReport::with('user')->findOrFail($id);
        $similarCount = $report->getSimilarCountAttribute();
        $similarReports = $report->getSimilarQuery(false)->latest('id')->limit(15)->get();

        return view('admin.crash_reports.show', compact('report', 'similarCount', 'similarReports'));
    }

    public function updateStatus(Request $request, $id)
    {
        $report = AppCrashReport::findOrFail($id);

        $statusInput = strtolower(trim($request->input('status', '')));
        $statusMap = [
            'unresolved' => 'Unresolved',
            'investigating' => 'Investigating',
            'resolved' => 'Resolved',
        ];

        if (!isset($statusMap[$statusInput])) {
            return back()->with('error', 'অবৈধ স্ট্যাটাস সিলেক্ট করা হয়েছে।');
        }

        $normalizedStatus = $statusMap[$statusInput];
        $report->status = $normalizedStatus;
        $report->save();

        if ($request->boolean('apply_to_similar')) {
            $similarUpdated = $report->getSimilarQuery(false)->update(['status' => $normalizedStatus]);
            $msg = "ক্র্যাশ রিপোর্ট #{$id} সফলভাবে '{$normalizedStatus}' করা হয়েছে এবং একই ধরনের আরও {$similarUpdated} টি ক্র্যাশ আপডেট হয়েছে।";
        } else {
            $msg = "ক্র্যাশ রিপোর্ট #{$id} সফলভাবে '{$normalizedStatus}' করা হয়েছে।";
        }

        return back()->with('success', $msg);
    }

    public function resolveSimilar($id)
    {
        $report = AppCrashReport::findOrFail($id);
        $report->status = 'Resolved';
        $report->save();

        $similarUpdated = $report->getSimilarQuery(false)->update(['status' => 'Resolved']);
        $total = $similarUpdated + 1;

        return back()->with('success', "এই ক্র্যাশ এবং একই ধরনের মোট {$total} টি ক্র্যাশ রিপোর্ট সফলভাবে Resolved হিসেবে চিহ্নিত করা হয়েছে।");
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:Resolved,Investigating,Unresolved,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ids = $request->input('ids', []);
        $action = $request->input('action');

        if (empty($ids)) {
            return back()->with('error', 'কোনো ক্র্যাশ রিপোর্ট সিলেক্ট করা হয়নি।');
        }

        if ($action === 'delete') {
            $count = AppCrashReport::whereIn('id', $ids)->delete();
            return back()->with('success', "মোট {$count} টি ক্র্যাশ রিপোর্ট মুছে ফেলা হয়েছে।");
        } else {
            $count = AppCrashReport::whereIn('id', $ids)->update(['status' => $action]);
            return back()->with('success', "মোট {$count} টি ক্র্যাশ রিপোর্টের স্ট্যাটাস '{$action}' করা হয়েছে।");
        }
    }

    public function destroy($id)
    {
        $report = AppCrashReport::findOrFail($id);
        $report->delete();

        return redirect()->route('admin.crash-reports.index')->with('success', 'Crash report deleted.');
    }

    public function clearResolved()
    {
        AppCrashReport::whereRaw('LOWER(status) = ?', ['resolved'])->delete();
        return back()->with('success', 'Resolved crash reports cleared.');
    }
}
