<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonationItem;
use App\Models\DuesCycle;
use App\Models\MemberPledge;
use App\Models\Payment;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        // Show members AND admins (exclude super_admin)
        $query = User::where('role', '!=', 'super_admin');

        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%"));
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->role) {
            $query->where('role', $request->role);
        }

        $perPage = in_array((int) $request->per_page, [10, 20, 50, 100]) ? (int) $request->per_page : 20;
        $members = $query->latest()->paginate($perPage)->withQueryString();

        return view('admin.members.index', compact('members'));
    }

    public function show(User $member)
    {
        $member->load('payments.duesCycle');

        $spouse = $member->spouse();
        $children = $member->visibleChildren();

        $myPledges = MemberPledge::where('user_id', $member->id)
            ->get()->keyBy('dues_cycle_id');

        $myItemsByCycle = DonationItem::where('user_id', $member->id)
            ->latest()->get()->groupBy('dues_cycle_id');

        $activeCycles = DuesCycle::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get()
            ->map(function ($cycle) use ($member, $myPledges, $myItemsByCycle) {
                if ($cycle->is_pledge_based) {
                    $pledge               = $myPledges->get($cycle->id);
                    $obligation           = $pledge ? $pledge->pledged_amount : 0;
                    $cycle->pledge_amount = $pledge ? $pledge->pledged_amount : null;
                    $cycle->my_items      = $myItemsByCycle->get($cycle->id, collect());
                } else {
                    $obligation           = $member->obligationFor($cycle);
                    $cycle->pledge_amount = null;
                    $cycle->my_items      = collect();
                }

                $paid              = $member->totalPaidWithSpouse($cycle->id, $cycle->couple_shared);
                $remaining         = max(0, $obligation - $paid);
                $percent           = $obligation > 0 ? min(100, round(($paid / $obligation) * 100)) : 0;

                $cycle->user_obligation   = $obligation;
                $cycle->user_paid         = $paid;
                $cycle->user_remaining    = $remaining;
                $cycle->user_percent      = $percent;
                $cycle->is_family_billing = $member->hasSpouse() && $cycle->couple_shared;
                return $cycle;
            });

        return view('admin.members.show', compact('member', 'spouse', 'children', 'activeCycles'));
    }

    public function updateStatus(Request $request, User $member)
    {
        abort_unless(auth()->user()->hasAccess('manage'), 403);
        $request->validate(['status' => 'required|in:active,inactive,suspended']);
        $member->update(['status' => $request->status]);
        return back()->with('success', "Member status updated to {$request->status}.");
    }

    public function updateRole(Request $request, User $member)
    {
        abort_unless(auth()->user()->hasAccess('manage'), 403);
        $request->validate(['role' => 'required|in:super_admin,admin,member']);
        $member->update(['role' => $request->role]);
        return back()->with('success', "Member role updated.");
    }

    public function sendSms(Request $request, User $member)
    {
        $request->validate(['message' => 'required|string|max:160']);

        if (! $member->phone) {
            return back()->withErrors(['message' => 'This member has no phone number on record.']);
        }

        $message = str_replace('{name}', $member->name, $request->message);

        $ok = app(SmsService::class)->send($member->phone, $message);

        return $ok
            ? back()->with('success', "SMS sent to {$member->name}.")
            : back()->with('warning', "SMS to {$member->name} was not accepted by the provider — check the application logs.");
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
            'role'     => 'required|in:super_admin,admin,member',
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
