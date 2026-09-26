<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\MemberLegacyBalance;
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
        $legacySearch = trim($request->get('legacy_search', ''));
        $legacySort   = in_array($request->get('legacy_sort'), ['name', 'year', 'amount']) ? $request->get('legacy_sort') : 'amount';
        $legacyDir    = $request->get('legacy_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        // 'all' is used by the Print/PDF button so the printout isn't silently
        // truncated to whatever page happened to be on screen.
        $showAll      = $request->get('per_page') === 'all';

        // Historical debt is its own selectable "period" in the year dropdown, not tied to a calendar year
        if ($request->get('year') === 'legacy') {
            $year          = 'legacy';
            $cycleId       = 'all';
            $cycles        = collect();
            $totalMembers  = User::where('role', '!=', 'super_admin')->where('status', 'active')->count();
            $selectedCycle = null;
            $showDetail    = false;
            $sort          = 'name';
            $dir           = 'asc';
            $mode          = 'legacy';
            $txnSearch     = '';
            $txnSort       = 'date';
            $txnDir        = 'desc';

            $reportData = $this->buildLegacyReport($legacySearch, $legacySort, $legacyDir, $showAll);

            return view('admin.reports.financial', array_merge($reportData, compact(
                'year', 'cycleId', 'cycles', 'totalMembers', 'selectedCycle', 'showDetail', 'mode', 'sort', 'dir',
                'txnSearch', 'txnSort', 'txnDir', 'legacySearch', 'legacySort', 'legacyDir'
            )));
        }

        $year       = max(2026, (int) $request->get('year', max(2026, now()->year)));
        $cycleId    = $request->get('cycle_id', 'all');
        $showDetail = $request->boolean('detail', false);
        $sort       = $request->get('sort', 'name');
        $dir        = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $txnSearch  = trim($request->get('txn_search', ''));
        $txnSort    = in_array($request->get('txn_sort'), ['amount', 'date']) ? $request->get('txn_sort') : 'date';
        $txnDir     = $request->get('txn_dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $cycles = DuesCycle::whereIn('status', ['active', 'closed'])
            ->where(function ($q) use ($year) {
                $q->whereYear('start_date', $year)->orWhereYear('end_date', $year);
            })
            ->orderBy('start_date')
            ->get();

        $totalMembers  = User::where('role', '!=', 'super_admin')->where('status', 'active')->count();
        $selectedCycle = null;
        $mode          = 'all';
        $reportData    = [];

        if ($cycleId !== 'all' && is_numeric($cycleId)) {
            $selectedCycle = DuesCycle::findOrFail($cycleId);
            if ($selectedCycle->is_pledge_based) {
                $mode       = 'pledge';
                $reportData = $this->buildPledgeReport($selectedCycle, $totalMembers, $showDetail, $sort, $dir);
            } else {
                $mode       = 'fixed';
                $reportData = $this->buildFixedReport($selectedCycle, $totalMembers, $showDetail, $sort, $dir);
            }
        } else {
            $reportData = $this->buildYearReport($year, $cycles, $totalMembers, $txnSearch, $txnSort, $txnDir, $showAll);
        }

        return view('admin.reports.financial', array_merge($reportData, compact(
            'year', 'cycleId', 'cycles', 'totalMembers', 'selectedCycle', 'showDetail', 'mode', 'sort', 'dir',
            'txnSearch', 'txnSort', 'txnDir', 'legacySearch', 'legacySort', 'legacyDir'
        )));
    }

    public function financialExportCsv(Request $request)
    {
        if ($request->get('year') === 'legacy') {
            return $this->exportLegacyCsv();
        }

        $year    = max(2026, (int) $request->get('year', max(2026, now()->year)));
        $cycleId = $request->get('cycle_id', 'all');

        $cycles = DuesCycle::whereIn('status', ['active', 'closed'])
            ->where(function ($q) use ($year) {
                $q->whereYear('start_date', $year)->orWhereYear('end_date', $year);
            })
            ->orderBy('start_date')
            ->get();

        $totalMembers  = User::where('role', '!=', 'super_admin')->where('status', 'active')->count();
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
                $csv->insertOne(['Outstanding (£)',      number_format($data['totalOutstanding'], 2)]);
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

    private function exportLegacyCsv()
    {
        $legacyRows = MemberLegacyBalance::with('user')->orderByDesc('amount')->get();

        $csv = Writer::createFromString();
        $csv->insertOne(['ACM Historical Debt Report (Pre-2026 Carryover)']);
        $csv->insertOne(['Generated: ' . now()->format('d M Y H:i')]);
        $csv->insertOne(['Total Outstanding (£): ' . number_format($legacyRows->sum('amount'), 2)]);
        $csv->insertOne(['Members Affected: ' . $legacyRows->pluck('user_id')->unique()->count()]);
        $csv->insertOne([]);
        $csv->insertOne(['Name', 'Description', 'Year', 'Amount (£)']);
        foreach ($legacyRows as $lb) {
            $csv->insertOne([
                $lb->user?->name ?? '—',
                $lb->label,
                $lb->year,
                number_format($lb->amount, 2),
            ]);
        }

        return response((string) $csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="acm-historical-debt.csv"',
        ]);
    }

    // ── Private report builders ───────────────────────────────────────────────

    private function buildFixedReport(DuesCycle $cycle, int $totalMembers, bool $showDetail, string $sort = 'name', string $dir = 'asc'): array
    {
        $totalCollected = (float) Payment::where('dues_cycle_id', $cycle->id)
            ->where('status', 'completed')->sum('amount');

        // Per-member obligation/payment using the same couple-aware logic as
        // the Arrears report (obligationFor + totalPaidWithSpouse), so the two
        // reports always tally for the same cycle instead of using different math.
        $rows = User::where('role', '!=', 'super_admin')->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($m) use ($cycle) {
                $obligation = $m->obligationFor($cycle);
                $paid       = $m->totalPaidWithSpouse($cycle->id, $cycle->couple_shared);
                return [
                    'name'       => $m->name,
                    'obligation' => $obligation,
                    'paid'       => $paid,
                    'balance'    => max(0, $obligation - $paid),
                    'status'     => $paid >= $obligation ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                ];
            });

        $totalExpected    = $rows->sum('obligation');
        $totalOutstanding = $rows->sum('balance');
        $collectionRate   = $totalExpected > 0
            ? round($totalCollected / $totalExpected * 100, 1) : 0;

        $paidCount   = $rows->where('status', 'paid')->count();
        $unpaidCount = $rows->count() - $paidCount;

        $chartData       = $this->monthlyChart($cycle->id);
        $methodBreakdown = $this->methodBreakdown($cycle->id);

        $memberDetail = null;
        if ($showDetail) {
            $validSorts = ['name', 'obligation', 'paid', 'balance', 'status'];
            $sortKey    = in_array($sort, $validSorts) ? $sort : 'name';

            $memberDetail = ($dir === 'desc' ? $rows->sortByDesc($sortKey) : $rows->sortBy($sortKey))->values();
        }

        return compact(
            'totalExpected', 'totalCollected', 'totalOutstanding', 'collectionRate',
            'paidCount', 'unpaidCount', 'chartData', 'methodBreakdown', 'memberDetail'
        );
    }

    private function buildPledgeReport(DuesCycle $cycle, int $totalMembers, bool $showDetail, string $sort = 'name', string $dir = 'asc'): array
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
            $validSorts    = ['name', 'pledged', 'paid', 'balance'];
            $sortKey       = in_array($sort, $validSorts) ? $sort : 'name';
            $pledgesByUser = MemberPledge::where('dues_cycle_id', $cycle->id)
                ->pluck('pledged_amount', 'user_id');

            $rows = User::where('role', '!=', 'super_admin')->where('status', 'active')
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
                });

            $memberDetail = ($dir === 'desc' ? $rows->sortByDesc($sortKey) : $rows->sortBy($sortKey))->values();
        }

        return compact(
            'totalPledged', 'totalCollected', 'pledgeFulfillmentRate',
            'pledgerCount', 'nonPledgerCount', 'chartData', 'methodBreakdown', 'memberDetail'
        );
    }

    private function buildYearReport(int $year, $cycles, int $totalMembers, string $txnSearch = '', string $txnSort = 'date', string $txnDir = 'desc', bool $showAll = false): array
    {
        $chartData       = $this->monthlyChart(null, $year);
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

        // Derive total from cycle sums so the KPI matches the breakdown table
        $totalCollected = $fixedCycles->sum('collected') + $pledgeCycles->sum('collected');

        // Individual payment records for all cycles in this year
        $cycleIds = $cycles->pluck('id');

        $paymentsQuery = $cycleIds->isEmpty() ? null : Payment::where('status', 'completed')
            ->whereIn('dues_cycle_id', $cycleIds)
            ->when($txnSearch !== '', fn($q) => $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$txnSearch}%")));

        $txnTotal = $paymentsQuery ? (clone $paymentsQuery)->sum('amount') : 0;

        $txnPerPage = $showAll ? 100000 : 20;

        $annualDuesPayments = $paymentsQuery
            ? $paymentsQuery->with(['user', 'duesCycle'])
                ->orderBy($txnSort === 'amount' ? 'amount' : 'payment_date', $txnDir === 'asc' ? 'asc' : 'desc')
                ->paginate($txnPerPage)
                ->withQueryString()
            : new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, $txnPerPage);

        return compact('chartData', 'totalCollected', 'fixedCycles', 'pledgeCycles', 'methodBreakdown', 'annualDuesPayments', 'txnTotal');
    }

    private function buildLegacyReport(string $legacySearch, string $legacySort, string $legacyDir, bool $showAll = false): array
    {
        $legacyQuery = MemberLegacyBalance::query()
            ->join('users', 'users.id', '=', 'member_legacy_balances.user_id')
            ->when($legacySearch !== '', fn($q) => $q->where('users.name', 'like', "%{$legacySearch}%"))
            ->select('member_legacy_balances.*', 'users.name as member_name');

        $legacyGrandTotal  = (clone $legacyQuery)->sum('member_legacy_balances.amount');
        $legacyMemberCount = (clone $legacyQuery)->distinct('member_legacy_balances.user_id')->count('member_legacy_balances.user_id');

        $legacySortColumn = match ($legacySort) {
            'name' => 'users.name',
            'year' => 'member_legacy_balances.year',
            default => 'member_legacy_balances.amount',
        };

        $legacyBalances = $legacyQuery
            ->orderBy($legacySortColumn, $legacyDir === 'asc' ? 'asc' : 'desc')
            ->paginate($showAll ? 100000 : 20, ['*'], 'legacy_page')
            ->withQueryString();

        return compact('legacyBalances', 'legacyGrandTotal', 'legacyMemberCount');
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
        $search  = trim($request->get('search', ''));
        $sort    = in_array($request->get('sort'), ['name', 'obligation', 'paid', 'outstanding'])
                   ? $request->get('sort') : 'outstanding';
        $dir     = $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc';
        // 'all' is used by the Print/PDF button so the printout isn't silently
        // truncated to whatever page happened to be on screen.
        $showAll = $request->get('per_page') === 'all';
        $perPage = $showAll
            ? null
            : (in_array((int) $request->get('per_page'), [10, 25, 50, 100]) ? (int) $request->get('per_page') : 25);

        $cycles = DuesCycle::whereIn('status', ['active', 'closed'])->orderByDesc('start_date')->get();

        $arrearsMembers   = collect();
        $totalOutstanding = 0;
        $totalInArrears   = 0;

        if ($cycleId) {
            $cycle = DuesCycle::findOrFail($cycleId);

            // Build full arrears list (unfiltered) for summary totals
            $all = User::where('role', '!=', 'super_admin')
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(function ($m) use ($cycle) {
                    $obligation  = $m->obligationFor($cycle);
                    $paid        = $m->totalPaidWithSpouse($cycle->id, $cycle->couple_shared);
                    $outstanding = max(0, $obligation - $paid);
                    $m->obligation  = $obligation;
                    $m->paid        = $paid;
                    $m->outstanding = $outstanding;
                    $m->spouseName  = $m->spouse()?->name;
                    return $m;
                })
                ->filter(fn($m) => $m->outstanding > 0)
                ->values();

            $totalOutstanding = $all->sum('outstanding');
            $totalInArrears   = $all->count();

            // Apply name search
            $filtered = $search !== ''
                ? $all->filter(fn($m) => str_contains(strtolower($m->name), strtolower($search)))->values()
                : $all;

            // Sort
            $sorted = $dir === 'desc'
                ? $filtered->sortByDesc($sort)->values()
                : $filtered->sortBy($sort)->values();

            if ($showAll) {
                $perPage = max(1, $sorted->count());
            }

            // Paginate the sorted collection
            $page   = max(1, (int) $request->get('page', 1));
            $offset = ($page - 1) * $perPage;

            $arrearsMembers = new \Illuminate\Pagination\LengthAwarePaginator(
                $sorted->slice($offset, $perPage)->values(),
                $sorted->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        $perPage = $perPage ?? 25;

        return view('admin.reports.arrears', compact(
            'cycles', 'cycleId', 'arrearsMembers',
            'search', 'sort', 'dir', 'perPage',
            'totalOutstanding', 'totalInArrears'
        ));
    }

    public function arrearsExportCsv(Request $request)
    {
        $cycleId = $request->get('cycle_id');
        $search  = trim($request->get('search', ''));
        $sort    = in_array($request->get('sort'), ['name', 'obligation', 'paid', 'outstanding'])
                   ? $request->get('sort') : 'outstanding';
        $dir     = $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        abort_if(! $cycleId, 404);

        $cycle = DuesCycle::findOrFail($cycleId);

        $rows = User::where('role', '!=', 'super_admin')
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($m) use ($cycle) {
                $obligation  = $m->obligationFor($cycle);
                $paid        = $m->totalPaidWithSpouse($cycle->id, $cycle->couple_shared);
                $outstanding = max(0, $obligation - $paid);
                $m->obligation  = $obligation;
                $m->paid        = $paid;
                $m->outstanding = $outstanding;
                $m->spouseName  = $m->spouse()?->name;
                return $m;
            })
            ->filter(fn($m) => $m->outstanding > 0)
            ->values();

        if ($search !== '') {
            $rows = $rows->filter(
                fn($m) => str_contains(strtolower($m->name), strtolower($search))
            )->values();
        }

        $rows = $dir === 'desc'
            ? $rows->sortByDesc($sort)->values()
            : $rows->sortBy($sort)->values();

        $slug     = preg_replace('/[^a-z0-9]+/i', '-', strtolower($cycle->title));
        $suffix   = $search !== '' ? '-' . preg_replace('/[^a-z0-9]/i', '_', $search) : '';
        $filename = "arrears-{$slug}{$suffix}.csv";

        $csv = Writer::createFromString();

        // Report header
        $csv->insertOne(['ACM Arrears Report']);
        $csv->insertOne(['Cycle: ' . $cycle->title]);
        $csv->insertOne(['Generated: ' . now()->format('d M Y H:i')]);
        if ($search !== '') {
            $csv->insertOne(['Filter: ' . $search]);
        }
        $csv->insertOne(['Total in Arrears: ' . $rows->count()]);
        $csv->insertOne(['Total Outstanding: £' . number_format($rows->sum('outstanding'), 2)]);
        $csv->insertOne([]);

        // Column headers
        $csv->insertOne(['Name', 'Phone', 'Email', 'Spouse', 'Obligation (£)', 'Paid (£)', 'Outstanding (£)']);

        foreach ($rows as $m) {
            $csv->insertOne([
                $m->name,
                $m->phone ?? '',
                $m->email ?? '',
                $m->spouseName ?? '',
                number_format($m->obligation, 2),
                number_format($m->paid, 2),
                number_format($m->outstanding, 2),
            ]);
        }

        return response((string) $csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function memberSummary()
    {
        $members = User::where('role', '!=', 'super_admin')
            ->withSum(['payments as total_paid' => fn($q) => $q->where('status', 'completed')], 'amount')
            ->withCount('payments')
            ->paginate(25);

        $genderCounts = User::where('role', '!=', 'super_admin')
            ->where('status', 'active')
            ->selectRaw("gender, COUNT(*) as total")
            ->groupBy('gender')
            ->pluck('total', 'gender');

        $childGenderCounts = \App\Models\MemberChild::selectRaw("gender, COUNT(*) as total")
            ->groupBy('gender')
            ->pluck('total', 'gender');

        return view('admin.reports.members', compact('members', 'genderCounts', 'childGenderCounts'));
    }

    public function engagement(Request $request)
    {
        $search  = trim($request->get('search', ''));
        $sort    = in_array($request->get('sort'), ['name', 'last_meeting_date', 'last_payment_date'])
                   ? $request->get('sort') : 'last_meeting_date';
        $dir     = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';
        // 'all' is used by the Print/PDF button so the printout isn't silently
        // truncated to whatever page happened to be on screen.
        $showAll = $request->get('per_page') === 'all';
        $perPage = $showAll
            ? null
            : (in_array((int) $request->get('per_page'), [10, 25, 50, 100]) ? (int) $request->get('per_page') : 25);

        $rows = $this->buildEngagementData($search);

        $sorted = $dir === 'desc' ? $rows->sortByDesc($sort)->values() : $rows->sortBy($sort)->values();

        if ($showAll) {
            $perPage = max(1, $sorted->count());
        }

        $page   = max(1, (int) $request->get('page', 1));
        $offset = ($page - 1) * $perPage;

        $members = new \Illuminate\Pagination\LengthAwarePaginator(
            $sorted->slice($offset, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $totalMembers  = $rows->count();
        $neverAttended = $rows->whereNull('last_meeting_date')->count();
        $neverPaid     = $rows->whereNull('last_payment_date')->count();

        return view('admin.reports.engagement', compact(
            'members', 'search', 'sort', 'dir', 'perPage',
            'totalMembers', 'neverAttended', 'neverPaid'
        ));
    }

    public function engagementExportCsv(Request $request)
    {
        $search = trim($request->get('search', ''));
        $sort   = in_array($request->get('sort'), ['name', 'last_meeting_date', 'last_payment_date'])
                  ? $request->get('sort') : 'last_meeting_date';
        $dir    = $request->get('dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $rows = $this->buildEngagementData($search);
        $rows = $dir === 'desc' ? $rows->sortByDesc($sort)->values() : $rows->sortBy($sort)->values();

        $filename = 'acm-member-engagement' . ($search !== '' ? '-' . preg_replace('/[^a-z0-9]/i', '_', $search) : '') . '.csv';

        $csv = Writer::createFromString();
        $csv->insertOne(['ACM Member Engagement Report']);
        $csv->insertOne(['Generated: ' . now()->format('d M Y H:i')]);
        if ($search !== '') {
            $csv->insertOne(['Filter: ' . $search]);
        }
        $csv->insertOne(['Total Members: ' . $rows->count()]);
        $csv->insertOne([]);
        $csv->insertOne(['Name', 'Phone', 'Last Meeting Attended', 'Last Dues Payment']);

        foreach ($rows as $m) {
            $csv->insertOne([
                $m['name'],
                $m['phone'] ?? '',
                $m['last_meeting_date'] ? \Carbon\Carbon::parse($m['last_meeting_date'])->format('d M Y') : 'Never',
                $m['last_payment_date'] ? \Carbon\Carbon::parse($m['last_payment_date'])->format('d M Y') : 'Never',
            ]);
        }

        return response((string) $csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Shared builder for the Member Engagement report (view + CSV export) —
     * one active-member row per member with their most recent attended
     * meeting date and most recent completed dues payment date, regardless
     * of year/cycle, so long-absent or long-unpaid members surface via sort.
     */
    private function buildEngagementData(string $search = '')
    {
        $lastMeetingByUser = \App\Models\AttendanceRecord::query()
            ->join('meetings', 'meetings.id', '=', 'attendance_records.meeting_id')
            ->whereIn('attendance_records.status', ['present', 'late'])
            ->selectRaw('attendance_records.user_id, MAX(meetings.meeting_date) as last_meeting_date')
            ->groupBy('attendance_records.user_id')
            ->pluck('last_meeting_date', 'user_id');

        $lastPaymentByUser = Payment::where('status', 'completed')
            ->selectRaw('user_id, MAX(payment_date) as last_payment_date')
            ->groupBy('user_id')
            ->pluck('last_payment_date', 'user_id');

        return User::where('role', '!=', 'super_admin')
            ->where('status', 'active')
            ->when($search !== '', fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get()
            ->map(fn($m) => [
                'id'                => $m->id,
                'name'              => $m->name,
                'phone'             => $m->phone,
                'last_meeting_date' => $lastMeetingByUser[$m->id] ?? null,
                'last_payment_date' => $lastPaymentByUser[$m->id] ?? null,
            ]);
    }
}
