<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class LegacyAuthController extends Controller
{
    /**
     * Legacy Login (login.php)
     */
    public function login(Request $request)
    {
        $number = $request->input('number');
        $password = $request->input('password');

        $user = SignUp::where('number', $number)->first();

        if ($user) {
            if (password_verify($password, $user->password)) {
                $token = bin2hex(random_bytes(32));
                $user->update(['api_token' => $token]);
                
                return response()->json([
                    'message' => 'লগইন সফল',
                    'userId' => $user->id,
                    'password_updated_at' => $user->password_updated_at ?? '',
                    'api_token' => $token,
                    'is_verified' => (int) $user->is_verified
                ]);
            } else {
                return response()->json(['message' => 'ভুল পাসওয়ার্ড']);
            }
        } else {
            return response()->json(['message' => 'নম্বরটি পাওয়া যায়নি']);
        }
    }

    /**
     * Legacy Verify Password (verify_password.php)
     */
    public function verifyPassword(Request $request)
    {
        $userId = $request->input('user_id');
        $password = $request->input('password');

        if (!$userId || !$password) {
            return response()->json(["status" => false, "message" => "Invalid request"]);
        }

        $user = SignUp::find($userId);

        if (!$user) {
            return response()->json(["status" => false, "message" => "User not found"]);
        }

        // Password যাচাই (Plain text এবং Hash উভয়ই সাপোর্ট করবে আপনার পুরনো কোডের মতো)
        if ($password === $user->password || Hash::check($password, $user->password)) {
            return response()->json(["status" => true, "message" => "Password verified"]);
        } else {
            return response()->json(["status" => false, "message" => "Wrong password"]);
        }
    }

    /**
     * Legacy Password Check (check-password-update.php)
     */
    public function checkPasswordUpdate(Request $request)
    {
        $number = $request->query('number');
        
        if (!$number) {
            return response()->json(['error' => 'Number is required']);
        }

        $user = SignUp::where('number', $number)->first();
        
        if ($user) {
            return response()->json([
                'error' => false,
                'password_updated_at' => $user->password_updated_at ?? ''
            ]);
        }
        
        return response()->json(['error' => true, 'message' => 'Number not found']);
    }

    /**
     * OTP and Password Reset
     */
    public function sendEmailOtp(Request $request)
    {
        $email = $request->input('email');

        if (!$email) {
            return response()->json(['success' => false, 'message' => 'ইমেইল প্রয়োজন']);
        }

        // Check if email exists
        $user = SignUp::where('email', $email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'এই ইমেইলটি রেজিস্ট্রেশনে নেই']);
        }

        $otp = rand(100000, 999999);

        // Insert or Update OTP in otp_verification table
        DB::table('otp_verification')->updateOrInsert(
            ['email' => $email],
            ['otp' => $otp, 'created_at' => now()]
        );

        try {
            Mail::send([], [], function ($message) use ($email, $otp) {
                $message->to($email)
                    ->subject('Your OTP Code for Password Reset')
                    ->html("
                        <p>Dear user,</p>
                        <p>Your OTP code for password reset is: 
                        <strong style='font-size:18px;'>$otp</strong></p>
                        <p>This OTP is valid for only 5 minutes.</p>
                        <br>
                        <p>Regards,<br>Rootva Team</p>
                    ");
            });

            return response()->json(['success' => true, 'message' => 'OTP পাঠানো হয়েছে']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'OTP পাঠাতে ব্যর্থ: ' . $e->getMessage()]);
        }
    }

    public function resetPassword(Request $request)
    {
        $email = $request->input('email');
        $otp = $request->input('otp');
        $new_password = $request->input('new_password');

        if (!$email || !$otp || !$new_password) {
            return response()->json(['success' => false, 'message' => 'সব তথ্য পূরণ করুন']);
        }

        // OTP check from database
        $otpRecord = DB::table('otp_verification')->where('email', $email)->first();

        if (!$otpRecord) {
            return response()->json(['success' => false, 'message' => 'OTP সেট করা হয়নি']);
        }

        $stored_otp = $otpRecord->otp;
        $created_at = strtotime($otpRecord->created_at);
        $current_time = time();
        $validity_duration = 5 * 60; // 5 minutes

        if ($stored_otp != $otp) {
            return response()->json(['success' => false, 'message' => 'OTP ভুল']);
        } elseif (($current_time - $created_at) > $validity_duration) {
            DB::table('otp_verification')->where('email', $email)->delete();
            return response()->json(['success' => false, 'message' => 'OTP-এর মেয়াদ শেষ হয়েছে']);
        }

        // Update Password
        $user = SignUp::where('email', $email)->first();
        if ($user) {
            $user->password = bcrypt($new_password);
            if ($user->save()) {
                // OTP রেকর্ড ডিলিট করে দাও
                DB::table('otp_verification')->where('email', $email)->delete();
                return response()->json(['success' => true, 'message' => 'পাসওয়ার্ড রিসেট সফল হয়েছে']);
            }
        }

        return response()->json(['success' => false, 'message' => 'পাসওয়ার্ড আপডেট ব্যর্থ']);
    }

    /**
     * Legacy Register (register.php)
     */
    public function register(Request $request)
    {
        $name = $request->input('name');
        $number = $request->input('number');
        $email = $request->input('email');
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

        if ($email && SignUp::where('email', $email)->exists()) {
            return response()->json(['message' => 'ইমেল ঠিকানাটি ইতিমধ্যে ব্যবহৃত হয়েছে']);
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
                'email' => $email ?? '',
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
                    'is_verified' => 0
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

}
