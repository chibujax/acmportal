<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class MeetingMinutes extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'meeting_minutes';

    protected $fillable = [
        'title', 'meeting_date', 'published_at', 'status',
        'file_path', 'file_name', 'created_by',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'published_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Members who have viewed or downloaded these minutes (one row each; see
     * recordViewBy()/recordDownloadBy()). first/last_viewed_at cover both actions.
     */
    public function viewers()
    {
        return $this->belongsToMany(User::class, 'meeting_minutes_views')
            ->withPivot('view_count', 'download_count', 'first_viewed_at', 'last_viewed_at');
    }

    public function recordViewBy(User $user): void
    {
        $this->recordAccess($user, 'view_count');
    }

    public function recordDownloadBy(User $user): void
    {
        $this->recordAccess($user, 'download_count');
    }

    /**
     * First access inserts the member's row; later ones bump $counter and last_viewed_at.
     */
    private function recordAccess(User $user, string $counter): void
    {
        $now = now();

        DB::table('meeting_minutes_views')->upsert(
            [[
                'meeting_minutes_id' => $this->id,
                'user_id'            => $user->id,
                'view_count'         => $counter === 'view_count' ? 1 : 0,
                'download_count'     => $counter === 'download_count' ? 1 : 0,
                'first_viewed_at'    => $now,
                'last_viewed_at'     => $now,
            ]],
            ['meeting_minutes_id', 'user_id'],
            [
                $counter         => DB::raw("{$counter} + 1"),
                'last_viewed_at' => $now,
            ]
        );
    }

    /**
     * Adds views_count / downloads_count — members who viewed / downloaded at least once.
     */
    public function scopeWithAccessCounts($query)
    {
        return $query->withCount([
            'viewers as views_count'     => fn($q) => $q->where('meeting_minutes_views.view_count', '>', 0),
            'viewers as downloads_count' => fn($q) => $q->where('meeting_minutes_views.download_count', '>', 0),
        ]);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    /**
     * Whether this was published within the last 5 days — drives the dashboard "New" badge.
     */
    public function isNew(): bool
    {
        return $this->status === 'published'
            && $this->published_at
            && $this->published_at->greaterThanOrEqualTo(now()->subDays(5));
    }
}
