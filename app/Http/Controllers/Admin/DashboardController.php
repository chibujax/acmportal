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
            'total_members'   => User::where('role', 'member')->count(),
            'active_members'  => User::where('role', 'member')->where('status', 'active')->count(),
            'pending_invites' => PendingMember::whereIn('status', ['pending', 'invited'])->count(),
            'active_cycles'   => DuesCycle::where('status', 'active')->count(),
            'total_collected' => Payment::where('status', 'completed')
                ->whereMonth('created_at', now()->month)->sum('amount'),
            'arrears_count'   => 0, // calculated per cycle
            'recent_payments' => Payment::with(['user', 'duesCycle'])
                ->where('status', 'completed')
                ->latest()
                ->take(5)
                ->get(),
            'active_dues_cycles' => DuesCycle::where('status', 'active')
                ->withCount(['payments as paid_count' => fn($q) => $q->where('status','completed')])
                ->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
