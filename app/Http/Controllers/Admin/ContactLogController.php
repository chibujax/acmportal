<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContactLogController extends Controller
{
    public function index(Request $request)
    {
        $logs    = $this->filtered($request)->latest('created_at')->paginate(50)->withQueryString();
        $batches = $this->recentBatches();

        return view('admin.contact_logs.index', compact('logs', 'batches'));
    }

    /**
     * CSV export honouring the same filters as the index: a title, the
     * applied date range, a representative sample of the email/SMS content
     * actually sent (not repeated per row), then a flat Datetime/Name/
     * Channel/Reason table.
     */
    public function export(Request $request)
    {
        $data = $this->buildReportData($request);

        $filename = 'contact-log-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Contact Log Report']);
            fputcsv($handle, ['Date range: ' . $data['dateRangeLabel']]);
            fputcsv($handle, []);

            if ($data['emailSample']) {
                fputcsv($handle, ['Email Content']);
                fputcsv($handle, ['Subject: ' . ($data['emailSample']->subject ?? '')]);
                fputcsv($handle, ['Message: ' . $data['emailSample']->message]);
                fputcsv($handle, []);
            }

            if ($data['smsSample']) {
                fputcsv($handle, ['SMS Content']);
                fputcsv($handle, ['Message: ' . $data['smsSample']->message]);
                fputcsv($handle, []);
            }

            fputcsv($handle, ['Datetime', 'Name', 'Channel', 'Reason']);
            foreach ($data['logs'] as $log) {
                fputcsv($handle, [
                    $log->created_at->format('d M Y H:i'),
                    $log->user?->name ?? 'Unknown',
                    strtoupper($log->channel),
                    ucfirst(str_replace('_', ' ', $log->context)),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Print-friendly view (browser "Print → Save as PDF") — same report
     * shape as the CSV export, same filters. This app has no PDF library,
     * so PDF output follows the same print-CSS convention already used on
     * the Financial Report and Consecutive Absentees pages.
     */
    public function print(Request $request)
    {
        return view('admin.contact_logs.print', $this->buildReportData($request));
    }

    /**
     * Shared data for both the CSV export and the print view: the filtered
     * logs plus a human label for the applied date range and one
     * representative sample of the email/SMS content within the result set.
     */
    private function buildReportData(Request $request): array
    {
        $logs = $this->filtered($request)->orderBy('created_at')->get();

        $dateFrom = $request->filled('date_from') ? \Carbon\Carbon::parse($request->date_from) : null;
        $dateTo   = $request->filled('date_to')   ? \Carbon\Carbon::parse($request->date_to)   : null;

        $dateRangeLabel = match (true) {
            $dateFrom && $dateTo => $dateFrom->format('d M Y H:i') . ' to ' . $dateTo->format('d M Y H:i'),
            (bool) $dateFrom     => 'From ' . $dateFrom->format('d M Y H:i'),
            (bool) $dateTo       => 'Up to ' . $dateTo->format('d M Y H:i'),
            default              => 'All time',
        };

        return [
            'logs'           => $logs,
            'dateRangeLabel' => $dateRangeLabel,
            'emailSample'    => $logs->firstWhere('channel', 'email'),
            'smsSample'      => $logs->firstWhere('channel', 'sms'),
        ];
    }

    private function filtered(Request $request): Builder
    {
        $query = ContactLog::with(['user', 'meeting', 'sentBy']);

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%"));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('context')) {
            $query->where('context', $request->context);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', \Carbon\Carbon::parse($request->date_from));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', \Carbon\Carbon::parse($request->date_to));
        }

        return $query;
    }

    /**
     * Recent send batches for the filter dropdown — one entry per batch_id
     * with a recipient count and a representative preview of what was sent.
     */
    private function recentBatches()
    {
        $stats = ContactLog::whereNotNull('batch_id')
            ->selectRaw('batch_id, MIN(created_at) as sent_at, COUNT(*) as recipient_count')
            ->groupBy('batch_id')
            ->orderByDesc('sent_at')
            ->limit(100)
            ->get();

        if ($stats->isEmpty()) {
            return collect();
        }

        $samples = ContactLog::whereIn('batch_id', $stats->pluck('batch_id'))
            ->orderBy('id')
            ->get(['batch_id', 'context', 'channel', 'subject', 'message'])
            ->groupBy('batch_id')
            ->map(fn ($rows) => $rows->first());

        return $stats->map(function ($stat) use ($samples) {
            $sample = $samples->get($stat->batch_id);
            $stat->context = $sample?->context;
            $stat->channel = $sample?->channel;
            $stat->preview = $sample ? Str::limit($sample->subject ?: $sample->message, 60) : null;

            return $stat;
        });
    }
}
