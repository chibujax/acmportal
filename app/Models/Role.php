<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'description', 'pages'];

    protected $casts = [
        'pages' => 'array',
    ];

    /**
     * The available admin page slugs that roles can grant access to.
     */
    public static array $availablePages = [
        'members'        => 'Member Management',
        'manage'         => 'Manage Member Status & Role',
        'payments'       => 'Payments & Dues Cycles',
        'meetings'       => 'Meeting Management',
        'attendance'     => 'Attendance Report',
        'absentees'      => 'Consecutive Absentees',
        'reports'        => 'Financial Reports',
        'arrears'        => 'Arrears Report',
        'communications' => 'Message Templates',
        'import'         => 'CSV Import & Pending Invites',
        'children'       => 'Children & Family Records',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Whether this role grants access to a given page slug.
     */
    public function grantsAccess(string $page): bool
    {
        return in_array($page, $this->pages ?? []);
    }
}
