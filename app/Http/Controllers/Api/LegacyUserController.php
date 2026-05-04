<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\AppUpdate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class LegacyUserController extends Controller
{
    /**
     * Legacy User Data (get_Data.php)
     */
    public function getUserData(Request $request)
    {
        $number = $request->input('number');
        
        if (empty($number)) {
            return response()->json(['status' => 'error', 'message' => 'অবৈধ ডেটা']);
        }

        $user = SignUp::where('number', $number)->first();
        if ($user) {
            return response()->json([
                'status' => 'success',
                'message' => 'ইউজার তথ্য সফলভাবে পাওয়া গেছে',
                'users' => $user,
                'show_verification_popup' => (bool)($user->is_verified == 1 && $user->verification_popup_shown == 0)
            ]);
        }
        return response()->json(['status' => 'error', 'message' => 'ইউজার পাওয়া যায়নি']);
    }

    /**
     * Legacy Individual Profile (get_profile.php)
     */
    public function getProfile(Request $request)
    {
        $userId = $request->query('user_id');
        $user = SignUp::find($userId);
        
        if ($user) {
            return response()->json([
                'users' => $user,
                'message' => "ডেটা সফলভাবে লোড হয়েছে"
            ]);
        }
        
        return response()->json([
            'message' => "ইউজার খুঁজে পাওয়া যায়নি"
        ]);
    }

    /**
     * Legacy Profile Update (update_profile.php)
     */
    public function updateProfile(Request $request)
    {
        Log::info('Update Profile Request:', [
            'all' => $request->all(),
            'user_id' => $request->input('user_id'),
            'post_user_id' => $_POST['user_id'] ?? 'not_in_post'
        ]);

        $user_id = $request->input('user_id') ?? ($_POST['user_id'] ?? null);
        $name = $request->input('name');
        $gender = $request->input('gender');
        $address = $request->input('address');
        $email = $request->input('email');
        $profile_pic_url = $request->input('profile_pic_url');

        if ($user_id) {
            $user = SignUp::find($user_id);
            if ($user) {
                $updated = $user->update([
                    'name'            => $name,
                    'gender'          => $gender,
                    'address'         => $address,
                    'email'           => $email,
                    'profile_pic_url' => $profile_pic_url,
                ]);

                if ($updated) {
                    return response()->json(['message' => 'প্রোফাইল আপডেট সফল']);
                } else {
                    return response()->json(['message' => 'প্রোফাইল আপডেট ব্যর্থ']);
                }
            }
        }
        
        return response()->json(['message' => 'অবৈধ ডেটা']);
    }

    /**
     * Legacy Upload Profile Pic (upload_profile_pic.php)
     */
    public function uploadProfilePic(Request $request)
    {
        Log::info('Upload Profile Pic Request:', [
            'all' => $request->all(),
            'user_id' => $request->input('user_id'),
            'post_user_id' => $_POST['user_id'] ?? null,
            'has_file' => $request->hasFile('file'),
            'files' => $_FILES
        ]);
        
        $user_id = $request->input('user_id') ?? ($_POST['user_id'] ?? null);
        $file = $request->file('file');

        if ($user_id && $file) {
            $targetDir = "Images/";
            $publicPath = public_path($targetDir);
            if (!is_dir($publicPath)) {
                mkdir($publicPath, 0777, true);
            }

            // পুরনো ছবি ডিলিট করার লজিক (আপনার পিএইচপি কোড অনুযায়ী)
            $user = SignUp::find($user_id);
            if ($user && !empty($user->profile_pic_url)) {
                $oldFilePath = parse_url($user->profile_pic_url, PHP_URL_PATH);
                $oldFilePath = ltrim($oldFilePath, '/');
                $fullOldPath = public_path($oldFilePath);
                if (file_exists($fullOldPath)) {
                    unlink($fullOldPath);
                }
            }

            // ফাইল ফরম্যাট চেক
            $fileExtension = $file->getClientOriginalExtension();
            $allowTypes = ['jpg', 'png', 'jpeg'];
            
            if (in_array(strtolower($fileExtension), $allowTypes)) {
                $fileName = uniqid('profile_', true) . "." . $fileExtension;
                
                try {
                    $file->move($publicPath, $fileName);
                    $profilePicUrl = "https://api.rootvabd.com/" . $targetDir . $fileName;
                    
                    if ($user) {
                        $user->update(['profile_pic_url' => $profilePicUrl]);
                        return response()->json(['message' => $profilePicUrl]);
                    } else {
                        return response()->json(['message' => 'ডেটা আপডেট ব্যর্থ']);
                    }
                } catch (\Exception $e) {
                    return response()->json(['message' => 'ছবি আপলোড ব্যর্থ']);
                }
            } else {
                return response()->json(['message' => 'অনুমোদিত ফাইল ফরম্যাট নয়']);
            }
        }
        
        return response()->json(['message' => 'অবৈধ ডেটা']);
    }

    /**
     * Legacy Save FCM Token (save_fcm_token.php)
     */
    public function saveFcmToken(Request $request)
    {
        $userId = $request->input('user_id');
        $token = $request->input('fcm_token');
        
        if (!$userId || !$token) {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }

        SignUp::where('id', $userId)->update(['fcm_token' => $token]);
        
        return response()->json([
            'status' => true,
            'message' => 'FCM Token updated'
        ]);
    }

    /**
     * Legacy Check Refer Code (getRefer.php)
     */
    public function checkReferCode(Request $request)
    {
        $referCode = $request->input('referCode');
        $user = SignUp::where('referCode', $referCode)->first();

        if ($user) {
            return response()->json(['success' => true, 'name' => $user->name, 'user_id' => $user->id]);
        }
        return response()->json(['success' => false, 'message' => 'Invalid Refer Code']);
    }

    /**
     * Mark Verification Popup Seen
     */
    public function markVerificationPopupSeen(Request $request)
    {
        $userId = $request->input('user_id');
        $user = SignUp::find($userId);
        
        if ($user) {
            $user->verification_popup_shown = 1;
            $user->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    }

    /**
     * Legacy App Update (get_latest_update.php)
     */
    public function getUpdate()
    {
        $update = AppUpdate::latest()->first();
        return response()->json([
            'version_code'   => (int)($update->version_code ?? 1),
            'update_link'    => $update->url ?? '',
            'update_message' => $update->message ?? 'New update available'
        ]);
    }
}
