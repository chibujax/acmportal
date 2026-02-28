<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberRelationship extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'member_id_1',
        'member_id_2',
        'relationship_type',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function member1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id_1');
    }

    public function member2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id_2');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Return the "other" member in the relationship.
     */
    public function otherMember(int $userId): ?User
    {
        if ($this->member_id_1 === $userId) {
            return $this->member2;
        }
        return $this->member1;
    }

    /**
     * Find the spouse relationship for a given user ID.
     */
    public static function spouseRelationshipFor(int $userId): ?self
    {
        return static::where(function ($q) use ($userId) {
            $q->where('member_id_1', $userId)
              ->orWhere('member_id_2', $userId);
        })->where('relationship_type', 'spouse')->first();
    }
}
