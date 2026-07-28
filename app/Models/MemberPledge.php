<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPledge extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id', 'donor_name', 'dues_cycle_id', 'pledged_amount', 'received_amount',
        'shared_with_spouse', 'currency', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'pledged_amount'     => 'float',
        'received_amount'    => 'float',
        'shared_with_spouse' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAnonymous(): bool
    {
        return is_null($this->user_id);
    }

    public function displayName(): string
    {
        return $this->user?->name ?? $this->donor_name ?? 'Anonymous';
    }

    public function duesCycle(): BelongsTo
    {
        return $this->belongsTo(DuesCycle::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
