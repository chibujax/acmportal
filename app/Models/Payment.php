<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'dues_cycle_id', 'amount', 'currency',
        'method', 'status', 'gateway_reference', 'gateway_response',
        'gateway_payload', 'recorded_by', 'receipt_number', 'notes',
        'payment_date', 'proof_of_payment', 'installment_number',
        'total_installments',
    ];

    protected $casts = [
        'amount'           => 'float',
        'gateway_payload'  => 'array',
        'payment_date'     => 'date',
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

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function formattedAmount(): string
    {
        return strtoupper($this->currency) . ' ' . number_format($this->amount, 2);
    }

    public static function generateReceiptNumber(): string
    {
        return 'ACM-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }
}
