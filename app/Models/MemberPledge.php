<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPledge extends Model
{
    protected $fillable = [
        'user_id', 'dues_cycle_id', 'pledged_amount', 'currency', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'pledged_amount' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
