<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MeetingController extends Controller
{
    // ── List all meetings ────────────────────────────────────

    public function index(Request $request)
    {
        $year = $request->get('year', now()->year);

        $meetings = Meeting::withCount('attendanceRecords')
            ->whereYear('meeting_date', $year)
            ->orderByDesc('meeting_date')
            ->paginate(20)
            ->withQueryString();

        $years = Meeting::selectRaw('YEAR(meeting_date) as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y');

        return view('admin.meetings.index', compact('meetings', 'year', 'years'));
    }

    // ── Create / Store ───────────────────────────────────────

    public function create()
    {
        return view('admin.meetings.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'              => 'required|string|max:255',
            'meeting_date'       => 'required|date',
            'meeting_time'       => 'required',
            'venue'              => 'nullable|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'venue_postcode'     => 'required|string|max:10',
            'venue_radius'       => 'nullable|integer|min:50|max:1000',
            'gps_failure_action' => 'nullable|in:reject,flag',
        ]);

        [$lat, $lng] = $this->geocodePostcode($request->venue_postcode);

        if (is_null($lat)) {
            return back()->withInput()
                ->withErrors(['venue_postcode' => 'Postcode could not be found. Please check and try again.']);
        }

        Meeting::create([
            'title'              => $request->title,
            'meeting_date'       => $request->meeting_date,
            'meeting_time'       => $request->meeting_time,
            'venue'              => $request->venue,
            'description'        => $request->description,
            'venue_postcode'     => strtoupper(trim($request->venue_postcode)),
            'venue_lat'          => $lat,
            'venue_lng'          => $lng,
            'venue_radius'       => $request->venue_radius ?? 150,
            'gps_failure_action' => $request->gps_failure_action ?? 'reject',
            'status'             => 'scheduled',
            'created_by'         => auth()->id(),
        ]);

        return redirect()->route('admin.meetings.index')
            ->with('success', 'Meeting created successfully.');
    }

    // ── Show meeting detail + attendance ─────────────────────

    public function show(Meeting $meeting)
    {
        $meeting->load('attendanceRecords.user');

        $allMembers = User::where('role', 'member')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $attendedIds = $meeting->attendanceRecords->pluck('user_id')->toArray();

        $absentees = $allMembers->whereNotIn('id', $attendedIds)->values();

        $qrUrl = $meeting->isActive()
            ? route('attendance.checkin', $meeting->qr_token)
            : null;

        return view('admin.meetings.show', compact('meeting', 'allMembers', 'absentees', 'qrUrl'));
    }

    // ── Activate QR ──────────────────────────────────────────

    public function activate(Meeting $meeting)
    {
        if ($meeting->status === 'closed') {
            return back()->withErrors(['error' => 'Cannot reactivate a closed meeting.']);
        }

        $meeting->activate(durationHours: 4);

        return back()->with('success', 'Meeting is now LIVE. QR code is active for 4 hours.');
    }

    // ── Close meeting ─────────────────────────────────────────

    public function close(Meeting $meeting)
    {
        $meeting->close();

        return back()->with('success', 'Meeting closed. No more check-ins accepted.');
    }

    // ── Manual check-in (admin action from meeting detail) ───

    public function manualCheckIn(Request $request, Meeting $meeting)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes'   => 'nullable|string|max:255',
        ]);

        if ($meeting->status === 'closed' && ! auth()->user()->isAdmin()) {
            return back()->withErrors(['error' => 'Meeting is closed.']);
        }

        // Prevent duplicate
        $exists = AttendanceRecord::where('meeting_id', $meeting->id)
            ->where('user_id', $request->user_id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'This member is already checked in.']);
        }

        AttendanceRecord::create([
            'meeting_id'       => $meeting->id,
            'user_id'          => $request->user_id,
            'check_in_time'    => now(),
            'check_in_method'  => 'manual',
            'status'           => 'present',
            'notes'            => $request->notes,
            'recorded_by'      => auth()->id(),
        ]);

        return back()->with('success', 'Member checked in manually.');
    }

    // ── Remove a check-in ────────────────────────────────────

    public function removeCheckIn(Meeting $meeting, AttendanceRecord $record)
    {
        if ($record->meeting_id !== $meeting->id) {
            abort(404);
        }

        $record->delete();

        return back()->with('success', 'Check-in removed.');
    }

    // ── Yearly attendance report ──────────────────────────────

    public function report(Request $request)
    {
        $year = $request->get('year', now()->year);

        $meetings = Meeting::whereYear('meeting_date', $year)
            ->whereIn('status', ['active', 'closed'])
            ->orderBy('meeting_date')
            ->withCount('attendanceRecords')
            ->get();

        $totalMeetings = $meetings->count();
        $totalMembers  = User::where('role', 'member')->where('status', 'active')->count();

        // Per-member stats
        $memberStats = User::where('role', 'member')
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($year, $totalMeetings) {
                $attended = $user->attendanceRecords()
                    ->whereHas('meeting', fn($q) => $q->whereYear('meeting_date', $year)
                        ->whereIn('status', ['active', 'closed']))
                    ->count();

                $percentage = $totalMeetings > 0
                    ? round(($attended / $totalMeetings) * 100, 1)
                    : 0;

                $user->attended   = $attended;
                $user->percentage = $percentage;
                $user->eligible   = $percentage >= 70; // 70% threshold per spec
                return $user;
            });

        // Chart data – attendance count per month
        $chartData = $meetings->groupBy(fn($m) => $m->meeting_date->month)
            ->map(fn($group) => $group->sum('attendance_records_count'))
            ->toArray();

        $monthlyChart = array_map(fn($m) => $chartData[$m] ?? 0, range(1, 12));

        $years = Meeting::selectRaw('YEAR(meeting_date) as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y');

        return view('admin.meetings.report', compact(
            'meetings', 'memberStats', 'year', 'years',
            'totalMeetings', 'totalMembers', 'monthlyChart'
        ));
    }

    // ── Edit meeting ─────────────────────────────────────────

    public function edit(Meeting $meeting)
    {
        return view('admin.meetings.edit', compact('meeting'));
    }

    public function update(Request $request, Meeting $meeting)
    {
        $request->validate([
            'title'              => 'required|string|max:255',
            'meeting_date'       => 'required|date',
            'meeting_time'       => 'required',
            'venue'              => 'nullable|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'venue_postcode'     => 'required|string|max:10',
            'venue_radius'       => 'nullable|integer|min:50|max:1000',
            'gps_failure_action' => 'nullable|in:reject,flag',
        ]);

        $postcode = strtoupper(trim($request->venue_postcode));
        $lat      = $meeting->venue_lat;
        $lng      = $meeting->venue_lng;

        // Only re-geocode if postcode changed
        if ($postcode !== strtoupper(trim($meeting->venue_postcode ?? ''))) {
            [$lat, $lng] = $this->geocodePostcode($postcode);

            if (is_null($lat)) {
                return back()->withInput()
                    ->withErrors(['venue_postcode' => 'Postcode could not be found. Please check and try again.']);
            }
        }

        $meeting->update([
            'title'              => $request->title,
            'meeting_date'       => $request->meeting_date,
            'meeting_time'       => $request->meeting_time,
            'venue'              => $request->venue,
            'description'        => $request->description,
            'venue_postcode'     => $postcode,
            'venue_lat'          => $lat,
            'venue_lng'          => $lng,
            'venue_radius'       => $request->venue_radius ?? 150,
            'gps_failure_action' => $request->gps_failure_action ?? 'reject',
        ]);

        return redirect()->route('admin.meetings.show', $meeting)
            ->with('success', 'Meeting updated.');
    }

    // ── Helpers ───────────────────────────────────────────────

    /**
     * Look up a UK postcode via postcodes.io and return [lat, lng] or [null, null].
     */
    private function geocodePostcode(string $postcode): array
    {
        try {
            $response = Http::timeout(5)
                ->get('https://api.postcodes.io/postcodes/' . urlencode($postcode));

            if ($response->successful() && $response->json('status') === 200) {
                return [
                    $response->json('result.latitude'),
                    $response->json('result.longitude'),
                ];
            }
        } catch (\Exception) {
            // Network error — treat as not found
        }

        return [null, null];
    }
}
