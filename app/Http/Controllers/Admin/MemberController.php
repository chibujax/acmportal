<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'member');

        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%"));
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $members = $query->latest()->paginate(20)->withQueryString();

        return view('admin.members.index', compact('members'));
    }

    public function show(User $member)
    {
        $member->load('payments.duesCycle');
        return view('admin.members.show', compact('member'));
    }

    public function updateStatus(Request $request, User $member)
    {
        $request->validate(['status' => 'required|in:active,inactive,suspended']);
        $member->update(['status' => $request->status]);
        return back()->with('success', "Member status updated to {$request->status}.");
    }

    public function updateRole(Request $request, User $member)
    {
        $request->validate(['role' => 'required|in:admin,financial_secretary,member']);
        $member->update(['role' => $request->role]);
        return back()->with('success', "Member role updated.");
    }

    /**
     * Admin can manually create a member (bypass CSV/invite flow).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|unique:users',
            'email'    => 'nullable|email|unique:users',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:admin,financial_secretary,member',
        ]);

        User::create([
            'name'     => $request->name,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'status'   => 'active',
        ]);

        return back()->with('success', 'Member created successfully.');
    }
}
