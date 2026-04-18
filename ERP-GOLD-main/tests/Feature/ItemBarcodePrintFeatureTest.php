<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemUnit;
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

class ItemBarcodePrintFeatureTest extends TestCase
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

    public function test_barcode_table_exposes_paper_profiles_for_printing(): void
    {
        $admin = $this->createAdminUser([
            'employee.items.show',
        ]);
        $item = $this->createItemWithUnit(Item::CLASSIFICATION_GOLD);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('items.barcode_table', $item->id, false));

        $response->assertOk();
        $response->assertSee('barcode_paper_profile', false);
        $response->assertSee('A4 - 3 x 8');
        $response->assertSee('A5 - 2 x 5');
        $response->assertSee('Label - 50 x 25');
    }

    public function test_print_barcodes_respects_selected_paper_profile(): void
    {
        $admin = $this->createAdminUser();
        $item = $this->createItemWithUnit(Item::CLASSIFICATION_GOLD);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('items.print_barcodes', $item->id, false).'?paper_profile=a5_2x5');

        $response->assertOk();
        $response->assertSee('data-paper-profile="a5_2x5"', false);
        $response->assertSee('Barcode Profile: A5 - 2 x 5');
        $response->assertSee('grid-template-columns: repeat(2, minmax(68mm, 1fr)); gap: 4mm;', false);
        $response->assertSee($item->title);
        $response->assertSee($item->units->first()->barcode);
    }

    public function test_print_single_barcode_shows_classification_for_non_gold_item(): void
    {
        $admin = $this->createAdminUser();
        $item = $this->createItemWithUnit(Item::CLASSIFICATION_SILVER);
        $unit = $item->units()->first();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('items.units.print_barcode', $unit->id, false).'?paper_profile=label_50x25');

        $response->assertOk();
        $response->assertSee('data-paper-profile="label_50x25"', false);
        $response->assertSee('Barcode Profile: Label - 50 x 25');
        $response->assertSee('التصنيف: فضة');
        $response->assertSee($unit->barcode);
    }

    public function test_lost_barcode_page_renders_search_form_and_profiles(): void
    {
        $admin = $this->createAdminUser([
            'employee.items.show',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('items.lost_barcodes', [], false));

        $response->assertOk();
        $response->assertSee('الباركود المفقود');
        $response->assertSee('name="weight"', false);
        $response->assertSee('lost_barcode_paper_profile', false);
        $response->assertSee('lost_barcode_results_filter', false);
        $response->assertSee('Label - 50 x 25');
    }

    public function test_lost_barcode_search_returns_matching_available_unit_by_weight(): void
    {
        $admin = $this->createAdminUser([
            'employee.items.show',
        ]);
        $item = $this->createItemWithUnit(Item::CLASSIFICATION_GOLD, Item::SALE_MODE_SINGLE, $admin->branch);
        $unit = $item->units()->firstOrFail();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.lost_barcodes.search', [], false), [
                'branch_id' => $item->branch_id,
                'weight' => 5.25,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.unit_id', $unit->id);
        $response->assertJsonPath('data.0.barcode', $unit->barcode);
        $response->assertJsonPath('data.0.item_code', $item->code);
        $response->assertJsonPath('data.0.print_url', route('items.units.print_barcode', $unit->id, false));
    }

    public function test_lost_barcode_search_returns_legacy_default_unit_when_it_has_barcode(): void
    {
        $admin = $this->createAdminUser([
            'employee.items.show',
        ]);
        $item = $this->createItemWithUnit(Item::CLASSIFICATION_GOLD, Item::SALE_MODE_SINGLE, $admin->branch);
        $item->units()->delete();

        $defaultUnit = $item->defaultUnit()->firstOrFail();
        $defaultUnit->update([
            'barcode' => 'LEGACY-900001-0525',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.lost_barcodes.search', [], false), [
                'branch_id' => $item->branch_id,
                'weight' => 5.25,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.unit_id', $defaultUnit->id);
        $response->assertJsonPath('data.0.barcode', 'LEGACY-900001-0525');
        $response->assertJsonPath('data.0.item_code', $item->code);
        $response->assertJsonPath('data.0.print_url', route('items.units.print_barcode', $defaultUnit->id, false));
    }

    public function test_store_barcodes_requires_positive_weight_values(): void
    {
        $admin = $this->createAdminUser([
            'employee.items.show',
        ]);
        $item = $this->createItemWithUnit(Item::CLASSIFICATION_GOLD);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store_barcodes', $item->id, false), [
                'weight' => [0, ''],
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $this->assertStringContainsString(
            'وزن الباركود يجب أن يكون أكبر من صفر',
            implode("\n", $response->json('errors', []))
        );
    }

    public function test_repeatable_item_rejects_barcode_creation(): void
    {
        $admin = $this->createAdminUser([
            'employee.items.show',
        ]);
        $item = $this->createItemWithUnit(Item::CLASSIFICATION_SILVER, Item::SALE_MODE_REPEATABLE);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('items.store_barcodes', $item->id, false), [
                'weight' => [2.5],
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $this->assertStringContainsString(
            'الباركودات متاحة فقط للأصناف التي تباع مرة واحدة',
            implode("\n", $response->json('errors', []))
        );
    }

    private function createItemWithUnit(string $classification, string $saleMode = Item::SALE_MODE_SINGLE, ?Branch $branch = null): Item
    {
        $branch = $branch ?: Branch::create([
            'name' => ['ar' => 'فرع الباركود', 'en' => 'Barcode Branch'],
            'phone' => '444555666',
            'status' => true,
        ]);

        $categoryId = DB::table('item_categories')->insertGetId([
            'title' => json_encode(['ar' => 'إكسسوارات', 'en' => 'Accessories'], JSON_UNESCAPED_UNICODE),
            'code' => 'BAR-CAT',
            'description' => json_encode(['ar' => 'تصنيف الباركود', 'en' => 'Barcode category'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $goldCaratId = null;
        $goldCaratTypeId = null;

        if ($classification === Item::CLASSIFICATION_GOLD) {
            $taxId = DB::table('taxes')->insertGetId([
                'title' => 'VAT',
                'rate' => 15,
                'zatca_code' => 'S',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $goldCaratId = DB::table('gold_carats')->insertGetId([
                'title' => json_encode(['ar' => 'عيار 21', 'en' => '21K'], JSON_UNESCAPED_UNICODE),
                'label' => 'C21',
                'tax_id' => $taxId,
                'transform_factor' => '1',
                'is_pure' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $goldCaratTypeId = DB::table('gold_carat_types')->insertGetId([
                'title' => 'مشغول',
                'key' => 'crafted',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $item = Item::create([
            'title' => ['ar' => $classification === Item::CLASSIFICATION_GOLD ? 'خاتم باركود' : 'سوار فضي باركود', 'en' => 'Barcode Item'],
            'description' => ['ar' => 'صنف للطباعة', 'en' => 'Printable item'],
            'code' => $classification === Item::CLASSIFICATION_GOLD ? '900001' : '900002',
            'branch_id' => $branch->id,
            'inventory_classification' => $classification,
            'sale_mode' => $saleMode,
            'category_id' => $categoryId,
            'gold_carat_id' => $goldCaratId,
            'gold_carat_type_id' => $goldCaratTypeId,
            'labor_cost_per_gram' => 5,
            'profit_margin_per_gram' => 3,
            'status' => true,
        ]);

        DB::table('branch_items')->insert([
            'branch_id' => $branch->id,
            'item_id' => $item->id,
            'is_active' => true,
            'is_visible' => true,
            'sale_price_per_gram' => null,
            'published_by_user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $item->defaultUnit()->create([
            'weight' => $saleMode === Item::SALE_MODE_SINGLE ? 5.25 : 0,
            'initial_cost_per_gram' => 100,
            'average_cost_per_gram' => 100,
            'current_cost_per_gram' => 100,
            'is_default' => true,
        ]);

        if ($saleMode === Item::SALE_MODE_SINGLE) {
            $item->units()->create([
                'weight' => 5.25,
            ]);
        }

        return $item->fresh(['units', 'branch', 'goldCarat']);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'الفرع الرئيسي', 'en' => 'Main Branch'],
            'phone' => '111111111',
            'status' => true,
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مدير الباركود', 'en' => 'Barcode Admin'],
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
            'name' => 'Barcode Admin',
            'email' => 'barcode-admin-'.uniqid().'@example.com',
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
