<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'batch_id', 'channel', 'subject', 'message', 'template_id',
        'context', 'meeting_id', 'status', 'sent_by', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'template_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
