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
        $search = $request->input('search');

        $requests = VerificationRequest::select('verification_requests.*', 'sign_up.name', 'sign_up.number', 'sign_up.referCode')
            ->join('sign_up', 'verification_requests.user_id', '=', 'sign_up.id')
            ->when($status, function ($q) use ($status) {
                return $q->where('verification_requests.status', $status);
            })
            ->when($search, function ($q) use ($search) {
                return $q->where(function ($query) use ($search) {
                    $query->where('verification_requests.transaction_id', 'like', "%{$search}%")
                        ->orWhere('sign_up.referCode', 'like', "%{$search}%")
                        ->orWhere('sign_up.id', $search);
                });
            })
            ->orderBy('verification_requests.id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.verification_requests.index', compact('requests', 'status', 'search'));
    }

    public function approve(Request $request, $id)
    {
        $verificationRequest = VerificationRequest::findOrFail($id);

        if ($verificationRequest->status !== 'Pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        DB::transaction(function () use ($verificationRequest) {
            $user = SignUp::findOrFail($verificationRequest->user_id);
            $current_time = date("d-m-Y h:i A");
            $verified_raw_time = now()->toDateTimeString();

            // 1. Update User status to verified
            $user->update([
                'is_verified' => 1,
                'verified_at' => $current_time,
                'verified_raw_time' => $verified_raw_time
            ]);

            // 2. Send Notification
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'message' => 'আপনার ভেরিফিকেশন সফল হয়েছে, আপডেট না হলে রিফ্রেশ করুন',
                'created_at' => $current_time
            ]);

            // 3. Update Request status
            $verificationRequest->update([
                'status' => 'Approved',
                'updated_at' => $current_time,
                'verified_raw_time' => $verified_raw_time
            ]);

            // 4. Distribute Referral Bonus
            $this->distributeReferralBonus($user, $current_time);

            // 5. math_game + 4 for the direct referrer
            if ($user->referredBy) {
                SignUp::where('referCode', $user->referredBy)->increment('math_game', 4);
            }
        });

        return back()->with('success', 'User has been verified successfully.');
    }

    public function reject(Request $request, $id)
    {
        $verificationRequest = VerificationRequest::findOrFail($id);

        if ($verificationRequest->status !== 'Pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        DB::transaction(function () use ($verificationRequest) {
            $user = SignUp::findOrFail($verificationRequest->user_id);
            $current_time = date("d-m-Y h:i A");

            // 1. Update User status to rejected (0)
            $user->update([
                'is_verified' => 0,
                'verified_at' => $current_time
            ]);

            // 2. Send Rejection Notification
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'message' => 'আপনার ভেরিফিকেশন রিজেক্ট করা হয়েছে, অনুগ্রহ করে টাকা সেন্ড মানি করে সঠিক মোবাইল নাম্বার ও ট্রানজেশন আই.ডি দিন',
                'created_at' => $current_time
            ]);

            // 3. Update Request status
            $verificationRequest->update([
                'status' => 'Rejected',
                'updated_at' => $current_time
            ]);
        });

        return back()->with('success', 'Verification request has been rejected.');
    }

    private function distributeReferralBonus($user, $current_time)
    {
        $levels = [76, 35, 15, 10, 6, 5, 4, 3, 2, 2]; // 10 Levels
        $current_level = 1;
        $referredByCode = $user->referredBy;

        while ($current_level <= count($levels) && $referredByCode) {
            $referrer = SignUp::where('referCode', $referredByCode)->first();

            if ($referrer) {
                $bonus = $levels[$current_level - 1];

                // Add balance
                $referrer->increment('wallet_balance', $bonus);

                // Transaction Record
                \App\Models\Transaction::create([
                    'user_id' => $referrer->id,
                    'refer_id' => $user->referCode, // Person who got verified
                    'amount' => $bonus,
                    'type' => 'commission',
                    'description' => "লেভেল $current_level এফিলিয়েট বোনাস যুক্ত হয়েছে",
                    'update_at' => $current_time,
                    'created_at' => $current_time,
                    'payment_gateway' => 'Internal'
                ]);

                $referredByCode = $referrer->referredBy;
                $current_level++;
            } else {
                break;
            }
        }
    }

    public function bulkCardsData(Request $request)
    {
        $date = $request->input('date'); // Y-m-d
        if (!$date) {
            return response()->json(['error' => 'Date is required'], 400);
        }

        // Parse date for comparison depending on how verified_raw_time or updated_at is stored.
        // verified_raw_time is a standard Y-m-d H:i:s
        $users = VerificationRequest::select('sign_up.name', 'sign_up.referCode', 'sign_up.profile_pic_url', 'verification_requests.verified_raw_time')
            ->join('sign_up', 'verification_requests.user_id', '=', 'sign_up.id')
            ->where('verification_requests.status', 'Approved')
            ->whereDate('verification_requests.verified_raw_time', $date)
            ->get();

        $data = $users->map(function ($user) {
            $imageUrl = $user->profile_pic_url ?: 'https://thumb.ac-illust.com/b1/b170870007dfa419295d949814474ab2_t.jpeg';
            $base64Image = $imageUrl;
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(5)->get($imageUrl);
                if ($response->successful()) {
                    $contentType = $response->header('Content-Type') ?? 'image/jpeg';
                    $base64Image = 'data:' . $contentType . ';base64,' . base64_encode($response->body());
                }
            } catch (\Exception $e) {
            }

            return [
                'name' => $user->name,
                'referCode' => $user->referCode,
                'image' => $base64Image
            ];
        });

        return response()->json($data);
    }
}
