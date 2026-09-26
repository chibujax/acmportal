<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $year = $request->get('year', now()->year);

        // All meetings (active/closed) for the year
        $meetings = Meeting::whereYear('meeting_date', $year)
            ->whereIn('status', ['active', 'closed'])
            ->orderByDesc('meeting_date')
            ->orderByDesc('meeting_time')
            ->withCount('attendanceRecords')
            ->get()
            ->map(function ($meeting) use ($user) {
                $meeting->user_record = $meeting->attendanceRecords
                    ->where('user_id', $user->id)
                    ->first();
                return $meeting;
            });

        $totalMeetings = $meetings->count();
        $attended      = $meetings->filter(fn($m) => $m->user_record && $m->user_record->status !== 'excused')->count();
        $percentage    = $totalMeetings > 0 ? round(($attended / $totalMeetings) * 100, 1) : 0;

        // Load attendance records with meeting for the query above
        $meetings->load('attendanceRecords');

        // Chart: monthly attended (present/late) and excused breakdown
        $grouped = $meetings->groupBy(fn($m) => $m->meeting_date->month);

        $monthlyAttended = $grouped->map(fn($g) => $g->filter(
            fn($m) => $m->user_record && $m->user_record->status !== 'excused'
        )->count())->toArray();

        $monthlyExcused = $grouped->map(fn($g) => $g->filter(
            fn($m) => $m->user_record && $m->user_record->status === 'excused'
        )->count())->toArray();

        $chartData    = array_map(fn($m) => $monthlyAttended[$m] ?? 0, range(1, 12));
        $excusedData  = array_map(fn($m) => $monthlyExcused[$m] ?? 0, range(1, 12));

        $years = Meeting::selectRaw('YEAR(meeting_date) as y')
            ->whereIn('status', ['active', 'closed'])
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y');

        return view('member.attendance', compact(
            'meetings', 'year', 'years', 'totalMeetings', 'attended', 'percentage', 'chartData', 'excusedData'
        ));
    }
}
