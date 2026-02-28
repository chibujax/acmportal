<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\User;
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
            'amount'            => 'required|numeric|min:0.01',
            'currency'          => 'required|string|size:3',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after:start_date',
            'payment_options'   => 'required|in:once,monthly,installments',
            'installment_count' => 'nullable|integer|min:2|max:24',
            'description'       => 'nullable|string|max:1000',
            'status'            => 'required|in:draft,active,closed',
            'send_reminders'    => 'nullable|boolean',
            'couple_shared'     => 'nullable|boolean',
        ]);

        $data['created_by']     = auth()->id();
        $data['send_reminders'] = $request->boolean('send_reminders');
        $data['couple_shared']  = $request->boolean('couple_shared');

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
            'amount'            => 'required|numeric|min:0.01',
            'currency'          => 'required|string|size:3',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after:start_date',
            'payment_options'   => 'required|in:once,monthly,installments',
            'installment_count' => 'nullable|integer|min:2|max:24',
            'description'       => 'nullable|string|max:1000',
            'status'            => 'required|in:draft,active,closed',
            'send_reminders'    => 'nullable|boolean',
            'couple_shared'     => 'nullable|boolean',
        ]);

        $data['send_reminders'] = $request->boolean('send_reminders');
        $data['couple_shared']  = $request->boolean('couple_shared');

        $duesCycle->update($data);

        return redirect()->route('admin.dues-cycles.index')
            ->with('success', 'Dues cycle updated.');
    }

    public function show(DuesCycle $duesCycle)
    {
        $duesCycle->load(['payments.user', 'payments.recordedBy']);

        // Per-member obligation and payment status
        $members = User::where('role', 'member')
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($duesCycle) {
                $obligation  = $user->obligationFor($duesCycle);
                $paid        = $user->totalPaidWithSpouse($duesCycle->id, $duesCycle->couple_shared);
                $remaining   = max(0, $obligation - $paid);
                $percent     = $obligation > 0 ? min(100, round(($paid / $obligation) * 100)) : 0;
                $spouse      = $user->spouse();

                $user->obligation  = $obligation;
                $user->paid        = $paid;
                $user->remaining   = $remaining;
                $user->percent     = $percent;
                $user->settled     = $remaining <= 0;
                $user->spouseName  = $spouse ? $spouse->name : null;
                return $user;
            });

        $totalObligation = $members->sum('obligation');
        $totalCollected  = $duesCycle->totalCollected();

        return view('admin.dues_cycles.show', compact('duesCycle', 'members', 'totalObligation', 'totalCollected'));
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
