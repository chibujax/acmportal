<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MeetingMinutes;
use Illuminate\Support\Facades\Storage;

class MinutesController extends Controller
{
    public function index()
    {
        $minutes = MeetingMinutes::published()
            ->orderByDesc('meeting_date')
            ->paginate(15);

        return view('member.minutes.index', compact('minutes'));
    }

    public function show(MeetingMinutes $minutes)
    {
        abort_unless($minutes->status === 'published', 404);

        return view('member.minutes.show', compact('minutes'));
    }

    public function view(MeetingMinutes $minutes)
    {
        abort_unless($minutes->status === 'published', 404);

        return Storage::disk('local')->response($minutes->file_path, $minutes->file_name);
    }

    public function download(MeetingMinutes $minutes)
    {
        abort_unless($minutes->status === 'published', 404);

        return Storage::disk('local')->download($minutes->file_path, $minutes->file_name);
    }
}
