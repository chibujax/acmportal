<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class NotificationRecipient extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'email', 'phone', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function getActivityDescription(string $action): string
    {
        return ucfirst($action) . ' notification recipient: ' . $this->name;
    }
}
