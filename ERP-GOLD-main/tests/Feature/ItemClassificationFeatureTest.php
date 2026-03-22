<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Item;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ItemClassificationFeatureTest extends TestCase
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

    public function test_item_create_form_exposes_inventory_classification_choices(): void
    {
        $admin = $this->createAdminUser([
            'employee.items.add',
        ]);
        $this->createItemCatalogLookups();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('items.create', [], false));

        $response->assertOk();
        $response->assertSee('name="inventory_classification"', false);
        $response->assertSee('ذهب');
        $response->assertSee('مقتنيات');
        $response->assertSee('فضة');
    }

    public function test_gold_items_require_carat_while_non_gold_items_can_be_saved_without_it(): void
    {
        $admin = $this->createAdminUser([
            'employee.items.add',
        ]);
        [$branch, $categoryId, $caratId, $caratTypeId] = $this->createItemCatalogLookups();

        $invalidGoldResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $branch->id,
                'inventory_classification' => Item::CLASSIFICATION_GOLD,
                'item_type' => $caratTypeId,
                'name_ar' => 'خاتم بدون عيار',
                'name_en' => 'Gold Ring Without Carat',
                'category_id' => $categoryId,
                'weight' => 4.2,
                'cost_per_gram' => 330,
            ], [
                'Accept' => 'application/json',
            ]);

        $invalidGoldResponse->assertStatus(422);
        $invalidGoldResponse->assertJsonPath('status', false);
        $invalidGoldResponse->assertJsonPath('errors.0', 'validation.required');

        $validSilverResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $branch->id,
                'inventory_classification' => Item::CLASSIFICATION_SILVER,
                'name_ar' => 'سوار فضي',
                'name_en' => 'Silver Bracelet',
                'category_id' => $categoryId,
                'weight' => 8.75,
                'cost_per_gram' => 45,
                'labor_cost_per_gram' => 7,
            ], [
                'Accept' => 'application/json',
            ]);

        $validSilverResponse->assertOk();
        $validSilverResponse->assertJsonPath('status', true);

        $savedItem = Item::query()->where('code', '000001')->first();

        $this->assertNotNull($savedItem);
        $this->assertSame(Item::CLASSIFICATION_SILVER, $savedItem->inventory_classification);
        $this->assertNull($savedItem->gold_carat_id);
        $this->assertNull($savedItem->gold_carat_type_id);
        $this->assertSame('سوار فضي', $savedItem->getTranslation('title', 'ar'));

        $this->assertDatabaseHas('item_units', [
            'item_id' => $savedItem->id,
            'weight' => 8.75,
            'average_cost_per_gram' => 45,
            'is_default' => true,
        ]);

        $validGoldResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $branch->id,
                'inventory_classification' => Item::CLASSIFICATION_GOLD,
                'item_type' => $caratTypeId,
                'carats_id' => $caratId,
                'name_ar' => 'خاتم ذهبي',
                'name_en' => 'Gold Ring',
                'category_id' => $categoryId,
                'weight' => 3.1,
                'cost_per_gram' => 350,
            ], [
                'Accept' => 'application/json',
            ]);

        $validGoldResponse->assertOk();
        $this->assertDatabaseHas('items', [
            'inventory_classification' => Item::CLASSIFICATION_GOLD,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
        ]);
    }

    /**
     * @return array{0:Branch,1:int,2:int,3:int}
     */
    private function createItemCatalogLookups(): array
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع الأصناف', 'en' => 'Items Branch'],
            'phone' => '111222333',
            'status' => true,
        ]);

        $taxId = DB::table('taxes')->insertGetId([
            'title' => 'VAT',
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('item_categories')->insertGetId([
            'title' => json_encode(['ar' => 'أساور', 'en' => 'Bracelets'], JSON_UNESCAPED_UNICODE),
            'code' => 'CAT-CLASS-1',
            'description' => json_encode(['ar' => 'تصنيف الأساور', 'en' => 'Bracelets category'], JSON_UNESCAPED_UNICODE),
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

        return [$branch, $categoryId, $caratId, $caratTypeId];
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'الفرع الرئيسي', 'en' => 'Main Branch'],
            'phone' => '999999999',
            'status' => true,
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مدير الأصناف', 'en' => 'Items Admin'],
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
            'name' => 'Items Admin',
            'email' => 'items-admin-'.uniqid().'@example.com',
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
