<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Meeting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckInController extends Controller
{
    /**
     * QR scan landing page — validate meeting, show identity confirmation.
     * GET /attend/{token}  (auth middleware ensures user is logged in)
     */
    public function show(string $token)
    {
        $meeting = $this->resolveMeeting($token);

        if (is_string($meeting)) {
            return view('attendance.checkin', ['error' => $meeting]);
        }

        $user = auth()->user();

        // Active member guard
        if ($user->status !== 'active') {
            return view('attendance.checkin', [
                'error'   => 'Your account is not active. Please contact an administrator.',
                'meeting' => $meeting,
            ]);
        }

        // Already checked in?
        $existing = AttendanceRecord::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return view('attendance.checkin', [
                'meeting' => $meeting,
                'already' => true,
                'record'  => $existing,
            ]);
        }

        // Show identity confirmation screen
        return view('attendance.checkin', [
            'meeting' => $meeting,
            'confirm' => true,
            'user'    => $user,
        ]);
    }

    /**
     * Log out the current user and redirect back to the QR URL so they can sign in as someone else.
     * POST /attend/{token}/switch
     */
    public function switchUser(Request $request, string $token)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        session(['url.intended' => route('attendance.checkin', $token)]);

        return redirect()->route('login');
    }

    /**
     * Receive GPS coordinates from the browser and mark attendance.
     * POST /attend/{token}/checkin
     */
    public function checkin(Request $request, string $token)
    {
        $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        $meeting = $this->resolveMeeting($token);

        if (is_string($meeting)) {
            return response()->json(['error' => $meeting], 422);
        }

        $user = auth()->user();

        if ($user->status !== 'active') {
            return response()->json(['error' => 'Your account is not active.'], 403);
        }

        // Race-condition guard
        if ($meeting->hasCheckedIn($user->id)) {
            return response()->json(['already' => true]);
        }

        $gpsLat   = $request->filled('lat') ? (float) $request->lat : null;
        $gpsLng   = $request->filled('lng') ? (float) $request->lng : null;
        $distance = null;
        $mismatch = false;

        if ($meeting->hasLocation()) {
            if (is_null($gpsLat) || is_null($gpsLng)) {
                return response()->json([
                    'gps_error' => 'location_denied',
                    'message'   => 'Location access is required to check in. Please allow location and try again, or contact an admin.',
                ], 422);
            }

            $distance = (int) round($meeting->distanceTo($gpsLat, $gpsLng));

            if ($distance > $meeting->venue_radius) {
                if ($meeting->gps_failure_action === 'reject') {
                    return response()->json([
                        'gps_error' => 'out_of_range',
                        'distance'  => $distance,
                        'radius'    => $meeting->venue_radius,
                        'message'   => "You appear to be {$distance}m away from the venue (limit: {$meeting->venue_radius}m). Please contact an admin for manual check-in.",
                    ], 422);
                }
                $mismatch = true;
            }
        }

        // Determine late: compare against late_after_time if set, else 15 min after meeting start
        $lateThreshold = $meeting->late_after_time
            ? Carbon::parse($meeting->meeting_date->format('Y-m-d') . ' ' . $meeting->late_after_time)
            : Carbon::parse($meeting->meeting_date->format('Y-m-d') . ' ' . $meeting->meeting_time)->addMinutes(15);

        $isLate = now()->gt($lateThreshold);

        $record = AttendanceRecord::create([
            'meeting_id'        => $meeting->id,
            'user_id'           => $user->id,
            'check_in_time'     => now(),
            'check_in_method'   => 'qr_scan',
            'status'            => $isLate ? 'late' : 'present',
            'gps_lat'           => $gpsLat,
            'gps_lng'           => $gpsLng,
            'gps_distance'      => $distance,
            'location_mismatch' => $mismatch,
        ]);

        return response()->json([
            'success'  => true,
            'isLate'   => $isLate,
            'mismatch' => $mismatch,
            'distance' => $distance,
            'recordId' => $record->id,
        ]);
    }

    /**
     * Validate token and return a Meeting or an error string.
     */
    private function resolveMeeting(string $token): Meeting|string
    {
        $meeting = Meeting::where('qr_token', $token)->first();

        if (! $meeting) {
            return 'Invalid QR code. Please scan the QR code displayed at the meeting.';
        }
        if ($meeting->status === 'scheduled') {
            return 'Check-in is not open yet. Please wait for the meeting to start.';
        }
        if ($meeting->status === 'closed') {
            return 'This meeting has been closed. Check-ins are no longer accepted.';
        }
        if ($meeting->isExpiredQr()) {
            return 'The QR code for this meeting has expired. Please see an administrator for manual check-in.';
        }

        return $meeting;
    }
}
