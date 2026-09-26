<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('name')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $availablePages = Role::$availablePages;
        return view('admin.roles.create', compact('availablePages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:roles,name',
            'description' => 'nullable|string|max:255',
            'pages'       => 'nullable|array',
            'pages.*'     => 'string|in:' . implode(',', array_keys(Role::$availablePages)),
        ]);

        Role::create([
            'name'        => $request->name,
            'description' => $request->description,
            'pages'       => $request->pages ?? [],
        ]);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role "' . $request->name . '" created.');
    }

    public function edit(Role $role)
    {
        $availablePages = Role::$availablePages;
        return view('admin.roles.edit', compact('role', 'availablePages'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:255',
            'pages'       => 'nullable|array',
            'pages.*'     => 'string|in:' . implode(',', array_keys(Role::$availablePages)),
        ]);

        $role->update([
            'name'        => $request->name,
            'description' => $request->description,
            'pages'       => $request->pages ?? [],
        ]);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete a role that is assigned to admin users. Remove it from all admins first.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted.');
    }

    public function assign()
    {
        $admins = User::where('role', 'admin')
            ->orderBy('name')
            ->with('roles')
            ->get();

        $roles = Role::orderBy('name')->get();

        return view('admin.roles.assign', compact('admins', 'roles'));
    }

    public function assignUpdate(Request $request)
    {
        $request->validate([
            'user_id'  => 'required|exists:users,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Cannot modify role assignments for a Super Admin.');
        }

        $user->roles()->sync($request->role_ids ?? []);

        return back()->with('success', 'Roles updated for ' . $user->name . '.');
    }
}
