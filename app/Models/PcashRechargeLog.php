<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PcashRechargeLog extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(SignUp::class, 'user_id');
    }

    public static function checkAndUpdatePendingLogs($userId = null)
    {
        $settings = PcashSetting::first();
        if (!$settings || !$settings->api_user || !$settings->api_key) {
            return;
        }

        $query = self::whereIn('api_status', ['pending', 'processing']);
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            // Only check logs from the last 48 hours for general/cron run
            $query->where('created_at', '>=', now()->subDays(2));
        }

        $pendingLogs = $query->get();

        foreach ($pendingLogs as $log) {
            try {
                $statusHeaders = [
                    'band-key: flexisoftwarebd',
                    'refer: rootvaadmin.rootvabd.com'
                ];
                $statusPostData = [
                    'user' => $settings->api_user,
                    'key' => $settings->api_key,
                    'id' => $log->api_id
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://pcashmoney.click/sendapi/status');
                curl_setopt($ch, CURLOPT_HTTPHEADER, $statusHeaders);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $statusPostData);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_REFERER, 'rootvaadmin.rootvabd.com');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

                $response = curl_exec($ch);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error) {
                    \Illuminate\Support\Facades\Log::error("PCash Pending Log status check cURL error for Log ID {$log->id}: " . $error);
                    continue;
                }

                $statusData = json_decode($response, true);
                if (is_array($statusData)) {
                    $cleanData = [];
                    foreach ($statusData as $k => $v) {
                        $cleanData[trim($k)] = $v;
                    }

                    $resStatus = isset($cleanData['status']) ? (string)$cleanData['status'] : '';

                    if ($resStatus === '1') {
                        // Mark as success
                        $log->update([
                            'api_status' => 'success',
                            'api_message' => 'Recharge successful. TrxID: ' . ($cleanData['trxid'] ?? '')
                        ]);

                        // Distribute commission
                        self::giveRechargeCommission($log->user_id, $log->amount, $log->api_id);

                        // Insert success notification
                        \Illuminate\Support\Facades\DB::table('notifications')->insert([
                            'user_id' => $log->user_id,
                            'message' => "আপনার {$log->number} নম্বরে ৳{$log->amount} রিচার্জ সফল হয়েছে।",
                            'is_read' => 0,
                            'created_at' => now()
                        ]);
                    } elseif ($resStatus === '2') {
                        // Mark as failed and process Refund
                        $user = SignUp::find($log->user_id);
                        if ($user) {
                            $user->voucher_balance += $log->amount;
                            $user->save();

                            // Determine if this was a SIM Offer or regular recharge refund
                            $gatewayName = 'Recharge Refund';
                            $description = 'Failed Recharge Refund for ' . $log->number;

                            $origTrx = Transaction::where('user_id', $log->user_id)
                                ->where('type', 'voucher_payment')
                                ->where('description', 'like', '%' . $log->number . '%')
                                ->where('created_at', '>=', $log->created_at->subMinutes(15))
                                ->first();

                            if ($origTrx && (str_contains($origTrx->description, 'SIM Offer') || str_contains($origTrx->payment_gateway, 'SIM Offer'))) {
                                $gatewayName = 'SIM Offer Refund';
                                $description = 'Failed SIM Offer Refund: ' . ($origTrx->description ? str_replace('Purchased SIM Offer: ', '', $origTrx->description) : 'SIM Offer') . ' for ' . $log->number;
                            }

                            // Create refund transaction log
                            Transaction::create([
                                'user_id' => $user->id,
                                'refer_id' => $user->referCode,
                                'amount' => $log->amount,
                                'type' => 'voucher_convert',
                                'payment_gateway' => $gatewayName,
                                'description' => $description,
                                'update_at' => date("d-m-Y h:i A"),
                                'created_at' => date("d-m-Y h:i A"),
                                'date' => now()
                            ]);

                            // Insert refund notification
                            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                                'user_id' => $log->user_id,
                                'message' => "আপনার {$log->number} নম্বরে ৳{$log->amount} রিচার্জ ব্যর্থ হয়েছে এবং টাকা ফেরত দেওয়া হয়েছে।",
                                'is_read' => 0,
                                'created_at' => now()
                            ]);
                        }

                        $log->update([
                            'api_status' => 'failed',
                            'api_message' => $cleanData['message'] ?? 'Recharge failed from gateway'
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("PCash checkAndUpdatePendingLogs Exception for Log ID {$log->id}: " . $e->getMessage());
            }
        }
    }

    private static function giveRechargeCommission($userId, $amount, $tranId)
    {
        $user = SignUp::find($userId);
        if (!$user) return;

        $now = now()->toDateTimeString();
        $currentTime = now()->format("d-m-Y h:i A");

        // Determine if this was a SIM Offer
        $log = self::where('api_id', $tranId)->first();
        $paymentGateway = 'Recharge Commission';
        $teamGateway = 'Recharge Team Commission';

        if ($log) {
            $origTrx = Transaction::where('user_id', $userId)
                ->where('type', 'voucher_payment')
                ->where('description', 'like', '%' . $log->number . '%')
                ->where('created_at', '>=', $log->created_at->subMinutes(15))
                ->first();
            if ($origTrx && (str_contains($origTrx->description, 'SIM Offer') || str_contains($origTrx->payment_gateway, 'SIM Offer'))) {
                $paymentGateway = 'SIM Offer Commission';
                $teamGateway = 'SIM Offer Team Commission';
            }
        }

        // 1. Self Commission (1.5%)
        $selfComm = round($amount * 0.015, 2);
        if ($selfComm > 0) {
            $user->increment('wallet_balance', $selfComm);
            $numberStr = $log ? " ({$log->number})" : "";
            $desc = $paymentGateway === 'SIM Offer Commission'
                ? "Commission from SIM offer" . $numberStr
                : "Commission from mobile recharge" . $numberStr;

            Transaction::create([
                'user_id' => $userId,
                'refer_id' => $user->referCode,
                'amount' => $selfComm,
                'type' => 'commission',
                'payment_gateway' => $paymentGateway,
                'description' => $desc,
                'update_at' => $currentTime,
                'created_at' => $now,
                'date' => $now
            ]);
        }

        // 2. Upline Commission (2 levels, 0.05% each)
        $uplineComm = round($amount * 0.0005, 2);
        if ($uplineComm <= 0) return;

        $currentUplineReferCode = $user->referredBy;
        for ($i = 1; $i <= 2; $i++) {
            if (empty($currentUplineReferCode) || $currentUplineReferCode == "0") break;

            $upline = SignUp::where('referCode', $currentUplineReferCode)->first();
            if (!$upline) break;

            $upline->increment('wallet_balance', $uplineComm);
            Transaction::create([
                'user_id' => $upline->id,
                'refer_id' => $user->referCode,
                'amount' => $uplineComm,
                'type' => 'commission',
                'payment_gateway' => $teamGateway,
                'description' => "Commission from {$user->name} (Level {$i})",
                'update_at' => $currentTime,
                'created_at' => $now,
                'date' => $now
            ]);

            $currentUplineReferCode = $upline->referredBy;
        }
    }
}
