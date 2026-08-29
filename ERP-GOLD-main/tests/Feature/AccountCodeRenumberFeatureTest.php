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

class AccountCodeRenumberFeatureTest extends TestCase
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

    public function test_changing_the_parent_recodes_the_account_and_its_children(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);

        $assets = $this->createAccount('الأصول');
        $liabilities = $this->createAccount('الخصوم');
        $cash = $this->createAccount('الصندوق', $assets);
        $drawer = $this->createAccount('درج الفرع', $cash);

        $this->assertSame('1', $assets->code);
        $this->assertSame('2', $liabilities->code);
        $this->assertSame('11', $cash->code);
        $this->assertSame('1101', $drawer->code);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $cash->id, false), [
                'name' => 'الصندوق',
                'parent_account_id' => $liabilities->id,
                'accounts_type' => 'liabilities',
                'transfers_side' => 'budget',
            ]);

        $response->assertRedirect(route('accounts.index', [], false));

        $cash->refresh();
        $drawer->refresh();

        $this->assertSame($liabilities->id, (int) $cash->parent_account_id);
        $this->assertSame('21', $cash->code);
        $this->assertSame('2', $cash->level);
        $this->assertSame('2101', $drawer->code, 'الحسابات الفرعية يجب أن تتبع كود أبيها الجديد');
        $this->assertSame('3', $drawer->level);
    }

    public function test_update_without_the_parent_field_keeps_the_account_in_place(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);

        $assets = $this->createAccount('الأصول');
        $cash = $this->createAccount('الصندوق', $assets);

        $this
            ->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $cash->id, false), [
                'name' => 'الصندوق الرئيسي',
                'accounts_type' => 'assets',
                'transfers_side' => 'budget',
            ])
            ->assertRedirect(route('accounts.index', [], false));

        $cash->refresh();

        $this->assertSame($assets->id, (int) $cash->parent_account_id, 'حقل الأب غير المرسل يجب ألّا يحوّل الحساب إلى جذر');
        $this->assertSame('11', $cash->code);
    }

    public function test_edit_form_never_disables_the_parent_select(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add', 'employee.accounts.edit']);

        $assets = $this->createAccount('الأصول');

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('accounts.edit', $assets->id, false));

        $response->assertOk();

        preg_match('/<select[^>]*id="parent_id".*?<\/select>/s', $response->getContent(), $matches);

        $this->assertNotEmpty($matches, 'قائمة الحساب الأب غير موجودة في الشاشة');
        $this->assertStringNotContainsString(
            'disabled',
            $matches[0],
            'قائمة معطّلة تعني أن الحقل لا يُرسل، فيبدو الحفظ وكأنه لم ينفّذ'
        );
        $this->assertStringContainsString('بدون أب', $matches[0]);
    }

    public function test_a_root_account_can_be_given_a_parent(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);

        $assets = $this->createAccount('الأصول');
        $liabilities = $this->createAccount('الخصوم');

        $this
            ->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $liabilities->id, false), [
                'name' => 'الخصوم',
                'parent_account_id' => $assets->id,
                'accounts_type' => 'liabilities',
                'transfers_side' => 'budget',
            ])
            ->assertRedirect(route('accounts.index', [], false));

        $liabilities->refresh();

        $this->assertSame($assets->id, (int) $liabilities->parent_account_id);
        $this->assertSame('11', $liabilities->code);
        $this->assertSame('2', $liabilities->level);
    }

    public function test_a_child_account_can_become_a_root_again(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);

        $assets = $this->createAccount('الأصول');
        $cash = $this->createAccount('الصندوق', $assets);

        $this
            ->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $cash->id, false), [
                'name' => 'الصندوق',
                'parent_account_id' => '',
                'accounts_type' => 'assets',
                'transfers_side' => 'budget',
            ])
            ->assertRedirect(route('accounts.index', [], false));

        $cash->refresh();

        $this->assertNull($cash->parent_account_id);
        $this->assertSame('2', $cash->code);
        $this->assertSame('1', $cash->level);
    }

    public function test_saving_an_account_repairs_a_code_left_over_from_its_old_family(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);

        $assets = $this->createAccount('الأصول');
        $cash = $this->createAccount('الصندوق', $assets);
        $drawer = $this->createAccount('درج الفرع', $cash);

        // محاكاة حساب نُقل قبل الإصلاح: أبوه صحيح وكوده من موضعه القديم.
        DB::table('accounts')->where('id', $drawer->id)->update(['code' => '67', 'level' => '2']);

        $this
            ->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $drawer->id, false), [
                'name' => 'درج الفرع',
                'parent_account_id' => $cash->id,
                'accounts_type' => 'assets',
                'transfers_side' => 'budget',
            ])
            ->assertRedirect(route('accounts.index', [], false));

        $drawer->refresh();

        $this->assertSame($cash->id, (int) $drawer->parent_account_id);
        $this->assertSame('1101', $drawer->code, 'الحفظ يصحّح كودًا لا يطابق موضع الحساب');
        $this->assertSame('3', $drawer->level);
    }

    public function test_saving_an_account_with_a_valid_code_leaves_it_alone(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);

        $assets = $this->createAccount('الأصول');
        $cash = $this->createAccount('الصندوق', $assets);

        $this
            ->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $cash->id, false), [
                'name' => 'الصندوق الرئيسي',
                'parent_account_id' => $assets->id,
                'accounts_type' => 'assets',
                'transfers_side' => 'budget',
            ])
            ->assertRedirect(route('accounts.index', [], false));

        $this->assertSame('11', $cash->refresh()->code, 'تعديل الاسم وحده لا يغيّر كودًا سليمًا');
    }

    public function test_account_cannot_be_moved_under_its_own_child(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);

        $assets = $this->createAccount('الأصول');
        $cash = $this->createAccount('الصندوق', $assets);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $assets->id, false), [
                'name' => 'الأصول',
                'parent_account_id' => $cash->id,
                'accounts_type' => 'assets',
                'transfers_side' => 'budget',
            ]);

        $response->assertSessionHas('error');

        $assets->refresh();
        $this->assertNull($assets->parent_account_id);
        $this->assertSame('1', $assets->code);
    }

    public function test_renumber_command_gives_liabilities_code_two(): void
    {
        $assets = $this->createAccount('الأصول', null, 'assets');
        $stray = $this->createAccount('صندوق تائه', null, 'not_have');
        $liabilities = $this->createAccount('الخصوم', null, 'liabilities');
        $equity = $this->createAccount('حقوق الملكية', null, 'equity');
        $suppliers = $this->createAccount('الموردين', $liabilities, 'liabilities');

        $this->assertSame('3', $liabilities->code);

        $this->artisan('accounts:renumber --apply')->assertSuccessful();

        $this->assertSame('1', $assets->refresh()->code);
        $this->assertSame('2', $liabilities->refresh()->code);
        $this->assertSame('3', $equity->refresh()->code);
        $this->assertSame('4', $stray->refresh()->code, 'الجذر غير المعروف النوع يأتي بعد المجموعات المعيارية');
        $this->assertSame('21', $suppliers->refresh()->code);
    }

    public function test_renumber_command_is_idempotent(): void
    {
        $assets = $this->createAccount('الأصول', null, 'assets');
        $this->createAccount('الصندوق', $assets, 'assets');
        $this->createAccount('الخصوم', null, 'liabilities');

        $this->artisan('accounts:renumber --apply')->assertSuccessful();
        $first = Account::query()->orderBy('id')->pluck('code', 'id')->all();

        $this->artisan('accounts:renumber --apply')->assertSuccessful();
        $second = Account::query()->orderBy('id')->pluck('code', 'id')->all();

        $this->assertSame($first, $second);
    }

    private function createAccount(string $name, ?Account $parent = null, string $type = 'assets'): Account
    {
        return Account::create([
            'name' => ['ar' => $name, 'en' => $name],
            'parent_account_id' => $parent?->id,
            'account_type' => $type,
            'transfer_side' => $type === 'revenues' || $type === 'expenses' ? 'income_statement' : 'budget',
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

        return $user;
    }
}
