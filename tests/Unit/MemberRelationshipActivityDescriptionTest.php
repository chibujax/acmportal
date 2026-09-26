<?php

namespace Tests\Unit;

use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberRelationshipActivityDescriptionTest extends TestCase
{
    use RefreshDatabase;

    private function makeMember(string $name, string $phone): User
    {
        return User::create([
            'name'     => $name,
            'phone'    => $phone,
            'password' => Hash::make('password'),
            'role'     => 'member',
            'status'   => 'active',
        ]);
    }

    public function test_created_description_names_both_members(): void
    {
        $a = $this->makeMember('Ada Lovelace', '07300000001');
        $b = $this->makeMember('Ben Franklin', '07300000002');

        $relationship = MemberRelationship::create([
            'member_id_1'       => $a->id,
            'member_id_2'       => $b->id,
            'relationship_type' => 'spouse',
            'created_by'        => $a->id,
        ]);

        $this->assertSame(
            'Linked Ada Lovelace and Ben Franklin as spouses',
            $relationship->getActivityDescription('created')
        );
    }

    public function test_deleted_description_names_both_members(): void
    {
        $a = $this->makeMember('Ada Lovelace', '07300000003');
        $b = $this->makeMember('Ben Franklin', '07300000004');

        $relationship = MemberRelationship::create([
            'member_id_1'       => $a->id,
            'member_id_2'       => $b->id,
            'relationship_type' => 'spouse',
            'created_by'        => $a->id,
        ]);

        $this->assertSame(
            'Unlinked spouse relationship between Ada Lovelace and Ben Franklin',
            $relationship->getActivityDescription('deleted')
        );
    }
}
