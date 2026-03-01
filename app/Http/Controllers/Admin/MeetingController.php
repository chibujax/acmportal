<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Meeting;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MeetingController extends Controller
{
    // ── List all meetings ────────────────────────────────────

    public function index(Request $request)
    {
        $year = $request->get('year', now()->year);

        $perPage = in_array((int) $request->get('per_page'), [10, 20, 50, 100]) ? (int) $request->get('per_page') : 20;
        $meetings = Meeting::withCount('attendanceRecords')
            ->whereYear('meeting_date', $year)
            ->orderByDesc('meeting_date')
            ->paginate($perPage)
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
            'meeting_date'       => 'required|date|after_or_equal:today',
            'meeting_time'       => ['required', function ($attribute, $value, $fail) use ($request) {
                if ($request->meeting_date) {
                    $datetime = \Carbon\Carbon::parse($request->meeting_date . ' ' . $value);
                    if ($datetime->isPast()) {
                        $fail('The meeting start time cannot be in the past.');
                    }
                }
            }],
            'late_after_time'    => 'required|after:meeting_time|before:meeting_end_time',
            'meeting_end_time'   => 'required|after:meeting_time',
            'venue'              => 'required|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'venue_postcode'     => 'required|string|max:10',
            'venue_radius'       => 'required|integer|min:50|max:1000',
            'gps_failure_action' => 'required|in:reject,flag',
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
            'late_after_time'    => $request->late_after_time,
            'meeting_end_time'   => $request->meeting_end_time,
            'venue'              => $request->venue,
            'description'        => $request->description,
            'venue_postcode'     => strtoupper(trim($request->venue_postcode)),
            'venue_lat'          => $lat,
            'venue_lng'          => $lng,
            'venue_radius'       => $request->venue_radius,
            'gps_failure_action' => $request->gps_failure_action,
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

        $meeting->activate();

        $expiryLabel = $meeting->meeting_end_time
            ? 'until ' . \Carbon\Carbon::parse($meeting->meeting_end_time)->format('g:i A')
            : 'for 4 hours';

        return back()->with('success', "Meeting is now LIVE. QR code is active {$expiryLabel}.");
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

    // ── Mark member as excused ───────────────────────────────

    public function markExcused(Request $request, Meeting $meeting)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes'   => 'nullable|string|max:255',
        ]);

        // Prevent duplicate
        $exists = AttendanceRecord::where('meeting_id', $meeting->id)
            ->where('user_id', $request->user_id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['excused_error' => 'This member already has an attendance record for this meeting.']);
        }

        AttendanceRecord::create([
            'meeting_id'      => $meeting->id,
            'user_id'         => $request->user_id,
            'check_in_time'   => now(),
            'check_in_method' => 'excused',
            'status'          => 'excused',
            'notes'           => $request->notes ?: 'Excused / Apologies',
            'recorded_by'     => auth()->id(),
        ]);

        return back()->with('success', 'Member marked as excused.');
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

        // Aggregate stats across all members for summary cards (single DB query via withCount)
        $allStats = User::where('role', 'member')
            ->where('status', 'active')
            ->withCount(['attendanceRecords as attended_count' => fn($q) =>
                $q->whereHas('meeting', fn($mq) => $mq->whereYear('meeting_date', $year)
                    ->whereIn('status', ['active', 'closed']))
            ])
            ->get();

        $avgRate       = $totalMeetings > 0
            ? round($allStats->avg(fn($u) => ($u->attended_count / $totalMeetings) * 100), 1)
            : 0;
        $eligibleCount = $allStats->filter(
            fn($u) => $totalMeetings > 0 && ($u->attended_count / $totalMeetings * 100) >= 70
        )->count();

        // Paginated per-member stats for the table
        $perPage = in_array((int) $request->get('per_page'), [10, 20, 50, 100]) ? (int) $request->get('per_page') : 20;
        $memberStats = User::where('role', 'member')
            ->where('status', 'active')
            ->orderBy('name')
            ->withCount(['attendanceRecords as attended_count' => fn($q) =>
                $q->whereHas('meeting', fn($mq) => $mq->whereYear('meeting_date', $year)
                    ->whereIn('status', ['active', 'closed']))
            ])
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($user) use ($totalMeetings) {
                $user->attended   = $user->attended_count;
                $user->percentage = $totalMeetings > 0 ? round(($user->attended_count / $totalMeetings) * 100, 1) : 0;
                $user->eligible   = $user->percentage >= 70;
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
            'totalMeetings', 'totalMembers', 'monthlyChart',
            'avgRate', 'eligibleCount'
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
            'late_after_time'    => 'required|after:meeting_time|before:meeting_end_time',
            'meeting_end_time'   => 'required|after:meeting_time',
            'venue'              => 'required|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'venue_postcode'     => 'required|string|max:10',
            'venue_radius'       => 'required|integer|min:50|max:1000',
            'gps_failure_action' => 'required|in:reject,flag',
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
            'late_after_time'    => $request->late_after_time,
            'meeting_end_time'   => $request->meeting_end_time,
            'venue'              => $request->venue,
            'description'        => $request->description,
            'venue_postcode'     => $postcode,
            'venue_lat'          => $lat,
            'venue_lng'          => $lng,
            'venue_radius'       => $request->venue_radius,
            'gps_failure_action' => $request->gps_failure_action,
        ]);

        return redirect()->route('admin.meetings.show', $meeting)
            ->with('success', 'Meeting updated.');
    }

    // ── Export per-meeting attendance as CSV ─────────────────

    public function exportMeeting(Meeting $meeting)
    {
        $meeting->load('attendanceRecords.user');

        $filename = 'attendance-' . str_replace(' ', '-', strtolower($meeting->title))
                  . '-' . $meeting->meeting_date->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($meeting) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'Name', 'Phone', 'Check-In Time', 'Method', 'Status',
                'GPS Flagged', 'Distance (m)', 'Notes',
            ]);

            foreach ($meeting->attendanceRecords->sortBy('check_in_time') as $record) {
                fputcsv($handle, [
                    $record->user->name,
                    $record->user->phone,
                    $record->check_in_time->format('H:i:s'),
                    $record->check_in_method,
                    $record->status,
                    $record->location_mismatch ? 'Yes' : 'No',
                    $record->gps_distance ?? '',
                    $record->notes ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Export yearly attendance report as CSV ────────────────

    public function exportReport(Request $request)
    {
        $year = $request->get('year', now()->year);

        $meetings = Meeting::whereYear('meeting_date', $year)
            ->whereIn('status', ['active', 'closed'])
            ->count();

        $filename = "acm-attendance-report-{$year}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($year, $meetings) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Name', 'Phone', 'Meetings Attended', 'Total Meetings',
                'Attendance %', 'Eligible (≥70%)',
            ]);

            User::where('role', 'member')
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->each(function ($user) use ($handle, $year, $meetings) {
                    $attended = $user->attendanceRecords()
                        ->whereHas('meeting', fn($q) => $q->whereYear('meeting_date', $year)
                            ->whereIn('status', ['active', 'closed']))
                        ->count();

                    $pct     = $meetings > 0 ? round(($attended / $meetings) * 100, 1) : 0;
                    $eligible = $pct >= 70 ? 'Yes' : 'No';

                    fputcsv($handle, [
                        $user->name,
                        $user->phone,
                        $attended,
                        $meetings,
                        $pct . '%',
                        $eligible,
                    ]);
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
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

    // ── Send SMS to absent members ────────────────────────────

    public function sendAbsenteeSms(Request $request, Meeting $meeting)
    {
        $request->validate([
            'user_ids'   => 'required|array',
            'user_ids.*' => 'integer',
            'message'    => 'required|string|max:160',
        ]);

        $absentees = User::whereIn('id', $request->user_ids)
            ->whereNotNull('phone')
            ->get();

        $sms    = app(SmsService::class);
        $sent   = 0;
        $failed = 0;

        foreach ($absentees as $member) {
            $message = str_replace(
                ['{name}', '{meeting}', '{date}'],
                [$member->name, $meeting->title, $meeting->meeting_date->format('d M Y')],
                $request->message
            );
            $sms->send($member->phone, $message) ? $sent++ : $failed++;
        }

        $msg = "SMS sent to {$sent} absent member(s).";
        if ($failed > 0) {
            $msg .= " {$failed} failed — check the application logs for details.";
        }

        return back()->with($failed > 0 ? 'warning' : 'success', $msg);
    }
}
