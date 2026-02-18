<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuesCycle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'type', 'amount', 'currency',
        'start_date', 'end_date', 'payment_options',
        'installment_count', 'description', 'status',
        'send_reminders', 'created_by',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'amount'          => 'float',
        'send_reminders'  => 'boolean',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function totalCollected(): float
    {
        return (float) $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function paidMembersCount(): int
    {
        return $this->payments()
            ->where('status', 'completed')
            ->distinct('user_id')
            ->count('user_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && now()->between($this->start_date, $this->end_date);
    }

    public function installmentAmount(): float
    {
        if ($this->payment_options === 'installments' && $this->installment_count > 0) {
            return round($this->amount / $this->installment_count, 2);
        }
        if ($this->payment_options === 'monthly') {
            $months = $this->start_date->diffInMonths($this->end_date) ?: 1;
            return round($this->amount / $months, 2);
        }
        return $this->amount;
    }
}
