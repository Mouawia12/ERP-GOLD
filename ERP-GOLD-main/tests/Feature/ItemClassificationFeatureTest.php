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
        $response->assertSee('name="sale_mode"', false);
        $response->assertSee('ذهب');
        $response->assertSee('مقتنيات');
        $response->assertSee('فضة');
        $response->assertSee('يباع مرة واحدة');
        $response->assertSee('يباع أكثر من مرة');
    }

    public function test_gold_items_require_carat_while_non_gold_items_can_be_saved_without_it(): void
    {
        $admin = $this->createAdminUser([
            'employee.items.add',
        ]);
        [$branch, $categoryId, $caratId, $caratTypeId, $silverCaratId] = $this->createItemCatalogLookups();

        $invalidGoldResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $branch->id,
                'inventory_classification' => Item::CLASSIFICATION_GOLD,
                'sale_mode' => Item::SALE_MODE_SINGLE,
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
        $this->assertStringContainsString(
            'carats id',
            strtolower(implode("\n", $invalidGoldResponse->json('errors', [])))
        );

        $validSilverResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $branch->id,
                'inventory_classification' => Item::CLASSIFICATION_SILVER,
                'sale_mode' => Item::SALE_MODE_REPEATABLE,
                'name_ar' => 'سوار فضي',
                'name_en' => 'Silver Bracelet',
                'category_id' => $categoryId,
                'carats_id' => $caratId,
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
        $this->assertSame(Item::SALE_MODE_REPEATABLE, $savedItem->sale_mode);
        $this->assertSame($silverCaratId, (int) $savedItem->gold_carat_id);
        $this->assertNull($savedItem->gold_carat_type_id);
        $this->assertSame('سوار فضي', $savedItem->getTranslation('title', 'ar'));

        $this->assertDatabaseHas('item_units', [
            'item_id' => $savedItem->id,
            'weight' => 0,
            'average_cost_per_gram' => 45,
            'is_default' => true,
            'barcode' => null,
        ]);

        $validGoldResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $branch->id,
                'inventory_classification' => Item::CLASSIFICATION_GOLD,
                'sale_mode' => Item::SALE_MODE_SINGLE,
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
            'sale_mode' => Item::SALE_MODE_SINGLE,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
        ]);
        $goldItem = Item::query()->where('inventory_classification', Item::CLASSIFICATION_GOLD)->latest('id')->firstOrFail();
        $this->assertDatabaseHas('item_units', [
            'item_id' => $goldItem->id,
            'weight' => 3.1,
            'is_default' => false,
        ]);
    }

    public function test_single_sale_item_cannot_be_saved_without_positive_weight(): void
    {
        $admin = $this->createAdminUser([
            'employee.items.add',
        ]);
        [$branch, $categoryId] = $this->createItemCatalogLookups();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $branch->id,
                'inventory_classification' => Item::CLASSIFICATION_SILVER,
                'sale_mode' => Item::SALE_MODE_SINGLE,
                'name_ar' => 'صنف بلا وزن',
                'category_id' => $categoryId,
                'cost_per_gram' => 45,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $this->assertDatabaseMissing('items', [
            'branch_id' => $branch->id,
        ]);
    }

    public function test_collectible_items_are_forced_to_18_carat_when_saved(): void
    {
        $admin = $this->createAdminUser([
            'employee.items.add',
        ]);
        [$branch, $categoryId, $goldCarat21Id] = $this->createItemCatalogLookups();
        $taxId = (int) DB::table('taxes')->value('id');
        $goldCarat18Id = DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => 'عيار 18', 'en' => '18K'], JSON_UNESCAPED_UNICODE),
            'label' => 'C18',
            'tax_id' => $taxId,
            'transform_factor' => '0.857143',
            'is_pure' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $branch->id,
                'inventory_classification' => Item::CLASSIFICATION_COLLECTIBLE,
                'sale_mode' => Item::SALE_MODE_REPEATABLE,
                'name_ar' => 'قطعة مقتنيات',
                'name_en' => 'Collectible Piece',
                'category_id' => $categoryId,
                'carats_id' => $goldCarat21Id,
                'cost_per_gram' => 180,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);

        $savedItem = Item::query()->where('inventory_classification', Item::CLASSIFICATION_COLLECTIBLE)->latest('id')->firstOrFail();

        $this->assertSame($goldCarat18Id, (int) $savedItem->gold_carat_id);
        $this->assertNull($savedItem->gold_carat_type_id);
    }

    public function test_repeatable_sale_item_can_be_saved_without_weight_and_uses_zero_weight_without_barcode(): void
    {
        $admin = $this->createAdminUser([
            'employee.items.add',
        ]);
        [$branch, $categoryId] = $this->createItemCatalogLookups();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $branch->id,
                'inventory_classification' => Item::CLASSIFICATION_SILVER,
                'sale_mode' => Item::SALE_MODE_REPEATABLE,
                'name_ar' => 'صنف متكرر',
                'category_id' => $categoryId,
                'cost_per_gram' => 45,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);

        $item = Item::query()->latest('id')->firstOrFail();

        $this->assertSame(Item::SALE_MODE_REPEATABLE, $item->sale_mode);
        $this->assertDatabaseHas('item_units', [
            'item_id' => $item->id,
            'is_default' => true,
            'weight' => 0,
            'barcode' => null,
        ]);
        $this->assertDatabaseMissing('item_units', [
            'item_id' => $item->id,
            'is_default' => false,
        ]);
    }

    public function test_branch_cannot_create_duplicate_item_name_for_reusable_item(): void
    {
        $admin = $this->createAdminUser([
            'employee.items.add',
        ]);
        [, $categoryId] = $this->createItemCatalogLookups();
        $branchId = $admin->branch_id;

        $firstResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $branchId,
                'inventory_classification' => Item::CLASSIFICATION_SILVER,
                'sale_mode' => Item::SALE_MODE_REPEATABLE,
                'name_ar' => 'سوار متكرر',
                'category_id' => $categoryId,
                'cost_per_gram' => 45,
            ], [
                'Accept' => 'application/json',
            ]);

        $firstResponse->assertOk();

        $duplicateResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $branchId,
                'inventory_classification' => Item::CLASSIFICATION_SILVER,
                'sale_mode' => Item::SALE_MODE_REPEATABLE,
                'name_ar' => '  سوار   متكرر  ',
                'category_id' => $categoryId,
                'cost_per_gram' => 47,
            ], [
                'Accept' => 'application/json',
            ]);

        $duplicateResponse->assertStatus(422);
        $duplicateResponse->assertJsonPath('status', false);
        $this->assertStringContainsString(
            'اسم الصنف مستخدم مسبقًا داخل هذا الفرع',
            implode("\n", $duplicateResponse->json('errors', []))
        );

        $this->assertSame(
            1,
            Item::query()
                ->where('branch_id', $branchId)
                ->get()
                ->filter(fn (Item $item) => $item->getTranslation('title', 'ar') === 'سوار متكرر')
                ->count()
        );
    }

    /**
     * @return array{0:Branch,1:int,2:int,3:int,4:int}
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

        $silverCaratId = DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => 'فضة 925', 'en' => 'Silver 925'], JSON_UNESCAPED_UNICODE),
            'label' => 'S925',
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

        return [$branch, $categoryId, $caratId, $caratTypeId, $silverCaratId];
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
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }
}
