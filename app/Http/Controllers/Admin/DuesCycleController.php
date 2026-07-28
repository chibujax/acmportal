<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\MemberPledge;
use App\Models\User;
use App\Services\EmailService;
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

    public function show(DuesCycle $duesCycle, Request $request)
    {
        $duesCycle->load(['payments.user', 'payments.recordedBy']);

        // Pre-load pledges and items for this cycle. Anonymous pledges (user_id null) are kept
        // separate from the map, since keying by user_id would collapse them all into one entry.
        $allPledges    = $duesCycle->is_pledge_based ? $duesCycle->pledges()->with('user')->get() : collect();
        $pledgesMap    = $allPledges->whereNotNull('user_id')->keyBy('user_id');
        $anonymousPledges = $allPledges->whereNull('user_id')->values();
        $donationItems = $duesCycle->accepts_items
            ? $duesCycle->donationItems()->with(['user', 'recordedBy'])->latest()->get()
            : collect();

        $sort    = in_array($request->get('sort'), ['name', 'paid', 'remaining', 'status']) ? $request->get('sort') : 'name';
        $dir     = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = in_array((int) $request->get('per_page'), [10, 25, 50, 100]) ? (int) $request->get('per_page') : 25;

        // Per-member obligation and payment status
        // For pledge-based cycles, only show members who have actually pledged — since that list is
        // already restricted to actual pledgers, admins who pledged personally are included too (they're
        // real community members; only fixed-dues cycles stay restricted to role=member to avoid pulling
        // in generic admin/office accounts that were never assigned an obligation).
        $allMembers = User::where('status', 'active')
            ->when(
                $duesCycle->is_pledge_based,
                fn($q) => $q->where('role', '!=', 'super_admin')->whereIn('id', $pledgesMap->keys()->toArray()),
                fn($q) => $q->where('role', 'member')
            )
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($duesCycle, $pledgesMap) {
                if ($duesCycle->is_pledge_based) {
                    $pledge     = $pledgesMap->get($user->id);
                    $obligation = $pledge ? $pledge->pledged_amount : 0;
                    // Only show a spouse here if this pledge was explicitly marked shared —
                    // otherwise every married member would show a spouse even when they gave individually.
                    $spouseName = ($pledge && $pledge->shared_with_spouse) ? $user->spouse()?->name : null;
                } else {
                    $obligation = $user->obligationFor($duesCycle);
                    $spouseName = $user->spouse()?->name;
                }

                $paid        = $user->totalPaidWithSpouse($duesCycle->id, $duesCycle->couple_shared);
                $remaining   = max(0, $obligation - $paid);
                $percent     = $obligation > 0 ? min(100, round(($paid / $obligation) * 100)) : 0;

                $user->obligation   = $obligation;
                $user->paid         = $paid;
                $user->remaining    = $remaining;
                $user->percent      = $percent;
                $user->settled      = $remaining <= 0 && $obligation > 0;
                $user->spouseName   = $spouseName;
                $user->is_anonymous = false;
                return $user;
            });

        // Anonymous / non-member pledges don't have a User row, so they're appended as plain objects
        $anonymousRows = $anonymousPledges->map(function ($pledge) {
            $obligation = $pledge->pledged_amount;
            $paid       = $pledge->received_amount;
            $remaining  = max(0, $obligation - $paid);

            return (object) [
                'id'           => null,
                'name'         => $pledge->displayName(),
                'phone'        => null,
                'obligation'   => $obligation,
                'paid'         => $paid,
                'remaining'    => $remaining,
                'percent'      => $obligation > 0 ? min(100, round(($paid / $obligation) * 100)) : 0,
                'settled'      => $remaining <= 0 && $obligation > 0,
                'spouseName'   => null,
                'is_anonymous' => true,
            ];
        });

        $allMembers = $allMembers->concat($anonymousRows);

        $totalObligation  = $allMembers->sum('obligation');
        $totalCollected   = $duesCycle->totalCollected() + $anonymousPledges->sum('received_amount');
        $totalOutstanding = max(0, $totalObligation - $totalCollected);
        $pledgerCount     = $duesCycle->is_pledge_based ? $allPledges->count() : null;

        $sortKey = $sort === 'status' ? 'settled' : $sort;
        $sorted  = ($dir === 'desc' ? $allMembers->sortByDesc($sortKey) : $allMembers->sortBy($sortKey))->values();

        $page   = max(1, (int) $request->get('page', 1));
        $offset = ($page - 1) * $perPage;

        $members = new \Illuminate\Pagination\LengthAwarePaginator(
            $sorted->slice($offset, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.dues_cycles.show', compact(
            'duesCycle', 'members', 'allMembers', 'totalObligation', 'totalCollected', 'totalOutstanding', 'pledgerCount',
            'pledgesMap', 'donationItems', 'sort', 'dir', 'perPage'
        ));
    }

    public function sendReminders(Request $request, DuesCycle $duesCycle)
    {
        $channel = $request->input('channel', 'sms');

        $rules = [
            'user_ids'   => 'required|array',
            'user_ids.*' => 'integer',
            'channel'    => 'required|in:sms,email',
            'message'    => 'required|string' . ($channel === 'sms' ? '|max:160' : ''),
        ];
        if ($channel === 'email') {
            $rules['subject'] = 'required|string|max:255';
        }
        $request->validate($rules);

        $selectedIds  = $request->user_ids;
        $donationsMap = $duesCycle->donationItems()->get()->groupBy('user_id');
        $pledgesMap   = $duesCycle->is_pledge_based
            ? $duesCycle->pledges()->get()->whereNotNull('user_id')->keyBy('user_id')
            : collect();

        $contactFilter = $channel === 'email' ? 'whereNotNull:email' : 'whereNotNull:phone';
        $members = User::where('status', 'active')
            ->when($duesCycle->is_pledge_based, fn($q) => $q->where('role', '!=', 'super_admin'), fn($q) => $q->where('role', 'member'))
            ->when($channel === 'email', fn ($q) => $q->whereNotNull('email'))
            ->when($channel === 'sms',   fn ($q) => $q->whereNotNull('phone'))
            ->whereIn('id', $selectedIds)
            ->orderBy('name')
            ->get();

        $sent   = 0;
        $failed = 0;
        $placeholders = ['{name}', '{amount}', '{cycle}', '{donations}'];

        $emailSvc = $channel === 'email' ? app(EmailService::class) : null;
        $smsSvc   = $channel === 'sms'   ? app(SmsService::class)   : null;

        foreach ($members as $member) {
            if ($duesCycle->is_pledge_based) {
                $pledge     = $pledgesMap->get($member->id);
                $obligation = $pledge ? $pledge->pledged_amount : 0;
            } else {
                $obligation = $member->obligationFor($duesCycle);
            }

            $paid       = $member->totalPaidWithSpouse($duesCycle->id, $duesCycle->couple_shared);
            $remaining  = $obligation - $paid;

            if ($remaining <= 0) {
                continue;
            }

            $memberItems = $donationsMap->get($member->id, collect());
            $donations   = $memberItems->isNotEmpty()
                ? $memberItems->map(fn ($i) => trim("{$i->quantity} {$i->description}"))->implode(', ')
                : 'none';

            $values  = [$member->name, number_format($remaining, 2), $duesCycle->title, $donations];
            $body    = str_replace($placeholders, $values, $request->message);

            if ($channel === 'email') {
                $subject = str_replace($placeholders, $values, $request->subject);
                $emailSvc->send($member->email, $subject, $body) ? $sent++ : $failed++;
            } else {
                $smsSvc->send($member->phone, $body) ? $sent++ : $failed++;
            }
        }

        $label = $channel === 'email' ? 'Email reminders' : 'SMS reminders';
        $msg   = "{$label} sent to {$sent} member(s).";
        if ($failed > 0) {
            $msg .= " {$failed} failed — check the application logs for details.";
        }

        return back()->with($failed > 0 ? 'warning' : 'success', $msg);
    }

    public function exportCsv(DuesCycle $duesCycle, Request $request)
    {
        $sort = in_array($request->get('sort'), ['name', 'paid', 'remaining', 'status']) ? $request->get('sort') : 'name';
        $dir  = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $allPledges       = $duesCycle->is_pledge_based ? $duesCycle->pledges()->with('user')->get() : collect();
        $pledgesMap       = $allPledges->whereNotNull('user_id')->keyBy('user_id');
        $anonymousPledges = $allPledges->whereNull('user_id')->values();

        $rows = User::where('status', 'active')
            ->when(
                $duesCycle->is_pledge_based,
                fn($q) => $q->where('role', '!=', 'super_admin')->whereIn('id', $pledgesMap->keys()->toArray()),
                fn($q) => $q->where('role', 'member')
            )
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($duesCycle, $pledgesMap) {
                if ($duesCycle->is_pledge_based) {
                    $pledge     = $pledgesMap->get($user->id);
                    $obligation = $pledge ? $pledge->pledged_amount : 0;
                    $spouseName = ($pledge && $pledge->shared_with_spouse) ? $user->spouse()?->name : null;
                } else {
                    $obligation = $user->obligationFor($duesCycle);
                    $spouseName = $user->spouse()?->name;
                }

                $paid       = $user->totalPaidWithSpouse($duesCycle->id, $duesCycle->couple_shared);
                $remaining  = max(0, $obligation - $paid);

                $user->obligation = $obligation;
                $user->paid       = $paid;
                $user->remaining  = $remaining;
                $user->settled    = $remaining <= 0 && $obligation > 0;
                $user->spouseName = $spouseName;
                return $user;
            });

        $anonymousRows = $anonymousPledges->map(function ($pledge) {
            $obligation = $pledge->pledged_amount;
            $paid       = $pledge->received_amount;
            $remaining  = max(0, $obligation - $paid);

            return (object) [
                'name'       => $pledge->displayName(),
                'phone'      => null,
                'obligation' => $obligation,
                'paid'       => $paid,
                'remaining'  => $remaining,
                'settled'    => $remaining <= 0 && $obligation > 0,
                'spouseName' => null,
            ];
        });

        $rows = $rows->concat($anonymousRows);

        $sortKey = $sort === 'status' ? 'settled' : $sort;
        $rows    = ($dir === 'desc' ? $rows->sortByDesc($sortKey) : $rows->sortBy($sortKey))->values();

        $totalObligation  = $rows->sum('obligation');
        $totalCollected   = $duesCycle->totalCollected() + $anonymousPledges->sum('received_amount');
        $totalOutstanding = max(0, $totalObligation - $totalCollected);
        $pledgerCount     = $allPledges->count();

        $filename = 'dues-' . str_replace(' ', '-', strtolower($duesCycle->title)) . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows, $duesCycle, $totalObligation, $totalCollected, $totalOutstanding, $pledgerCount) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ACM Dues Cycle Report']);
            fputcsv($handle, ['Cycle: ' . $duesCycle->title]);
            fputcsv($handle, ['Generated: ' . now()->format('d M Y H:i')]);
            fputcsv($handle, []);
            fputcsv($handle, ['Total Expected (£)', number_format($totalObligation, 2)]);
            fputcsv($handle, ['Money at Hand (£)', number_format($totalCollected, 2)]);
            fputcsv($handle, ['Outstanding (£)', number_format($totalOutstanding, 2)]);
            if ($duesCycle->is_pledge_based) {
                fputcsv($handle, ['Members Pledged', $pledgerCount]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Name', 'Spouse', 'Obligation (£)', 'Paid (£)', 'Remaining (£)', 'Status']);

            foreach ($rows as $user) {
                fputcsv($handle, [
                    $user->name,
                    $user->spouseName ?? '',
                    number_format($user->obligation, 2),
                    number_format($user->paid, 2),
                    number_format($user->remaining, 2),
                    $user->settled ? 'Settled' : 'Outstanding',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
