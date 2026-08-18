<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name', 'phone', 'email', 'password',
        'role', 'status', 'profile_photo', 'address',
        'date_of_birth', 'gender', 'occupation', 'email_verified_at',
        'activation_token', 'activation_token_expires_at', 'activation_invited_at',
        'portal_activated_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'           => 'datetime',
        'date_of_birth'               => 'date',
        'password'                    => 'hashed',
        'activation_token_expires_at' => 'datetime',
        'activation_invited_at'       => 'datetime',
        'portal_activated_at'         => 'datetime',
    ];

    // ── Roles ─────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /**
     * Whether this user can access a given admin page slug.
     * Super admins always have full access.
     * Regular admins need at least one assigned role that grants access to the page.
     */
    public function hasAccess(string $page): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roles->contains(fn($role) => in_array($page, $role->pages ?? []));
    }

    // ── Relationships ─────────────────────────────────────────

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

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
     * Pre-2026 carryover (legacy balance) is folded into whichever yearly
     * dues cycle is currently collecting — there's no "Carryover" cycle of
     * its own, and outstanding dues are conceptually cumulative across years.
     * Unsplit even on a couple_shared cycle: carryover belongs to the individual.
     */
    public function obligationFor(DuesCycle $cycle): float
    {
        $obligation = $cycle->couple_shared
            ? ($this->hasSpouse() ? $cycle->amount : round($cycle->amount / 2, 2))
            : $cycle->amount;

        if ($cycle->isCurrentYearlyDues()) {
            $obligation += $this->legacyBalanceTotal();
        }

        return $obligation;
    }

    public function legacyBalances()
    {
        return $this->hasMany(MemberLegacyBalance::class);
    }

    /**
     * Sum of pre-2026 carryover balances (can be negative — a credit).
     */
    public function legacyBalanceTotal(): float
    {
        return (float) $this->legacyBalances()->sum('amount');
    }

    /**
     * Legacy balance not currently folded into any cycle's obligation — i.e. the
     * full amount, unless a current yearly dues cycle exists to absorb it via
     * obligationFor(). Callers summing "total outstanding" should add this once,
     * separately from any per-cycle totals, to avoid double-counting.
     */
    public function unfoldedLegacyBalance(): float
    {
        return DuesCycle::query()->where('type', 'yearly_dues')->where('status', 'active')->exists()
            ? 0.0
            : $this->legacyBalanceTotal();
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

    /**
     * Earliest evidence of membership — payment or attendance — falling back to account creation date.
     */
    public function memberSince(): \Carbon\Carbon
    {
        $earliestPayment = $this->payments()->min('payment_date');

        $earliestAttendance = $this->attendanceRecords()
            ->join('meetings', 'attendance_records.meeting_id', '=', 'meetings.id')
            ->min('meetings.meeting_date');

        $candidates = array_filter([$earliestPayment, $earliestAttendance]);

        return $candidates
            ? \Carbon\Carbon::parse(min($candidates))
            : $this->created_at;
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    /** Whether this member has set their portal password and can log in. */
    public function hasPortalAccess(): bool
    {
        return ! is_null($this->portal_activated_at);
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
