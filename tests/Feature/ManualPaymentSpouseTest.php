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

    public function test_store_rejects_pay_for_spouse_on_non_couple_shared_cycle(): void
    {
        $member = $this->makeMember('07100000010');
        $spouse = $this->makeMember('07100000011');
        $this->linkSpouses($member, $spouse);
        $cycle = $this->makeCycle(['couple_shared' => false]);

        $request = new Request([
            'user_id'        => $member->id,
            'dues_cycle_id'  => $cycle->id,
            'amount'         => 90,
            'payment_date'   => '2026-08-07',
            'pay_for_spouse' => '1',
        ]);

        $response = app(ManualPaymentController::class)->store($request);

        $this->assertTrue($response->getSession()->has('errors'));
        $this->assertStringContainsString(
            "isn't supported",
            $response->getSession()->get('errors')->first('pay_for_spouse')
        );
        $this->assertSame(0, Payment::count());
    }

    public function test_store_succeeds_normally_on_couple_shared_cycle(): void
    {
        $member = $this->makeMember('07100000012');
        $spouse = $this->makeMember('07100000013');
        $this->linkSpouses($member, $spouse);
        $cycle = $this->makeCycle(['couple_shared' => true, 'amount' => 120]);

        $request = new Request([
            'user_id'        => $member->id,
            'dues_cycle_id'  => $cycle->id,
            'amount'         => 120,
            'payment_date'   => '2026-08-07',
            'pay_for_spouse' => '1',
        ]);

        app(ManualPaymentController::class)->store($request);

        // Exactly one payment row - no duplication, no linked spouse record
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

    public function test_check_status_reports_not_settled_for_general_sentinel(): void
    {
        $member = $this->makeMember('07100000016');

        $response = app(ManualPaymentController::class)->checkStatus(new Request([
            'user_id' => $member->id, 'dues_cycle_id' => 'general',
        ]));

        $this->assertFalse($response->getData(true)['settled']);
    }
}
