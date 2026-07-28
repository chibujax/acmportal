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
        $members = User::where('role', '!=', 'super_admin')->where('status', 'active')->orderBy('name')->get();

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
            'user_id'                     => 'nullable|exists:users,id',
            'donor_name'                   => 'required_without:user_id|nullable|string|max:255',
            'pledged_amount'              => 'nullable|numeric|min:0.01',
            'received_amount'             => 'nullable|numeric|min:0',
            'shared_with_spouse'          => 'nullable|boolean',
            'pledge_notes'                => 'nullable|string|max:500',
            'items'                       => 'nullable|array',
            'items.*.description'         => 'nullable|string|max:255',
            'items.*.item_type'           => 'nullable|in:money,other',
            'items.*.quantity'            => 'nullable|string|max:100',
            'items.*.estimated_value'     => 'nullable|numeric|min:0',
        ]);

        // Record or update money pledge if an amount was given
        if ($request->filled('pledged_amount')) {
            $data = [
                'pledged_amount'     => $request->pledged_amount,
                'received_amount'    => $request->filled('received_amount') ? $request->received_amount : 0,
                'shared_with_spouse' => $request->boolean('shared_with_spouse'),
                'currency'           => $duesCycle->currency,
                'notes'              => $request->pledge_notes,
                'recorded_by'        => auth()->id(),
            ];

            if ($request->filled('user_id')) {
                // Real member — one pledge per member per cycle, so update if it already exists
                MemberPledge::updateOrCreate(
                    ['user_id' => $request->user_id, 'dues_cycle_id' => $duesCycle->id],
                    $data
                );
            } else {
                // Anonymous / non-member donor — always a new entry, nothing to match against
                MemberPledge::create(array_merge($data, [
                    'user_id'       => null,
                    'donor_name'    => $request->donor_name,
                    'dues_cycle_id' => $duesCycle->id,
                ]));
            }
        }

        // Record any item contributions — members only, since items need a real recipient to follow up with
        if ($request->filled('user_id')) {
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
        }

        return back()->with('success', 'Contribution recorded.');
    }

    public function destroy(MemberPledge $pledge)
    {
        $pledge->delete();

        return back()->with('success', 'Pledge removed.');
    }
}
