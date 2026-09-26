<?php

namespace Tests\Feature;

use App\Http\Controllers\Payment\ManualPaymentController;
use App\Models\DuesCycle;
use App\Models\MemberRelationship;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManualPaymentSpouseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Admin',
            'phone'    => '07000000000',
            'password' => Hash::make('password'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        $this->actingAs($this->admin);
    }

    private function makeMember(string $phone): User
    {
        return User::create([
            'name'     => 'Member ' . $phone,
            'phone'    => $phone,
            'password' => Hash::make('password'),
            'role'     => 'member',
            'status'   => 'active',
        ]);
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
            'created_by'      => $this->admin->id,
        ], $overrides));
    }

    private function linkSpouses(User $a, User $b): void
    {
        MemberRelationship::create([
            'member_id_1' => $a->id,
            'member_id_2' => $b->id,
            'relationship_type' => 'spouse',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_store_splits_payment_between_spouses_on_non_couple_shared_cycle(): void
    {
        $member = $this->makeMember('07100000010');
        $spouse = $this->makeMember('07100000011');
        $this->linkSpouses($member, $spouse);
        $cycle = $this->makeCycle(['couple_shared' => false]);

        $request = new Request([
            'user_id'           => $member->id,
            'dues_cycle_id'     => $cycle->id,
            'amount'            => 40,
            'payment_date'      => '2026-08-07',
            'split_with_spouse' => '1',
            'spouse_amount'     => 60,
            'total_amount'      => 100,
        ]);

        app(ManualPaymentController::class)->store($request);

        $this->assertSame(2, Payment::count());

        $memberPayment = Payment::where('user_id', $member->id)->firstOrFail();
        $spousePayment = Payment::where('user_id', $spouse->id)->firstOrFail();

        $this->assertEquals(40, $memberPayment->amount);
        $this->assertEquals(60, $spousePayment->amount);
        $this->assertSame($spousePayment->id, $memberPayment->linked_payment_id);
        $this->assertSame($memberPayment->id, $spousePayment->linked_payment_id);
        $this->assertNotSame($memberPayment->receipt_number, $spousePayment->receipt_number);
    }

    public function test_store_rejects_split_when_shares_dont_add_up_to_the_total(): void
    {
        // Regression test: husband owed 17, spouse owed 52. Admin entered a total of 17
        // (the amount actually received) but the spouse_amount field was left at its
        // default of 52 — previously nothing tied these together, so both got recorded
        // in full (17 + 52) instead of just the 17 actually paid.
        $member = $this->makeMember('07100000021');
        $spouse = $this->makeMember('07100000022');
        $this->linkSpouses($member, $spouse);
        $cycle = $this->makeCycle(['couple_shared' => false, 'amount' => 17]);

        $request = new Request([
            'user_id'           => $member->id,
            'dues_cycle_id'     => $cycle->id,
            'amount'            => 17,
            'payment_date'      => '2026-08-07',
            'split_with_spouse' => '1',
            'spouse_amount'     => 52,
            'total_amount'      => 17,
        ]);

        $response = app(ManualPaymentController::class)->store($request);

        $this->assertTrue($response->getSession()->has('errors'));
        $this->assertStringContainsString(
            "doesn't match",
            $response->getSession()->get('errors')->first('total_amount')
        );
        $this->assertSame(0, Payment::count());
    }

    public function test_store_rejects_split_with_spouse_on_couple_shared_cycle(): void
    {
        $member = $this->makeMember('07100000012');
        $spouse = $this->makeMember('07100000013');
        $this->linkSpouses($member, $spouse);
        $cycle = $this->makeCycle(['couple_shared' => true, 'amount' => 120]);

        $request = new Request([
            'user_id'           => $member->id,
            'dues_cycle_id'     => $cycle->id,
            'amount'            => 120,
            'payment_date'      => '2026-08-07',
            'split_with_spouse' => '1',
            'spouse_amount'     => 60,
        ]);

        $response = app(ManualPaymentController::class)->store($request);

        $this->assertTrue($response->getSession()->has('errors'));
        $this->assertStringContainsString(
            "isn't supported",
            $response->getSession()->get('errors')->first('split_with_spouse')
        );
        $this->assertSame(0, Payment::count());
    }

    public function test_store_succeeds_normally_on_couple_shared_cycle_without_split(): void
    {
        $member = $this->makeMember('07100000017');
        $spouse = $this->makeMember('07100000018');
        $this->linkSpouses($member, $spouse);
        $cycle = $this->makeCycle(['couple_shared' => true, 'amount' => 120]);

        $request = new Request([
            'user_id'       => $member->id,
            'dues_cycle_id' => $cycle->id,
            'amount'        => 120,
            'payment_date'  => '2026-08-07',
        ]);

        app(ManualPaymentController::class)->store($request);

        // Exactly one payment row — couple_shared cycles merge via totalPaidWithSpouse(),
        // they never need a second linked row.
        $this->assertSame(1, Payment::count());
        $payment = Payment::first();
        $this->assertSame($member->id, $payment->user_id);
        $this->assertNull($payment->linked_payment_id);
    }

    public function test_check_status_reports_settled_for_fully_paid_member(): void
    {
        $member = $this->makeMember('07100000014');
        $cycle  = $this->makeCycle();

        Payment::create([
            'user_id' => $member->id, 'dues_cycle_id' => $cycle->id,
            'amount' => 60, 'status' => 'completed', 'method' => 'manual', 'payment_date' => now(),
        ]);

        $response = app(ManualPaymentController::class)->checkStatus(new Request([
            'user_id' => $member->id, 'dues_cycle_id' => (string) $cycle->id,
        ]));

        $data = $response->getData(true);
        $this->assertTrue($data['settled']);
        $this->assertEquals(60.0, $data['paid']);
        $this->assertEquals(60.0, $data['obligation']);
    }

    public function test_check_status_reports_not_settled_for_partially_paid_member(): void
    {
        $member = $this->makeMember('07100000015');
        $cycle  = $this->makeCycle();

        Payment::create([
            'user_id' => $member->id, 'dues_cycle_id' => $cycle->id,
            'amount' => 20, 'status' => 'completed', 'method' => 'manual', 'payment_date' => now(),
        ]);

        $response = app(ManualPaymentController::class)->checkStatus(new Request([
            'user_id' => $member->id, 'dues_cycle_id' => (string) $cycle->id,
        ]));

        $this->assertFalse($response->getData(true)['settled']);
    }

    public function test_check_status_includes_spouse_figures_for_non_couple_shared_cycle(): void
    {
        $member = $this->makeMember('07100000019');
        $spouse = $this->makeMember('07100000020');
        $this->linkSpouses($member, $spouse);
        $cycle = $this->makeCycle(['couple_shared' => false]);

        Payment::create([
            'user_id' => $spouse->id, 'dues_cycle_id' => $cycle->id,
            'amount' => 25, 'status' => 'completed', 'method' => 'manual', 'payment_date' => now(),
        ]);

        $response = app(ManualPaymentController::class)->checkStatus(new Request([
            'user_id' => $member->id, 'dues_cycle_id' => (string) $cycle->id,
        ]));

        $data = $response->getData(true);
        $this->assertArrayHasKey('spouse', $data);
        $this->assertSame($spouse->id, $data['spouse']['id']);
        $this->assertEquals(60.0, $data['spouse']['obligation']);
        $this->assertEquals(25.0, $data['spouse']['paid']);
        $this->assertEquals(35.0, $data['spouse']['remaining']);
    }

    public function test_check_status_reports_not_settled_for_general_sentinel(): void
    {
        $member = $this->makeMember('07100000016');

        $response = app(ManualPaymentController::class)->checkStatus(new Request([
            'user_id' => $member->id, 'dues_cycle_id' => 'general',
        ]));

        $this->assertFalse($response->getData(true)['settled']);
    }
}
