<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MicrojobSubmission;
use App\Models\Transaction;
use App\Models\SignUp; // Available if user uncomment wallet update
use Carbon\Carbon;

class MicrojobAutoApprove extends Command
{
    protected $signature = 'microjob:auto-approve';
    protected $description = 'Auto approve microjob submissions older than 60 minutes';

    public function handle()
    {
        $now = Carbon::now('Asia/Dhaka');
        $currentTime = $now->format('d-m-Y h:i A');
        $nowRaw = $now->format('Y-m-d H:i:s');

        // Fetch submissions pending for > 60 mins
        $submissions = MicrojobSubmission::with('job')
            ->where('status', 'pending')
            ->where('created_at', '<=', $now->copy()->subMinutes(60))
            ->limit(500)
            ->get();

        if ($submissions->isEmpty()) {
            $this->info("No 1-hour old pending submissions found for auto-approval.");
            return;
        }

        foreach ($submissions as $submission) {
            $job = $submission->job;
            $userId = $submission->worker_user_id;
            
            // If job relation is missing, fallback or skip
            if (!$job) {
                continue;
            }

            $amount = (float) $job->amount_per_worker;
            $title = $job->title;

            // 1) Update submission status
            $submission->update([
                'status' => 'approved',
                'reject_reason' => null
            ]);

            // 2) Reduce remaining target (commented out in legacy code)
            // \App\Models\Microjob::where('id', $job->id)->update([
            //     'remaining_target' => DB::raw('GREATEST(remaining_target - 1, 0)')
            // ]);

            // 3) Add transaction for worker
            $description = "Auto-approved microjob earn: {$title}";
            
            Transaction::insert([
                'user_id' => $userId,
                'amount' => $amount,
                'type' => 'income',
                'payment_gateway' => 'Microjob',
                'description' => $description,
                'update_at' => $currentTime,
                'created_at' => $currentTime,
                'date' => $nowRaw
            ]);

            // 4) Update wallet balance (commented out in legacy code as DB triggers handle it)
            // SignUp::where('id', $userId)->increment('wallet_balance', $amount);

            $this->info("Auto-approved submission: ID = {$submission->id} | Job = {$job->id} | User = {$userId}");
        }

        $this->info("Done approving auto microjobs at {$nowRaw}");
    }
}
