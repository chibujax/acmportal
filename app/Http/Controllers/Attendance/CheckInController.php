<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Meeting;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    /**
     * QR scan landing page.
     * URL: /attend/{token}
     * Requires auth – if guest, redirect to login with intended URL.
     */
    public function show(string $token)
    {
        $meeting = Meeting::where('qr_token', $token)->first();

        if (! $meeting) {
            return view('attendance.checkin', ['error' => 'Invalid QR code. Please scan the QR code displayed at the meeting.']);
        }

        if ($meeting->status === 'closed') {
            return view('attendance.checkin', ['error' => 'This meeting has been closed. Check-ins are no longer accepted.', 'meeting' => $meeting]);
        }

        if ($meeting->status === 'scheduled') {
            return view('attendance.checkin', ['error' => 'Check-in is not open yet. Please wait for the meeting to start.', 'meeting' => $meeting]);
        }

        if ($meeting->isExpiredQr()) {
            return view('attendance.checkin', ['error' => 'The QR code for this meeting has expired. Please see an administrator for manual check-in.', 'meeting' => $meeting]);
        }

        $user = auth()->user();

        // Already checked in?
        $existing = AttendanceRecord::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return view('attendance.checkin', [
                'meeting'  => $meeting,
                'already'  => true,
                'record'   => $existing,
            ]);
        }

        // Determine if late (> 15 min past meeting time)
        $meetingDateTime = \Carbon\Carbon::parse(
            $meeting->meeting_date->format('Y-m-d') . ' ' . $meeting->meeting_time
        );
        $isLate = now()->diffInMinutes($meetingDateTime, false) < -15;

        // Auto check-in
        $record = AttendanceRecord::create([
            'meeting_id'      => $meeting->id,
            'user_id'         => $user->id,
            'check_in_time'   => now(),
            'check_in_method' => 'qr_scan',
            'status'          => $isLate ? 'late' : 'present',
        ]);

        return view('attendance.checkin', [
            'meeting' => $meeting,
            'record'  => $record,
            'success' => true,
            'isLate'  => $isLate,
        ]);
    }
}
