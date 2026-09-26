<?php

namespace Tests\Feature;

use App\Models\MeetingMinutes;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MinutesViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $name, string $phone, string $role = 'member'): User
    {
        return User::create([
            'name'     => $name,
            'phone'    => $phone,
            'password' => Hash::make('password'),
            'role'     => $role,
            'status'   => 'active',
        ]);
    }

    private function makeMinutes(User $creator): MeetingMinutes
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->create('minutes.pdf', 10, 'application/pdf')->store('meeting-minutes', 'local');

        return MeetingMinutes::create([
            'title'        => 'August Meeting',
            'meeting_date' => now()->toDateString(),
            'status'       => 'published',
            'published_at' => now(),
            'file_path'    => $path,
            'file_name'    => 'minutes.pdf',
            'created_by'   => $creator->id,
        ]);
    }

    public function test_views_and_downloads_are_counted_separately_on_one_row(): void
    {
        $member  = $this->makeUser('Ada Obi', '07100000001');
        $minutes = $this->makeMinutes($member);

        $this->actingAs($member)->get(route('member.minutes.view', $minutes))->assertOk();
        $this->actingAs($member)->get(route('member.minutes.view', $minutes))->assertOk();
        $this->actingAs($member)->get(route('member.minutes.download', $minutes))->assertOk();

        $this->assertDatabaseCount('meeting_minutes_views', 1);
        $this->assertDatabaseHas('meeting_minutes_views', [
            'meeting_minutes_id' => $minutes->id,
            'user_id'            => $member->id,
            'view_count'         => 2,
            'download_count'     => 1,
        ]);
    }

    public function test_download_only_member_counts_as_downloader_not_viewer(): void
    {
        $downloader = $this->makeUser('Dayo Downloader', '07100000001');
        $viewer     = $this->makeUser('Chika Viewer', '07100000002');
        $minutes    = $this->makeMinutes($viewer);
        $minutes->recordDownloadBy($downloader);
        $minutes->recordViewBy($viewer);

        $this->actingAs($viewer)->get(route('member.minutes.show', $minutes))
            ->assertOk()
            ->assertSee('Viewed by 1 member')
            ->assertSee('Downloaded by 1 member');
    }

    public function test_opening_the_show_page_does_not_count_as_a_view(): void
    {
        $member  = $this->makeUser('Ada Obi', '07100000001');
        $minutes = $this->makeMinutes($member);

        $this->actingAs($member)->get(route('member.minutes.show', $minutes))->assertOk();

        $this->assertDatabaseCount('meeting_minutes_views', 0);
    }

    public function test_member_sees_count_but_not_viewer_names(): void
    {
        $viewer  = $this->makeUser('Chika Viewer', '07100000001');
        $member  = $this->makeUser('Bola Member', '07100000002');
        $minutes = $this->makeMinutes($viewer);
        $minutes->recordViewBy($viewer);

        $this->actingAs($member)->get(route('member.minutes.show', $minutes))
            ->assertOk()
            ->assertSee('Viewed by 1 member')
            ->assertDontSee('Chika Viewer')
            ->assertDontSee('See who viewed');
    }

    public function test_admin_with_minutes_access_sees_viewer_names(): void
    {
        $viewer  = $this->makeUser('Chika Viewer', '07100000001');
        $admin   = $this->makeUser('Admin', '07000000000', 'admin');
        $admin->roles()->attach(Role::create(['name' => 'Secretary', 'pages' => ['minutes']]));
        $minutes = $this->makeMinutes($admin);
        $minutes->recordViewBy($viewer);

        $this->actingAs($admin)->get(route('member.minutes.show', $minutes))
            ->assertOk()
            ->assertSee('Viewed by 1 member')
            ->assertSee('See who viewed')
            ->assertSee('Chika Viewer');
    }
}
