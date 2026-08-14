<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
