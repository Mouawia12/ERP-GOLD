<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolesController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:employee.user_permissions.show', ['only' => ['index']]);
        $this->middleware('permission:employee.user_permissions.add', ['only' => ['create', 'store']]);
        $this->middleware('permission:employee.user_permissions.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:employee.user_permissions.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $roles = Role::all();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permission = Permission::get();
        return view('admin.roles.create', compact('permission'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'guard_name' => 'required',
            'permission' => 'required',
        ]);

        $role = Role::create([
            'name' => ['ar' => $request->input('name'), 'en' => $request->input('name')],
            'guard_name' => $request->input('guard_name')
        ]);
        $role->syncPermissions($request->input('permission'));

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'تم اضافة الصلاحية بنجاح');
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
        return view('admin.roles.edit', compact('role', 'rolePermissions'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'permission' => 'required',
        ]);
        $role = Role::findOrFail($id);
        $role->name = $request->input('name');
        $role->save();

        $role->syncPermissions($request->input('permission'));

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'تم تعديل الصلاحية بنجاح');
    }

    public function destroy(Request $request)
    {
        DB::table('roles')->where('id', $request->role_id)->delete();
        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'تم حذف الصلاحية بنجاح');
    }
}
