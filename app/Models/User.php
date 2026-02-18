<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'email', 'password',
        'role', 'status', 'profile_photo', 'address',
        'date_of_birth', 'occupation', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth'     => 'date',
        'password'          => 'hashed',
    ];

    // ── Roles ─────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isFinancialSecretary(): bool
    {
        return in_array($this->role, ['admin', 'financial_secretary']);
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    // ── Relationships ─────────────────────────────────────────

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function recordedPayments()
    {
        return $this->hasMany(Payment::class, 'recorded_by');
    }

    // ── Helpers ───────────────────────────────────────────────

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    public function totalPaid(int $cycleId = null): float
    {
        $q = $this->payments()->where('status', 'completed');
        if ($cycleId) {
            $q->where('dues_cycle_id', $cycleId);
        }
        return (float) $q->sum('amount');
    }
}
