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
        'date_of_birth', 'gender', 'occupation', 'email_verified_at',
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

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * All MemberRelationship rows involving this user.
     */
    public function memberRelationships()
    {
        return MemberRelationship::where('member_id_1', $this->id)
            ->orWhere('member_id_2', $this->id)
            ->where('relationship_type', 'spouse');
    }

    /**
     * Children where this user is the father or mother.
     */
    public function childrenAsFather()
    {
        return $this->hasMany(MemberChild::class, 'father_id');
    }

    public function childrenAsMother()
    {
        return $this->hasMany(MemberChild::class, 'mother_id');
    }

    // ── Spouse helpers ────────────────────────────────────────

    /**
     * Returns the spouse User or null.
     */
    public function spouse(): ?self
    {
        $rel = MemberRelationship::spouseRelationshipFor($this->id);
        if (! $rel) return null;
        return $rel->otherMember($this->id);
    }

    /**
     * Returns the MemberRelationship for the spouse link, or null.
     */
    public function spouseRelationship(): ?MemberRelationship
    {
        return MemberRelationship::spouseRelationshipFor($this->id);
    }

    public function hasSpouse(): bool
    {
        return MemberRelationship::where(function ($q) {
            $q->where('member_id_1', $this->id)
              ->orWhere('member_id_2', $this->id);
        })->where('relationship_type', 'spouse')->exists();
    }

    /**
     * All children visible to this user:
     * - Children they added as father or mother
     * - If they have a spouse, children of that spouse are also included
     */
    public function visibleChildren()
    {
        $ids = collect([$this->id]);

        $spouse = $this->spouse();
        if ($spouse) {
            $ids->push($spouse->id);
        }

        return MemberChild::where(function ($q) use ($ids) {
            $q->whereIn('father_id', $ids)
              ->orWhereIn('mother_id', $ids);
        })->with(['father', 'mother'])->get();
    }

    // ── Family dues helpers ───────────────────────────────────

    /**
     * If couple_shared: married members owe the full amount (shared),
     * single members owe half. Otherwise every member owes the full amount.
     */
    public function obligationFor(DuesCycle $cycle): float
    {
        if ($cycle->couple_shared) {
            return $this->hasSpouse()
                ? $cycle->amount
                : round($cycle->amount / 2, 2);
        }

        return $cycle->amount;
    }

    /**
     * Total paid for a dues cycle.
     * If couple_shared, includes the spouse's payments too.
     * If not couple_shared, only this member's own payments count.
     */
    public function totalPaidWithSpouse(int $cycleId, bool $coupleShared = true): float
    {
        $ids = [$this->id];

        if ($coupleShared) {
            $spouse = $this->spouse();
            if ($spouse) {
                $ids[] = $spouse->id;
            }
        }

        return (float) Payment::whereIn('user_id', $ids)
            ->where('dues_cycle_id', $cycleId)
            ->where('status', 'completed')
            ->sum('amount');
    }

    // ── Existing helpers ──────────────────────────────────────

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

    /**
     * Attendance percentage across all closed meetings in a given year.
     */
    public function attendancePercentage(int $year = null): float
    {
        $year = $year ?? now()->year;

        $total = Meeting::whereYear('meeting_date', $year)
            ->whereIn('status', ['active', 'closed'])
            ->count();

        if ($total === 0) return 0;

        $attended = $this->attendanceRecords()
            ->whereHas('meeting', fn($q) => $q->whereYear('meeting_date', $year))
            ->count();

        return round(($attended / $total) * 100, 1);
    }
}
