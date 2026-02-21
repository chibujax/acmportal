<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Meeting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'meeting_date', 'meeting_time', 'venue',
        'description', 'qr_token', 'qr_expires_at', 'status', 'created_by',
    ];

    protected $casts = [
        'meeting_date'  => 'date',
        'qr_expires_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ───────────────────────────────────────────────

    /**
     * Generate a secure QR token and activate the meeting.
     */
    public function activate(int $durationHours = 3): void
    {
        $this->update([
            'qr_token'      => Str::random(48),
            'qr_expires_at' => now()->addHours($durationHours),
            'status'        => 'active',
        ]);
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->qr_expires_at
            && $this->qr_expires_at->isFuture();
    }

    public function isExpiredQr(): bool
    {
        return $this->status === 'active'
            && $this->qr_expires_at
            && $this->qr_expires_at->isPast();
    }

    /**
     * Check if a user has already checked in.
     */
    public function hasCheckedIn(int $userId): bool
    {
        return $this->attendanceRecords()->where('user_id', $userId)->exists();
    }

    /**
     * Attendance percentage (out of total active members).
     */
    public function attendanceRate(): float
    {
        $total = User::where('role', 'member')->where('status', 'active')->count();
        if ($total === 0) return 0;
        return round(($this->attendanceRecords()->count() / $total) * 100, 1);
    }
}
