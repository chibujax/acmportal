<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ContactLog;
use App\Models\Meeting;
use App\Models\User;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            ->orderByDesc('meeting_time')
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
        $manualLocation = $request->boolean('manual_location');

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
            'venue_postcode'     => $manualLocation ? 'nullable|string|max:10' : 'required|string|max:10',
            'venue_radius'       => 'required|integer|min:5|max:1000',
            'gps_failure_action' => 'required|in:reject,flag',
            'venue_lat'          => $manualLocation ? 'required|numeric|between:-90,90' : 'nullable|numeric',
            'venue_lng'          => $manualLocation ? 'required|numeric|between:-180,180' : 'nullable|numeric',
            'geocode_source'     => 'nullable|string|max:50',
        ]);

        if ($manualLocation) {
            $lat = $request->venue_lat;
            $lng = $request->venue_lng;
        } else {
            $lat = $request->filled('venue_lat') ? $request->venue_lat : null;
            $lng = $request->filled('venue_lng') ? $request->venue_lng : null;

            if (is_null($lat)) {
                // Fall back to postcode geocoding
                [$lat, $lng] = $this->geocodePostcode($request->venue_postcode);

                if (is_null($lat)) {
                    return back()->withInput()
                        ->withErrors(['venue_postcode' => 'Postcode could not be found. Please check and try again, or tick "Enter location manually" below.']);
                }
            }
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

        return redirect()->route('admin.meetings.index')->with('success', 'Meeting created successfully.');
    }

    // ── Show meeting detail + attendance ─────────────────────

    public function show(Meeting $meeting)
    {
        $meeting->load('attendanceRecords.user', 'attendanceRecords.recordedBy');

        $allMembers = User::where('role', '!=', 'super_admin')
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
        $year   = $request->get('year', now()->year);
        $search = trim($request->get('search', ''));
        $sort   = in_array($request->get('sort'), ['name', 'attended', 'rate']) ? $request->get('sort') : 'name';
        $dir    = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $meetings = Meeting::whereYear('meeting_date', $year)
            ->whereIn('status', ['active', 'closed'])
            ->orderBy('meeting_date')
            ->withCount('attendanceRecords')
            ->get();

        $totalMeetings = $meetings->count();
        $totalMembers  = User::where('role', '!=', 'super_admin')->where('status', 'active')->count();

        // Aggregate stats for summary cards — always unfiltered so cards reflect the full year
        $allStats = User::where('role', '!=', 'super_admin')
            ->where('status', 'active')
            ->withCount(['attendanceRecords as attended_count' => fn($q) =>
                $q->whereIn('status', ['present', 'late'])
                  ->whereHas('meeting', fn($mq) => $mq->whereYear('meeting_date', $year)
                    ->whereIn('status', ['active', 'closed']))
            ])
            ->get();

        $avgRate       = $totalMeetings > 0
            ? round($allStats->avg(fn($u) => ($u->attended_count / $totalMeetings) * 100), 1)
            : 0;
        $eligibleCount = $allStats->filter(
            fn($u) => $totalMeetings > 0 && ($u->attended_count / $totalMeetings * 100) >= 70
        )->count();

        // Per-member table query with search + sort
        $perPage = in_array((int) $request->get('per_page'), [10, 20, 50, 100]) ? (int) $request->get('per_page') : 20;

        $memberQuery = User::where('role', '!=', 'super_admin')
            ->where('status', 'active')
            ->withCount(['attendanceRecords as attended_count' => fn($q) =>
                $q->whereIn('status', ['present', 'late'])
                  ->whereHas('meeting', fn($mq) => $mq->whereYear('meeting_date', $year)
                    ->whereIn('status', ['active', 'closed']))
            ]);

        if ($search !== '') {
            $memberQuery->where('name', 'like', "%{$search}%");
        }

        $memberQuery->orderBy(
            $sort === 'name' ? 'name' : 'attended_count',
            // 'rate' sorts by attended_count since rate is proportional to it
            $dir
        );

        $memberStats = $memberQuery
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
            'avgRate', 'eligibleCount', 'search', 'sort', 'dir'
        ));
    }

    // ── Edit meeting ─────────────────────────────────────────

    public function edit(Meeting $meeting)
    {
        return view('admin.meetings.edit', compact('meeting'));
    }

    public function update(Request $request, Meeting $meeting)
    {
        $manualLocation = $request->boolean('manual_location');

        $request->validate([
            'title'              => 'required|string|max:255',
            'meeting_date'       => 'required|date',
            'meeting_time'       => 'required',
            'late_after_time'    => 'required|after:meeting_time|before:meeting_end_time',
            'meeting_end_time'   => 'required|after:meeting_time',
            'venue'              => 'required|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'venue_postcode'     => $manualLocation ? 'nullable|string|max:10' : 'required|string|max:10',
            'venue_radius'       => 'required|integer|min:5|max:1000',
            'gps_failure_action' => 'required|in:reject,flag',
            'venue_lat'          => $manualLocation ? 'required|numeric|between:-90,90' : 'nullable|numeric',
            'venue_lng'          => $manualLocation ? 'required|numeric|between:-180,180' : 'nullable|numeric',
            'geocode_source'     => 'nullable|string|max:50',
        ]);

        $postcode = strtoupper(trim($request->venue_postcode ?? ''));

        if ($manualLocation) {
            $lat = $request->venue_lat;
            $lng = $request->venue_lng;
        } else {
            $lat = $request->filled('venue_lat') ? $request->venue_lat : null;
            $lng = $request->filled('venue_lng') ? $request->venue_lng : null;

            if (is_null($lat)) {
                // Use existing coords if venue/postcode unchanged, otherwise re-geocode
                $venueChanged    = trim($request->venue) !== trim($meeting->venue ?? '');
                $postcodeChanged = $postcode !== strtoupper(trim($meeting->venue_postcode ?? ''));

                if (!$venueChanged && !$postcodeChanged && $meeting->venue_lat) {
                    $lat = $meeting->venue_lat;
                    $lng = $meeting->venue_lng;
                } else {
                    [$lat, $lng] = $this->geocodePostcode($postcode);

                    if (is_null($lat)) {
                        return back()->withInput()
                            ->withErrors(['venue_postcode' => 'Postcode could not be found. Please check and try again, or tick "Enter location manually" below.']);
                    }
                }
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

        return redirect()->route('admin.meetings.show', $meeting)->with('success', 'Meeting updated.');
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
        $year   = $request->get('year', now()->year);
        $search = trim($request->get('search', ''));
        $sort   = in_array($request->get('sort'), ['name', 'attended', 'rate']) ? $request->get('sort') : 'name';
        $dir    = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $totalMeetings = Meeting::whereYear('meeting_date', $year)
            ->whereIn('status', ['active', 'closed'])
            ->count();

        $suffix   = $search !== '' ? '-' . preg_replace('/[^a-z0-9]/i', '_', $search) : '';
        $filename = "acm-attendance-{$year}{$suffix}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($year, $totalMeetings, $search, $sort, $dir) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Name', 'Phone', 'Meetings Attended', 'Total Meetings',
                'Attendance %', 'Eligible (≥70%)',
            ]);

            $query = User::where('role', '!=', 'super_admin')
                ->where('status', 'active')
                ->withCount(['attendanceRecords as attended_count' => fn($q) =>
                    $q->whereIn('status', ['present', 'late'])
                      ->whereHas('meeting', fn($mq) => $mq->whereYear('meeting_date', $year)
                        ->whereIn('status', ['active', 'closed']))
                ]);

            if ($search !== '') {
                $query->where('name', 'like', "%{$search}%");
            }

            $query->orderBy($sort === 'name' ? 'name' : 'attended_count', $dir);

            $query->get()->each(function ($user) use ($handle, $totalMeetings) {
                $pct     = $totalMeetings > 0 ? round(($user->attended_count / $totalMeetings) * 100, 1) : 0;
                $eligible = $pct >= 70 ? 'Yes' : 'No';

                fputcsv($handle, [
                    $user->name,
                    $user->phone,
                    $user->attended_count,
                    $totalMeetings,
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
     * AJAX: look up addresses for a UK postcode via Ideal Postcodes.
     * Returns a list of addresses the user can select from.
     */
    public function verifyAddress(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'postcode' => 'required|string|max:10',
        ]);

        $postcode = strtoupper(str_replace(' ', '', trim($request->postcode)));
        $apiKey   = config('services.ideal_postcodes.key');

        try {
            $response = Http::timeout(8)
                ->get("https://api.ideal-postcodes.co.uk/v1/postcodes/{$postcode}", [
                    'api_key' => $apiKey,
                ]);

            $body = $response->json();

            if (($body['code'] ?? 0) === 2000) {
                $addresses = collect($body['result'])->map(function ($addr) {
                    $parts = array_filter([
                        $addr['line_1'] ?? '',
                        $addr['line_2'] ?? '',
                        $addr['line_3'] ?? '',
                        $addr['post_town'] ?? '',
                        $addr['postcode'] ?? '',
                    ]);
                    return [
                        'address'  => implode(', ', $parts),
                        'postcode' => $addr['postcode'] ?? '',
                        'lat'      => (float) ($addr['latitude'] ?? 0),
                        'lng'      => (float) ($addr['longitude'] ?? 0),
                    ];
                })->values()->toArray();

                return response()->json(['success' => true, 'addresses' => $addresses]);
            }

            if (($body['code'] ?? 0) === 4040) {
                return response()->json(['success' => false, 'message' => 'Postcode not found. Please check and try again.']);
            }

            Log::warning('verifyAddress: unexpected response from Ideal Postcodes', [
                'postcode' => $postcode,
                'body'     => $body,
            ]);
        } catch (\Throwable $e) {
            Log::error('verifyAddress: request to Ideal Postcodes failed', [
                'postcode' => $postcode,
                'error'    => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Could not look up this postcode. Please try again.']);
    }

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

            Log::warning('geocodePostcode: unexpected response from postcodes.io', [
                'postcode' => $postcode,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('geocodePostcode: request to postcodes.io failed', [
                'postcode' => $postcode,
                'error'    => $e->getMessage(),
            ]);
        }

        return [null, null];
    }

    // ── Send SMS / Email to absent members ───────────────────

    public function sendAbsenteeSms(Request $request, Meeting $meeting)
    {
        $channel = $request->input('channel', 'sms');

        $rules = [
            'user_ids'   => 'required|array',
            'user_ids.*' => 'integer',
            'channel'    => 'required|in:sms,email',
            'message'    => 'required|string' . ($channel === 'sms' ? '|max:160' : ''),
        ];
        if ($channel === 'email') {
            $rules['subject'] = 'required|string|max:255';
        }
        $request->validate($rules);

        $placeholders = ['{name}', '{meeting}', '{date}'];
        $sent   = 0;
        $failed = 0;
        $batchId = (string) Str::uuid();

        if ($channel === 'email') {
            $absentees = User::whereIn('id', $request->user_ids)->whereNotNull('email')->get();
            $email = app(EmailService::class);
            foreach ($absentees as $member) {
                $values  = [$member->name, $meeting->title, $meeting->meeting_date->format('d M Y')];
                $subject = str_replace($placeholders, $values, $request->subject);
                $body    = str_replace($placeholders, $values, $request->message);
                $ok = $email->send($member->email, $subject, $body);
                $ok ? $sent++ : $failed++;

                ContactLog::create([
                    'user_id'    => $member->id,
                    'batch_id'   => $batchId,
                    'channel'    => 'email',
                    'subject'    => $subject,
                    'message'    => $body,
                    'context'    => 'meeting_absentee',
                    'meeting_id' => $meeting->id,
                    'status'     => $ok ? 'sent' : 'failed',
                    'sent_by'    => auth()->id(),
                    'created_at' => now(),
                ]);
            }
            $label = 'email';
        } else {
            $absentees = User::whereIn('id', $request->user_ids)->whereNotNull('phone')->get();
            $sms = app(SmsService::class);
            foreach ($absentees as $member) {
                $values  = [$member->name, $meeting->title, $meeting->meeting_date->format('d M Y')];
                $message = str_replace($placeholders, $values, $request->message);
                $ok = $sms->send($member->phone, $message);
                $ok ? $sent++ : $failed++;

                ContactLog::create([
                    'user_id'    => $member->id,
                    'batch_id'   => $batchId,
                    'channel'    => 'sms',
                    'message'    => $message,
                    'context'    => 'meeting_absentee',
                    'meeting_id' => $meeting->id,
                    'status'     => $ok ? 'sent' : 'failed',
                    'sent_by'    => auth()->id(),
                    'created_at' => now(),
                ]);
            }
            $label = 'SMS';
        }

        $msg = "{$label} sent to {$sent} absent member(s).";
        if ($failed > 0) {
            $msg .= " {$failed} failed — check the application logs for details.";
        }

        return back()->with($failed > 0 ? 'warning' : 'success', $msg);
    }

    // ── Members absent from last N consecutive meetings ──────

    public function consecutiveAbsentees(Request $request)
    {
        return view('admin.meetings.consecutive_absentees', $this->buildConsecutiveAbsenteesData($request));
    }

    // ── Export consecutive absentees as CSV ────────────────────

    public function exportConsecutiveAbsentees(Request $request)
    {
        $data = $this->buildConsecutiveAbsenteesData($request);

        $filename = 'consecutive-absentees-' . $data['count'] . '-meetings-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Name', 'Missed', 'Phone', 'Email']);

            foreach ($data['members'] as $m) {
                fputcsv($handle, [
                    $m->name,
                    $m->missed_count . ' meeting' . ($m->missed_count === 1 ? '' : 's'),
                    $m->phone ?? '',
                    $m->email ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Shared query logic for consecutive-absentees view/export ──

    private function buildConsecutiveAbsenteesData(Request $request): array
    {
        $allMeetingsThisYear = Meeting::whereIn('status', ['active', 'closed'])
            ->whereYear('meeting_date', now()->year)
            ->orderByDesc('meeting_date')
            ->orderByDesc('meeting_time')
            ->get();

        $maxCount = $allMeetingsThisYear->count();

        if ($maxCount < 2) {
            return [
                'members'      => collect(),
                'lastMeetings' => $allMeetingsThisYear,
                'enough'       => false,
                'maxCount'     => $maxCount,
                'count'        => $maxCount,
            ];
        }

        $count = max(2, min((int) $request->input('count', min(3, $maxCount)), $maxCount));

        $lastMeetings = $allMeetingsThisYear->take($count);
        $meetingIds   = $lastMeetings->pluck('id');

        // Members who attended at least one of the selected meetings
        $attendedAny = AttendanceRecord::whereIn('meeting_id', $meetingIds)
            ->pluck('user_id')
            ->unique();

        $candidates = User::where('role', '!=', 'super_admin')
            ->where('status', 'active')
            ->whereNotIn('id', $attendedAny)
            ->orderBy('name')
            ->get();

        // Only include members who were already members for every meeting in
        // the selected window — someone who joined partway through hasn't
        // missed all $count meetings, they've only missed the ones that
        // occurred after they joined, so they don't belong on this list.
        $members = $candidates->map(function ($member) use ($lastMeetings, $count) {
            $joinDate   = $member->memberSince()->startOfDay();
            $applicable = $lastMeetings->filter(fn ($m) => $m->meeting_date->gte($joinDate));

            if ($applicable->count() < $count) {
                return null;
            }

            $member->missed_count = $applicable->count();

            return $member;
        })->filter()->values();

        return [
            'members'      => $members,
            'lastMeetings' => $lastMeetings,
            'enough'       => true,
            'maxCount'     => $maxCount,
            'count'        => $count,
        ];
    }

    // ── Send SMS / Email to consecutive absentees ─────────────

    public function sendConsecutiveAbsenteeSms(Request $request)
    {
        $channel = $request->input('channel', 'sms');

        $rules = [
            'user_ids'   => 'required|array',
            'user_ids.*' => 'integer',
            'channel'    => 'required|in:sms,email',
            'message'    => 'required|string' . ($channel === 'sms' ? '|max:160' : ''),
        ];
        if ($channel === 'email') {
            $rules['subject'] = 'required|string|max:255';
        }
        $request->validate($rules);

        $sent   = 0;
        $failed = 0;
        $batchId = (string) Str::uuid();

        if ($channel === 'email') {
            $members = User::whereIn('id', $request->user_ids)->whereNotNull('email')->get();
            $email   = app(EmailService::class);
            foreach ($members as $member) {
                $subject = str_replace('{name}', $member->name, $request->subject);
                $body    = str_replace('{name}', $member->name, $request->message);
                $ok = $email->send($member->email, $subject, $body);
                $ok ? $sent++ : $failed++;

                ContactLog::create([
                    'user_id'    => $member->id,
                    'batch_id'   => $batchId,
                    'channel'    => 'email',
                    'subject'    => $subject,
                    'message'    => $body,
                    'context'    => 'consecutive_absentee',
                    'status'     => $ok ? 'sent' : 'failed',
                    'sent_by'    => auth()->id(),
                    'created_at' => now(),
                ]);
            }
            $label = 'Email';
        } else {
            $members = User::whereIn('id', $request->user_ids)->whereNotNull('phone')->get();
            $sms     = app(SmsService::class);
            foreach ($members as $member) {
                $message = str_replace('{name}', $member->name, $request->message);
                $ok = $sms->send($member->phone, $message);
                $ok ? $sent++ : $failed++;

                ContactLog::create([
                    'user_id'    => $member->id,
                    'batch_id'   => $batchId,
                    'channel'    => 'sms',
                    'message'    => $message,
                    'context'    => 'consecutive_absentee',
                    'status'     => $ok ? 'sent' : 'failed',
                    'sent_by'    => auth()->id(),
                    'created_at' => now(),
                ]);
            }
            $label = 'SMS';
        }

        $msg = "{$label} sent to {$sent} member(s).";
        if ($failed > 0) {
            $msg .= " {$failed} failed — check logs.";
        }

        return redirect()->route('admin.meetings.consecutive-absentees')
            ->with($failed > 0 ? 'warning' : 'success', $msg);
    }
}
