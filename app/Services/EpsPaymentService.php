<?php

namespace App\Services;

use App\Models\PaymentNumber;
use App\Models\EpsTransaction;
use App\Models\SignUp;
use App\Models\Transaction;
use App\Models\VerificationRequest;
use App\Models\Notification;
use App\Models\Order;
use App\Traits\LegacyFCMTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EpsPaymentService
{
    use LegacyFCMTrait;

    protected string $baseUrl = 'https://pgapi.eps.com.bd';

    /**
     * Auto ensure columns and tables exist
     */
    public static function ensureSchema(): void
    {
        try {
            if (Schema::hasTable('payment_number')) {
                $cols = [
                    'eps_enabled' => "TINYINT DEFAULT 0",
                    'manual_payment_enabled' => "TINYINT DEFAULT 1",
                    'bangla_qr_enabled' => "TINYINT DEFAULT 1",
                    'eps_auto_disable_others' => "TINYINT DEFAULT 1",
                    'eps_username' => "VARCHAR(191) NULL",
                    'eps_password' => "VARCHAR(191) NULL",
                    'eps_hash_key' => "TEXT NULL",
                    'eps_store_id' => "VARCHAR(191) NULL",
                    'eps_merchant_id' => "VARCHAR(191) NULL",
                    'eps_sandbox' => "TINYINT DEFAULT 0",
                ];
                foreach ($cols as $col => $definition) {
                    if (!Schema::hasColumn('payment_number', $col)) {
                        try {
                            DB::statement("ALTER TABLE payment_number ADD COLUMN {$col} {$definition}");
                        } catch (\Throwable $e) {}
                    }
                }
            }

            if (!Schema::hasTable('eps_transactions')) {
                Schema::create('eps_transactions', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->nullable()->index();
                    $table->string('customer_order_id', 100)->unique()->index();
                    $table->string('merchant_transaction_id', 100)->unique()->index();
                    $table->string('eps_transaction_id', 100)->nullable()->index();
                    $table->decimal('amount', 12, 2)->default(0.00);
                    $table->string('purpose', 50)->default('verification');
                    $table->string('reference_id', 100)->nullable();
                    $table->string('status', 30)->default('Initiated');
                    $table->string('financial_entity', 50)->nullable();
                    $table->string('customer_name', 191)->nullable();
                    $table->string('customer_phone', 50)->nullable();
                    $table->string('customer_email', 191)->nullable();
                    $table->text('redirect_url')->nullable();
                    $table->longText('response_data')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            Log::warning('EPS ensureSchema error: ' . $e->getMessage());
        }
    }

    /**
     * Compute HMAC-SHA512 Base64 Hash
     */
    public function generateHash(string $data, string $hashKey): string
    {
        $hashBinary = hash_hmac('sha512', $data, $hashKey, true);
        return base64_encode($hashBinary);
    }

    /**
     * Get Settings
     */
    public function getSettings(): PaymentNumber
    {
        self::ensureSchema();
        return PaymentNumber::first() ?? new PaymentNumber();
    }

    /**
     * Get EPS Auth Token (API No. 01)
     */
    public function getToken(?PaymentNumber $settings = null): ?string
    {
        $settings = $settings ?? $this->getSettings();

        $userName = trim((string)$settings->eps_username);
        $password = trim((string)$settings->eps_password);
        $hashKey  = trim((string)$settings->eps_hash_key);

        if (empty($userName) || empty($password) || empty($hashKey)) {
            Log::error('EPS: Username, password, or hash key is missing in settings.');
            return null;
        }

        // Cache token for 20 minutes (token expiry is typically longer)
        $cacheKey = 'eps_auth_token_' . md5($userName . $password);
        $cachedToken = Cache::get($cacheKey);
        if ($cachedToken) {
            return $cachedToken;
        }

        try {
            $xHash = $this->generateHash($userName, $hashKey);

            $response = Http::withHeaders([
                'x-hash' => $xHash,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->timeout(15)->post("{$this->baseUrl}/v1/Auth/GetToken", [
                'userName' => $userName,
                'password' => $password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['token'] ?? null;
                if ($token) {
                    Cache::put($cacheKey, $token, now()->addMinutes(20));
                    return $token;
                }
            }

            Log::error('EPS GetToken failed: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('EPS GetToken Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Initialize Payment (API No. 02)
     */
    public function initializePayment(SignUp $user, float $amount, string $purpose = 'verification', ?string $referenceId = null): array
    {
        $settings = $this->getSettings();

        if (empty($settings->eps_enabled)) {
            return ['success' => false, 'message' => 'EPS পেমেন্ট গেটওয়ে বর্তমানে বন্ধ রয়েছে।'];
        }

        $token = $this->getToken($settings);
        if (!$token) {
            return ['success' => false, 'message' => 'EPS গেটওয়ে অথেনটিকেশন ব্যর্থ হয়েছে। ক্রেডেনশিয়াল যাচাই করুন।'];
        }

        $storeId    = trim((string)$settings->eps_store_id);
        $merchantId = trim((string)$settings->eps_merchant_id);
        $hashKey    = trim((string)$settings->eps_hash_key);

        // Generate unique Order ID & Merchant Transaction ID (min 10 digits)
        $merchantTxnId = date('YmdHis') . rand(1000, 9999);
        $customerOrderId = 'ROOTVA_' . strtoupper(substr($purpose, 0, 3)) . '_' . $user->id . '_' . time();

        $xHash = $this->generateHash($merchantTxnId, $hashKey);

        $successUrl = route('api.eps.callback', ['status' => 'success', 'order_id' => $customerOrderId]);
        $failUrl    = route('api.eps.callback', ['status' => 'failed', 'order_id' => $customerOrderId]);
        $cancelUrl  = route('api.eps.callback', ['status' => 'cancelled', 'order_id' => $customerOrderId]);

        $customerName  = !empty($user->name) ? $user->name : 'Customer';
        $customerEmail = !empty($user->email) ? $user->email : 'support@rootvabd.com';
        $customerPhone = !empty($user->number) ? $user->number : '01700000000';
        $customerAddress = !empty($user->address) ? $user->address : 'Dhaka, Bangladesh';

        $payload = [
            'merchantId'            => $merchantId ?: $storeId,
            'storeId'               => $storeId,
            'CustomerOrderId'       => $customerOrderId,
            'merchantTransactionId' => $merchantTxnId,
            'transactionTypeId'     => 2, // 2 = Android Devices, 1 = Web
            'totalAmount'           => (float) $amount,
            'ipAddress'             => request()->ip() ?: '127.0.0.1',
            'version'               => '1',
            'successUrl'            => $successUrl,
            'failUrl'               => $failUrl,
            'cancelUrl'             => $cancelUrl,
            'customerName'          => $customerName,
            'customerEmail'         => $customerEmail,
            'CustomerAddress'       => $customerAddress,
            'CustomerCity'          => 'Dhaka',
            'CustomerState'         => 'Dhaka',
            'CustomerPostcode'      => '1200',
            'CustomerCountry'       => 'BD',
            'CustomerPhone'         => $customerPhone,
            'ShipmentName'          => $customerName,
            'ShipmentAddress'       => $customerAddress,
            'ShipmentCity'          => 'Dhaka',
            'ShipmentState'         => 'Dhaka',
            'ShipmentPostcode'      => '1200',
            'ShipmentCountry'       => 'BD',
            'ValueA'                => (string) $user->id,
            'ValueB'                => $purpose,
            'ValueC'                => (string) $referenceId,
            'ValueD'                => (string) $amount,
            'shippingMethod'        => 'NO',
            'noOfItem'              => '1',
            'productName'           => 'Rootva Digital Service - ' . ucfirst($purpose),
            'productProfile'        => 'general',
            'productCategory'       => 'digital',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'x-hash'        => $xHash,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json'
            ])->timeout(20)->post("{$this->baseUrl}/v1/EPSEngine/InitializeEPS", $payload);

            $data = $response->json();

            if ($response->successful() && !empty($data['RedirectURL'])) {
                // Record EPS Transaction in database
                EpsTransaction::create([
                    'user_id'                 => $user->id,
                    'customer_order_id'       => $customerOrderId,
                    'merchant_transaction_id' => $merchantTxnId,
                    'eps_transaction_id'      => $data['TransactionId'] ?? null,
                    'amount'                  => $amount,
                    'purpose'                 => $purpose,
                    'reference_id'            => $referenceId,
                    'status'                  => 'Initiated',
                    'customer_name'           => $customerName,
                    'customer_phone'          => $customerPhone,
                    'customer_email'          => $customerEmail,
                    'redirect_url'            => $data['RedirectURL'],
                    'response_data'           => $data,
                ]);

                return [
                    'success'                 => true,
                    'redirect_url'            => $data['RedirectURL'],
                    'merchant_transaction_id' => $merchantTxnId,
                    'customer_order_id'       => $customerOrderId,
                    'eps_transaction_id'      => $data['TransactionId'] ?? null,
                ];
            }

            $errMsg = $data['ErrorMessage'] ?? ($data['message'] ?? 'পেমেন্ট গেটওয়ে ইনিশিয়ালাইজ করতে সমস্যা হয়েছে।');
            Log::error('EPS InitializeEPS failed: ' . json_encode($data));
            return ['success' => false, 'message' => $errMsg];

        } catch (\Throwable $e) {
            Log::error('EPS InitializeEPS Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'সার্ভার যোগাযোগে সমস্যা হয়েছে: ' . $e->getMessage()];
        }
    }

    /**
     * Verify Transaction with EPS Engine (API No. 03)
     */
    public function verifyTransaction(string $merchantTxnId, ?string $epsTxnId = null): ?array
    {
        $settings = $this->getSettings();
        $token = $this->getToken($settings);
        if (!$token) {
            return null;
        }

        $hashKey = trim((string)$settings->eps_hash_key);
        $xHash = $this->generateHash($merchantTxnId, $hashKey);

        try {
            $url = "{$this->baseUrl}/v1/EPSEngine/CheckMerchantTransactionStatus?merchantTransactionId={$merchantTxnId}";
            if (!empty($epsTxnId)) {
                $url .= "&EPSTransactionId={$epsTxnId}";
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'x-hash'        => $xHash,
                'Accept'        => 'application/json'
            ])->timeout(20)->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EPS Verify failed: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('EPS Verify Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Process Successful Payment Callback
     */
    public function processSuccessfulPayment(EpsTransaction $txn, array $epsData): bool
    {
        if ($txn->status === 'Success') {
            return true; // Already processed, avoid duplicate
        }

        return DB::transaction(function () use ($txn, $epsData) {
            $financialEntity = $epsData['FinancialEntity'] ?? ($epsData['financialEntity'] ?? 'EPS Gateway');
            $epsTxnId = $epsData['EpsTransactionId'] ?? ($epsData['epsTransactionId'] ?? $txn->eps_transaction_id);

            $txn->update([
                'status'           => 'Success',
                'eps_transaction_id'=> $epsTxnId,
                'financial_entity' => $financialEntity,
                'response_data'    => $epsData,
            ]);

            $user = SignUp::find($txn->user_id);
            if (!$user) {
                return true;
            }

            $currentTime = date("d-m-Y h:i A");
            $verifiedRawTime = now()->toDateTimeString();

            // 1. Verification Purpose
            if ($txn->purpose === 'verification') {
                if ($user->is_verified != 1) {
                    $user->update([
                        'is_verified'       => 1,
                        'verified_at'       => $currentTime,
                        'verified_raw_time' => $verifiedRawTime,
                        'video_earning'     => $user->video_earning + 3,
                    ]);

                    // Create approved verification request record
                    VerificationRequest::create([
                        'user_id'           => $user->id,
                        'transaction_id'    => $epsTxnId ?: $txn->merchant_transaction_id,
                        'account_number'    => $user->number,
                        'payment_gateway'   => 'EPS (' . $financialEntity . ')',
                        'amount'            => $txn->amount,
                        'status'            => 'Approved',
                        'created_at'        => $currentTime,
                        'updated_at'        => $currentTime,
                        'verified_raw_time' => $verifiedRawTime,
                    ]);

                    // Notification
                    Notification::create([
                        'user_id'    => $user->id,
                        'message'    => '🎉 অভিনন্দন! EPS পেমেন্টের মাধ্যমে আপনার Rootva অ্যাকাউন্ট সফলভাবে ভেরিফাই হয়েছে।',
                        'is_read'    => 0,
                        'created_at' => $currentTime,
                    ]);

                    if (!empty($user->fcm_token)) {
                        $this->sendFCMNotification(
                            $user->fcm_token,
                            '🎉 ভেরিফিকেশন সফল!',
                            'EPS পেমেন্টের মাধ্যমে আপনার রুটবা অ্যাকাউন্ট সফলভাবে ভেরিফাই করা হয়েছে।'
                        );
                    }

                    // Distribute Referral Bonus
                    $this->distributeReferralBonus($user, $currentTime);

                    // Referrer rewards
                    if ($user->referredBy) {
                        SignUp::where('referCode', $user->referredBy)->increment('math_game', 4);
                        SignUp::where('referCode', $user->referredBy)->increment('video_earning', 3);
                    }

                    // Log Income Transaction
                    Transaction::create([
                        'user_id'         => $user->id,
                        'amount'          => $txn->amount,
                        'type'            => 'verification_fee',
                        'payment_gateway' => 'EPS - ' . $financialEntity,
                        'description'     => 'Verification fee paid online via EPS Gateway',
                        'update_at'       => $currentTime,
                        'created_at'      => $verifiedRawTime,
                    ]);
                }
            }

            // 2. Add Balance Purpose
            elseif ($txn->purpose === 'add_balance') {
                $user->increment('wallet_balance', $txn->amount);

                Transaction::create([
                    'user_id'         => $user->id,
                    'amount'          => $txn->amount,
                    'type'            => 'income',
                    'payment_gateway' => 'EPS - ' . $financialEntity,
                    'description'     => 'Deposit added via EPS Gateway (' . $financialEntity . ')',
                    'update_at'       => $currentTime,
                    'created_at'      => $verifiedRawTime,
                ]);

                Notification::create([
                    'user_id'    => $user->id,
                    'message'    => '৳' . number_format($txn->amount, 2) . ' আপনার ওয়ালেটে সফলভাবে যুক্ত হয়েছে (EPS Gateway)।',
                    'is_read'    => 0,
                    'created_at' => $currentTime,
                ]);
            }

            // 3. Reselling Order Payment
            elseif ($txn->purpose === 'order') {
                if ($txn->reference_id) {
                    $order = Order::find($txn->reference_id);
                    if ($order) {
                        $order->update([
                            'order_status'    => 'Confirmed',
                            'payment_gateway' => 'EPS (' . $financialEntity . ')',
                            'transaction_id'  => $epsTxnId ?: $txn->merchant_transaction_id,
                        ]);
                    }
                }
            }

            return true;
        });
    }

    /**
     * Distribute referral bonus across 5 levels (Same logic as VerificationRequestController)
     */
    private function distributeReferralBonus(SignUp $user, string $currentTime): void
    {
        $currentReferrerCode = $user->referredBy;
        $bonusPercentages = [
            1 => 0.50, // 50%
            2 => 0.05, // 5%
            3 => 0.05, // 5%
            4 => 0.05, // 5%
            5 => 0.05, // 5%
        ];

        for ($level = 1; $level <= 5; $level++) {
            if (!$currentReferrerCode) break;

            $referrer = SignUp::where('referCode', $currentReferrerCode)->first();
            if (!$referrer) break;

            $bonusAmount = 250 * $bonusPercentages[$level];

            Transaction::create([
                'user_id'         => $referrer->id,
                'refer_id'        => $user->referCode,
                'amount'          => $bonusAmount,
                'type'            => 'bonus',
                'payment_gateway' => 'system',
                'description'     => "Level {$level} referral bonus from {$user->name}",
                'update_at'       => $currentTime,
                'created_at'      => now()->toDateTimeString(),
            ]);

            $referrer->increment('wallet_balance', $bonusAmount);
            $currentReferrerCode = $referrer->referredBy;
        }
    }
}
