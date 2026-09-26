<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MeetingMinutes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MinutesController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', now()->year);

        $perPage = in_array((int) $request->get('per_page'), [10, 20, 50, 100]) ? (int) $request->get('per_page') : 20;

        $minutes = MeetingMinutes::whereYear('meeting_date', $year)
            ->orderByDesc('meeting_date')
            ->paginate($perPage)
            ->withQueryString();

        $years = MeetingMinutes::selectRaw('YEAR(meeting_date) as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y');

        return view('admin.minutes.index', compact('minutes', 'year', 'years'));
    }

    public function create()
    {
        return view('admin.minutes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'meeting_date' => 'required|date',
            'file'         => 'required|file|mimes:pdf|max:10240',
        ]);

        $path = $request->file('file')->store('meeting-minutes', 'local');

        $publishNow = $request->boolean('publish_now');

        MeetingMinutes::create([
            'title'        => $request->title,
            'meeting_date' => $request->meeting_date,
            'status'       => $publishNow ? 'published' : 'draft',
            'published_at' => $publishNow ? now() : null,
            'file_path'    => $path,
            'file_name'    => $request->file('file')->getClientOriginalName(),
            'created_by'   => auth()->id(),
        ]);

        return redirect()->route('admin.minutes.index')->with('success', 'Minutes uploaded successfully.');
    }

    public function edit(MeetingMinutes $minutes)
    {
        return view('admin.minutes.edit', compact('minutes'));
    }

    public function update(Request $request, MeetingMinutes $minutes)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'meeting_date' => 'required|date',
            'file'         => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $data = [
            'title'        => $request->title,
            'meeting_date' => $request->meeting_date,
        ];

        if ($request->hasFile('file')) {
            Storage::disk('local')->delete($minutes->file_path);
            $data['file_path'] = $request->file('file')->store('meeting-minutes', 'local');
            $data['file_name'] = $request->file('file')->getClientOriginalName();
        }

        $minutes->update($data);

        return redirect()->route('admin.minutes.index')->with('success', 'Minutes updated.');
    }

    public function publish(MeetingMinutes $minutes)
    {
        $minutes->update(['status' => 'published', 'published_at' => now()]);

        return back()->with('success', 'Minutes published — members can now view and download it.');
    }

    public function unpublish(MeetingMinutes $minutes)
    {
        $minutes->update(['status' => 'draft']);

        return back()->with('success', 'Minutes unpublished.');
    }

    public function destroy(MeetingMinutes $minutes)
    {
        $minutes->delete();

        return back()->with('success', 'Minutes deleted.');
    }

    public function view(MeetingMinutes $minutes)
    {
        return Storage::disk('local')->response($minutes->file_path, $minutes->file_name);
    }

    public function download(MeetingMinutes $minutes)
    {
        return Storage::disk('local')->download($minutes->file_path, $minutes->file_name);
    }
}
