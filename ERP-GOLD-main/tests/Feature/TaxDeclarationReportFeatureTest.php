<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TaxDeclarationReportFeatureTest extends TestCase
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

    public function test_tax_declaration_search_page_exposes_common_filters(): void
    {
        $admin = $this->createAdminUser();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('tax.declaration.index', [], false));

        $response->assertOk();
        $response->assertSee('name="from_time"', false);
        $response->assertSee('name="to_time"', false);
        $response->assertSee('name="invoice_number"', false);
        $response->assertSee('name="branch_id"', false);
        $response->assertSee('name="user_id"', false);
    }

    public function test_tax_declaration_respects_branch_user_time_and_invoice_number_filters(): void
    {
        $admin = $this->createAdminUser();
        $otherBranch = Branch::create([
            'name' => ['ar' => 'فرع بديل', 'en' => 'Other Branch'],
            'phone' => '555888222',
            'status' => true,
        ]);

        $otherUser = User::create([
            'name' => 'Other Tax User',
            'email' => 'tax-report-user-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $admin->branch_id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $branchUser = User::create([
            'name' => 'Other Branch Tax User',
            'email' => 'tax-report-branch-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $otherBranch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'عميل ضريبة',
            'phone' => '0557333333',
            'type' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplierId = DB::table('customers')->insertGetId([
            'name' => 'مورد ضريبة',
            'phone' => '0557444444',
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        [$tax15Id] = $this->prepareTaxDimensions();

        $matchingSaleId = $this->insertInvoice([
            'bill_number' => 'TX-FILTER-MATCH',
            'type' => 'sale',
            'date' => '2026-03-22',
            'time' => '15:20:00',
            'customer_id' => $customerId,
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $matchingSaleId,
            'unit_tax_id' => $tax15Id,
            'line_total' => 1111,
            'line_tax' => 166.65,
            'net_total' => 1277.65,
            'date' => '2026-03-22',
        ]);

        $matchingPurchaseId = $this->insertInvoice([
            'bill_number' => 'TX-FILTER-MATCH',
            'type' => 'purchase',
            'date' => '2026-03-22',
            'time' => '15:45:00',
            'customer_id' => $supplierId,
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $matchingPurchaseId,
            'unit_tax_id' => $tax15Id,
            'line_total' => 444,
            'line_tax' => 66.60,
            'net_total' => 510.60,
            'date' => '2026-03-22',
        ]);

        $otherUserSaleId = $this->insertInvoice([
            'bill_number' => 'TX-FILTER-MATCH',
            'type' => 'sale',
            'date' => '2026-03-22',
            'time' => '15:25:00',
            'customer_id' => $customerId,
            'branch_id' => $admin->branch_id,
            'user_id' => $otherUser->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $otherUserSaleId,
            'unit_tax_id' => $tax15Id,
            'line_total' => 999,
            'line_tax' => 149.85,
            'net_total' => 1148.85,
            'date' => '2026-03-22',
        ]);

        $otherBranchPurchaseId = $this->insertInvoice([
            'bill_number' => 'TX-FILTER-MATCH',
            'type' => 'purchase',
            'date' => '2026-03-22',
            'time' => '15:30:00',
            'customer_id' => $supplierId,
            'branch_id' => $otherBranch->id,
            'user_id' => $branchUser->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $otherBranchPurchaseId,
            'unit_tax_id' => $tax15Id,
            'line_total' => 555,
            'line_tax' => 83.25,
            'net_total' => 638.25,
            'date' => '2026-03-22',
        ]);

        $otherTimeSaleId = $this->insertInvoice([
            'bill_number' => 'TX-FILTER-MATCH',
            'type' => 'sale',
            'date' => '2026-03-22',
            'time' => '14:10:00',
            'customer_id' => $customerId,
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
        ]);

        $this->insertInvoiceDetail([
            'invoice_id' => $otherTimeSaleId,
            'unit_tax_id' => $tax15Id,
            'line_total' => 222,
            'line_tax' => 33.30,
            'net_total' => 255.30,
            'date' => '2026-03-22',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('tax.declaration.search', [], false), [
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'from_time' => '15:00',
                'to_time' => '16:00',
                'branch_id' => $admin->branch_id,
                'user_id' => $admin->id,
                'invoice_number' => 'TX-FILTER-MATCH',
            ]);

        $response->assertOk();
        $response->assertSee('166.65');
        $response->assertSee('1,111.00');
        $response->assertSee('66.60');
        $response->assertSee('444.00');
        $response->assertSee('100.05');
        $response->assertDontSee('149.85');
        $response->assertDontSee('83.25');
        $response->assertDontSee('33.30');
        $response->assertDontSee('999.00');
        $response->assertDontSee('555.00');
        $response->assertDontSee('222.00');
    }

    private function createAdminUser(): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع الضرائب', 'en' => 'Tax Branch'],
            'phone' => '111222333',
            'status' => true,
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مدير الضرائب', 'en' => 'Tax Admin'],
            'guard_name' => 'admin-web',
        ]);

        return tap(User::create([
            'name' => 'Tax Reports Admin',
            'email' => 'tax-report-admin-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]), function (User $user) use ($role) {
            $user->assignRole($role);
        });
    }

    /**
     * @return array<int, int>
     */
    private function prepareTaxDimensions(): array
    {
        $tax15Id = DB::table('taxes')->insertGetId([
            'title' => 'VAT 15',
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $taxZeroId = DB::table('taxes')->insertGetId([
            'title' => 'VAT 0',
            'rate' => 0,
            'zatca_code' => 'Z',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tax15Id, $taxZeroId];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertInvoice(array $attributes): int
    {
        return DB::table('invoices')->insertGetId(array_merge([
            'bill_number' => null,
            'serial' => null,
            'financial_year' => null,
            'branch_id' => null,
            'warehouse_id' => null,
            'customer_id' => null,
            'bill_client_phone' => null,
            'bill_client_name' => null,
            'parent_id' => null,
            'type' => 'sale',
            'account_id' => null,
            'sale_type' => 'simplified',
            'purchase_type' => null,
            'purchase_carat_type_id' => null,
            'supplier_bill_number' => null,
            'notes' => null,
            'payment_type' => 'cash',
            'date' => '2026-03-22',
            'time' => '00:00:00',
            'lines_total' => 0,
            'discount_total' => 0,
            'lines_total_after_discount' => 0,
            'taxes_total' => 0,
            'net_total' => 0,
            'user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertInvoiceDetail(array $attributes): int
    {
        return DB::table('invoice_details')->insertGetId(array_merge([
            'invoice_id' => null,
            'warehouse_id' => null,
            'parent_id' => null,
            'item_id' => null,
            'no_metal' => 0,
            'no_metal_type' => 'fixed',
            'unit_id' => null,
            'gold_carat_id' => null,
            'gold_carat_type_id' => null,
            'date' => '2026-03-22',
            'in_quantity' => 0,
            'out_quantity' => 0,
            'in_weight' => 0,
            'out_weight' => 0,
            'unit_cost' => 0,
            'labor_cost_per_gram' => 0,
            'unit_price' => 0,
            'unit_discount' => 0,
            'unit_tax' => 0,
            'unit_tax_rate' => 15,
            'unit_tax_id' => null,
            'line_total' => 0,
            'line_discount' => 0,
            'line_tax' => 0,
            'net_total' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
