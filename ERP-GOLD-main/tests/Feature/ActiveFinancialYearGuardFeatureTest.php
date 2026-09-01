<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\FinancialYear;
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
 * كل مستند محاسبي يحتاج سنة مالية نشطة. بدونها كانت الشاشات تقرأ `->id` على
 * `null` فتعطي رسالة إنجليزية لا تدل على شيء؛ الآن تُرفض العملية برسالة واضحة
 * ولا يُكتب أي شيء ناقص.
 */
class ActiveFinancialYearGuardFeatureTest extends TestCase
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

    public function test_active_returns_null_when_no_year_is_active(): void
    {
        $this->assertNull(FinancialYear::active());
    }

    public function test_active_or_fail_explains_what_is_missing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('لا توجد سنة مالية نشطة');

        FinancialYear::activeOrFail();
    }

    public function test_active_or_fail_returns_the_active_year(): void
    {
        $id = $this->createFinancialYear();

        $this->assertSame($id, (int) FinancialYear::activeOrFail()->id);
    }

    public function test_active_or_fail_ignores_a_closed_year(): void
    {
        DB::table('financial_years')->insert([
            'description' => 'FY 2025',
            'from' => '2025-01-01',
            'to' => '2025-12-31',
            'is_closed' => true,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull(FinancialYear::active());
    }

    public function test_a_manual_journal_is_refused_and_nothing_is_written_without_an_active_year(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add']);

        $debit = $this->createAccount(['code' => '1101001']);
        $credit = $this->createAccount(['code' => '1102001']);

        $journalsBefore = DB::table('journal_entries')->count();
        $documentsBefore = DB::table('journal_entry_documents')->count();

        $response = $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.journals.store', [], false), [
                'date' => '2026-03-22',
                'notes' => 'قيد بلا سنة مالية',
                'branch_id' => $admin->branch_id,
                'account_id' => [$debit, $credit],
                'debit' => [100, 0],
                'credit' => [0, 100],
            ]);

        $this->assertStringContainsString(
            'لا توجد سنة مالية نشطة',
            (string) $response->json('errors')
        );

        // أهم ما في الأمر: المعاملة رجعت كاملة ولم يُكتب مستند نصف مكتمل.
        $this->assertSame($journalsBefore, DB::table('journal_entries')->count());
        $this->assertSame($documentsBefore, DB::table('journal_entry_documents')->count());
    }

    public function test_the_same_journal_is_saved_once_a_year_is_active(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add']);
        $this->createFinancialYear();

        $debit = $this->createAccount(['code' => '1101001']);
        $credit = $this->createAccount(['code' => '1102001']);

        $this->actingAs($admin, 'admin-web')
            ->post(route('accounts.journals.store', [], false), [
                'date' => '2026-03-22',
                'notes' => 'قيد بسنة مالية نشطة',
                'branch_id' => $admin->branch_id,
                'account_id' => [$debit, $credit],
                'debit' => [100, 0],
                'credit' => [0, 100],
            ]);

        $this->assertSame(1, DB::table('journal_entries')->count());
        $this->assertSame(2, DB::table('journal_entry_documents')->count());
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
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = []): User
    {
        $branch = Branch::create([
            'name' => ['ar' => 'الفرع الرئيسي', 'en' => 'Main Branch'],
            'phone' => '123456789',
        ]);

        $role = Role::create([
            'name' => ['ar' => 'مدير السنة المالية', 'en' => 'Financial Year Admin'],
            'guard_name' => 'admin-web',
        ]);

        foreach ($permissions as $permissionName) {
            $role->givePermissionTo(Permission::findOrCreate($permissionName, 'admin-web'));
        }

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'fy-admin@example.com',
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
}
