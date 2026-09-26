<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DuesCycleController;
use App\Http\Controllers\Admin\ReportController;
use App\Models\DuesCycle;
use App\Models\MemberLegacyBalance;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Covers the fix where pre-2026 legacy/carryover balances are folded into
 * whichever yearly-dues cycle is currently collecting (User::obligationFor()),
 * instead of sitting in a separate ledger that payments never touch.
 */
class DuesObligationTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = User::create([
            'name'     => 'Admin',
            'phone'    => '07000000000',
            'password' => Hash::make('password'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);
    }

    private function makeMember(string $phone, ?float $legacyAmount = null): User
    {
        $user = User::create([
            'name'     => 'Member ' . $phone,
            'phone'    => $phone,
            'password' => Hash::make('password'),
            'role'     => 'member',
            'status'   => 'active',
        ]);

        if ($legacyAmount !== null) {
            MemberLegacyBalance::create([
                'user_id' => $user->id,
                'label'   => 'Carried over (pre-2026)',
                'year'    => 2025,
                'amount'  => $legacyAmount,
            ]);
        }

        return $user;
    }

    private function makeCycle(array $overrides = []): DuesCycle
    {
        return DuesCycle::create(array_merge([
            'title'           => 'Annual Dues 2026',
            'type'            => 'yearly_dues',
            'amount'          => 60,
            'currency'        => 'GBP',
            'start_date'      => '2026-01-01',
            'end_date'        => '2026-12-31',
            'payment_options' => 'once',
            'status'          => 'active',
            'couple_shared'   => false,
            'is_pledge_based' => false,
            'accepts_items'   => false,
            'created_by'      => $this->creator->id,
        ], $overrides));
    }

    public function test_legacy_folds_only_into_the_current_active_yearly_dues_cycle(): void
    {
        $member = $this->makeMember('07100000001', 20.00);

        $activeYearlyDues = $this->makeCycle();

        $closedPriorYearlyDues = $this->makeCycle([
            'title' => 'Annual Dues 2025', 'status' => 'closed',
            'start_date' => '2025-01-01', 'end_date' => '2025-12-31',
        ]);

        $donationCycle = $this->makeCycle([
            'title' => 'Iriji 2026 Donation', 'type' => 'donation', 'amount' => 0,
        ]);

        $this->assertSame(80.0, $member->obligationFor($activeYearlyDues));
        $this->assertSame(60.0, $member->obligationFor($closedPriorYearlyDues));
        $this->assertSame(0.0, $member->obligationFor($donationCycle));
    }

    public function test_payment_covering_dues_and_carryover_settles_both(): void
    {
        // The Oluchi Okoye scenario: £60 dues + £20 carryover, paid £80 in one go.
        $member = $this->makeMember('07100000002', 20.00);
        $cycle  = $this->makeCycle();

        Payment::create([
            'user_id'       => $member->id,
            'dues_cycle_id' => $cycle->id,
            'amount'        => 80,
            'status'        => 'completed',
            'method'        => 'manual',
            'payment_date'  => now(),
        ]);

        $obligation = $member->obligationFor($cycle);
        $paid       = $member->totalPaidWithSpouse($cycle->id, $cycle->couple_shared);

        $this->assertSame(80.0, $obligation);
        $this->assertSame(80.0, $paid);
        $this->assertSame(0.0, $obligation - $paid);
        $this->assertSame(0.0, $member->unfoldedLegacyBalance());
    }

    public function test_credit_larger_than_dues_shows_as_negative_and_is_excluded_from_arrears(): void
    {
        // The association owes this member £100 from before 2026; this year's dues is only £60.
        $member = $this->makeMember('07100000003', -100.00);
        $cycle  = $this->makeCycle();

        $obligation = $member->obligationFor($cycle);
        $this->assertSame(-40.0, $obligation);

        // Total outstanding should show the credit as negative, not clamp it away.
        $remaining = $obligation - $member->totalPaidWithSpouse($cycle->id, $cycle->couple_shared);
        $this->assertSame(-40.0, $remaining);

        // The Dues Cycle detail page's per-member row should surface the credit.
        $response = app(DuesCycleController::class)->show($cycle, new Request());
        $row = collect($response->getData()['members']->items())->firstWhere('id', $member->id);

        $this->assertNotNull($row);
        $this->assertSame(-40.0, $row->remaining);
        $this->assertTrue($row->settled);

        // Arrears (which only lists outstanding > 0) must not list a member in credit.
        $arrears = app(ReportController::class)->arrears(new Request(['cycle_id' => $cycle->id]));
        $arrearsIds = collect($arrears->getData()['arrearsMembers']->items())->pluck('id');

        $this->assertFalse($arrearsIds->contains($member->id));
    }

    public function test_member_with_no_legacy_balance_is_unaffected(): void
    {
        $member = $this->makeMember('07100000004');
        $cycle  = $this->makeCycle();

        $this->assertSame(60.0, $member->obligationFor($cycle));
        $this->assertSame(0.0, $member->unfoldedLegacyBalance());
    }

    public function test_member_joining_mid_cycle_year_owes_a_prorated_amount(): void
    {
        // June (zero-based month 5) -> owes 7 of 12 months: (60/12)*7 = 35.
        $member = $this->makeMember('07100000005');
        $member->update(['join_date' => '2026-06-15']);
        $cycle = $this->makeCycle();

        $this->assertSame(35.0, $member->obligationFor($cycle));
    }

    public function test_member_joining_in_january_owes_the_full_amount(): void
    {
        $member = $this->makeMember('07100000006');
        $member->update(['join_date' => '2026-01-20']);
        $cycle = $this->makeCycle();

        $this->assertSame(60.0, $member->obligationFor($cycle));
    }

    public function test_member_joining_in_december_owes_one_twelfth(): void
    {
        $member = $this->makeMember('07100000007');
        $member->update(['join_date' => '2026-12-05']);
        $cycle = $this->makeCycle();

        $this->assertSame(5.0, $member->obligationFor($cycle));
    }

    public function test_join_date_from_an_earlier_year_does_not_prorate(): void
    {
        // Joined in a prior year - a full, established member for this cycle's year.
        $member = $this->makeMember('07100000008');
        $member->update(['join_date' => '2024-06-15']);
        $cycle = $this->makeCycle();

        $this->assertSame(60.0, $member->obligationFor($cycle));
    }

    public function test_join_date_does_not_prorate_non_yearly_dues_cycles(): void
    {
        $member = $this->makeMember('07100000009');
        $member->update(['join_date' => '2026-06-15']);

        $eventLevy = $this->makeCycle([
            'title' => 'Iriji 2026', 'type' => 'event_levy', 'amount' => 50,
        ]);

        $this->assertSame(50.0, $member->obligationFor($eventLevy));
    }

    public function test_mid_year_join_prorates_before_legacy_balance_is_added(): void
    {
        // June join on a £60 cycle -> £35 prorated dues, plus the full £20 carryover.
        $member = $this->makeMember('07100000010', 20.00);
        $member->update(['join_date' => '2026-06-15']);
        $cycle = $this->makeCycle();

        $this->assertSame(55.0, $member->obligationFor($cycle));
    }
}
