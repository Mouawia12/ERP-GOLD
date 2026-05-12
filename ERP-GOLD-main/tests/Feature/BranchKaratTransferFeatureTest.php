<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchKaratTransfer;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\JournalEntryDocument;
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

class BranchKaratTransferFeatureTest extends TestCase
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

    public function test_transfer_creates_invoices_and_journal_entries_reflecting_stock_and_trial_balance(): void
    {
        $context = $this->prepareContext();
        $admin = $this->createAdminUser([
            'employee.branch_karat_transfers.add',
            'employee.branch_karat_transfers.show',
        ], $context['from_branch_id']);

        $payload = [
            'bill_date' => '2026-05-12',
            'from_branch_id' => $context['from_branch_id'],
            'to_branch_id' => $context['to_branch_id'],
            'gold_carat_type_id' => $context['crafted_type_id'],
            'account_id' => $context['transfer_account_id'],
            'lines' => [
                [
                    'from_carat_id' => $context['carat_24_id'],
                    'to_carat_id' => $context['carat_21_id'],
                    'from_weight' => 100,
                    'to_weight' => 114.28,
                    'unit_cost' => 200,
                    'line_notes' => 'تحويل تجريبي',
                ],
            ],
        ];

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->postJson(route('branch_karat_transfers.store', [], false), $payload);

        $response->assertOk();
        $response->assertJson(['status' => true]);

        $transfer = BranchKaratTransfer::firstOrFail();
        $this->assertNotNull($transfer->out_invoice_id);
        $this->assertNotNull($transfer->in_invoice_id);
        $this->assertSame(20000.0, (float) $transfer->total_value);

        $outInvoice = Invoice::findOrFail($transfer->out_invoice_id);
        $inInvoice = Invoice::findOrFail($transfer->in_invoice_id);
        $this->assertSame('branch_karat_transfer_out', $outInvoice->type);
        $this->assertSame('branch_karat_transfer_in', $inInvoice->type);
        $this->assertSame($context['from_branch_id'], $outInvoice->branch_id);
        $this->assertSame($context['to_branch_id'], $inInvoice->branch_id);

        // Stock movement: source loses 100g of 24K, destination gains 114.28g of 21K.
        $outDetail = InvoiceDetail::where('invoice_id', $outInvoice->id)->firstOrFail();
        $this->assertEquals(100, (float) $outDetail->out_weight);
        $this->assertEquals(0, (float) $outDetail->in_weight);
        $this->assertSame($context['carat_24_id'], (int) $outDetail->gold_carat_id);

        $inDetail = InvoiceDetail::where('invoice_id', $inInvoice->id)->firstOrFail();
        $this->assertEquals(114.28, (float) $inDetail->in_weight);
        $this->assertEquals(0, (float) $inDetail->out_weight);
        $this->assertSame($context['carat_21_id'], (int) $inDetail->gold_carat_id);

        // Trial balance: source stock account credited, destination stock account debited,
        // transfer (contra) account is balanced (debit on source side, credit on destination side).
        $outDocs = JournalEntryDocument::query()
            ->whereHas('journal_entry', fn ($q) => $q->where('branch_id', $context['from_branch_id']))
            ->get();
        $sourceStockCredit = $outDocs->where('account_id', $context['from_stock_account_id'])->sum('credit');
        $sourceContraDebit = $outDocs->where('account_id', $context['transfer_account_id'])->sum('debit');
        $this->assertEquals(20000.0, (float) $sourceStockCredit);
        $this->assertEquals(20000.0, (float) $sourceContraDebit);

        $inDocs = JournalEntryDocument::query()
            ->whereHas('journal_entry', fn ($q) => $q->where('branch_id', $context['to_branch_id']))
            ->get();
        $destStockDebit = $inDocs->where('account_id', $context['to_stock_account_id'])->sum('debit');
        $destContraCredit = $inDocs->where('account_id', $context['transfer_account_id'])->sum('credit');
        $this->assertEquals(20000.0, (float) $destStockDebit);
        $this->assertEquals(20000.0, (float) $destContraCredit);
    }

    public function test_transfer_destroy_removes_invoices_details_and_journal_entries(): void
    {
        $context = $this->prepareContext();
        $admin = $this->createAdminUser([
            'employee.branch_karat_transfers.add',
            'employee.branch_karat_transfers.show',
            'employee.branch_karat_transfers.delete',
        ], $context['from_branch_id']);

        $this
            ->actingAs($admin, 'admin-web')
            ->postJson(route('branch_karat_transfers.store', [], false), [
                'bill_date' => '2026-05-12',
                'from_branch_id' => $context['from_branch_id'],
                'to_branch_id' => $context['to_branch_id'],
                'gold_carat_type_id' => $context['crafted_type_id'],
                'account_id' => $context['transfer_account_id'],
                'lines' => [
                    [
                        'from_carat_id' => $context['carat_24_id'],
                        'to_carat_id' => $context['carat_21_id'],
                        'from_weight' => 50,
                        'to_weight' => 57.14,
                        'unit_cost' => 200,
                    ],
                ],
            ])->assertOk();

        $transfer = BranchKaratTransfer::firstOrFail();

        $this
            ->actingAs($admin, 'admin-web')
            ->delete(route('branch_karat_transfers.destroy', $transfer->id, false))
            ->assertRedirect();

        $this->assertDatabaseCount('branch_karat_transfers', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_details', 0);
        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseCount('journal_entry_documents', 0);
    }

    private function prepareContext(): array
    {
        $this->createFinancialYear();
        $taxId = $this->createTax();
        $craftedTypeId = $this->createGoldCaratType('مصنع', 'crafted');
        $this->createGoldCaratType('كسر', 'scrap');
        $this->createGoldCaratType('سبيكة', 'pure');
        $carat24Id = $this->createGoldCarat('عيار 24', '24', $taxId, 1.1428);
        $carat21Id = $this->createGoldCarat('عيار 21', '21', $taxId, 1);

        $fromBranchId = DB::table('branches')->insertGetId([
            'name' => json_encode(['ar' => 'فرع المرقب', 'en' => 'Almurqib Branch'], JSON_UNESCAPED_UNICODE),
            'phone' => '123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $toBranchId = DB::table('branches')->insertGetId([
            'name' => json_encode(['ar' => 'فرع المرقب 2', 'en' => 'Almurqib 2 Branch'], JSON_UNESCAPED_UNICODE),
            'phone' => '456',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$fromBranchId, $toBranchId] as $branchId) {
            DB::table('warehouses')->insert([
                'name' => 'مخزن',
                'code' => 'WH-' . $branchId,
                'branch_id' => $branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $fromStockAccountId = $this->createAccount('مخزون مصدر', '1100');
        $toStockAccountId = $this->createAccount('مخزون وجهة', '1200');
        $transferAccountId = $this->createAccount('تحويلات الفروع', '1900');

        DB::table('account_settings')->insert([
            'branch_id' => $fromBranchId,
            'stock_account_crafted' => $fromStockAccountId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('account_settings')->insert([
            'branch_id' => $toBranchId,
            'stock_account_crafted' => $toStockAccountId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'from_branch_id' => $fromBranchId,
            'to_branch_id' => $toBranchId,
            'crafted_type_id' => $craftedTypeId,
            'carat_24_id' => $carat24Id,
            'carat_21_id' => $carat21Id,
            'from_stock_account_id' => $fromStockAccountId,
            'to_stock_account_id' => $toStockAccountId,
            'transfer_account_id' => $transferAccountId,
        ];
    }

    private function createAdminUser(array $permissions, int $branchId): User
    {
        $role = Role::create([
            'name' => ['ar' => 'مدير', 'en' => 'Admin'],
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
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branchId,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createFinancialYear(): void
    {
        DB::table('financial_years')->insert([
            'description' => 'FY 2026',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'is_closed' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTax(): int
    {
        return DB::table('taxes')->insertGetId([
            'title' => 'VAT',
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createGoldCaratType(string $title, string $key): int
    {
        return DB::table('gold_carat_types')->insertGetId([
            'title' => json_encode(['ar' => $title, 'en' => $title], JSON_UNESCAPED_UNICODE),
            'key' => $key,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createGoldCarat(string $title, string $label, int $taxId, float $transformFactor): int
    {
        return DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => $title, 'en' => $title], JSON_UNESCAPED_UNICODE),
            'label' => $label,
            'tax_id' => $taxId,
            'transform_factor' => (string) $transformFactor,
            'is_pure' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAccount(string $name, string $code): int
    {
        return DB::table('accounts')->insertGetId([
            'name' => json_encode(['ar' => $name, 'en' => $name], JSON_UNESCAPED_UNICODE),
            'code' => $code,
            'old_id' => null,
            'level' => '1',
            'parent_account_id' => null,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
