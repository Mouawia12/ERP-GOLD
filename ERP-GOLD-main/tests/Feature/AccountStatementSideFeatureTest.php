<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounts\AccountStatementSideResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccountStatementSideFeatureTest extends TestCase
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

    /**
     * @dataProvider balanceSheetLists
     */
    public function test_balance_sheet_lists_resolve_to_budget(string $list): void
    {
        $this->assertSame('budget', app(AccountStatementSideResolver::class)->forList($list));
    }

    /**
     * @dataProvider incomeStatementLists
     */
    public function test_income_statement_lists_resolve_to_income_statement(string $list): void
    {
        $this->assertSame('income_statement', app(AccountStatementSideResolver::class)->forList($list));
    }

    public function test_unspecified_list_resolves_to_unspecified_side(): void
    {
        $resolver = app(AccountStatementSideResolver::class);

        $this->assertSame('not_have', $resolver->forList('not_have'));
        $this->assertSame('not_have', $resolver->forList(null));
    }

    public function test_storing_an_expense_account_sets_the_income_statement_side(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add']);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.store'), [
                'name' => 'مصروفات الكهرباء',
                'type' => 'parent',
                'parent_account_id' => null,
                'accounts_type' => 'expenses',
            ])
            ->assertRedirect(route('accounts.index'));

        $this->assertSame('income_statement', DB::table('accounts')->latest('id')->first()->transfer_side);
    }

    public function test_storing_an_asset_account_sets_the_budget_side(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add']);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.store'), [
                'name' => 'الصندوق',
                'type' => 'parent',
                'parent_account_id' => null,
                'accounts_type' => 'assets',
            ])
            ->assertRedirect(route('accounts.index'));

        $this->assertSame('budget', DB::table('accounts')->latest('id')->first()->transfer_side);
    }

    public function test_a_submitted_side_never_overrides_the_derived_one(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add']);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.store'), [
                'name' => 'ايرادات المبيعات',
                'type' => 'parent',
                'parent_account_id' => null,
                'accounts_type' => 'revenues',
                // قيمة مخالفة للقاعدة تصل من الشاشة أو من طلب مباشر.
                'transfers_side' => 'budget',
            ])
            ->assertRedirect(route('accounts.index'));

        $this->assertSame('income_statement', DB::table('accounts')->latest('id')->first()->transfer_side);
    }

    public function test_changing_the_list_on_edit_moves_the_account_to_the_matching_side(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);

        $accountId = $this->createAccount([
            'code' => '1',
            'level' => '1',
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $accountId), [
                'name' => 'صار مصروفات',
                'type' => 'parent',
                'parent_account_id' => null,
                'accounts_type' => 'expenses',
            ]);

        $account = DB::table('accounts')->find($accountId);
        $this->assertSame('expenses', $account->account_type);
        $this->assertSame('income_statement', $account->transfer_side);
    }

    public function test_the_department_field_is_shown_as_derived_and_not_submitted(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add']);

        $content = $this->actingAs($admin, 'admin-web')
            ->get(route('accounts.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="department" disabled', $content);
        $this->assertStringNotContainsString('name="transfers_side"', $content);
        $this->assertStringContainsString('يُحدَّد تلقائيًا من «قائمة الحساب»', $content);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function balanceSheetLists(): array
    {
        return [['assets'], ['liabilities'], ['equity']];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function incomeStatementLists(): array
    {
        return [['revenues'], ['expenses']];
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
            'name' => ['ar' => 'مدير الأقسام', 'en' => 'Side Admin'],
            'guard_name' => 'admin-web',
        ]);

        foreach ($permissions as $permissionName) {
            $role->givePermissionTo(Permission::findOrCreate($permissionName, 'admin-web'));
        }

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'side-admin@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAccount(array $attributes = []): int
    {
        $name = $attributes['name'] ?? ['ar' => 'حساب اختبار', 'en' => 'Test Account'];
        unset($attributes['name']);

        return DB::table('accounts')->insertGetId(array_merge([
            'name' => json_encode($name, JSON_UNESCAPED_UNICODE),
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
}
