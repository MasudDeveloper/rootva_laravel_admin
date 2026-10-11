<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\CouponClaim;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::with(['claims.user' => function($q) {
            $q->select('id', 'name', 'number', 'referCode');
        }])->orderBy('id', 'desc')->paginate(20);

        $totalCoupons = Coupon::count();
        $activeCoupons = Coupon::where('status', 1)->count();
        $totalClaims = CouponClaim::count();
        $totalDistributed = CouponClaim::sum('amount');

        $stats = [
            'total_coupons' => $totalCoupons,
            'active_coupons' => $activeCoupons,
            'total_claims' => $totalClaims,
            'total_distributed' => (float)$totalDistributed,
        ];

        return view('admin.coupons.index', compact('coupons', 'stats'));
    }

    public function store(Request $request)
    {
        $code = strtoupper(trim($request->input('code', '')));
        if (empty($code)) {
            $code = 'MEET' . strtoupper(Str::random(5));
        }

        $request->merge(['code' => $code]);

        $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'title' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.5',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        $maxUses = $request->input('max_uses') ? (int)$request->input('max_uses') : 1;

        Coupon::create([
            'code' => $code,
            'title' => $request->input('title', 'মিটিং স্পেশাল কুপন'),
            'amount' => (float)$request->input('amount'),
            'max_uses' => $maxUses,
            'used_count' => 0,
            'expires_at' => $request->input('expires_at') ?: null,
            'status' => $request->has('status') ? 1 : 1,
        ]);

        return back()->with('success', "কুপন কোড '{$code}' সফলভাবে তৈরি করা হয়েছে!");
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $code = strtoupper(trim($request->input('code', $coupon->code)));
        $request->merge(['code' => $code]);

        $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,' . $id,
            'title' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.5',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        $coupon->update([
            'code' => $code,
            'title' => $request->input('title', $coupon->title),
            'amount' => (float)$request->input('amount'),
            'max_uses' => (int)$request->input('max_uses', $coupon->max_uses),
            'expires_at' => $request->input('expires_at') ?: null,
            'status' => $request->input('status', 1),
        ]);

        return back()->with('success', 'কুপন তথ্য আপডেট করা হয়েছে!');
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->claims()->delete();
        $coupon->delete();

        return back()->with('success', 'কুপন সফলভাবে মুছে ফেলা হয়েছে!');
    }

    public function toggleStatus($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->status = !$coupon->status;
        $coupon->save();

        return back()->with('success', 'কুপন স্ট্যাটাস পরিবর্তন করা হয়েছে!');
    }
}
