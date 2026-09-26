<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeEvent;
use Illuminate\Http\Request;

class StripeEventController extends Controller
{
    public function index(Request $request)
    {
        $query = StripeEvent::with('payment.user')->latest('received_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('payment.user', fn($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%"));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('received_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('received_at', '<=', $request->date_to);
        }

        $events = $query->paginate(50)->withQueryString();

        $types = StripeEvent::select('type')->distinct()->orderBy('type')->pluck('type');

        return view('admin.stripe_events.index', compact('events', 'types'));
    }
}
