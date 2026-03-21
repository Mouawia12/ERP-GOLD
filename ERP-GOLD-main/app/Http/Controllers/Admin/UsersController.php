<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index()
    {
        $users = User::all();

        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::all();
        $branches = Branch::all();

        return view('admin.users.create', compact('roles', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'email' => 'required|email|max:255|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'required|exists:branches,id',
            'password' => 'required|string|same:confirm-password',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'branch_id' => $validated['branch_id'],
            'password' => Hash::make($validated['password']),
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $user->assignRole($role);

        return redirect()->route('admin.users.index')->with('success', __('main.created'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:users,name,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'nullable|boolean',
            'password' => 'nullable|string|same:confirm-password',
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'branch_id' => $validated['branch_id'],
            'status' => (bool) ($validated['status'] ?? $user->status),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $role = Role::findOrFail($validated['role_id']);
        $user->syncRoles([$role]);

        return redirect()->route('admin.users.index')->with('success', __('main.updated'));
    }

    public function show($id)
    {
        $user = User::findorfail($id);
        return view('admin.users.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::all();
        $branches = Branch::all();
        $userRole = $user->roles->pluck('id')->all();

        return view('admin.users.edit', compact('user', 'roles', 'branches', 'userRole'));
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->delete();
            return redirect()->route('admin.users.index')->with('success', __('main.deleted'));
        }
    }
}
