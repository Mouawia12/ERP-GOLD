<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionIds = [];

        foreach (['add', 'edit', 'delete', 'show'] as $action) {
            DB::table('permissions')->updateOrInsert(
                [
                    'name' => 'employee.subscribers.' . $action,
                    'guard_name' => 'admin-web',
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $permissionIds[] = DB::table('permissions')
                ->where('name', 'employee.subscribers.' . $action)
                ->where('guard_name', 'admin-web')
                ->value('id');
        }

        $ownerRoleIds = DB::table('model_has_roles')
            ->join('users', 'model_has_roles.model_id', '=', 'users.id')
            ->where('model_has_roles.model_type', \App\Models\User::class)
            ->where('users.is_admin', true)
            ->pluck('model_has_roles.role_id')
            ->unique()
            ->filter()
            ->all();

        foreach ($ownerRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ], []);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('guard_name', 'admin-web')
            ->whereIn('name', [
                'employee.subscribers.add',
                'employee.subscribers.edit',
                'employee.subscribers.delete',
                'employee.subscribers.show',
            ])
            ->delete();
    }
};
