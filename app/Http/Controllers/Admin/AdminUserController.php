<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * List of available modules and their human readable labels
     */
    public static function availablePermissions(): array
    {
        return [
            'users'       => 'User Management (ইউজার তালিকা, এডিট ও ওয়ালেট)',
            'microjobs'   => 'Microjob Management (মাইক্রোজব পোস্ট ও সাবমিশন রিভিউ)',
            'smm'         => 'SMM Panel (SMM টাস্ক ও সাবমিশন রিভিউ)',
            'reselling'   => 'Reselling & Products (প্রোডাক্ট ও অর্ডার কনফার্মেশন)',
            'sim_offers'  => 'SIM Offers & Recharge (সিম অফার ও রিচার্জ)',
            'courses'     => 'Courses & Digital Items (ডিজিটাল কোর্স)',
            'withdrawals' => 'Withdrawal Requests (উইথড্র রিকোয়েস্ট অনুমোদন)',
            'leadership'  => 'Leadership & Salary (লিডারশিপ ও স্যালারি)',
            'sub_admins'  => 'Sub-Admin Management (সাব-এডমিন তৈরি ও পারমিশন)',
        ];
    }

    public function index()
    {
        $admins = Admin::orderBy('id', 'desc')->paginate(20);
        $availablePermissions = self::availablePermissions();

        return view('admin.sub_admins.index', compact('admins', 'availablePermissions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'username'    => 'required|string|max:100|unique:admin_users,username',
            'password'    => 'required|string|min:4',
            'role'        => 'required|string|in:super_admin,sub_admin',
            'permissions' => 'nullable|array',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['permissions'] = $data['role'] === 'super_admin' ? array_keys(self::availablePermissions()) : ($data['permissions'] ?? []);

        Admin::create($data);

        return back()->with('success', 'নতুন এডমিন/সাব-এডমিন সফলভাবে তৈরি করা হয়েছে।');
    }

    public function update(Request $request, $id)
    {
        $adminUser = Admin::findOrFail($id);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'username'    => 'required|string|max:100|unique:admin_users,username,' . $id,
            'password'    => 'nullable|string|min:4',
            'role'        => 'required|string|in:super_admin,sub_admin',
            'permissions' => 'nullable|array',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['permissions'] = $data['role'] === 'super_admin' ? array_keys(self::availablePermissions()) : ($data['permissions'] ?? []);

        $adminUser->update($data);

        return back()->with('success', 'এডমিন তথ্য ও পারমিশন সফলভাবে আপডেট করা হয়েছে।');
    }

    public function destroy($id)
    {
        $adminUser = Admin::findOrFail($id);

        if ($adminUser->id === auth()->id() || $adminUser->username === 'admin') {
            return back()->with('error', 'মূল সুপার এডমিন অ্যাকাউন্ট মুছে ফেলা যাবে না!');
        }

        $adminUser->delete();

        return back()->with('success', 'সাব-এডমিন অ্যাকাউন্ট মুছে ফেলা হয়েছে।');
    }
}
