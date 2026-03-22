<?php

namespace Tests\Feature;

use App\Models\Account;
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

class BankAccountsFeatureTest extends TestCase
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

    public function test_authorized_admin_can_create_default_bank_account_and_sync_branch_account_setting(): void
    {
        $admin = $this->createAdminUser([
            'employee.system_settings.show',
            'employee.system_settings.edit',
        ]);

        $branch = Branch::create([
            'name' => ['ar' => 'فرع البنوك', 'en' => 'Bank Branch'],
            'phone' => '123123123',
        ]);

        $ledgerAccount = $this->createAccount('بنك الراجحي', '7001');

        DB::table('account_settings')->insert([
            'branch_id' => $branch->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('admin.system-settings.bank-accounts.store', [], false), [
                'branch_id' => $branch->id,
                'ledger_account_id' => $ledgerAccount->id,
                'account_name' => 'راجحي رئيسي',
                'bank_name' => 'مصرف الراجحي',
                'iban' => 'SA0000000000000000000000',
                'terminal_name' => 'POS-1',
                'supports_credit_card' => '1',
                'supports_bank_transfer' => '1',
                'is_default' => '1',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.system-settings.bank-accounts.index', [], false));

        $this->assertDatabaseHas('bank_accounts', [
            'branch_id' => $branch->id,
            'ledger_account_id' => $ledgerAccount->id,
            'account_name' => 'راجحي رئيسي',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('account_settings', [
            'branch_id' => $branch->id,
            'bank_account' => $ledgerAccount->id,
        ]);
    }

    public function test_bank_accounts_index_lists_saved_bank_accounts(): void
    {
        $admin = $this->createAdminUser([
            'employee.system_settings.show',
        ]);

        $branch = Branch::create([
            'name' => ['ar' => 'فرع العرض', 'en' => 'Display Branch'],
            'phone' => '321321321',
        ]);
        $ledgerAccount = $this->createAccount('البنك الأهلي', '7002');

        DB::table('bank_accounts')->insert([
            'branch_id' => $branch->id,
            'ledger_account_id' => $ledgerAccount->id,
            'account_name' => 'أهلي شبكة',
            'bank_name' => 'البنك الأهلي',
            'supports_credit_card' => true,
            'supports_bank_transfer' => false,
            'is_default' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('admin.system-settings.bank-accounts.index', [], false));

        $response->assertOk();
        $response->assertSee('الحسابات البنكية');
        $response->assertSee('أهلي شبكة');
        $response->assertSee('البنك الأهلي');
    }

    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'الفرع الرئيسي', 'en' => 'Main Branch'],
            'phone' => '111111111',
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
            'name' => 'Bank Admin',
            'email' => 'bank-admin-'.uniqid().'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createAccount(string $name, string $code): Account
    {
        return Account::create([
            'name' => $name,
            'code' => $code,
        ]);
    }
}
