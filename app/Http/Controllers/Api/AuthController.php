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

        $user = SignUp::create([
            'name' => $name,
            'number' => $number,
            'password' => $hashed_password,
            'referCode' => $refer_code,
            'referredBy' => $referred_by,
            'created_at' => $created_at,
        ]);

        if ($user) {
            if ($referred_by) {
                $this->distributeReferralCommission($user);
            }

            return response()->json([
                'message' => 'রেজিস্ট্রেশন সফল',
                'password_updated_at' => $user->password_updated_at ?? '',
            ]);
        }

        return response()->json(['message' => 'রেজিস্ট্রেশন ব্যর্থ']);
    }

    private function distributeReferralCommission($user)
    {
        $levels = [
            1 => 60, 2 => 30, 3 => 10, 4 => 5, 5 => 5, 6 => 5, 7 => 5
        ];

        $current_level = 1;
        $referrerCode = $user->referredBy;

        while ($current_level <= 7 && $referrerCode) {
            $referrer = SignUp::where('referCode', $referrerCode)->first();

            if ($referrer) {
                \App\Models\ReferralCommission::create([
                    'user_id' => $referrer->id,
                    'level' => $current_level,
                    'amount' => $levels[$current_level],
                    'description' => "Level $current_level referral commission for user ID $user->id",
                ]);

                $referrerCode = $referrer->referredBy;
                $current_level++;
            } else {
                break;
            }
        }
    }
}
