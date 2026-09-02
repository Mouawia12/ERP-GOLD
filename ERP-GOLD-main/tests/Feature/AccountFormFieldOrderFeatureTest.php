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

class AccountFormFieldOrderFeatureTest extends TestCase
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

    public function test_account_list_field_comes_before_the_parent_field(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add']);

        $content = $this->actingAs($admin, 'admin-web')
            ->get(route('accounts.create'))
            ->assertOk()
            ->getContent();

        $listPosition = strpos($content, 'id="list"');
        $parentPosition = strpos($content, 'id="parent_id"');

        $this->assertNotFalse($listPosition, 'حقل «قائمة الحساب» غير موجود في الشاشة.');
        $this->assertNotFalse($parentPosition, 'حقل «يصب في» غير موجود في الشاشة.');
        $this->assertLessThan(
            $parentPosition,
            $listPosition,
            'يجب أن تسبق «قائمة الحساب» حقل «يصب في» في الشاشة.'
        );
    }

    public function test_every_parent_option_carries_its_account_list(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add']);

        $expensesId = $this->createAccount([
            'code' => '5',
            'level' => '1',
            'account_type' => 'expenses',
            'name' => ['ar' => 'المصروفات', 'en' => 'Expenses'],
        ]);
        $assetsId = $this->createAccount([
            'code' => '1',
            'level' => '1',
            'account_type' => 'assets',
            'name' => ['ar' => 'الأصول', 'en' => 'Assets'],
        ]);

        $content = $this->actingAs($admin, 'admin-web')
            ->get(route('accounts.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'value="' . $expensesId . '" data-account-type="expenses"',
            $content
        );
        $this->assertStringContainsString(
            'value="' . $assetsId . '" data-account-type="assets"',
            $content
        );
    }

    public function test_root_account_category_is_labelled_main_not_root(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add']);

        $content = $this->actingAs($admin, 'admin-web')
            ->get(route('accounts.create'))
            ->assertOk()
            ->getContent();

        $this->assertSame('رئيسي', __('main.accounts_categories.parent'));
        $this->assertSame('فرعي', __('main.accounts_categories.child'));
        $this->assertStringContainsString('رئيسي', $content);
        $this->assertStringNotContainsString('جذري', $content);
    }

    /**
     * الحساب الجديد يخصّ كل الفروع، فلا معنى لعرض خانة الفروع عند إنشائه —
     * وتبقى في شاشة التعديل لمن أراد قصر الحساب على فرع.
     */
    public function test_branches_field_is_absent_when_creating_an_account(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add']);

        $content = $this->actingAs($admin, 'admin-web')
            ->get(route('accounts.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('name="branch_ids[]"', $content);
    }

    public function test_branches_field_stays_when_editing_an_account(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add', 'employee.accounts.edit']);
        $accountId = $this->createAccount();

        $content = $this->actingAs($admin, 'admin-web')
            ->get(route('accounts.edit', $accountId))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="branch_ids[]"', $content);
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
            'name' => ['ar' => 'مدير الشاشة', 'en' => 'Form Admin'],
            'guard_name' => 'admin-web',
        ]);

        foreach ($permissions as $permissionName) {
            $role->givePermissionTo(Permission::findOrCreate($permissionName, 'admin-web'));
        }

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'form-admin@example.com',
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
