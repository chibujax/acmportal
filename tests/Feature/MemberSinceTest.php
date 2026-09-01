<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberSinceTest extends TestCase
{
    use RefreshDatabase;

    private function makeMember(array $overrides = []): User
    {
        return User::create(array_merge([
            'name'     => 'Member',
            'phone'    => '07200000000',
            'password' => Hash::make('password'),
            'role'     => 'member',
            'status'   => 'active',
        ], $overrides));
    }

    public function test_join_date_is_used_when_set_even_if_earlier_evidence_exists(): void
    {
        $member = $this->makeMember(['join_date' => '2026-06-15']);

        Payment::create([
            'user_id'      => $member->id,
            'amount'       => 60,
            'status'       => 'completed',
            'method'       => 'manual',
            'payment_date' => '2026-01-01',
        ]);

        $this->assertSame('2026-06-15', $member->memberSince()->toDateString());
    }

    public function test_falls_back_to_earliest_payment_when_join_date_is_null(): void
    {
        $member = $this->makeMember();

        Payment::create([
            'user_id'      => $member->id,
            'amount'       => 60,
            'status'       => 'completed',
            'method'       => 'manual',
            'payment_date' => '2026-02-10',
        ]);

        $this->assertSame('2026-02-10', $member->memberSince()->toDateString());
    }

    public function test_falls_back_to_account_creation_when_nothing_else_is_available(): void
    {
        $member = $this->makeMember();

        $this->assertSame(
            $member->created_at->toDateString(),
            $member->memberSince()->toDateString()
        );
    }
}
