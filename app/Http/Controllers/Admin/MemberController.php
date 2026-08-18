<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonationItem;
use App\Models\DuesCycle;
use App\Models\MemberLegacyBalance;
use App\Models\MemberPledge;
use App\Models\Payment;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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

        if ($request->portal === 'registered') {
            $query->where(fn($q) => $q->whereNotNull('portal_activated_at')
                ->orWhereNotNull('activation_invited_at'));
        } elseif ($request->portal === 'not_registered') {
            $query->whereNull('portal_activated_at')->whereNull('activation_invited_at');
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

        // Spouse's pledges explicitly marked "shared" — fallback when this member hasn't pledged individually
        $spousePledges = $spouse
            ? MemberPledge::where('user_id', $spouse->id)->where('shared_with_spouse', true)->get()->keyBy('dues_cycle_id')
            : collect();

        $pledgeCycleMapper = function ($cycle) use ($member, $myPledges, $myItemsByCycle, $spousePledges) {
            $pledgeFromSpouse = false;

            if ($cycle->is_pledge_based) {
                $pledge = $myPledges->get($cycle->id);

                if (! $pledge && $spousePledges->has($cycle->id)) {
                    $pledge = $spousePledges->get($cycle->id);
                    $pledgeFromSpouse = true;
                }

                $obligation                = $pledge ? $pledge->pledged_amount : 0;
                $cycle->pledge_amount      = $pledge ? $pledge->pledged_amount : null;
                $cycle->pledge_from_spouse = $pledgeFromSpouse;
                $cycle->pledge_is_shared   = $pledge ? (bool) $pledge->shared_with_spouse : false;
                $cycle->my_items           = $myItemsByCycle->get($cycle->id, collect());
            } else {
                $obligation                = $member->obligationFor($cycle);
                $cycle->pledge_amount      = null;
                $cycle->pledge_from_spouse = false;
                $cycle->pledge_is_shared   = false;
                $cycle->my_items           = collect();
            }

            $mergeWithSpouse = $cycle->is_pledge_based ? $cycle->pledge_is_shared : $cycle->couple_shared;
            $paid            = $member->totalPaidWithSpouse($cycle->id, $mergeWithSpouse);

            $cycle->user_obligation   = $obligation;
            $cycle->user_paid         = $paid;
            // Left signed (can be negative — a credit) so totals can net it correctly;
            // callers that only want "what's still owed" should filter > 0 themselves.
            $cycle->user_remaining    = $obligation - $paid;
            $cycle->user_percent      = $obligation > 0 ? min(100, round(($paid / $obligation) * 100)) : 0;
            $cycle->is_family_billing = $member->hasSpouse() && $mergeWithSpouse;
            return $cycle;
        };

        $activeCycles = DuesCycle::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get()
            ->map($pledgeCycleMapper);

        // Legacy outstanding (pre-2026 carryover)
        $legacyBalances = MemberLegacyBalance::where('user_id', $member->id)
            ->orderBy('year')->orderBy('label')->get();
        $legacyTotal = $legacyBalances->sum('amount');

        // 2026+ outstanding from cycle data. Sum the total from the full (unfiltered)
        // collection so a credit on one cycle (e.g. legacy carryover folded into Annual
        // Dues, see User::obligationFor()) still nets into the total correctly, before
        // filtering down to only the cycles actually still owed for display.
        $spouseName = $spouse ? $spouse->name : null;
        $allCurrentCycles = DuesCycle::whereIn('status', ['active', 'closed'])
            ->where('start_date', '>=', '2026-01-01')
            ->orderBy('start_date')
            ->get()
            ->map($pledgeCycleMapper)
            ->each(function ($cycle) use ($spouseName) {
                $cycle->spouse_name = $spouseName;
            });

        $currentTotal  = $allCurrentCycles->sum('user_remaining');
        $currentCycles = $allCurrentCycles->where('user_remaining', '>', 0);

        // Legacy is already inside $currentTotal via the Annual Dues cycle's obligation
        // whenever a current yearly-dues cycle exists — unfoldedLegacyBalance() is only
        // non-zero as a fallback for the (currently theoretical) case where none does.
        $totalOutstanding = $currentTotal + $member->unfoldedLegacyBalance();

        return view('admin.members.show', compact(
            'member', 'spouse', 'children', 'activeCycles',
            'legacyBalances', 'legacyTotal', 'currentCycles', 'currentTotal', 'totalOutstanding'
        ));
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

    /**
     * Update a member's contact details (phone/email). Super admin only.
     */
    public function updateContact(Request $request, User $member)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $request->validate([
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($member->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($member->id)],
        ]);

        $member->update([
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        return back()->with('success', 'Contact details updated.');
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
