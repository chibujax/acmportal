<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StripeReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $payments = $this->filtered($request)->orderByDesc('payment_date')->orderByDesc('created_at')
            ->paginate(50)->withQueryString();

        $totals = $this->totals($request);

        return view('admin.stripe_reconciliation.index', compact('payments', 'totals'));
    }

    public function export(Request $request)
    {
        $payments = $this->filtered($request)->orderBy('payment_date')->orderBy('created_at')->get();

        $filename = 'stripe-reconciliation-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($payments, $request) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Stripe Reconciliation Report']);
            fputcsv($handle, ['Date range: ' . $this->dateRangeLabel($request)]);
            fputcsv($handle, []);

            fputcsv($handle, ['Date', 'Member', 'Cycle', 'Amount', 'Status', 'Receipt #', 'Stripe Reference']);
            foreach ($payments as $p) {
                fputcsv($handle, [
                    ($p->payment_date ?? $p->created_at)->format('d M Y H:i'),
                    $p->user?->name ?? 'Unknown',
                    $p->duesCycle?->title ?? '—',
                    number_format($p->amount, 2),
                    ucfirst($p->status),
                    $p->receipt_number ?? '—',
                    $p->gateway_reference ?? '—',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function filtered(Request $request): Builder
    {
        $query = Payment::with(['user', 'duesCycle'])->where('method', 'stripe');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to));
        }

        return $query;
    }

    /**
     * Completed/refunded totals for the current filter — what actually moved
     * money, for matching against the bank statement.
     */
    private function totals(Request $request): array
    {
        $base = $this->filtered($request);

        return [
            'completed' => (clone $base)->where('status', 'completed')->sum('amount'),
            'refunded'  => (clone $base)->where('status', 'refunded')->sum('amount'),
            'pending'   => (clone $base)->where('status', 'pending')->sum('amount'),
            'failed'    => (clone $base)->where('status', 'failed')->count(),
        ];
    }

    private function dateRangeLabel(Request $request): string
    {
        $from = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $to   = $request->filled('date_to')   ? Carbon::parse($request->date_to)   : null;

        return match (true) {
            $from && $to => $from->format('d M Y H:i') . ' to ' . $to->format('d M Y H:i'),
            (bool) $from => 'From ' . $from->format('d M Y H:i'),
            (bool) $to   => 'Up to ' . $to->format('d M Y H:i'),
            default      => 'All time',
        };
    }
}
