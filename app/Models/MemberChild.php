<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberChild extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'notes',
        'father_id',
        'mother_id',
        'added_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function father(): BelongsTo
    {
        return $this->belongsTo(User::class, 'father_id');
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mother_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function age(): ?int
    {
        return $this->date_of_birth
            ? $this->date_of_birth->age
            : null;
    }
}
