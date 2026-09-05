<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SmmSubmission;
use App\Models\SignUp;
use App\Models\Transaction;
use App\Traits\LegacyFCMTrait;
use Illuminate\Support\Facades\DB;

class SmmSubmissionController extends Controller
{
    use LegacyFCMTrait;
    /**
     * Display list of SMM Submissions for verification.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $taskType = $request->input('task_type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        $query = SmmSubmission::with('user')->where('status', $status);

        if (!empty($taskType)) {
            $query->where('task_type', $taskType);
        }

        if (!empty($startDate)) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        if (!empty($search)) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('referCode', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->input('export') === 'csv') {
            $submissions = $query->orderBy('id', 'desc')->get();
            $filename = "smm_submissions_" . $status . "_" . date('Ymd_His') . ".csv";
            
            $headers = [
                "Content-type"        => "text/csv; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $callback = function() use($submissions, $status) {
                $file = fopen('php://output', 'w');
                // UTF-8 BOM
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                $columns = [
                    'Submission ID', 'User Name', 'User Phone', 'User Refer Code', 
                    'Referred By', 'Task Type', 'Field 1 (Credentials/Account)', 
                    'Field 2 (Password/Secret)', 'Field 3', 'Field 4', 
                    'Payout Rate (TK)', 'Submission Status', 'Submitted At'
                ];
                
                if ($status === 'rejected') {
                    $columns[] = 'Rejection Feedback';
                }

                fputcsv($file, $columns);

                foreach ($submissions as $sub) {
                    $row = [
                        $sub->id,
                        $sub->user->name ?? 'Unknown',
                        $sub->user->number ?? 'N/A',
                        $sub->user->referCode ?? 'N/A',
                        $sub->user->referredBy ?? 'System',
                        strtoupper($sub->task_type),
                        $sub->input_field_1,
                        $sub->input_field_2,
                        $sub->input_field_3,
                        $sub->input_field_4,
                        $sub->price,
                        strtoupper($sub->status),
                        $sub->created_at ? $sub->created_at->format('Y-m-d H:i:s') : 'N/A'
                    ];

                    if ($status === 'rejected') {
                        $row[] = $sub->admin_feedback ?: 'No reason specified';
                    }

                    fputcsv($file, $row);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        $submissions = $query->orderBy('id', 'desc')->paginate(50)->withQueryString();

        return view('admin.smm.index', compact('submissions', 'status'));
    }

    /**
     * Approve SMM Task and distribute payment.
     */
    public function approve($id)
    {
        $submission = SmmSubmission::findOrFail($id);

        if ($submission->status !== 'pending') {
            return back()->with('error', 'This submission has already been processed.');
        }

        DB::transaction(function () use ($submission) {
            $user = SignUp::findOrFail($submission->user_id);

            // 1. Update Submission status
            $submission->update(['status' => 'approved']);

            // 2. Increment User Wallet Balance
            $user->increment('wallet_balance', $submission->price);

            // 3. Log Transaction record
            Transaction::create([
                'user_id' => $user->id,
                'refer_id' => $user->referCode,
                'amount' => $submission->price,
                'type' => 'income',
                'payment_gateway' => 'SMM Selling',
                'description' => "Approved SMM Task: " . strtoupper($submission->task_type) . " Sell (" . $submission->input_field_1 . ")",
                'update_at' => now()->format('d-m-Y, h:i A'),
                'created_at' => now()->toDateTimeString(),
            ]);

            // Send FCM Push Notification to User
            if (!empty($user->fcm_token)) {
                $this->sendFCMNotification(
                    $user->fcm_token,
                    '🎉 টাস্ক অনুমোদিত হয়েছে!',
                    'আপনার ' . strtoupper($submission->task_type) . ' কাজ অনুমোদন করা হয়েছে এবং ৳' . number_format((double)$submission->price, 2) . ' ওয়ালেটে যোগ করা হয়েছে।'
                );
            }

            // 4. Distribute 5-Level Referral Commission (10% of task price split equally among 5 levels)
            $taskPrice = (double)$submission->price;
            $perLevelCommission = ($taskPrice * 0.10) / 5;

            if ($perLevelCommission > 0 && !empty($user->referredBy)) {
                $currentReferredBy = trim($user->referredBy);

                for ($level = 1; $level <= 5; $level++) {
                    if (empty($currentReferredBy)) {
                        break;
                    }

                    $upline = SignUp::where('referCode', $currentReferredBy)->first();
                    if (!$upline) {
                        break;
                    }

                    $upline->increment('wallet_balance', $perLevelCommission);

                    Transaction::create([
                        'user_id' => $upline->id,
                        'refer_id' => $upline->referCode,
                        'amount' => $perLevelCommission,
                        'type' => 'income',
                        'payment_gateway' => 'SMM Referral Bonus',
                        'description' => "SMM Referral Commission (Lvl {$level}) from " . $user->name,
                        'update_at' => now()->format('d-m-Y, h:i A'),
                        'created_at' => now()->toDateTimeString(),
                    ]);

                    // Send FCM Push Notification to Upline Referrer
                    if (!empty($upline->fcm_token)) {
                        $this->sendFCMNotification(
                            $upline->fcm_token,
                            '🎁 রেফার কমিশন পেয়েছেন!',
                            'আপনার রেফারেল লেভেল ' . $level . ' এর সদস্যের কাজ অনুমোদন হওয়ায় আপনি ৳' . number_format($perLevelCommission, 2) . ' কমিশন পেয়েছেন।'
                        );
                    }

                    $currentReferredBy = trim($upline->referredBy ?? '');
                }
            }
        });

        return back()->with('success', 'Submission approved and payment processed.');
    }

    /**
     * Reject SMM Task.
     */
    public function reject(Request $request, $id)
    {
        $submission = SmmSubmission::findOrFail($id);

        if ($submission->status !== 'pending') {
            return back()->with('error', 'This submission has already been processed.');
        }

        $feedback = $request->input('feedback', 'Rejected by admin due to incorrect details');
        $submission->update([
            'status' => 'rejected',
            'admin_feedback' => $feedback
        ]);

        return back()->with('success', 'Submission rejected.');
    }
}
