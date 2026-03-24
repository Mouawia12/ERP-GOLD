<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Role::count() > 0) {
            return;
        }

        $branch = Branch::firstOrCreate([
            'name' => ['ar' => 'الفرع الرئيسي', 'en' => 'Main Branch'],
        ]);

        $user = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('123456'),
                'branch_id' => $branch->id,
                'phone_number' => '123456789',
                'profile_pic' => 'default.png',
                'status' => true,
                'is_admin' => true,
            ]
        );

        $user->forceFill([
            'name' => $user->name ?: 'Super Admin',
            'branch_id' => $user->branch_id ?: $branch->id,
            'phone_number' => $user->phone_number ?: '123456789',
            'profile_pic' => $user->profile_pic ?: 'default.png',
            'status' => true,
            'is_admin' => true,
        ])->save();

        $new_role = Role::create(['name' => ['ar' => 'سوبر ادمن', 'en' => 'Super Admin'], 'guard_name' => 'admin-web']);
        $permissions_modules = config('settings.permissions_modules');
        foreach ($permissions_modules as $permission) {
            $permission_add = Permission::updateOrCreate(['name' => 'employee.' . $permission . '.add', 'guard_name' => 'admin-web'], ['name' => 'employee.' . $permission . '.add', 'guard_name' => 'admin-web']);
            $permission_edit = Permission::updateOrCreate(['name' => 'employee.' . $permission . '.edit', 'guard_name' => 'admin-web'], ['name' => 'employee.' . $permission . '.edit', 'guard_name' => 'admin-web']);
            $permission_delete = Permission::updateOrCreate(['name' => 'employee.' . $permission . '.delete', 'guard_name' => 'admin-web'], ['name' => 'employee.' . $permission . '.delete', 'guard_name' => 'admin-web']);
            $permission_show = Permission::updateOrCreate(['name' => 'employee.' . $permission . '.show', 'guard_name' => 'admin-web'], ['name' => 'employee.' . $permission . '.show', 'guard_name' => 'admin-web']);
            $new_role->givePermissionTo($permission_add);
            $new_role->givePermissionTo($permission_edit);
            $new_role->givePermissionTo($permission_delete);
            $new_role->givePermissionTo($permission_show);
        }
        $user->assignRole($new_role);
    }
}
