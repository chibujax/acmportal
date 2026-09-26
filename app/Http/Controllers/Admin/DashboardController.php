<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\Payment;
use App\Models\PendingMember;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_members'   => User::where('role', '!=', 'super_admin')->count(),
            'active_members'  => User::where('role', '!=', 'super_admin')->where('status', 'active')->count(),
            'pending_invites' => PendingMember::whereIn('status', ['pending', 'invited'])->count(),
            'active_cycles'   => DuesCycle::where('status', 'active')->count(),
            'total_collected' => Payment::where('status', 'completed')
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount'),
            'arrears_count'   => 0, // calculated per cycle
            'recent_payments' => Payment::with(['user', 'duesCycle'])
                ->where('status', 'completed')
                ->latest('payment_date')
                ->take(5)
                ->get(),
            'active_dues_cycles' => DuesCycle::where('status', 'active')
                ->withCount(['payments as paid_count' => fn($q) => $q->where('status','completed')])
                ->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
