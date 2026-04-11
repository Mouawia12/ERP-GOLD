<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Item;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Branches\BranchContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ItemBranchPublicationFeatureTest extends TestCase
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

    public function test_admin_can_create_item_once_and_publish_it_to_multiple_branches(): void
    {
        $ownerBranch = $this->createBranch('فرع المالك');
        $secondBranch = $this->createBranch('فرع العرض');
        $thirdBranch = $this->createBranch('فرع إضافي');
        $admin = $this->createAdminUser([
            'employee.items.add',
        ], $ownerBranch, [$ownerBranch, $secondBranch, $thirdBranch]);
        [$categoryId, $caratId, $caratTypeId] = $this->createCatalogLookups();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $ownerBranch->id,
                'published_branch_ids' => [$secondBranch->id, $thirdBranch->id],
                'branch_sale_prices' => [
                    $secondBranch->id => 480.55,
                ],
                'inventory_classification' => Item::CLASSIFICATION_GOLD,
                'sale_mode' => Item::SALE_MODE_SINGLE,
                'item_type' => $caratTypeId,
                'carats_id' => $caratId,
                'name_ar' => 'خاتم منشور',
                'name_en' => 'Published Ring',
                'category_id' => $categoryId,
                'weight' => 4.5,
                'cost_per_gram' => 350,
                'labor_cost_per_gram' => 15,
                'profit_margin_per_gram' => 20,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);

        $item = Item::query()->with('publishedBranches')->where('code', '000001')->firstOrFail();

        $this->assertSame($ownerBranch->id, $item->branch_id);
        $this->assertEqualsCanonicalizing(
            [$ownerBranch->id, $secondBranch->id, $thirdBranch->id],
            $item->publishedBranches->pluck('id')->all()
        );

        $this->assertDatabaseHas('branch_items', [
            'item_id' => $item->id,
            'branch_id' => $ownerBranch->id,
            'is_active' => true,
            'is_visible' => true,
        ]);
        $this->assertDatabaseHas('branch_items', [
            'item_id' => $item->id,
            'branch_id' => $secondBranch->id,
            'sale_price_per_gram' => 480.55,
            'is_active' => true,
            'is_visible' => true,
        ]);
        $this->assertDatabaseHas('branch_items', [
            'item_id' => $item->id,
            'branch_id' => $thirdBranch->id,
            'is_active' => true,
            'is_visible' => true,
        ]);
    }

    public function test_sales_search_returns_only_items_published_to_requested_branch_and_uses_branch_sale_price_override(): void
    {
        $ownerBranch = $this->createBranch('فرع المصدر');
        $publishedBranch = $this->createBranch('فرع البيع');
        $hiddenBranch = $this->createBranch('فرع غير منشور');
        $admin = $this->createAdminUser([
            'employee.items.add',
        ], $ownerBranch, [$ownerBranch, $publishedBranch]);
        [$categoryId, $caratId, $caratTypeId] = $this->createCatalogLookups();

        $this->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $ownerBranch->id,
                'published_branch_ids' => [$publishedBranch->id],
                'branch_sale_prices' => [
                    $publishedBranch->id => 512.75,
                ],
                'inventory_classification' => Item::CLASSIFICATION_GOLD,
                'sale_mode' => Item::SALE_MODE_SINGLE,
                'item_type' => $caratTypeId,
                'carats_id' => $caratId,
                'name_ar' => 'سلسال متعدد الفروع',
                'name_en' => 'Cross Branch Chain',
                'category_id' => $categoryId,
                'weight' => 2,
                'cost_per_gram' => 350,
                'labor_cost_per_gram' => 15,
                'profit_margin_per_gram' => 20,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $publishedBranchUser = $this->createRegularUser($publishedBranch, 'published-item-search@example.com');
        $hiddenBranchUser = $this->createRegularUser($hiddenBranch, 'hidden-item-search@example.com');

        $publishedResponse = $this
            ->actingAs($publishedBranchUser, 'admin-web')
            ->post(route('items.search', [], false), [
                'branch_id' => $publishedBranch->id,
                'code' => 'سلسال متعدد',
            ]);

        $publishedResponse->assertOk();
        $publishedResponse->assertJsonPath('status', true);
        $publishedResponse->assertJsonCount(1, 'data');
        $publishedResponse->assertJsonPath('data.0.gram_price', 512.75);
        $publishedResponse->assertJsonPath('data.0.quantity', 1);

        $hiddenResponse = $this
            ->actingAs($hiddenBranchUser, 'admin-web')
            ->post(route('items.search', [], false), [
                'branch_id' => $hiddenBranch->id,
                'code' => 'سلسال متعدد',
            ]);

        $hiddenResponse->assertOk();
        $hiddenResponse->assertJsonPath('status', true);
        $hiddenResponse->assertJsonCount(0, 'data');
    }

    public function test_sales_search_filters_items_by_selected_gold_carat_type(): void
    {
        $ownerBranch = $this->createBranch('فرع الأصل');
        $publishedBranch = $this->createBranch('فرع التصفية');
        $admin = $this->createAdminUser([
            'employee.items.add',
        ], $ownerBranch, [$ownerBranch, $publishedBranch]);
        [$categoryId, $caratId, $craftedTypeId] = $this->createCatalogLookups();
        $scrapTypeId = $this->createGoldCaratType('كسر', 'scrap');

        $craftedItem = $this->createPublishedGoldItem(
            $admin,
            $ownerBranch,
            $publishedBranch,
            $categoryId,
            $caratId,
            $craftedTypeId,
            'فلتر بيع مشغول',
            'Crafted Sales Filter'
        );

        $scrapItem = $this->createPublishedGoldItem(
            $admin,
            $ownerBranch,
            $publishedBranch,
            $categoryId,
            $caratId,
            $scrapTypeId,
            'فلتر بيع كسر',
            'Scrap Sales Filter'
        );

        $publishedBranchUser = $this->createRegularUser($publishedBranch, 'sales-type-filter@example.com');

        $craftedResponse = $this
            ->actingAs($publishedBranchUser, 'admin-web')
            ->post(route('items.search', [], false), [
                'branch_id' => $publishedBranch->id,
                'carat_type' => 'crafted',
                'code' => 'فلتر بيع',
            ]);

        $craftedResponse->assertOk();
        $craftedResponse->assertJsonPath('status', true);
        $craftedPayload = $craftedResponse->json('data');
        $this->assertCount(1, $craftedPayload);
        $this->assertStringContainsString('فلتر بيع مشغول', $craftedPayload[0]['item_name_without_break']);
        $this->assertStringContainsString($craftedItem->units->first()->barcode, $craftedPayload[0]['item_name_without_break']);

        $scrapResponse = $this
            ->actingAs($publishedBranchUser, 'admin-web')
            ->post(route('items.search', [], false), [
                'branch_id' => $publishedBranch->id,
                'carat_type' => 'scrap',
                'code' => 'فلتر بيع',
            ]);

        $scrapResponse->assertOk();
        $scrapResponse->assertJsonPath('status', true);
        $scrapPayload = $scrapResponse->json('data');
        $this->assertCount(1, $scrapPayload);
        $this->assertStringContainsString('فلتر بيع كسر', $scrapPayload[0]['item_name_without_break']);
        $this->assertStringContainsString($scrapItem->units->first()->barcode, $scrapPayload[0]['item_name_without_break']);
    }

    private function createBranch(string $name): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'phone' => '555444333',
            'status' => true,
        ]);
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function createCatalogLookups(): array
    {
        $taxId = DB::table('taxes')->insertGetId([
            'title' => 'VAT',
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('item_categories')->insertGetId([
            'title' => json_encode(['ar' => 'مجوهرات', 'en' => 'Jewelry'], JSON_UNESCAPED_UNICODE),
            'code' => 'CAT-PUB-1',
            'description' => json_encode(['ar' => 'تصنيف المجوهرات', 'en' => 'Jewelry category'], JSON_UNESCAPED_UNICODE),
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

        return [$categoryId, $caratId, $caratTypeId];
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = [], ?Branch $defaultBranch = null, array $accessibleBranches = []): User
    {
        $branch = $defaultBranch ?? $this->createBranch('فرع المدير');

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
            'email' => 'items-publication-admin-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        if ($accessibleBranches !== []) {
            app(BranchContextService::class)->syncUserBranches(
                $user,
                collect($accessibleBranches)->map(fn (Branch $branch) => $branch->id)->all(),
                $branch->id
            );
        }

        return $user->fresh();
    }

    private function createRegularUser(Branch $branch, string $email): User
    {
        return User::create([
            'name' => 'Branch User',
            'email' => $email,
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);
    }

    private function createGoldCaratType(string $title, string $key): int
    {
        return DB::table('gold_carat_types')->insertGetId([
            'title' => $title,
            'key' => $key,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPublishedGoldItem(
        User $admin,
        Branch $ownerBranch,
        Branch $publishedBranch,
        int $categoryId,
        int $caratId,
        int $caratTypeId,
        string $nameAr,
        string $nameEn
    ): Item {
        $this->actingAs($admin, 'admin-web')
            ->post(route('items.store', [], false), [
                'branch_id' => $ownerBranch->id,
                'published_branch_ids' => [$publishedBranch->id],
                'inventory_classification' => Item::CLASSIFICATION_GOLD,
                'sale_mode' => Item::SALE_MODE_SINGLE,
                'item_type' => $caratTypeId,
                'carats_id' => $caratId,
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'category_id' => $categoryId,
                'weight' => 2,
                'cost_per_gram' => 350,
                'labor_cost_per_gram' => 15,
                'profit_margin_per_gram' => 20,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        return Item::query()->latest('id')->with(['defaultUnit', 'units'])->firstOrFail();
    }
}
