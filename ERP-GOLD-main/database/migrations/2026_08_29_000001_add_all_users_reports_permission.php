<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * صلاحية «تقارير كل المستخدمين»: ترفع قيد «فواتيري فقط» عن حاملها في تقارير
 * المبيعات والمخزون والأصناف المباعة، دون أن تفتح له فرعًا غير مصرّح به.
 *
 * تُنشأ الصلاحية فقط وتُمنح لدور السوبر أدمن (الذي يملك كل الصلاحيات أصلًا حسب
 * DefaultRoleSeeder). منحها لأي مستخدم آخر قرار المالك من شاشة الصلاحيات.
 */
return new class extends Migration {
    private const PERMISSION = 'employee.all_users_reports.show';

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', self::PERMISSION)
            ->where('guard_name', 'admin-web')
            ->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => self::PERMISSION,
                'guard_name' => 'admin-web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $superAdminRoleIds = DB::table('roles')
            ->where('guard_name', 'admin-web')
            ->get(['id', 'name'])
            ->filter(function ($role) {
                $name = $role->name;

                if (is_string($name) && str_starts_with($name, '{')) {
                    $decoded = json_decode($name, true);
                    $name = is_array($decoded) ? ($decoded['ar'] ?? $decoded['en'] ?? '') : $name;
                }

                return str_contains((string) $name, 'سوبر') || str_contains(mb_strtolower((string) $name), 'super');
            })
            ->pluck('id');

        foreach ($superAdminRoleIds as $roleId) {
            $exists = DB::table('role_has_permissions')
                ->where('permission_id', $permissionId)
                ->where('role_id', $roleId)
                ->exists();

            if (! $exists) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', self::PERMISSION)
            ->where('guard_name', 'admin-web')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
