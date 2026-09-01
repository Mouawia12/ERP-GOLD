<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\Accounts\SubscriberChartProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * «التزامات» و«خصوم» اسمان لنفس القائمة، فكل موضع يذكر أحدهما يذكر الآخر بين
 * قوسين حتى لا يبحث المحاسب عن قائمة يظنها مفقودة.
 */
class LiabilitiesWordingFeatureTest extends TestCase
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

    public function test_the_account_list_label_names_both_words(): void
    {
        $label = __('main.accounts_types.liabilities');

        $this->assertStringContainsString('التزامات', $label);
        $this->assertStringContainsString('خصوم', $label);
        $this->assertSame('التزامات (خصوم)', $label);
    }

    public function test_the_old_liabilities_typo_is_gone(): void
    {
        $this->assertStringNotContainsString(
            'التزمات',
            __('main.accounts_types.liabilities'),
            'كانت التسمية مكتوبة «التزمات» بحرف ناقص.'
        );
    }

    public function test_the_account_form_offers_both_words_in_the_list_field(): void
    {
        $admin = $this->createAdminUser(['employee.accounts.add']);

        $content = $this->actingAs($admin, 'admin-web')
            ->get(route('accounts.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('التزامات (خصوم)', $content);
        $this->assertStringContainsString('الالتزامات (الخصوم)', $content);
    }

    public function test_a_new_subscriber_chart_names_liabilities_with_both_words(): void
    {
        $subscriber = Subscriber::create([
            'name' => 'مشترك التسمية',
            'login_email' => 'wording-subscriber@example.com',
            'status' => true,
        ]);

        app(SubscriberChartProvisioner::class)->ensureProvisioned($subscriber);

        $root = DB::table('accounts')
            ->where('subscriber_id', $subscriber->id)
            ->where('code', '2')
            ->first();

        $this->assertNotNull($root, 'جذر الخصوم غير موجود في الشجرة الافتراضية.');
        $this->assertStringContainsString('الخصوم', $root->name);
        $this->assertStringContainsString('الالتزامات', $root->name);
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
            'name' => ['ar' => 'مدير التسمية', 'en' => 'Wording Admin'],
            'guard_name' => 'admin-web',
        ]);

        foreach ($permissions as $permissionName) {
            $role->givePermissionTo(Permission::findOrCreate($permissionName, 'admin-web'));
        }

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'wording-admin@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return $user;
    }
}
