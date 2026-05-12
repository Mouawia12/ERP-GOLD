<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $actions = ['add', 'edit', 'delete', 'show'];
        $permissionIds = [];

        foreach ($actions as $action) {
            DB::table('permissions')->updateOrInsert(
                [
                    'name' => 'employee.branch_karat_transfers.' . $action,
                    'guard_name' => 'admin-web',
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $permissionIds[$action] = DB::table('permissions')
                ->where('name', 'employee.branch_karat_transfers.' . $action)
                ->where('guard_name', 'admin-web')
                ->value('id');
        }

        // Grant the new permissions to any role that already holds
        // an equivalent action on the manufacturing_orders module
        // (admin-level roles for the stock & operations group).
        foreach ($actions as $action) {
            $referencePermissionId = DB::table('permissions')
                ->where('name', 'employee.manufacturing_orders.' . $action)
                ->where('guard_name', 'admin-web')
                ->value('id');

            if (! $referencePermissionId || empty($permissionIds[$action])) {
                continue;
            }

            $roleIds = DB::table('role_has_permissions')
                ->where('permission_id', $referencePermissionId)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->updateOrInsert(
                    [
                        'permission_id' => $permissionIds[$action],
                        'role_id' => $roleId,
                    ],
                    []
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'admin-web')
            ->whereIn('name', [
                'employee.branch_karat_transfers.add',
                'employee.branch_karat_transfers.edit',
                'employee.branch_karat_transfers.delete',
                'employee.branch_karat_transfers.show',
            ])
            ->pluck('id')
            ->all();

        if ($permissionIds !== []) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
