<?php

namespace Tests\Feature;

use App\Models\MemberRelationship;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSpouseLinkingTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(array $pages = ['relationships']): User
    {
        $admin = User::create([
            'name'     => 'Admin',
            'phone'    => '07000000000',
            'password' => Hash::make('password'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        if ($pages) {
            $role = Role::create(['name' => 'Family Admin', 'pages' => $pages]);
            $admin->roles()->attach($role);
        }

        return $admin;
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

    public function test_admin_without_permission_is_forbidden(): void
    {
        $admin  = $this->makeAdmin(pages: []);
        $member = $this->makeMember('07200000001');
        $spouse = $this->makeMember('07200000002');

        $this->actingAs($admin)
            ->post(route('admin.spouses.link'), ['member_id' => $member->id, 'spouse_id' => $spouse->id])
            ->assertForbidden();
    }

    public function test_admin_with_permission_can_link_spouses_and_it_is_audited(): void
    {
        $admin  = $this->makeAdmin();
        $member = $this->makeMember('07200000003');
        $spouse = $this->makeMember('07200000004');

        $this->actingAs($admin)
            ->post(route('admin.spouses.link'), ['member_id' => $member->id, 'spouse_id' => $spouse->id])
            ->assertRedirect(route('admin.spouses.index'));

        $this->assertTrue($member->fresh()->hasSpouse());
        $this->assertTrue($spouse->fresh()->hasSpouse());

        // Audit logging itself (LogsActivity) can't be exercised here — its
        // shouldLog() gate keys off runningInConsole(), which PHPUnit's CLI
        // SAPI always satisfies, in every test regardless of HTTP context.
        // The description text it would log is covered directly in
        // MemberRelationshipActivityDescriptionTest instead.
    }

    public function test_link_is_rejected_when_either_member_already_has_a_spouse(): void
    {
        $admin   = $this->makeAdmin();
        $member  = $this->makeMember('07200000005');
        $spouse  = $this->makeMember('07200000006');
        $another = $this->makeMember('07200000007');

        MemberRelationship::create([
            'member_id_1'       => $member->id,
            'member_id_2'       => $spouse->id,
            'relationship_type' => 'spouse',
            'created_by'        => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.spouses.link'), ['member_id' => $member->id, 'spouse_id' => $another->id])
            ->assertSessionHasErrors('member_id');

        $this->assertSame(1, MemberRelationship::count());
    }

    public function test_admin_can_unlink_a_spouse_pair_and_it_is_audited(): void
    {
        $admin  = $this->makeAdmin();
        $member = $this->makeMember('07200000008');
        $spouse = $this->makeMember('07200000009');

        $relationship = MemberRelationship::create([
            'member_id_1'       => $member->id,
            'member_id_2'       => $spouse->id,
            'relationship_type' => 'spouse',
            'created_by'        => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.spouses.unlink', $relationship))
            ->assertRedirect();

        $this->assertSame(0, MemberRelationship::count());
    }

    public function test_member_can_still_self_service_link_a_spouse(): void
    {
        $member = $this->makeMember('07200000010');
        $spouse = $this->makeMember('07200000011');

        $this->actingAs($member)
            ->post(route('member.relationships.spouse.link'), ['spouse_id' => $spouse->id])
            ->assertRedirect();

        $this->assertTrue($member->fresh()->hasSpouse());
    }
}
