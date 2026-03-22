<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class ItemListReportFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            LaravelLocalizationRedirectFilter::class,
            LocaleSessionRedirect::class,
        ]);
    }

    public function test_item_list_search_page_exposes_report_filters_for_authenticated_user(): void
    {
        $branch = $this->createBranch('فرع التقرير');
        $user = $this->createUser($branch, 'items-report-user@example.com');
        $this->createCatalogLookups();

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('reports.items.list', [], false));

        $response->assertOk();
        $response->assertSee('name="branch_id"', false);
        $response->assertSee('name="inventory_classification"', false);
        $response->assertSee('name="carat"', false);
        $response->assertSee('name="category"', false);
        $response->assertSee('name="code"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="fcode"', false);
        $response->assertSee('name="tcode"', false);
        $response->assertSee('name="status"', false);
    }

    public function test_item_list_report_respects_branch_status_category_carat_name_and_code_range_filters(): void
    {
        $branch = $this->createBranch('فرع الذهب');
        $otherBranch = $this->createBranch('فرع آخر');
        $user = $this->createUser($branch, 'items-filter-user@example.com');
        [$categoryId, $otherCategoryId, $caratId, $otherCaratId] = $this->createCatalogLookups();

        $this->insertItem([
            'branch_id' => $otherBranch->id,
            'published_branch_ids' => [$branch->id],
            'inventory_classification' => 'gold',
            'category_id' => $categoryId,
            'gold_carat_id' => $caratId,
            'code' => '000101',
            'title' => ['ar' => 'خاتم ذهب', 'en' => 'Gold Ring'],
            'status' => true,
        ]);

        $this->insertItem([
            'branch_id' => $branch->id,
            'inventory_classification' => 'silver',
            'category_id' => $categoryId,
            'gold_carat_id' => $caratId,
            'code' => '000102',
            'title' => ['ar' => 'سلسال فضي', 'en' => 'Silver Chain'],
            'status' => true,
        ]);

        $this->insertItem([
            'branch_id' => $otherBranch->id,
            'inventory_classification' => 'gold',
            'category_id' => $otherCategoryId,
            'gold_carat_id' => $otherCaratId,
            'code' => '000201',
            'title' => ['ar' => 'سوار فرع آخر', 'en' => 'Other Branch Bracelet'],
            'status' => true,
        ]);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->post(route('reports.items.list.search', [], false), [
                'branch_id' => $branch->id,
                'inventory_classification' => 'gold',
                'carat' => $caratId,
                'category' => $categoryId,
                'name' => 'خاتم',
                'fcode' => '000100',
                'tcode' => '000150',
                'status' => '1',
            ]);

        $response->assertOk();
        $response->assertSee('خاتم ذهب');
        $response->assertSee('فرع الذهب');
        $response->assertSee('نشط');
        $response->assertSee('ذهب');
        $response->assertDontSee('سلسال فضي');
        $response->assertDontSee('سوار فرع آخر');
        $response->assertDontSee('فرع آخر');
    }

    private function createBranch(string $name): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'phone' => '123456789',
            'status' => true,
        ]);
    }

    private function createUser(Branch $branch, string $email): User
    {
        return User::create([
            'name' => 'Item Report User',
            'email' => $email,
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);
    }

    /**
     * @return array{0:int,1:int,2:int,3:int}
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

        $otherCaratId = DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => 'عيار 18', 'en' => '18K'], JSON_UNESCAPED_UNICODE),
            'label' => 'C18',
            'tax_id' => $taxId,
            'transform_factor' => '0.75',
            'is_pure' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('item_categories')->insertGetId([
            'title' => json_encode(['ar' => 'خواتم', 'en' => 'Rings'], JSON_UNESCAPED_UNICODE),
            'code' => 'CAT-1',
            'description' => json_encode(['ar' => 'قسم الخواتم', 'en' => 'Rings category'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherCategoryId = DB::table('item_categories')->insertGetId([
            'title' => json_encode(['ar' => 'أساور', 'en' => 'Bracelets'], JSON_UNESCAPED_UNICODE),
            'code' => 'CAT-2',
            'description' => json_encode(['ar' => 'قسم الأساور', 'en' => 'Bracelets category'], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$categoryId, $otherCategoryId, $caratId, $otherCaratId];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertItem(array $attributes): void
    {
        $title = $attributes['title'];
        $publishedBranchIds = $attributes['published_branch_ids'] ?? [$attributes['branch_id']];
        unset($attributes['title']);
        unset($attributes['published_branch_ids']);

        $itemId = DB::table('items')->insertGetId(array_merge([
            'description' => null,
            'inventory_classification' => 'gold',
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

        foreach ($publishedBranchIds as $branchId) {
            DB::table('branch_items')->insert([
                'branch_id' => $branchId,
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
}
