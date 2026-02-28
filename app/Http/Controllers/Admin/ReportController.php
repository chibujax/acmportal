<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function financial(Request $request)
    {
        $year = $request->get('year', now()->year);

        $monthlyTotals = Payment::selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->where('status', 'completed')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Fill missing months with 0
        $chartData = array_map(fn($m) => $monthlyTotals[$m] ?? 0, range(1, 12));

        $cycleStats = DuesCycle::withSum(['payments as collected' => fn($q) => $q->where('status','completed')], 'amount')
            ->withCount(['payments as payers' => fn($q) => $q->where('status','completed')->select(\DB::raw('DISTINCT user_id'))])
            ->get();

        $totalMembers  = User::where('role', 'member')->where('status', 'active')->count();
        $totalCollected = Payment::where('status', 'completed')->whereYear('created_at', $year)->sum('amount');

        return view('admin.reports.financial', compact(
            'chartData', 'year', 'cycleStats', 'totalMembers', 'totalCollected'
        ));
    }

    public function arrears(Request $request)
    {
        $cycleId = $request->get('cycle_id');
        $cycles  = DuesCycle::whereIn('status', ['active', 'closed'])->orderByDesc('start_date')->get();

        $arrearsMembers = collect();

        if ($cycleId) {
            $cycle = DuesCycle::findOrFail($cycleId);

            $arrearsMembers = User::where('role', 'member')
                ->where('status', 'active')
                ->get()
                ->map(function ($m) use ($cycle) {
                    $obligation = $m->obligationFor($cycle);
                    $paid       = $m->totalPaidWithSpouse($cycle->id);
                    $outstanding = max(0, $obligation - $paid);
                    $m->obligation  = $obligation;
                    $m->paid        = $paid;
                    $m->outstanding = $outstanding;
                    $m->spouseName  = $m->spouse()?->name;
                    $m->cycle       = $cycle;
                    return $m;
                })
                ->filter(fn($m) => $m->outstanding > 0)
                ->values();
        }

        return view('admin.reports.arrears', compact('cycles', 'cycleId', 'arrearsMembers'));
    }

    public function memberSummary()
    {
        $members = User::where('role', 'member')
            ->withSum(['payments as total_paid' => fn($q) => $q->where('status','completed')], 'amount')
            ->withCount('payments')
            ->paginate(25);

        $genderCounts = User::where('role', 'member')
            ->where('status', 'active')
            ->selectRaw("gender, COUNT(*) as total")
            ->groupBy('gender')
            ->pluck('total', 'gender');

        $childGenderCounts = \App\Models\MemberChild::selectRaw("gender, COUNT(*) as total")
            ->groupBy('gender')
            ->pluck('total', 'gender');

        return view('admin.reports.members', compact('members', 'genderCounts', 'childGenderCounts'));
    }
}
