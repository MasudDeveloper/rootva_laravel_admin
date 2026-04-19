<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VerificationRequest;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class VerificationRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'Pending');
        
        $requests = VerificationRequest::select('verification_requests.*', 'sign_up.name', 'sign_up.number', 'sign_up.referCode')
            ->join('sign_up', 'verification_requests.user_id', '=', 'sign_up.id')
            ->when($status, function($q) use ($status) {
                return $q->where('verification_requests.status', $status);
            })
            ->orderBy('verification_requests.id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.verification_requests.index', compact('requests', 'status'));
    }

    public function approve(Request $request, $id)
    {
        $verificationRequest = VerificationRequest::findOrFail($id);
        
        if ($verificationRequest->status !== 'Pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        DB::transaction(function () use ($verificationRequest) {
            $user = SignUp::findOrFail($verificationRequest->user_id);
            
            // Update User status to verified
            $user->update([
                'is_verified' => 1,
                'verified_at' => now()->toDateTimeString(),
                'verified_raw_time' => now()->timestamp
            ]);

            // Update Request status
            $verificationRequest->update([
                'status' => 'Approved',
                'updated_at' => now()->toDateTimeString()
            ]);

            // Optional: Log Transaction for verification fee? 
            // In the legacy DB, amount is present in verification_requests.
            Transaction::create([
                'user_id' => $user->id,
                'refer_id' => $user->referCode,
                'amount' => $verificationRequest->amount,
                'type' => 'verification_fee',
                'payment_gateway' => $verificationRequest->payment_gateway,
                'description' => 'Account Verification Success',
                'update_at' => now()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
            ]);
        });

        return back()->with('success', 'User has been verified successfully.');
    }

    public function reject(Request $request, $id)
    {
        $verificationRequest = VerificationRequest::findOrFail($id);

        if ($verificationRequest->status !== 'Pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $verificationRequest->update([
            'status' => 'Rejected',
            'updated_at' => now()->toDateTimeString()
        ]);

        return back()->with('success', 'Verification request has been rejected.');
    }
}
