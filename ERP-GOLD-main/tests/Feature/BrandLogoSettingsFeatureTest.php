<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BrandLogoSettingsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutMiddleware([
            LaravelLocalizationRedirectFilter::class,
            LocaleSessionRedirect::class,
        ]);
    }

    public function test_authorized_admin_can_upload_brand_logo_and_it_appears_in_core_views(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser([
            'employee.system_settings.show',
            'employee.system_settings.edit',
        ]);

        $file = UploadedFile::fake()->image('gold-brand-logo.png', 640, 240);

        $this->actingAs($admin, 'admin-web')
            ->patch(route('admin.system-settings.branding.update', [], false), [
                'brand_logo' => $file,
            ])
            ->assertRedirect(route('admin.system-settings.branding.edit', [], false))
            ->assertSessionHasNoErrors();

        $storedPath = SystemSetting::getValue('brand_logo_path');

        $this->assertNotNull($storedPath);
        $this->assertStringStartsWith('branding/', $storedPath);
        Storage::disk('public')->assertExists($storedPath);

        $settingsResponse = $this->actingAs($admin, 'admin-web')
            ->get(route('admin.system-settings.branding.edit', [], false));

        $settingsResponse->assertOk();
        $settingsResponse->assertSee('/storage/'.$storedPath, false);

        Auth::guard('admin-web')->logout();

        $loginResponse = $this->get(route('admin.login', [], false));
        $loginResponse->assertOk();
        $loginResponse->assertSee('/storage/'.$storedPath, false);

        $dashboardResponse = $this->actingAs($admin, 'admin-web')
            ->get(route('admin.home', [], false));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('/storage/'.$storedPath, false);
        $dashboardResponse->assertSee('brand-header-logo', false);
        $dashboardResponse->assertSee('app-sidebar__brand-logo', false);
    }

    public function test_unauthorized_admin_cannot_update_brand_logo(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser([
            'employee.system_settings.show',
        ]);

        $this->actingAs($admin, 'admin-web')
            ->patch(route('admin.system-settings.branding.update', [], false), [
                'brand_logo' => UploadedFile::fake()->image('blocked-brand-logo.png'),
            ])
            ->assertForbidden();

        $this->assertNull(SystemSetting::getValue('brand_logo_path'));
        $this->assertCount(0, Storage::disk('public')->allFiles('branding'));
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع الإعدادات', 'en' => 'Settings Branch'],
            'phone' => '123456789',
            'status' => true,
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مدير الإعدادات', 'en' => 'Settings Admin'],
            'guard_name' => 'admin-web',
        ]);

        foreach ($permissions as $permissionName) {
            $permission = Permission::create([
                'name' => $permissionName,
                'guard_name' => 'admin-web',
            ]);

            $role->givePermissionTo($permission);
        }

        $user = User::create([
            'name' => 'Settings Admin',
            'email' => 'settings-admin@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }
}
