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

class AccountRenumberingFeatureTest extends TestCase
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

    public function test_renaming_an_account_keeps_its_code_and_level(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);
        $assetsId = $this->createAccount(['code' => '1', 'level' => '1']);
        $cashId = $this->createAccount([
            'code' => '11',
            'level' => '2',
            'parent_account_id' => $assetsId,
        ]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $cashId), $this->payload('الصندوق الرئيسي', $assetsId))
            ->assertRedirect(route('accounts.index'));

        $cash = DB::table('accounts')->find($cashId);
        $this->assertSame('11', $cash->code);
        $this->assertSame('2', (string) $cash->level);
        $this->assertStringContainsString('الصندوق الرئيسي', $cash->name);
    }

    public function test_moving_an_account_renumbers_it_under_the_new_parent(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);
        $assetsId = $this->createAccount(['code' => '1', 'level' => '1']);
        $liabilitiesId = $this->createAccount(['code' => '2', 'level' => '1']);

        $cashId = $this->createAccount([
            'code' => '11',
            'level' => '2',
            'parent_account_id' => $assetsId,
        ]);
        $this->createAccount([
            'code' => '21',
            'level' => '2',
            'parent_account_id' => $liabilitiesId,
        ]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $cashId), $this->payload('الصندوق', $liabilitiesId))
            ->assertRedirect(route('accounts.index'));

        $cash = DB::table('accounts')->find($cashId);

        // صار ثاني أبناء «الخصوم» فأخذ الكود 22 بدل 11.
        $this->assertSame('22', $cash->code);
        $this->assertSame('2', (string) $cash->level);
        $this->assertSame((string) $liabilitiesId, (string) $cash->parent_account_id);
    }

    public function test_moving_an_account_renumbers_its_whole_subtree(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);
        $assetsId = $this->createAccount(['code' => '1', 'level' => '1']);
        $liabilitiesId = $this->createAccount(['code' => '2', 'level' => '1']);

        $cashId = $this->createAccount([
            'code' => '11',
            'level' => '2',
            'parent_account_id' => $assetsId,
        ]);
        $drawerId = $this->createAccount([
            'code' => '1101',
            'level' => '3',
            'parent_account_id' => $cashId,
        ]);
        $tillId = $this->createAccount([
            'code' => '1101001',
            'level' => '4',
            'parent_account_id' => $drawerId,
        ]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $cashId), $this->payload('الصندوق', $liabilitiesId));

        $this->assertSame('21', DB::table('accounts')->find($cashId)->code);
        $this->assertSame('2101', DB::table('accounts')->find($drawerId)->code);
        $this->assertSame('2101001', DB::table('accounts')->find($tillId)->code);

        $this->assertSame('3', (string) DB::table('accounts')->find($drawerId)->level);
        $this->assertSame('4', (string) DB::table('accounts')->find($tillId)->level);
    }

    public function test_moving_an_account_out_closes_the_gap_it_left_behind(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);
        $assetsId = $this->createAccount(['code' => '1', 'level' => '1']);
        $liabilitiesId = $this->createAccount(['code' => '2', 'level' => '1']);

        $firstId = $this->createAccount(['code' => '11', 'level' => '2', 'parent_account_id' => $assetsId]);
        $secondId = $this->createAccount(['code' => '12', 'level' => '2', 'parent_account_id' => $assetsId]);
        $thirdId = $this->createAccount(['code' => '13', 'level' => '2', 'parent_account_id' => $assetsId]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $firstId), $this->payload('المنقول', $liabilitiesId));

        // بقي إخوته يتسلسلون 11 و12 بدون فجوة.
        $this->assertSame('11', DB::table('accounts')->find($secondId)->code);
        $this->assertSame('12', DB::table('accounts')->find($thirdId)->code);
        $this->assertSame('21', DB::table('accounts')->find($firstId)->code);
    }

    public function test_moving_an_account_to_root_gives_it_a_root_code(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);
        $assetsId = $this->createAccount(['code' => '1', 'level' => '1']);
        $cashId = $this->createAccount([
            'code' => '11',
            'level' => '2',
            'parent_account_id' => $assetsId,
        ]);
        $childId = $this->createAccount([
            'code' => '1101',
            'level' => '3',
            'parent_account_id' => $cashId,
        ]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $cashId), $this->payload('الصندوق', null));

        $this->assertSame('2', DB::table('accounts')->find($cashId)->code);
        $this->assertSame('1', (string) DB::table('accounts')->find($cashId)->level);
        $this->assertSame('21', DB::table('accounts')->find($childId)->code);
        $this->assertSame('2', (string) DB::table('accounts')->find($childId)->level);
    }

    public function test_account_cannot_be_moved_under_its_own_descendant(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);
        $assetsId = $this->createAccount(['code' => '1', 'level' => '1']);
        $cashId = $this->createAccount([
            'code' => '11',
            'level' => '2',
            'parent_account_id' => $assetsId,
        ]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $assetsId), $this->payload('الأصول', $cashId));

        $this->assertStringContainsString('تحت نفسه', (string) session('error'));

        $assets = DB::table('accounts')->find($assetsId);
        $this->assertNull($assets->parent_account_id);
        $this->assertSame('1', $assets->code);
    }

    public function test_account_cannot_be_moved_under_itself(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.edit']);
        $assetsId = $this->createAccount(['code' => '1', 'level' => '1']);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.update', $assetsId), $this->payload('الأصول', $assetsId));

        $this->assertStringContainsString('تحت نفسه', (string) session('error'));
        $this->assertNull(DB::table('accounts')->find($assetsId)->parent_account_id);
    }

    public function test_code_preview_keeps_current_code_when_parent_is_unchanged(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add', 'employee.accounts.edit']);
        $assetsId = $this->createAccount(['code' => '1', 'level' => '1']);
        $cashId = $this->createAccount([
            'code' => '11',
            'level' => '2',
            'parent_account_id' => $assetsId,
        ]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.excepted_code'), [
                'parent_id' => $assetsId,
                'account_id' => $cashId,
            ])
            ->assertOk()
            ->assertJson(['code' => '11']);
    }

    public function test_code_preview_returns_next_code_under_a_new_parent(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add', 'employee.accounts.edit']);
        $assetsId = $this->createAccount(['code' => '1', 'level' => '1']);
        $liabilitiesId = $this->createAccount(['code' => '2', 'level' => '1']);

        $cashId = $this->createAccount([
            'code' => '11',
            'level' => '2',
            'parent_account_id' => $assetsId,
        ]);
        $this->createAccount([
            'code' => '21',
            'level' => '2',
            'parent_account_id' => $liabilitiesId,
        ]);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.excepted_code'), [
                'parent_id' => $liabilitiesId,
                'account_id' => $cashId,
            ])
            ->assertOk()
            ->assertJson(['code' => '22']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $name, ?int $parentId): array
    {
        return [
            'name' => $name,
            'parent_account_id' => $parentId,
            'accounts_type' => 'assets',
            'transfers_side' => 'budget',
        ];
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
            'name' => ['ar' => 'مدير الترقيم', 'en' => 'Renumbering Admin'],
            'guard_name' => 'admin-web',
        ]);

        foreach ($permissions as $permissionName) {
            $role->givePermissionTo(Permission::findOrCreate($permissionName, 'admin-web'));
        }

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'renumber-admin@example.com',
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
        $name = $attributes['name'] ?? ['ar' => 'حساب اختبار ' . uniqid(), 'en' => 'Test Account'];
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
