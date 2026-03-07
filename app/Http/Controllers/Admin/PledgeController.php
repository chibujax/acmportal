<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonationItem;
use App\Models\DuesCycle;
use App\Models\MemberPledge;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class PledgeController extends Controller
{
    public function index(DuesCycle $duesCycle)
    {
        $pledges = $duesCycle->pledges()->with(['user', 'recordedBy'])->orderBy('pledged_amount', 'desc')->get();
        $members = User::where('role', 'member')->where('status', 'active')->orderBy('name')->get();

        $donationItems = $duesCycle->accepts_items
            ? $duesCycle->donationItems()->with(['user', 'recordedBy'])->latest()->get()
            : collect();

        // Total completed payments per member for this cycle
        $paymentsMap = Payment::where('dues_cycle_id', $duesCycle->id)
            ->where('status', 'completed')
            ->get()
            ->groupBy('user_id')
            ->map(fn($ps) => $ps->sum('amount'));

        return view('admin.pledges.index', compact('duesCycle', 'pledges', 'members', 'donationItems', 'paymentsMap'));
    }

    public function store(Request $request, DuesCycle $duesCycle)
    {
        $request->validate([
            'user_id'                     => 'required|exists:users,id',
            'pledged_amount'              => 'nullable|numeric|min:0.01',
            'pledge_notes'                => 'nullable|string|max:500',
            'items'                       => 'nullable|array',
            'items.*.description'         => 'nullable|string|max:255',
            'items.*.item_type'           => 'nullable|in:money,other',
            'items.*.quantity'            => 'nullable|string|max:100',
            'items.*.estimated_value'     => 'nullable|numeric|min:0',
        ]);

        // Record or update money pledge if an amount was given
        if ($request->filled('pledged_amount')) {
            MemberPledge::updateOrCreate(
                ['user_id' => $request->user_id, 'dues_cycle_id' => $duesCycle->id],
                [
                    'pledged_amount' => $request->pledged_amount,
                    'currency'       => $duesCycle->currency,
                    'notes'          => $request->pledge_notes,
                    'recorded_by'    => auth()->id(),
                ]
            );
        }

        // Record any item contributions
        foreach ((array) $request->items as $item) {
            if (! empty($item['description'])) {
                DonationItem::create([
                    'user_id'         => $request->user_id,
                    'dues_cycle_id'   => $duesCycle->id,
                    'item_type'       => $item['item_type'] ?? 'other',
                    'description'     => $item['description'],
                    'quantity'        => $item['quantity'] ?? null,
                    'estimated_value' => $item['estimated_value'] ?? null,
                    'currency'        => $duesCycle->currency,
                    'donation_date'   => now()->toDateString(),
                    'recorded_by'     => auth()->id(),
                    'notes'           => null,
                ]);
            }
        }

        return back()->with('success', 'Contribution recorded.');
    }

    public function destroy(MemberPledge $pledge)
    {
        $pledge->delete();

        return back()->with('success', 'Pledge removed.');
    }
}
