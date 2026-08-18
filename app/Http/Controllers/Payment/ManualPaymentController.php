<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\MemberPledge;
use App\Models\MemberRelationship;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class ManualPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'duesCycle', 'recordedBy']);

        if ($request->method) {
            $query->where('method', $request->method);
        }

        if ($request->search) {
            $s = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%"));
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->cycle_id) {
            $query->where('dues_cycle_id', $request->cycle_id);
        }

        $sortable  = ['payment_date', 'amount', 'status'];
        $sort      = in_array($request->sort, $sortable) ? $request->sort : 'payment_date';
        $direction = $request->direction === 'asc' ? 'asc' : 'desc';

        if ($request->sort === 'member') {
            $query->join('users', 'payments.user_id', '=', 'users.id')
                  ->orderBy('users.name', $direction)
                  ->select('payments.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $perPage  = in_array((int) $request->per_page, [10, 20, 50, 100]) ? (int) $request->per_page : 20;
        $payments = $query->paginate($perPage)->withQueryString();
        $cycles   = DuesCycle::orderByDesc('start_date')->get();

        // Failed online-payment attempts per user in the trailing hour — a repeated
        // burst of these on one account is the classic card-testing fraud signal.
        $recentFailedCounts = Payment::where('method', 'stripe')
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->whereIn('user_id', $payments->pluck('user_id')->unique())
            ->selectRaw('user_id, count(*) as cnt')
            ->groupBy('user_id')
            ->pluck('cnt', 'user_id');

        return view('payment.manual.index', compact('payments', 'cycles', 'recentFailedCounts'));
    }

    public function create(Request $request)
    {
        $members = User::where('role', '!=', 'super_admin')->where('status', 'active')
            ->orderBy('name')->get();
        $cycles  = DuesCycle::whereIn('status', ['active', 'closed'])->orderByDesc('start_date')->get();

        $prefillUserId  = $request->integer('user_id') ?: null;
        $prefillCycleId = $request->integer('dues_cycle_id') ?: null;

        // Map: member_id => spouse {id, name}
        $spouseMap = [];
        MemberRelationship::where('relationship_type', 'spouse')
            ->get()
            ->each(function ($r) use (&$spouseMap, $members) {
                $m1 = $members->firstWhere('id', $r->member_id_1);
                $m2 = $members->firstWhere('id', $r->member_id_2);
                if ($m1 && $m2) {
                    $spouseMap[$r->member_id_1] = ['id' => $m2->id, 'name' => $m2->name];
                    $spouseMap[$r->member_id_2] = ['id' => $m1->id, 'name' => $m1->name];
                }
            });

        // Map for legacy has_spouse check
        $membersWithSpouse = collect($spouseMap)->keys()->flip();

        // Map: member_id => [cycle_id => pledge_amount]
        $pledgeMap = MemberPledge::get()
            ->groupBy('user_id')
            ->map(fn($pledges) => $pledges->pluck('pledged_amount', 'dues_cycle_id'));

        return view('payment.manual.create', compact('members', 'cycles', 'membersWithSpouse', 'spouseMap', 'pledgeMap', 'prefillUserId', 'prefillCycleId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'        => 'required|exists:users,id',
            'dues_cycle_id'  => ['required', function ($attr, $value, $fail) {
                if ($value !== 'general' && ! DuesCycle::where('id', $value)->exists()) {
                    $fail('Please select a valid dues cycle.');
                }
            }],
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'pay_for_spouse' => 'nullable|boolean',
            'notes'          => 'nullable|string|max:1000',
            'proof'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        // Convert 'general' sentinel to null for storage
        $request->merge(['dues_cycle_id' => $request->dues_cycle_id === 'general' ? null : $request->dues_cycle_id]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('payment-proofs', 'public');
        }

        $payForSpouse = $request->boolean('pay_for_spouse');

        // "Pay for spouse" duplicated the entered amount into two separate payment rows
        // instead of splitting/sharing it, which produced incorrect totals (see the Onuoha
        // £90-recorded-as-£180 case). couple_shared cycles never needed this — a single
        // payment there already counts for both spouses via totalPaidWithSpouse()'s merge —
        // so this was only ever reachable for non-couple_shared cycles, which is exactly
        // where it's now rejected. Admins must record each person's payment separately.
        if ($payForSpouse) {
            $cycle = $request->dues_cycle_id ? DuesCycle::find($request->dues_cycle_id) : null;

            if (! $cycle || ! $cycle->couple_shared) {
                return back()->withInput()->withErrors([
                    'pay_for_spouse' => "Spouse payment isn't supported for this dues cycle. Please record each person's payment separately.",
                ]);
            }
        }

        $commonData = [
            'dues_cycle_id'    => $request->dues_cycle_id,
            'amount'           => $request->amount,
            'currency'         => 'GBP',
            'method'           => 'manual',
            'status'           => 'completed',
            'recorded_by'      => auth()->id(),
            'notes'            => $request->notes,
            'payment_date'     => $request->payment_date,
            'proof_of_payment' => $proofPath,
        ];

        Payment::create(array_merge($commonData, [
            'user_id'        => $request->user_id,
            'receipt_number' => Payment::generateReceiptNumber(),
        ]));

        return redirect()->route('admin.payments.index')->with('success', 'Payment recorded successfully.');
    }

    /**
     * AJAX: whether the given member has already fully paid their obligation for the given
     * cycle, so the create-payment form can warn before a payment is recorded against the
     * wrong cycle. Uses the same obligation/paid calculation as everywhere else in the app.
     */
    public function checkStatus(Request $request)
    {
        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'dues_cycle_id' => 'required|string',
        ]);

        if ($request->dues_cycle_id === 'general') {
            return response()->json(['settled' => false]);
        }

        $cycle = DuesCycle::find($request->dues_cycle_id);
        $user  = User::find($request->user_id);

        if (! $cycle || ! $user) {
            return response()->json(['settled' => false]);
        }

        if ($cycle->is_pledge_based) {
            $pledge = MemberPledge::where('user_id', $user->id)->where('dues_cycle_id', $cycle->id)->first();

            if (! $pledge) {
                return response()->json(['settled' => false]);
            }

            $obligation      = (float) $pledge->pledged_amount;
            $mergeWithSpouse = (bool) $pledge->shared_with_spouse;
        } else {
            $obligation      = $user->obligationFor($cycle);
            $mergeWithSpouse = $cycle->couple_shared;
        }

        $paid      = $user->totalPaidWithSpouse($cycle->id, $mergeWithSpouse);
        $remaining = $obligation - $paid;

        return response()->json([
            'settled'    => $remaining <= 0 && $obligation != 0,
            'obligation' => round($obligation, 2),
            'paid'       => round($paid, 2),
            'remaining'  => round($remaining, 2),
        ]);
    }

    public function show(Payment $payment)
    {
        $payment->load(['user', 'duesCycle', 'recordedBy', 'pairedPayment.user']);
        return view('payment.manual.show', compact('payment'));
    }

    public function update(Request $request, Payment $payment)
    {
        $request->validate([
            'status' => 'required|in:completed,failed,refunded',
            'notes'  => 'nullable|string|max:1000',
        ]);

        $payment->update($request->only('status', 'notes'));

        return back()->with('success', 'Payment updated.');
    }
}
