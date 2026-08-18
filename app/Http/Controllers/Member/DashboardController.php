<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\DonationItem;
use App\Models\DuesCycle;
use App\Models\MeetingMinutes;
use App\Models\MemberLegacyBalance;
use App\Models\MemberPledge;
use App\Models\Meeting;
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

        // Spouse's pledges that were explicitly marked "shared" — used as a fallback
        // when this member hasn't pledged individually for a pledge-based cycle.
        $spousePledges = $spouse
            ? MemberPledge::where('user_id', $spouse->id)->where('shared_with_spouse', true)->get()->keyBy('dues_cycle_id')
            : collect();

        $cycleMapper = function ($cycle) use ($user, $myPledges, $myItemsByCycle, $spouse, $spouseName, $spousePledges) {
            $pledgeFromSpouse = false;

            if ($cycle->is_pledge_based) {
                $pledge = $myPledges->get($cycle->id);

                if (! $pledge && $spousePledges->has($cycle->id)) {
                    $pledge = $spousePledges->get($cycle->id);
                    $pledgeFromSpouse = true;
                }

                $obligation = $pledge ? $pledge->pledged_amount : 0;
                $cycle->pledge_amount           = $pledge ? $pledge->pledged_amount : null;
                $cycle->pledge_from_spouse      = $pledgeFromSpouse;
                $cycle->pledge_is_shared        = $pledge ? (bool) $pledge->shared_with_spouse : false;
                $cycle->pledge_recorded_by_self = $pledge && ! $pledgeFromSpouse && $pledge->recorded_by === $user->id;
                $cycle->my_items                = $myItemsByCycle->get($cycle->id, collect());
            } else {
                $obligation = $user->obligationFor($cycle);
                $cycle->pledge_amount           = null;
                $cycle->pledge_from_spouse      = false;
                $cycle->pledge_is_shared        = false;
                $cycle->pledge_recorded_by_self = false;
                $cycle->my_items                = collect();
            }

            // For pledge-based cycles, payments merge with the spouse only when the pledge itself is shared;
            // for fixed dues, that's still governed by the cycle-wide couple_shared setting.
            $mergeWithSpouse = $cycle->is_pledge_based ? $cycle->pledge_is_shared : $cycle->couple_shared;

            $paid      = $user->totalPaidWithSpouse($cycle->id, $mergeWithSpouse);
            // Left signed (can be negative — a credit) so totals can net it correctly;
            // callers that only want "what's still owed" should filter > 0 themselves.
            $remaining = $obligation - $paid;
            $percent   = $obligation > 0 ? min(100, round(($paid / $obligation) * 100)) : 0;

            $cycle->user_obligation   = $obligation;
            $cycle->user_paid         = $paid;
            $cycle->user_remaining    = $remaining;
            $cycle->user_percent      = $percent;
            $cycle->is_family_billing = $user->hasSpouse() && $mergeWithSpouse;
            $cycle->spouse_name       = $spouseName;
            return $cycle;
        };

        // Currently open cycles — shown in the detailed Active Dues section (newest first)
        $activeCycles = DuesCycle::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderByDesc('created_at')
            ->get()
            ->map($cycleMapper);

        // Legacy outstanding imported from worksheet (pre-2026)
        $legacyBalances = MemberLegacyBalance::where('user_id', $user->id)
            ->orderBy('year')
            ->orderBy('label')
            ->get();

        $legacyTotal = $legacyBalances->sum('amount');

        // 2026+ cycles — calculated outstanding from cycle data. Sum the total from the
        // full (unfiltered) collection so a credit on one cycle (e.g. legacy carryover
        // folded into Annual Dues, see User::obligationFor()) still nets into the total
        // correctly, before filtering down to only the cycles actually still owed for display.
        $allCurrentCycles = DuesCycle::whereIn('status', ['active', 'closed'])
            ->where('start_date', '>=', '2026-01-01')
            ->orderBy('start_date')
            ->get()
            ->map($cycleMapper);

        $currentTotal  = $allCurrentCycles->sum('user_remaining');
        $currentCycles = $allCurrentCycles->where('user_remaining', '>', 0);

        // Legacy is already inside $currentTotal via the Annual Dues cycle's obligation
        // whenever a current yearly-dues cycle exists — unfoldedLegacyBalance() is only
        // non-zero as a fallback for the (currently theoretical) case where none does.
        $totalOutstanding = $currentTotal + $user->unfoldedLegacyBalance();

        $recentPayments = Payment::where('user_id', $user->id)
            ->with('duesCycle')
            ->latest()
            ->take(5)
            ->get();

        $totalPaid = Payment::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('amount');

        // Live meeting — lets the member check in from the dashboard instead of only via QR/link
        $liveMeeting = Meeting::where('status', 'active')
            ->whereNotNull('qr_expires_at')
            ->where('qr_expires_at', '>', now())
            ->first();

        // Most recently published minutes, shown as a banner for 5 days after publishing
        $latestMinutes = MeetingMinutes::published()
            ->where('published_at', '>=', now()->subDays(5))
            ->orderByDesc('published_at')
            ->first();

        // Missed attendance summary (current year) — clicking takes them to My Attendance
        $attendanceYear = now()->year;
        $yearMeetings = Meeting::whereYear('meeting_date', $attendanceYear)
            ->whereIn('status', ['active', 'closed'])
            ->get();

        $myAttendanceRecords = AttendanceRecord::where('user_id', $user->id)
            ->whereIn('meeting_id', $yearMeetings->pluck('id'))
            ->get();

        $lateCount    = $myAttendanceRecords->where('status', 'late')->count();
        $excusedCount = $myAttendanceRecords->where('status', 'excused')->count();
        $absentCount  = $yearMeetings->count() - $myAttendanceRecords->count();

        return view('member.dashboard', compact(
            'activeCycles', 'legacyBalances', 'legacyTotal',
            'currentCycles', 'currentTotal', 'totalOutstanding',
            'recentPayments', 'totalPaid', 'liveMeeting', 'latestMinutes',
            'absentCount', 'lateCount', 'excusedCount'
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
