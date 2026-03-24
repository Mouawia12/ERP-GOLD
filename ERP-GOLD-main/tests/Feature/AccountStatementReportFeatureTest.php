<?php

namespace Tests\Feature;

use App\Models\Branch;
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

class AccountStatementReportFeatureTest extends TestCase
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

    public function test_account_statement_search_page_exposes_common_filters(): void
    {
        $admin = $this->createAdminUser([
            'employee.accounting_reports.show',
        ]);

        $this->createAccount();

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('account_statement.index', [], false));

        $response->assertOk();
        $response->assertSee('name="from_time"', false);
        $response->assertSee('name="to_time"', false);
        $response->assertSee('name="invoice_number"', false);
        $response->assertSee('name="branch_id"', false);
        $response->assertSee('name="user_id"', false);
        $response->assertSee('name="source_type"', false);
    }

    public function test_account_statement_respects_branch_user_time_reference_and_source_filters(): void
    {
        $admin = $this->createAdminUser([
            'employee.accounting_reports.show',
        ]);

        $otherBranch = Branch::create([
            'name' => ['ar' => 'فرع محاسبي آخر', 'en' => 'Other Accounting Branch'],
            'phone' => '555444333',
            'status' => true,
        ]);

        $otherUser = User::create([
            'name' => 'Other Accounting User',
            'email' => 'accounting-user-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $admin->branch_id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $branchUser = User::create([
            'name' => 'Other Accounting Branch User',
            'email' => 'accounting-branch-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $otherBranch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $financialYearId = $this->createFinancialYear();
        $accountId = $this->createAccount();

        $matchingInvoiceId = $this->insertInvoice([
            'bill_number' => 'INV-MATCH-001',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
            'type' => 'sale',
            'notes' => 'فاتورة مطابقة',
            'date' => '2026-03-22',
            'time' => '15:20:00',
        ]);

        $matchingJournalId = $this->insertJournalEntry([
            'serial' => 'J-1-00001',
            'journal_date' => '2026-03-22',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
            'journalable_type' => 'App\\Models\\Invoice',
            'journalable_id' => $matchingInvoiceId,
        ]);

        $this->insertJournalEntryDocument([
            'journal_id' => $matchingJournalId,
            'account_id' => $accountId,
            'document_date' => '2026-03-22',
            'debit' => 500,
            'credit' => 0,
            'notes' => 'حركة مطابقة',
        ]);

        $otherUserInvoiceId = $this->insertInvoice([
            'bill_number' => 'INV-MATCH-001',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
            'user_id' => $otherUser->id,
            'type' => 'sale',
            'notes' => 'فاتورة مستخدم آخر',
            'date' => '2026-03-22',
            'time' => '15:25:00',
        ]);

        $otherUserJournalId = $this->insertJournalEntry([
            'serial' => 'J-1-00002',
            'journal_date' => '2026-03-22',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
            'journalable_type' => 'App\\Models\\Invoice',
            'journalable_id' => $otherUserInvoiceId,
        ]);

        $this->insertJournalEntryDocument([
            'journal_id' => $otherUserJournalId,
            'account_id' => $accountId,
            'document_date' => '2026-03-22',
            'debit' => 650,
            'credit' => 0,
            'notes' => 'حركة مستخدم آخر',
        ]);

        $otherBranchInvoiceId = $this->insertInvoice([
            'bill_number' => 'INV-MATCH-001',
            'financial_year' => $financialYearId,
            'branch_id' => $otherBranch->id,
            'user_id' => $branchUser->id,
            'type' => 'sale',
            'notes' => 'فاتورة فرع آخر',
            'date' => '2026-03-22',
            'time' => '15:30:00',
        ]);

        $otherBranchJournalId = $this->insertJournalEntry([
            'serial' => 'J-2-00001',
            'journal_date' => '2026-03-22',
            'financial_year' => $financialYearId,
            'branch_id' => $otherBranch->id,
            'journalable_type' => 'App\\Models\\Invoice',
            'journalable_id' => $otherBranchInvoiceId,
        ]);

        $this->insertJournalEntryDocument([
            'journal_id' => $otherBranchJournalId,
            'account_id' => $accountId,
            'document_date' => '2026-03-22',
            'debit' => 700,
            'credit' => 0,
            'notes' => 'حركة فرع آخر',
        ]);

        $otherTimeInvoiceId = $this->insertInvoice([
            'bill_number' => 'INV-MATCH-001',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
            'type' => 'sale',
            'notes' => 'فاتورة وقت آخر',
            'date' => '2026-03-22',
            'time' => '14:10:00',
        ]);

        $otherTimeJournalId = $this->insertJournalEntry([
            'serial' => 'J-1-00003',
            'journal_date' => '2026-03-22',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
            'journalable_type' => 'App\\Models\\Invoice',
            'journalable_id' => $otherTimeInvoiceId,
        ]);

        $this->insertJournalEntryDocument([
            'journal_id' => $otherTimeJournalId,
            'account_id' => $accountId,
            'document_date' => '2026-03-22',
            'debit' => 720,
            'credit' => 0,
            'notes' => 'حركة وقت آخر',
        ]);

        $manualJournalId = $this->insertJournalEntry([
            'serial' => 'MJ-1-00001',
            'journal_date' => '2026-03-22',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
            'notes' => 'قيد يدوي',
            'journalable_type' => null,
            'journalable_id' => null,
        ]);

        $this->insertJournalEntryDocument([
            'journal_id' => $manualJournalId,
            'account_id' => $accountId,
            'document_date' => '2026-03-22',
            'debit' => 1200,
            'credit' => 0,
            'notes' => 'قيد يدوي مستبعد',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('account_statement.search', [], false), [
                'account_id' => $accountId,
                'date_from' => '2026-03-22',
                'date_to' => '2026-03-22',
                'from_time' => '15:00',
                'to_time' => '16:00',
                'branch_id' => $admin->branch_id,
                'user_id' => $admin->id,
                'invoice_number' => 'INV-MATCH-001',
                'source_type' => 'invoice',
            ]);

        $response->assertOk();
        $response->assertSee('INV-MATCH-001');
        $response->assertSee('15:20:00');
        $response->assertSee('فاتورة');
        $response->assertSee('فاتورة مطابقة');
        $response->assertSee('500.00');
        $response->assertSee($admin->name);
        $response->assertSee($admin->branch->name);
        $response->assertDontSee('فاتورة مستخدم آخر');
        $response->assertDontSee('فاتورة فرع آخر');
        $response->assertDontSee('فاتورة وقت آخر');
        $response->assertDontSee('قيد يدوي');
        $response->assertDontSee('650.00');
        $response->assertDontSee('700.00');
        $response->assertDontSee('720.00');
        $response->assertDontSee('1,200.00');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'فرع الحسابات', 'en' => 'Accounting Branch'],
            'phone' => '111222333',
            'status' => true,
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مدير الحسابات', 'en' => 'Accounting Admin'],
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
            'name' => 'Accounting Reports Admin',
            'email' => 'accounting-report-admin-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createFinancialYear(): int
    {
        return DB::table('financial_years')->insertGetId([
            'description' => 'FY 2026',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'is_closed' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAccount(): int
    {
        return DB::table('accounts')->insertGetId([
            'name' => json_encode(['ar' => 'الصندوق', 'en' => 'Cash Account'], JSON_UNESCAPED_UNICODE),
            'code' => '1101',
            'old_id' => null,
            'level' => '1',
            'parent_account_id' => null,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
    private function insertJournalEntry(array $attributes): int
    {
        return DB::table('journal_entries')->insertGetId(array_merge([
            'serial' => null,
            'journal_date' => '2026-03-22',
            'notes' => null,
            'financial_year' => null,
            'branch_id' => null,
            'journalable_type' => null,
            'journalable_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertJournalEntryDocument(array $attributes): int
    {
        return DB::table('journal_entry_documents')->insertGetId(array_merge([
            'journal_id' => null,
            'account_id' => null,
            'document_date' => '2026-03-22',
            'credit' => 0,
            'debit' => 0,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ], $attributes));
    }
}
