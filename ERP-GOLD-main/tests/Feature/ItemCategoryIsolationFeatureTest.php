<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ItemCategoryIsolationFeatureTest extends TestCase
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

    public function test_subscriber_admin_only_sees_his_own_categories_in_categories_and_item_filters(): void
    {
        $subscriber = $this->createSubscriber('مشترك الأصناف');
        $otherSubscriber = $this->createSubscriber('مشترك أصناف آخر');
        $admin = $this->createSubscriberAdminUser($subscriber, [
            'employee.items.show',
            'employee.items.add',
        ]);

        $ownCategory = $this->createCategory($subscriber, 'خواتم المشترك');
        $foreignCategory = $this->createCategory($otherSubscriber, 'خواتم مشترك آخر');
        $this->createCatalogLookups();

        $categoriesResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('categories', [], false));

        $categoriesResponse->assertOk();
        $categoriesResponse->assertSee($ownCategory->getTranslation('title', 'ar'));
        $categoriesResponse->assertDontSee($foreignCategory->getTranslation('title', 'ar'));

        $itemFormResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('items.create', [], false));

        $itemFormResponse->assertOk();
        $itemFormResponse->assertSee($ownCategory->getTranslation('title', 'ar'));
        $itemFormResponse->assertDontSee($foreignCategory->getTranslation('title', 'ar'));

        $reportFiltersResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('reports.items.list', [], false));

        $reportFiltersResponse->assertOk();
        $reportFiltersResponse->assertSee($ownCategory->getTranslation('title', 'ar'));
        $reportFiltersResponse->assertDontSee($foreignCategory->getTranslation('title', 'ar'));
    }

    public function test_subscriber_admin_cannot_fetch_or_use_foreign_category_when_creating_item(): void
    {
        $subscriber = $this->createSubscriber('مشترك حفظ الصنف');
        $otherSubscriber = $this->createSubscriber('مشترك آخر لحفظ الصنف');
        $admin = $this->createSubscriberAdminUser($subscriber, [
            'employee.items.show',
            'employee.items.add',
        ]);

        $foreignCategory = $this->createCategory($otherSubscriber, 'تصنيف أجنبي');
        [, $caratId, $caratTypeId] = $this->createCatalogLookups();

        $this->actingAs($admin, 'admin-web')
            ->get(route('getCategory', $foreignCategory->id, false))
            ->assertNotFound();

        $branch = Branch::query()->where('subscriber_id', $subscriber->id)->firstOrFail();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $branch->id,
                'inventory_classification' => Item::CLASSIFICATION_GOLD,
                'sale_mode' => Item::SALE_MODE_SINGLE,
                'item_type' => $caratTypeId,
                'carats_id' => $caratId,
                'name_ar' => 'خاتم مرفوض',
                'name_en' => 'Rejected Ring',
                'category_id' => $foreignCategory->id,
                'weight' => 4.5,
                'cost_per_gram' => 350,
                'labor_cost_per_gram' => 15,
                'profit_margin_per_gram' => 20,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonFragment([
            'مجموعة الصنف المختارة غير متاحة لهذا المشترك.',
        ]);
    }

    public function test_item_list_report_search_is_scoped_to_current_subscriber_items(): void
    {
        $subscriber = $this->createSubscriber('مشترك تقرير الأصناف');
        $otherSubscriber = $this->createSubscriber('مشترك تقرير آخر');
        $admin = $this->createSubscriberAdminUser($subscriber, [
            'employee.items.show',
        ]);

        $ownBranch = Branch::query()->where('subscriber_id', $subscriber->id)->firstOrFail();
        $foreignBranch = Branch::create([
            'subscriber_id' => $otherSubscriber->id,
            'name' => ['ar' => 'فرع مشترك آخر', 'en' => 'Foreign Subscriber Branch'],
            'phone' => '555333222',
            'status' => true,
        ]);

        $ownCategory = $this->createCategory($subscriber, 'تصنيف التقرير المحلي');
        $foreignCategory = $this->createCategory($otherSubscriber, 'تصنيف التقرير الأجنبي');
        [, $caratId] = $this->createCatalogLookups();

        $this->insertItem([
            'branch_id' => $ownBranch->id,
            'inventory_classification' => Item::CLASSIFICATION_GOLD,
            'category_id' => $ownCategory->id,
            'gold_carat_id' => $caratId,
            'code' => '000101',
            'title' => ['ar' => 'صنف المشترك', 'en' => 'Subscriber Item'],
            'status' => true,
        ]);

        $this->insertItem([
            'branch_id' => $foreignBranch->id,
            'inventory_classification' => Item::CLASSIFICATION_GOLD,
            'category_id' => $foreignCategory->id,
            'gold_carat_id' => $caratId,
            'code' => '000202',
            'title' => ['ar' => 'صنف مشترك آخر', 'en' => 'Foreign Subscriber Item'],
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('reports.items.list.search', [], false), [
                'inventory_classification' => Item::CLASSIFICATION_GOLD,
            ]);

        $response->assertOk();
        $response->assertSee('صنف المشترك');
        $response->assertDontSee('صنف مشترك آخر');
        $response->assertDontSee('فرع مشترك آخر');
    }

    private function createSubscriber(string $name): Subscriber
    {
        return Subscriber::create([
            'name' => $name,
            'login_email' => uniqid('subscriber-', true).'@example.com',
            'status' => true,
        ]);
    }

    private function createCategory(Subscriber $subscriber, string $name): ItemCategory
    {
        return ItemCategory::withoutGlobalScopes()->create([
            'subscriber_id' => $subscriber->id,
            'title' => ['ar' => $name, 'en' => $name],
            'code' => strtoupper(substr(md5($name.$subscriber->id), 0, 8)),
            'description' => ['ar' => $name.' وصف', 'en' => $name.' description'],
        ]);
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function createCatalogLookups(): array
    {
        $taxId = DB::table('taxes')->insertGetId([
            'title' => 'VAT 15%',
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $caratId = DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => 'عيار 21', 'en' => '21K'], JSON_UNESCAPED_UNICODE),
            'label' => 'C21',
            'tax_id' => $taxId,
            'transform_factor' => '1',
            'is_pure' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $caratTypeId = DB::table('gold_carat_types')->insertGetId([
            'title' => 'مشغول',
            'key' => 'crafted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $fallbackCategoryId = DB::table('item_categories')->insertGetId([
            'title' => json_encode(['ar' => 'تصنيف عام للاختبارات', 'en' => 'General Category'], JSON_UNESCAPED_UNICODE),
            'code' => 'TEST-CAT-'.uniqid(),
            'description' => json_encode(['ar' => 'تصنيف عام', 'en' => 'General category'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$fallbackCategoryId, $caratId, $caratTypeId];
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createSubscriberAdminUser(Subscriber $subscriber, array $permissions = []): User
    {
        $branch = Branch::create([
            'subscriber_id' => $subscriber->id,
            'name' => ['ar' => 'فرع '.$subscriber->name, 'en' => 'Branch '.$subscriber->name],
            'phone' => '555111222',
            'status' => true,
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مدير أصناف '.$subscriber->id, 'en' => 'Items Admin '.$subscriber->id],
            'guard_name' => 'admin-web',
        ]);

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'admin-web',
            ]);

            $role->givePermissionTo($permission);
        }

        $user = User::create([
            'subscriber_id' => $subscriber->id,
            'name' => 'Category Isolation Admin',
            'email' => uniqid('category-admin-', true).'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user->fresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertItem(array $attributes): void
    {
        $title = $attributes['title'];
        unset($attributes['title']);

        $itemId = DB::table('items')->insertGetId(array_merge([
            'description' => null,
            'inventory_classification' => Item::CLASSIFICATION_GOLD,
            'gold_carat_type_id' => null,
            'no_metal' => 0,
            'no_metal_type' => 'fixed',
            'labor_cost_per_gram' => 0,
            'profit_margin_per_gram' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], [
            'title' => json_encode($title, JSON_UNESCAPED_UNICODE),
        ], $attributes));

        DB::table('branch_items')->insert([
            'branch_id' => $attributes['branch_id'],
            'item_id' => $itemId,
            'is_active' => true,
            'is_visible' => true,
            'sale_price_per_gram' => null,
            'published_by_user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
