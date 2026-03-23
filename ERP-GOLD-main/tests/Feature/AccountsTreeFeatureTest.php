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

class AccountsTreeFeatureTest extends TestCase
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

    public function test_accounts_index_displays_accounting_summary_and_recursive_tree(): void
    {
        $admin = $this->createAdminUser([
            'employee.accounts.show',
        ]);
        $financialYearId = $this->createFinancialYear();

        $assetsId = $this->createAccount([
            'name' => ['ar' => 'الأصول', 'en' => 'Assets'],
            'code' => '1000',
            'level' => '1',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);
        $cashId = $this->createAccount([
            'name' => ['ar' => 'الصندوق', 'en' => 'Cash'],
            'code' => '100001',
            'level' => '2',
            'parent_account_id' => $assetsId,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);
        $drawerId = $this->createAccount([
            'name' => ['ar' => 'صندوق الفرع', 'en' => 'Branch Drawer'],
            'code' => '1000011',
            'level' => '3',
            'parent_account_id' => $cashId,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);
        $this->createAccount([
            'name' => ['ar' => 'الالتزامات', 'en' => 'Liabilities'],
            'code' => '2000',
            'level' => '1',
            'account_type' => 'liabilities',
            'transfer_side' => 'budget',
        ]);

        DB::table('opening_balances')->insert([
            'financial_year' => $financialYearId,
            'account_id' => $drawerId,
            'debit' => 250,
            'credit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $manualJournalId = DB::table('journal_entries')->insertGetId([
            'serial' => 'MJ-1-00001',
            'journal_date' => '2026-03-22',
            'notes' => 'Manual journal',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
            'journalable_type' => null,
            'journalable_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_entries')->insert([
            'serial' => 'J-1-00001',
            'journal_date' => '2026-03-22',
            'notes' => 'Transaction journal',
            'financial_year' => $financialYearId,
            'branch_id' => $admin->branch_id,
            'journalable_type' => 'App\\Models\\Invoice',
            'journalable_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_entry_documents')->insert([
            'journal_id' => $manualJournalId,
            'account_id' => $drawerId,
            'document_date' => '2026-03-22',
            'credit' => 0,
            'debit' => 150,
            'notes' => 'Manual document',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('accounts.index', [], false));

        $response->assertOk();
        $response->assertSee('قسم المحاسبة');
        $response->assertSee('إجمالي الحسابات');
        $response->assertSee('الأصول');
        $response->assertSee('الصندوق');
        $response->assertSee('صندوق الفرع');
        $response->assertSee('L1');
        $response->assertSee('L2');
        $response->assertSee('L3');
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['total_accounts'] === 4
                && $stats['root_accounts'] === 2
                && $stats['leaf_accounts'] === 2
                && $stats['max_level'] === 3
                && $stats['accounts_with_opening_balance'] === 1
                && $stats['manual_journals_count'] === 1
                && $stats['transaction_journals_count'] === 1
                && $stats['journal_documents_count'] === 1;
        });
        $response->assertViewHas('roots', function ($roots) use ($assetsId, $cashId, $drawerId) {
            $assets = $roots->firstWhere('id', $assetsId);

            return $assets
                && $assets->childrensRecursive->count() === 1
                && optional($assets->childrensRecursive->first())->id === $cashId
                && optional($assets->childrensRecursive->first()->childrensRecursive->first())->id === $drawerId;
        });
    }

    public function test_delete_route_blocks_parent_accounts_and_deletes_safe_leaf_accounts(): void
    {
        $admin = $this->createAdminUser([
            'employee.accounts.show',
            'employee.accounts.delete',
        ]);

        $parentId = $this->createAccount([
            'name' => ['ar' => 'الأصول', 'en' => 'Assets'],
            'code' => '1000',
            'level' => '1',
        ]);
        $childId = $this->createAccount([
            'name' => ['ar' => 'الصندوق', 'en' => 'Cash'],
            'code' => '100001',
            'level' => '2',
            'parent_account_id' => $parentId,
        ]);
        $leafId = $this->createAccount([
            'name' => ['ar' => 'عهدة إدارية', 'en' => 'Administrative Float'],
            'code' => '3000',
            'level' => '1',
        ]);

        $blockedResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('accounts.delete', $parentId, false));

        $blockedResponse->assertRedirect(route('accounts.index', [], false));
        $blockedResponse->assertSessionHas('error');
        $this->assertDatabaseHas('accounts', [
            'id' => $parentId,
        ]);
        $this->assertDatabaseHas('accounts', [
            'id' => $childId,
        ]);

        $deletedResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('accounts.delete', $leafId, false));

        $deletedResponse->assertRedirect(route('accounts.index', [], false));
        $deletedResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('accounts', [
            'id' => $leafId,
        ]);
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
            'name' => ['ar' => 'مدير النظام', 'en' => 'System Admin'],
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
            'name' => 'Admin User',
            'email' => 'admin@example.com',
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
    private function createAccount(array $attributes): int
    {
        return DB::table('accounts')->insertGetId(array_merge([
            'name' => json_encode(['ar' => 'حساب', 'en' => 'Account'], JSON_UNESCAPED_UNICODE),
            'code' => '1000',
            'old_id' => null,
            'level' => '1',
            'parent_account_id' => null,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
            'created_at' => now(),
            'updated_at' => now(),
        ], [
            'name' => json_encode($attributes['name'] ?? ['ar' => 'حساب', 'en' => 'Account'], JSON_UNESCAPED_UNICODE),
        ], collect($attributes)->except('name')->all()));
    }
}
