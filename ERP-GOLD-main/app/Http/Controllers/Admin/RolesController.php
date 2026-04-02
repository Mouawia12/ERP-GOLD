<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Permissions\PermissionMatrixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolesController extends Controller
{
    public function __construct(
        private readonly PermissionMatrixService $permissionMatrixService,
    )
    {
        $this->middleware('permission:employee.user_permissions.show', ['only' => ['index']]);
        $this->middleware('permission:employee.user_permissions.add', ['only' => ['create', 'store']]);
        $this->middleware('permission:employee.user_permissions.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:employee.user_permissions.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $roles = Role::withCount('permissions')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create', [
            'permissionGroups' => $this->permissionMatrixService->permissionGroups(),
            'selectedPermissions' => old('permission', []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $exists = Role::query()
                        ->where(function ($query) use ($value) {
                            $query->where('name->ar', $value)
                                ->orWhere('name->en', $value)
                                ->orWhere('name', $value);
                        })
                        ->exists();

                    if ($exists) {
                        $fail('اسم مجموعة الصلاحيات مستخدم بالفعل. اختر اسمًا آخر.');
                    }
                },
            ],
            'guard_name' => 'required',
            'permission' => 'required|array|min:1',
            'permission.*' => 'string|exists:permissions,name',
        ], $this->roleValidationMessages(), $this->roleValidationAttributes());

        $role = Role::create([
            'name' => ['ar' => $validated['name'], 'en' => $validated['name']],
            'guard_name' => $validated['guard_name']
        ]);
        $role->syncPermissions($validated['permission']);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'تم إنشاء مجموعة الصلاحيات بنجاح. يمكنك الآن إسنادها لأي مستخدم.');
    }

    public function show($id)
    {
        $role = Role::findOrFail($id);
        $rolePermissions = Permission::join('role_has_permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->where('role_has_permissions.role_id', $id)
            ->get();
        return view('admin.roles.show', compact('role', 'rolePermissions'));
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $rolePermissions = $role->permissions()->pluck('name')->toArray();

        return view('admin.roles.edit', [
            'role' => $role,
            'rolePermissions' => $rolePermissions,
            'permissionGroups' => $this->permissionMatrixService->permissionGroups(),
            'selectedPermissions' => old('permission', $rolePermissions),
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($id): void {
                    $exists = Role::query()
                        ->where('id', '!=', $id)
                        ->where(function ($query) use ($value) {
                            $query->where('name->ar', $value)
                                ->orWhere('name->en', $value)
                                ->orWhere('name', $value);
                        })
                        ->exists();

                    if ($exists) {
                        $fail('اسم مجموعة الصلاحيات مستخدم بالفعل. اختر اسمًا آخر.');
                    }
                },
            ],
            'permission' => 'required|array|min:1',
            'permission.*' => 'string|exists:permissions,name',
        ], $this->roleValidationMessages($id), $this->roleValidationAttributes());
        $role = Role::findOrFail($id);
        $role->name = ['ar' => $validated['name'], 'en' => $validated['name']];
        $role->save();

        $role->syncPermissions($validated['permission']);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'تم تحديث مجموعة الصلاحيات بنجاح.');
    }

    public function destroy(Request $request)
    {
        DB::table('roles')->where('id', $request->role_id)->delete();
        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'تم حذف الصلاحية بنجاح');
    }

    /**
     * @return array<string, string>
     */
    private function roleValidationMessages(?int $roleId = null): array
    {
        return [
            'name.required' => 'يرجى إدخال اسم واضح لمجموعة الصلاحيات.',
            'name.string' => 'اسم مجموعة الصلاحيات يجب أن يكون نصًا صالحًا.',
            'name.unique' => 'اسم مجموعة الصلاحيات مستخدم بالفعل. اختر اسمًا آخر.',
            'guard_name.required' => 'تعذر تحديد نوع الحارس المرتبط بالصلاحيات.',
            'permission.required' => 'حدد صلاحية واحدة على الأقل داخل المجموعة قبل الحفظ.',
            'permission.array' => 'قائمة الصلاحيات المختارة غير صحيحة.',
            'permission.min' => 'حدد صلاحية واحدة على الأقل داخل المجموعة قبل الحفظ.',
            'permission.*.exists' => 'تم اختيار صلاحية غير موجودة أو غير صالحة.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function roleValidationAttributes(): array
    {
        return [
            'name' => 'اسم مجموعة الصلاحيات',
            'guard_name' => 'نوع الحارس',
            'permission' => 'الصلاحيات',
        ];
    }
}
