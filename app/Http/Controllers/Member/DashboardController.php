<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\DonationItem;
use App\Models\DuesCycle;
use App\Models\MemberLegacyBalance;
use App\Models\MemberPledge;
use App\Models\Payment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Pre-load pledges and donation items for this user
        $myPledges = MemberPledge::where('user_id', $user->id)
            ->with('duesCycle')
            ->get()
            ->keyBy('dues_cycle_id');

        $myItemsByCycle = DonationItem::where('user_id', $user->id)
            ->with('duesCycle')
            ->latest()
            ->get()
            ->groupBy('dues_cycle_id');

        $spouse = $user->spouse();
        $spouseName = $spouse ? $spouse->name : null;

        $cycleMapper = function ($cycle) use ($user, $myPledges, $myItemsByCycle, $spouse, $spouseName) {
            if ($cycle->is_pledge_based) {
                $pledge     = $myPledges->get($cycle->id);
                $obligation = $pledge ? $pledge->pledged_amount : 0;
                $cycle->pledge_amount = $pledge ? $pledge->pledged_amount : null;
                $cycle->my_items      = $myItemsByCycle->get($cycle->id, collect());
            } else {
                $obligation = $user->obligationFor($cycle);
                $cycle->pledge_amount = null;
                $cycle->my_items      = collect();
            }

            $paid      = $user->totalPaidWithSpouse($cycle->id, $cycle->couple_shared);
            $remaining = max(0, $obligation - $paid);
            $percent   = $obligation > 0 ? min(100, round(($paid / $obligation) * 100)) : 0;

            $cycle->user_obligation   = $obligation;
            $cycle->user_paid         = $paid;
            $cycle->user_remaining    = $remaining;
            $cycle->user_percent      = $percent;
            $cycle->is_family_billing = $user->hasSpouse() && $cycle->couple_shared;
            $cycle->spouse_name       = $spouseName;
            return $cycle;
        };

        // Currently open cycles — shown in the detailed Active Dues section
        $activeCycles = DuesCycle::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get()
            ->map($cycleMapper);

        // Legacy outstanding imported from worksheet (pre-2026)
        $legacyBalances = MemberLegacyBalance::where('user_id', $user->id)
            ->orderBy('year')
            ->orderBy('label')
            ->get();

        $legacyTotal = $legacyBalances->sum('amount');

        // 2026+ cycles — calculated outstanding from cycle data
        $currentCycles = DuesCycle::whereIn('status', ['active', 'closed'])
            ->where('start_date', '>=', '2026-01-01')
            ->orderBy('start_date')
            ->get()
            ->map($cycleMapper)
            ->where('user_remaining', '>', 0);

        $currentTotal = $currentCycles->sum('user_remaining');

        $totalOutstanding = $legacyTotal + $currentTotal;

        $recentPayments = Payment::where('user_id', $user->id)
            ->with('duesCycle')
            ->latest()
            ->take(5)
            ->get();

        $totalPaid = Payment::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('amount');

        return view('member.dashboard', compact(
            'activeCycles', 'legacyBalances', 'legacyTotal',
            'currentCycles', 'currentTotal', 'totalOutstanding',
            'recentPayments', 'totalPaid'
        ));
    }

    public function profile()
    {
        return view('member.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'email'       => "nullable|email|unique:users,email,{$user->id}",
            'address'     => 'nullable|string|max:500',
            'occupation'  => 'nullable|string|max:255',
            'gender'      => 'nullable|in:male,female,other',
        ]);

        $user->update($request->only('email', 'address', 'occupation', 'gender'));

        return back()->with('success', 'Profile updated.');
    }

    public function paymentHistory()
    {
        $payments = Payment::where('user_id', auth()->id())
            ->with('duesCycle')
            ->latest()
            ->paginate(15);

        return view('member.payments', compact('payments'));
    }
}
