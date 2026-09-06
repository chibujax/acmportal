<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Meeting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckInController extends Controller
{
    // Never grant more than this much benefit-of-the-doubt from a device's reported
    // accuracy, so a very poor (e.g. cell-tower/IP) fix can't check someone in from afar.
    private const ACCURACY_GRACE_CAP_M = 100;

    // Accuracy at/above this is "poor" - worth telling the member about instead of a
    // flat "too far" (typical outdoor GPS is 5-20m; indoor/network fallback often exceeds this).
    private const POOR_ACCURACY_THRESHOLD_M = 50;

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
            'lat'      => 'nullable|numeric|between:-90,90',
            'lng'      => 'nullable|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
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
        $accuracy = $request->filled('accuracy') ? (float) $request->accuracy : null;
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
                // A poor GPS/network fix reports a large accuracy radius (the device is
                // unsure of its own position by that many metres) - give the benefit of
                // the doubt up to a capped grace so a shaky signal indoors doesn't reject
                // someone who is plausibly at the venue. Capped so a wildly inaccurate
                // (e.g. cell-tower/IP-based) fix can't be used to check in from anywhere.
                $grace       = $accuracy !== null ? min($accuracy, self::ACCURACY_GRACE_CAP_M) : 0;
                $withinGrace = ($distance - $grace) <= $meeting->venue_radius;

                if ($withinGrace) {
                    Log::info('GPS check-in accepted within accuracy grace', [
                        'meeting_id' => $meeting->id,
                        'user_id'    => $user->id,
                        'distance_m' => $distance,
                        'radius_m'   => $meeting->venue_radius,
                        'accuracy_m' => $accuracy,
                    ]);
                } elseif ($meeting->gps_failure_action === 'reject') {
                    Log::warning('GPS check-in rejected: reported distance exceeds venue radius', [
                        'meeting_id' => $meeting->id,
                        'user_id'    => $user->id,
                        'user_lat'   => $gpsLat,
                        'user_lng'   => $gpsLng,
                        'venue_lat'  => $meeting->venue_lat,
                        'venue_lng'  => $meeting->venue_lng,
                        'distance_m' => $distance,
                        'radius_m'   => $meeting->venue_radius,
                        'accuracy_m' => $accuracy,
                    ]);

                    // Poor accuracy is a plausible innocent explanation - tell the member
                    // so they can try again with a better signal. Otherwise (accuracy was
                    // fine, or unknown) they're genuinely far away, so keep it generic and
                    // don't hand out coordinates/accuracy for no reason.
                    if ($accuracy !== null && $accuracy >= self::POOR_ACCURACY_THRESHOLD_M) {
                        $accuracyRounded = (int) round($accuracy);

                        return response()->json([
                            'gps_error' => 'low_accuracy_out_of_range',
                            'distance'  => $distance,
                            'radius'    => $meeting->venue_radius,
                            'accuracy'  => $accuracyRounded,
                            'user_lat'  => $gpsLat,
                            'user_lng'  => $gpsLng,
                            'venue_lat' => $meeting->venue_lat,
                            'venue_lng' => $meeting->venue_lng,
                            'message'   => "Your device could only pin your location to within about {$accuracyRounded}m "
                                . "(this can happen with a weak GPS or network signal, especially indoors), and that puts "
                                . "you {$distance}m from the venue (limit: {$meeting->venue_radius}m). "
                                . "Your location: {$gpsLat}, {$gpsLng}. Venue location: {$meeting->venue_lat}, {$meeting->venue_lng}. "
                                . "Try moving somewhere with a clearer signal and check in again, or contact an admin.",
                        ], 422);
                    }

                    return response()->json([
                        'gps_error' => 'out_of_range',
                        'distance'  => $distance,
                        'radius'    => $meeting->venue_radius,
                        'message'   => "You appear to be {$distance}m away from the venue (limit: {$meeting->venue_radius}m). Please contact an admin for manual check-in.",
                    ], 422);
                } else {
                    $mismatch = true;
                }
            }
        }

        // Determine late: compare against late_after_time if set, else 15 min after meeting start.
        // Explicitly use the app timezone so the comparison is correct regardless of server timezone.
        $tz = config('app.timezone', 'Europe/London');
        $lateThreshold = $meeting->late_after_time
            ? Carbon::parse($meeting->meeting_date->format('Y-m-d') . ' ' . $meeting->late_after_time, $tz)
            : Carbon::parse($meeting->meeting_date->format('Y-m-d') . ' ' . $meeting->meeting_time, $tz)->addMinutes(15);

        $isLate = now($tz)->gt($lateThreshold);

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
