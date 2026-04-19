<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeadershipRewardRequest;
use App\Models\Transaction;
use App\Models\SignUp;
use Illuminate\Support\Facades\DB;

class LeadershipController extends Controller
{
    /**
     * Display a list of disbursed leadership bonuses (History).
     */
    public function history(Request $request)
    {
        $rewardFilter = $request->input('reward');

        $winners = Transaction::select('transactions.*', 'sign_up.name', 'sign_up.number', 'sign_up.referCode')
            ->join('sign_up', 'transactions.user_id', '=', 'sign_up.id')
            ->where('transactions.payment_gateway', 'Leadership Bonus')
            ->when($rewardFilter, function($q) use ($rewardFilter) {
                return $q->where('transactions.description', 'like', "%{$rewardFilter}%");
            })
            ->orderBy('transactions.id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.leadership.history', compact('winners', 'rewardFilter'));
    }

    /**
     * Display pending reward claims.
     */
    public function requests()
    {
        $requests = LeadershipRewardRequest::with('user')
            ->where('status', 'Pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.leadership.requests', compact('requests'));
    }

    /**
     * Approve or Reject a leadership reward claim.
     */
    public function processRequest(Request $request, $id)
    {
        $claim = LeadershipRewardRequest::findOrFail($id);
        $action = $request->input('action'); // 'Approved' or 'Rejected'

        if ($claim->status !== 'Pending') {
            return back()->with('error', 'This claim has already been processed.');
        }

        DB::transaction(function () use ($claim, $action) {
            if ($action === 'Approved') {
                // Update User Balance
                $user = SignUp::findOrFail($claim->user_id);
                $user->increment('wallet_balance', $claim->amount);

                // Create Transaction record
                Transaction::create([
                    'user_id' => $user->id,
                    'refer_id' => $user->referCode,
                    'amount' => $claim->amount,
                    'type' => 'income',
                    'payment_gateway' => 'Leadership Bonus',
                    'description' => $claim->reward_type . " Reward",
                    'update_at' => now()->toDateTimeString(),
                    'created_at' => now()->toDateTimeString(),
                ]);
            }

            // Update Claim Status
            $claim->update([
                'status' => $action,
                'updated_at' => now()->toDateTimeString(),
            ]);
        });

        return back()->with('success', "Claim has been {$action} successfully.");
    }
}
