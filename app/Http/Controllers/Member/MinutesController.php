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

        $counts = MeetingMinutes::withAccessCounts()->find($minutes->id);

        $viewCount     = $counts->views_count;
        $downloadCount = $counts->downloads_count;

        // Everyone sees the counts; only admins with minutes access see who viewed/downloaded.
        $viewers = auth()->user()->hasAccess('minutes')
            ? $minutes->viewers()->orderByPivot('last_viewed_at', 'desc')->get(['users.id', 'users.name'])
            : null;

        return view('member.minutes.show', compact('minutes', 'viewCount', 'downloadCount', 'viewers'));
    }

    // view() and download() are what get counted — the show page itself
    // only links to the PDF, so opening it there isn't reading the minutes.
    public function view(MeetingMinutes $minutes)
    {
        abort_unless($minutes->status === 'published', 404);

        $minutes->recordViewBy(auth()->user());

        return Storage::disk('local')->response($minutes->file_path, $minutes->file_name);
    }

    public function download(MeetingMinutes $minutes)
    {
        abort_unless($minutes->status === 'published', 404);

        $minutes->recordDownloadBy(auth()->user());

        return Storage::disk('local')->download($minutes->file_path, $minutes->file_name);
    }
}
