<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SignUp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $number = $request->input('number');
        $password = $request->input('password');

        if (!$number || !$password) {
            return response()->json(['message' => 'অবৈধ ডেটা']);
        }

        $user = SignUp::where('number', $number)->first();

        if ($user) {
            if (Hash::check($password, $user->password) || $password === $user->password) {
                // Generate secure token (matching legacy bin2hex 32 bytes if needed)
                $token = bin2hex(random_bytes(32));
                
                $user->api_token = $token;
                $user->save();

                return response()->json([
                    'message' => 'লগইন সফল',
                    'userId' => $user->id,
                    'password_updated_at' => $user->password_updated_at ?? '',
                    'api_token' => $token,
                ]);
            } else {
                return response()->json(['message' => 'ভুল পাসওয়ার্ড']);
            }
        } else {
            return response()->json(['message' => 'নম্বরটি পাওয়া যায়নি']);
        }
    }

    public function register(Request $request)
    {
        $name = $request->input('name');
        $number = $request->input('number');
        $password = $request->input('password');
        $referred_by = $request->input('referredBy');
        $created_at = $request->input('created_at', now()->toDateTimeString());

        if (!$name || !$number || !$password) {
            return response()->json(['message' => 'অবৈধ ডেটা']);
        }

        if ($referred_by) {
            $referrer = SignUp::where('referCode', $referred_by)->first();
            if (!$referrer) {
                return response()->json(['message' => 'রেফার কোডটি সঠিক নয়']);
            }
        }

        if (SignUp::where('number', $number)->exists()) {
            return response()->json(['message' => 'মোবাইল নম্বরটি ইতিমধ্যে ব্যবহৃত হয়েছে']);
        }

        $hashed_password = Hash::make($password);

        do {
            $refer_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (SignUp::where('referCode', $refer_code)->exists());

        try {
            $user = SignUp::create([
                'name' => $name,
                'number' => $number,
                'password' => $hashed_password,
                'referCode' => $refer_code,
                'referredBy' => $referred_by,
                'created_at' => $created_at,
                'email' => '',
                'address' => '',
                'profile_pic_url' => '',
                'gender' => 'Male',
                'verified_at' => '',
                'verified_raw_time' => date('Y-m-d H:i:s'),
            ]);

            if ($user) {
                // Refresh the user model to get the database-generated password_updated_at
                $user->refresh();

                // Generate secure token for the new user
                $token = bin2hex(random_bytes(32));
                $user->api_token = $token;
                $user->save();

                return response()->json([
                    'message' => 'রেজিস্ট্রেশন সফল',
                    'userId' => $user->id,
                    'password_updated_at' => (string) $user->password_updated_at,
                    'api_token' => $token,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'রেজিস্ট্রেশন ব্যর্থ',
                'error' => $e->getMessage()
            ]);
        }

        return response()->json(['message' => 'রেজিস্ট্রেশন ব্যর্থ']);
    }

    private function distributeReferralCommission($user)
    {
        $levels = [
            1 => 60, // Level 1
            2 => 30, // Level 2
            3 => 10, // Level 3
            4 => 5,  // Level 4
            5 => 5,  // Level 5
            6 => 5,  // Level 6
            7 => 5   // Level 7
        ];

        $current_level = 1;
        $referrerCode = $user->referredBy;

        while ($current_level <= 7 && $referrerCode) {
            $referrer = SignUp::where('referCode', $referrerCode)->first();

            if ($referrer) {
                $amount = $levels[$current_level];
                
                // 1. Create Referral Commission Record
                \App\Models\ReferralCommission::create([
                    'user_id' => $referrer->id,
                    'level' => $current_level,
                    'amount' => $amount,
                    'description' => "Level $current_level referral commission for user: $user->name ($user->number)",
                ]);

                // 2. Create Transaction Log
                \App\Models\Transaction::create([
                    'user_id' => $referrer->id,
                    'refer_id' => $user->referCode,
                    'amount' => $amount,
                    'type' => 'referral_commission',
                    'payment_gateway' => 'Referral System',
                    'description' => "Level $current_level Referral Commission from $user->name",
                    'update_at' => date('d-m-Y h:i A'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'date' => date('Y-m-d H:i:s')
                ]);

                // 3. Update User Balance
                $referrer->increment('wallet_balance', $amount);

                // Move to next level
                $referrerCode = $referrer->referredBy;
                $current_level++;
            } else {
                break;
            }
        }
    }
}
