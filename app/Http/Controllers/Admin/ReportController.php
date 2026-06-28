<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\MemberPledge;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use League\Csv\Writer;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function financial(Request $request)
    {
        $year    = max(2026, (int) $request->get('year', max(2026, now()->year)));
        $cycleId = $request->get('cycle_id', 'all');
        $showDetail = $request->boolean('detail', false);

        $cycles = DuesCycle::whereIn('status', ['active', 'closed'])
            ->where(function ($q) use ($year) {
                $q->whereYear('start_date', $year)->orWhereYear('end_date', $year);
            })
            ->orderBy('start_date')
            ->get();

        $totalMembers  = User::where('role', 'member')->where('status', 'active')->count();
        $selectedCycle = null;
        $mode          = 'all';
        $reportData    = [];

        if ($cycleId !== 'all' && is_numeric($cycleId)) {
            $selectedCycle = DuesCycle::findOrFail($cycleId);
            if ($selectedCycle->is_pledge_based) {
                $mode       = 'pledge';
                $reportData = $this->buildPledgeReport($selectedCycle, $totalMembers, $showDetail);
            } else {
                $mode       = 'fixed';
                $reportData = $this->buildFixedReport($selectedCycle, $totalMembers, $showDetail);
            }
        } else {
            $reportData = $this->buildYearReport($year, $cycles, $totalMembers);
        }

        return view('admin.reports.financial', array_merge($reportData, compact(
            'year', 'cycleId', 'cycles', 'totalMembers', 'selectedCycle', 'showDetail', 'mode'
        )));
    }

    public function financialExportCsv(Request $request)
    {
        $year    = max(2026, (int) $request->get('year', max(2026, now()->year)));
        $cycleId = $request->get('cycle_id', 'all');

        $cycles = DuesCycle::whereIn('status', ['active', 'closed'])
            ->where(function ($q) use ($year) {
                $q->whereYear('start_date', $year)->orWhereYear('end_date', $year);
            })
            ->orderBy('start_date')
            ->get();

        $totalMembers  = User::where('role', 'member')->where('status', 'active')->count();
        $selectedCycle = null;
        $data          = [];

        if ($cycleId !== 'all' && is_numeric($cycleId)) {
            $selectedCycle = DuesCycle::findOrFail($cycleId);
            $data = $selectedCycle->is_pledge_based
                ? $this->buildPledgeReport($selectedCycle, $totalMembers, true)
                : $this->buildFixedReport($selectedCycle, $totalMembers, true);
        } else {
            $data = $this->buildYearReport($year, $cycles, $totalMembers);
        }

        $filename = 'acm-financial-' . $year . ($cycleId !== 'all' ? '-cycle-' . $cycleId : '-all') . '.csv';

        $csv = Writer::createFromString();

        // Header
        $csv->insertOne(['ACM Financial Report']);
        $csv->insertOne(['Year: ' . $year]);
        $csv->insertOne(['Scope: ' . ($selectedCycle?->title ?? 'All Cycles')]);
        $csv->insertOne(['Generated: ' . now()->format('d M Y H:i')]);
        $csv->insertOne(['Active Members: ' . $totalMembers]);
        $csv->insertOne([]);

        if ($selectedCycle) {
            if ($selectedCycle->is_pledge_based) {
                $csv->insertOne(['PLEDGE SUMMARY']);
                $csv->insertOne(['Members Pledged',     $data['pledgerCount']]);
                $csv->insertOne(['Members Not Pledged', $data['nonPledgerCount']]);
                $csv->insertOne(['Total Pledged (£)',   number_format($data['totalPledged'], 2)]);
                $csv->insertOne(['Total Collected (£)', number_format($data['totalCollected'], 2)]);
                $csv->insertOne(['Fulfillment Rate',    $data['pledgeFulfillmentRate'] . '%']);
                $csv->insertOne([]);

                if (!empty($data['memberDetail'])) {
                    $csv->insertOne(['MEMBER DETAIL']);
                    $csv->insertOne(['Name', 'Pledged (£)', 'Paid (£)', 'Balance (£)', 'Status']);
                    foreach ($data['memberDetail'] as $m) {
                        $csv->insertOne([
                            $m['name'],
                            number_format($m['pledged'], 2),
                            number_format($m['paid'], 2),
                            number_format($m['balance'], 2),
                            $m['has_pledged'] ? 'Pledged' : 'Not Pledged',
                        ]);
                    }
                    $csv->insertOne([]);
                }
            } else {
                $csv->insertOne(['FIXED DUES SUMMARY']);
                $csv->insertOne(['Dues per Member (£)',  number_format($selectedCycle->amount, 2)]);
                $csv->insertOne(['Total Expected (£)',   number_format($data['totalExpected'], 2)]);
                $csv->insertOne(['Total Collected (£)',  number_format($data['totalCollected'], 2)]);
                $csv->insertOne(['Outstanding (£)',      number_format($data['totalExpected'] - $data['totalCollected'], 2)]);
                $csv->insertOne(['Collection Rate',      $data['collectionRate'] . '%']);
                $csv->insertOne(['Members Paid',         $data['paidCount']]);
                $csv->insertOne(['Members Unpaid',       $data['unpaidCount']]);
                $csv->insertOne([]);

                if (!empty($data['memberDetail'])) {
                    $csv->insertOne(['MEMBER DETAIL']);
                    $csv->insertOne(['Name', 'Obligation (£)', 'Paid (£)', 'Balance (£)', 'Status']);
                    foreach ($data['memberDetail'] as $m) {
                        $csv->insertOne([
                            $m['name'],
                            number_format($m['obligation'], 2),
                            number_format($m['paid'], 2),
                            number_format($m['balance'], 2),
                            ucfirst($m['status']),
                        ]);
                    }
                    $csv->insertOne([]);
                }
            }
        } else {
            // Year summary
            $csv->insertOne(['YEAR SUMMARY']);
            $csv->insertOne(['Total Collected (£)', number_format($data['totalCollected'], 2)]);
            $csv->insertOne([]);

            if ($data['fixedCycles']->count()) {
                $csv->insertOne(['FIXED DUES CYCLES']);
                $csv->insertOne(['Cycle', 'Rate/Member (£)', 'Expected (£)', 'Collected (£)', 'Collection Rate', 'Members Paid']);
                foreach ($data['fixedCycles'] as $fc) {
                    $csv->insertOne([
                        $fc['cycle']->title,
                        number_format($fc['cycle']->amount, 2),
                        number_format($fc['expected'], 2),
                        number_format($fc['collected'], 2),
                        $fc['rate'] . '%',
                        $fc['paid_count'],
                    ]);
                }
                $csv->insertOne([]);
            }

            if ($data['pledgeCycles']->count()) {
                $csv->insertOne(['PLEDGE / DONATION CYCLES']);
                $csv->insertOne(['Cycle', 'Total Pledged (£)', 'Collected (£)', 'Fulfillment Rate', 'Pledgers']);
                foreach ($data['pledgeCycles'] as $pc) {
                    $csv->insertOne([
                        $pc['cycle']->title,
                        number_format($pc['pledged'], 2),
                        number_format($pc['collected'], 2),
                        $pc['fulfillment'] . '%',
                        $pc['pledger_count'],
                    ]);
                }
                $csv->insertOne([]);
            }
        }

        // Monthly breakdown (always included)
        $csv->insertOne(['MONTHLY COLLECTIONS (£)']);
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $csv->insertOne($months);
        $csv->insertOne(array_map(fn($v) => number_format($v, 2), $data['chartData']));

        // Payment method breakdown
        if ($data['methodBreakdown']->count()) {
            $csv->insertOne([]);
            $csv->insertOne(['PAYMENT METHOD BREAKDOWN']);
            $csv->insertOne(['Method', 'Total (£)', 'Transactions']);
            foreach ($data['methodBreakdown'] as $mb) {
                $csv->insertOne([
                    ucfirst(str_replace('_', ' ', $mb->method ?? 'unknown')),
                    number_format($mb->total, 2),
                    $mb->count,
                ]);
            }
        }

        return response((string) $csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ── Private report builders ───────────────────────────────────────────────

    private function buildFixedReport(DuesCycle $cycle, int $totalMembers, bool $showDetail): array
    {
        $totalExpected  = $cycle->amount * $totalMembers;
        $totalCollected = (float) Payment::where('dues_cycle_id', $cycle->id)
            ->where('status', 'completed')->sum('amount');
        $collectionRate = $totalExpected > 0
            ? round($totalCollected / $totalExpected * 100, 1) : 0;

        $paidMemberIds = Payment::where('dues_cycle_id', $cycle->id)
            ->where('status', 'completed')
            ->distinct('user_id')
            ->pluck('user_id');
        $paidCount   = $paidMemberIds->count();
        $unpaidCount = $totalMembers - $paidCount;

        $chartData       = $this->monthlyChart($cycle->id);
        $methodBreakdown = $this->methodBreakdown($cycle->id);

        $memberDetail = null;
        if ($showDetail) {
            $memberDetail = User::where('role', 'member')->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(function ($m) use ($cycle) {
                    $paid = $m->totalPaid($cycle->id);
                    return [
                        'name'       => $m->name,
                        'obligation' => $cycle->amount,
                        'paid'       => $paid,
                        'balance'    => max(0, $cycle->amount - $paid),
                        'status'     => $paid >= $cycle->amount ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                    ];
                })
                ->values();
        }

        return compact(
            'totalExpected', 'totalCollected', 'collectionRate',
            'paidCount', 'unpaidCount', 'chartData', 'methodBreakdown', 'memberDetail'
        );
    }

    private function buildPledgeReport(DuesCycle $cycle, int $totalMembers, bool $showDetail): array
    {
        $totalPledged          = (float) MemberPledge::where('dues_cycle_id', $cycle->id)->sum('pledged_amount');
        $totalCollected        = (float) Payment::where('dues_cycle_id', $cycle->id)
            ->where('status', 'completed')->sum('amount');
        $pledgeFulfillmentRate = $totalPledged > 0
            ? round($totalCollected / $totalPledged * 100, 1) : 0;

        $pledgerCount    = MemberPledge::where('dues_cycle_id', $cycle->id)->distinct('user_id')->count('user_id');
        $nonPledgerCount = $totalMembers - $pledgerCount;

        $chartData       = $this->monthlyChart($cycle->id);
        $methodBreakdown = $this->methodBreakdown($cycle->id);

        $memberDetail = null;
        if ($showDetail) {
            $pledgesByUser = MemberPledge::where('dues_cycle_id', $cycle->id)
                ->pluck('pledged_amount', 'user_id');

            $memberDetail = User::where('role', 'member')->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(function ($m) use ($cycle, $pledgesByUser) {
                    $pledged = (float) ($pledgesByUser[$m->id] ?? 0);
                    $paid    = $m->totalPaid($cycle->id);
                    return [
                        'name'        => $m->name,
                        'pledged'     => $pledged,
                        'paid'        => $paid,
                        'balance'     => $pledged > 0 ? max(0, $pledged - $paid) : 0,
                        'has_pledged' => isset($pledgesByUser[$m->id]),
                    ];
                })
                ->values();
        }

        return compact(
            'totalPledged', 'totalCollected', 'pledgeFulfillmentRate',
            'pledgerCount', 'nonPledgerCount', 'chartData', 'methodBreakdown', 'memberDetail'
        );
    }

    private function buildYearReport(int $year, $cycles, int $totalMembers): array
    {
        $chartData      = $this->monthlyChart(null, $year);
        $totalCollected = (float) array_sum($chartData);
        $methodBreakdown = $this->methodBreakdown(null, $year);

        $fixedCycles = $cycles->where('is_pledge_based', false)
            ->map(function ($c) use ($totalMembers) {
                $collected = (float) Payment::where('dues_cycle_id', $c->id)
                    ->where('status', 'completed')->sum('amount');
                $expected  = $c->amount * $totalMembers;
                $paidCount = Payment::where('dues_cycle_id', $c->id)
                    ->where('status', 'completed')
                    ->distinct('user_id')->count('user_id');
                return [
                    'cycle'      => $c,
                    'collected'  => $collected,
                    'expected'   => $expected,
                    'rate'       => $expected > 0 ? round($collected / $expected * 100, 1) : 0,
                    'paid_count' => $paidCount,
                ];
            })->values();

        $pledgeCycles = $cycles->where('is_pledge_based', true)
            ->map(function ($c) {
                $pledged      = (float) MemberPledge::where('dues_cycle_id', $c->id)->sum('pledged_amount');
                $collected    = (float) Payment::where('dues_cycle_id', $c->id)
                    ->where('status', 'completed')->sum('amount');
                $pledgerCount = MemberPledge::where('dues_cycle_id', $c->id)
                    ->distinct('user_id')->count('user_id');
                return [
                    'cycle'         => $c,
                    'pledged'       => $pledged,
                    'collected'     => $collected,
                    'fulfillment'   => $pledged > 0 ? round($collected / $pledged * 100, 1) : 0,
                    'pledger_count' => $pledgerCount,
                ];
            })->values();

        return compact('chartData', 'totalCollected', 'fixedCycles', 'pledgeCycles', 'methodBreakdown');
    }

    private function monthlyChart(?int $cycleId, ?int $year = null): array
    {
        $q = Payment::where('status', 'completed')
            ->selectRaw('MONTH(payment_date) as month, SUM(amount) as total')
            ->groupBy('month');

        if ($cycleId) {
            $q->where('dues_cycle_id', $cycleId);
        } elseif ($year) {
            $q->whereYear('payment_date', $year);
        }

        $totals = $q->pluck('total', 'month')->toArray();
        return array_map(fn($m) => (float) ($totals[$m] ?? 0), range(1, 12));
    }

    private function methodBreakdown(?int $cycleId, ?int $year = null)
    {
        $q = Payment::where('status', 'completed')
            ->selectRaw('method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('method');

        if ($cycleId) {
            $q->where('dues_cycle_id', $cycleId);
        } elseif ($year) {
            $q->whereYear('payment_date', $year);
        }

        return $q->get();
    }

    // ── Other reports ─────────────────────────────────────────────────────────

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
            ->withSum(['payments as total_paid' => fn($q) => $q->where('status', 'completed')], 'amount')
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
