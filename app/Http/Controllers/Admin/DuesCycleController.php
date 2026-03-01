<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\MemberPledge;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;

class DuesCycleController extends Controller
{
    public function index()
    {
        $cycles = DuesCycle::withSum(['payments as collected' => fn($q) => $q->where('status', 'completed')], 'amount')
            ->withCount(['payments as payers' => fn($q) => $q->where('status', 'completed')->distinct('user_id')])
            ->orderByDesc('start_date')
            ->get();

        return view('admin.dues_cycles.index', compact('cycles'));
    }

    public function create()
    {
        return view('admin.dues_cycles.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'             => 'required|string|max:255',
            'type'              => 'required|in:yearly_dues,donation,event_levy',
            'amount'            => 'nullable|numeric|min:0.01',
            'currency'          => 'required|string|size:3',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after:start_date',
            'payment_options'   => 'required|in:once,monthly,installments',
            'installment_count' => 'nullable|integer|min:2|max:24',
            'description'       => 'nullable|string|max:1000',
            'status'            => 'required|in:draft,active,closed',
            'send_reminders'    => 'nullable|boolean',
            'couple_shared'     => 'nullable|boolean',
            'is_pledge_based'   => 'nullable|boolean',
            'accepts_items'     => 'nullable|boolean',
        ]);

        $data['created_by']      = auth()->id();
        $data['send_reminders']  = $request->boolean('send_reminders');
        $data['couple_shared']   = $request->boolean('couple_shared');
        $data['is_pledge_based'] = $request->boolean('is_pledge_based');
        $data['accepts_items']   = $request->boolean('accepts_items');
        $data['amount']          = $data['is_pledge_based'] ? 0 : ($data['amount'] ?? 0);

        DuesCycle::create($data);

        return redirect()->route('admin.dues-cycles.index')
            ->with('success', 'Dues cycle created.');
    }

    public function edit(DuesCycle $duesCycle)
    {
        return view('admin.dues_cycles.edit', compact('duesCycle'));
    }

    public function update(Request $request, DuesCycle $duesCycle)
    {
        $data = $request->validate([
            'title'             => 'required|string|max:255',
            'type'              => 'required|in:yearly_dues,donation,event_levy',
            'amount'            => 'nullable|numeric|min:0.01',
            'currency'          => 'required|string|size:3',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after:start_date',
            'payment_options'   => 'required|in:once,monthly,installments',
            'installment_count' => 'nullable|integer|min:2|max:24',
            'description'       => 'nullable|string|max:1000',
            'status'            => 'required|in:draft,active,closed',
            'send_reminders'    => 'nullable|boolean',
            'couple_shared'     => 'nullable|boolean',
            'is_pledge_based'   => 'nullable|boolean',
            'accepts_items'     => 'nullable|boolean',
        ]);

        $data['send_reminders']  = $request->boolean('send_reminders');
        $data['couple_shared']   = $request->boolean('couple_shared');
        $data['is_pledge_based'] = $request->boolean('is_pledge_based');
        $data['accepts_items']   = $request->boolean('accepts_items');
        $data['amount']          = $data['is_pledge_based'] ? 0 : ($data['amount'] ?? 0);

        $duesCycle->update($data);

        return redirect()->route('admin.dues-cycles.index')
            ->with('success', 'Dues cycle updated.');
    }

    public function show(DuesCycle $duesCycle)
    {
        $duesCycle->load(['payments.user', 'payments.recordedBy']);

        // Pre-load pledges and items for this cycle
        $pledgesMap    = $duesCycle->is_pledge_based
            ? $duesCycle->pledges()->with('user')->get()->keyBy('user_id')
            : collect();
        $donationItems = $duesCycle->accepts_items
            ? $duesCycle->donationItems()->with(['user', 'recordedBy'])->latest()->get()
            : collect();

        // Per-member obligation and payment status
        // For pledge-based cycles, only show members who have actually pledged
        $members = User::where('role', 'member')
            ->where('status', 'active')
            ->when($duesCycle->is_pledge_based, fn($q) => $q->whereIn('id', $pledgesMap->keys()->toArray()))
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($duesCycle, $pledgesMap) {
                if ($duesCycle->is_pledge_based) {
                    $pledge     = $pledgesMap->get($user->id);
                    $obligation = $pledge ? $pledge->pledged_amount : 0;
                } else {
                    $obligation = $user->obligationFor($duesCycle);
                }

                $paid        = $user->totalPaidWithSpouse($duesCycle->id, $duesCycle->couple_shared);
                $remaining   = max(0, $obligation - $paid);
                $percent     = $obligation > 0 ? min(100, round(($paid / $obligation) * 100)) : 0;
                $spouse      = $user->spouse();

                $user->obligation  = $obligation;
                $user->paid        = $paid;
                $user->remaining   = $remaining;
                $user->percent     = $percent;
                $user->settled     = $remaining <= 0 && $obligation > 0;
                $user->spouseName  = $spouse ? $spouse->name : null;
                return $user;
            });

        $totalObligation = $members->sum('obligation');
        $totalCollected  = $duesCycle->totalCollected();

        return view('admin.dues_cycles.show', compact(
            'duesCycle', 'members', 'totalObligation', 'totalCollected',
            'pledgesMap', 'donationItems'
        ));
    }

    public function sendReminders(Request $request, DuesCycle $duesCycle)
    {
        $request->validate([
            'user_ids'   => 'required|array',
            'user_ids.*' => 'integer',
            'message'    => 'required|string|max:160',
        ]);

        $selectedIds   = $request->user_ids;
        $donationsMap  = $duesCycle->donationItems()->get()->groupBy('user_id');

        $members = User::where('role', 'member')
            ->where('status', 'active')
            ->whereNotNull('phone')
            ->whereIn('id', $selectedIds)
            ->orderBy('name')
            ->get();

        $sms    = app(SmsService::class);
        $sent   = 0;
        $failed = 0;

        foreach ($members as $member) {
            $obligation = $member->obligationFor($duesCycle);
            $paid       = $member->totalPaidWithSpouse($duesCycle->id, $duesCycle->couple_shared);
            $remaining  = $obligation - $paid;

            if ($remaining <= 0) {
                continue;
            }

            $memberItems = $donationsMap->get($member->id, collect());
            $donations   = $memberItems->isNotEmpty()
                ? $memberItems->map(fn ($i) => trim("{$i->quantity} {$i->description}"))->implode(', ')
                : 'none';

            $message = str_replace(
                ['{name}', '{amount}', '{cycle}', '{donations}'],
                [$member->name, number_format($remaining, 2), $duesCycle->title, $donations],
                $request->message
            );

            $sms->send($member->phone, $message) ? $sent++ : $failed++;
        }

        $msg = "SMS reminders sent to {$sent} member(s).";
        if ($failed > 0) {
            $msg .= " {$failed} failed — check the application logs for details.";
        }

        return back()->with($failed > 0 ? 'warning' : 'success', $msg);
    }

    public function exportCsv(DuesCycle $duesCycle)
    {
        $filename = 'dues-' . str_replace(' ', '-', strtolower($duesCycle->title)) . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($duesCycle) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Phone', 'Spouse', 'Obligation (£)', 'Paid (£)', 'Remaining (£)', 'Status']);

            User::where('role', 'member')->where('status', 'active')->orderBy('name')->get()
                ->each(function ($user) use ($handle, $duesCycle) {
                    $obligation = $user->obligationFor($duesCycle);
                    $paid       = $user->totalPaidWithSpouse($duesCycle->id);
                    $remaining  = max(0, $obligation - $paid);
                    $spouse     = $user->spouse();

                    fputcsv($handle, [
                        $user->name,
                        $user->phone,
                        $spouse ? $spouse->name : '',
                        number_format($obligation, 2),
                        number_format($paid, 2),
                        number_format($remaining, 2),
                        $remaining <= 0 ? 'Settled' : 'Outstanding',
                    ]);
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
