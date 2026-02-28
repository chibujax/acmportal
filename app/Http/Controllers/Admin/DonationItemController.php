<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonationItem;
use App\Models\DuesCycle;
use App\Models\User;
use Illuminate\Http\Request;

class DonationItemController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->isFinancialSecretary()) {
                abort(403, 'Access denied.');
            }
            return $next($request);
        });
    }

    public function index(DuesCycle $duesCycle)
    {
        $items = $duesCycle->donationItems()->with(['user', 'recordedBy'])->latest()->get();

        return view('admin.donation_items.index', compact('duesCycle', 'items'));
    }

    public function create(DuesCycle $duesCycle)
    {
        $members = User::where('role', 'member')->where('status', 'active')->orderBy('name')->get();

        return view('admin.donation_items.create', compact('duesCycle', 'members'));
    }

    public function store(Request $request, DuesCycle $duesCycle)
    {
        $request->validate([
            'user_id'         => 'required|exists:users,id',
            'item_type'       => 'required|in:money,other',
            'description'     => 'required|string|max:255',
            'quantity'        => 'nullable|string|max:100',
            'estimated_value' => 'nullable|numeric|min:0',
            'donation_date'   => 'required|date',
            'notes'           => 'nullable|string|max:1000',
        ]);

        DonationItem::create([
            'user_id'         => $request->user_id,
            'dues_cycle_id'   => $duesCycle->id,
            'item_type'       => $request->item_type,
            'description'     => $request->description,
            'quantity'        => $request->quantity,
            'estimated_value' => $request->estimated_value,
            'currency'        => $duesCycle->currency,
            'donation_date'   => $request->donation_date,
            'recorded_by'     => auth()->id(),
            'notes'           => $request->notes,
        ]);

        return redirect()->route('admin.donation-items.index', $duesCycle)
            ->with('success', 'Donation item recorded.');
    }

    public function destroy(DonationItem $donationItem)
    {
        $cycleId = $donationItem->dues_cycle_id;
        $donationItem->delete();

        return redirect()->route('admin.donation-items.index', $cycleId)
            ->with('success', 'Donation item removed.');
    }

    public function fulfill(DonationItem $donationItem)
    {
        $nowFulfilled = ! $donationItem->is_fulfilled;

        $donationItem->update([
            'is_fulfilled' => $nowFulfilled,
            'fulfilled_at' => $nowFulfilled ? now() : null,
            'fulfilled_by' => $nowFulfilled ? auth()->id() : null,
        ]);

        $msg = $nowFulfilled ? 'Item marked as received.' : 'Item marked as pending.';

        return back()->with('success', $msg);
    }
}
