<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberLegacyBalance extends Model
{
    protected $fillable = ['user_id', 'label', 'year', 'amount'];

    protected $casts = ['amount' => 'float', 'year' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
