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

    private function createItemWithUnit(string $classification): Item
    {
        $branch = Branch::create([
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
            'category_id' => $categoryId,
            'gold_carat_id' => $goldCaratId,
            'gold_carat_type_id' => $goldCaratTypeId,
            'labor_cost_per_gram' => 5,
            'profit_margin_per_gram' => 3,
            'status' => true,
        ]);

        $item->defaultUnit()->create([
            'weight' => 5.25,
            'initial_cost_per_gram' => 100,
            'average_cost_per_gram' => 100,
            'current_cost_per_gram' => 100,
            'is_default' => true,
        ]);

        $item->units()->create([
            'weight' => 5.25,
        ]);

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
