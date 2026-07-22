<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VerificationRequest;
use App\Models\IncomingPaymentSms;
use App\Models\SignUp;
use App\Models\Notification;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Throwable;

class ReconcilePayments extends Command
{
    protected $signature = 'pcash:reconcile-payments';
    protected $description = 'Reconcile pending verification requests with incoming SMS payments';

    public function handle()
    {
        $BATCH_LIMIT = 200;
        $MIN_AUTO_APPROVE_BDT = 250.00;
        $AMT_TOLERANCE = 0.50;
        
        $now = Carbon::now('Asia/Dhaka');
        $timeNowHum = $now->format('d-m-Y h:i A');
        $timeNowRaw = $now->format('Y-m-d H:i:s');

        $requests = VerificationRequest::where('status', 'Pending')
            ->orderBy('id', 'asc')
            ->limit($BATCH_LIMIT)
            ->get();

        $checked = 0;
        $matched = 0;

        foreach ($requests as $req) {
            $checked++;
            $gw = $this->normGateway($req->payment_gateway);
            $tx = trim($req->transaction_id ?? '');
            $acct = $this->normPhone($req->account_number ?? '');
            $amt = (float) $req->amount;

            $sms = null;

            // ---------- try 1: exact by transaction_id + gateway ----------
            if ($tx !== '' && $gw !== '') {
                $sms = IncomingPaymentSms::where('gateway', $gw)
                    ->where('transaction_id', $tx)
                    ->whereNull('matched_request_id')
                    ->first();
            }

            // ---------- try 2: by number + amount (fallback) ----------
            if (!$sms && $acct && $gw !== '') {
                $like = "%" . $acct . "%";
                $sms = IncomingPaymentSms::where('gateway', $gw)
                    ->whereNull('matched_request_id')
                    ->whereRaw("ABS(amount - ?) <= ?", [$amt, $AMT_TOLERANCE])
                    ->where(function($query) use ($like) {
                        $query->where('account_number', 'LIKE', $like)
                              ->orWhere('raw_text', 'LIKE', $like);
                    })
                    ->orderBy('id', 'desc')
                    ->first();
            }

            // ---------- approve if matched ----------
            if ($sms) {
                $smsAmt = (float) $sms->amount;
                $reqAmt = (float) $req->amount;

                if ($smsAmt + 1e-9 < $MIN_AUTO_APPROVE_BDT || $reqAmt + 1e-9 < $MIN_AUTO_APPROVE_BDT) {
                    if (is_null($sms->processed) || $sms->processed === 'Skipped') {
                        $sms->processed = 'UnderThreshold';
                        $sms->save();
                    }
                    continue;
                }

                if ($this->approveRequest($req, $sms, $timeNowHum, $timeNowRaw)) {
                    $matched++;
                }
            }
        }

        $this->info("Checked: {$checked}");
        $this->info("Matched & Approved: {$matched}");
        $this->info("Done at {$timeNowRaw}");
    }

    private function normGateway($g)
    {
        if (!$g) return '';
        $s = trim(mb_strtolower($g, 'UTF-8'));
        $s = str_replace([' ', '-', '–', '—'], '', $s);

        $bkash = ['bkash', 'বিকাশ', 'বিক্যাশ', 'বিকাস', 'বি-ক্যাশ', 'বি-কাস'];
        if (in_array($s, $bkash, true)) return 'bkash';

        $nagad = ['nagad', 'নগদ'];
        if (in_array($s, $nagad, true)) return 'nagad';

        $rocket = ['rocket', 'রকেট', 'dbbl', 'ডিবিবিএল', 'ডাচবাংলা', 'ডাচ-বাংলা', 'ডাচবাংলাব্যাংক', 'dutchbangla', '16216'];
        if (in_array($s, $rocket, true)) return 'rocket';

        return $s;
    }

    private function normPhone($n)
    {
        if (!$n) return null;
        $d = preg_replace('/\D+/', '', $n);
        if (strlen($d) === 13 && substr($d, 0, 3) === '880') $d = substr($d, 2);
        if (strlen($d) === 14 && substr($d, 0, 4) === '0088') $d = substr($d, 4);
        if (strlen($d) > 11) $d = substr($d, -11);
        return (strlen($d) === 11 && substr($d, 0, 2) === '01') ? $d : null;
    }

    private function approveRequest($req, $sms, $timeNowHum, $timeNowRaw)
    {
        if (!empty($sms->matched_request_id)) return false;

        DB::beginTransaction();
        try {
            $userId = (int) $req->user_id;
            $userRefer = $req->refer_id;

            // 1. Lock Verification Request
            $reqUpdated = VerificationRequest::where('id', $req->id)
                ->where('status', 'Pending')
                ->update([
                    'status' => 'Approved',
                    'updated_at' => $timeNowHum,
                    'verified_raw_time' => $timeNowRaw
                ]);

            if ($reqUpdated === 0) {
                DB::rollBack();
                return false;
            }

            // 2. Lock SMS
            $smsUpdated = IncomingPaymentSms::where('id', $sms->id)
                ->whereNull('matched_request_id')
                ->update([
                    'processed' => 'Matched',
                    'matched_request_id' => $req->id
                ]);

            if ($smsUpdated === 0) {
                DB::rollBack();
                return false;
            }

            // 3. Process the rest safely
            SignUp::where('id', $userId)->update([
                'is_verified' => 1,
                'verified_at' => $timeNowHum,
                'verified_raw_time' => $timeNowRaw
            ]);

            Notification::insert([
                'user_id' => $userId,
                'message' => 'আপনার ভেরিফিকেশন সফল হয়েছে',
                'created_at' => $timeNowHum
            ]);

            $ref = SignUp::where('id', $userId)->select('referCode', 'referredBy')->first();
            if ($ref) {
                $this->distributeReferralBonus($userRefer, $ref->referredBy, $timeNowHum);
            }

            DB::commit();
            return true;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error("reconcile error: " . $e->getMessage());
            return false;
        }
    }

    private function distributeReferralBonus($userRefer, $referredBy, $currentTime)
    {
        $levels = [76, 35, 15, 10, 6, 5, 4, 3, 2, 2];
        $currentLevel = 1;

        while ($currentLevel <= count($levels)) {
            if (!$referredBy) break;

            $upline = SignUp::where('referCode', $referredBy)->first();

            if ($upline) {
                $uplineUserId = $upline->id;
                $uplineReferCode = $upline->referCode;
                $uplineReferredBy = $upline->referredBy;

                $bonus = $levels[$currentLevel - 1];

                SignUp::where('referCode', $uplineReferCode)->increment('wallet_balance', $bonus);

                $description = "লেভেল {$currentLevel} এফিলিয়েট বোনাস যুক্ত হয়েছে";
                
                Transaction::insert([
                    'user_id' => $uplineUserId,
                    'refer_id' => $userRefer,
                    'amount' => $bonus,
                    'type' => 'commission',
                    'payment_gateway' => 'Account Verification',
                    'description' => $description,
                    'update_at' => $currentTime,
                    'created_at' => $currentTime,
                    'date' => Carbon::now('Asia/Dhaka')->format('Y-m-d H:i:s')
                ]);

                if ($currentLevel === 1) {
                    SignUp::where('referCode', $uplineReferCode)->increment('math_game', 4);
                }

                $referredBy = $uplineReferredBy;
                $currentLevel++;
            } else {
                break;
            }
        }
    }
}
