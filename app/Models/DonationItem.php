<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationItem extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id', 'dues_cycle_id', 'item_type', 'description',
        'quantity', 'estimated_value', 'currency', 'donation_date',
        'recorded_by', 'notes',
        'is_fulfilled', 'fulfilled_at', 'fulfilled_by', 'fulfilled_quantity',
    ];

    protected $casts = [
        'estimated_value' => 'float',
        'donation_date'   => 'date',
        'is_fulfilled'    => 'boolean',
        'fulfilled_at'    => 'datetime',
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

    public function formattedValue(): string
    {
        if ($this->estimated_value === null) {
            return '—';
        }
        return strtoupper($this->currency) . ' ' . number_format($this->estimated_value, 2);
    }
}
