<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\DonationItem;
use App\Models\DuesCycle;
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

        $activeCycles = DuesCycle::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get()
            ->filter(function ($cycle) use ($myPledges) {
                // Pledge-based cycles only appear for members who have actually pledged
                return !$cycle->is_pledge_based || $myPledges->has($cycle->id);
            })
            ->map(function ($cycle) use ($user, $myPledges) {
                if ($cycle->is_pledge_based) {
                    $pledge     = $myPledges->get($cycle->id);
                    $obligation = $pledge ? $pledge->pledged_amount : 0;
                    $cycle->pledge_amount = $pledge ? $pledge->pledged_amount : null;
                } else {
                    $obligation = $user->obligationFor($cycle);
                    $cycle->pledge_amount = null;
                }

                $paid       = $user->totalPaidWithSpouse($cycle->id, $cycle->couple_shared);
                $remaining  = max(0, $obligation - $paid);
                $percent    = $obligation > 0 ? min(100, round(($paid / $obligation) * 100)) : 0;
                $spouse     = $user->spouse();

                $cycle->user_obligation   = $obligation;
                $cycle->user_paid         = $paid;
                $cycle->user_remaining    = $remaining;
                $cycle->user_percent      = $percent;
                $cycle->is_family_billing = $user->hasSpouse() && $cycle->couple_shared;
                $cycle->spouse_name       = $spouse ? $spouse->name : null;
                return $cycle;
            });

        $recentPayments = Payment::where('user_id', $user->id)
            ->with('duesCycle')
            ->latest()
            ->take(5)
            ->get();

        $totalPaid = Payment::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('amount');

        // Donation items this member has contributed
        $myDonationItems = DonationItem::where('user_id', $user->id)
            ->with('duesCycle')
            ->latest()
            ->get();

        return view('member.dashboard', compact('activeCycles', 'recentPayments', 'totalPaid', 'myDonationItems'));
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
