<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee.users.show', ['only' => ['index', 'show']]);
        $this->middleware('permission:employee.users.add', ['only' => ['create', 'store']]);
        $this->middleware('permission:employee.users.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:employee.users.delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $users = User::with(['branch', 'roles'])
            ->latest()
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $branches = Branch::latest()->get();

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
        $previousBranch = $user->branch;
        $previousRole = $user->roles->first();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:users,name,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'nullable|boolean',
            'password' => 'nullable|string|same:confirm-password',
        ]);

        $previousStatus = (bool) $user->status;
        $previousBranchId = (int) $user->branch_id;
        $previousRoleId = $previousRole?->id;
        $passwordChanged = ! empty($validated['password']);

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
        $this->writeAuditLogs(
            $request->user('admin-web'),
            $user->fresh(['branch', 'roles']),
            [
                'status' => $previousStatus,
                'branch_id' => $previousBranchId,
                'branch_name' => $previousBranch?->branch_name,
                'role_id' => $previousRoleId,
                'role_name' => $this->resolveRoleName($previousRole),
            ],
            $passwordChanged,
        );

        return redirect()->route('admin.users.index')->with('success', __('main.updated'));
    }

    public function show($id)
    {
        $user = User::with([
            'branch',
            'roles',
            'auditLogs' => function ($query) {
                $query->with('actor')->latest()->limit(15);
            },
        ])->findOrFail($id);
        return view('admin.users.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::with(['branch', 'roles'])->findOrFail($id);
        $roles = Role::all();
        $branches = Branch::latest()->get();
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

    /**
     * @param  array<string, mixed>  $before
     */
    private function writeAuditLogs(?User $actor, User $user, array $before, bool $passwordChanged): void
    {
        $currentRole = $user->roles->first();

        if ((bool) $before['status'] !== (bool) $user->status) {
            UserAuditLog::create([
                'actor_user_id' => $actor?->id,
                'target_user_id' => $user->id,
                'event_key' => 'status_changed',
                'old_values' => [
                    'status' => (bool) $before['status'],
                ],
                'new_values' => [
                    'status' => (bool) $user->status,
                ],
            ]);
        }

        if ((int) $before['branch_id'] !== (int) $user->branch_id) {
            UserAuditLog::create([
                'actor_user_id' => $actor?->id,
                'target_user_id' => $user->id,
                'event_key' => 'branch_changed',
                'old_values' => [
                    'branch_id' => $before['branch_id'],
                    'branch_name' => $before['branch_name'],
                ],
                'new_values' => [
                    'branch_id' => $user->branch_id,
                    'branch_name' => $user->branch?->branch_name,
                ],
            ]);
        }

        if ((int) ($before['role_id'] ?? 0) !== (int) ($currentRole?->id ?? 0)) {
            UserAuditLog::create([
                'actor_user_id' => $actor?->id,
                'target_user_id' => $user->id,
                'event_key' => 'role_changed',
                'old_values' => [
                    'role_id' => $before['role_id'],
                    'role_name' => $before['role_name'],
                ],
                'new_values' => [
                    'role_id' => $currentRole?->id,
                    'role_name' => $this->resolveRoleName($currentRole),
                ],
            ]);
        }

        if ($passwordChanged) {
            UserAuditLog::create([
                'actor_user_id' => $actor?->id,
                'target_user_id' => $user->id,
                'event_key' => 'password_changed',
                'new_values' => [
                    'password_reset' => true,
                ],
            ]);
        }
    }

    private function resolveRoleName(?Role $role): ?string
    {
        if (! $role) {
            return null;
        }

        $roleName = $role->name;

        if (is_array($roleName)) {
            return $roleName['ar'] ?? $roleName['en'] ?? reset($roleName) ?: null;
        }

        return $roleName;
    }
}
