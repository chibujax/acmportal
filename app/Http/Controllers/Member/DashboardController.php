<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\Payment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $activeCycles = DuesCycle::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get()
            ->map(function ($cycle) use ($user) {
                $obligation = $user->obligationFor($cycle);
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

        return view('member.dashboard', compact('activeCycles', 'recentPayments', 'totalPaid'));
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
