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
                $paid = $user->totalPaid($cycle->id);
                $cycle->user_paid      = $paid;
                $cycle->user_remaining = max(0, $cycle->amount - $paid);
                $cycle->user_percent   = $cycle->amount > 0
                    ? min(100, round(($paid / $cycle->amount) * 100))
                    : 0;
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
        ]);

        $user->update($request->only('email', 'address', 'occupation'));

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
