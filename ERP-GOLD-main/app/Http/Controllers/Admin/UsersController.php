<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\UserAuditLog;
use App\Services\Branches\BranchContextService;
use App\Services\Permissions\PermissionMatrixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UsersController extends Controller
{
    public function __construct(
        private readonly BranchContextService $branchContextService,
        private readonly PermissionMatrixService $permissionMatrixService,
    )
    {
        $this->middleware('permission:employee.users.show', ['only' => ['index', 'show']]);
        $this->middleware('permission:employee.users.add', ['only' => ['create', 'store']]);
        $this->middleware('permission:employee.users.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:employee.users.delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $actor = request()->user('admin-web');

        $users = User::with(['subscriber', 'branch', 'branches', 'roles'])
            ->when(
                filled($actor?->subscriber_id),
                fn ($query) => $query->where('subscriber_id', $actor->subscriber_id)
            )
            ->latest()
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $actor = request()->user('admin-web');
        $branches = Branch::query()
            ->when(
                filled($actor?->subscriber_id),
                fn ($query) => $query->where('subscriber_id', $actor->subscriber_id)
            )
            ->latest()
            ->get();
        $selectedBranchId = request()->integer('branch_id');
        $selectedBranchIds = old('branch_ids', $selectedBranchId ? [$selectedBranchId] : []);
        $returnBranchId = request()->integer('return_branch_id');

        return view('admin.users.create', [
            'roles' => $roles,
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId,
            'selectedBranchIds' => $selectedBranchIds,
            'returnBranchId' => $returnBranchId,
            'permissionGroups' => $this->permissionMatrixService->permissionGroups(),
            'selectedPermissions' => old('direct_permissions', []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'email' => 'required|email|max:255|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'required|exists:branches,id',
            'branch_ids' => 'required|array|min:1',
            'branch_ids.*' => 'integer|exists:branches,id',
            'password' => 'required|string|same:confirm-password',
            'direct_permissions' => 'nullable|array',
            'direct_permissions.*' => 'string|exists:permissions,name',
        ]);

        $assignedBranchIds = $this->normalizedBranchIds(
            $validated['branch_ids'] ?? [],
            (int) $validated['branch_id']
        );

        $actor = $request->user('admin-web');
        $subscriber = $this->currentSubscriber($actor);

        $this->ensureBranchSelectionBelongsToSubscriber($subscriber, $assignedBranchIds);
        $this->ensureSubscriberCanAddUser($subscriber);

        $user = User::create([
            'subscriber_id' => $subscriber?->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'branch_id' => $validated['branch_id'],
            'password' => Hash::make($validated['password']),
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $user->assignRole($role);
        $user->syncPermissions($validated['direct_permissions'] ?? []);
        $this->branchContextService->syncUserBranches($user, $assignedBranchIds, (int) $validated['branch_id']);

        return $this->redirectAfterUserSave($request, __('main.created'));
    }

    public function update(Request $request, $id)
    {
        $user = User::with(['roles', 'permissions', 'branches'])->findOrFail($id);
        $actor = $request->user('admin-web');
        $subscriber = $this->currentSubscriber($actor);
        $this->ensureManagedUserBelongsToSubscriber($subscriber, $user);

        $previousBranch = $user->branch;
        $previousRole = $user->roles->first();
        $previousDirectPermissions = $user->permissions->pluck('name')->sort()->values()->all();
        $previousAssignedBranches = $user->branches
            ->pluck('branch_name', 'id')
            ->map(fn ($name, $branchId) => ['id' => (int) $branchId, 'name' => $name])
            ->values()
            ->all();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:users,name,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'required|exists:branches,id',
            'branch_ids' => 'required|array|min:1',
            'branch_ids.*' => 'integer|exists:branches,id',
            'status' => 'nullable|boolean',
            'password' => 'nullable|string|same:confirm-password',
            'direct_permissions' => 'nullable|array',
            'direct_permissions.*' => 'string|exists:permissions,name',
        ]);

        $previousStatus = (bool) $user->status;
        $previousBranchId = (int) $user->branch_id;
        $previousRoleId = $previousRole?->id;
        $passwordChanged = ! empty($validated['password']);
        $assignedBranchIds = $this->normalizedBranchIds(
            $validated['branch_ids'] ?? [],
            (int) $validated['branch_id']
        );

        $this->ensureBranchSelectionBelongsToSubscriber($subscriber, $assignedBranchIds);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subscriber_id' => $subscriber?->id ?? $user->subscriber_id,
            'branch_id' => $validated['branch_id'],
            'status' => (bool) ($validated['status'] ?? $user->status),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $this->branchContextService->syncUserBranches($user, $assignedBranchIds, (int) $validated['branch_id']);
        $role = Role::findOrFail($validated['role_id']);
        $user->syncRoles([$role]);
        $user->syncPermissions($validated['direct_permissions'] ?? []);
        $this->writeAuditLogs(
            $request->user('admin-web'),
            $user->fresh(['branch', 'roles', 'permissions']),
            [
                'status' => $previousStatus,
                'branch_id' => $previousBranchId,
                'branch_name' => $previousBranch?->branch_name,
                'role_id' => $previousRoleId,
                'role_name' => $this->resolveRoleName($previousRole),
                'assigned_branches' => $previousAssignedBranches,
                'direct_permissions' => $previousDirectPermissions,
            ],
            $passwordChanged,
        );

        return $this->redirectAfterUserSave($request, __('main.updated'));
    }

    public function show($id)
    {
        $user = User::with([
            'branch',
            'branches',
            'roles.permissions',
            'permissions',
            'auditLogs' => function ($query) {
                $query->with('actor')->latest()->limit(15);
            },
        ])->findOrFail($id);
        $this->ensureManagedUserBelongsToSubscriber($this->currentSubscriber(request()->user('admin-web')), $user);

        $directPermissions = $user->permissions->pluck('name')->sort()->values();
        $rolePermissions = $user->roles
            ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
            ->unique()
            ->sort()
            ->values();
        $effectivePermissions = $user->getAllPermissions()
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();

        return view('admin.users.show', compact('user', 'directPermissions', 'rolePermissions', 'effectivePermissions'));
    }

    public function edit($id)
    {
        $user = User::with(['branch', 'branches', 'roles', 'permissions'])->findOrFail($id);
        $actor = request()->user('admin-web');
        $subscriber = $this->currentSubscriber($actor);
        $this->ensureManagedUserBelongsToSubscriber($subscriber, $user);
        $roles = Role::all();
        $branches = Branch::query()
            ->when(
                filled($actor?->subscriber_id),
                fn ($query) => $query->where('subscriber_id', $actor->subscriber_id)
            )
            ->latest()
            ->get();
        $userRole = $user->roles->pluck('id')->all();

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $roles,
            'branches' => $branches,
            'userRole' => $userRole,
            'selectedBranchIds' => old('branch_ids', $user->branches->pluck('id')->all() ?: [$user->branch_id]),
            'permissionGroups' => $this->permissionMatrixService->permissionGroups(),
            'selectedPermissions' => old('direct_permissions', $user->permissions->pluck('name')->all()),
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        $this->ensureManagedUserBelongsToSubscriber($this->currentSubscriber(request()->user('admin-web')), $user);

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

        $beforeAssignedBranches = collect($before['assigned_branches'] ?? [])
            ->map(fn ($branch) => ['id' => (int) ($branch['id'] ?? 0), 'name' => $branch['name'] ?? null])
            ->sortBy('id')
            ->values()
            ->all();

        $currentAssignedBranches = $user->branches
            ->pluck('branch_name', 'id')
            ->map(fn ($name, $branchId) => ['id' => (int) $branchId, 'name' => $name])
            ->sortBy('id')
            ->values()
            ->all();

        if ($beforeAssignedBranches !== $currentAssignedBranches) {
            UserAuditLog::create([
                'actor_user_id' => $actor?->id,
                'target_user_id' => $user->id,
                'event_key' => 'assigned_branches_changed',
                'old_values' => [
                    'branches' => $beforeAssignedBranches,
                ],
                'new_values' => [
                    'branches' => $currentAssignedBranches,
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

        $currentDirectPermissions = $user->permissions->pluck('name')->sort()->values()->all();
        $beforeDirectPermissions = collect($before['direct_permissions'] ?? [])
            ->sort()
            ->values()
            ->all();

        if ($beforeDirectPermissions !== $currentDirectPermissions) {
            UserAuditLog::create([
                'actor_user_id' => $actor?->id,
                'target_user_id' => $user->id,
                'event_key' => 'direct_permissions_changed',
                'old_values' => [
                    'permissions' => $beforeDirectPermissions,
                ],
                'new_values' => [
                    'permissions' => $currentDirectPermissions,
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

    /**
     * @param  array<int, mixed>  $branchIds
     * @return array<int>
     */
    private function normalizedBranchIds(array $branchIds, int $defaultBranchId): array
    {
        return collect($branchIds)
            ->map(fn ($branchId) => (int) $branchId)
            ->push($defaultBranchId)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function redirectAfterUserSave(Request $request, string $message)
    {
        $returnBranchId = $request->integer('return_branch_id');

        if (
            $returnBranchId
            && Branch::query()->whereKey($returnBranchId)->exists()
            && $request->user('admin-web')?->can('employee.branches.show')
        ) {
            return redirect()
                ->route('admin.branches.show', $returnBranchId)
                ->with('success', $message);
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    private function currentSubscriber(?User $actor): ?Subscriber
    {
        if (! $actor || ! filled($actor->subscriber_id)) {
            return null;
        }

        return $actor->subscriber ?: Subscriber::query()->find($actor->subscriber_id);
    }

    /**
     * @param  array<int>  $branchIds
     */
    private function ensureBranchSelectionBelongsToSubscriber(?Subscriber $subscriber, array $branchIds): void
    {
        if (! $subscriber || $branchIds === []) {
            return;
        }

        $count = Branch::query()
            ->where('subscriber_id', $subscriber->id)
            ->whereIn('id', $branchIds)
            ->count();

        if ($count !== count($branchIds)) {
            throw ValidationException::withMessages([
                'branch_ids' => ['لا يمكنك ربط المستخدم بفروع خارج حساب المشترك الحالي.'],
            ]);
        }
    }

    private function ensureSubscriberCanAddUser(?Subscriber $subscriber): void
    {
        if (! $subscriber || blank($subscriber->max_users) || (int) $subscriber->max_users <= 0) {
            return;
        }

        $currentUsersCount = User::query()
            ->where('subscriber_id', $subscriber->id)
            ->count();

        if ($currentUsersCount >= (int) $subscriber->max_users) {
            throw ValidationException::withMessages([
                'email' => ['تم الوصول إلى الحد الأقصى للمستخدمين في هذا الاشتراك.'],
            ]);
        }
    }

    private function ensureManagedUserBelongsToSubscriber(?Subscriber $subscriber, ?User $managedUser): void
    {
        if (! $subscriber || ! $managedUser) {
            return;
        }

        abort_unless(
            (int) $managedUser->subscriber_id === (int) $subscriber->id,
            403,
            'لا يمكنك إدارة مستخدم خارج حساب المشترك الحالي.'
        );
    }
}
