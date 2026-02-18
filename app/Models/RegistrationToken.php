<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RegistrationToken extends Model
{
    protected $fillable = ['pending_member_id', 'token', 'expires_at', 'used_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function pendingMember(): BelongsTo
    {
        return $this->belongsTo(PendingMember::class);
    }

    public static function generate(PendingMember $member): self
    {
        // Remove any existing token
        static::where('pending_member_id', $member->id)->delete();

        return static::create([
            'pending_member_id' => $member->id,
            'token'             => Str::random(64),
            'expires_at'        => now()->addDays(config('app.registration_token_expiry', 7)),
        ]);
    }

    public function isValid(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }

    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}
