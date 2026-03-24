<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DefaultRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DefaultRoleSeederTest extends TestCase
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

    public function test_default_role_seeder_bootstraps_an_owner_account(): void
    {
        $this->seed(DefaultRoleSeeder::class);

        $user = User::query()
            ->where('email', 'superadmin@gmail.com')
            ->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->isOwner());
        $this->assertTrue((bool) $user->status);
        $this->assertNotNull($user->branch_id);
        $this->assertGreaterThan(0, $user->roles()->count());

        $response = $this->actingAs($user, 'admin-web')
            ->get(route('admin.home', [], false));

        $response->assertOk();
        $response->assertSee('إدارة المشتركين');
        $response->assertDontSee('المبيعات الضريبية المبسطة');
    }
}
