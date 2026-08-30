<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Customer;
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

class AccountDeletionFeatureTest extends TestCase
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

    public function test_empty_account_is_deleted_after_confirmation(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.delete']);
        $accountId = $this->createAccount(['code' => '110001']);

        $response = $this->actingAs($admin, 'admin-web')
            ->delete(route('accounts.delete', $accountId));

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('accounts', ['id' => $accountId]);
    }

    public function test_account_with_journal_entry_documents_cannot_be_deleted(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.delete']);
        $accountId = $this->createAccount(['code' => '110002']);
        $this->createJournalEntryDocument($accountId, $admin);

        $response = $this->actingAs($admin, 'admin-web')
            ->delete(route('accounts.delete', $accountId));

        $response->assertRedirect(route('accounts.index'));
        $this->assertStringContainsString('عليه حركة', (string) session('error'));
        $this->assertStringContainsString('قيود يومية', (string) session('error'));
        $this->assertDatabaseHas('accounts', ['id' => $accountId]);
    }

    public function test_account_with_opening_balance_cannot_be_deleted(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.delete']);
        $accountId = $this->createAccount(['code' => '110003']);

        DB::table('opening_balances')->insert([
            'account_id' => $accountId,
            'financial_year' => $this->createFinancialYear(),
            'debit' => 500,
            'credit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->delete(route('accounts.delete', $accountId));

        $this->assertStringContainsString('عليه حركة', (string) session('error'));
        $this->assertDatabaseHas('accounts', ['id' => $accountId]);
    }

    public function test_account_linked_to_a_customer_cannot_be_deleted(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.delete']);
        $accountId = $this->createAccount(['code' => '110004']);

        Customer::create([
            'name' => 'عميل مرتبط',
            'phone' => '0512345678',
            'type' => 'customer',
            'account_id' => $accountId,
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->delete(route('accounts.delete', $accountId));

        $this->assertStringContainsString('مرتبط', (string) session('error'));
        $this->assertDatabaseHas('accounts', ['id' => $accountId]);
        $this->assertDatabaseHas('customers', ['account_id' => $accountId]);
    }

    public function test_parent_account_with_children_cannot_be_deleted(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.delete']);
        $parentId = $this->createAccount(['code' => '110005']);
        $this->createAccount([
            'code' => '1100051',
            'level' => '2',
            'parent_account_id' => $parentId,
        ]);

        $response = $this->actingAs($admin, 'admin-web')
            ->delete(route('accounts.delete', $parentId));

        $this->assertStringContainsString('حسابات فرعية', (string) session('error'));
        $this->assertDatabaseHas('accounts', ['id' => $parentId]);
    }

    public function test_user_without_delete_permission_cannot_delete_account(): void
    {
        $admin = $this->createAdminUser([]);
        $accountId = $this->createAccount(['code' => '110006']);

        $this->actingAs($admin, 'admin-web')
            ->delete(route('accounts.delete', $accountId))
            ->assertForbidden();

        $this->assertDatabaseHas('accounts', ['id' => $accountId]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'الفرع الرئيسي', 'en' => 'Main Branch'],
            'phone' => '123456789',
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مدير الحذف', 'en' => 'Deletion Admin'],
            'guard_name' => 'admin-web',
        ]);

        foreach ($permissions as $permissionName) {
            $role->givePermissionTo(Permission::findOrCreate($permissionName, 'admin-web'));
        }

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'delete-admin@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAccount(array $attributes = []): int
    {
        return DB::table('accounts')->insertGetId(array_merge([
            'name' => json_encode(['ar' => 'حساب اختبار', 'en' => 'Test Account'], JSON_UNESCAPED_UNICODE),
            'code' => '1000',
            'old_id' => null,
            'level' => '1',
            'parent_account_id' => null,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function createJournalEntryDocument(int $accountId, User $user): void
    {
        $journalEntryId = DB::table('journal_entries')->insertGetId([
            'serial' => 'JE-1',
            'journal_date' => now()->toDateString(),
            'financial_year' => $this->createFinancialYear(),
            'branch_id' => $user->branch_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_entry_documents')->insert([
            'journal_id' => $journalEntryId,
            'account_id' => $accountId,
            'debit' => 100,
            'credit' => 0,
            'document_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
