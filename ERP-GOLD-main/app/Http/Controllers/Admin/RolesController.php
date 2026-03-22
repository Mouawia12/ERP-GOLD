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
    /**
     * @var array<string, string>
     */
    private array $permissionActions = [
        'add' => 'اضافة',
        'show' => 'عرض',
        'edit' => 'تعديل',
        'delete' => 'حذف',
    ];

    function __construct()
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
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => old('permission', []),
        ]);
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

        return view('admin.roles.edit', [
            'role' => $role,
            'rolePermissions' => $rolePermissions,
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => old('permission', $rolePermissions),
        ]);
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function permissionGroups(): array
    {
        $categories = [
            'الادارة' => ['users', 'user_permissions', 'branches', 'system_settings'],
            'المبيعات' => ['tax_invoices', 'simplified_tax_invoices', 'sales_returns', 'receipt_vouchers', 'cash_in_entries'],
            'المشتريات' => ['purchase_invoices', 'expense_vouchers', 'cash_out_entries'],
            'المخزون والتشغيل' => ['items', 'initial_quantities', 'stock_entries', 'warehouses', 'stock', 'inventory_list', 'stock_settlements', 'workbook', 'breakbook', 'convert_work_to_break'],
            'العملاء والموردون' => ['customers', 'suppliers'],
            'المحاسبة' => ['accounts', 'journal_entries'],
            'التقارير' => ['inventory_reports', 'accounting_reports', 'gold_balance_sheet'],
            'الاسعار' => ['gold_prices'],
        ];

        $configuredModules = collect(config('settings.permissions_modules'))
            ->map(fn ($module) => (string) $module);

        $grouped = [];
        foreach ($categories as $categoryLabel => $modules) {
            $categoryModules = $configuredModules
                ->filter(fn ($module) => in_array($module, $modules, true))
                ->values();

            if ($categoryModules->isEmpty()) {
                continue;
            }

            $grouped[] = [
                'label' => $categoryLabel,
                'modules' => $categoryModules->map(fn ($module) => [
                    'key' => $module,
                    'label' => __("dashboard.permissions_modules.$module"),
                    'permissions' => collect($this->permissionActions)->mapWithKeys(fn ($actionLabel, $actionKey) => [
                        $actionKey => [
                            'label' => $actionLabel,
                            'name' => "employee.{$module}.{$actionKey}",
                        ],
                    ])->all(),
                ])->all(),
            ];
        }

        $alreadyGrouped = collect($grouped)
            ->flatMap(fn ($group) => collect($group['modules'])->pluck('key'))
            ->all();

        $remainingModules = $configuredModules
            ->reject(fn ($module) => in_array($module, $alreadyGrouped, true))
            ->values();

        if ($remainingModules->isNotEmpty()) {
            $grouped[] = [
                'label' => 'اخرى',
                'modules' => $remainingModules->map(fn ($module) => [
                    'key' => $module,
                    'label' => __("dashboard.permissions_modules.$module"),
                    'permissions' => collect($this->permissionActions)->mapWithKeys(fn ($actionLabel, $actionKey) => [
                        $actionKey => [
                            'label' => $actionLabel,
                            'name' => "employee.{$module}.{$actionKey}",
                        ],
                    ])->all(),
                ])->all(),
            ];
        }

        return $grouped;
    }
}
