<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EpsTransaction;
use App\Models\SignUp;
use App\Services\EpsPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EpsApiController extends Controller
{
    protected EpsPaymentService $epsService;

    public function __construct(EpsPaymentService $epsService)
    {
        $this->epsService = $epsService;
    }

    /**
     * Initiate EPS Payment (API endpoint for mobile app)
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'amount'  => 'nullable|numeric|min:1',
            'purpose' => 'nullable|string|in:verification,add_balance,order',
        ]);

        $user = SignUp::find($request->user_id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $purpose = $request->input('purpose', 'verification');
        $settings = $this->epsService->getSettings();

        $amount = (float) $request->input('amount');
        if ($amount <= 0) {
            if ($purpose === 'verification') {
                $amount = (float) ($settings->verify_amount ?: 250);
            } else {
                $amount = 100;
            }
        }

        $referenceId = $request->input('reference_id');
        $result = $this->epsService->initializePayment($user, $amount, $purpose, $referenceId);

        return response()->json($result);
    }

    /**
     * Direct Checkout Web URL (opens inside WebViewActivity or browser directly)
     */
    public function directCheckout(Request $request)
    {
        $userId = $request->query('user_id');
        $user = $userId ? SignUp::find($userId) : null;
        if (!$user) {
            return view('admin.eps.result', [
                'status'  => 'failed',
                'title'   => 'ইউজার পাওয়া যায়নি',
                'message' => 'ইউজার অ্যাকাউন্ট শনাক্ত করা যায়নি (User ID: ' . ($userId ?: 'Empty') . ')। অনুগ্রহ করে অ্যাপে ফিরে যান।',
                'txn'     => null,
            ]);
        }

        $purpose = $request->query('purpose', 'verification');
        $settings = $this->epsService->getSettings();

        $amount = (float) $request->query('amount');
        if ($amount <= 0) {
            if ($purpose === 'verification') {
                $amount = (float) ($settings->verify_amount ?: 250);
            } else {
                $amount = 100;
            }
        }

        $referenceId = $request->query('reference_id');
        $result = $this->epsService->initializePayment($user, $amount, $purpose, $referenceId);

        if (!empty($result['success']) && !empty($result['redirect_url'])) {
            return redirect()->away($result['redirect_url']);
        }

        $errMsg = $result['message'] ?? 'পেমেন্ট গেটওয়ে চালু করা সম্ভব হয়নি।';
        return view('admin.eps.result', [
            'status'  => 'failed',
            'title'   => 'পেমেন্ট ত্রুটি',
            'message' => $errMsg,
            'txn'     => null,
        ]);
    }

    /**
     * Payment Callback Handler from EPS Engine
     */
    public function callback(Request $request)
    {
        Log::info("EPS Callback received. Query: " . json_encode($request->all()));

        $orderId = $request->input('order_id')
            ?? $request->input('orderId')
            ?? $request->input('CustomerOrderId')
            ?? $request->input('customerOrderId')
            ?? $request->query('order_id')
            ?? $request->query('orderId')
            ?? $request->query('CustomerOrderId');

        $merchantTxnId = $request->input('merchantTransactionId')
            ?? $request->input('MerchantTransactionId')
            ?? $request->input('merchant_transaction_id')
            ?? $request->query('merchantTransactionId')
            ?? $request->query('MerchantTransactionId')
            ?? $request->query('merchant_transaction_id');

        $epsTxnId = $request->input('EPSTransactionId')
            ?? $request->input('EpsTransactionId')
            ?? $request->input('epsTransactionId')
            ?? $request->input('TransactionId')
            ?? $request->input('transactionId')
            ?? $request->query('EPSTransactionId')
            ?? $request->query('EpsTransactionId')
            ?? $request->query('epsTransactionId')
            ?? $request->query('TransactionId')
            ?? $request->query('transactionId');

        $statusParam = strtolower(trim((string)(
            $request->input('status')
            ?? $request->input('Status')
            ?? $request->input('TransactionStatus')
            ?? $request->query('status')
            ?? $request->query('Status')
            ?? 'success'
        )));

        // Find transaction by merchant_transaction_id, order_id, or eps_transaction_id
        $txn = null;
        if ($merchantTxnId) {
            $txn = EpsTransaction::where('merchant_transaction_id', $merchantTxnId)->first();
        }
        if (!$txn && $orderId) {
            $txn = EpsTransaction::where('customer_order_id', $orderId)->first();
        }
        if (!$txn && $epsTxnId) {
            $txn = EpsTransaction::where('eps_transaction_id', $epsTxnId)->first();
        }

        if (!$txn) {
            Log::error("EPS Callback: Transaction not found. Order: {$orderId}, MerchantTxn: {$merchantTxnId}, EPSTxn: {$epsTxnId}");
            return view('admin.eps.result', [
                'status'  => 'failed',
                'title'   => 'লেনদেন পাওয়া যায়নি',
                'message' => 'পেমেন্ট রেকর্ড শনাক্ত করা যায়নি। অনুগ্রহ করে আপনার অ্যাপে ফিরে যান।',
                'txn'     => null,
            ]);
        }

        // If user cancelled or failed from callback query
        if (in_array($statusParam, ['cancelled', 'cancel'])) {
            $txn->update(['status' => 'Cancelled']);
            return view('admin.eps.result', [
                'status'  => 'cancelled',
                'title'   => 'পেমেন্ট বাতিল হয়েছে',
                'message' => 'আপনি পেমেন্ট প্রক্রিয়াটি বাতিল করেছেন।',
                'txn'     => $txn,
            ]);
        }

        if (in_array($statusParam, ['failed', 'fail'])) {
            $txn->update(['status' => 'Failed']);
            return view('admin.eps.result', [
                'status'  => 'failed',
                'title'   => 'পেমেন্ট ব্যর্থ হয়েছে',
                'message' => 'পেমেন্টটি সম্পন্ন হতে পারেনি। অনুগ্রহ করে পুনরায় চেষ্টা করুন।',
                'txn'     => $txn,
            ]);
        }

        // Verify with EPS API 3
        $epsData = null;
        if ($txn->merchant_transaction_id) {
            $epsData = $this->epsService->verifyTransaction($txn->merchant_transaction_id, $epsTxnId);
        }

        $verifiedStatus = strtolower(trim((string)(
            $epsData['Status']
            ?? $epsData['status']
            ?? $epsData['TransactionStatus']
            ?? $epsData['transactionStatus']
            ?? ''
        )));

        $isVerifiedSuccess = in_array($verifiedStatus, ['success', 'successful', 'completed', 'paid', 'approved']);

        // Fallback: If EPS API 3 verify was unreachable/timed out but callback confirmed success with EPSTransactionId
        if (!$isVerifiedSuccess && empty($epsData) && in_array($statusParam, ['success', 'successful']) && !empty($epsTxnId)) {
            Log::warning("EPS: API 3 verify unavailable, falling back to callback success status. MerchantTxn: {$txn->merchant_transaction_id}, EPSTxn: {$epsTxnId}");
            $isVerifiedSuccess = true;
            $epsData = [
                'Status'             => 'Success',
                'EpsTransactionId'   => $epsTxnId,
                'FinancialEntity'    => $request->input('FinancialEntity') ?? $request->query('FinancialEntity') ?? 'EPS Gateway',
                'FallbackVerified'   => true
            ];
        }

        if ($isVerifiedSuccess) {
            try {
                $this->epsService->processSuccessfulPayment($txn, $epsData ?? []);
            } catch (\Throwable $e) {
                Log::error("EPS processSuccessfulPayment Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            }

            $successMsg = 'আপনার ৳' . number_format($txn->amount, 2) . ' পেমেন্ট সফলভাবে সম্পন্ন হয়েছে!';
            if ($txn->purpose === 'verification') {
                $successMsg = '🎉 অভিনন্দন! আপনার Rootva অ্যাকাউন্ট সফলভাবে ভেরিফাই করা হয়েছে।';
            } elseif ($txn->purpose === 'add_balance') {
                $successMsg = '৳' . number_format($txn->amount, 2) . ' আপনার ভাউচার ব্যালেন্সে সফলভাবে জমা হয়েছে।';
            }

            return view('admin.eps.result', [
                'status'  => 'success',
                'title'   => 'পেমেন্ট সফল!',
                'message' => $successMsg,
                'txn'     => $txn,
            ]);
        } else {
            $errMsg = $epsData['ErrorMessage']
                ?? $epsData['errorMessage']
                ?? 'পেমেন্ট ভেরিফিকেশন নিশ্চিত করা যায়নি।';
            $txn->update(['status' => 'Failed', 'response_data' => $epsData]);

            return view('admin.eps.result', [
                'status'  => 'failed',
                'title'   => 'পেমেন্ট নিশ্চিতকরণ ব্যর্থ',
                'message' => $errMsg,
                'txn'     => $txn,
            ]);
        }
    }

    /**
     * Check Transaction Status via API
     */
    public function checkStatus($merchantTransactionId)
    {
        $txn = EpsTransaction::where('merchant_transaction_id', $merchantTransactionId)->first();
        if (!$txn) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        return response()->json([
            'success' => true,
            'status'  => $txn->status,
            'amount'  => $txn->amount,
            'purpose' => $txn->purpose,
            'eps_transaction_id' => $txn->eps_transaction_id,
        ]);
    }
}
