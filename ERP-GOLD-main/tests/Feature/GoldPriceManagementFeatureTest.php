<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GoldPriceManagementFeatureTest extends TestCase
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

    public function test_authorized_admin_can_sync_gold_prices_from_remote_service(): void
    {
        Config::set('services.gold_api.key', 'test-gold-api-key');
        Config::set('services.gold_api.base_url', 'https://fake-gold.example');

        Http::fake([
            'https://fake-gold.example/api/XAU/SAR' => Http::response([
                'timestamp' => '2026-03-22T21:30:00.000Z',
                'metal' => 'XAU',
                'currency' => 'SAR',
                'price' => 12345.67,
                'ask' => 12346.11,
                'bid' => 12344.75,
                'open_price' => 12300.00,
                'prev_close_price' => 12290.00,
                'low_price' => 12250.25,
                'high_price' => 12380.75,
                'price_gram_14k' => 180.12,
                'price_gram_18k' => 231.45,
                'price_gram_21k' => 270.98,
                'price_gram_22k' => 282.11,
                'price_gram_24k' => 307.33,
            ], 200),
        ]);

        $admin = $this->createAdminUser([
            'employee.gold_prices.show',
            'employee.gold_prices.edit',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('updatePrices', [], false), [
                'currency' => 'SAR',
            ]);

        $response->assertRedirect(route('prices', [], false));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('gold_prices', [
            'currency' => 'SAR',
            'source' => 'remote',
            'source_currency' => 'SAR',
            'ounce_21_price' => 270.98,
            'ounce_24_price' => 307.33,
        ]);

        $this->assertDatabaseHas('gold_price_histories', [
            'currency' => 'SAR',
            'source' => 'remote',
            'source_currency' => 'SAR',
            'ounce_21_price' => 270.98,
            'synced_by_user_id' => $admin->id,
        ]);
    }

    public function test_authorized_admin_can_update_gold_prices_manually_and_view_history(): void
    {
        $admin = $this->createAdminUser([
            'employee.gold_prices.show',
            'employee.gold_prices.edit',
        ]);

        $updateResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('updatePricesManual', [], false), [
                'currency' => 'SAR',
                'price14' => 175.50,
                'price18' => 225.40,
                'price21' => 263.70,
                'price22' => 274.80,
                'price24' => 299.10,
            ]);

        $updateResponse->assertRedirect(route('prices', [], false));
        $updateResponse->assertSessionHas('success');

        $this->assertDatabaseHas('gold_prices', [
            'currency' => 'SAR',
            'source' => 'manual',
            'source_currency' => 'SAR',
            'ounce_21_price' => 263.70,
            'ounce_24_price' => 299.10,
        ]);

        $this->assertDatabaseHas('gold_price_histories', [
            'currency' => 'SAR',
            'source' => 'manual',
            'source_currency' => 'SAR',
            'ounce_21_price' => 263.70,
            'synced_by_user_id' => $admin->id,
        ]);

        $indexResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('prices', [], false));

        $indexResponse->assertOk();
        $indexResponse->assertSee('السعر الحالي داخل النظام');
        $indexResponse->assertSee('263.70');
        $indexResponse->assertSee('تحديث يدوي');
        $indexResponse->assertSee($admin->name);
    }

    public function test_admin_without_edit_permission_cannot_update_gold_prices(): void
    {
        $admin = $this->createAdminUser([
            'employee.gold_prices.show',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('updatePricesManual', [], false), [
                'currency' => 'SAR',
                'price14' => 100,
                'price18' => 150,
                'price21' => 200,
                'price22' => 210,
                'price24' => 220,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('gold_prices', 0);
        $this->assertDatabaseCount('gold_price_histories', 0);
    }

    public function test_stock_market_page_displays_latest_remote_snapshots_per_currency(): void
    {
        Config::set('services.gold_api.key', 'test-gold-api-key');
        Config::set('services.gold_api.base_url', 'https://fake-gold.example');

        Http::fake([
            'https://fake-gold.example/api/XAU/USD' => Http::response([
                'timestamp' => '2026-03-22T21:30:00.000Z',
                'metal' => 'XAU',
                'currency' => 'USD',
                'price' => 3021.10,
                'price_gram_14k' => 56.42,
                'price_gram_18k' => 72.10,
                'price_gram_21k' => 84.11,
                'price_gram_22k' => 88.32,
                'price_gram_24k' => 96.14,
            ], 200),
            'https://fake-gold.example/api/XAU/SAR' => Http::response([
                'timestamp' => '2026-03-22T21:45:00.000Z',
                'metal' => 'XAU',
                'currency' => 'SAR',
                'price' => 11329.50,
                'price_gram_14k' => 173.00,
                'price_gram_18k' => 222.55,
                'price_gram_21k' => 259.58,
                'price_gram_22k' => 271.94,
                'price_gram_24k' => 296.66,
            ], 200),
        ]);

        $admin = $this->createAdminUser([
            'employee.gold_prices.show',
            'employee.gold_prices.edit',
        ]);

        $this->actingAs($admin, 'admin-web')->post(route('updatePrices', [], false), ['currency' => 'USD']);
        $this->actingAs($admin, 'admin-web')->post(route('updatePrices', [], false), ['currency' => 'SAR']);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('gold.stock.market.prices', [], false));

        $response->assertOk();
        $response->assertSee('آخر Snapshot بالدولار');
        $response->assertSee('آخر Snapshot بالريال السعودي');
        $response->assertSee('3,021.10');
        $response->assertSee('259.58');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع الأسعار', 'en' => 'Prices Branch'],
            'phone' => '555666777',
            'status' => true,
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مدير الأسعار', 'en' => 'Prices Admin'],
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
            'name' => 'Gold Price Admin',
            'email' => 'gold-price-admin-'.uniqid().'@example.com',
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
