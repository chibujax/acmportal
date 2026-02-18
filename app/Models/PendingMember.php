<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingMember extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'status',
        'import_batch', 'failure_reason',
        'invited_at', 'registered_at', 'imported_by',
    ];

    protected $casts = [
        'invited_at'    => 'datetime',
        'registered_at' => 'datetime',
    ];

    public function registrationToken(): HasOne
    {
        return $this->hasOne(RegistrationToken::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function activeToken(): ?RegistrationToken
    {
        return $this->registrationToken()
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->first();
    }
}
