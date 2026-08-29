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

/**
 * الحذف مقصور على الحسابات الفارغة بلا حركة — والمطلوب أن يقول البرنامج
 * السبب بوضوح بدل رسالة قاعدة بيانات إنجليزية.
 */
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

    public function test_an_empty_account_is_deleted_and_its_branch_links_are_released(): void
    {
        [$admin, $branch] = $this->createAdminUser(['employee.accounts.delete']);

        $account = $this->createAccount('حساب فارغ');
        $account->branches()->attach($branch->id);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->delete(route('accounts.delete', $account->id, false));

        $response->assertRedirect(route('accounts.index', [], false));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
        $this->assertSame(0, DB::table('account_branch')->where('account_id', $account->id)->count());
    }

    public function test_an_account_used_elsewhere_is_refused_with_a_readable_reason(): void
    {
        [$admin, $branch] = $this->createAdminUser(['employee.accounts.delete']);

        $account = $this->createAccount('حساب بنك مرتبط');

        DB::table('bank_accounts')->insert([
            'branch_id' => $branch->id,
            'ledger_account_id' => $account->id,
            'account_name' => 'حساب جاري',
            'bank_name' => 'بنك البلاد',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->delete(route('accounts.delete', $account->id, false));

        $response->assertRedirect(route('accounts.index', [], false));
        $response->assertSessionHas('error');

        $error = (string) session('error');
        $this->assertStringContainsString('لا يمكن حذف الحساب لأنه مستخدم في', $error);
        $this->assertStringContainsString('حسابات بنكية', $error);
        $this->assertStringNotContainsString('SQLSTATE', $error, 'لا رسائل قاعدة بيانات في وجه المستخدم');

        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
    }

    public function test_the_index_shows_edit_and_delete_buttons_to_a_permitted_user(): void
    {
        [$admin] = $this->createAdminUser([
            'employee.accounts.show',
            'employee.accounts.edit',
            'employee.accounts.delete',
        ]);

        $account = $this->createAccount('حساب للعرض');

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('accounts.index', [], false));

        $response->assertOk();
        $response->assertSee(route('accounts.edit', $account->id), false);
        $response->assertSee('data-id="' . $account->id . '"', false);
    }

    private function createAccount(string $name): Account
    {
        return Account::create([
            'name' => ['ar' => $name, 'en' => $name],
            'account_type' => 'assets',
            'transfer_side' => 'budget',
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array{0: User, 1: Branch}
     */
    private function createAdminUser(array $permissions = []): array
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
            $role->givePermissionTo(Permission::findOrCreate($permissionName, 'admin-web'));
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

        return [$user, $branch];
    }
}
