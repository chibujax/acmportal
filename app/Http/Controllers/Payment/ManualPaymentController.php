<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\MemberRelationship;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class ManualPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->isFinancialSecretary()) {
                abort(403, 'Access denied. Financial Secretary or Admin role required.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = Payment::with(['user', 'duesCycle', 'recordedBy'])
            ->where('method', 'manual');

        if ($request->search) {
            $s = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%"));
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        return view('payment.manual.index', compact('payments'));
    }

    public function create()
    {
        $members = User::where('role', 'member')->where('status', 'active')
            ->orderBy('name')->get();
        $cycles  = DuesCycle::whereIn('status', ['active', 'closed'])->orderByDesc('start_date')->get();

        $membersWithSpouse = MemberRelationship::where('relationship_type', 'spouse')
            ->get(['member_id_1', 'member_id_2'])
            ->flatMap(fn($r) => [$r->member_id_1, $r->member_id_2])
            ->unique()
            ->flip();

        return view('payment.manual.create', compact('members', 'cycles', 'membersWithSpouse'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'      => 'required|exists:users,id',
            'dues_cycle_id'=> 'nullable|exists:dues_cycles,id',
            'amount'       => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes'        => 'nullable|string|max:1000',
            'proof'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('payment-proofs', 'public');
        }

        Payment::create([
            'user_id'         => $request->user_id,
            'dues_cycle_id'   => $request->dues_cycle_id,
            'amount'          => $request->amount,
            'currency'        => 'GBP',
            'method'          => 'manual',
            'status'          => 'completed',
            'recorded_by'     => auth()->id(),
            'receipt_number'  => Payment::generateReceiptNumber(),
            'notes'           => $request->notes,
            'payment_date'    => $request->payment_date,
            'proof_of_payment'=> $proofPath,
        ]);

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment)
    {
        $payment->load(['user', 'duesCycle', 'recordedBy']);
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
