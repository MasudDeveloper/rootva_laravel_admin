<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\AppUpdate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

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
            $api_token = $request->header('Authorization') ?: $request->query('api_token') ?: $request->input('api_token');
            if (strpos((string)$api_token, 'Bearer ') === 0) {
                $api_token = substr($api_token, 7);
            }
            $password = $request->input('password') ?: $request->header('Auth-Password');

            $isAuthenticated = false;
            if ($api_token && $user->api_token === $api_token) {
                $isAuthenticated = true;
            } elseif ($password && (\Illuminate\Support\Facades\Hash::check($password, $user->password) || $password === $user->password)) {
                $isAuthenticated = true;
            }

            $isNewApp = $request->hasHeader('Auth-User-Id');
            if ($isNewApp && !$isAuthenticated) {
                // DON'T leak data! If the password doesn't match, return Unauthorized
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized request'
                ], 401);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'ইউজার তথ্য সফলভাবে পাওয়া গেছে',
                'users' => $user,
                'is_verified' => (int) $user->is_verified,
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
                'is_verified' => (int) $user->is_verified,
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
                // ইনপুট ক্লিন করা এবং নিরাপদ ডেটা তৈরি করা
                $updateData = [];
                if ($name !== null) {
                    $updateData['name'] = $name;
                }
                if (in_array($gender, ['Male', 'Female'])) {
                    $updateData['gender'] = $gender;
                }
                if ($address !== null) {
                    $updateData['address'] = $address;
                }
                if ($email !== null) {
                    $updateData['email'] = $email;
                }
                if (!empty($profile_pic_url)) {
                    $updateData['profile_pic_url'] = $profile_pic_url;
                }

                try {
                    // যদি কোনো ডেটা আপডেট করার মতো থাকে
                    if (!empty($updateData)) {
                        $user->update($updateData);
                    }
                    
                    return response()->json(['message' => 'প্রোফাইল আপডেট সফল']);
                } catch (\Throwable $e) {
                    Log::error('Profile Update Failed: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                        'user_id' => $user_id,
                        'data' => $updateData
                    ]);
                    return response()->json(['message' => 'প্রোফাইল আপডেট ব্যর্থ: ' . $e->getMessage()]);
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
            $user = SignUp::find($user_id);
            if (!$user) {
                return response()->json(['message' => 'ইউজার পাওয়া যায়নি']);
            }

            $destPath = '/home/syfoocuv/api.rootvabd.com/Images';
            
            // Fallback for local development
            if (!file_exists('/home/syfoocuv')) {
                $destPath = public_path('Images');
            }

            if (!is_dir($destPath)) {
                mkdir($destPath, 0777, true);
            }

            // পুরনো ছবি ডিলিট করার লজিক
            if (!empty($user->profile_pic_url)) {
                $fileNameOnly = basename($user->profile_pic_url);
                $fullOldPath = $destPath . '/' . $fileNameOnly;
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
                    $file->move($destPath, $fileName);
                    $profilePicUrl = "https://api.rootvabd.com/Images/" . $fileName;
                    
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

        $user = SignUp::find($userId);
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User not found']);
        }

        $user->update(['fcm_token' => $token]);
        
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
        $data = Cache::remember('api_app_update', 300, function () {
            $update = AppUpdate::latest()->first();
            
            $isMaintenance = 0;
            $maintenanceMessage = '';
            $maintenanceCountdown = '';
            
            $path = storage_path('app/maintenance.json');
            if (file_exists($path)) {
                $maintenance = json_decode(file_get_contents($path), true);
                $isMaintenance = (int)($maintenance['is_maintenance'] ?? 0);
                $maintenanceMessage = $maintenance['maintenance_message'] ?? '';
                $maintenanceCountdown = $maintenance['maintenance_countdown'] ?? '';
            }

            return [
                'version_code'   => (int)($update->version_code ?? 1),
                'update_link'    => $update->url ?? '',
                'update_message' => $update->message ?? 'New update available',
                'is_maintenance' => $isMaintenance,
                'maintenance_message' => $maintenanceMessage,
                'maintenance_countdown' => $maintenanceCountdown
            ];
        });

        return response()->json($data);
    }

    /**
     * Update User Active Status (update_active_status.php)
     */
    public function updateActiveStatus(Request $request)
    {
        $userId = $request->input('user_id');
        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'অবৈধ ডেটা']);
        }

        $user = SignUp::find($userId);
        if ($user) {
            $user->update(['last_active_at' => now()]);
            return response()->json(['status' => 'success', 'message' => 'স্ট্যাটাস আপডেট সফল']);
        }

        return response()->json(['status' => 'error', 'message' => 'ইউজার পাওয়া যায়নি']);
    }
}
