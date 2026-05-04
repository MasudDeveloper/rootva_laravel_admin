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
                    'api_token' => $token
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
            // Temporarily configure mail settings
            Config::set('mail.mailers.smtp.host', 'rootvabd.com');
            Config::set('mail.mailers.smtp.port', 465);
            Config::set('mail.mailers.smtp.encryption', 'ssl');
            Config::set('mail.mailers.smtp.username', 'otp@rootvabd.com');
            Config::set('mail.mailers.smtp.password', 'Masud1999@@');
            Config::set('mail.from.address', 'otp@rootvabd.com');
            Config::set('mail.from.name', 'Rootva');

            Mail::html("
                <p>Dear user,</p>
                <p>Your OTP code for password reset is: 
                <strong style='font-size:18px;'>$otp</strong></p>
                <p>This OTP is valid for only 5 minutes.</p>
                <br>
                <p>Regards,<br>Rootva Team</p>
            ", function ($message) use ($email) {
                $message->to($email)
                    ->subject('Your OTP Code for Password Reset');
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
}
